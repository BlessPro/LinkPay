<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePromotionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $user->startTrialIfMissing();

        if ($user->canUsePromotionFeatures()) {
            return $next($request);
        }

        return redirect()
            ->route('billing.upgrade')
            ->with('billing_required', 'Upgrade required to manage your products and public page.');
    }
}

