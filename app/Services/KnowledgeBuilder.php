<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KnowledgeBuilder
{
    public function __construct(private LLMInterface $llm) {}

    /**
     * Build seluruh knowledge dari master tables ke chatbot_knowledge.
     * Return ringkasan jumlah baris yang diproses.
     */
    public function buildAll(): array
    {
        $totals = [
            'products' => $this->buildProducts(),
            'services' => $this->buildServices(),
            'office_hours' => $this->buildOfficeHours(),
            'company_contacts' => $this->buildCompanyContacts(),
            'faq_qa' => Schema::hasTable('faq_qa') ? $this->buildFaq() : 0,
        ];

        $totals['orphaned_knowledge_removed'] = $this->pruneOrphans();

        return $totals;
    }

    public function buildProducts(): int
    {
        $rows = DB::table('products as p')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->select('p.id', 'p.sku', 'p.name', 'p.description', 'p.unit', 'p.stock', 'p.reorder_level', 'p.price',
                'c.name as category_name', 'c.code as category_code')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $title = "Produk: {$r->name}";
            $content = $this->fmtProduct($r);
            $embeddingText = trim(
                "Nama: {$r->name}\n"
                ."SKU: {$r->sku}\n"
                ."Kategori: {$r->category_name} ({$r->category_code})\n"
                .($r->description ? "Deskripsi: {$r->description}" : '')
            );
            $this->upsertWithEmbedding(
                'product',
                'products',
                $r->id,
                $title,
                $content,
                $embeddingText
            );
            $count++;
        }

        return $count;
    }

    public function buildServices(): int
    {
        $rows = DB::table('services as s')
            ->leftJoin('service_categories as c', 'c.id', '=', 's.category_id')
            ->where('s.is_active', true)
            ->select('s.id', 's.name', 's.description', 's.is_active', 's.price',
                'c.name as category_name', 'c.slug as category_slug')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $title = "Layanan: {$r->name}";
            $content = $this->fmtService($r);
            $embeddingText = trim(
                "Nama Layanan: {$r->name}\n"
                ."Kategori: {$r->category_name} ({$r->category_slug})\n"
                .($r->description ? "Deskripsi: {$r->description}" : '')
            );
            $this->upsertWithEmbedding(
                'service',
                'services',
                $r->id,
                $title,
                $content,
                $embeddingText
            );
            $count++;
        }

        return $count;
    }

    public function buildOfficeHours(): int
    {
        // desain single-row (09:00–17:00 Asia/Jakarta)
        $row = DB::table('office_hours')->orderBy('id')->first();
        if (! $row) {
            return 0;
        }

        $title = 'Jam Operasional Perusahaan';
        $content = $this->fmtOfficeHours($row);
        // source_id bisa NULL untuk entitas global seperti ini
        $this->upsertWithEmbedding('office_hours', 'office_hours', $row->id, $title, $content);

        return 1;
    }

    public function buildCompanyContacts(): int
    {
        $rows = DB::table('company_contacts')
            ->select('id', 'type', 'value', 'is_primary')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $title = "Kontak ({$r->type})";
            $content = $this->fmtContact($r);
            $this->upsertWithEmbedding('contact', 'company_contacts', $r->id, $title, $content);
            $count++;
        }

        return $count;
    }

    public function buildFaq(): int
    {
        $rows = DB::table('faq_qa')
            ->where('is_active', true)
            ->select('id', 'intent', 'question', 'answer')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $this->upsertFaq($r);
            $count++;
        }

        return $count;
    }

    public function syncFaq(int $id): bool
    {
        $row = DB::table('faq_qa')
            ->where('id', $id)
            ->where('is_active', true)
            ->select('id', 'intent', 'question', 'answer')
            ->first();

        if (! $row) {
            $this->deleteSourceKnowledge('faq_qa', $id);

            return false;
        }

        $this->upsertFaq($row);

        return true;
    }

    public function deleteSourceKnowledge(string $table, int $sourceId): int
    {
        return DB::table('chatbot_knowledge')
            ->where('source_table', $table)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public function pruneOrphans(): int
    {
        $sources = [
            'products' => DB::table('products')->pluck('id')->all(),
            'services' => DB::table('services')
                ->where('is_active', true)
                ->pluck('id')
                ->all(),
            'office_hours' => DB::table('office_hours')->pluck('id')->all(),
            'company_contacts' => DB::table('company_contacts')->pluck('id')->all(),
        ];

        if (Schema::hasTable('faq_qa')) {
            $sources['faq_qa'] = DB::table('faq_qa')
                ->where('is_active', true)
                ->pluck('id')
                ->all();
        }

        $removed = 0;
        foreach ($sources as $table => $validIds) {
            $query = DB::table('chatbot_knowledge')
                ->where('source_table', $table);

            $query->where(function ($query) use ($validIds) {
                $query->whereNull('source_id');
                if ($validIds !== []) {
                    $query->orWhereNotIn('source_id', $validIds);
                }
            });

            if ($validIds === []) {
                $query = DB::table('chatbot_knowledge')
                    ->where('source_table', $table);
            }

            $removed += $query->delete();
        }

        return $removed;
    }

    // --------------------- Helpers ---------------------

    private function upsertWithEmbedding(
        string $kind,
        string $table,
        ?int $sourceId,
        string $title,
        string $content,
        ?string $embeddingText = null
    ): void {
        $existing = DB::table('chatbot_knowledge')
            ->where('source_table', $table)
            ->where('source_id', $sourceId)
            ->first();

        $embeddingText = trim($embeddingText ?? $content);
        $contentChanged = ! $existing
            || (string) $existing->kind !== $kind
            || (string) $existing->title !== $title
            || (string) $existing->content_text !== $content;
        $semanticTextChanged = ! $existing
            || $this->semanticTextFromExisting($kind, $existing) !== $embeddingText;
        $hasValidEmbedding = $existing
            && $this->isValidEmbedding($existing->embedding);
        $needsEmbedding = $semanticTextChanged || ! $hasValidEmbedding;

        if (! $contentChanged && ! $needsEmbedding) {
            return;
        }

        $values = [
            'kind' => $kind,
            'title' => $title,
            'content_text' => $content,
            'updated_at' => now(),
        ];

        if ($needsEmbedding) {
            $embedding = $this->llm->getEmbedding($embeddingText);
            $values['embedding'] = $this->isValidEmbedding($embedding)
                ? json_encode(array_map('floatval', $embedding))
                : null;
        }

        DB::table('chatbot_knowledge')->updateOrInsert(
            ['source_table' => $table, 'source_id' => $sourceId],
            $values
        );
    }

    private function upsertFaq(object $row): void
    {
        $title = $row->intent ? "FAQ: {$row->intent}" : 'FAQ';
        $content = $this->fmtFaq($row);
        $embeddingText = trim(
            ($row->intent ? "Intent: {$row->intent}\n" : '')
            ."Pertanyaan: {$row->question}"
        );

        $this->upsertWithEmbedding(
            'faq',
            'faq_qa',
            $row->id,
            $title,
            $content,
            $embeddingText
        );
    }

    private function isValidEmbedding(mixed $embedding): bool
    {
        if (is_string($embedding)) {
            $embedding = json_decode($embedding, true);
        }

        $expectedDimensions = max(
            1,
            (int) config('chatbot.gemini.embedding_dimension', 768)
        );

        return is_array($embedding)
            && count($embedding) === $expectedDimensions
            && collect($embedding)->doesntContain(
                static fn ($value) => ! is_numeric($value)
            );
    }

    private function semanticTextFromExisting(string $kind, object $row): string
    {
        $content = trim((string) $row->content_text);

        if ($kind === 'faq') {
            preg_match('/(?:^|\R)Intent:\s*([^\r\n]+)/iu', $content, $intent);
            preg_match('/(?:^|\R)Pertanyaan:\s*([^\r\n]+)/iu', $content, $question);

            return trim(
                (! empty($intent[1]) ? "Intent: {$intent[1]}\n" : '')
                .'Pertanyaan: '.($question[1] ?? '')
            );
        }

        if (in_array($kind, ['product', 'service'], true)) {
            $ignoredPrefixes = $kind === 'product'
                ? ['Unit:', 'Harga:']
                : ['Status:', 'Biaya detail', 'Tersedia estimasi'];

            return collect(preg_split('/\R/u', $content) ?: [])
                ->reject(function ($line) use ($ignoredPrefixes) {
                    $line = trim((string) $line);

                    return collect($ignoredPrefixes)->contains(
                        fn ($prefix) => str_starts_with($line, $prefix)
                    );
                })
                ->map(fn ($line) => trim((string) $line))
                ->filter()
                ->implode("\n");
        }

        return $content;
    }

    private function fmtProduct(object $r): string
    {
        // Jangan sebut harga spesifik jika NULL; hanya informatif.
        $price = is_null($r->price) ? 'Harga: silakan hubungi admin.' : 'Harga tersedia (hubungi admin).';

        return trim(
            "Nama: {$r->name}\n".
            "SKU: {$r->sku}\n".
            "Kategori: {$r->category_name} ({$r->category_code})\n".
            ($r->description ? "Deskripsi: {$r->description}\n" : '').
            "Unit: {$r->unit}; Stok: {$r->stock}; Reorder: {$r->reorder_level}\n".
            "{$price}"
        );
    }

    private function fmtService(object $r): string
    {
        $active = $r->is_active ? 'Aktif' : 'Nonaktif';
        $price = is_null($r->price) ? 'Biaya detail perlu survei/hubungi admin.' : 'Tersedia estimasi biaya (hubungi admin).';

        return trim(
            "Nama Layanan: {$r->name}\n".
            "Kategori: {$r->category_name} ({$r->category_slug})\n".
            ($r->description ? "Deskripsi: {$r->description}\n" : '').
            "Status: {$active}\n".
            "{$price}"
        );
    }

    private function fmtOfficeHours(object $r): string
    {
        // contoh: Buka setiap hari 09:00–17:00 WIB
        $note = $r->note ? "Catatan: {$r->note}\n" : '';

        return trim(
            "Jam Operasional: {$r->open_time}–{$r->close_time} ({$r->timezone})\n".
            $note.
            'Untuk pertanyaan di luar jam kerja, akan dibalas pada hari/jam operasional.'
        );
    }

    private function fmtContact(object $r): string
    {
        $label = match ($r->type) {
            'whatsapp' => 'WhatsApp',
            'email' => 'Email',
            'address' => 'Alamat',
            'website' => 'Website',
            default => ucfirst($r->type),
        };
        $primary = $r->is_primary ? ' (primary)' : '';

        return "{$label}{$primary}: {$r->value}";
    }

    private function fmtFaq(object $r): string
    {
        $intent = $r->intent ? "Intent: {$r->intent}\n" : '';

        return trim(
            "{$intent}".
            "Pertanyaan: {$r->question}\n".
            "Jawaban: {$r->answer}"
        );
    }
}
