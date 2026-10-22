<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('faq_qa')) {
            return;
        }

        $faq = DB::table('faq_qa')
            ->where('intent', 'aplikasi_mobile')
            ->where('answer', 'like', '%bukan aplikasi mobile%')
            ->first();

        if (! $faq) {
            return;
        }

        $answer = 'Ya, Neev menyediakan layanan Software Development untuk '
            .'pembuatan aplikasi web, mobile, dan aplikasi bisnis custom. '
            .'Kebutuhan fitur, integrasi, jadwal, dan biaya perlu '
            .'dikonsultasikan dengan admin.';

        DB::table('faq_qa')->where('id', $faq->id)->update([
            'answer' => $answer,
            'updated_at' => now(),
        ]);

        if (! Schema::hasTable('chatbot_knowledge')) {
            return;
        }

        DB::table('chatbot_knowledge')
            ->where('source_table', 'faq_qa')
            ->where('source_id', $faq->id)
            ->update([
                'title' => 'FAQ: aplikasi_mobile',
                'content_text' => "Intent: aplikasi_mobile\n"
                    ."Pertanyaan: {$faq->question}\n"
                    ."Jawaban: {$answer}",
                // The old vector represented the contradictory answer. Lexical
                // retrieval remains available until the next targeted sync.
                'embedding' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Do not restore a business statement known to contradict active
        // services. A rollback requires an explicit verified replacement.
    }
};
