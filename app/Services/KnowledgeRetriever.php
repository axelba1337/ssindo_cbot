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

        if ($queryVector) {
            foreach ($rows as $row) {
                if (!$row->embedding) continue;

                $vector = is_string($row->embedding)
                    ? json_decode($row->embedding, true)
                    : $row->embedding;

                if (!is_array($vector)
                    || count($queryVector) !== count($vector)) {
                    continue;
                }

                $scored[] = $this->result(
                    $row,
                    Similarity::cosine($queryVector, $vector),
                    'semantic'
                );
            }
        }

        // This covers provider switches, missing embeddings, and temporary
        // embedding API failures. FAQ answers are deliberately excluded from
        // lexical matching so generic words in an answer cannot hijack intent.
        if ($scored === []) {
            foreach ($rows as $row) {
                [$title, $searchableContent] = $this->lexicalFields($row);
                $score = $this->lexicalScore(
                    $normalizedQuery,
                    $title,
                    $searchableContent
                );

                if ($score > 0) {
                    $scored[] = $this->result($row, $score, 'lexical');
                }
            }
        }

        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

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
        ];

        return array_values(array_unique(array_filter(
            $tokens,
            fn ($token) => mb_strlen($token, 'UTF-8') >= 3
                && !in_array($token, $stopWords, true)
        )));
    }

    private function result(
        object $row,
        float $score,
        string $retrieval
    ): array {
        return [
            'id'           => $row->id,
            'kind'         => $row->kind,
            'title'        => $row->title,
            'content_text' => $row->content_text,
            'score'        => $score,
            'retrieval'    => $retrieval,
        ];
    }
}
