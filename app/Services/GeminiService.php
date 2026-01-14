<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService implements LLMInterface
{
    private string $apiKey;
    private string $embedModel;
    private string $chatModel;

    // batasan ringan agar context tidak kepanjangan
    private int $maxContextItems = 3;     // top-3 snippet
    private int $maxContextChars = 2000;  // ~perkiraan agar prompt efisien

    public function __construct()
    {
        $this->apiKey     = (string) env('GEMINI_API_KEY', '');
        $this->embedModel = (string) config('chatbot.models.embed', 'models/embedding-001');
        $this->chatModel  = (string) config('chatbot.models.chat',  'models/gemini-1.5-flash');
    }

    /**
     * Ambil embedding vektor untuk teks.
     */
    public function getEmbedding(string $text): ?array
    {
        $ver = config('chatbot.api_version', 'v1');

        if ($text === '') {
            return null;
        }

    // getEmbedding() → pakai model embed dari config (benar)
    $url = "https://generativelanguage.googleapis.com/{$ver}/{$this->embedModel}:embedContent";


        $resp = Http::withOptions([
                    'timeout' => 15,
                    'retry'   => 1, // 1x retry ringan
                ])
                ->withQueryParameters(['key' => $this->apiKey])   // <<— API key via query param
                ->post($url, [
                    'model'   => $this->embedModel,
                    'content' => ['parts' => [['text' => $text]]],
                ]);

        if (!$resp->ok()) {
            logger()->warning('Gemini embedding error', ['status' => $resp->status(), 'body' => $resp->body()]);
            return null;
        }

        // Struktur: { embedding: { values: [...] } }
        return $resp->json('embedding.values') ?: null;
    }

    /**
     * Generate jawaban dengan konteks (RAG).
     * $context: array teks ringkas (snippet DB). Akan dipangkas otomatis.
     */
    public function chat(string $prompt, array $context = []): string
    {
        $ver = config('chatbot.api_version', 'v1');

        $ctx = $this->buildContext($context);

        $systemGuard = <<<TXT
    Anda adalah asisten perusahaan Neev Solusindo.

    Gaya bahasa:
    - Jangan terlalu sering membuka jawaban dengan "Halo!" atau sapaan berulang.
    - Gunakan nada profesional dan natural. Boleh ramah, tetapi singkat.
    - Jawab langsung ke inti. Hindari kalimat basa-basi.

    Aturan jawaban:
    1) Utamakan menjawab berdasarkan "Konteks" yang diberikan.
    2) Anggap percakapan berkelanjutan. Jika pengguna menulis pertanyaan singkat atau lanjutan, anggap itu merujuk ke topik terakhir yang dibahas. Termasuk:
    - kata ganti: "-nya", "itu", "tadi", "tersebut", "yang tadi"
    - jawaban singkat: "iya", "ya", "betul", "oke", "sip", "nggak", "tidak"
    - pertanyaan lanjutan tanpa objek: "spek kamera", "stoknya berapa", "harganya berapa"
    Contoh: setelah membahas "kamera bullet", pertanyaan "spek kamera" berarti "spek kamera bullet".

    3) Jika pengguna menjawab "iya/ya/betul" terhadap pertanyaan klarifikasi Anda, perlakukan itu sebagai konfirmasi topik terakhir. Lanjutkan jawaban tanpa bertanya ulang.
    Jika tetap perlu detail, ajukan tepat 1 pertanyaan singkat yang paling penting (pilih salah satu):
    - "Untuk indoor atau outdoor?"
    - "Butuh berapa titik kamera?"
    - "Area lokasinya di kota mana?"

    4) Jika pengguna menanyakan stok atau hal yang memang berubah ubah, jangan langsung masuk format handoff dua paragraf.
    Beri jawaban yang menjelaskan bahwa angka stok perlu pengecekan, lalu arahkan langkah berikutnya:
    - Minta pengguna menyebut produk yang dimaksud (contoh: Bullet 4MP atau Dome).
    - Jika perlu, sarankan hubungi admin untuk pengecekan stok terbaru.

    5) Jangan mengarang harga, stok, atau biaya detail. Jika diminta angka pasti tetapi tidak ada di konteks, jelaskan bahwa Anda belum bisa menyebutkan angka pastinya.

    6) Handoff ke admin ditentukan oleh threshold di backend. Anda tidak perlu memaksa ajakan WhatsApp di setiap jawaban.
    Ajakan WhatsApp cukup disebut jika pengguna meminta hal yang memang perlu pengecekan langsung: stok terbaru, harga final, jadwal teknisi, atau ketersediaan pemasangan.

    7) Jika pertanyaan benar benar di luar layanan Neev atau tidak nyambung sama sekali, jawab singkat lalu minta pengguna memperjelas.

    Larangan:
    - Jangan menggunakan kata "sistem" atau "data".
    - Jangan terlalu sering menggunakan "Halo!".
    - Jangan mengarang angka.

    TXT;

        $fullPrompt = "Instruksi:\n{$systemGuard}\n\nKonteks:\n{$ctx}\n\nPertanyaan pengguna:\n{$prompt}";

        $url = "https://generativelanguage.googleapis.com/{$ver}/{$this->chatModel}:generateContent";

        $resp = Http::withOptions([
                'timeout' => 25,
                'retry'   => 1,
            ])
            ->withQueryParameters(['key' => $this->apiKey])
            ->post($url, [
                'contents' => [[
                    'role'  => 'user',
                    'parts' => [[ 'text' => $fullPrompt ]],
                ]],
                'generationConfig' => [
                    'temperature'      => (float) config('chatbot.llm.temperature', 0.2),
                    'topK'             => (int)   config('chatbot.llm.top_k', 40),
                    'topP'             => (float) config('chatbot.llm.top_p', 0.95),
                    'maxOutputTokens'  => (int)   config('chatbot.llm.max_output_tokens', 512),
                ],
            ]);

        if (!$resp->ok()) {
            logger()->warning('Gemini chat error', [
                'status' => $resp->status(),
                'error'  => $resp->json('error.message'),
            ]);
            return 'Maaf, saya belum bisa menjawab sekarang.';
        }

        $text = $resp->json('candidates.0.content.parts.0.text')
            ?? $resp->json('candidates.0.output_text')
            ?? '';

        return $text !== '' ? $text : 'Maaf, saya belum bisa menjawab sekarang.';
    }

    /**
     * Satukan dan pangkas konteks agar tidak melebihi batas ringan.
     */
    private function buildContext(array $context): string
    {
        if (empty($context)) return '(tanpa konteks)';

        // ambil maksimal N item
        $items = array_slice($context, 0, $this->maxContextItems);

        // gabungkan dan batasi karakter
        $joined = collect($items)
            ->map(fn($t, $i) => "- Snippet ".($i+1).": ".trim((string)$t))
            ->implode("\n");

        if (mb_strlen($joined) > $this->maxContextChars) {
            $joined = mb_substr($joined, 0, $this->maxContextChars) . " …";
        }

        return $joined;
    }
}