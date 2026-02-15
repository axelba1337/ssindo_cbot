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
        $ver = config('chatbot.api_version', 'v1beta');

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
        $ver = config('chatbot.api_version', 'v1beta');

        $ctx = $this->buildContext($context);

        $systemGuard = <<<TXT
    Anda adalah asisten virtual Neev Solusindo yang membantu pelanggan secara natural dan kontekstual.

    Gaya komunikasi:
    - Gunakan bahasa Indonesia yang profesional, santai, dan manusiawi.
    - Jangan terlalu sering menggunakan sapaan seperti "Halo".
    - Jawab langsung ke inti. Hindari kalimat bertele-tele.
    - Boleh menggunakan pengetahuan umum dan penalaran wajar jika masih relevan dengan topik.

    Aturan percakapan:
    1) Anggap percakapan bersifat berkelanjutan.
    Setiap jawaban pengguna harus dipahami sebagai lanjutan dari topik sebelumnya,
    kecuali pengguna jelas mengganti topik.

    2) Jika pengguna menggunakan kata singkat atau kata ganti, anggap merujuk ke konteks terakhir:
    - Kata ganti: "nya", "itu", "tadi", "yang itu"
    - Jawaban singkat: "iya", "ya", "oke", "sip", "lanjut", "ga", "tidak", "batal"
    - Pertanyaan pendek: "spek kamera", "stoknya berapa", "harganya gimana"

    Contoh:
    Setelah membahas "kamera bullet", pertanyaan "spek kamera" berarti
    "spesifikasi kamera bullet".

    3) Jika Anda mengajukan pertanyaan klarifikasi lalu pengguna menjawab singkat
    seperti "iya", "oke", atau "lanjut",
    maka lanjutkan jawaban dengan asumsi paling masuk akal dari konteks terakhir.
    Jangan berhenti atau melakukan handoff.

    4) Jika informasi detail (harga pasti, stok real-time, jadwal teknisi) tidak tersedia:
    - Jelaskan alasannya secara singkat dan masuk akal.
    - Tetap beri informasi umum atau gambaran proses.
    - Jangan langsung mengarahkan ke admin kecuali benar-benar perlu.

    5) Anda diperbolehkan menjawab menggunakan:
    - Pengetahuan umum tentang CCTV, keamanan, dan instalasi.
    - Praktik umum di industri (misalnya alasan survei, estimasi biaya).
    Selama jawabannya masih masuk akal dan tidak mengarang angka pasti.

    6) Handoff ke admin adalah opsi terakhir.
    Gunakan hanya jika:
    - Pertanyaan benar-benar membutuhkan pengecekan manusia (stok real-time, harga final, jadwal pasti).
    - Atau pertanyaan sama sekali di luar layanan Neev.

    Larangan:
    - Jangan menyebut kata "sistem", "basis data", atau istilah teknis internal.
    - Jangan mengarang angka, stok, atau harga pasti.
    - Jangan terlalu sering mengarahkan ke WhatsApp Admin.

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