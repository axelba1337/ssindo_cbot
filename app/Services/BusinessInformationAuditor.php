<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BusinessInformationAuditor
{
    public function audit(?string $homepageContent = null): array
    {
        $issues = [];
        $services = Schema::hasTable('services')
            ? DB::table('services')->where('is_active', true)->get()
            : collect();
        $faqs = Schema::hasTable('faq_qa')
            ? DB::table('faq_qa')->where('is_active', true)->get()
            : collect();

        $softwareService = $services->first(function ($service) {
            $text = mb_strtolower(
                "{$service->name} {$service->description}",
                'UTF-8'
            );

            return preg_match('/\b(software|aplikasi|web|mobile)\b/u', $text);
        });

        if ($softwareService) {
            foreach ($faqs as $faq) {
                $answer = mb_strtolower((string) $faq->answer, 'UTF-8');
                if (preg_match(
                    '/\b(tidak|bukan)\b.*\b(aplikasi|mobile|software)\b'
                        .'|\b(not|does not|do not)\b.*\b(application|mobile|software)\b/u',
                    $answer
                )) {
                    $issues[] = $this->issue(
                        'software_mobile_capability_conflict',
                        'high',
                        'FAQ menolak kemampuan software/mobile yang tercatat sebagai layanan aktif.',
                        ["services:{$softwareService->id}", "faq_qa:{$faq->id}"]
                    );
                }
            }
        }

        $specificPriceFaqs = $faqs->filter(
            fn ($faq) => preg_match(
                '/\bmulai\s+dari\b|rp\s*[\d.]+/iu',
                (string) $faq->answer
            )
        );
        $variablePriceFaqs = $faqs->filter(
            fn ($faq) => preg_match(
                '/\b(tidak\s+bisa\s+(?:langsung|disamaratakan)|tergantung)\b/iu',
                (string) $faq->answer
            )
        );
        if ($specificPriceFaqs->isNotEmpty() && $variablePriceFaqs->isNotEmpty()) {
            $issues[] = $this->issue(
                'cctv_price_policy_conflict',
                'high',
                'FAQ memuat harga awal spesifik sekaligus menyatakan biaya tidak dapat ditentukan tanpa survei.',
                $specificPriceFaqs->merge($variablePriceFaqs)
                    ->pluck('id')
                    ->unique()
                    ->map(fn ($id) => "faq_qa:{$id}")
                    ->values()
                    ->all()
            );
        }

        $guaranteeFaqs = $faqs->filter(
            fn ($faq) => preg_match(
                '/\b(1\s+hari\s+kerja|maximal\s+24\s+jam|tidak\s+dikenakan\s+biaya|gratis)\b/iu',
                (string) $faq->answer
            )
        );
        if ($guaranteeFaqs->isNotEmpty()) {
            $issues[] = $this->issue(
                'unverified_survey_promises',
                'medium',
                'FAQ memuat janji waktu atau biaya survei yang tidak didukung tabel layanan.',
                $guaranteeFaqs->pluck('id')
                    ->map(fn ($id) => "faq_qa:{$id}")
                    ->values()
                    ->all()
            );
        }

        if (Schema::hasTable('office_hours')) {
            $hours = DB::table('office_hours')->orderBy('id')->first();
            if ($hours) {
                $expected = [
                    substr((string) $hours->open_time, 0, 5),
                    substr((string) $hours->close_time, 0, 5),
                ];

                foreach ($faqs as $faq) {
                    if (! preg_match_all(
                        '/\b([01]?\d|2[0-3]):[0-5]\d\b/u',
                        (string) $faq->answer,
                        $matches
                    ) || count($matches[0]) < 2) {
                        continue;
                    }

                    if (array_slice($matches[0], 0, 2) !== $expected) {
                        $issues[] = $this->issue(
                            'office_hours_conflict',
                            'high',
                            'FAQ memuat jam operasional yang berbeda dari office_hours.',
                            ["office_hours:{$hours->id}", "faq_qa:{$faq->id}"]
                        );
                    }
                }
            }
        }

        if (Schema::hasTable('company_contacts')) {
            $contacts = DB::table('company_contacts')->get();
            foreach ($contacts->where('is_primary', true)->groupBy('type') as $type => $rows) {
                if ($rows->count() > 1) {
                    $issues[] = $this->issue(
                        'multiple_primary_contacts',
                        'high',
                        "Lebih dari satu kontak {$type} ditandai primary.",
                        $rows->pluck('id')
                            ->map(fn ($id) => "company_contacts:{$id}")
                            ->all()
                    );
                }
            }

            $homepageContent ??= $this->homepageContent();
            $issues = array_merge(
                $issues,
                $this->homepageContactIssues($contacts, $homepageContent)
            );
        }

        return $issues;
    }

    private function homepageContactIssues($contacts, string $homepage): array
    {
        $issues = [];
        $primaryEmail = $contacts->first(
            fn ($contact) => $contact->type === 'email' && $contact->is_primary
        );
        preg_match_all('/mailto:([^"\s>]+)/iu', $homepage, $emailMatches);
        $homepageEmails = array_values(array_unique($emailMatches[1] ?? []));

        if ($primaryEmail && $homepageEmails !== []
            && ! in_array($primaryEmail->value, $homepageEmails, true)) {
            $issues[] = $this->issue(
                'homepage_primary_email_mismatch',
                'high',
                'Email primary di database berbeda dari email yang ditampilkan homepage.',
                ["company_contacts:{$primaryEmail->id}", 'resources/views/welcome.blade.php']
            );
        }

        $primaryAddress = $contacts->first(
            fn ($contact) => $contact->type === 'address' && $contact->is_primary
        );
        preg_match(
            '/<span>Lokasi<\/span><strong>([^<]+)<\/strong>/iu',
            $homepage,
            $addressMatch
        );
        $homepageAddress = trim((string) ($addressMatch[1] ?? ''));

        if ($primaryAddress && $homepageAddress !== ''
            && ! str_contains(
                mb_strtolower($homepageAddress, 'UTF-8'),
                mb_strtolower((string) $primaryAddress->value, 'UTF-8')
            )) {
            $issues[] = $this->issue(
                'homepage_primary_address_mismatch',
                'medium',
                'Alamat primary di database tidak sama dengan alamat rinci pada homepage.',
                ["company_contacts:{$primaryAddress->id}", 'resources/views/welcome.blade.php']
            );
        }

        return $issues;
    }

    private function homepageContent(): string
    {
        $path = resource_path('views/welcome.blade.php');

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    private function issue(
        string $code,
        string $severity,
        string $summary,
        array $records
    ): array {
        return compact('code', 'severity', 'summary', 'records');
    }
}
