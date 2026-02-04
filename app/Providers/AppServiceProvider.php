<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Rate limit padrão para rotas api (opcional)
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Rate limit específico para redirect das URLs curtas
        RateLimiter::for('short-url-redirect', function (Request $request) {
            $code = $request->route('code');

            // 5 requisições por segundo para cada combinação código+IP
            return [
                Limit::perSecond(5)->by($code . '|' . $request->ip()),
            ];
        });
    }
}
