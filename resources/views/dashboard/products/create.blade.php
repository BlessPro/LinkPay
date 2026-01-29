@php($title = 'Create Product')
@extends('layouts.dashboard')

@section('content')
    <div class="max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Add product</h2>
        <form class="mt-6 space-y-5" method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700">Name</label>
                <input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Description</label>
                <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                @error('description') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Price</label>
                <input name="price" value="{{ old('price') }}" required type="number" step="0.01" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('price') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Image (optional)</label>
                <input name="image" type="file" class="mt-2 w-full rounded-xl border-slate-200 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700" />
                @error('image') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Stock status</label>
                <select name="status" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                    @foreach(\App\Models\Product::statusOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', \App\Models\Product::STATUS_IN_STOCK) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-3">
                <input id="is_active" name="is_active" type="checkbox" value="1" checked class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                <label for="is_active" class="text-sm text-slate-600">Active</label>
            </div>
            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Save product
                </button>
                <a href="{{ route('products.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
