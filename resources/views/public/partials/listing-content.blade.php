<div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.3em] text-slate-400">Storefront</p>
            <h1 class="mt-2 text-2xl font-semibold text-slate-900 sm:text-3xl">{{ $profile->business_name }}</h1>
            @if($profile->phone)
                <p class="mt-1 text-sm text-slate-500">{{ $profile->phone }}</p>
            @endif
        </div>
        @if(! $profile->paystack_subaccount_code)
            <span class="inline-flex rounded-full bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">Paystack not connected</span>
        @endif
    </div>
</div>

@if($errors->any())
    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
        <p class="font-semibold">Please correct the highlighted fields and try again.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-xs sm:text-sm">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
@if(session('status') === 'interest-captured')
    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        Thanks! The seller has been notified.
    </div>
@endif
@if(session('status') === 'cart-updated')
    <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
        Cart updated.
    </div>
@endif

@php
    $cartProductIds = collect($cart['items'] ?? [])
        ->map(fn ($item) => (int) data_get($item, 'product.id'))
        ->filter()
        ->values()
        ->all();
@endphp

@if($template === 'services')
    <div id="shop-section" class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-slate-900">Services</h2>
            <span class="text-xs uppercase tracking-[0.3em] text-slate-400">Template</span>
        </div>
        <div class="mt-6 space-y-5">
            @forelse($products as $product)
                <div class="rounded-2xl border border-slate-200 p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">{{ $product->name }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $product->description }}</p>
                            <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $product->statusBadgeClass() }}">{{ $product->statusLabel() }}</span>
                        </div>
                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-5 py-4 text-center">
                            <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Service fee</p>
                            <p class="mt-2 text-lg font-semibold text-emerald-700">{{ \App\Support\Money::format($product->price, $currency) }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('public.products.pay', [$profile->public_slug, $product]) }}" class="mt-4 grid gap-3 sm:grid-cols-[1.2fr_1fr_1fr]">
                        @csrf
                        <input
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            placeholder="Phone number"
                            class="sm:col-span-3 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('phone_number') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                            data-strip-leading-zero="true"
                            inputmode="numeric"
                            autocomplete="tel"
                            required
                        />
                        @error('phone_number') <p class="sm:col-span-3 -mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <input
                            name="location"
                            value="{{ old('location') }}"
                            placeholder="Location (optional)"
                            class="sm:col-span-3 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('location') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        />
                        @error('location') <p class="sm:col-span-3 -mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <textarea
                            name="note"
                            rows="2"
                            placeholder="Notes for seller (optional)"
                            class="sm:col-span-3 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('note') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        >{{ old('note') }}</textarea>
                        @error('note') <p class="sm:col-span-3 -mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        <input type="hidden" name="phone_country" value="+233" />
                        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}" />
                        @php
                            $canPay = $product->isPayable();
                            $isAddedToCart = in_array((int) $product->id, $cartProductIds, true);
                            $sellerPhone = $profile->phone ?: ($profile->user?->phone);
                            $sellerPhone = $sellerPhone ? \App\Support\Phone::normalize($sellerPhone, '+233') : null;
                            $productUrl = route('public.product', ['product_slug' => $product->slug]);
                            $chatMessage = "Hi, I am interested in {$product->name}.\nLink: {$productUrl}";
                            $chatUrl = $sellerPhone ? \App\Support\WhatsApp::chatUrl($sellerPhone, $chatMessage) : null;
                        @endphp
                        <div class="sm:col-span-3 flex flex-wrap items-center gap-3">
                            <input type="number" name="quantity" min="1" value="1" class="w-20 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <button
                                type="submit"
                                formaction="{{ route('public.products.cart.add', [$profile->public_slug, $product]) }}"
                                formnovalidate
                                class="rounded-full border px-4 py-3 text-sm {{ $canPay ? ($isAddedToCart ? 'border-emerald-600 bg-emerald-50 font-bold text-emerald-700 hover:bg-emerald-100' : 'border-slate-200 font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700') : 'border-slate-200 font-semibold text-slate-400 cursor-not-allowed' }}"
                                {{ $canPay ? '' : 'disabled' }}
                            >
                                {{ $canPay ? ($isAddedToCart ? 'Added' : 'Add to cart') : 'Unavailable' }}
                            </button>
                            <button type="submit" class="rounded-full px-4 py-3 text-sm font-semibold text-white {{ ($canPay && ($paymentsEnabled ?? true)) ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 cursor-not-allowed' }}" {{ ($profile->paystack_subaccount_code && $canPay && ($paymentsEnabled ?? true)) ? '' : 'disabled' }}>
                                @if(! ($paymentsEnabled ?? true))
                                    Payments disabled
                                @else
                                    {{ $canPay ? 'Pay now' : 'Unavailable' }}
                                @endif
                            </button>
                            @if($chatUrl)
                                <a
                                    href="{{ $chatUrl }}"
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    aria-label="Chat on WhatsApp"
                                    title="Chat on WhatsApp"
                                    class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-emerald-200 text-emerald-700 hover:bg-emerald-50"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-5 w-5" fill="currentColor" aria-hidden="true">
                                        <path d="M20.5 3.5A11 11 0 0 0 3.54 17.14L2 22l4.99-1.5A11 11 0 1 0 20.5 3.5Zm-8.52 17a9.08 9.08 0 0 1-4.63-1.27l-.33-.19-2.97.89.9-2.9-.22-.35a9.09 9.09 0 1 1 7.25 3.82Zm4.98-6.84c-.27-.14-1.62-.8-1.87-.89-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.31.2-.58.07a7.46 7.46 0 0 1-2.19-1.35 8.24 8.24 0 0 1-1.52-1.9c-.16-.27-.02-.42.12-.56.12-.12.27-.31.41-.47.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.47-.07-.14-.61-1.48-.84-2.03-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.47.07-.72.34s-.95.93-.95 2.27.98 2.63 1.11 2.81c.14.18 1.92 2.93 4.66 4.11.65.28 1.16.45 1.55.58.65.21 1.24.18 1.71.11.52-.08 1.62-.66 1.85-1.3.23-.64.23-1.18.16-1.3-.07-.12-.25-.2-.52-.34Z"/>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            @empty
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">
                    No active services yet.
                </div>
            @endforelse
        </div>
    </div>
