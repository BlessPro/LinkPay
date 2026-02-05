<div class="rounded-3xl border border-slate-200 bg-white/80 p-6 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Seller</p>
            <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $profile->business_name }}</h1>
            @if($profile->phone)
                <p class="mt-1 text-sm text-slate-500">{{ $profile->phone }}</p>
            @endif
        </div>
        @if(! $profile->paystack_subaccount_code)
            <span class="rounded-full bg-amber-50 px-4 py-2 text-xs font-semibold text-amber-700">Paystack not connected</span>
        @endif
    </div>
    @if($errors->any())
        <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
            {{ $errors->first() }}
        </div>
    @endif
    @if(session('status') === 'interest-captured')
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Thanks! The seller has been notified.
        </div>
    @endif
</div>

@if($template === 'services')
    <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
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
                        <input name="name" placeholder="Customer name (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        <input name="phone_number" placeholder="WhatsApp / phone number" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" data-strip-leading-zero="true" />
                        <input name="email" placeholder="Email (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                        <textarea name="note" rows="2" placeholder="Note (optional)" class="sm:col-span-3 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                        <input type="hidden" name="phone_country" value="+233" />
                        @php
                            $canPay = $product->isPayable();
                            $sellerPhone = $profile->phone ?: ($profile->user?->phone);
                            $sellerPhone = $sellerPhone ? \App\Support\Phone::normalize($sellerPhone, '+233') : null;
                            $productUrl = route('public.product', ['product_slug' => $product->slug]);
                            $chatMessage = "Hi there, I am interested in {$product->name}. Is it available? Please tell me more.\nLink: {$productUrl}";
                            $chatUrl = $sellerPhone ? \App\Support\WhatsApp::chatUrl($sellerPhone, $chatMessage) : null;
                        @endphp
                        <div class="sm:col-span-3 flex flex-wrap gap-3">
                            <button type="submit" class="rounded-full px-4 py-3 text-sm font-semibold text-white {{ $canPay ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 cursor-not-allowed' }}" {{ ($profile->paystack_subaccount_code && $canPay) ? '' : 'disabled' }}>
                                {{ $canPay ? 'Pay now' : 'Unavailable' }}
                            </button>
                            @if($chatUrl)
                                <a href="{{ $chatUrl }}" target="_blank" rel="noreferrer noopener" class="rounded-full border border-slate-200 px-4 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                    Chat on WhatsApp
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
    <div class="mt-6 grid gap-6 md:grid-cols-2">
        @forelse($products as $product)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                @if($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-48 w-full rounded-2xl object-cover">
                @endif
                <h2 class="mt-4 text-xl font-semibold text-slate-900">{{ $product->name }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $product->description }}</p>
                @php
                    $canPay = $product->isPayable();
                @endphp
                <span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $product->statusBadgeClass() }}">{{ $product->statusLabel() }}</span>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-lg font-semibold text-emerald-700">{{ \App\Support\Money::format($product->price, $currency) }}</span>
                </div>
                <form method="POST" action="{{ route('public.products.pay', [$profile->public_slug, $product]) }}" class="mt-4 grid gap-3">
                    @csrf
                    <input name="name" placeholder="Customer name (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <input name="phone_number" placeholder="WhatsApp / phone number" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" data-strip-leading-zero="true" />
                    <input name="email" placeholder="Email (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <textarea name="note" rows="2" placeholder="Note (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    <input type="hidden" name="phone_country" value="+233" />
                    @php
                        $sellerPhone = $profile->phone ?: ($profile->user?->phone);
                        $sellerPhone = $sellerPhone ? \App\Support\Phone::normalize($sellerPhone, '+233') : null;
                        $productUrl = route('public.product', ['product_slug' => $product->slug]);
                        $chatMessage = "Hi there, I am interested in {$product->name}. Is it available? Please tell me more.\nLink: {$productUrl}";
                        $chatUrl = $sellerPhone ? \App\Support\WhatsApp::chatUrl($sellerPhone, $chatMessage) : null;
                    @endphp
                    <div class="flex flex-wrap gap-3">
                        <button type="submit" class="flex-1 rounded-full px-4 py-3 text-sm font-semibold text-white {{ $canPay ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 cursor-not-allowed' }}" {{ ($profile->paystack_subaccount_code && $canPay) ? '' : 'disabled' }}>
                            {{ $canPay ? 'Pay now' : 'Unavailable' }}
                        </button>
                        @if($chatUrl)
                            <a href="{{ $chatUrl }}" target="_blank" rel="noreferrer noopener" class="flex-1 rounded-full border border-slate-200 px-4 py-3 text-center text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700">
                                Chat on WhatsApp
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">
                No active products yet.
            </div>
        @endforelse
    </div>
@endif
