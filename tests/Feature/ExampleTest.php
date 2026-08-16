<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_homepage_renders_with_chatbot_and_redirect_modal(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Kembangkan bisnis Anda')
            ->assertSee('id="redirect-modal"', false)
            ->assertSee('id="cbot-launcher"', false)
            ->assertSee('id="cbot"', false);
    }

    public function test_existing_chatbot_and_admin_routes_still_render(): void
    {
        $this->get('/chatbot')->assertOk();
        $this->get('/admin')->assertOk();
        $this->get('/admin/faq')->assertOk();
        $this->get('/admin/unanswered')->assertOk();
    }
}
