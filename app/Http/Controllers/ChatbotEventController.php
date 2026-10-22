<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaqRequest;
use App\Http\Requests\PromoteUnansweredRequest;
use App\Services\KnowledgeBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChatbotEventController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'type' => [
                'required',
                'string',
                Rule::in(['open', 'close', 'widget_open', 'widget_close', 'rating']),
            ],
            'rating' => ['nullable', 'required_if:type,rating', 'integer', 'between:1,5'],
        ]);

        // Event persistence is optional in the current schema. A validated
        // acknowledgement keeps this public endpoint bounded and predictable.
        return response()->json(['ok' => true], 202);
    }

    // -------- FAQ LIST (dipakai daftar tabel) --------
    public function faqIndex()
    {
        $rows = DB::table('faq_qa')
            ->orderBy('id')
            ->get();

        return response()->json($rows);
    }

    // -------- CREATE / UPDATE FAQ + rebuild langsung --------
    public function faqStore(FaqRequest $request, KnowledgeBuilder $builder)
    {
        $validated = $request->validated();
        $id = isset($validated['id']) ? (int) $validated['id'] : null;

        $data = [
            'intent' => $validated['intent'] ?? null,
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'is_active' => $validated['is_active'] ?? true,
            'updated_at' => now(),
        ];

        if ($id) {
            DB::table('faq_qa')->where('id', $id)->update($data);
        } else {
            $data['created_at'] = now();
            $id = DB::table('faq_qa')->insertGetId($data);
        }

        $synchronized = $builder->syncFaq((int) $id);

        return response()->json([
            'ok' => true,
            'id' => $id,
            'synchronized' => $synchronized,
            'rebuilt' => $synchronized ? 1 : 0,
        ]);
    }

    // -------- HAPUS FAQ --------
    public function faqDelete(int $id, KnowledgeBuilder $builder)
    {
        $deleted = DB::transaction(function () use ($id, $builder) {
            $deleted = DB::table('faq_qa')->where('id', $id)->delete();
            $builder->deleteSourceKnowledge('faq_qa', (int) $id);

            return $deleted;
        });

        if (! $deleted) {
            return response()->json([
                'ok' => false,
                'error' => 'FAQ tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'rebuilt' => 0,
        ]);
    }

    // API: list pertanyaan belum terjawab dari provider LLM.
    public function unanswered()
    {
        $rows = DB::table('chatbot_logs')
            ->whereIn('source', ['llm', 'gemini'])
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
                'id' => $r->id,
                'session_id' => $r->session_id,
                'user_message' => $r->user_message,
                'bot_answer' => $r->bot_answer,
                'similarity' => (float) $r->similarity,
                'created_at' => $r->created_at,
            ];
        });

        return response()->json($data);
    }

    // API: jadikan log tertentu sebagai FAQ + rebuild knowledge
    // [FIX] Hapus LLMInterface dari parameter, cukup KnowledgeBuilder
    public function toFaq(
        PromoteUnansweredRequest $request,
        int $id,
        KnowledgeBuilder $builder
    ) {
        $validated = $request->validated();

        $unansweredExists = DB::table('chatbot_logs')
            ->where('id', $id)
            ->whereIn('source', ['llm', 'gemini'])
            ->where('similarity', '<', 0.0)
            ->exists();

        if (! $unansweredExists) {
            return response()->json([
                'ok' => false,
                'error' => 'Pertanyaan tidak ditemukan.',
            ], 404);
        }

        // buat baris faq_qa baru
        $faqId = DB::table('faq_qa')->insertGetId([
            'intent' => $validated['intent'] ?: null,
            'question' => trim($validated['question']),
            'answer' => trim($validated['answer']),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $builder->syncFaq((int) $faqId);

        return response()->json([
            'ok' => true,
            'faq_id' => $faqId,
        ]);
    }
}
