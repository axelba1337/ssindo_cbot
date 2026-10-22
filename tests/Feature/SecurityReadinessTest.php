<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ChatResult;
use App\Services\GeminiService;
use App\Services\LLMInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SecurityReadinessTest extends TestCase
{
    private object $fakeLlm;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(str_repeat('k', 32))]);
        $this->createTables();

        $this->fakeLlm = new class implements LLMInterface
        {
            public array $histories = [];

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
                $this->histories[] = $history;

                return new ChatResult('Mocked Gemini response.');
            }
        };
        $this->app->instance(LLMInterface::class, $this->fakeLlm);
    }

    public function test_guests_cannot_access_admin_pages_or_apis(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/faq')->assertRedirect('/admin/login');
        $this->get('/admin/unanswered')->assertRedirect('/admin/login');

        $this->getJson('/api/admin/faq')->assertUnauthorized();
        $this->postJson('/api/admin/faq', [])->assertUnauthorized();
        $this->deleteJson('/api/admin/faq/1')->assertUnauthorized();
        $this->getJson('/api/admin/unanswered')->assertUnauthorized();
        $this->getJson('/api/admin/metrics/today')->assertUnauthorized();
    }

    public function test_authenticated_non_admin_is_forbidden(): void
    {
        $this->actingAs($this->createUser(false));

        $this->get('/admin')->assertForbidden();
        $this->get('/admin/login')->assertOk();
        $this->getJson('/api/admin/faq')->assertForbidden();
        $this->postJson('/api/admin/faq', [
            'question' => 'Tidak boleh dibuat?',
            'answer' => 'Tidak.',
        ])->assertForbidden();
    }

    public function test_guest_admin_mutation_returns_json_401_before_csrf(): void
    {
        $this->app->instance('env', 'production');

        try {
            $this->postJson('/api/admin/faq', [
                'question' => 'Tidak boleh dibuat?',
                'answer' => 'Tidak.',
            ])->assertUnauthorized()->assertJson([
                'message' => 'Unauthenticated.',
            ]);
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_admin_can_log_in_manage_faq_and_log_out(): void
    {
        $admin = $this->createUser(true, 'admin@example.test');

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'VeryStrongPassword123!',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($admin);

        $created = $this->postJson('/api/admin/faq', [
            'intent' => 'network_support',
            'question' => 'Apakah tersedia dukungan jaringan?',
            'answer' => 'Ya, konsultasikan kebutuhan jaringan dengan admin.',
        ])->assertOk();

        $faqId = (int) $created->json('id');
        $this->getJson('/api/admin/faq')->assertOk()
            ->assertJsonFragment(['id' => $faqId]);
        $this->deleteJson("/api/admin/faq/{$faqId}")->assertOk();

        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
    }

    public function test_admin_login_is_limited_by_failed_account_and_ip_attempts(): void
    {
        config([
            'security.rate_limits.login_failures_per_minute' => 2,
            'security.rate_limits.login_decay_seconds' => 60,
        ]);

        $credentials = [
            'email' => 'missing@example.test',
            'password' => 'WrongPassword123!',
        ];

        $this->post('/admin/login', $credentials)->assertRedirect();
        $this->post('/admin/login', $credentials)->assertRedirect();
        $this->post('/admin/login', $credentials)->assertStatus(429)
            ->assertHeader('Retry-After');
    }

    public function test_csrf_is_enforced_for_admin_mutations(): void
    {
        $this->actingAs($this->createUser(true));
        $this->app->instance('env', 'production');

        try {
            $payload = [
                'question' => 'Apakah CSRF aktif?',
                'answer' => 'Ya.',
            ];

            $this->postJson('/api/admin/faq', $payload)->assertStatus(419);

            $this->withSession(['_token' => 'known-csrf-token'])
                ->withHeader('X-CSRF-TOKEN', 'known-csrf-token')
                ->postJson('/api/admin/faq', $payload)
                ->assertOk();
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_public_chatbot_uses_an_opaque_token_without_admin_login(): void
    {
        $response = $this->postJson('/api/chatbot/query', [
            'message' => 'Can you speak English?',
        ])->assertOk()->assertJsonPath('source', 'assistant');

        $token = $response->json('session_token');
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $token);
        $this->assertSame($token, $response->json('session_id'));

        $storedIdentifier = (string) DB::table('chatbot_sessions')->value('user_identifier');
        $this->assertStringStartsWith('v2:', $storedIdentifier);
        $this->assertNotSame($token, $storedIdentifier);
        $this->assertGuest();
    }

    public function test_numeric_or_invalid_session_credentials_are_rejected_safely(): void
    {
        $first = $this->postJson('/api/chatbot/query', [
            'message' => 'Can you speak English?',
        ])->assertOk();

        $internalId = (int) DB::table('chatbot_sessions')->value('id');
        $logCount = DB::table('chatbot_logs')->count();

        $this->postJson('/api/chatbot/query', [
            'message' => 'Read another conversation',
            'session_id' => $internalId,
        ])->assertUnprocessable()->assertJsonValidationErrors('session_id');

        $this->postJson('/api/chatbot/query', [
            'message' => 'Invalid token test',
            'session_token' => 'not-a-valid-token',
        ])->assertUnprocessable()->assertJsonValidationErrors('session_token');

        $this->assertSame($logCount, DB::table('chatbot_logs')->count());
        $this->assertNotEmpty($first->json('session_token'));
    }

    public function test_valid_token_preserves_only_its_conversation_context(): void
    {
        $first = $this->postJson('/api/chatbot/query', [
            'message' => 'Explain recursion briefly.',
        ])->assertOk();
        $token = $first->json('session_token');

        $second = $this->postJson('/api/chatbot/query', [
            'message' => 'Can you elaborate?',
            'session_token' => $token,
        ])->assertOk();

        $this->assertSame($token, $second->json('session_token'));
        $this->assertSame(1, DB::table('chatbot_sessions')->count());
        $this->assertCount(2, $this->fakeLlm->histories);
        $this->assertSame([], $this->fakeLlm->histories[0]);
        $this->assertSame(
            'Explain recursion briefly.',
            $this->fakeLlm->histories[1][0]['content']
        );
        $this->assertSame(
            'Mocked Gemini response.',
            $this->fakeLlm->histories[1][1]['content']
        );
    }

    public function test_chatbot_message_length_is_limited(): void
    {
        $this->postJson('/api/chatbot/query', [
            'message' => str_repeat('a', 2001),
        ])->assertUnprocessable()->assertJsonValidationErrors('message');
    }

    public function test_public_widget_events_are_validated_and_bounded(): void
    {
        $this->postJson('/api/chatbot/events', [
            'type' => '<script>alert(1)</script>',
        ])->assertUnprocessable()->assertJsonValidationErrors('type');

        $this->postJson('/api/chatbot/events', [
            'type' => 'rating',
            'rating' => 5,
        ])->assertStatus(202)->assertJson(['ok' => true]);
    }

    public function test_chatbot_rate_limit_returns_http_429(): void
    {
        config([
            'security.rate_limits.chatbot_per_minute' => 2,
            'security.rate_limits.chatbot_per_hour' => 100,
        ]);

        $payload = ['message' => 'Can you speak English?'];
        $this->postJson('/api/chatbot/query', $payload)->assertOk();
        $this->postJson('/api/chatbot/query', $payload)->assertOk();
        $this->postJson('/api/chatbot/query', $payload)->assertStatus(429);
    }

    public function test_security_headers_and_development_route_are_production_safe(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Content-Security-Policy');

        $this->getJson('/api/chatbot/dev/models')->assertNotFound();
        $this->get('/register')->assertNotFound();
    }

    public function test_untrusted_forwarded_ip_cannot_spoof_rate_limit_identity(): void
    {
        TrustProxies::flushState();

        try {
            $request = Request::create(
                'https://example.test/api/chatbot/query',
                'POST',
                server: [
                    'REMOTE_ADDR' => '203.0.113.10',
                    'HTTP_X_FORWARDED_FOR' => '198.51.100.25',
                ]
            );

            $response = (new TrustProxies)->handle(
                $request,
                fn (Request $request) => response()->json([
                    'ip' => $request->ip(),
                ])
            );

            $this->assertSame(
                '203.0.113.10',
                json_decode($response->getContent(), true)['ip']
            );
        } finally {
            $trustedProxies = config('security.trusted_proxies', []);
            if ($trustedProxies !== []) {
                TrustProxies::at($trustedProxies);
            }
        }
    }

    public function test_production_chatbot_errors_do_not_expose_gemini_details(): void
    {
        $this->app->forgetInstance(LLMInterface::class);
        $this->app->singleton(LLMInterface::class, GeminiService::class);
        $this->app->instance('env', 'production');
        config([
            'app.debug' => false,
            'services.gemini.key' => '',
        ]);

        try {
            $response = $this->postJson('/api/chatbot/query', [
                'message' => 'Explain deployment readiness.',
            ])->assertStatus(500);

            $response->assertJson(['message' => 'Server Error']);
            $this->assertStringNotContainsString(
                'GEMINI_API_KEY',
                $response->getContent()
            );
            $this->assertStringNotContainsString(
                'RuntimeException',
                $response->getContent()
            );
        } finally {
            $this->app->instance('env', 'testing');
        }
    }

    public function test_homepage_contains_all_contextual_demo_actions(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Saya ingin konsultasi pemasangan CCTV.')
            ->assertSee('Saya membutuhkan instalasi jaringan kantor.')
            ->assertSee('Saya ingin membuat aplikasi untuk bisnis.')
            ->assertSee('Saya membutuhkan bantuan maintenance IT.');
    }

    public function test_contextual_actions_reuse_widget_without_auto_submission(): void
    {
        $homeScript = file_get_contents(
            public_path('assets/landing/js/home.js')
        );
        $shellScript = file_get_contents(
            public_path('assets/landing/js/chatbot-widget-shell.js')
        );
        $widgetScript = file_get_contents(
            public_path('assets/landing/js/chatbot-widget.js')
        );

        $this->assertStringContainsString(
            '[data-neev-assistant-question]',
            $homeScript
        );
        $this->assertStringContainsString('neev:assistant-open', $homeScript);
        $this->assertStringNotContainsString('/api/chatbot/query', $homeScript);
        $this->assertStringContainsString('input.value = message', $shellScript);
        $this->assertStringContainsString(
            "fetch('/api/chatbot/query'",
            $widgetScript
        );
    }

    private function createUser(
        bool $isAdmin,
        string $email = 'user@example.test'
    ): User {
        $user = User::query()->create([
            'name' => $isAdmin ? 'Admin User' : 'Regular User',
            'email' => $email,
            'password' => 'VeryStrongPassword123!',
        ]);
        $user->is_admin = $isAdmin;
        $user->save();

        return $user;
    }

    private function createTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
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
