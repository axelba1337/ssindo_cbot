<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class MobileDeploymentReadinessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        URL::forceRootUrl('https://demo.example.test');
        // Exercise the production manifest without deleting the developer's hot file.
        Vite::useHotFile(storage_path('framework/mobile-test-no-dev-server.hot'));
    }

    public function test_public_pages_render_https_assets_without_a_dev_server(): void
    {
        foreach (['/', '/chatbot', '/chatbot/widget'] as $path) {
            $response = $this->get('https://demo.example.test'.$path);
            $response->assertOk()->assertSee('viewport-fit=cover', false);

            $dom = new DOMDocument;
            @$dom->loadHTML($response->getContent());
            $xpath = new DOMXPath($dom);
            $assets = $xpath->query('//img[@src] | //script[@src] | //link[@rel="stylesheet" or @rel="icon"]');
            $this->assertGreaterThan(0, $assets->length);

            foreach ($assets as $node) {
                $url = $node->getAttribute($node->hasAttribute('src') ? 'src' : 'href');
                $this->assertSame('https', parse_url($url, PHP_URL_SCHEME));
                $this->assertSame('demo.example.test', parse_url($url, PHP_URL_HOST));
                $assetPath = public_path(ltrim(parse_url($url, PHP_URL_PATH), '/'));
                $this->assertFileExists($assetPath);
                if ($node->tagName === 'img') {
                    if (strtolower(pathinfo($assetPath, PATHINFO_EXTENSION)) === 'svg') {
                        $svg = new DOMDocument;
                        $this->assertTrue($svg->load($assetPath, LIBXML_NONET));
                        $this->assertSame('svg', $svg->documentElement?->localName);
                    } else {
                        $this->assertNotFalse(getimagesize($assetPath));
                    }
                }
            }

            $response->assertDontSee('@vite/client')->assertDontSee(':5173');
        }
    }

    public function test_homepage_retains_all_products_slides_and_contextual_actions(): void
    {
        $html = $this->get('/')->assertOk()->getContent();
        $dom = new DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $this->assertSame(8, $xpath->query('//article[contains(concat(" ", normalize-space(@class), " "), " product-item ")]')->length);
        $this->assertSame(2, $xpath->query('//img[@data-hero-slide]')->length);
        $this->assertSame(6, $xpath->query('//button[@data-neev-assistant-question]')->length);
        $this->assertSame(1, $xpath->query('//button[@id="cbot-launcher"]')->length);
    }

    public function test_admin_tables_have_focusable_local_scroll_regions(): void
    {
        $admin = new User;
        $admin->forceFill(['id' => 1, 'name' => 'UI Test Admin', 'email' => 'ui@example.test', 'is_admin' => true]);
        $admin->exists = true;
        $this->actingAs($admin);

        foreach (['/admin/faq', '/admin/unanswered'] as $path) {
            $this->get($path)->assertOk()
                ->assertSee('class="table-scroll" role="region"', false)
                ->assertSee('tabindex="0"', false);
        }
    }
}
