<?php

namespace App\Services;

final class QueryRouter
{
    /**
     * Decide whether a question needs private company data.
     *
     * General knowledge and current-information questions should go directly
     * to the model/web. Retrieval is reserved for company-specific questions
     * so a weak similarity match cannot pull an unrelated database row into
     * the answer.
     */
    public function usesCompanyData(string $query, array $history = []): bool
    {
        $query = mb_strtolower(trim($query), 'UTF-8');

        if ($query === '') {
            return false;
        }

        if (preg_match('/\b(neev|solusindo)\b/u', $query)) {
            return true;
        }

        if ($this->isCompanyCommerceQuestion($query)
            || $this->isCompanyServiceQuestion($query)
            || $this->isCompanyContactQuestion($query)
            || $this->isTechnicalSupportQuestion($query)) {
            return true;
        }

        if (preg_match(
            '/\b(harga(?:nya)?|price|stok(?:nya)?|stock|berapa|'
                .'spek(?:nya)?|spesifikasi|detail|bagaimana|gimana|tersebut)\b/u',
            $query
        )) {
            $recentHistory = collect(array_slice($history, -4))
                ->pluck('content')
                ->implode(' ');

            return preg_match(
                '/\b(neev|produk|product|cctv|kamera|camera|nvr|'
                    .'router|wifi|kabel|cable|ups|printer|layanan|service)\b/u',
                mb_strtolower($recentHistory, 'UTF-8')
            ) === 1;
        }

        return false;
    }

    public function isCompanyCommerceQuestion(string $query): bool
    {
        $commerce = '(?:jual|menjual|beli|membeli|harga|stok|tersedia|'
            .'sell|selling|buy|price|stock|available|carry)';
        $product = '(?:produk|product|barang|cctv|kamera|camera|nvr|dvr|'
            .'router|wifi|kabel|cable|ups|printer|switch|access point|ap)';

        return preg_match(
            "/\b{$commerce}\b.*\b{$product}\b"
                ."|\b{$product}\b.*\b{$commerce}\b/u",
            $query
        ) === 1
            || preg_match(
                '/\b(?:do\s+you|apakah\s+(?:neev\s+)?)'
                    .'\s*(?:have|sell|stock|punya|jual|menjual)\b/u',
                $query
            ) === 1;
    }

    private function isCompanyServiceQuestion(string $query): bool
    {
        return preg_match(
            '/\b(pasang|pemasangan|instalasi|installation|maintenance|'
                .'perawatan|survei|survey|teknisi|technician|layanan|service)\b'
                .'.*\b(cctv|kamera|camera|nvr|dvr|jaringan|network|wifi)\b'
                .'|\b(cctv|kamera|camera|nvr|dvr|jaringan|network|wifi)\b'
                .'.*\b(pasang|pemasangan|instalasi|installation|maintenance|'
                .'perawatan|survei|survey|teknisi|technician|layanan|service)\b/u',
            $query
        ) === 1;
    }

    private function isCompanyContactQuestion(string $query): bool
    {
        return preg_match(
            '/\b(jam\s+operasional|buka|tutup|alamat|address|kontak|contact|'
                .'whatsapp|nomor\s+(?:admin|telepon)|phone|email)\b/u',
            $query
        ) === 1;
    }

    private function isTechnicalSupportQuestion(string $query): bool
    {
        $device = '(?:cctv|kamera|camera|nvr|dvr|router|wifi|jaringan|'
            .'network|kabel|cable|ups)';
        $symptom = '(?:rusak|error|masalah|kendala|offline|mati|putus|'
            .'tidak|gagal|buram|blank|hilang|nampil|tampil|rekam|record|'
            .'broken|issue|problem|disconnect|blurry|working)';

        return preg_match(
            "/\b{$device}\b.*\b{$symptom}\b"
                ."|\b{$symptom}\b.*\b{$device}\b/u",
            $query
        ) === 1;
    }
}
