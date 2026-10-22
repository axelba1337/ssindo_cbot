<?php

namespace Tests\Unit;

use App\Services\KnowledgeRetriever;
use Tests\TestCase;

class KnowledgeRetrieverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'chatbot.threshold' => 0.45,
            'chatbot.lexical_threshold' => 0.65,
            'chatbot.top_k' => 5,
        ]);
    }

    public function test_faq_answer_words_cannot_hijack_an_unrelated_query(): void
    {
        $rows = [
            $this->row(
                1,
                'faq',
                'FAQ: greeting',
                "Intent: greeting\nPertanyaan: hello\n"
                    .'Jawaban: Kalau butuh bantuan teknisi saya siap membantu.'
            ),
            $this->row(
                2,
                'faq',
                'FAQ: troubleshoot_cctv',
                "Intent: troubleshoot_cctv\n"
                    ."Pertanyaan: Kamera tidak menampilkan gambar\n"
                    .'Jawaban: Hubungi teknisi.'
            ),
        ];

        $matches = app(KnowledgeRetriever::class)->rank(
            $rows,
            null,
            'butuh bantuan teknisi'
        );

        $this->assertSame([], $matches);
    }

    public function test_lexical_faq_match_requires_the_question_to_be_relevant(): void
    {
        $rows = [
            $this->row(
                2,
                'faq',
                'FAQ: troubleshoot_cctv',
                "Intent: troubleshoot_cctv\n"
                    ."Pertanyaan: Kamera saya tidak menampilkan gambar\n"
                    .'Jawaban: Periksa daya dan kabel.'
            ),
        ];

        $match = app(KnowledgeRetriever::class)->rank(
            $rows,
            null,
            'kamera saya tidak menampilkan gambar'
        )[0];

        $this->assertSame(2, $match['id']);
        $this->assertSame('lexical', $match['retrieval']);
        $this->assertTrue(
            app(KnowledgeRetriever::class)->isRelevant($match)
        );
    }

    public function test_product_data_ranks_above_an_unrelated_faq(): void
    {
        $rows = [
            $this->row(
                2,
                'faq',
                'FAQ: troubleshoot_cctv',
                "Intent: troubleshoot_cctv\n"
                    ."Pertanyaan: Kamera tidak menampilkan gambar\n"
                    .'Jawaban: Periksa daya dan kabel.'
            ),
            $this->row(
                3,
                'product',
                'Produk: Kamera Bullet 4MP',
                "Nama: Kamera Bullet 4MP\nSKU: CCTV-BUL-4MP\n"
                    .'Kategori: CCTV'
            ),
        ];

        $matches = app(KnowledgeRetriever::class)->rank(
            $rows,
            null,
            'jual cctv tidak'
        );

        $this->assertSame(3, $matches[0]['id']);
        $this->assertSame('product', $matches[0]['kind']);
    }

    public function test_lexical_fallback_survives_mixed_embedding_dimensions(): void
    {
        $semanticRow = $this->row(
            1,
            'service',
            'Layanan: CCTV',
            'Pemasangan CCTV',
            [0.0, 1.0, 0.0]
        );
        $lexicalRow = $this->row(
            2,
            'service',
            'Layanan: Software Development',
            'Pembuatan aplikasi web mobile untuk bisnis.',
            [1.0, 0.0]
        );

        $matches = app(KnowledgeRetriever::class)->rank(
            [$semanticRow, $lexicalRow],
            [1.0, 0.0, 0.0],
            'butuh aplikasi mobile bisnis'
        );

        $this->assertSame(2, $matches[0]['id']);
        $this->assertSame('lexical', $matches[0]['retrieval']);
        $this->assertTrue(
            app(KnowledgeRetriever::class)->isRelevant($matches[0])
        );
    }

    private function row(
        int $id,
        string $kind,
        string $title,
        string $content,
        ?array $embedding = null
    ): object {
        return (object) [
            'id' => $id,
            'kind' => $kind,
            'title' => $title,
            'content_text' => $content,
            'embedding' => $embedding,
        ];
    }
}
