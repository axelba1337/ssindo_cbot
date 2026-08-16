<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\KnowledgeRetriever;
use App\Services\LLMInterface;
use App\Services\QueryRouter;
use App\Helpers\Normalizer;

class ChatbotController extends Controller
{
    public function __construct(
        private LLMInterface $llm,
        private KnowledgeRetriever $retriever,
        private QueryRouter $router,
    ) {}

    public function query(Request $request)
    {
        $message     = (string) ($request->input('message') ?? '');
        $rawSession  = $request->input('session_id');                           // token/string dari client (boleh null/uuid/numeric)
        $sessionId   = $this->resolveSessionId($rawSession);                    // pastikan BIGINT id dari tabel chatbot_sessions
        $fallbackSource = (string) config('chatbot.fallback_source', 'llm');

        if (trim($message) === '') {
            return response()->json([
                'answer'        => 'Silakan ketik pertanyaan Anda.',
                'source'        => 'faq',
                'similarity'    => null,
                'cta_whatsapp'  => ['show' => false, 'link' => null],
                'session_id'    => $sessionId,
                'session_token' => $rawSession,
            ], 200);
        }

        // Normalisasi awal
        $normalized = Normalizer::text($message);

        // =========================
        // INTENT KHUSUS: KONTAK WA
        // =========================
        if ($this->isWhatsAppIntent($normalized)) {
            // Paksa CTA selalu muncul
            $cta = $this->buildWhatsAppCta(true, $message);

            $answer = 'Anda bisa menghubungi admin Neev melalui WhatsApp '
                    . 'menggunakan tombol "WhatsApp Admin" di bagian atas. '
                    . 'Silakan jelaskan kebutuhan dan lokasi Anda, admin akan membantu tindak lanjut.';

            if (!empty($cta['link'])) {
                $answer = "Anda bisa menghubungi admin melalui WhatsApp pada tautan berikut:\n"
                . $cta['link'] . "\n\nSilakan jelaskan kebutuhan Anda.";        
            }

            // Log sebagai CONTACT
            $this->log($sessionId, $message, $answer, 'contact', null, null);

            return response()->json([
                'answer'        => $answer,
                'source'        => 'contact',
                'similarity'    => null,
                'cta_whatsapp'  => $cta,
                'session_id'    => $sessionId,
                'session_token' => $rawSession,
            ]);
        }

        if ($direct = $this->directBusinessResponse($normalized)) {
            $this->log(
                $sessionId,
                $message,
                $direct['answer'],
                $direct['source'],
                null,
                null
            );

            return response()->json([
                'answer'        => $direct['answer'],
                'source'        => $direct['source'],
                'similarity'    => null,
                'used_web'      => false,
                'sources'       => [],
                'cta_whatsapp'  => $this->buildWhatsAppCta(
                    (bool) ($direct['show_whatsapp'] ?? false),
                    $message
                ),
                'session_id'    => $sessionId,
                'session_token' => $rawSession,
            ]);
        }

        // =========================
        // NORMAL FLOW CHATBOT
        // =========================

        $history = $this->conversationHistory($sessionId);
        $useCompanyData = $this->router->usesCompanyData(
            $normalized,
            $history
        );

        // Most questions go directly to the model/web. Company retrieval only
        // runs for company-specific intents, so weak cosine matches cannot
        // inject unrelated database rows into general answers.
        $topK = [];
        if ($useCompanyData) {
            $qVec = $this->llm->getEmbedding($normalized);
            $rows = DB::table('chatbot_knowledge')
                ->select('id','kind','title','content_text','embedding')
                ->orderByDesc('updated_at')
                ->get();

            $topK = $this->retriever->rank(
                $rows->all(),
                $qVec,
                $normalized
            );
        }

        $best = $topK[0] ?? null;

        $similarity = $best['score'] ?? null;
        $hasDatabaseMatch = $best
            && $this->retriever->isRelevant($best);

        // Only send sufficiently relevant database rows to the model.
        $relevantMatches = array_values(array_filter(
            $topK,
            fn ($match) => $this->retriever->isRelevant($match)
        ));
        $context = array_map(
            fn($x) => "Type: {$x['kind']}\n"
                ."Title: {$x['title']}\n"
                ."Data:\n{$x['content_text']}",
            array_slice(
                $relevantMatches,
                0,
                (int) config('chatbot.llm.max_context_items', 3)
            )
        );

        $chat = $this->llm->chat(
            $message,
            $context,
            $history,
            [
                'safety_identifier' => hash(
                    'sha256',
                    (string) $sessionId
                ),
            ],
        );

        if ($chat->successful) {
            $answer = $chat->text;

            if ($chat->usedWeb && $hasDatabaseMatch) {
                $source = 'hybrid';
            } elseif ($chat->usedWeb) {
                $source = 'web';
            } elseif ($hasDatabaseMatch) {
                $source = (string) $best['kind'];
            } else {
                $source = $fallbackSource;
            }

            $cta = $this->buildWhatsAppCta(false, $message);
        } elseif ($hasDatabaseMatch) {
            // The database can still answer useful company questions when the
            // external AI provider is temporarily unavailable.
            $answer = $this->databaseFallbackAnswer($best);
            $source = (string) $best['kind'];
            $cta = $this->buildWhatsAppCta(false, $message);
        } else {
            $answer = $chat->text;
            $source = $fallbackSource;
            $cta = $this->buildWhatsAppCta($useCompanyData, $message);
        }

        // Simpan log
        $this->log($sessionId, $message, $answer, $source, $similarity, $topK);

        return response()->json([
            'answer'        => $answer,
            'source'        => $source,
            'similarity'    => $similarity,
            'used_web'      => $chat->usedWeb,
            'sources'       => $chat->sources,
            'cta_whatsapp'  => $cta,
            'session_id'    => $sessionId,
            'session_token' => $rawSession,
        ]);
    }

