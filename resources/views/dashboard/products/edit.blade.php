@php($title = 'Edit Product')
@extends('layouts.dashboard')

@section('content')
    <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Edit product</h2>
            <form class="mt-6 space-y-5" method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div>
                <label class="text-sm font-medium text-slate-700">Name</label>
                <input name="name" value="{{ old('name', $product->name) }}" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Description</label>
                <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">{{ old('description', $product->description) }}</textarea>
                @error('description') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Price</label>
                <input name="price" value="{{ old('price', $product->price) }}" required type="number" step="0.01" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('price') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Image (optional)</label>
                <input name="image" type="file" class="mt-2 w-full rounded-xl border-slate-200 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700" />
                @if($product->image_path)
                    <p class="mt-2 text-xs text-slate-500">Current image set.</p>
                @endif
                @error('image') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3">
                <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                <label for="is_active" class="text-sm text-slate-600">Active</label>
            </div>
                <div class="flex items-center gap-4">
                    <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        Update product
                    </button>
                    <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
            </form>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-slate-900">Product insights (30 days)</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                    <p class="text-xs text-slate-400">Impressions</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $stats['impressions'] }} ({{ $stats['impressionsUnique'] }} unique)</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                    <p class="text-xs text-slate-400">Clicks</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $stats['clicks'] }} ({{ $stats['clicksUnique'] }} unique)</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                    <p class="text-xs text-slate-400">Payments</p>
                    <p class="text-sm font-semibold text-slate-900">{{ $stats['payments'] }}</p>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2">
                    <p class="text-xs text-slate-400">Conversion</p>
                    <p class="text-sm font-semibold text-slate-900">{{ number_format($stats['conversion'], 1) }}%</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-600">Total paid: {{ \App\Support\Money::format($stats['paymentTotal'], $currency) }}</p>
        </div>
    </div>
@endsection
