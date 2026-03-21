<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->validateCsrfTokens(except: [
            'webhooks/paystack',
            'webhooks/twilio/status',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'active_access' => \App\Http\Middleware\EnsureActiveAccess::class,
            'payments_plan' => \App\Http\Middleware\EnsurePaymentsPlan::class,
            'promotion_access' => \App\Http\Middleware\EnsurePromotionAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $exception): void {
            if (! config('monitoring.error_tracking')) {
                return;
            }

            $provider = strtolower((string) config('monitoring.provider', 'sentry'));
            Log::error('Unhandled exception captured for monitoring', [
                'provider' => $provider,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            if ($provider === 'sentry' && app()->bound('sentry')) {
                app('sentry')->captureException($exception);
                return;
            }

            if ($provider === 'bugsnag' && app()->bound('bugsnag')) {
                app('bugsnag')->notifyException($exception);
                return;
            }

            Log::warning('Monitoring provider not bound; exception logged only', [
                'provider' => $provider,
            ]);
        });
    })->create();
