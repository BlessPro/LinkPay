@php
    $title = $product->name;
@endphp
@extends('layouts.public')

@section('og_title', $ogTitle ?? ($product->name.' - '.($profile?->business_name ?? 'Seller')))
@section('og_description', $ogDescription ?? ($smallDescription ?? 'Discover this product on LinkPay.'))
@section('og_image', $ogImage ?? asset('images/og-default.png'))
@section('og_url', $ogUrl ?? url()->current())
@section('og_type', $ogType ?? 'website')

@section('content')
    <div class="mx-auto max-w-5xl space-y-6">
        <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Product</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $product->name }}</h1>
                    <p class="mt-1 text-sm text-slate-600">Seller: {{ $profile?->business_name ?? 'LinkPay seller' }}</p>
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
                @endphp

                <form method="POST" action="{{ route('public.products.pay', [$profile->public_slug, $product]) }}" class="mt-6 grid gap-3 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-[1.2fr_1fr_1fr]">
                    @csrf
                    <input name="name" placeholder="Customer name (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <input name="phone_number" placeholder="WhatsApp / phone number" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" data-strip-leading-zero="true" />
                    <input name="email" placeholder="Email (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                    <textarea name="note" rows="2" placeholder="Note (optional)" class="sm:col-span-3 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    <input type="hidden" name="phone_country" value="+233" />
                    <div class="sm:col-span-3 flex flex-wrap gap-3">
                        <button
                            type="submit"
                            class="rounded-full px-5 py-3 text-sm font-semibold text-white {{ $canPay ? 'bg-emerald-600 hover:bg-emerald-500' : 'bg-slate-300 cursor-not-allowed' }}"
                            {{ ($profile->paystack_subaccount_code && $canPay) ? '' : 'disabled' }}
                        >
                            {{ $canPay ? 'Pay now' : 'Unavailable' }}
                        </button>
                        <button
                            type="submit"
                            formaction="{{ route('public.products.interest', [$profile->public_slug, $product]) }}"
                            class="rounded-full border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:border-emerald-200 hover:text-emerald-700"
                        >
                            Chat on WhatsApp
                        </button>
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
