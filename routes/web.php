<?php

use Illuminate\Support\Facades\Route;

// ==========================
// ROUTE UNTUK CHATBOT (USER)
// ==========================
Route::get('/chatbot', function () {
    return view('chatbot.widget');
})->name('chatbot.widget');

Route::get('/chatbot/widget', function () {
    return view('chatbot.widget-demo');
})->name('chatbot.widget');

// ==========================
// ROUTE UNTUK ADMIN PANEL
// ==========================
Route::prefix('admin')->group(function () {
    Route::get('/', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/faq', function () {
        return view('admin.faq');
    })->name('admin.faq');

    Route::get('/unanswered', function () {
        return view('admin.unanswered');
    })->name('admin.unanswered');

});