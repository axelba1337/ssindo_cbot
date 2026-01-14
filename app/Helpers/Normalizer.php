<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class Normalizer
{
    /**
     * Normalisasi teks untuk retrieval/embedding:
     * - lowercase + trim + collapse spaces
     * - terapkan rules dari tabel text_normalization_rules (is_active = true), urut priority DESC
     * - rule_type: "replace" → word-boundary replace; "regex" → preg_replace /ui
     * - fallback kecil untuk variasi tulis "wa"/"whatsapp" dkk
     */
    public static function text(string $s): string
    {
        // 1) Normalisasi dasar
        $s = trim($s);
        $s = mb_strtolower($s, 'UTF-8');
        // Jangan hapus semua simbol dulu—biarkan tanda tanya/titik tidak mengganggu boundary
        $s = preg_replace('/\s+/u', ' ', $s);

        // 2) Ambil rules aktif (priority DESC)
        $rules = DB::table('text_normalization_rules')
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();

        foreach ($rules as $r) {
            $pat  = (string) $r->pattern;
            $repl = (string) $r->replacement;

            if ($r->rule_type === 'regex') {
                // regex unicode + case-insensitive
                // contoh: pattern: "(?:wa|w\.a)" replacement: "whatsapp"
                $s = @preg_replace('/'.$pat.'/ui', $repl, $s);
            } else {
                // replace berbasis kata: \bPATTERN\b
                // contoh: "wa" → "whatsapp", "gk" → "tidak"
                $rx = '/\b'.preg_quote($pat, '/').'\b/u';
                $s = preg_replace($rx, $repl, $s);
            }
        }

        // 3) Fallback kecil (variasi umum yang sering lolos aturan)
        //    Tidak wajib, tapi membantu kasus "wa?", "wa.", "wasap", dsb.
        $fallbackMap = [
            'w.a'   => 'whatsapp',
            'wa.'   => 'whatsapp',
            'wa:'   => 'whatsapp',
            'wa?'   => 'whatsapp',
            'wasap' => 'whatsapp',
        ];
        foreach ($fallbackMap as $k => $v) {
            $rx = '/\b'.preg_quote($k, '/').'\b/u';
            $s  = preg_replace($rx, $v, $s);
        }

        // 4) Rapikan spasi akhir (jaga tetap ringan)
        $s = preg_replace('/\s+/u', ' ', trim($s));

        return $s;
    }

    /**
     * Pastikan seluruh nilai vektor bertipe float.
     */
    public static function vector(array $vec): array
    {
        return array_map(fn($v) => (float) $v, $vec);
    }
}