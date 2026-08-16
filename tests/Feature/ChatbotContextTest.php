<?php

namespace Tests\Feature;

use App\Services\ChatResult;
use App\Services\LLMInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatbotContextTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTables();
        $this->app->instance(LLMInterface::class, new class implements LLMInterface {
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

        DB::table('products')->insert([
            'name' => 'Kamera Bullet 4MP',
            'sku' => 'CCTV-BUL-4MP',
            'stock' => 20,
            'unit' => 'pcs',
            'price' => 1250000,
        ]);
    }

    public function test_sales_question_uses_live_product_data(): void
    {
        $response = $this->postJson('/api/chatbot/query', [
            'message' => 'jual cctv ga?',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'product')
            ->assertJsonPath('used_web', false)
            ->assertJsonFragment(['answer' => $response->json('answer')]);

        $this->assertStringContainsString(
            'Ya, Neev menjual perangkat CCTV',
            $response->json('answer')
        );
        $this->assertStringContainsString(
            'Kamera Bullet 4MP',
            $response->json('answer')
        );
        $this->assertStringNotContainsString(
            'Periksa daya kamera',
            $response->json('answer')
        );
    }

    public function test_generic_technical_help_asks_for_the_actual_symptom(): void
    {
        $response = $this->postJson('/api/chatbot/query', [
            'message' => 'Butuh bantuan teknis',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'assistant');

        $this->assertStringContainsString(
            'Kendalanya apa',
            $response->json('answer')
        );
        $this->assertStringNotContainsString(
            'pasar beli jambu',
            $response->json('answer')
        );
    }

    public function test_language_question_is_answered_without_external_ai(): void
    {
        $response = $this->postJson('/api/chatbot/query', [
            'message' => 'Can you speak English?',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'assistant')
            ->assertJsonPath(
                'answer',
                'Yes, I can speak English. How can I help you?'
            );
    }

    public function test_unknown_product_is_checked_against_the_live_catalog(): void
    {
        $response = $this->postJson('/api/chatbot/query', [
            'message' => 'Do you sell printer?',
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'product')
            ->assertJsonPath('used_web', false);

        $this->assertStringContainsString(
            'Printer is not currently listed',
            $response->json('answer')
        );
        $this->assertStringContainsString(
            'Kamera Bullet 4MP',
            $response->json('answer')
        );
        $this->assertStringNotContainsString(
            'AI eksternal',
            $response->json('answer')
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

        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value');
            $table->boolean('is_primary')->default(false);
        });
    }
}
