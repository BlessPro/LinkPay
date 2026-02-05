<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        // Safety: older users may not have trial fields populated.
        $user->startTrialIfMissing();

        if ($user->hasActiveAccess()) {
            return $next($request);
        }

        return redirect()
            ->route('billing.upgrade')
            ->with('billing_required', 'Upgrade required to continue.');
    }
}

