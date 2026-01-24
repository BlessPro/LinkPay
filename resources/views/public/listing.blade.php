@php($title = $profile->business_name)
@extends('layouts.public')

@section('content')
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
    </div>

    <div class="mt-8 grid gap-6 md:grid-cols-2">
        @forelse($products as $product)
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                @if($product->image_path)
                    <img src="{{ asset('storage/'.$product->image_path) }}" alt="{{ $product->name }}" class="h-48 w-full rounded-2xl object-cover">
                @endif
                <h2 class="mt-4 text-xl font-semibold text-slate-900">{{ $product->name }}</h2>
                <p class="mt-2 text-sm text-slate-600">{{ $product->description }}</p>
                <div class="mt-4 flex items-center justify-between">
                    <span class="text-lg font-semibold text-emerald-700">{{ \App\Support\Money::format($product->price, $currency) }}</span>
                </div>
                <form method="POST" action="{{ route('public.products.pay', [$profile->public_slug, $product]) }}" class="mt-4 space-y-3">
                    @csrf
                    <input name="email" placeholder="Customer email" class="w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" required />
                    <button type="submit" class="w-full rounded-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white hover:bg-emerald-500" {{ $profile->paystack_subaccount_code ? '' : 'disabled' }}>
                        Pay now
                    </button>
                </form>
            </div>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500">
                No active products yet.
            </div>
        @endforelse
    </div>
@endsection
