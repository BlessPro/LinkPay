@php
    $title = $product->name;
@endphp
@extends('layouts.public')

@section('og_title', $ogTitle ?? ($product->name.' - '.($profile?->business_name ?? 'Seller')))
@section('og_description', $ogDescription ?? ($smallDescription ?? 'Discover this product on 8Kommerce.'))
@section('og_image', $ogImage ?? url('/images/og-default.jpg'))
@section('og_image_width', $ogImageWidth ?? '1200')
@section('og_image_height', $ogImageHeight ?? '630')
@section('og_url', $ogUrl ?? url()->current())
@section('og_type', $ogType ?? 'website')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Product</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $product->name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">Seller: {{ $profile?->business_name ?? '8Kommerce seller' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full px-4 py-2 text-xs font-semibold {{ $product->statusBadgeClass() }}">
                        {{ $product->statusLabel() }}
                    </span>
                    <span class="text-lg font-semibold text-emerald-700">
                        {{ \App\Support\Money::format((string) $product->price, $currency) }}
                    </span>
                </div>
            </div>

            @if($product->image_path)
                <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="mt-6 w-full rounded-2xl object-cover">
            @endif

            @if($smallDescription)
                <p class="mt-5 text-sm text-slate-600">{{ $smallDescription }}</p>
            @endif

            @if(! $profile?->public_slug)
                <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                    This seller has not set up their public page yet. Please try again later.
                </div>
            @else
                @if($errors->any())
                    <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif
                @if(session('status') === 'interest-captured')
                    <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        Thanks! The seller has been notified.
                    </div>
                @endif

                @php
                    $canPay = $product->isPayable();
                    $sellerPhone = $profile->phone ?: ($profile->user?->phone);
                    $sellerPhone = $sellerPhone ? \App\Support\Phone::normalize($sellerPhone, '+233') : null;
                    $productUrl = route('public.product', ['product_slug' => $product->slug]);
                    $chatMessage = "Hi there, I am interested in {$product->name}. Is it available? Please tell me more.\nLink: {$productUrl}";
                    $chatUrl = $sellerPhone ? \App\Support\WhatsApp::chatUrl($sellerPhone, $chatMessage) : null;
                @endphp

                <form method="POST" action="{{ route('public.products.pay', [$profile->public_slug, $product]) }}" class="mt-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[1.2fr_1fr_1fr]">
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
                    <input type="hidden" name="phone_country" value="+233" />
                    <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}" />
                    <div class="sm:col-span-3 flex flex-wrap gap-3">
                        <button
                            type="submit"
                            class="rounded-full px-5 py-3 text-sm font-semibold text-white {{ ($canPay && ($paymentsEnabled ?? true)) ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 cursor-not-allowed' }}"
                            {{ ($profile->paystack_subaccount_code && $canPay && ($paymentsEnabled ?? true)) ? '' : 'disabled' }}
                        >
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
                                class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700"
                            >
                                Chat on WhatsApp
                            </a>
                        @endif
                        <a
                            href="{{ route('public.listing', [$profile->public_slug]) }}"
                            class="ml-auto rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-600 hover:border-emerald-200 hover:text-emerald-700"
                        >
                            View seller page
                        </a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection
