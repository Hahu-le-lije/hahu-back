<?php

namespace App\Providers;

use App\Services\ChildServiceClient;
use App\Services\SyncServiceClient;
use Illuminate\Support\ServiceProvider;

class ServiceClientsProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ChildServiceClient::class, function ($app) {
            return new ChildServiceClient();
        });

        $this->app->singleton(SyncServiceClient::class, function ($app) {
            return new SyncServiceClient();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
