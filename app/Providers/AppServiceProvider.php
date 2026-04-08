<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RabbitRpcClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RabbitRpcClient::class, function ($app) {
            return new RabbitRpcClient();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
