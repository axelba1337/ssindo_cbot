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
            'products'        => $this->buildProducts(),
            'services'        => $this->buildServices(),
            'office_hours'    => $this->buildOfficeHours(),
            'company_contacts'=> $this->buildCompanyContacts(),
            'faq_qa'          => Schema::hasTable('faq_qa') ? $this->buildFaq() : 0,
        ];
        return $totals;
    }

    public function buildProducts(): int
    {
        $rows = DB::table('products as p')
            ->leftJoin('product_categories as c', 'c.id', '=', 'p.category_id')
            ->select('p.id','p.sku','p.name','p.description','p.unit','p.stock','p.reorder_level','p.price',
                     'c.name as category_name','c.code as category_code')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $title = "Produk: {$r->name}";
            $content = $this->fmtProduct($r);
            $this->upsertWithEmbedding('product', 'products', $r->id, $title, $content);
            $count++;
        }
        return $count;
    }

    public function buildServices(): int
    {
        $rows = DB::table('services as s')
            ->leftJoin('service_categories as c', 'c.id', '=', 's.category_id')
            ->select('s.id','s.name','s.description','s.is_active','s.price',
                     'c.name as category_name','c.slug as category_slug')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $title = "Layanan: {$r->name}";
            $content = $this->fmtService($r);
            $this->upsertWithEmbedding('service', 'services', $r->id, $title, $content);
            $count++;
        }
        return $count;
    }

    public function buildOfficeHours(): int
    {
        // desain single-row (09:00–17:00 Asia/Jakarta)
        $row = DB::table('office_hours')->orderBy('id')->first();
        if (!$row) return 0;

        $title = "Jam Operasional Perusahaan";
        $content = $this->fmtOfficeHours($row);
        // source_id bisa NULL untuk entitas global seperti ini
        $this->upsertWithEmbedding('office_hours', 'office_hours', $row->id, $title, $content);
        return 1;
    }

    public function buildCompanyContacts(): int
    {
        $rows = DB::table('company_contacts')
            ->select('id','type','value','is_primary')
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
            ->select('id','intent','question','answer')
            ->get();

        $count = 0;
        foreach ($rows as $r) {
            $title = $r->intent ? "FAQ: {$r->intent}" : "FAQ";
            $content = $this->fmtFaq($r);
            $this->upsertWithEmbedding('faq', 'faq_qa', $r->id, $title, $content);
            $count++;
        }
        return $count;
    }

    // --------------------- Helpers ---------------------

    private function upsertWithEmbedding(string $kind, string $table, ?int $sourceId, string $title, string $content): void
    {
        $emb = $this->llm->getEmbedding($content);

        DB::table('chatbot_knowledge')->updateOrInsert(
            ['source_table' => $table, 'source_id' => $sourceId],
            [
                'kind'         => $kind,
                'title'        => $title,
                'content_text' => $content,
                'embedding'    => $emb ? json_encode($emb) : null,
                'updated_at'   => now(),
            ]
        );
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
        $price  = is_null($r->price) ? 'Biaya detail perlu survei/hubungi admin.' : 'Tersedia estimasi biaya (hubungi admin).';
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
            "Untuk pertanyaan di luar jam kerja, akan dibalas pada hari/jam operasional."
        );
    }

    private function fmtContact(object $r): string
    {
        $label = match ($r->type) {
            'whatsapp' => 'WhatsApp',
            'email'    => 'Email',
            'address'  => 'Alamat',
            'website'  => 'Website',
            default    => ucfirst($r->type),
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