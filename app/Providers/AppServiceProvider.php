<?php

namespace App\Providers;

use App\Models\SellerNotification;
use App\Observers\SellerNotificationObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        SellerNotification::observe(SellerNotificationObserver::class);

        $forceHttps = (bool) env('APP_FORCE_HTTPS', app()->environment('production'));
        if ($forceHttps) {
            URL::forceScheme('https');
        }
    }
}
