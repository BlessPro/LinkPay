<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Lead;
use App\Services\SellerNotifier;
use App\Services\AnalyticsService;
use App\Services\PaystackService;
use App\Support\Email;
use App\Support\Money;
use App\Support\Phone;
use App\Support\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicListingController extends Controller
{
    public function show(string $public_slug, Request $request, AnalyticsService $analytics)
    {
        $profile = SellerProfile::where('public_slug', $public_slug)
            ->with(['user.products' => function ($query) {
                $query->where('is_active', true)
                    ->where('status', '!=', Product::STATUS_UNAVAILABLE);
            }])
            ->firstOrFail();

        $template = $request->query('template', 'products');
        if (! in_array($template, ['products', 'services'], true)) {
            $template = 'products';
        }
        $isOwner = $request->user() && $request->user()->id === $profile->user_id;

        $analytics->trackEvent(
            $request,
            $profile->user_id,
            \App\Models\AnalyticsEvent::TYPE_LISTING_VIEW,
            'listing',
            (string) $profile->id
        );

        foreach ($profile->user->products as $product) {
            $analytics->trackEvent(
                $request,
                $profile->user_id,
                \App\Models\AnalyticsEvent::TYPE_PRODUCT_IMPRESSION,
                'product',
                (string) $product->id
            );
        }

        return view('public.listing', [
            'profile' => $profile,
            'products' => $profile->user->products,
            'currency' => config('services.paystack.currency', 'GHS'),
            'template' => $template,
            'isOwner' => $isOwner,
            'ogTitle' => $profile->business_name,
            'ogDescription' => 'Browse products & services and contact on WhatsApp',
            'ogImage' => $this->resolveSellerOgImage($profile),
            'ogUrl' => route('public.listing', $profile->public_slug),
            'ogType' => 'website',
        ]);
    }

    private function resolveSellerOgImage(SellerProfile $profile): string
    {
        $firstWithImage = $profile->user->products()
            ->where('is_active', true)
            ->where('status', '!=', Product::STATUS_UNAVAILABLE)
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->latest()
            ->first();

        $firstActive = $firstWithImage ?: $profile->user->products()
            ->where('is_active', true)
            ->where('status', '!=', Product::STATUS_UNAVAILABLE)
            ->latest()
            ->first();

        if ($firstActive?->image_path) {
            return url('storage/'.$firstActive->image_path);
        }

        return asset('images/og-default.png');
    }

    public function pay(
        Request $request,
        string $public_slug,
        Product $product,
        PaystackService $paystack,
        AnalyticsService $analytics
    )
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'email'],
            'phone_number' => ['required', 'string', 'max:25'],
            'phone_country' => ['nullable', 'string'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();

        abort_unless($product->user_id === $profile->user_id, 404);
        abort_unless($product->is_active, 404);
        abort_unless($product->status !== Product::STATUS_UNAVAILABLE, 404);
        abort_unless(
            in_array($product->status, [Product::STATUS_IN_STOCK, Product::STATUS_LOW_STOCK, Product::STATUS_PRE_ORDER], true),
            404
        );

        if (! $profile->paystack_subaccount_code) {
            return back()->withErrors(['paystack' => 'Seller is not connected to Paystack yet.']);
        }

        $reference = (string) Str::uuid();
        $phoneInput = $request->input('phone_number');
        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phoneInput)));
        $primaryPhone = $phoneParts[0] ?? $phoneInput;
        $phone = Phone::normalize($primaryPhone, $request->input('phone_country', '+233'));
        if (! $phone || ! Phone::isValidGh($primaryPhone)) {
            return back()->withErrors(['phone_number' => 'Enter a valid WhatsApp number.'])->withInput();
        }

        $emailInput = $request->input('email');
        $emailParts = array_filter(array_map('trim', explode(',', (string) $emailInput)));
        $email = $emailParts[0] ?? $emailInput;
        if (! $email) {
            $email = Email::placeholder($reference);
        }

        $analytics->trackEvent(
            $request,
            $profile->user_id,
            \App\Models\AnalyticsEvent::TYPE_PRODUCT_CLICK,
            'product',
            (string) $product->id
        );

        $payment = Payment::create([
            'user_id' => $profile->user_id,
            'product_id' => $product->id,
            'reference' => $reference,
            'amount' => (string) $product->price,
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $phone,
                ],
            ],
        ]);

        $platformFee = (string) config('services.paystack.platform_fee_flat', '0');
        $platformFee = Money::compare($platformFee, '0.00') === 1 ? $platformFee : null;

        $data = $paystack->initializeTransaction(
            (string) $product->price,
            $email,
            [
                'reference' => $reference,
                'payment_id' => $payment->id,
                'product_id' => $product->id,
                'purpose' => 'product',
                'customer' => [
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $phone,
                ],
            ],
            $profile->paystack_subaccount_code,
            $platformFee
        );

        return redirect()->away($data['authorization_url']);
    }

    public function interest(Request $request, string $public_slug, Product $product)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'max:500'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        abort_unless($product->user_id === $profile->user_id, 404);

        $emailsInput = $request->input('email');
        $phonesInput = $request->input('phone_number');

        $parts = array_filter(array_map('trim', explode(',', (string) $emailsInput)));
        $emails = [];
        foreach ($parts as $part) {
            if (str_contains($part, '@')) {
                $emails[] = $part;
            }
        }

        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phonesInput)));
        $phones = [];
        foreach ($phoneParts as $part) {
            $phone = Phone::normalize($part, '+233');
            if ($phone) {
                $phones[] = $phone;
            }
        }

        if (empty($emails) && empty($phones)) {
            return back()->withErrors(['phone_number' => 'Enter at least one valid email or phone number.'])->withInput();
        }

        $sellerPhone = $profile->phone ?: ($profile->user?->phone);
        $sellerPhone = $sellerPhone ? Phone::normalize($sellerPhone, '+233') : null;
        if (! $sellerPhone) {
            return back()->withErrors(['phone_number' => 'Seller WhatsApp number is not available.'])->withInput();
        }

        $raw = trim(implode(', ', array_filter(array_merge($emails, $phones))));

        $lead = Lead::create([
            'user_id' => $profile->user_id,
            'product_id' => $product->id,
            'name' => $request->input('name'),
            'contact_raw' => $raw,
            'emails' => $emails,
            'phones' => $phones,
            'note' => $request->input('note'),
        ]);

        app(SellerNotifier::class)->notify(
            $profile->user,
            \App\Models\SellerNotification::TYPE_LEAD_CAPTURED,
            'New interested customer',
            'A customer expressed interest in '.$product->name.'. Contacts: '.$raw,
            [
                'lead_id' => $lead->id,
                'product_id' => $product->id,
                'contact' => $raw,
            ]
        );

        $productUrl = route('public.product', ['product_slug' => $product->slug]);
        $sellerName = $profile->business_name ?: ($profile->user?->name ?: 'Seller');

        $name = trim((string) $request->input('name'));
        $note = trim((string) $request->input('note'));

        $message = "Hi there, I am interested in this product, is it available or tell me more about it.\n";
        $message .= "Product: {$product->name}\n";
        if ($name !== '') {
            $message .= "Name: {$name}\n";
        }
        if ($raw !== '') {
            $message .= "Contact: {$raw}\n";
        }
        if ($note !== '') {
            $message .= "Note: {$note}\n";
        }
        $message .= "Link: {$productUrl}";

        return redirect()->away(WhatsApp::chatUrl($sellerPhone, $message));
    }
}
