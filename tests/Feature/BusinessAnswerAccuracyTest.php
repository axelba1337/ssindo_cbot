<?php

namespace Tests\Feature;

use App\Services\ChatResult;
use App\Services\LLMInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BusinessAnswerAccuracyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->app->instance(LLMInterface::class, new class implements LLMInterface
        {
            public function getEmbedding(string $text): ?array
            {
                return null;
            }

            public function chat(
                string $prompt,
                array $context = [],
                array $history = [],
                array $options = [],
            ): ChatResult {
                return ChatResult::failure();
            }
        });

        DB::table('services')->insert([
            [
                'name' => 'Pemasangan CCTV',
                'description' => 'Instalasi kamera dan konfigurasi keamanan.',
                'is_active' => true,
                'price' => null,
            ],
            [
                'name' => 'Jaringan Internet',
                'description' => 'Setup LAN/WiFi kantor dan sekolah.',
                'is_active' => true,
                'price' => null,
            ],
            [
                'name' => 'Software Development',
                'description' => 'Pembuatan aplikasi web/mobile.',
                'is_active' => true,
                'price' => null,
            ],
            [
                'name' => 'Maintenance IT',
                'description' => 'Perawatan dan dukungan teknis dasar.',
                'is_active' => true,
                'price' => null,
            ],
        ]);
        DB::table('products')->insert([
            [
                'name' => 'Kamera Bullet 4MP',
                'sku' => 'CCTV-BUL-4MP',
                'stock' => 20,
                'unit' => 'pcs',
                'price' => null,
            ],
            [
                'name' => 'UPS 650VA',
                'sku' => 'UPS-650VA',
                'stock' => 7,
                'unit' => 'pcs',
                'price' => null,
            ],
        ]);
        DB::table('company_contacts')->insert([
            'type' => 'whatsapp',
            'value' => '+6281335117054',
            'is_primary' => true,
        ]);
        DB::table('office_hours')->insert([
            'open_time' => '09:00:00',
            'close_time' => '17:00:00',
            'timezone' => 'Asia/Jakarta',
            'note' => 'Buka setiap hari 09:00-17:00 WIB',
        ]);
    }

    public function test_cctv_and_networking_answers_use_active_services(): void
    {
        $cctv = $this->postJson('/api/chatbot/query', [
            'message' => 'Saya ingin konsultasi pemasangan CCTV.',
        ]);
        $network = $this->postJson('/api/chatbot/query', [
            'message' => 'Saya membutuhkan instalasi jaringan kantor.',
        ]);

        $cctv->assertOk()->assertJsonPath('source', 'service');
        $network->assertOk()->assertJsonPath('source', 'service');
        $this->assertStringContainsString(
            'Pemasangan CCTV',
            $cctv->json('answer')
        );
        $this->assertStringContainsString(
            'Jaringan Internet',
            $network->json('answer')
        );
    }

    public function test_software_solutions_are_not_treated_as_physical_inventory(): void
    {
        $indonesian = $this->postJson('/api/chatbot/query', [
            'message' => 'Apakah Neev menyediakan SIMPUS, HRMS, atau POS?',
        ]);
        $softwareIndonesian = $this->postJson('/api/chatbot/query', [
            'message' => 'Apakah Neev membuat software custom?',
        ]);
        $english = $this->postJson('/api/chatbot/query', [
            'message' => 'Can Neev build a mobile application?',
        ]);

        $indonesian->assertOk()->assertJsonPath('source', 'service');
        $softwareIndonesian->assertOk()->assertJsonPath('source', 'service');
        $english->assertOk()->assertJsonPath('source', 'service');
        $this->assertStringContainsString(
            'bukan stok barang fisik',
            $indonesian->json('answer')
        );
        $this->assertStringStartsWith(
            'Ya.',
            $softwareIndonesian->json('answer')
        );
        $this->assertStringContainsString(
            'not physical-stock items',
            $english->json('answer')
        );
    }

    public function test_product_availability_and_unknown_price_use_catalog_data(): void
    {
        $available = $this->postJson('/api/chatbot/query', [
            'message' => 'Apakah UPS tersedia?',
        ]);
        $unknown = $this->postJson('/api/chatbot/query', [
            'message' => 'Berapa harga printer?',
        ]);

        $available->assertOk()->assertJsonPath('source', 'product');
        $unknown->assertOk()->assertJsonPath('source', 'product');
        $this->assertStringContainsString('UPS 650VA', $available->json('answer'));
        $this->assertStringNotContainsString(
            'perangkat CCTV',
            $available->json('answer')
        );
        $this->assertStringContainsString(
            'belum tercatat dalam katalog',
            $unknown->json('answer')
        );
        $this->assertStringNotContainsString('Rp', $unknown->json('answer'));
    }

    public function test_office_hours_and_whatsapp_are_grounded_and_bilingual(): void
    {
        $hoursId = $this->postJson('/api/chatbot/query', [
            'message' => 'Jam operasional Neev kapan?',
        ]);
        $hoursEn = $this->postJson('/api/chatbot/query', [
            'message' => 'What are Neev office hours?',
        ]);
        $whatsappEn = $this->postJson('/api/chatbot/query', [
            'message' => 'What is your WhatsApp number?',
        ]);

        $hoursId->assertOk()->assertJsonPath('source', 'office_hours');
        $hoursEn->assertOk()->assertJsonPath('source', 'office_hours');
        $whatsappEn->assertOk()
            ->assertJsonPath('source', 'contact')
            ->assertJsonPath(
                'cta_whatsapp.link',
                'https://wa.me/6281335117054'
            );
        $this->assertStringContainsString('09:00-17:00', $hoursId->json('answer'));
        $this->assertStringContainsString(
            "Neev's recorded office hours",
            $hoursEn->json('answer')
        );
        $this->assertStringContainsString(
            'You can contact the admin',
            $whatsappEn->json('answer')
        );
    }

    private function createTables(): void
    {
        Schema::create('text_normalization_rules', function (Blueprint $table) {
            $table->id();
            $table->string('pattern');
            $table->string('replacement');
            $table->string('rule_type')->default('replace');
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
        });
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('user_identifier');
            $table->dateTime('started_at');
            $table->text('meta')->nullable();
        });
        Schema::create('chatbot_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->text('user_message');
            $table->text('bot_answer');
            $table->string('source');
            $table->decimal('similarity', 10, 8)->nullable();
            $table->text('top_matches')->nullable();
            $table->dateTime('created_at');
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku');
            $table->integer('stock');
            $table->string('unit');
            $table->decimal('price', 15, 2)->nullable();
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('price', 15, 2)->nullable();
        });
        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value');
            $table->boolean('is_primary')->default(false);
        });
        Schema::create('office_hours', function (Blueprint $table) {
            $table->id();
            $table->time('open_time');
            $table->time('close_time');
            $table->string('timezone');
            $table->text('note')->nullable();
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
