<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneAuthEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) config('auth_phone.enabled', true)) {
            return $next($request);
        }

        $fallback = $request->routeIs('register.*') ? route('register') : route('login');

        return redirect($fallback)->with('status', 'Phone auth is currently unavailable. Use email login/signup.');
    }
}

