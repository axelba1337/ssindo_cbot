<?php

namespace App\Services;

final class ChatResult
{
    public function __construct(
        public readonly string $text,
        public readonly bool $successful = true,
        public readonly bool $usedWeb = false,
        public readonly array $sources = [],
    ) {}

    public static function failure(
        string $text = 'Maaf, koneksi ke layanan AI eksternal sedang tidak '
            .'tersedia. Saya masih dapat membantu dengan informasi produk '
            .'dan layanan Neev yang tersimpan.'
    ): self {
        return new self($text, false);
    }
}
