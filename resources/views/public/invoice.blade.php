@php($title = $invoice->title)
@extends('layouts.public')

@section('og_title', $ogTitle ?? $title)
@section('og_description', $ogDescription ?? "Amount due: GHS {$amountDue}. Tap to view and pay securely.")
@section('og_image', $ogImage ?? asset('images/og-default.png'))
@section('og_url', $ogUrl ?? route('public.invoice', $invoice->token))
@section('og_type', $ogType ?? 'website')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Invoice</p>
                    <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $invoice->title }}</h1>
                    <p class="mt-2 text-sm text-slate-600">Seller: {{ $seller?->business_name }}</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">{{ $invoice->status }}</span>
            </div>

            <p class="mt-4 text-sm text-slate-600">{{ $invoice->description }}</p>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-slate-100 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Total</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->total_amount, $currency) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Paid</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($invoice->paid_total, $currency) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-100 px-4 py-3">
                    <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Balance</p>
                    <p class="mt-2 text-lg font-semibold text-slate-900">{{ \App\Support\Money::format($balance, $currency) }}</p>
                </div>
            </div>

            @if($invoice->image_path)
                <img src="{{ asset('storage/'.$invoice->image_path) }}" alt="{{ $invoice->title }}" class="mt-6 h-64 w-full rounded-2xl object-cover">
            @endif
        </div>

        <div class="rounded-3xl border border-emerald-100 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Pay invoice</h2>
            <p class="mt-2 text-sm text-slate-600">
                Amount due now: <span class="font-semibold text-emerald-700">{{ \App\Support\Money::format($amountDue, $currency) }}</span>
            </p>

            @if($errors->any())
                <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-600">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(! $seller?->paystack_subaccount_code)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    Seller is not connected to Paystack yet.
                </div>
            @endif

            <form method="POST" action="{{ route('public.invoice.pay', $invoice->token) }}" class="mt-5 space-y-3">
                @csrf
                <input name="name" placeholder="Name (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                <input name="phone_number" placeholder="WhatsApp / phone number" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" required data-strip-leading-zero="true" />
                <input name="email" placeholder="Email (optional)" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                <input type="hidden" name="phone_country" value="+233" />
                <button type="submit" class="w-full rounded-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500" {{ $invoice->status === \App\Models\Invoice::STATUS_PAID || ! $seller?->paystack_subaccount_code ? 'disabled' : '' }}>
                    Pay amount due
                </button>
            </form>

            <div class="mt-6 rounded-2xl border border-slate-100 bg-slate-50/60 px-4 py-3 text-xs text-slate-500">
                This invoice supports {{ $invoice->payment_mode }} payments.
            </div>
        </div>
    </div>
@endsection
