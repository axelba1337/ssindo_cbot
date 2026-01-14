<?php

namespace App\Helpers;

class Similarity
{
    /**
     * Hitung cosine similarity antara dua vektor float.
     */
    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0; $magA = 0.0; $magB = 0.0;

        $n = min(count($a), count($b));
        for ($i = 0; $i < $n; $i++) {
            $dot  += $a[$i] * $b[$i];
            $magA += $a[$i] ** 2;
            $magB += $b[$i] ** 2;
        }

        if ($magA == 0.0 || $magB == 0.0) return 0.0;

        return $dot / (sqrt($magA) * sqrt($magB));
    }

    /**
     * Dapatkan top-N hasil similarity dari kumpulan data embedding.
     */
    public static function topMatches(array $queryVec, array $dbItems, int $topK = 5): array
    {
        $scored = [];

        foreach ($dbItems as $item) {
            if (empty($item['embedding'])) continue;
            $vec = json_decode($item['embedding'], true);
            $score = self::cosine($queryVec, $vec);
            $scored[] = ['item' => $item, 'score' => $score];
        }

        // urutkan dari skor tertinggi
        usort($scored, fn($x, $y) => $y['score'] <=> $x['score']);

        return array_slice($scored, 0, $topK);
    }
}