@else
    <div id="shop-section" class="mt-6 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="-mx-2 flex gap-1 overflow-x-auto px-2 pb-2 sm:mx-0 sm:px-0 sm:pb-0">
                @foreach($statusTabs ?? [] as $tab)
                    @php
                        $isActiveTab = ($statusFilter ?? 'all') === $tab['key'];
                        $tabUrl = route('public.listing', $profile->public_slug).'?'.http_build_query(array_filter([
                            'template' => 'products',
                            'status' => $tab['key'] !== 'all' ? $tab['key'] : null,
                        ]));
                    @endphp
                    <a href="{{ $tabUrl }}" class="whitespace-nowrap rounded-full px-3 py-1.5 text-sm font-medium {{ $isActiveTab ? 'bg-slate-900 text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-700' }}">
                        {{ $tab['label'] }}
                    </a>
                @endforeach
            </div>
            <span class="inline-flex items-center rounded-full border border-slate-300 px-4 py-1.5 text-sm font-semibold text-slate-600">
                {{ $products->count() }} results
            </span>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-3 sm:gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @forelse($products as $product)
                @php
                    $canPay = $product->isPayable();
                    $isAddedToCart = in_array((int) $product->id, $cartProductIds, true);
                    $sellerPhone = $profile->phone ?: ($profile->user?->phone);
                    $sellerPhone = $sellerPhone ? \App\Support\Phone::normalize($sellerPhone, '+233') : null;
                    $productUrl = route('public.product', ['product_slug' => $product->slug]);
                    $chatMessage = "Hi, I am interested in {$product->name}.\nLink: {$productUrl}";
                    $chatUrl = $sellerPhone ? \App\Support\WhatsApp::chatUrl($sellerPhone, $chatMessage) : null;
                    $statusDot = match ($product->status) {
                        \App\Models\Product::STATUS_LOW_STOCK => 'bg-amber-500',
                        \App\Models\Product::STATUS_PRE_ORDER => 'bg-indigo-500',
                        \App\Models\Product::STATUS_SOLD_OUT => 'bg-rose-500',
                        default => 'bg-emerald-600',
                    };
                @endphp
                <article class="group overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <div class="relative bg-slate-100">
                        <a href="{{ $productUrl }}" class="block">
                            @if($product->image_path)
                                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-44 w-full object-cover transition duration-300 group-hover:scale-[1.02] sm:h-56">
                            @else
                                <div class="flex h-56 items-center justify-center bg-slate-100 text-slate-400">
                                    <span class="text-xs uppercase tracking-[0.25em]">No image</span>
                                </div>
                            @endif
                        </a>
                        @if($chatUrl)
                            <a
                                href="{{ $chatUrl }}"
                                target="_blank"
                                rel="noreferrer noopener"
                                aria-label="Chat on WhatsApp"
                                title="Chat on WhatsApp"
                                class="absolute right-2 top-2 inline-flex h-8 w-8 items-center justify-center rounded-full border border-emerald-200 bg-white/95 text-emerald-700 shadow-sm hover:bg-emerald-50"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                    <path d="M20.5 3.5A11 11 0 0 0 3.54 17.14L2 22l4.99-1.5A11 11 0 1 0 20.5 3.5Zm-8.52 17a9.08 9.08 0 0 1-4.63-1.27l-.33-.19-2.97.89.9-2.9-.22-.35a9.09 9.09 0 1 1 7.25 3.82Zm4.98-6.84c-.27-.14-1.62-.8-1.87-.89-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.31.2-.58.07a7.46 7.46 0 0 1-2.19-1.35 8.24 8.24 0 0 1-1.52-1.9c-.16-.27-.02-.42.12-.56.12-.12.27-.31.41-.47.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.47-.07-.14-.61-1.48-.84-2.03-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.47.07-.72.34s-.95.93-.95 2.27.98 2.63 1.11 2.81c.14.18 1.92 2.93 4.66 4.11.65.28 1.16.45 1.55.58.65.21 1.24.18 1.71.11.52-.08 1.62-.66 1.85-1.3.23-.64.23-1.18.16-1.3-.07-.12-.25-.2-.52-.34Z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                    <div class="space-y-3 p-3">
                        <a href="{{ $productUrl }}" class="block text-xs font-bold uppercase leading-5 tracking-[0.02em] text-slate-900 hover:text-emerald-700">{{ $product->name }}</a>
                        <p class="line-clamp-1 text-sm text-emerald-700/80">{{ $product->description ?: $product->statusLabel() }}</p>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="h-3 w-3 rounded-full {{ $statusDot }}"></span>
                                <span class="h-3 w-3 rounded-full bg-slate-300"></span>
                            </div>
                            <span class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($product->price, $currency) }}</span>
                        </div>
                        <div class="grid w-full grid-cols-3 gap-2 text-xs md:flex md:items-center">
                            <form method="POST" action="{{ route('public.products.cart.add', [$profile->public_slug, $product]) }}" class="w-full">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button
                                    type="submit"
                                    class="w-full rounded-full border px-2.5 py-1 {{ $canPay ? ($isAddedToCart ? 'border-emerald-600 bg-emerald-50 font-bold text-emerald-700 hover:bg-emerald-100' : 'border-slate-200 font-semibold text-slate-600 hover:border-emerald-300 hover:text-emerald-700') : 'border-slate-200 font-semibold text-slate-400 cursor-not-allowed' }}"
                                    {{ $canPay ? '' : 'disabled' }}
                                >
                                    {{ $canPay ? ($isAddedToCart ? 'Added' : 'Add') : 'Unavailable' }}
                                </button>
                            </form>
                            <a href="{{ $productUrl }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 px-2.5 py-1 font-semibold text-slate-600 hover:border-emerald-300 hover:text-emerald-700">Details</a>
                            @if($chatUrl)
                                <a
                                    href="{{ $chatUrl }}"
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    aria-label="Chat on WhatsApp"
                                    title="Chat on WhatsApp"
                                    class="inline-flex w-full items-center justify-center rounded-full border border-emerald-200 px-2.5 py-1 font-semibold text-emerald-700 hover:bg-emerald-50 md:h-8 md:w-8 md:p-0"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                        <path d="M20.5 3.5A11 11 0 0 0 3.54 17.14L2 22l4.99-1.5A11 11 0 1 0 20.5 3.5Zm-8.52 17a9.08 9.08 0 0 1-4.63-1.27l-.33-.19-2.97.89.9-2.9-.22-.35a9.09 9.09 0 1 1 7.25 3.82Zm4.98-6.84c-.27-.14-1.62-.8-1.87-.89-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.31.2-.58.07a7.46 7.46 0 0 1-2.19-1.35 8.24 8.24 0 0 1-1.52-1.9c-.16-.27-.02-.42.12-.56.12-.12.27-.31.41-.47.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.47-.07-.14-.61-1.48-.84-2.03-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.47.07-.72.34s-.95.93-.95 2.27.98 2.63 1.11 2.81c.14.18 1.92 2.93 4.66 4.11.65.28 1.16.45 1.55.58.65.21 1.24.18 1.71.11.52-.08 1.62-.66 1.85-1.3.23-.64.23-1.18.16-1.3-.07-.12-.25-.2-.52-.34Z"/>
                                    </svg>
                                </a>
                            @else
                                <span class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 px-2.5 py-1 text-slate-400 md:h-8 md:w-8 md:p-0">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor" aria-hidden="true">
                                        <path d="M20.5 3.5A11 11 0 0 0 3.54 17.14L2 22l4.99-1.5A11 11 0 1 0 20.5 3.5Zm-8.52 17a9.08 9.08 0 0 1-4.63-1.27l-.33-.19-2.97.89.9-2.9-.22-.35a9.09 9.09 0 1 1 7.25 3.82Zm4.98-6.84c-.27-.14-1.62-.8-1.87-.89-.25-.09-.43-.14-.61.14-.18.27-.7.88-.86 1.06-.16.18-.31.2-.58.07a7.46 7.46 0 0 1-2.19-1.35 8.24 8.24 0 0 1-1.52-1.9c-.16-.27-.02-.42.12-.56.12-.12.27-.31.41-.47.14-.16.18-.27.27-.45.09-.18.05-.34-.02-.47-.07-.14-.61-1.48-.84-2.03-.22-.53-.45-.46-.61-.47h-.52c-.18 0-.47.07-.72.34s-.95.93-.95 2.27.98 2.63 1.11 2.81c.14.18 1.92 2.93 4.66 4.11.65.28 1.16.45 1.55.58.65.21 1.24.18 1.71.11.52-.08 1.62-.66 1.85-1.3.23-.64.23-1.18.16-1.3-.07-.12-.25-.2-.52-.34Z"/>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">
                    No products found in this filter.
                </div>
            @endforelse
        </div>
    </div>
