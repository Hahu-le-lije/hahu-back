<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
<<<<<<< HEAD:subscription-service/app/Providers/AppServiceProvider.php
use App\Services\InternalUserService;
=======
>>>>>>> origin/cms:cms/app/Providers/AppServiceProvider.php

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
<<<<<<< HEAD:subscription-service/app/Providers/AppServiceProvider.php
        $this->app->singleton(InternalUserService::class, function ($app) {
            return new InternalUserService();
        });
=======
        //
>>>>>>> origin/cms:cms/app/Providers/AppServiceProvider.php
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
