<?php

$trustedProxies = array_values(array_filter(
    array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
    static fn (string $proxy): bool => $proxy !== ''
        && ! in_array($proxy, ['*', '**', 'REMOTE_ADDR'], true)
));

return [
    'trusted_proxies' => $trustedProxies,

    'rate_limits' => [
        'chatbot_per_minute' => (int) env('CHATBOT_RATE_LIMIT_PER_MINUTE', 10),
        'chatbot_per_hour' => (int) env('CHATBOT_RATE_LIMIT_PER_HOUR', 60),
        'events_per_minute' => (int) env('CHATBOT_EVENT_RATE_LIMIT_PER_MINUTE', 30),
        'admin_api_per_minute' => (int) env('ADMIN_API_RATE_LIMIT_PER_MINUTE', 120),
        'login_failures_per_minute' => (int) env('ADMIN_LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_seconds' => (int) env('ADMIN_LOGIN_DECAY_SECONDS', 60),
    ],
];
