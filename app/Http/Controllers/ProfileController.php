<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\PaystackService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, PaystackService $paystack): View
    {
        $profile = $request->user()->sellerProfile;

        if (! $profile) {
            $profile = $request->user()->sellerProfile()->create([
                'business_name' => $request->user()->name,
                'public_slug' => \App\Models\SellerProfile::generateUniqueSlug($request->user()->name),
            ]);
        }

        $banks = [];
        try {
            $banks = $paystack->listBanks(config('services.paystack.currency', 'GHS'));
        } catch (\Throwable $exception) {
            $banks = [];
        }

        return view('profile.edit', [
            'user' => $request->user(),
            'profile' => $profile,
            'currency' => config('services.paystack.currency', 'GHS'),
            'banks' => $banks,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
