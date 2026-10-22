<?php

namespace App\Services;

use App\Helpers\Similarity;

final class KnowledgeRetriever
{
    public function rank(
        array $rows,
        ?array $queryVector,
        string $normalizedQuery
    ): array {
        $scored = [];
        $queryVector = $this->validVector($queryVector);

        foreach ($rows as $row) {
            $semanticScore = null;
            $vector = $this->validVector($row->embedding ?? null);

            if ($queryVector && $vector
                && count($queryVector) === count($vector)) {
                $semanticScore = Similarity::cosine($queryVector, $vector);
            }

            // Lexical scoring is calculated per row, not only when every
            // semantic vector fails. This keeps exact fallback matches usable
            // during partial migrations or mixed embedding dimensions.
            [$title, $searchableContent] = $this->lexicalFields($row);
            $lexicalScore = $this->lexicalScore(
                $normalizedQuery,
                $title,
                $searchableContent
            );

            if ($lexicalScore > 0
                && ($semanticScore === null || $lexicalScore >= $semanticScore)) {
                $scored[] = $this->result($row, $lexicalScore, 'lexical');
            } elseif ($semanticScore !== null && $semanticScore > 0) {
                $scored[] = $this->result($row, $semanticScore, 'semantic');
            }
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        return array_slice(
            $scored,
            0,
            max(1, (int) config('chatbot.top_k', 5))
        );
    }

    public function isRelevant(array $match): bool
    {
        $threshold = ($match['retrieval'] ?? null) === 'lexical'
            ? (float) config('chatbot.lexical_threshold', 0.65)
            : (float) config('chatbot.threshold', 0.45);

        return (float) ($match['score'] ?? 0) >= $threshold;
    }

    private function lexicalFields(object $row): array
    {
        $title = (string) $row->title;
        $content = (string) $row->content_text;

        if ((string) $row->kind !== 'faq') {
            return [$title, $content];
        }

        preg_match('/(?:^|\R)Intent:\s*([^\r\n]+)/iu', $content, $intent);
        preg_match('/(?:^|\R)Pertanyaan:\s*([^\r\n]+)/iu', $content, $question);

        return [
            trim($title.' '.($intent[1] ?? '')),
            trim((string) ($question[1] ?? '')),
        ];
    }

    private function lexicalScore(
        string $query,
        string $title,
        string $content
    ): float {
        $tokens = $this->tokens($query);
        if ($tokens === []) {
            return 0.0;
        }

        $contentMatches = count(array_intersect(
            $tokens,
            $this->tokens($content)
        ));
        $titleMatches = count(array_intersect(
            $tokens,
            $this->tokens($title)
        ));

        return (($contentMatches / count($tokens)) * 0.8)
            + (($titleMatches / count($tokens)) * 0.2);
    }

    private function tokens(string $text): array
    {
        $tokens = preg_split(
            '/[^\pL\pN]+/u',
            mb_strtolower($text, 'UTF-8'),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $stopWords = [
            'apa', 'apakah', 'yang', 'dan', 'atau', 'dari', 'untuk',
            'saya', 'ingin', 'tidak', 'the', 'and', 'are', 'can', 'you',
            'jual', 'menjual', 'beli', 'harga', 'stok', 'tersedia', 'ada',
            'sell', 'selling', 'buy', 'price', 'stock', 'available', 'have',
            'layanan', 'service', 'services', 'butuh', 'perlu', 'need',
        ];

        return array_values(array_unique(array_filter(
            $tokens,
            fn ($token) => mb_strlen($token, 'UTF-8') >= 3
                && ! in_array($token, $stopWords, true)
        )));
    }

    private function result(
        object $row,
        float $score,
        string $retrieval
    ): array {
        return [
            'id' => $row->id,
            'kind' => $row->kind,
            'title' => $row->title,
            'content_text' => $row->content_text,
            'source_table' => $row->source_table ?? null,
            'source_id' => isset($row->source_id)
                ? (int) $row->source_id
                : null,
            'score' => $score,
            'retrieval' => $retrieval,
        ];
    }

    private function validVector(mixed $vector): ?array
    {
        if (is_string($vector)) {
            $vector = json_decode($vector, true);
        }

        if (! is_array($vector) || $vector === []
            || collect($vector)->contains(fn ($value) => ! is_numeric($value))) {
            return null;
        }

        return array_map('floatval', $vector);
    }
}
