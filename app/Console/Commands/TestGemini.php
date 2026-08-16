<?php

namespace App\Console\Commands;

use App\Services\LLMInterface;
use Illuminate\Console\Command;

final class TestGemini extends Command
{
    protected $signature = 'chatbot:test-gemini';

    protected $description = 'Verify Gemini chat and embedding connectivity without exposing credentials.';

    public function handle(): int
    {
        if (trim((string) config('services.gemini.key', '')) === '') {
            $this->error(
                'GEMINI_API_KEY is missing. Add it to your runtime environment.'
            );

            return self::FAILURE;
        }

        try {
            $gemini = app(LLMInterface::class);
            $embedding = $gemini->getEmbedding('Neev Solusindo CCTV');

            if (!$embedding) {
                $this->error('Gemini embedding request failed. Check storage/logs/laravel.log.');

                return self::FAILURE;
            }

            $chat = $gemini->chat('Balas hanya dengan: GEMINI_OK');
            if (!$chat->successful) {
                $this->error('Gemini chat request failed. Check storage/logs/laravel.log.');

                return self::FAILURE;
            }

            $this->info('Gemini API: OK');
            $this->line('Chat model: '.str_replace(
                'models/',
                '',
                (string) config('chatbot.models.chat')
            ));
            $this->line('Embedding model: '.str_replace(
                'models/',
                '',
                (string) config('chatbot.models.embed')
            ));
            $this->line('Embedding dimension: '.count($embedding));
            $this->line('Response: '.$chat->text);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }
}
