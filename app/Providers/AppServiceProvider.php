<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('web', function (Request $request) {
            // Auth pages are hit often during testing; use a higher ceiling to avoid 429 loops.
            if ($request->is('login', 'csrf-refresh', 'forgot-password', 'reset-password', 'logout')) {
                return Limit::perMinute(300)->by($request->ip());
            }

            return Limit::perMinute(180)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(90)->by($request->user()?->id ?: $request->ip());
        });
    }
}
