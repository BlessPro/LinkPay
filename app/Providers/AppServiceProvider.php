<?php

namespace App\Providers;

use App\Models\SellerNotification;
use App\Observers\SellerNotificationObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureRateLimiting();

        $forceHttps = (bool) env('APP_FORCE_HTTPS', app()->environment('production'));
        if ($forceHttps) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('public-pay', function (Request $request): Limit {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number', 'na')) ?: 'na';
            return Limit::perMinute(10)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('public-checkout', function (Request $request): Limit {
            $phone = preg_replace('/\D+/', '', (string) $request->input('phone_number', 'na')) ?: 'na';
            return Limit::perMinute(8)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('auth-phone-send', function (Request $request): Limit {
            $phone = preg_replace('/\D+/', '', (string) ($request->input('phone') ?: $request->input('phone_number') ?: 'na')) ?: 'na';
            return Limit::perMinute(4)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('auth-phone-verify', function (Request $request): Limit {
            $phone = preg_replace('/\D+/', '', (string) ($request->input('phone') ?: $request->input('phone_number') ?: 'na')) ?: 'na';
            return Limit::perMinute(8)->by($request->ip().'|'.$phone);
        });
    }
}
