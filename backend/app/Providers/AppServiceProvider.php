<?php

namespace App\Providers;

use App\Integrations\Djen\Contracts\DjenClient;
use App\Integrations\Djen\HttpDjenClient;
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
    }

    public function boot(): void
    {
        //
    }
}
