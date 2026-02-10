<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()) {
            return redirect()->route('admin.login');
        }

        if (! $request->user()->is_admin) {
            abort(403);
        }

        $allowed = array_map('strtolower', config('admin.allowed_emails', []));
        $email = strtolower((string) $request->user()->email);
        if (! in_array($email, $allowed, true)) {
            abort(403);
        }

        return $next($request);
    }
}
