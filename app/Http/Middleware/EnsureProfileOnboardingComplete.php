<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        if ($request->routeIs(
            'onboarding.*',
            'profile.*',
            'logout',
            'verification.*',
            'billing.*',
            'legal.data-deletion.store'
        )) {
            return $next($request);
        }

        if ($user->hasCompletedProfileOnboarding()) {
            return $next($request);
        }

        if ($this->isMobile($request)) {
            return redirect()
                ->route('onboarding.index')
                ->with('onboarding_required', 'Complete onboarding to continue.');
        }

        // Desktop stays non-blocking; onboarding is guided through popup and checklist.
        return $next($request);
    }

    private function isMobile(Request $request): bool
    {
        $userAgent = strtolower((string) $request->userAgent());

        return str_contains($userAgent, 'mobile')
            || str_contains($userAgent, 'android')
            || str_contains($userAgent, 'iphone')
            || str_contains($userAgent, 'ipad');
    }
}
