<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\Lead;
use App\Models\PublicCartSession;
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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\RequestException;

class PublicListingController extends Controller
{
    private const CART_TOKEN_COOKIE = 'lp_cart_token';
    private const CART_TTL_DAYS = 30;

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
        $statusFilter = $request->query('status', 'all');
        $allowedStatusFilters = [
            'all',
            Product::STATUS_IN_STOCK,
            Product::STATUS_LOW_STOCK,
            Product::STATUS_PRE_ORDER,
            Product::STATUS_SOLD_OUT,
        ];
        if (! in_array($statusFilter, $allowedStatusFilters, true)) {
            $statusFilter = 'all';
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

        $allProducts = $profile->user->products->values();
        $products = $statusFilter === 'all'
            ? $allProducts
            : $allProducts->where('status', $statusFilter)->values();

        $statusTabs = [
            ['key' => 'all', 'label' => 'All products', 'count' => $allProducts->count()],
            ['key' => Product::STATUS_IN_STOCK, 'label' => 'In stock', 'count' => $allProducts->where('status', Product::STATUS_IN_STOCK)->count()],
            ['key' => Product::STATUS_LOW_STOCK, 'label' => 'Low stock', 'count' => $allProducts->where('status', Product::STATUS_LOW_STOCK)->count()],
            ['key' => Product::STATUS_PRE_ORDER, 'label' => 'Pre-order', 'count' => $allProducts->where('status', Product::STATUS_PRE_ORDER)->count()],
            ['key' => Product::STATUS_SOLD_OUT, 'label' => 'Sold out', 'count' => $allProducts->where('status', Product::STATUS_SOLD_OUT)->count()],
        ];

        $cart = $this->buildCartSummary($request, $profile);

        return view('public.listing', [
            'profile' => $profile,
            'products' => $products,
            'statusFilter' => $statusFilter,
            'statusTabs' => $statusTabs,
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
            $shouldGenerate = ! Storage::disk('public')->exists($ogPath);
            if (! $shouldGenerate) {
                try {
                    $absolutePath = Storage::disk('public')->path($ogPath);
                    $mtime = @filemtime($absolutePath);
                    $shouldGenerate = ! $mtime || $firstWithImage->updated_at?->timestamp > $mtime;
                } catch (\Throwable $e) {
                    $shouldGenerate = false;
                }
            }
            if ($shouldGenerate) {
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
        $validated = $this->validateCheckoutContact($request);

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
        if ($product->isInventoryManaged() && (int) $product->stock_quantity < 1) {
            return back()->withErrors([
                'paystack' => 'This item is currently out of stock. Please remove it or contact the seller.',
            ])->withInput();
        }

        if (! $profile->paystack_subaccount_code) {
            return back()->withErrors(['paystack' => 'Seller is not connected to Paystack yet.']);
        }

        $reference = (string) Str::uuid();
        $phoneInput = $validated['phone_number'];
        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phoneInput)));
        $primaryPhone = $phoneParts[0] ?? $phoneInput;
        $phone = Phone::normalize($primaryPhone, $validated['phone_country'] ?? '+233');
        $location = trim((string) ($validated['location'] ?? ''));
        $note = trim((string) ($validated['note'] ?? ''));
        if (! $phone) {
            return back()->withErrors(['phone_number' => 'Please enter a valid phone number.'])->withInput();
        }
        $idempotencyKey = (string) $validated['idempotency_key'];
        $idempotencyScope = 'public_product_pay:'.$public_slug.':'.$product->id;
        if (! $this->acquireIdempotencyKey($request, $idempotencyScope, $idempotencyKey)) {
            return back()->withErrors([
                'paystack' => 'This payment request is already being processed. Please wait a moment.',
            ])->withInput();
        }

        $email = Email::placeholder($reference);
        $analytics->trackEvent(
            $request,
            $profile->user_id,
            \App\Models\AnalyticsEvent::TYPE_PRODUCT_CLICK,
            'product',
            (string) $product->id
        );
        $analytics->trackEvent(
            $request,
            $profile->user_id,
            \App\Models\AnalyticsEvent::TYPE_CHECKOUT_STARTED,
            'product',
            (string) $product->id
        );

        $order = Order::create([
            'user_id' => $profile->user_id,
            'reference' => $reference,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Payment::STATUS_PENDING,
            'customer_name' => null,
            'customer_phone' => $phone,
            'customer_location' => $location !== '' ? $location : null,
            'delivery_required' => false,
            'delivery_note' => $note !== '' ? $note : null,
            'subtotal' => (string) $product->price,
            'coupon_code' => null,
            'discount_amount' => '0.00',
            'total' => (string) $product->price,
            'currency' => config('services.paystack.currency', 'GHS'),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => (string) $product->price,
            'quantity' => 1,
            'line_total' => (string) $product->price,
        ]);

        $payment = Payment::create([
            'user_id' => $profile->user_id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'reference' => $reference,
            'amount' => (string) $product->price,
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location !== '' ? $location : null,
                    'note' => $note !== '' ? $note : null,
                ],
                'order' => [
                    'id' => $order->id,
                    'delivery_required' => false,
                    'delivery_note' => $note !== '' ? $note : null,
                    'items' => [[
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'qty' => 1,
                        'unit_price' => (string) $product->price,
                        'line_total' => (string) $product->price,
                    ]],
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
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location !== '' ? $location : null,
                        'note' => $note !== '' ? $note : null,
                    ],
                ],
                $profile->paystack_subaccount_code,
                $platformFee
            );
        } catch (RequestException $exception) {
            $this->releaseIdempotencyKey($request, $idempotencyScope, $idempotencyKey);
            $payment->status = Payment::STATUS_FAILED;
            $payment->raw_payload = array_merge($payment->raw_payload ?? [], [
                'initialize_error' => $exception->getMessage(),
            ]);
            $payment->save();
            $order->status = Order::STATUS_CANNOT_FULFILL;
            $order->payment_status = Payment::STATUS_FAILED;
            $order->save();

            return back()->withErrors([
                'paystack' => 'Could not initialize payment. Please confirm seller Paystack connection and try again.',
            ])->withInput();
        }

        return redirect()->away($data['authorization_url'] ?? route('public.listing', $public_slug));
    }

    public function addToCart(Request $request, string $public_slug, Product $product, AnalyticsService $analytics)
    {
        $request->validate([
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        abort_unless($product->user_id === $profile->user_id, 404);
        abort_unless($product->isPayable(), 404);

        $cart = $this->readCartRaw($request, $public_slug);
        $currentQty = (int) ($cart[$product->id]['quantity'] ?? 0);
        $requestedQuantity = max(1, (int) $request->integer('quantity', 1));
        $newQuantity = $currentQty + $requestedQuantity;
        if ($product->isInventoryManaged() && $newQuantity > (int) $product->stock_quantity) {
            return back()->withErrors([
                'paystack' => 'Only '.$product->stock_quantity.' item(s) left for '.$product->name.'.',
            ])->withInput();
        }

        $cart[$product->id] = [
            'product_id' => $product->id,
            'quantity' => $newQuantity,
        ];

        $this->persistCartRaw($request, $public_slug, $cart);

        $analytics->trackEvent(
            $request,
            $profile->user_id,
            \App\Models\AnalyticsEvent::TYPE_ADD_TO_CART,
            'product',
            (string) $product->id
        );

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
            $product = $profile->user->products()->find($productId);
            if ($product && $product->isInventoryManaged() && $quantity > (int) $product->stock_quantity) {
                return back()->withErrors([
                    'paystack' => 'Quantity for '.$product->name.' exceeds available stock ('.$product->stock_quantity.').',
                ])->withInput();
            }
            $updated[$productId] = [
                'product_id' => $productId,
                'quantity' => $quantity,
            ];
        }

        $this->persistCartRaw($request, $public_slug, $updated);

        return back()->with('status', 'cart-updated');
    }

    public function removeFromCart(Request $request, string $public_slug, Product $product)
    {
        $profile = SellerProfile::where('public_slug', $public_slug)->firstOrFail();
        abort_unless($product->user_id === $profile->user_id, 404);

        $cart = $this->readCartRaw($request, $public_slug);
        unset($cart[$product->id]);
        $this->persistCartRaw($request, $public_slug, $cart);

        return back()->with('status', 'cart-updated');
    }

    public function checkoutCart(Request $request, string $public_slug, PaystackService $paystack, AnalyticsService $analytics)
    {
        $validated = $this->validateCheckoutContact($request);

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
        $stockIssues = collect($cart['items'])
            ->filter(function (array $row) {
                return $row['product']->isInventoryManaged() && $row['quantity'] > (int) $row['product']->stock_quantity;
            })
            ->map(function (array $row) {
                return $row['product']->name.' (available '.$row['product']->stock_quantity.')';
            })
            ->values();
        if ($stockIssues->isNotEmpty()) {
            return back()->withErrors([
                'paystack' => 'Some cart quantities exceed stock: '.$stockIssues->implode(', ').'. Please reduce quantity and try again.',
            ])->withInput();
        }

        $phoneInput = $validated['phone_number'];
        $phoneParts = array_filter(array_map('trim', explode(',', (string) $phoneInput)));
        $primaryPhone = $phoneParts[0] ?? $phoneInput;
        $phone = Phone::normalize($primaryPhone, $validated['phone_country'] ?? '+233');
        $location = trim((string) ($validated['location'] ?? ''));
        $note = trim((string) ($validated['note'] ?? ''));
        if (! $phone) {
            return back()->withErrors(['phone_number' => 'Please enter a valid phone number.'])->withInput();
        }
        $idempotencyKey = (string) $validated['idempotency_key'];
        $idempotencyScope = 'public_cart_checkout:'.$public_slug;
        if (! $this->acquireIdempotencyKey($request, $idempotencyScope, $idempotencyKey)) {
            return back()->withErrors([
                'paystack' => 'This checkout is already being processed. Please wait a moment.',
            ])->withInput();
        }

        $subtotal = (string) $cart['total'];
        $discountAmount = '0.00';
        $couponCode = null;
        $total = $subtotal;

        $analytics->trackEvent(
            $request,
            $profile->user_id,
            \App\Models\AnalyticsEvent::TYPE_CHECKOUT_STARTED,
            'listing',
            (string) $profile->id
        );

        $reference = (string) \Illuminate\Support\Str::uuid();
        $email = Email::placeholder($reference);

        $order = Order::create([
            'user_id' => $profile->user_id,
            'reference' => $reference,
            'status' => Order::STATUS_PENDING_PAYMENT,
            'payment_status' => Payment::STATUS_PENDING,
            'customer_name' => null,
            'customer_phone' => $phone,
            'customer_location' => $location !== '' ? $location : null,
            'delivery_required' => false,
            'delivery_note' => $note !== '' ? $note : null,
            'subtotal' => $subtotal,
            'coupon_code' => $couponCode,
            'discount_amount' => $discountAmount,
            'total' => $total,
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
            'amount' => $total,
            'status' => Payment::STATUS_PENDING,
            'raw_payload' => [
                'customer' => [
                    'email' => $email,
                    'phone' => $phone,
                    'location' => $location !== '' ? $location : null,
                    'note' => $note !== '' ? $note : null,
                    'ip_address' => $request->ip(),
                ],
                'order' => [
                    'id' => $order->id,
                    'coupon_code' => $couponCode,
                    'discount_amount' => $discountAmount,
                    'delivery_required' => false,
                    'delivery_note' => $note !== '' ? $note : null,
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

        $platformFee = $paystack->platformChargeFor($total);

        try {
            $data = $paystack->initializeTransaction(
                $total,
                $email,
                [
                    'reference' => $reference,
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'purpose' => 'order',
                    'platform_fee' => $platformFee,
                    'customer' => [
                        'email' => $email,
                        'phone' => $phone,
                        'location' => $location !== '' ? $location : null,
                        'note' => $note !== '' ? $note : null,
                    ],
                ],
                $profile->paystack_subaccount_code,
                $platformFee
            );
        } catch (RequestException $exception) {
            $this->releaseIdempotencyKey($request, $idempotencyScope, $idempotencyKey);
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

        $this->persistCartRaw($request, $public_slug, []);

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
        $raw = $this->readCartRaw($request, $profile->public_slug);
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

        $this->persistCartRaw($request, $profile->public_slug, $normalizedRaw);

        return [
            'items' => $items,
            'total' => $total,
            'count' => $items->count(),
        ];
    }

    private function validateCheckoutContact(Request $request): array
    {
        $rules = [
            'phone_number' => [
                'required',
                'string',
                'max:25',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $parts = array_filter(array_map('trim', explode(',', (string) $value)));
                    $primaryPhone = $parts[0] ?? $value;
                    if (! Phone::isValidGh((string) $primaryPhone)) {
                        $fail('Please enter a valid Ghana phone number (example: 0541900229).');
                    }
                },
            ],
            'phone_country' => ['nullable', 'string', 'max:8'],
            'location' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:500'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:120'],
        ];

        return $request->validate($rules, [
            'phone_number.required' => 'A phone number is required so the seller can reach you.',
        ]);
    }

    private function acquireIdempotencyKey(Request $request, string $scope, string $key): bool
    {
        return Cache::add($this->idempotencyCacheKey($request, $scope, $key), now()->toIso8601String(), now()->addMinutes(15));
    }

    private function releaseIdempotencyKey(Request $request, string $scope, string $key): void
    {
        Cache::forget($this->idempotencyCacheKey($request, $scope, $key));
    }

    private function idempotencyCacheKey(Request $request, string $scope, string $key): string
    {
        return 'idem:'.sha1($scope.'|'.$request->ip().'|'.$key);
    }

    private function readCartRaw(Request $request, string $publicSlug): array
    {
        $sessionKey = $this->cartKey($publicSlug);
        $sessionCart = $request->session()->get($sessionKey, []);
        $sessionCart = is_array($sessionCart) ? $sessionCart : [];
        $token = $this->resolveCartToken($request);

        if (! empty($sessionCart)) {
            $this->persistCartSession($publicSlug, $token, $sessionCart);
            return $sessionCart;
        }

        $stored = PublicCartSession::query()
            ->where('public_slug', $publicSlug)
            ->where('session_token', $token)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        $restored = is_array($stored?->cart_payload) ? $stored->cart_payload : [];
        if (! empty($restored)) {
            $request->session()->put($sessionKey, $restored);
        }

        return $restored;
    }

    private function persistCartRaw(Request $request, string $publicSlug, array $raw): void
    {
        $sessionKey = $this->cartKey($publicSlug);
        $normalized = is_array($raw) ? $raw : [];

        $token = $this->resolveCartToken($request);
        if (empty($normalized)) {
            $request->session()->forget($sessionKey);
            PublicCartSession::query()
                ->where('public_slug', $publicSlug)
                ->where('session_token', $token)
                ->delete();
            return;
        }

        $request->session()->put($sessionKey, $normalized);
        $this->persistCartSession($publicSlug, $token, $normalized);
    }

    private function persistCartSession(string $publicSlug, string $token, array $payload): void
    {
        PublicCartSession::query()->updateOrCreate(
            [
                'public_slug' => $publicSlug,
                'session_token' => $token,
            ],
            [
                'cart_payload' => $payload,
                'expires_at' => now()->addDays(self::CART_TTL_DAYS),
            ]
        );
    }

    private function resolveCartToken(Request $request): string
    {
        $token = (string) $request->cookie(self::CART_TOKEN_COOKIE, '');
        if ($token === '' || ! Str::isUuid($token)) {
            $token = (string) Str::uuid();
        }

        Cookie::queue(cookie(
            self::CART_TOKEN_COOKIE,
            $token,
            self::CART_TTL_DAYS * 24 * 60,
            path: '/',
            secure: $request->isSecure(),
            httpOnly: true,
            sameSite: 'lax'
        ));

        return $token;
    }
}
