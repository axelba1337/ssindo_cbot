<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\KnowledgeBuilder;
use Illuminate\Support\Facades\DB;

/**
 * Command untuk membangun ulang seluruh knowledge base chatbot.
 * Dipakai pertama kali setelah migrasi atau update besar data master.
 */
class BuildKnowledge extends Command
{
    /**
     * Nama perintah artisan.
     *
     * Jalankan: php artisan chatbot:build-knowledge
     */
    protected $signature = 'chatbot:build-knowledge';

    /**
     * Deskripsi singkat perintah.
     */
    protected $description = 'Bangun seluruh knowledge base chatbot dari tabel master (produk, layanan, kontak, dll).';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Eksekusi perintah.
     */
    public function handle(KnowledgeBuilder $builder): int
    {
        $this->info('🚀 Memulai proses build knowledge base...');

        try {
            // Never retain vectors made by another provider or model during a
            // full rebuild. If Gemini is unavailable, lexical retrieval remains
            // available instead of comparing incompatible embeddings.
            DB::table('chatbot_knowledge')->update(['embedding' => null]);

            $totals = $builder->buildAll();

            foreach ($totals as $key => $val) {
                $this->line("- {$key}: {$val} entri diproses");
            }

            $this->info('✅ Build knowledge base selesai!');
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('❌ Terjadi kesalahan: ' . $e->getMessage());
            logger()->error('BuildKnowledge error', ['trace' => $e->getTraceAsString()]);
            return Command::FAILURE;
        }
    }
}
