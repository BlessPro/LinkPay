@php
    $title = 'Edit Product';
@endphp
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
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Stock quantity</label>
                    <input name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}" type="number" min="0" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('stock_quantity') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Low stock threshold</label>
                    <input name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}" type="number" min="0" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('low_stock_threshold') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Image (optional)</label>
                <input name="image" type="file" accept="image/*" class="mt-2 w-full rounded-xl border-slate-200 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700" />
                @if($product->image_path)
                    <p class="mt-2 text-xs text-slate-500">Current image set.</p>
                @endif
                @error('image') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Stock status</label>
                <div class="relative mt-2">
                    <select name="status" class="w-full appearance-none rounded-xl border-slate-200 bg-white pr-10 text-sm text-slate-700 focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach(\App\Models\Product::statusOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $product->status ?? \App\Models\Product::STATUS_IN_STOCK) === $value)>{{ $label }}</option>
                    @endforeach
                    </select>
                    <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </span>
                </div>
                @error('status') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3">
                <input id="is_active" name="is_active" type="checkbox" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="h-5 w-5 cursor-pointer rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                <label for="is_active" class="cursor-pointer text-sm text-slate-600">Active</label>
            </div>
                <div class="hidden items-center gap-4 sm:flex">
                    <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                        Update product
                    </button>
                    <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
                </div>
                <div class="mt-3 flex items-center gap-2 sm:hidden">
                    <div class="flex w-full items-center gap-2">
                        <a href="{{ route('products.index') }}" class="inline-flex flex-1 items-center justify-center rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700">
                            Cancel
                        </a>
                        <button type="submit" class="inline-flex flex-1 items-center justify-center rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white">
                            Update product
                        </button>
                    </div>
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
                <div class="rounded-lg border border-slate-100 bg-slate-50/70 px-3 py-2 sm:col-span-2">
                    <p class="text-xs text-slate-400">Inventory</p>
                    <p class="text-sm font-semibold text-slate-900">Qty {{ $product->stock_quantity }} · Threshold {{ $product->low_stock_threshold }}</p>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-600">Total paid: {{ \App\Support\Money::format($stats['paymentTotal'], $currency) }}</p>
        </div>
    </div>
@endsection
