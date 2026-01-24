@php($title = 'Payment successful')
@extends('layouts.public')

@section('content')
    <div class="mx-auto max-w-2xl rounded-3xl border border-emerald-100 bg-white/90 p-8 text-center shadow-sm">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
            OK
        </div>
        <h1 class="mt-4 text-3xl font-semibold text-slate-900">Payment successful</h1>
        <p class="mt-2 text-sm text-slate-600">Thanks for your payment. A confirmation will be sent by the seller.</p>

        @if($payment)
            @php
                $sellerName = $payment->invoice?->user?->sellerProfile?->business_name
                    ?? $payment->product?->user?->sellerProfile?->business_name
                    ?? 'Seller';
                $itemName = $payment->invoice?->title ?? $payment->product?->name ?? 'payment';
                $message = "Hi {$sellerName}, I just paid {$currency} ".number_format((float) $payment->amount, 2, '.', ',')." for {$itemName}. Reference: {$payment->reference}.";
            @endphp

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50/70 p-4 text-left">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Copy message to seller</p>
                <textarea readonly class="mt-3 w-full rounded-xl border-slate-200 bg-white p-3 text-sm text-slate-700" rows="4">{{ $message }}</textarea>
            </div>
        @else
            @if($reference)
                <p class="mt-6 text-sm text-slate-500">Reference: {{ $reference }}</p>
            @endif
        @endif
    </div>
@endsection
