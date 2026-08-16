<?php

namespace Tests\Unit;

use App\Services\GeminiService;
use App\Services\LLMInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'test-gemini-key',
            'services.gemini.base_url' => 'https://gemini.test',
            'chatbot.api_version' => 'v1beta',
            'chatbot.models.chat' => 'models/gemini-test',
            'chatbot.models.embed' => 'models/embedding-test',
            'chatbot.gemini.embedding_dimension' => 3,
            'chatbot.gemini.google_search' => false,
            'chatbot.history_messages' => 8,
            'chatbot.llm.max_output_tokens' => 256,
            'chatbot.llm.retry' => 0,
        ]);
    }

    public function test_it_requests_a_configured_embedding_with_header_auth(): void
    {
        Http::fake([
            'https://gemini.test/v1beta/models/embedding-test:embedContent'
                => Http::response([
                    'embedding' => ['values' => [0.1, 0.2, 0.3]],
                ]),
        ]);

        $embedding = (new GeminiService())->getEmbedding('kamera CCTV');

        $this->assertSame([0.1, 0.2, 0.3], $embedding);
        Http::assertSent(function (Request $request) {
            return $request->url()
                    === 'https://gemini.test/v1beta/models/embedding-test:embedContent'
                && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
                && !str_contains($request->url(), 'test-gemini-key')
                && $request['content']['parts'][0]['text'] === 'kamera CCTV'
                && $request['outputDimensionality'] === 3;
        });
    }

    public function test_chat_uses_native_system_instruction_and_history(): void
    {
        Http::fake([
            'https://gemini.test/v1beta/models/gemini-test:generateContent'
                => Http::response([
                    'candidates' => [[
                        'content' => [
                            'parts' => [['text' => 'Jawaban Gemini.']],
                        ],
                        'finishReason' => 'STOP',
                    ]],
                ]),
        ]);

        $result = (new GeminiService())->chat(
            'Berapa stoknya?',
            ['Produk NVR memiliki stok tercatat 8 unit.'],
            [
                ['role' => 'user', 'content' => 'Ada NVR?'],
                ['role' => 'assistant', 'content' => 'Ada NVR 8 Channel.'],
                ['role' => 'assistant', 'content' => ''],
            ]
        );

        $this->assertSame('Jawaban Gemini.', $result->text);
        $this->assertTrue($result->successful);
        $this->assertFalse($result->usedWeb);
        $this->assertSame([], $result->sources);

        Http::assertSent(function (Request $request) {
            $contents = $request['contents'];

            return isset($request['system_instruction']['parts'][0]['text'])
                && $contents[0]['role'] === 'user'
                && $contents[0]['parts'][0]['text'] === 'Ada NVR?'
                && $contents[1]['role'] === 'model'
                && $contents[1]['parts'][0]['text'] === 'Ada NVR 8 Channel.'
                && $contents[2]['role'] === 'user'
                && str_contains(
                    $contents[2]['parts'][0]['text'],
                    '<company_database_context>'
                )
                && $request['generationConfig'] === [
                    'maxOutputTokens' => 256,
                ]
                && !isset($request['tools']);
        });
    }

    public function test_optional_google_search_preserves_sources_contract(): void
    {
        config(['chatbot.gemini.google_search' => true]);
        Http::fake([
            'https://gemini.test/v1beta/models/gemini-test:generateContent'
                => Http::response([
                    'candidates' => [[
                        'content' => [
                            'parts' => [['text' => 'Informasi terbaru.']],
                        ],
                        'groundingMetadata' => [
                            'groundingChunks' => [[
                                'web' => [
                                    'title' => 'Sumber resmi',
                                    'uri' => 'https://example.com/source',
                                ],
                            ]],
                        ],
                    ]],
                ]),
        ]);

        $result = (new GeminiService())->chat('Informasi terbaru?');

        $this->assertTrue($result->usedWeb);
        $this->assertSame([[
            'title' => 'Sumber resmi',
            'url' => 'https://example.com/source',
        ]], $result->sources);
        Http::assertSent(
            fn (Request $request) => array_key_exists(
                'google_search',
                $request['tools'][0] ?? []
            )
        );
    }

    public function test_invalid_key_failure_is_not_retried(): void
    {
        config(['chatbot.llm.retry' => 2]);
        Http::fake([
            '*' => Http::response([
                'error' => [
                    'code' => 401,
                    'status' => 'UNAUTHENTICATED',
                    'message' => 'Invalid API key.',
                ],
            ], 401),
        ]);

        $result = (new GeminiService())->chat('Halo');

        $this->assertFalse($result->successful);
        Http::assertSentCount(1);
    }

    public function test_missing_key_fails_with_a_clear_message(): void
    {
        config(['services.gemini.key' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY is missing');

        new GeminiService();
    }

    public function test_container_resolves_gemini_as_the_only_provider(): void
    {
        config(['chatbot.provider' => 'unused-provider']);
        $this->app->forgetInstance(LLMInterface::class);

        $this->assertInstanceOf(
            GeminiService::class,
            $this->app->make(LLMInterface::class)
        );
    }

    public function test_health_command_reports_models_and_dimension(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), ':embedContent')) {
                return Http::response([
                    'embedding' => ['values' => [0.1, 0.2, 0.3]],
                ]);
            }

            return Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [['text' => 'GEMINI_OK']],
                    ],
                ]],
            ]);
        });

        $this->app->forgetInstance(LLMInterface::class);

        $this->artisan('chatbot:test-gemini')
            ->expectsOutput('Gemini API: OK')
            ->expectsOutput('Chat model: gemini-test')
            ->expectsOutput('Embedding model: embedding-test')
            ->expectsOutput('Embedding dimension: 3')
            ->expectsOutput('Response: GEMINI_OK')
            ->assertSuccessful();
    }
}
