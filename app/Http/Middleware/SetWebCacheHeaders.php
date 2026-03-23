<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetWebCacheHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $request->isMethod('GET')) {
            return $response;
        }

        $path = ltrim($request->path(), '/');
        $contentType = (string) $response->headers->get('Content-Type', '');

        // Keep HTML fresh to reduce iOS/PWA stale-shell issues.
        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        // Keep update-sensitive endpoints fresh.
        if (in_array($path, ['sw.js', 'manifest.webmanifest', 'version.json', 'offline.html'], true)) {
            $response->headers->set('Cache-Control', 'no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');

            return $response;
        }

        // Hashed build assets can be cached aggressively.
        if (str_starts_with($path, 'build/')) {
            $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        }

        return $response;
    }
}

