<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Render and similar platforms terminate TLS at a proxy/load balancer.
        // Trusting proxy headers keeps scheme/cookie/CSRF behavior consistent.
        $middleware->trustProxies(at: '*');

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

        $middleware->web(append: [
            \App\Http\Middleware\SetWebCacheHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (PostTooLargeException $exception, $request) {
            $contentLength = (int) ($request->server('CONTENT_LENGTH') ?? 0);
            $userId = optional($request->user())->id;

            Log::warning('Upload rejected: request exceeds server body size limit', [
                'path' => $request->path(),
                'method' => $request->method(),
                'content_length_bytes' => $contentLength,
                'user_id' => $userId,
                'ip' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Upload too large. Reduce image sizes or upload fewer files at once.',
                ], 413);
            }

            return back()->withErrors([
                'upload' => 'Upload is too large for the server limit. Try fewer files or smaller images.',
            ]);
        });

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
