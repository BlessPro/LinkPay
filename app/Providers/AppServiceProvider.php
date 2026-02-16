<?php

namespace App\Providers;

use App\Models\SellerNotification;
use App\Observers\SellerNotificationObserver;
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
        SellerNotification::observe(SellerNotificationObserver::class);
    }
}
