<?php

namespace App\Providers;

use App\Integrations\Djen\Contracts\DjenClient;
use App\Integrations\Djen\HttpDjenClient;
use App\Integrations\DataJud\Contracts\DataJudClient;
use App\Integrations\DataJud\HttpDataJudClient;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            CurrentOrganization::class,
            fn () => new CurrentOrganization,
        );

        $this->app->singleton(
            DjenClient::class,
            HttpDjenClient::class,
        );

        $this->app->singleton(
            DataJudClient::class,
            HttpDataJudClient::class,
        );
    }

    public function boot(): void
    {
        RateLimiter::for(
            'datajud',
            fn () => Limit::perMinute(
                max(1, (int) config('services.datajud.rate_limit_per_minute', 30)),
            )->by('datajud'),
        );
    }
}
