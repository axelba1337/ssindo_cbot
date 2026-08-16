<?php

namespace Tests\Unit;

use App\Services\LLMInterface;
use App\Services\OpenAIService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenAIServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.openai.key' => 'test-openai-key',
            'services.openai.base_url' => 'https://api.openai.test/v1',
            'chatbot.openai.chat_model' => 'gpt-test',
            'chatbot.openai.embed_model' => 'embedding-test',
            'chatbot.openai.store' => false,
            'chatbot.openai.web_search.enabled' => true,
            'chatbot.openai.web_search.live' => true,
            'chatbot.openai.web_search.context_size' => 'medium',
            'chatbot.history_messages' => 8,
            'chatbot.llm.retry' => 0,
        ]);
    }

    public function test_it_requests_an_embedding_with_bearer_authentication(): void
    {
        Http::fake([
            'https://api.openai.test/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => [0.1, 0.2, 0.3]],
                ],
            ]),
        ]);

        $embedding = app(OpenAIService::class)->getEmbedding('kamera CCTV');

        $this->assertSame([0.1, 0.2, 0.3], $embedding);
        Http::assertSent(function (Request $request) {
            return $request->url() === 'https://api.openai.test/v1/embeddings'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && $request['model'] === 'embedding-test'
                && $request['input'] === 'kamera CCTV';
        });
    }

    public function test_it_collects_text_from_a_responses_api_message(): void
    {
        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'output' => [
                    ['type' => 'reasoning', 'summary' => []],
                    [
                        'type' => 'message',
                        'content' => [
                            [
                                'type' => 'output_text',
                                'text' => 'Jawaban OpenAI.',
                                'annotations' => [[
                                    'type' => 'url_citation',
                                    'title' => 'Example source',
                                    'url' => 'https://example.com/source',
                                ]],
                            ],
                        ],
                    ],
                    ['type' => 'web_search_call', 'status' => 'completed'],
                ],
            ]),
        ]);

        $result = app(OpenAIService::class)->chat(
            'Apa layanan yang tersedia?',
            ['Instalasi CCTV tersedia.'],
            [
                ['role' => 'user', 'content' => 'Saya perlu CCTV.'],
                ['role' => 'assistant', 'content' => 'Untuk lokasi apa?'],
            ],
            ['safety_identifier' => 'chatbot-test']
        );

        $this->assertSame('Jawaban OpenAI.', $result->text);
        $this->assertTrue($result->successful);
        $this->assertTrue($result->usedWeb);
        $this->assertSame([
            [
                'title' => 'Example source',
                'url' => 'https://example.com/source',
            ],
        ], $result->sources);

        Http::assertSent(function (Request $request) {
            $input = $request['input'];

            return $request->url() === 'https://api.openai.test/v1/responses'
                && $request['model'] === 'gpt-test'
                && $request['store'] === false
                && $request['tools'] === [[
                    'type' => 'web_search',
                    'search_context_size' => 'medium',
                    'external_web_access' => true,
                ]]
                && $request['tool_choice'] === 'auto'
                && $request['safety_identifier'] === 'chatbot-test'
                && collect($input)->contains(
                    fn ($message) => str_contains(
                        $message['content'],
                        'Instalasi CCTV tersedia.'
                    )
                )
                && collect($input)->contains(
                    fn ($message) => $message['role'] === 'assistant'
                        && $message['content'] === 'Untuk lokasi apa?'
                );
        });
    }

    public function test_chat_returns_a_failure_result_when_openai_rejects_the_request(): void
    {
        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'error' => ['message' => 'Quota exceeded'],
            ], 429),
        ]);

        $result = app(OpenAIService::class)->chat('Can you speak English?');

        $this->assertFalse($result->successful);
        $this->assertFalse($result->usedWeb);
        $this->assertSame([], $result->sources);
    }

    public function test_quota_failure_is_not_retried(): void
    {
        config(['chatbot.llm.retry' => 2]);
        Http::fake([
            'https://api.openai.test/v1/responses' => Http::response([
                'error' => [
                    'code' => 'insufficient_quota',
                    'type' => 'insufficient_quota',
                    'message' => 'You exceeded your current quota.',
                ],
            ], 429),
        ]);

        $result = app(OpenAIService::class)->chat('General question');

        $this->assertFalse($result->successful);
        Http::assertSentCount(1);
    }

    public function test_the_container_resolves_the_configured_provider(): void
    {
        config(['chatbot.provider' => 'openai']);
        $this->app->forgetInstance(LLMInterface::class);

        $this->assertInstanceOf(
            OpenAIService::class,
            $this->app->make(LLMInterface::class)
        );
    }
}
