<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\{
    ChatbotController,
    ChatbotEventController,
    ChatbotMetricsController
};

// Healthcheck
Route::get('/ping', fn () => response()->json([
    'status'  => 'ok',
    'message' => 'API connected',
]));

// Chatbot utama
Route::post('/chatbot/query', [ChatbotController::class, 'query'])
    ->name('chatbot.query');

// Event non-chat (opsional: open widget, close widget, rating, dll)
Route::post('/chatbot/events', [ChatbotEventController::class, 'store'])
    ->name('chatbot.events');

// ======================
// Admin metrics (Dashboard)
// ======================
Route::prefix('admin/metrics')->group(function () {
    Route::get('/today', [ChatbotMetricsController::class, 'today'])
        ->name('admin.metrics.today');

    Route::get('/trend-sources', [ChatbotMetricsController::class, 'trendSources'])
        ->name('admin.metrics.trendSources');
});

// ======================
// Admin FAQ API (form FAQ page)
// GET  /api/admin/faq        → list semua FAQ
// POST /api/admin/faq        → simpan & rebuild knowledge
// DELETE /api/admin/faq/{id} → hapus FAQ
// ======================
Route::prefix('admin')->group(function () {
    Route::get('/faq', [ChatbotEventController::class, 'faqIndex'])
        ->name('admin.faq.index');

    Route::post('/faq', [ChatbotEventController::class, 'faqStore'])
        ->name('admin.faq.store');

    Route::delete('/faq/{id}', [ChatbotEventController::class, 'faqDelete'])
        ->name('admin.faq.delete');
    
    Route::get('/unanswered', [ChatbotEventController::class, 'unanswered']);

    Route::post('/unanswered/{id}/to-faq', [ChatbotEventController::class, 'toFaq']);    
});

// ======================
// Dev helper: cek daftar model Gemini
// ======================
Route::get('/chatbot/dev/models', function () {
    $key   = env('GEMINI_API_KEY');

    $v1    = Http::get("https://generativelanguage.googleapis.com/v1/models?key={$key}")
                ->json();
    $v1b   = Http::get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}")
                ->json();

    return response()->json([
        'v1'     => $v1,
        'v1beta' => $v1b,
    ]);
});