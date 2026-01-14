<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\LLMInterface;
use App\Helpers\Normalizer;
use App\Helpers\Similarity;

class ChatbotController extends Controller
{
    public function __construct(private LLMInterface $llm) {}

    public function query(Request $request)
    {
        $message     = (string) ($request->input('message') ?? '');
        $rawSession  = $request->input('session_id');                           // token/string dari client (boleh null/uuid/numeric)
        $sessionId   = $this->resolveSessionId($rawSession);                    // pastikan BIGINT id dari tabel chatbot_sessions

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

        // =========================
        // NORMAL FLOW CHATBOT
        // =========================

        // Embedding query
        $qVec = $this->llm->getEmbedding($normalized);
        if (!$qVec) {
            // kalau embedding gagal, fallback aman
            $fallback = 
            "Maaf, saya belum dapat memberikan jawaban terbaik untuk pertanyaan ini.\n\n" .
            "Silakan tekan tombol \"WhatsApp Admin\" di bawah ini agar staff kami dapat menjawab pertanyaan Anda, " .
            "atau ketik \"no wa\" di pesan dan kirim jika tombol tersebut tidak muncul.";

            $this->log($sessionId, $message, $fallback, 'gemini', null, null);
            return response()->json([
                'answer'        => $fallback,
                'source'        => 'gemini',
                'similarity'    => null,
                'cta_whatsapp'  => $this->buildWhatsAppCta(true, $message),
                'session_id'    => $sessionId,
                'session_token' => $rawSession,
            ]);
        }

        // Ambil knowledge & hitung cosine similarity
        $rows = DB::table('chatbot_knowledge')
            ->select('id','kind','title','content_text','embedding')
            ->orderByDesc('updated_at')
            ->get();

        $scored = [];
        foreach ($rows as $r) {
            if (!$r->embedding) continue;
            $vec = is_string($r->embedding) ? json_decode($r->embedding, true) : $r->embedding;
            if (!is_array($vec)) continue;
            $score = Similarity::cosine($qVec, $vec);
            $scored[] = [
                'id'           => $r->id,
                'kind'         => $r->kind,
                'title'        => $r->title,
                'content_text' => $r->content_text,
                'score'        => $score,
            ];
        }

        // Urutkan & ambil top-K
        usort($scored, fn($a,$b) => $b['score'] <=> $a['score']);
        $topK = array_slice($scored, 0, (int) config('chatbot.top_k', 5));
        $best = $topK[0] ?? null;

        $threshold  = (float) config('chatbot.threshold', 0.50);
        $answer     = '';
        $source     = 'gemini';
        $similarity = $best['score'] ?? null;

        // Bangun konteks dari top-K (service + office_hours, dll)
        $context = array_map(
            fn($x) => $x['content_text'],
            array_slice($topK, 0, (int) config('chatbot.llm.max_context_items', 3))
        );

        if ($best && $best['score'] >= $threshold) {
            // Jawaban disintesis oleh LLM, source tetap dari best match
            $answer = $this->llm->chat($message, $context);
            $source = (string) $best['kind'];
        } else {
            // Fallback ke LLM dengan konteks top-3
            $answer = $this->llm->chat($message, $context);
            $source = 'gemini';
        }

        // Jika fallback / skor rendah, ganti jawaban dengan pesan ramah + alasan
        if ($source === 'gemini' || ($similarity !== null && $similarity < $threshold)) {
            $answer =
            "Maaf, saya belum dapat memberikan jawaban terbaik untuk pertanyaan ini.\n\n" .
            "Silakan tekan tombol \"WhatsApp Admin\" di bawah ini agar staff kami dapat menjawab pertanyaan Anda, " .
            "atau ketik \"no wa\" di pesan dan kirim jika tombol tersebut tidak muncul.";
            $source = 'gemini';
        }


        // CTA WA (tampilkan jika fallback / skor di bawah threshold)
        $cta = $this->buildWhatsAppCta(
            $source === 'gemini' || ($similarity !== null && $similarity < $threshold),
            $message
        );

        // Simpan log
        $this->log($sessionId, $message, $answer, $source, $similarity, $topK);

        return response()->json([
            'answer'        => $answer,
            'source'        => $source,
            'similarity'    => $similarity,
            'cta_whatsapp'  => $cta,
            'session_id'    => $sessionId,
            'session_token' => $rawSession,
        ]);
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