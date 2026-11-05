<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Default API rate limiter
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Public read endpoints (60 req / min per IP)
        RateLimiter::for('public-api', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        // Live search endpoint (30 req / min per IP)
        RateLimiter::for('search', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        // Login brute-force protection (5 attempts / min per IP + email)
        RateLimiter::for('login', function (Request $request) {
            $email = strtolower((string) $request->input('email', ''));
            return Limit::perMinute(5)
                ->by($request->ip() . '|' . $email)
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many sign-in attempts. Please wait 60 seconds before trying again.',
                    ], 429);
                });
        });

        // Contact form anti-spam rate limiter (3 submissions / 5 minutes per IP)
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perMinutes(5, 3)
                ->by($request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'Too many messages sent recently. Please wait a few minutes before sending another message.',
                    ], 429);
                });
        });

        // Authenticated admin endpoints (120 req / min per user)
        RateLimiter::for('admin-api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
