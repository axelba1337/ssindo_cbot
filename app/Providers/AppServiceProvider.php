<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuration is available here (including from config:cache),
        // unlike during Application::withMiddleware() construction.
        TrustProxies::flushState();
        $trustedProxies = config('security.trusted_proxies', []);
        if ($trustedProxies !== []) {
            TrustProxies::at($trustedProxies);
        }

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin());

        RateLimiter::for('chatbot', function (Request $request): array {
            $ip = $request->ip() ?: 'unknown';

            return [
                Limit::perMinute(max(
                    1,
                    (int) config('security.rate_limits.chatbot_per_minute', 10)
                ))->by("chatbot-minute:{$ip}"),
                Limit::perHour(max(
                    1,
                    (int) config('security.rate_limits.chatbot_per_hour', 60)
                ))->by("chatbot-hour:{$ip}"),
            ];
        });

        RateLimiter::for('chatbot-events', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('security.rate_limits.events_per_minute', 30))
        )->by('chatbot-events:'.($request->ip() ?: 'unknown')));

        RateLimiter::for('admin-api', fn (Request $request) => Limit::perMinute(
            max(1, (int) config('security.rate_limits.admin_api_per_minute', 120))
        )->by('admin-api:'.($request->user()?->getAuthIdentifier() ?: $request->ip())));

        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
