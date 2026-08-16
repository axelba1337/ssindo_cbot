<?php

namespace Tests\Unit;

use App\Services\QueryRouter;
use PHPUnit\Framework\TestCase;

class QueryRouterTest extends TestCase
{
    private QueryRouter $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = new QueryRouter();
    }

    public function test_general_questions_skip_company_retrieval(): void
    {
        $this->assertFalse(
            $this->router->usesCompanyData('can you speak english')
        );
        $this->assertFalse(
            $this->router->usesCompanyData('what is the capital of france')
        );
        $this->assertFalse(
            $this->router->usesCompanyData('latest printer models in 2026')
        );
    }

    public function test_company_questions_use_company_retrieval(): void
    {
        $this->assertTrue(
            $this->router->usesCompanyData('berapa harga nvr neev')
        );
        $this->assertTrue(
            $this->router->usesCompanyData('jual printer')
        );
        $this->assertTrue(
            $this->router->usesCompanyData('kamera tidak menampilkan gambar')
        );
    }

    public function test_price_follow_up_uses_recent_product_context(): void
    {
        $history = [
            ['role' => 'user', 'content' => 'Saya mencari produk NVR.'],
            ['role' => 'assistant', 'content' => 'NVR 8 channel tersedia.'],
        ];

        $this->assertTrue(
            $this->router->usesCompanyData('berapa harganya?', $history)
        );
        $this->assertTrue(
            $this->router->usesCompanyData('Speknya bagaimana?', $history)
        );
    }
}