@endif

@if(($paymentsEnabled ?? true))
    <div id="cart-section" class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <h2 class="text-lg font-semibold text-slate-900">Cart checkout</h2>
            <span class="text-sm text-slate-500">{{ $cart['count'] ?? 0 }} item(s)</span>
        </div>

        @if(($cart['count'] ?? 0) > 0)
            <form method="POST" action="{{ route('public.cart.update', $profile->public_slug) }}" class="mt-4 space-y-3">
                @csrf
                @foreach(($cart['items'] ?? collect()) as $item)
                    <div class="grid gap-3 rounded-xl border border-slate-100 p-3 sm:grid-cols-[1fr_auto_auto] sm:items-center">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $item['product']->name }}</p>
                            <p class="text-xs text-slate-500">{{ \App\Support\Money::format($item['unit_price'], $currency) }} each</p>
                        </div>
                        <input type="number" min="0" max="100" name="items[{{ $item['product']->id }}][quantity]" value="{{ $item['quantity'] }}" class="w-24 rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <div class="flex items-center justify-end gap-3">
                            <p class="text-sm font-semibold text-slate-900">{{ \App\Support\Money::format($item['line_total'], $currency) }}</p>
                            <button
                                type="submit"
                                formaction="{{ route('public.products.cart.remove', [$profile->public_slug, $item['product']]) }}"
                                name="_method"
                                value="DELETE"
                                class="rounded-full border border-rose-200 px-2.5 py-1 text-xs font-semibold text-rose-600 hover:bg-rose-50"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                @endforeach
                <p class="text-xs text-slate-500">Tip: set quantity to 0 then click "Update cart" to remove an item.</p>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-700">Total</span>
                    <span class="text-lg font-semibold text-emerald-700">{{ \App\Support\Money::format($cart['total'] ?? '0.00', $currency) }}</span>
                </div>
                <button type="submit" name="_method" value="PATCH" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                    Update cart
                </button>
            </form>

            <form method="POST" action="{{ route('public.cart.checkout', $profile->public_slug) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <input
                        name="phone_number"
                        value="{{ old('phone_number') }}"
                        placeholder="Phone number"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('phone_number') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                        data-strip-leading-zero="true"
                        inputmode="numeric"
                        autocomplete="tel"
                        required
                    />
                    @error('phone_number') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <input
                        name="location"
                        value="{{ old('location') }}"
                        placeholder="Location (optional)"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('location') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    />
                    @error('location') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <textarea
                        name="note"
                        rows="2"
                        placeholder="Notes for seller (optional)"
                        class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500 @error('note') border-rose-300 focus:border-rose-500 focus:ring-rose-500 @enderror"
                    >{{ old('note') }}</textarea>
                    @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <input type="hidden" name="phone_country" value="+233" />
                <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}" />
                <button type="submit" class="rounded-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500 sm:col-span-2">
                    Pay total
                </button>
            </form>
        @else
            <p class="mt-4 text-sm text-slate-500">Cart is empty. Add items above.</p>
        @endif
    </div>

    <a
        href="#cart-section"
        id="floating-cart-toggle"
        class="fixed bottom-5 right-5 z-40 inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-300"
    >
        <svg id="floating-icon-cart" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="9" cy="20" r="1"></circle>
            <circle cx="18" cy="20" r="1"></circle>
            <path d="M2 3h2l2.4 12.2a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.7L22 7H6"></path>
        </svg>
        <svg id="floating-icon-shop" xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9.5 4.5 4h15L21 9.5"></path>
            <path d="M4 10v9h16v-9"></path>
            <path d="M9 19v-6h6v6"></path>
        </svg>
        <span id="floating-cart-label">Cart</span>
    </a>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toggle = document.getElementById('floating-cart-toggle');
            var cart = document.getElementById('cart-section');
            var shop = document.getElementById('shop-section');
            var label = document.getElementById('floating-cart-label');
            var cartIcon = document.getElementById('floating-icon-cart');
            var shopIcon = document.getElementById('floating-icon-shop');

            if (!toggle || !cart || !shop || !label || !cartIcon || !shopIcon) {
                return;
            }

            var atCart = false;
            var isRefreshing = false;

            function isCartInView() {
                var rect = cart.getBoundingClientRect();
                var viewport = window.innerHeight || document.documentElement.clientHeight;
                return rect.top <= viewport * 0.45 && rect.bottom >= viewport * 0.25;
            }

            function setMode(cartMode) {
                atCart = cartMode;
                toggle.setAttribute('href', cartMode ? '#shop-section' : '#cart-section');
                label.textContent = cartMode ? 'Go to Shop' : 'Cart';
                cartIcon.classList.toggle('hidden', cartMode);
                shopIcon.classList.toggle('hidden', !cartMode);
            }

            function syncMode() {
                setMode(isCartInView());
            }

            function skeletonCardsMarkup(count) {
                var cards = [];
                for (var i = 0; i < count; i++) {
                    cards.push(
                        '<article class="animate-pulse overflow-hidden rounded-xl border border-slate-200 bg-white">'
                        + '<div class="h-44 w-full bg-slate-200 sm:h-56"></div>'
                        + '<div class="space-y-3 p-3">'
                        + '<div class="h-3 w-3/4 rounded bg-slate-200"></div>'
                        + '<div class="h-3 w-1/2 rounded bg-slate-200"></div>'
                        + '<div class="h-8 w-full rounded-full bg-slate-200"></div>'
                        + '</div>'
                        + '</article>'
                    );
                }

                return cards.join('');
            }

            function showShopSkeleton() {
                var grid = shop.querySelector('div.mt-5.grid');
                if (!grid) {
                    return;
                }

                grid.setAttribute('aria-busy', 'true');
                grid.innerHTML = skeletonCardsMarkup(6);
            }

            function refreshNodeRefs() {
                toggle = document.getElementById('floating-cart-toggle');
                cart = document.getElementById('cart-section');
                shop = document.getElementById('shop-section');
                label = document.getElementById('floating-cart-label');
                cartIcon = document.getElementById('floating-icon-cart');
                shopIcon = document.getElementById('floating-icon-shop');
            }

            async function refreshSectionsFromResponse(responseText, nextUrl) {
                var parser = new DOMParser();
                var doc = parser.parseFromString(responseText, 'text/html');
                var nextShop = doc.getElementById('shop-section');
                var nextCart = doc.getElementById('cart-section');
                var nextToggle = doc.getElementById('floating-cart-toggle');

                if (nextShop && shop) {
                    shop.replaceWith(nextShop);
                }
                if (nextCart && cart) {
                    cart.replaceWith(nextCart);
                }
                if (nextToggle && toggle) {
                    toggle.replaceWith(nextToggle);
                }

                refreshNodeRefs();
                bindInteractions();
                bindFloatingToggle();
                syncMode();

                if (nextUrl) {
                    window.history.pushState({}, '', nextUrl);
                }
            }

            function trackTelemetry(eventName, payload, level) {
                if (window.lpTelemetry && typeof window.lpTelemetry.track === 'function') {
                    window.lpTelemetry.track(eventName, payload || {}, level || 'info');
                }
            }

            async function asyncRequest(url, options, showSkeleton, pushUrl, telemetryMeta) {
                if (isRefreshing) {
                    return;
                }
                isRefreshing = true;
                var startedAt = window.performance ? performance.now() : Date.now();

                if (showSkeleton) {
                    showShopSkeleton();
                }

                try {
                    var response = await fetch(url, Object.assign({
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                        credentials: 'same-origin',
                    }, options || {}));

                    var html = await response.text();
                    await refreshSectionsFromResponse(html, pushUrl || null);
                    var endedAt = window.performance ? performance.now() : Date.now();
                    if (telemetryMeta && telemetryMeta.action) {
                        trackTelemetry('optimistic_action_success', {
                            screen: window.location.pathname,
                            action: telemetryMeta.action,
                            metricMs: Math.round(endedAt - startedAt),
                        });
                    }
                } catch (error) {
                    var endedAt = window.performance ? performance.now() : Date.now();
                    if (telemetryMeta && telemetryMeta.action) {
                        trackTelemetry('optimistic_action_failed', {
                            screen: window.location.pathname,
                            action: telemetryMeta.action,
                            metricMs: Math.round(endedAt - startedAt),
                            rollback: true,
                        }, 'warn');
                    }
                    window.location.reload();
                } finally {
                    isRefreshing = false;
                }
            }

            function bindFilterTabs() {
                if (!shop) {
                    return;
                }

                shop.querySelectorAll('a[href*="status="]').forEach(function (link) {
                    if (link.dataset.boundAsync === '1') {
                        return;
                    }
                    link.dataset.boundAsync = '1';
                    link.addEventListener('click', function (event) {
                        event.preventDefault();
                        asyncRequest(link.href, { method: 'GET' }, true, link.href, { action: 'filter_products' });
                    });
                });
            }

            function bindAddToCartForms() {
                if (!shop) {
                    return;
                }

                shop.querySelectorAll('form').forEach(function (form) {
                    if (form.closest('#cart-section') || form.dataset.boundAsyncCart === '1') {
                        return;
                    }
                    form.dataset.boundAsyncCart = '1';

                    // Fallback for browsers without event.submitter support.
                    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                        if (button.dataset.boundClickSubmitter === '1') {
                            return;
                        }
                        button.dataset.boundClickSubmitter = '1';
                        button.addEventListener('click', function () {
                            form.__lastSubmitter = button;
                        });
                    });

                    form.addEventListener('submit', function (event) {
                        var submitter = event.submitter || form.__lastSubmitter || form.querySelector('button[type="submit"]');
                        if (!submitter) {
                            return;
                        }
                        var targetUrl = submitter.getAttribute('formaction') || form.action || '';
                        var isAddToCartAction = targetUrl.indexOf('/products/') !== -1 && targetUrl.indexOf('/cart') !== -1;
                        if (!isAddToCartAction) {
                            return;
                        }

                        event.preventDefault();

                        // Optimistic UI: make action feel instant.
                        submitter.disabled = true;
                        submitter.textContent = 'Added';
                        submitter.classList.add('border-emerald-600', 'bg-emerald-50', 'font-bold', 'text-emerald-700');

                        var payload = new FormData(form, submitter);
                        asyncRequest(targetUrl, { method: 'POST', body: payload }, false, null, { action: 'add_to_cart' });
                    });
                });
            }

            function bindCartForm() {
                if (!cart) {
                    return;
                }

                var updateForm = cart.querySelector('form[action*="/cart"]');
                if (!updateForm || updateForm.action.indexOf('/checkout') !== -1 || updateForm.dataset.boundAsync === '1') {
                    return;
                }
                updateForm.dataset.boundAsync = '1';

                updateForm.addEventListener('submit', function (event) {
                    var submitter = event.submitter || updateForm.querySelector('button[type="submit"]');
                    if (!submitter) {
                        return;
                    }

                    event.preventDefault();
                    var actionUrl = submitter.getAttribute('formaction') || updateForm.action;
                    var payload = new FormData(updateForm, submitter);

                    // Optimistic remove feedback.
                    if ((submitter.value || '').toUpperCase() === 'DELETE') {
                        var row = submitter.closest('div.grid');
                        if (row) {
                            row.style.opacity = '0.45';
                        }
                    } else {
                        submitter.disabled = true;
                        submitter.textContent = 'Updating...';
                    }

                    var actionType = ((submitter.value || '').toUpperCase() === 'DELETE') ? 'remove_from_cart' : 'update_cart';
                    asyncRequest(actionUrl, { method: 'POST', body: payload }, false, null, { action: actionType });
                });
            }

            function bindInteractions() {
                bindFilterTabs();
                bindAddToCartForms();
                bindCartForm();
            }

            function bindFloatingToggle() {
                if (!toggle || toggle.dataset.boundScroll === '1') {
                    return;
                }

                toggle.dataset.boundScroll = '1';
                toggle.addEventListener('click', function (event) {
                    event.preventDefault();
                    var target = atCart ? shop : cart;
                    if (!target) {
                        return;
                    }
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    setTimeout(syncMode, 300);
                });
            }

            window.addEventListener('scroll', syncMode, { passive: true });
            window.addEventListener('resize', syncMode);
            window.addEventListener('popstate', function () {
                asyncRequest(window.location.href, { method: 'GET' }, true, null, { action: 'history_navigation_refresh' });
            });

            bindInteractions();
            bindFloatingToggle();
            syncMode();
        });
    </script>
@endif
