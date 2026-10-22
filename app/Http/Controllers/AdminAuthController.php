<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()?->can('access-admin')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:1000'],
        ]);

        $key = $this->throttleKey($credentials['email'], $request->ip());
        $maxAttempts = max(
            1,
            (int) config('security.rate_limits.login_failures_per_minute', 5)
        );

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return back()
                ->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi nanti.'])
                ->onlyInput('email')
                ->setStatusCode(429)
                ->withHeaders([
                    'Retry-After' => (string) RateLimiter::availableIn($key),
                ]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit(
                $key,
                max(1, (int) config('security.rate_limits.login_decay_seconds', 60))
            );

            return back()
                ->withErrors(['email' => 'Email atau password tidak valid.'])
                ->onlyInput('email');
        }

        if (! $request->user()?->can('access-admin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            abort(403);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    private function throttleKey(string $email, ?string $ip): string
    {
        return 'admin-login:'.Str::transliterate(Str::lower($email)).'|'.($ip ?: 'unknown');
    }
}
