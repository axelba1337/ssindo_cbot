<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChatbotSessionManager
{
    public function resolve(?string $token): array
    {
        $clientToken = $token ?: $this->generateToken();
        $storedIdentifier = $this->storedIdentifier($clientToken);

        $existingId = DB::table('chatbot_sessions')
            ->where('user_identifier', $storedIdentifier)
            ->orderByDesc('id')
            ->value('id');

        if ($existingId) {
            return [
                'id' => (int) $existingId,
                'token' => $clientToken,
            ];
        }

        $sessionId = DB::table('chatbot_sessions')->insertGetId([
            'user_identifier' => $storedIdentifier,
            'started_at' => now(),
            // Do not retain visitor IP addresses or full user-agent strings.
            'meta' => null,
        ]);

        return [
            'id' => (int) $sessionId,
            'token' => $clientToken,
        ];
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function storedIdentifier(string $token): string
    {
        $key = (string) config('app.key');

        if ($key === '') {
            throw new RuntimeException('APP_KEY must be configured.');
        }

        return 'v2:'.hash_hmac('sha256', $token, $key);
    }
}
