<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ChatbotEventController;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => response()->json([
    'status' => 'ok',
    'message' => 'API connected',
]));

Route::post('/chatbot/query', [ChatbotController::class, 'query'])
    ->middleware('throttle:chatbot')
    ->name('chatbot.query');

Route::post('/chatbot/events', [ChatbotEventController::class, 'store'])
    ->middleware('throttle:chatbot-events')
    ->name('chatbot.events');

// Never expose model discovery outside a local development environment.
if (app()->environment('local')) {
    Route::get('/chatbot/dev/models', function () {
        $key = (string) config('services.gemini.key');

        abort_if($key === '', 503, 'GEMINI_API_KEY is not configured.');

        $v1 = Http::get(
            'https://generativelanguage.googleapis.com/v1/models',
            ['key' => $key]
        )->json();
        $v1b = Http::get(
            'https://generativelanguage.googleapis.com/v1beta/models',
            ['key' => $key]
        )->json();

        return response()->json([
            'v1' => $v1,
            'v1beta' => $v1b,
        ]);
    });
}
