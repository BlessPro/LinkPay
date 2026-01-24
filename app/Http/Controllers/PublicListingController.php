<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Services\PaystackService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicListingController extends Controller
{
    public function show(string $public_slug)
    {
        $profile = SellerProfile::where('public_slug', $public_slug)
            ->with(['user.products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->firstOrFail();

        return view('public.listing', [
            'profile' => $profile,
            'products' => $profile->user->products,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);
    }

    public function pay(Request $request, string $public_slug, Product $product, PaystackService $paystack)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();

        abort_unless($product->user_id === $profile->user_id, 404);
        abort_unless($product->is_active, 404);

        if (! $profile->paystack_subaccount_code) {
            return back()->withErrors(['paystack' => 'Seller is not connected to Paystack yet.']);
        }

        $reference = (string) Str::uuid();

        $payment = Payment::create([
            'user_id' => $profile->user_id,
            'product_id' => $product->id,
            'reference' => $reference,
            'amount' => (string) $product->price,
            'status' => Payment::STATUS_PENDING,
        ]);

        $platformFee = (string) config('services.paystack.platform_fee_flat', '0');
        $platformFee = Money::compare($platformFee, '0.00') === 1 ? $platformFee : null;

        $data = $paystack->initializeTransaction(
            (string) $product->price,
            $request->input('email'),
            [
                'reference' => $reference,
                'payment_id' => $payment->id,
                'product_id' => $product->id,
                'purpose' => 'product',
            ],
            $profile->paystack_subaccount_code,
            $platformFee
        );

        return redirect()->away($data['authorization_url']);
    }
}
