<?php

namespace App\Http\Controllers;

use App\Helpers\Normalizer;
use App\Http\Requests\ChatbotQueryRequest;
use App\Services\ChatbotSessionManager;
use App\Services\KnowledgeRetriever;
use App\Services\LLMInterface;
use App\Services\QueryRouter;
use Illuminate\Support\Facades\DB;

class ChatbotController extends Controller
{
    public function __construct(
        private LLMInterface $llm,
        private KnowledgeRetriever $retriever,
        private QueryRouter $router,
        private ChatbotSessionManager $sessions,
    ) {}

    public function query(ChatbotQueryRequest $request)
    {
        $validated = $request->validated();
        $message = $validated['message'];
        $session = $this->sessions->resolve(
            $validated['session_token'] ?? $validated['session_id'] ?? null
        );
        $sessionId = $session['id'];
        $sessionToken = $session['token'];
        $fallbackSource = (string) config('chatbot.fallback_source', 'llm');

        // Normalisasi awal
        $normalized = Normalizer::text($message);

        // =========================
        // INTENT KHUSUS: KONTAK WA
        // =========================
        if ($this->isWhatsAppIntent($normalized)) {
            // Paksa CTA selalu muncul
            $cta = $this->buildWhatsAppCta(true, $message);
            $isEnglish = $this->isEnglishMessage($normalized);

            $answer = $isEnglish
                ? 'You can contact the Neev admin using the WhatsApp Admin '
                    .'button. Please describe your needs so the admin can '
                    .'verify the details.'
                : 'Anda bisa menghubungi admin Neev melalui tombol WhatsApp '
                    .'Admin. Silakan jelaskan kebutuhan Anda agar admin dapat '
                    .'memeriksa detailnya.';

            if (! empty($cta['link'])) {
                $answer = $isEnglish
                    ? "You can contact the admin through WhatsApp:\n"
                        .$cta['link']."\n\nPlease describe your needs."
                    : "Anda bisa menghubungi admin melalui WhatsApp:\n"
                        .$cta['link']."\n\nSilakan jelaskan kebutuhan Anda.";
            }

            // Log sebagai CONTACT
            $this->log($sessionId, $message, $answer, 'contact', null, null);

            return response()->json([
                'answer' => $answer,
                'source' => 'contact',
                'similarity' => null,
                'cta_whatsapp' => $cta,
                'session_id' => $sessionToken,
                'session_token' => $sessionToken,
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
                'answer' => $direct['answer'],
                'source' => $direct['source'],
                'similarity' => null,
                'used_web' => false,
                'sources' => [],
                'cta_whatsapp' => $this->buildWhatsAppCta(
                    (bool) ($direct['show_whatsapp'] ?? false),
                    $message
                ),
                'session_id' => $sessionToken,
                'session_token' => $sessionToken,
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
                ->select(
                    'id',
                    'kind',
                    'title',
                    'content_text',
                    'embedding',
                    'source_table',
                    'source_id'
                )
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
            fn ($x) => "Type: {$x['kind']}\n"
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
            'answer' => $answer,
            'source' => $source,
            'similarity' => $similarity,
            'used_web' => $chat->usedWeb,
            'sources' => $chat->sources,
            'cta_whatsapp' => $cta,
            'session_id' => $sessionToken,
            'session_token' => $sessionToken,
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

        if ($serviceResponse = $this->directServiceResponse($message)) {
            return $serviceResponse;
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
                            .'produk Neev saat ini. Produk yang tercatat: '
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
                    : 'Produk yang sesuai dan tercatat dalam katalog Neev '
                        ."saat ini:\n{$list}\n\n"
                        .'Stok dan harga mengikuti data katalog saat ini.',
                'source' => 'product',
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

        if ((str_contains($message, 'jam')
                && preg_match('/\b(operasional|buka|tutup)\b/u', $message))
            || preg_match(
                '/\b(office|business|opening)\s+hours\b'
                    .'|\bwhat\s+time\b.*\b(open|close)\b/u',
                $message
            )) {
            $hours = DB::table('office_hours')->orderBy('id')->first();

            if ($hours) {
                $open = substr((string) $hours->open_time, 0, 5);
                $close = substr((string) $hours->close_time, 0, 5);
                $note = trim((string) ($hours->note ?? ''));
                $timezone = (string) $hours->timezone === 'Asia/Jakarta'
                    ? 'WIB'
                    : (string) $hours->timezone;
                $isEnglish = $this->isEnglishMessage($message);

                return [
                    'answer' => $isEnglish
                        ? "Neev's recorded office hours are {$open}-{$close} "
                            ."{$timezone}. Please confirm holiday exceptions "
                            .'with the admin.'
                        : "Jam operasional Neev tercatat pukul {$open}-{$close} "
                            ."{$timezone}."
                            .($note !== '' ? " Catatan: {$note}." : ''),
                    'source' => 'office_hours',
                ];
            }
        }

        if (preg_match(
            '/^(halo|hai|hello|selamat\s+(pagi|siang|sore|malam)|'
                .'assalamualaikum|permisi)$/u',
            $message
        )) {
            $isEnglish = $this->isEnglishMessage($message);

            return [
                'answer' => $isEnglish
                    ? 'Hello! I am Neev Assistant. I can help with products, '
                        .'services, office hours, and technical support. What '
                        .'would you like to know?'
                    : 'Halo! Saya Neev Assistant. Saya bisa membantu soal '
                        .'produk, layanan, jam operasional, atau kendala teknis. '
                        .'Apa yang ingin Anda tanyakan?',
                'source' => 'assistant',
            ];
        }

        return null;
    }

    private function directServiceResponse(string $message): ?array
    {
        if (! $this->router->isCompanyServiceQuestion($message)) {
            return null;
        }

        $topic = $this->serviceTopic($message);
        $asksForServiceList = preg_match(
            '/\b(apa|what|which|daftar|list)\b.*\b(layanan|service|services)\b'
                .'|\b(layanan|service|services)\b.*\b(apa|what|available)\b/u',
            $message
        ) === 1;

        // FAQ questions such as "kenapa survei" should continue through RAG.
        if ($topic === null && ! $asksForServiceList) {
            return null;
        }

        $services = DB::table('services')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'description', 'price']);
        $matches = $topic === null
            ? $services
            : $services->filter(
                fn ($service) => $this->serviceMatchesTopic($service, $topic)
            )->values();
        $isEnglish = $this->isEnglishMessage($message);

        if ($matches->isEmpty()) {
            return [
                'answer' => $isEnglish
                    ? 'That capability is not currently recorded as an active '
                        .'Neev service. Please ask the admin to verify it.'
                    : 'Kemampuan tersebut belum tercatat sebagai layanan aktif '
                        .'Neev. Silakan minta admin untuk memastikannya.',
                'source' => 'service',
                'show_whatsapp' => true,
            ];
        }

        if ($topic === 'software') {
            return [
                'answer' => $isEnglish
                    ? 'Yes. Neev records Software Development as an active '
                        .'service for web/mobile and custom business '
                        .'applications. SIMPUS, HRMS, and POS are software '
                        .'solutions, not physical-stock items. Features, '
                        .'integrations, schedule, and price require admin '
                        .'confirmation.'
                    : 'Ya. Neev mencatat Software Development sebagai layanan '
                        .'aktif untuk aplikasi web/mobile dan aplikasi bisnis '
                        .'custom. SIMPUS, HRMS, dan POS adalah solusi software, '
                        .'bukan stok barang fisik. Fitur, integrasi, jadwal, dan '
                        .'biaya perlu dikonfirmasi dengan admin.',
                'source' => 'service',
                'show_whatsapp' => true,
            ];
        }

        $names = $matches->pluck('name')->implode(', ');
        $descriptions = $matches->pluck('description')->filter()->implode(' ');

        return [
            'answer' => $isEnglish
                ? "Yes. Neev records {$names} as an active service. The exact "
                    .'scope, schedule, and price require admin confirmation.'
                : "Ya. Neev mencatat {$names} sebagai layanan aktif."
                    .($descriptions !== '' ? " {$descriptions}" : '')
                    .' Detail cakupan, jadwal, dan biaya perlu dikonfirmasi '
                    .'dengan admin.',
            'source' => 'service',
            'show_whatsapp' => true,
        ];
    }

    private function serviceTopic(string $message): ?string
    {
        if (preg_match(
            '/\b(software|aplikasi|application|apps?|mobile|website|web|'
                .'simpus|hrms|pos|point\s+of\s+sale|custom)\b/u',
            $message
        )) {
            return 'software';
        }

        if (preg_match('/\b(jaringan|network|wifi|wi-fi|lan|internet)\b/u', $message)) {
            return 'network';
        }

        if (preg_match('/\b(cctv|kamera|camera|nvr|dvr)\b/u', $message)) {
            return 'cctv';
        }

        if (preg_match(
            '/\b(maintenance|perawatan|it\s+support|dukungan\s+it|teknisi)\b/u',
            $message
        )) {
            return 'maintenance';
        }

        if (preg_match('/\b(ai|virtual\s+admin|chatbot)\b/u', $message)) {
            return 'ai';
        }

        return null;
    }

    private function serviceMatchesTopic(object $service, string $topic): bool
    {
        $haystack = mb_strtolower(
            "{$service->name} {$service->description}",
            'UTF-8'
        );
        $patterns = [
            'software' => '/\b(software|aplikasi|application|web|mobile)\b/u',
            'network' => '/\b(jaringan|network|internet|lan|wifi)\b/u',
            'cctv' => '/\b(cctv|kamera|camera|keamanan)\b/u',
            'maintenance' => '/\b(maintenance|perawatan|dukungan|support)\b/u',
            'ai' => '/\b(ai|virtual\s+admin|chatbot)\b/u',
        ];

        return isset($patterns[$topic])
            && preg_match($patterns[$topic], $haystack) === 1;
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
                && ! in_array($token, $ignored, true)
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
        $englishSignals = preg_match_all(
            '/\b(do|does|you|your|sell|have|price|stock|available|english|'
                .'what|which|can|could|would|build|develop|install|office|'
                .'hours|contact|number|please|hello|need)\b/u',
            $message
        );
        $indonesianSignals = preg_match_all(
            '/\b(apa|apakah|saya|kami|ingin|bisa|dapat|buat|membuat|berapa|'
                .'harga|stok|tersedia|jam|kontak|nomor|layanan|butuh|perlu|'
                .'halo|hai)\b/u',
            $message
        );

        return $englishSignals > $indonesianSignals;
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
    private function buildWhatsAppCta(bool $show, string $originalMessage): array
    {
        // Ambil nomor dari DB atau fallback
        $number = DB::table('company_contacts')
            ->where('type', 'whatsapp')
            ->where('is_primary', true)
            ->value('value');

        if (! $number) {
            $number = config('chatbot.whatsapp.fallback_number');
        }

        // Hanya format link utama, tanpa "?text=..."
        $cleanNumber = preg_replace('/\D/', '', (string) $number);
        $link = is_string($cleanNumber)
            && preg_match('/^[1-9]\d{7,14}$/', $cleanNumber) === 1
                ? "https://wa.me/{$cleanNumber}"
                : null;

        return [
            'show' => $show && (bool) $link,
            'link' => $link,            // contoh: https://wa.me/6281335117054
        ];
    }

    // Simpan log (session_id BIGINT, boleh null jika sesi hilang)
    private function log(?int $sessionId, string $userMessage, string $botAnswer, string $source, ?float $similarity, ?array $topK): void
    {
        if ($sessionId !== null && ! DB::table('chatbot_sessions')->where('id', $sessionId)->exists()) {
            $sessionId = null;
        }

        try {
            DB::table('chatbot_logs')->insert([
                'session_id' => $sessionId,
                'user_message' => $userMessage,
                'bot_answer' => $botAnswer,
                'source' => $source,
                'similarity' => $similarity,
                'top_matches' => $topK ? json_encode(
                    array_map(
                        fn ($x) => ['id' => $x['id'], 'score' => round($x['score'], 4), 'kind' => $x['kind']],
                        $topK
                    )
                ) : null,
                'created_at' => now(),
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
            '/\b(?:wa|whatsapp)\b'
                .'|\b(?:kontak|nomor|hubungi)\s*(?:admin|whatsapp|wa)\b/u',
            $t
        ) === 1;
    }
}