    private function directBusinessResponse(string $message): ?array
    {
        if (preg_match(
            '/\b(can|do)\s+you\s+speak\s+english\b'
                .'|\b(bisa|dapat)\s+(?:bicara|berbahasa)\s+inggris\b/u',
            $message
        )) {
            return [
                'answer' => 'Yes, I can speak English. How can I help you?',
                'source' => 'assistant',
            ];
        }

        if ($this->isProductCatalogIntent($message)) {
            $products = DB::table('products')
                ->orderBy('name')
                ->limit(20)
                ->get(['name', 'sku', 'stock', 'unit', 'price']);

            if ($products->isEmpty()) {
                return [
                    'answer' => 'Katalog produk Neev belum tersedia saat ini. '
                        .'Silakan jelaskan produk yang Anda butuhkan agar admin '
                        .'dapat memeriksanya.',
                    'source' => 'product',
                    'show_whatsapp' => true,
                ];
            }

            $matches = $products->filter(
                fn ($product) => $this->productMatchesMessage(
                    $message,
                    $product
                )
            )->values();
            $isEnglish = $this->isEnglishMessage($message);

            if ($matches->isEmpty()) {
                $requested = $this->requestedProductLabel($message);
                $available = $products->take(8)->pluck('name')->implode(', ');

                return [
                    'answer' => $isEnglish
                        ? ucfirst($requested).' is not currently listed in '
                            ."Neev's product catalog. Listed products include: "
                            ."{$available}. Please contact the admin if you "
                            .'want us to check special availability.'
                        : ucfirst($requested).' belum tercatat dalam katalog '
                            ."produk Neev saat ini. Produk yang tercatat: "
                            ."{$available}. Hubungi admin jika Anda ingin kami "
                            .'memeriksa ketersediaan khusus.',
                    'source' => 'product',
                    'show_whatsapp' => true,
                ];
            }

            $list = $matches->map(
                fn ($product) => $this->formatProductLine(
                    $product,
                    $isEnglish
                )
            )->implode("\n");

            return [
                'answer' => $isEnglish
                    ? "Yes. These matching products are currently listed:\n"
                        ."{$list}\n\nStock and prices are based on the current "
                        .'catalog data.'
                    : "Ya, Neev menjual perangkat CCTV. Produk yang "
                        ."sesuai dan tercatat saat ini:\n{$list}\n\n"
                        .'Stok dan harga mengikuti data katalog saat ini.',
                'source' => 'product',
            ];
        }

        if (preg_match(
            '/\b(pasang|pemasangan|instalasi)\b.*\bcctv\b'
                .'|\bcctv\b.*\b(pasang|pemasangan|instalasi)\b/u',
            $message
        )) {
            return [
                'answer' => 'Tentu, Neev melayani pemasangan CCTV. Agar '
                    .'rekomendasinya tepat, pemasangan ini untuk rumah, toko, '
                    .'kantor, atau lokasi lain? Jika sudah tahu, sebutkan juga '
                    .'perkiraan jumlah titik kamera.',
                'source' => 'service',
            ];
        }

        if (preg_match(
            '/^(?:saya\s+)?(?:butuh|perlu|minta)?\s*'
                .'bantuan\s+(?:teknis|teknisi)$/u',
            $message
        )) {
            return [
                'answer' => 'Siap. Kendalanya apa: kamera tidak menampilkan '
                    .'gambar, rekaman tidak tersimpan, perangkat offline, atau '
                    .'masalah lain? Sebutkan gejala dan perangkat yang digunakan.',
                'source' => 'assistant',
            ];
        }

        if (str_contains($message, 'jam')
            && preg_match('/\b(operasional|buka|tutup)\b/u', $message)) {
            $hours = DB::table('office_hours')->orderBy('id')->first();

            if ($hours) {
                $open = substr((string) $hours->open_time, 0, 5);
                $close = substr((string) $hours->close_time, 0, 5);
                $note = trim((string) ($hours->note ?? ''));

                return [
                    'answer' => "Jam operasional Neev pukul {$open}–{$close} "
                        ."WIB setiap hari."
                        .($note !== '' ? " {$note}" : ''),
                    'source' => 'office_hours',
                ];
            }
        }

        if (preg_match(
            '/^(halo|hai|hello|selamat\s+(pagi|siang|sore|malam)|'
                .'assalamualaikum|permisi)$/u',
            $message
        )) {
            return [
                'answer' => 'Halo! Saya Neev Assistant. Saya bisa membantu '
                    .'soal produk CCTV, pemasangan, jam operasional, atau '
                    .'kendala teknis. Apa yang ingin Anda tanyakan?',
                'source' => 'assistant',
            ];
        }

        return null;
    }

