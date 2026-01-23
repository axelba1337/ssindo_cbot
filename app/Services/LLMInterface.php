<?php

namespace App\Services;

interface LLMInterface
{
    /**
     * Menghasilkan embedding vektor untuk teks (array float) atau null saat gagal.
     */
    public function getEmbedding(string $text): ?array;

    /**
     * Menghasilkan jawaban dari LLM dengan konteks (list snippet ringkas).
     */
    public function chat(string $prompt, array $context = []): string;
}   