<?php

// Responsable de prise en main : Yedidia ; relecture croisee, attribution non assimilee a une contribution deja realisee.

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
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
        ResetPassword::createUrlUsing(function (object $user, string $token): string {
            return rtrim(config('commerce.frontend_url'), '/').'/reset-password?'.http_build_query([
                'token' => $token,
                'email' => $user->email,
            ]);
        });
        RateLimiter::for('authentication', function (Request $request): array {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            ];
        });
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(120)->by($request->user()?->id ?? $request->ip());
        });
    }
}
