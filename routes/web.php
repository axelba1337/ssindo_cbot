<?php

use Illuminate\Support\Facades\Route;

// Tambahkan ini di paling atas file routes/web.php
Route::get('/', function () {
    return redirect()->route('chatbot'); 
});

// ==========================
// ROUTE UNTUK CHATBOT (USER)
// ==========================
Route::get('/chatbot', function () {
    return view('chatbot.widget');
})->name('chatbot');

Route::get('/chatbot/widget', function () {
    return view('chatbot.widget-demo');
})->name('chatbot.widget_demo');

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