<?php

namespace App\Providers;

use App\Integrations\Djen\Contracts\DjenClient;
use App\Integrations\Djen\HttpDjenClient;
use App\Integrations\DataJud\Contracts\DataJudClient;
use App\Integrations\DataJud\HttpDataJudClient;
use App\Support\Tenancy\CurrentOrganization;
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
    }

    public function boot(): void
    {
        //
    }
}
