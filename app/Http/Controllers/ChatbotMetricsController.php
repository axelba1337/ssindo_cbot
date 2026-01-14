<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ChatbotMetricsController extends Controller
{
    // KPI hari ini + ringkasan
    public function today()
    {
        $total = (int) DB::table('chatbot_logs')
            ->whereRaw('created_at::date = CURRENT_DATE')
            ->count();

        // auto-answer = semua source selain 'gemini'
        $auto = (int) DB::table('chatbot_logs')
            ->whereRaw('created_at::date = CURRENT_DATE')
            ->where('source', '<>', 'gemini')
            ->count();

        // rata-rata similarity 7 hari terakhir
        $avgSim = (float) (DB::table('chatbot_logs')
            ->whereRaw("created_at >= CURRENT_DATE - INTERVAL '6 days'")
            ->avg('similarity') ?? 0);

        return response()->json([
            'today_total'       => $total,
            'today_auto'        => $auto,
            'auto_answer_rate'  => $total > 0 ? $auto / $total : 0, // 0..1
            'avg_similarity'    => $avgSim,                         // 0..1 null,
        ]);
    }

    // Agregasi total per-source untuk 7 hari terakhir (untuk bar chart)
    public function trendSources()
    {
        $rows = DB::table('chatbot_logs')
            ->select('source', DB::raw('COUNT(*) AS total'))
            ->whereRaw("created_at >= CURRENT_DATE - INTERVAL '6 days'")
            ->groupBy('source')
            ->get()
            ->keyBy('source');

        $labels = ['product','service','office_hours','contact','faq','gemini'];
        $data   = [];
        foreach ($labels as $s) {
            $data[] = (int) ($rows[$s]->total ?? 0);
        }

        return response()->json([
            'labels' => $labels,
            'data'   => $data,
        ]);
    }
}