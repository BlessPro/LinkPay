@php($title = 'Create Invoice')
@extends('layouts.dashboard')

@section('content')
    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-semibold text-slate-900">Create invoice</h2>
        <form class="mt-6 space-y-5" method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label class="text-sm font-medium text-slate-700">Title</label>
                <input name="title" value="{{ old('title') }}" required class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('title') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Description</label>
                <textarea name="description" rows="4" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">{{ old('description') }}</textarea>
                @error('description') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-slate-700">Total amount</label>
                    <input name="total_amount" value="{{ old('total_amount') }}" required type="number" step="0.01" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                    @error('total_amount') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Payment mode</label>
                    <select name="payment_mode" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="FULL" {{ old('payment_mode') === 'FULL' ? 'selected' : '' }}>Full payment</option>
                        <option value="PARTIAL" {{ old('payment_mode') === 'PARTIAL' ? 'selected' : '' }}>Partial (deposit)</option>
                    </select>
                    @error('payment_mode') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Deposit amount (for partial)</label>
                <input name="deposit_amount" value="{{ old('deposit_amount') }}" type="number" step="0.01" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('deposit_amount') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Customer name (optional)</label>
                <input name="customer_name" value="{{ old('customer_name') }}" class="mt-2 w-full rounded-xl border-slate-200 focus:border-emerald-500 focus:ring-emerald-500" />
                @error('customer_name') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="text-sm font-medium text-slate-700">Image (optional)</label>
                <input name="image" type="file" class="mt-2 w-full rounded-xl border-slate-200 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-emerald-700" />
                @error('image') <p class="mt-2 text-xs text-rose-500">{{ $message }}</p> @enderror
            </div>
            <div class="flex items-center gap-4">
                <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 text-sm font-semibold text-white hover:bg-emerald-500">
                    Create invoice
                </button>
                <a href="{{ route('invoices.index') }}" class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
