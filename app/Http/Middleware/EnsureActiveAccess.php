<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isSuspended()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Your account is suspended. Contact support.',
            ]);
        }

        // Safety: older users may not have trial fields populated.
        $user->startTrialIfMissing();

        if (! $user->pin_hash && ! $request->routeIs('pin.setup.*', 'logout')) {
            return redirect()
                ->route('pin.setup.show')
                ->with('status', 'Set your PIN to continue.');
        }

        if ($user->hasActiveAccess()) {
            return $next($request);
        }

        return redirect()
            ->route('billing.upgrade')
            ->with('billing_required', 'Upgrade required to continue.');
    }
}
