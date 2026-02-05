<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $user->startTrialIfMissing();

        return view('billing.show', [
            'user' => $user,
            'plans' => config('plans'),
        ]);
    }

    public function upgrade(Request $request)
    {
        $user = $request->user();
        $user->startTrialIfMissing();

        return view('billing.upgrade', [
            'user' => $user,
            'plans' => config('plans'),
        ]);
    }

    public function activate(Request $request, string $plan): RedirectResponse
    {
        $user = $request->user();
        $user->startTrialIfMissing();

        $plan = strtoupper(trim($plan));
        abort_unless(in_array($plan, [User::PLAN_PROMOTION, User::PLAN_PAYMENTS], true), 404);

        // MVP: simulate subscription activation. Later: replace with Paystack subscription.
        $user->plan_type = $plan;
        $user->plan_started_at = now();
        $user->plan_ends_at = now()->addDays(30);
        $user->save();

        return redirect()
            ->route('billing.show')
            ->with('status', 'plan-activated');
    }
}

