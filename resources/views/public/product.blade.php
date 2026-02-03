@php($title = $product->name)
@extends('layouts.public')

@section('og_title', $ogTitle ?? ($product->name.' • '.($seller->business_name ?? 'Seller')))
@section('og_description', $ogDescription ?? $shortDescription ?? 'Discover this product on LinkPay.')
@section('og_image', $ogImage ?? asset('images/og-default.png'))
@section('og_url', $ogUrl ?? url()->current())
@section('og_type', $ogType ?? 'website')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.35em] text-slate-400">Product</p>
                <h1 class="mt-2 text-3xl font-semibold text-slate-900">{{ $product->name }}</h1>
                <p class="mt-1 text-sm text-slate-600">Seller: {{ $seller?->business_name ?? 'LinkPay seller' }}</p>
            </div>
            <p class="rounded-full bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">{{ \App\Models\Product::statusOptions()[$product->status] ?? 'Available' }}</p>
        </div>

        @if($product->image_path)
            <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="w-full rounded-2xl object-cover">
        @endif

        <p class="text-sm text-slate-600">{{ $smallDescription }}</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-100 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Price</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">{{ \App\Support\Money::format((string) $product->price, $currency) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-100 px-4 py-4">
                <p class="text-xs uppercase tracking-[0.3em] text-slate-400">Status</p>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ \App\Models\Product::statusOptions()[$product->status] ?? 'Available' }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/80 p-4 text-sm text-emerald-700">
            Tap the seller’s WhatsApp link to discuss or pay directly.
        </div>
    </div>
@endsection
