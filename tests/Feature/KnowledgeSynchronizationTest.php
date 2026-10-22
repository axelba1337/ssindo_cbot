<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ChatResult;
use App\Services\KnowledgeBuilder;
use App\Services\LLMInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KnowledgeSynchronizationTest extends TestCase
{
    private object $fakeLlm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        config(['chatbot.gemini.embedding_dimension' => 3]);

        $this->fakeLlm = new class implements LLMInterface
        {
            public int $embeddingCalls = 0;

            public function getEmbedding(string $text): ?array
            {
                $this->embeddingCalls++;

                return [0.1, 0.2, 0.3];
            }

            public function chat(
                string $prompt,
                array $context = [],
                array $history = [],
                array $options = [],
            ): ChatResult {
                return ChatResult::failure();
            }
        };
        $this->app->instance(LLMInterface::class, $this->fakeLlm);

        $admin = new User;
        $admin->forceFill([
            'id' => 1,
            'name' => 'Knowledge Admin',
            'email' => 'knowledge-admin@example.test',
            'is_admin' => true,
        ]);
        $admin->exists = true;
        $this->actingAs($admin);
    }

    public function test_sync_reuses_valid_embeddings_and_repairs_bad_dimensions(): void
    {
        DB::table('product_categories')->insert([
            'id' => 1,
            'code' => 'CCTV',
            'name' => 'CCTV',
        ]);
        DB::table('products')->insert([
            'id' => 1,
            'category_id' => 1,
            'sku' => 'CCTV-BUL-4MP',
            'name' => 'Kamera Bullet 4MP',
            'description' => 'Kamera outdoor night vision',
            'unit' => 'pcs',
            'stock' => 20,
            'reorder_level' => 5,
            'price' => null,
        ]);
        DB::table('chatbot_knowledge')->insert([
            'kind' => 'product',
            'title' => 'Produk: Kamera Bullet 4MP',
            'content_text' => "Nama: Kamera Bullet 4MP\n"
                ."SKU: CCTV-BUL-4MP\n"
                ."Kategori: CCTV (CCTV)\n"
                ."Deskripsi: Kamera outdoor night vision\n"
                ."Unit: pcs; Stok: 20; Reorder: 5\n"
                .'Harga: silakan hubungi admin.',
            'source_table' => 'products',
            'source_id' => 1,
            'embedding' => json_encode([0.1, 0.2, 0.3]),
            'updated_at' => now(),
        ]);

        app(KnowledgeBuilder::class)->buildProducts();
        $this->assertSame(0, $this->fakeLlm->embeddingCalls);

        DB::table('chatbot_knowledge')->where('source_id', 1)->update([
            'embedding' => json_encode([0.1, 0.2]),
        ]);
        app(KnowledgeBuilder::class)->buildProducts();
        $this->assertSame(1, $this->fakeLlm->embeddingCalls);
        $this->assertCount(
            3,
            json_decode(
                DB::table('chatbot_knowledge')->value('embedding'),
                true
            )
        );

        DB::table('products')->where('id', 1)->update(['stock' => 19]);
        app(KnowledgeBuilder::class)->buildProducts();
        $this->assertSame(1, $this->fakeLlm->embeddingCalls);
        $this->assertStringContainsString(
            'Stok: 19',
            DB::table('chatbot_knowledge')->value('content_text')
        );
    }

    public function test_prune_removes_deleted_and_inactive_sources_only(): void
    {
        DB::table('products')->insert([
            'id' => 1,
            'sku' => 'UPS-650',
            'name' => 'UPS 650VA',
            'unit' => 'pcs',
            'stock' => 7,
            'reorder_level' => 2,
        ]);
        DB::table('services')->insert([
            ['id' => 1, 'name' => 'Maintenance IT', 'is_active' => true],
            ['id' => 2, 'name' => 'Layanan Lama', 'is_active' => false],
        ]);
        DB::table('faq_qa')->insert([
            [
                'id' => 1,
                'question' => 'FAQ aktif',
                'answer' => 'Jawaban aktif',
                'is_active' => true,
            ],
            [
                'id' => 2,
                'question' => 'FAQ nonaktif',
                'answer' => 'Jawaban nonaktif',
                'is_active' => false,
            ],
        ]);

        foreach ([
            ['product', 'products', 1],
            ['product', 'products', 999],
            ['service', 'services', 1],
            ['service', 'services', 2],
            ['faq', 'faq_qa', 1],
            ['faq', 'faq_qa', 2],
            ['faq', 'faq_qa', 999],
            ['manual', 'manual_notes', 999],
        ] as [$kind, $table, $sourceId]) {
            DB::table('chatbot_knowledge')->insert([
                'kind' => $kind,
                'title' => "{$table}:{$sourceId}",
                'content_text' => 'test',
                'source_table' => $table,
                'source_id' => $sourceId,
                'embedding' => null,
                'updated_at' => now(),
            ]);
        }

        $removed = app(KnowledgeBuilder::class)->pruneOrphans();

        $this->assertSame(4, $removed);
        $this->assertDatabaseHas('chatbot_knowledge', [
            'source_table' => 'products',
            'source_id' => 1,
        ]);
        $this->assertDatabaseHas('chatbot_knowledge', [
            'source_table' => 'services',
            'source_id' => 1,
        ]);
        $this->assertDatabaseHas('chatbot_knowledge', [
            'source_table' => 'faq_qa',
            'source_id' => 1,
        ]);
        $this->assertDatabaseHas('chatbot_knowledge', [
            'source_table' => 'manual_notes',
            'source_id' => 999,
        ]);
    }

    public function test_faq_create_update_deactivate_and_delete_stay_synchronized(): void
    {
        $created = $this->postJson('/api/admin/faq', [
            'intent' => 'network_service',
            'question' => 'Apakah ada instalasi jaringan?',
            'answer' => 'Ya, tersedia instalasi jaringan.',
        ])->assertOk();
        $faqId = (int) $created->json('id');

        $this->assertSame(1, $this->fakeLlm->embeddingCalls);
        $this->assertDatabaseHas('chatbot_knowledge', [
            'source_table' => 'faq_qa',
            'source_id' => $faqId,
        ]);

        $this->postJson('/api/admin/faq', [
            'id' => $faqId,
            'intent' => 'network_service',
            'question' => 'Apakah ada instalasi jaringan?',
            'answer' => 'Ya, tersedia instalasi jaringan.',
        ])->assertOk();
        $this->assertSame(1, $this->fakeLlm->embeddingCalls);

        $this->postJson('/api/admin/faq', [
            'id' => $faqId,
            'intent' => 'network_service',
            'question' => 'Apakah ada instalasi jaringan?',
            'answer' => 'Ya, tersedia instalasi jaringan.',
            'is_active' => false,
        ])->assertOk();
        $this->assertDatabaseMissing('chatbot_knowledge', [
            'source_table' => 'faq_qa',
            'source_id' => $faqId,
        ]);

        $second = $this->postJson('/api/admin/faq', [
            'question' => 'FAQ yang akan dihapus',
            'answer' => 'Jawaban sementara',
        ])->assertOk();
        $secondId = (int) $second->json('id');
        $callsBeforeDelete = $this->fakeLlm->embeddingCalls;

        $this->deleteJson("/api/admin/faq/{$secondId}")->assertOk();
        $this->assertSame($callsBeforeDelete, $this->fakeLlm->embeddingCalls);
        $this->assertDatabaseMissing('faq_qa', ['id' => $secondId]);
        $this->assertDatabaseMissing('chatbot_knowledge', [
            'source_table' => 'faq_qa',
            'source_id' => $secondId,
        ]);
    }

    private function createTables(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('name');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('sku');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit');
            $table->integer('stock');
            $table->integer('reorder_level')->default(0);
            $table->decimal('price', 15, 2)->nullable();
        });
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 15, 2)->nullable();
        });
        Schema::create('office_hours', function (Blueprint $table) {
            $table->id();
            $table->time('open_time');
            $table->time('close_time');
            $table->string('timezone');
            $table->text('note')->nullable();
        });
        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value');
            $table->boolean('is_primary')->default(false);
        });
        Schema::create('faq_qa', function (Blueprint $table) {
            $table->id();
            $table->string('intent')->nullable();
            $table->text('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('chatbot_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('title');
            $table->text('content_text');
            $table->string('source_table')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->text('embedding')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }
}
