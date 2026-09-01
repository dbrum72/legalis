<?php

namespace App\Providers;

use App\Integrations\DataJud\Contracts\DataJudClient;
use App\Integrations\DataJud\HttpDataJudClient;
use App\Integrations\Djen\Contracts\DjenClient;
use App\Integrations\Djen\HttpDjenClient;
use App\Integrations\ViaCep\Contracts\PostalCodeClient;
use App\Integrations\ViaCep\HttpViaCepClient;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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

        $this->app->singleton(
            PostalCodeClient::class,
            HttpViaCepClient::class,
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
