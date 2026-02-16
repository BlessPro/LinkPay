<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Lead;
use App\Services\SellerNotifier;
use App\Services\OgImageService;
use App\Services\AnalyticsService;
use App\Services\PaystackService;
use App\Support\Email;
use App\Support\Money;
use App\Support\Phone;
use App\Support\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\RequestException;

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

        $cart = $this->buildCartSummary($request, $profile);

        return view('public.listing', [
            'profile' => $profile,
            'products' => $profile->user->products,
            'cart' => $cart,
            'currency' => config('services.paystack.currency', 'GHS'),
            'template' => $template,
            'isOwner' => $isOwner,
            'paymentsEnabled' => $profile->user?->canUsePaymentsFeature() ?? false,
            'ogTitle' => $profile->business_name,
            'ogDescription' => 'Browse products & services and contact on WhatsApp',
            'ogImage' => $this->resolveSellerOgImage($profile),
            'ogUrl' => route('public.listing', $profile->public_slug),
            'ogType' => 'website',
        ]);
    }

    private function resolveSellerOgImage(SellerProfile $profile): string
    {
        // WhatsApp preview for seller page: first active product's OG image (largest/consistent).
        $firstWithImage = $profile->user->products()
            ->where('is_active', true)
            ->where('status', '!=', Product::STATUS_UNAVAILABLE)
            ->whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->latest()
            ->first();

        if ($firstWithImage) {
            $ogService = app(OgImageService::class);
            $ogPath = $ogService->publicProductOgPath($firstWithImage->id);
            if (! Storage::disk('public')->exists($ogPath)) {
                try {
                    $firstWithImage->loadMissing('user.sellerProfile');
                    $ogService->generateProduct($firstWithImage);
                } catch (\Throwable $e) {
                    // ignore
                }
            }
            if (Storage::disk('public')->exists($ogPath)) {
                return url(Storage::url($ogPath));
            }
        }

        // Fallback: seller OG image (generated from seller info).
        $sellerOgPath = app(OgImageService::class)->publicSellerOgPath($profile->public_slug);
        if (Storage::disk('public')->exists($sellerOgPath)) {
            return url(Storage::url($sellerOgPath));
        }

        // Last resort.
        return url('/images/og-default.jpg');
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
            'location' => ['nullable', 'string', 'max:160'],
            'phone_number' => ['required', 'string', 'max:25'],
            'phone_country' => ['nullable', 'string'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();

        $sellerUser = $profile->user;
        if ($sellerUser) {
            if (! $sellerUser->canUsePaymentsFeature()) {
                return back()->withErrors([
                    'paystack' => 'This seller is not on the Payments plan. Please use Chat on WhatsApp.',
                ])->withInput();
            }
        }

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

        $email = Email::placeholder($reference);
        $location = trim((string) $request->input('location'));

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
                    'location' => $location,
                ],
            ],
        ]);

        $platformFee = $paystack->platformChargeFor((string) $product->price);

        try {
            $data = $paystack->initializeTransaction(
                (string) $product->price,
                $email,
                [
                    'reference' => $reference,
                    'payment_id' => $payment->id,
                    'product_id' => $product->id,
                    'purpose' => 'product',
                    'platform_fee' => $platformFee,
                    'customer' => [
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location,
                ],
            ],
                $profile->paystack_subaccount_code,
                $platformFee
            );
        } catch (RequestException $exception) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->raw_payload = array_merge($payment->raw_payload ?? [], [
                'initialize_error' => $exception->getMessage(),
            ]);
            $payment->save();

            return back()->withErrors([
                'paystack' => 'Could not initialize payment. Please confirm seller Paystack connection and try again.',
            ])->withInput();
        }

        return redirect()->away($data['authorization_url'] ?? route('public.listing', $public_slug));
    }

    public function addToCart(Request $request, string $public_slug, Product $product)
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        abort_unless($product->user_id === $profile->user_id, 404);
        abort_unless($product->isPayable(), 404);

        $cart = $request->session()->get($this->cartKey($public_slug), []);
        $currentQty = (int) ($cart[$product->id]['quantity'] ?? 0);
        $cart[$product->id] = [
            'product_id' => $product->id,
            'quantity' => $currentQty + (int) $request->integer('quantity', 1),
        ];

        $request->session()->put($this->cartKey($public_slug), $cart);

        return back()->with('status', 'cart-updated');
    }

    public function updateCart(Request $request, string $public_slug)
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.quantity' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        $productIds = $profile->user->products()->pluck('id')->all();

        $updated = [];
        foreach ($request->input('items', []) as $productId => $row) {
            $productId = (int) $productId;
            if (! in_array($productId, $productIds, true)) {
                continue;
            }
            $quantity = (int) ($row['quantity'] ?? 1);
            if ($quantity < 1) {
                continue;
            }
            $updated[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
        }

        $request->session()->put($this->cartKey($public_slug), $updated);

        return back()->with('status', 'cart-updated');
    }

    public function removeFromCart(Request $request, string $public_slug, Product $product)
    {
        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        abort_unless($product->user_id === $profile->user_id, 404);

        $cart = $request->session()->get($this->cartKey($public_slug), []);
        unset($cart[$product->id]);
        $request->session()->put($this->cartKey($public_slug), $cart);

        return back()->with('status', 'cart-updated');
    }

    public function checkoutCart(Request $request, string $public_slug, PaystackService $paystack)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'phone_number' => ['required', 'string', 'max:25'],
            'phone_country' => ['nullable', 'string'],
            'delivery_required' => ['nullable', 'boolean'],
            'delivery_note' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)
            ->with(['user.products' => function ($query) {
                $query->where('is_active', true)
                    ->where('status', '!=', Product::STATUS_UNAVAILABLE);
            }])
            ->firstOrFail();

        $sellerUser = $profile->user;
        if (! $sellerUser || ! $sellerUser->canUsePaymentsFeature()) {
            return back()->withErrors(['paystack' => 'This seller is not on the Payments plan.'])->withInput();
        }
        if (! $profile->paystack_subaccount_code) {
            return back()->withErrors(['paystack' => 'Seller is not connected to Paystack yet.'])->withInput();
        }

        $cart = $this->buildCartSummary($request, $profile);
        if ($cart['items']->isEmpty()) {
            return back()->withErrors(['paystack' => 'Your cart is empty.'])->withInput();
        }

        $phoneInput = $request->input('phone_number');
        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phoneInput)));
        $primaryPhone = $phoneParts[0] ?? $phoneInput;
        $phone = Phone::normalize($primaryPhone, $request->input('phone_country', '+233'));
        if (! $phone || ! Phone::isValidGh($primaryPhone)) {
            return back()->withErrors(['phone_number' => 'Enter a valid WhatsApp number.'])->withInput();
        }

        $reference = (string) \Illuminate\Support\Str::uuid();
        $email = Email::placeholder($reference);
        $location = trim((string) $request->input('location'));
        $deliveryRequired = (bool) $request->boolean('delivery_required');
        $deliveryNote = trim((string) $request->input('delivery_note'));

        $order = Order::create([
            'user_id' => $profile->user_id,
            'reference' => $reference,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Payment::STATUS_PENDING,
            'customer_name' => $request->input('name'),
            'customer_phone' => $phone,
            'customer_location' => $location !== '' ? $location : null,
            'delivery_required' => $deliveryRequired,
            'delivery_note' => $deliveryNote !== '' ? $deliveryNote : null,
            'subtotal' => $cart['total'],
            'total' => $cart['total'],
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);

        foreach ($cart['items'] as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'line_total' => $item['line_total'],
            ]);
        }

        $payment = Payment::create([
            'user_id' => $profile->user_id,
            'order_id' => $order->id,
            'reference' => $reference,
            'amount' => $cart['total'],
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'name' => $request->input('name'),
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location,
                ],
                'order' => [
                    'id' => $order->id,
                    'delivery_required' => $deliveryRequired,
                    'delivery_note' => $deliveryNote,
                    'items' => $cart['items']->map(function (array $row) {
                        return [
                            'product_id' => $row['product']->id,
                            'name' => $row['product']->name,
                            'qty' => $row['quantity'],
                            'unit_price' => $row['unit_price'],
                            'line_total' => $row['line_total'],
                        ];
                    })->values()->all(),
                ],
            ],
        ]);

        $platformFee = $paystack->platformChargeFor((string) $cart['total']);

        try {
            $data = $paystack->initializeTransaction(
                (string) $cart['total'],
                $email,
                [
                    'reference' => $reference,
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'purpose' => 'order',
                    'platform_fee' => $platformFee,
                    'customer' => [
                        'name' => $request->input('name'),
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location,
                    ],
                ],
                $profile->paystack_subaccount_code,
                $platformFee
            );
        } catch (RequestException $exception) {
            $payment->status = Payment::STATUS_FAILED;
            $payment->raw_payload = array_merge($payment->raw_payload ?? [], [
                'initialize_error' => $exception->getMessage(),
            ]);
            $payment->save();

            $order->status = Order::STATUS_CANNOT_FULFILL;
            $order->payment_status = Payment::STATUS_FAILED;
            $order->save();

            return back()->withErrors([
                'paystack' => 'Could not initialize payment. Please try again shortly.',
            ])->withInput();
        }

        $request->session()->forget($this->cartKey($public_slug));

        return redirect()->away($data['authorization_url'] ?? route('public.listing', $public_slug));
    }

    public function interest(Request $request, string $public_slug, Product $product)
    {
        $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:160'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        abort_unless($product->user_id === $profile->user_id, 404);

        $phonesInput = $request->input('phone_number');
        $location = trim((string) $request->input('location'));

        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phonesInput)));
        $phones = [];
        foreach ($phoneParts as $part) {
            $phone = Phone::normalize($part, '+233');
            if ($phone) {
                $phones[] = $phone;
            }
        }

        if (empty($phones)) {
            return back()->withErrors(['phone_number' => 'Enter a valid WhatsApp number.'])->withInput();
        }

        $sellerPhone = $profile->phone ?: ($profile->user?->phone);
        $sellerPhone = $sellerPhone ? Phone::normalize($sellerPhone, '+233') : null;
        if (! $sellerPhone) {
            return back()->withErrors(['phone_number' => 'Seller WhatsApp number is not available.'])->withInput();
        }

        $raw = trim(implode(', ', array_filter($phones)));
        $composedNote = trim(implode(' | ', array_filter([
            $location !== '' ? 'Location: '.$location : null,
            $request->input('note'),
        ])));

        $lead = Lead::create([
            'user_id' => $profile->user_id,
            'product_id' => $product->id,
            'name' => $request->input('name'),
            'contact_raw' => $raw,
            'emails' => [],
            'phones' => $phones,
            'note' => $composedNote,
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
                'location' => $location,
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
        if ($location !== '') {
            $message .= "Location: {$location}\n";
        }
        if ($note !== '') {
            $message .= "Note: {$note}\n";
        }
        $message .= "Link: {$productUrl}";

        return redirect()->away(WhatsApp::chatUrl($sellerPhone, $message));
    }

    private function cartKey(string $publicSlug): string
    {
        return 'public_cart:'.$publicSlug;
    }

    private function buildCartSummary(Request $request, SellerProfile $profile): array
    {
        $raw = $request->session()->get($this->cartKey($profile->public_slug), []);
        $raw = is_array($raw) ? $raw : [];

        $activeProducts = $profile->user->products
            ? $profile->user->products->keyBy('id')
            : $profile->user->products()
                ->where('is_active', true)
                ->where('status', '!=', Product::STATUS_UNAVAILABLE)
                ->get()
                ->keyBy('id');

        $items = collect();
        $total = '0.00';
        $normalizedRaw = [];

        foreach ($raw as $productId => $row) {
            $productId = (int) $productId;
            $product = $activeProducts->get($productId);
            if (! $product || ! $product->isPayable()) {
                continue;
            }

            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $unitPrice = (string) $product->price;
            $lineTotal = Money::multiply($unitPrice, $quantity);
            $total = Money::add($total, $lineTotal);

            $normalizedRaw[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];

            $items->push([
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }

        $request->session()->put($this->cartKey($profile->public_slug), $normalizedRaw);

        return [
            'items' => $items,
            'total' => $total,
            'count' => $items->count(),
        ];
    }
}
