<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class MonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if (! config('monitoring.error_tracking')) {
            return;
        }

        $provider = strtolower((string) config('monitoring.provider', 'sentry'));
        $dsn = (string) config('monitoring.dsn', '');
        if ($dsn === '') {
            return;
        }

        if ($provider === 'sentry' && class_exists(\Sentry\ClientBuilder::class)) {
            $this->app->singleton('sentry', function () use ($dsn) {
                $builder = \Sentry\ClientBuilder::create([
                    'dsn' => $dsn,
                    'environment' => config('monitoring.environment', config('app.env')),
                    'release' => config('app.name').'-'.config('app.env'),
                ]);

                return \Sentry\State\Hub::create($builder->getClient());
            });
        }
    }
}

