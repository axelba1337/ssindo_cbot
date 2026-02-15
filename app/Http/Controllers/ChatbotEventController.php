<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\KnowledgeBuilder;

class ChatbotEventController extends Controller
{
    // -------- FAQ LIST (dipakai daftar tabel) --------
    public function faqIndex()
    {
        $rows = DB::table('faq_qa')
            ->orderBy('id')
            ->get();

        return response()->json($rows);
    }

    // -------- CREATE / UPDATE FAQ + rebuild langsung --------
    public function faqStore(Request $request, KnowledgeBuilder $builder)
    {
        $id       = $request->input('id');          // boleh null (create) atau id (update)
        $intent   = $request->input('intent');
        $question = $request->input('question');
        $answer   = $request->input('answer');

        if (! $question || ! $answer) {
            return response()->json([
                'ok'    => false,
                'error' => 'Pertanyaan dan jawaban wajib diisi.'
            ], 422);
        }

        $data = [
            'intent'    => $intent ?: null,
            'question'  => $question,
            'answer'    => $answer,
            'is_active' => true,
            'updated_at'=> now(),
        ];

        if ($id) {
            DB::table('faq_qa')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = now();
            $id = DB::table('faq_qa')->insertGetId($data);
        }

        // Langsung rebuild knowledge hanya untuk FAQ
        $count = $builder->buildFaq();

        return response()->json([
            'ok'       => true,
            'id'       => $id,
            'rebuilt'  => $count,
        ]);
    }

    // -------- HAPUS FAQ --------
    public function faqDelete($id, KnowledgeBuilder $builder)
    {
        DB::table('faq_qa')->where('id', $id)->delete();

        // rebuild lagi supaya knowledge sinkron
        $count = $builder->buildFaq();

        return response()->json([
            'ok'      => true,
            'rebuilt' => $count,
        ]);
    }

    // API: list pertanyaan belum terjawab (source = gemini, similarity < thresehold)
    public function unanswered()
    {
        $rows = DB::table('chatbot_logs')
            ->where('source', 'gemini')
            ->where('similarity', '<', 0.0)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get([
                'id',
                'session_id',
                'user_message',
                'bot_answer',
                'similarity',
                'created_at',
            ]);

        $data = $rows->map(function ($r) {
            return [
                'id'          => $r->id,
                'session_id'  => $r->session_id,
                'user_message'=> $r->user_message,
                'bot_answer'  => $r->bot_answer,
                'similarity'  => (float) $r->similarity,
                'created_at'  => $r->created_at,
            ];
        });

        return response()->json($data);
    }

    // API: jadikan log tertentu sebagai FAQ + rebuild knowledge
    // [FIX] Hapus LLMInterface dari parameter, cukup KnowledgeBuilder
    public function toFaq(Request $request, int $logId, KnowledgeBuilder $builder)
    {
        $validated = $request->validate([
            'intent'   => ['nullable','string','max:64'],
            'question' => ['required','string'],
            'answer'   => ['required','string'],
        ]);

        // buat baris faq_qa baru
        $faqId = DB::table('faq_qa')->insertGetId([
            'intent'     => $validated['intent'] ?: null,
            'question'   => $validated['question'],
            'answer'     => $validated['answer'],
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // [FIX] Tidak perlu new KnowledgeBuilder, pakai instance yang sudah di-inject
        $builder->buildFaq();

        return response()->json([
            'ok'    => true,
            'faq_id'=> $faqId,
        ]);
    }
}