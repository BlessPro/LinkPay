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

        return redirect()
            ->route('profile.edit', ['onboarding' => 'profile'])
            ->with('onboarding_required', 'Complete your business profile to continue.');
    }
}

