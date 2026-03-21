<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Tracking
    |--------------------------------------------------------------------------
    |
    | Set MONITORING_ERROR_TRACKING=true and install/bind a "sentry" service
    | in production to forward unhandled exceptions to your provider.
    |
    */
    'error_tracking' => (bool) env('MONITORING_ERROR_TRACKING', false),
    'provider' => (string) env('MONITORING_PROVIDER', 'sentry'),
    'dsn' => (string) env('MONITORING_DSN', env('SENTRY_LARAVEL_DSN', '')),
    'environment' => (string) env('MONITORING_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Thresholds
    |--------------------------------------------------------------------------
    */
    'reconciliation' => [
        'critical_threshold' => (int) env('MONITORING_RECON_CRITICAL_THRESHOLD', 1),
        'high_threshold' => (int) env('MONITORING_RECON_HIGH_THRESHOLD', 3),
    ],
];
