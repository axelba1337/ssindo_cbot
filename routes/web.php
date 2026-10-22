<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\ChatbotEventController;
use App\Http\Controllers\ChatbotMetricsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/chatbot', function () {
    return view('chatbot.widget');
})->name('chatbot');

Route::get('/chatbot/widget', function () {
    return view('chatbot.widget-demo');
})->name('chatbot.widget_demo');

Route::get('/admin/login', [AdminAuthController::class, 'create'])
    ->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'store'])
    ->name('admin.login.store');

Route::prefix('admin')->middleware('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/faq', function () {
        return view('admin.faq');
    })->name('admin.faq');

    Route::get('/unanswered', function () {
        return view('admin.unanswered');
    })->name('admin.unanswered');

    Route::post('/logout', [AdminAuthController::class, 'destroy'])
        ->name('admin.logout');
});

// Existing admin API URLs stay stable, while the web middleware group adds
// session authentication and CSRF validation to state-changing requests.
Route::prefix('api/admin')
    ->middleware(['admin', 'throttle:admin-api'])
    ->group(function () {
        Route::prefix('metrics')->group(function () {
            Route::get('/today', [ChatbotMetricsController::class, 'today'])
                ->name('admin.metrics.today');
            Route::get('/trend-sources', [ChatbotMetricsController::class, 'trendSources'])
                ->name('admin.metrics.trendSources');
        });

        Route::get('/faq', [ChatbotEventController::class, 'faqIndex'])
            ->name('admin.faq.index');
        Route::post('/faq', [ChatbotEventController::class, 'faqStore'])
            ->name('admin.faq.store');
        Route::delete('/faq/{id}', [ChatbotEventController::class, 'faqDelete'])
            ->whereNumber('id')
            ->name('admin.faq.delete');
        Route::get('/unanswered', [ChatbotEventController::class, 'unanswered'])
            ->name('admin.unanswered.index');
        Route::post('/unanswered/{id}/to-faq', [ChatbotEventController::class, 'toFaq'])
            ->whereNumber('id')
            ->name('admin.unanswered.to-faq');
    });
