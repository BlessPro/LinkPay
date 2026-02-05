<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePaymentsPlan
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $user->startTrialIfMissing();

        if ($user->canUsePaymentsFeature()) {
            return $next($request);
        }

        return redirect()
            ->route('billing.upgrade')
            ->with('billing_required', 'Upgrade to Payments plan to use invoices and payments.');
    }
}

