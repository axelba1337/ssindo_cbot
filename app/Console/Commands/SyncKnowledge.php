<?php

namespace App\Console\Commands;

use App\Services\KnowledgeBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Command untuk sinkronisasi data baru/berubah
 * tanpa menghapus embedding lama.
 */
class SyncKnowledge extends Command
{
    /**
     * Nama perintah artisan.
     *
     * Jalankan: php artisan chatbot:sync-knowledge
     */
    protected $signature = 'chatbot:sync-knowledge {--prune-only : Hapus knowledge yatim tanpa membuat embedding}';

    /**
     * Deskripsi singkat perintah.
     */
    protected $description = 'Sinkronisasi knowledge base chatbot (update embedding dari data master terbaru).';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Eksekusi perintah.
     */
    public function handle(KnowledgeBuilder $builder): int
    {
        $this->info('🔄 Memulai sinkronisasi knowledge base...');

        try {
            if ($this->option('prune-only')) {
                $removed = $builder->pruneOrphans();
                $this->info("Knowledge yatim dihapus: {$removed}");

                return Command::SUCCESS;
            }

            DB::beginTransaction();

            $totals = $builder->buildAll();

            foreach ($totals as $key => $val) {
                $this->line("- {$key}: {$val} entri diperbarui");
            }

            DB::commit();
            $this->info('✅ Sinkronisasi selesai!');

            return Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('❌ Gagal sinkronisasi: '.$e->getMessage());
            logger()->error('SyncKnowledge error', ['trace' => $e->getTraceAsString()]);

            return Command::FAILURE;
        }
    }
}
