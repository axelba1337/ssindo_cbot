<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\{LLMInterface, GeminiService};

/**
 * ChatbotServiceProvider
 *
 * Provider ini mendaftarkan dependency injection (binding)
 * antara interface LLMInterface dan implementasi GeminiService.
 * Selain itu, provider juga memuat konfigurasi chatbot.php
 * agar dapat diakses lewat config('chatbot.*').
 */
class ChatbotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind interface ke implementasi
        $this->app->bind(LLMInterface::class, GeminiService::class);

        // Gabungkan konfigurasi chatbot ke sistem config Laravel
        $this->mergeConfigFrom(
            config_path('chatbot.php'), 'chatbot'
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bisa digunakan untuk logika awal (misal: memastikan tabel chatbot_knowledge tersedia)
        if (app()->runningInConsole()) {
            $this->publishes([
                config_path('chatbot.php') => config_path('chatbot.php'),
            ], 'chatbot-config');
        }
    }
}