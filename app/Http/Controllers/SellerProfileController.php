<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Models\SellerNotification;
use App\Services\PaystackService;
use Illuminate\Http\Request;

class SellerProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, PaystackService $paystack)
    {
        $user = $request->user();
        $profile = $user->sellerProfile;

        if (! $profile) {
            $profile = $user->sellerProfile()->create([
                'business_name' => $user->name,
                'public_slug' => \App\Models\SellerProfile::generateUniqueSlug($user->name),
            ]);
        }

        $profile->fill([
            'business_name' => $request->input('business_name'),
            'settlement_bank_code' => $request->input('settlement_bank_code'),
            'account_number' => $request->input('account_number'),
            'account_name' => $request->input('account_name'),
        ]);

        $phoneNumber = $request->input('phone_number');
        $phoneCountry = $request->input('phone_country', '+233');
        if ($phoneNumber) {
            $digits = preg_replace('/\D+/', '', $phoneNumber);
            if (str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            }
            $profile->phone = $phoneCountry.$digits;
        } else {
            $profile->phone = null;
        }

        $profile->save();

        $shouldConnectPaystack = $request->filled('settlement_bank_code')
            && $request->filled('account_number')
            && $request->filled('account_name');

        if ($shouldConnectPaystack) {
            try {
                $response = $paystack->createOrUpdateSubaccount($profile);
                $profile->paystack_subaccount_code = $response['subaccount_code'] ?? $profile->paystack_subaccount_code;
                $profile->percent_charge = $response['percentage_charge'] ?? $profile->percent_charge;
                $profile->save();

                SellerNotification::create([
                    'user_id' => $user->id,
                    'type' => SellerNotification::TYPE_PAYSTACK_CONNECTED,
                    'title' => 'Paystack connected',
                    'body' => 'Your payout details are connected to Paystack.',
                    'data' => ['subaccount_code' => $profile->paystack_subaccount_code],
                ]);
            } catch (\Throwable $exception) {
                return back()
                    ->withErrors(['paystack' => 'Unable to connect Paystack. Please confirm your payout details.'])
                    ->withInput();
            }
        }

        return back()->with('status', 'profile-updated');
    }

    public function testConnection(Request $request, PaystackService $paystack)
    {
        $user = $request->user();
        $profile = $user->sellerProfile;

        if (! $profile) {
            return back()->withErrors(['paystack' => 'No seller profile found.']);
        }

        if (! $profile->settlement_bank_code || ! $profile->account_number || ! $profile->account_name) {
            return back()->withErrors(['paystack' => 'Please fill in payout details before testing.']);
        }

        try {
            $response = $paystack->createOrUpdateSubaccount($profile);
            $profile->paystack_subaccount_code = $response['subaccount_code'] ?? $profile->paystack_subaccount_code;
            $profile->percent_charge = $response['percentage_charge'] ?? $profile->percent_charge;
            $profile->save();

            return back()->with('paystack_status', 'Paystack connection successful.');
        } catch (\Throwable $exception) {
            return back()->withErrors(['paystack' => 'Paystack connection failed. Please verify payout details.']);
        }
    }
}
