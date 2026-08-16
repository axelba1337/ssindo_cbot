<?php

namespace App\Services;

interface LLMInterface
{
    /**
     * Menghasilkan embedding vektor untuk teks (array float) atau null saat gagal.
     */
    public function getEmbedding(string $text): ?array;

    /**
     * Menghasilkan jawaban dari LLM dengan konteks database dan riwayat chat.
     */
    public function chat(
        string $prompt,
        array $context = [],
        array $history = [],
        array $options = [],
    ): ChatResult;
}
