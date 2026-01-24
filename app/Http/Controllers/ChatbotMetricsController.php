<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class ChatbotMetricsController extends Controller
{
    public function today()
    {
        $startToday = now()->startOfDay();
        $endToday   = now()->addDay()->startOfDay();

        $start7d = now()->subDays(6)->startOfDay();

        $total = (int) DB::table('chatbot_logs')
            ->where('created_at', '>=', $startToday)
            ->where('created_at', '<',  $endToday)
            ->count();

        $auto = (int) DB::table('chatbot_logs')
            ->where('created_at', '>=', $startToday)
            ->where('created_at', '<',  $endToday)
            ->where('source', '<>', 'gemini')
            ->count();

        // rata-rata similarity 7 hari terakhir (termasuk hari ini)
        $avgSim = (float) (DB::table('chatbot_logs')
            ->where('created_at', '>=', $start7d)
            ->avg('similarity') ?? 0);

        return response()->json([
            'today_total'      => $total,
            'today_auto'       => $auto,
            'auto_answer_rate' => $total > 0 ? $auto / $total : 0,
            'avg_similarity'   => $avgSim,
        ]);
    }

    public function trendSources()
    {
        $start7d = now()->subDays(6)->startOfDay();

        $rows = DB::table('chatbot_logs')
            ->select('source', DB::raw('COUNT(*) AS total'))
            ->where('created_at', '>=', $start7d)
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