    private function isProductCatalogIntent(string $message): bool
    {
        return $this->router->isCompanyCommerceQuestion($message)
            || preg_match(
                '/\bproduk\b.*\b(apa|mana)\b.*\b(tersedia|ada|stok)\b'
                    .'|\b(daftar|list|katalog)\s+produk\b/u',
                $message
            ) === 1;
    }

    private function productMatchesMessage(
        string $message,
        object $product
    ): bool {
        $tokens = $this->productQueryTokens($message);
        if ($tokens === []) {
            return true;
        }

        $haystack = mb_strtolower(
            "{$product->name} {$product->sku}",
            'UTF-8'
        );

        foreach ($tokens as $token) {
            if (str_contains($haystack, $token)) {
                return true;
            }

            if ($token === 'cctv'
                && preg_match('/\b(kamera|camera|nvr|dvr)\b/u', $haystack)) {
                return true;
            }

            if ($token === 'camera' && str_contains($haystack, 'kamera')) {
                return true;
            }
        }

        return false;
    }

    private function productQueryTokens(string $message): array
    {
        $tokens = preg_split(
            '/[^\pL\pN-]+/u',
            mb_strtolower($message, 'UTF-8'),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $ignored = [
            'jual', 'menjual', 'beli', 'harga', 'stok', 'tersedia', 'ada',
            'produk', 'barang', 'apakah', 'apa', 'yang', 'mana', 'berapa',
            'ga', 'gak', 'nggak', 'kah', 'sih', 'dong', 'neev',
            'do', 'does', 'you', 'sell', 'selling', 'have', 'carry', 'buy',
            'price', 'stock', 'available', 'product', 'products', 'any',
            'the', 'a', 'an', 'of', 'list', 'catalog',
        ];

        return array_values(array_unique(array_filter(
            $tokens,
            fn ($token) => mb_strlen($token, 'UTF-8') >= 2
                && !in_array($token, $ignored, true)
        )));
    }

    private function requestedProductLabel(string $message): string
    {
        $tokens = $this->productQueryTokens($message);

        return $tokens === [] ? 'Produk tersebut' : implode(' ', $tokens);
    }

    private function formatProductLine(object $product, bool $english): string
    {
        $price = is_null($product->price)
            ? ($english ? 'price: ask admin' : 'harga: hubungi admin')
            : 'Rp'.number_format((float) $product->price, 0, ',', '.');
        $stock = (int) $product->stock.' '.$product->unit;

        return $english
            ? "- {$product->name} — recorded stock: {$stock}; {$price}"
            : "- {$product->name} — stok tercatat {$stock}; {$price}";
    }

    private function isEnglishMessage(string $message): bool
    {
        return preg_match(
            '/\b(do|does|you|sell|have|price|stock|available|product|'
                .'products|english|what|which)\b/u',
            $message
        ) === 1;
    }

    private function databaseFallbackAnswer(array $match): string
    {
        $content = trim((string) $match['content_text']);

        if (($match['kind'] ?? null) === 'faq'
            && preg_match('/(?:^|\R)Jawaban:\s*(.+)$/isu', $content, $parts)) {
            return trim($parts[1]);
        }

        return $content;
    }

    private function conversationHistory(int $sessionId): array
    {
        $messageLimit = max(0, (int) config('chatbot.history_messages', 8));
        if ($messageLimit === 0) {
            return [];
        }

        $logs = DB::table('chatbot_logs')
            ->where('session_id', $sessionId)
            ->orderByDesc('id')
            ->limit((int) ceil($messageLimit / 2))
            ->get()
            ->reverse();

        $history = [];
        foreach ($logs as $log) {
            $history[] = [
                'role' => 'user',
                'content' => (string) $log->user_message,
            ];
            $history[] = [
                'role' => 'assistant',
                'content' => (string) $log->bot_answer,
            ];
        }

        return array_slice($history, -$messageLimit);
    }

    // Konversi session token/string → BIGINT id (chatbot_sessions.id)
    private function resolveSessionId($sessionInput): int
    {
        // jika angka, pastikan ada
        if (is_numeric($sessionInput)) {
            $id = (int) $sessionInput;
            if (DB::table('chatbot_sessions')->where('id', $id)->exists()) {
                return $id;
            }
            // jatuhkan ke alur token agar dibuat sesi baru
            $sessionInput = "migrated-{$id}";
        }

        $token = $sessionInput ?: (string) Str::uuid();

        $existing = DB::table('chatbot_sessions')
            ->where('user_identifier', $token)
            ->orderByDesc('id')
            ->first();

        if ($existing) return (int) $existing->id;

        return (int) DB::table('chatbot_sessions')->insertGetId([
            'user_identifier' => $token,
            'started_at'      => now(),
            'meta'            => json_encode([
                'ip' => request()->ip(),
                'ua' => request()->userAgent(),
            ]),
        ]);
    }

    private function buildWhatsAppCta(bool $show, string $originalMessage): array
    {
        // Ambil nomor dari DB atau fallback
        $number = DB::table('company_contacts')
            ->where('type','whatsapp')
            ->where('is_primary', true)
            ->value('value');

        if (!$number) {
            $number = config('chatbot.whatsapp.fallback_number');
        }

        // Hanya format link utama, tanpa "?text=..."
        $cleanNumber = preg_replace('/\D/', '', $number);  
        $link = $cleanNumber ? "https://wa.me/{$cleanNumber}" : null;

        return [
            'show' => $show && (bool) $link,
            'link' => $link,            // contoh: https://wa.me/6281335117054
        ];
    }

    // Simpan log (session_id BIGINT, boleh null jika sesi hilang)
    private function log(?int $sessionId, string $userMessage, string $botAnswer, string $source, ?float $similarity, ?array $topK): void
    {
        if ($sessionId !== null && !DB::table('chatbot_sessions')->where('id', $sessionId)->exists()) {
            $sessionId = null;
        }

        try {
            DB::table('chatbot_logs')->insert([
                'session_id'   => $sessionId,
                'user_message' => $userMessage,
                'bot_answer'   => $botAnswer,
                'source'       => $source,
                'similarity'   => $similarity,
                'top_matches'  => $topK ? json_encode(
                    array_map(
                        fn($x)=>['id'=>$x['id'],'score'=>round($x['score'],4),'kind'=>$x['kind']],
                        $topK
                    )
                ) : null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Logging must never turn a valid chatbot response into an HTTP 500.
            logger()->error('Unable to persist chatbot log', [
                'source' => $source,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Deteksi intent "kontak WhatsApp admin"
    private function isWhatsAppIntent(string $text): bool
    {
        $t = mb_strtolower($text);

        return preg_match(
            '/(wa|whatsapp|kontak\s*admin|nomor\s*admin|hubungi\s*admin|kontak\s*wa)/u',
            $t
        ) === 1;
    }
}
