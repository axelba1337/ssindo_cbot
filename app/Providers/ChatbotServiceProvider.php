<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\{LLMInterface, GeminiService, OpenAIService};

/**
 * ChatbotServiceProvider
 *
 * Provider ini mendaftarkan dependency injection (binding)
 * antara interface LLMInterface dan provider yang dipilih.
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
        // Resolve provider saat aplikasi melakukan dependency injection.
        $this->app->singleton(LLMInterface::class, function ($app) {
            return match (config('chatbot.provider')) {
                'gemini' => $app->make(GeminiService::class),
                'openai' => $app->make(OpenAIService::class),
                default => throw new \RuntimeException(
                    'Unsupported LLM provider: '.config('chatbot.provider')
                ),
            };
        });

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
