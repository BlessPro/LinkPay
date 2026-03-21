@php($title = 'Coupons')
@extends('layouts.dashboard')

@section('content')
    @if(session('status') === 'coupon-created')
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            Coupon created successfully.
        </div>
    @endif
    @if(session('status') === 'coupon-updated')
        <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            Coupon status updated.
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Create coupon</h2>
            <p class="mt-1 text-sm text-slate-500">Use codes to drive conversions on cart checkout.</p>
            <form method="POST" action="{{ route('coupons.store') }}" class="mt-5 grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="text-sm font-medium text-slate-700">Code</label>
                    <input name="code" value="{{ old('code') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="SAVE10" />
                    @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Type</label>
                    <select name="discount_type" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                        <option value="PERCENT" @selected(old('discount_type', 'PERCENT') === 'PERCENT')>Percent</option>
                        <option value="FIXED" @selected(old('discount_type') === 'FIXED')>Fixed amount</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Discount value</label>
                    <input name="discount_value" type="number" step="0.01" min="0.01" value="{{ old('discount_value') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" placeholder="10" />
                    @error('discount_value') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Max discount (optional)</label>
                    <input name="max_discount" type="number" step="0.01" min="0" value="{{ old('max_discount') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Min order amount (optional)</label>
                    <input name="min_order_amount" type="number" step="0.01" min="0" value="{{ old('min_order_amount') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Usage limit (optional)</label>
                    <input name="usage_limit" type="number" min="1" value="{{ old('usage_limit') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Starts at (optional)</label>
                    <input name="starts_at" type="datetime-local" value="{{ old('starts_at') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-700">Ends at (optional)</label>
                    <input name="ends_at" type="datetime-local" value="{{ old('ends_at') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm focus:border-emerald-500 focus:ring-emerald-500" />
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 sm:col-span-2">
                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500" checked>
                    Active immediately
                </label>
                <button type="submit" class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500 sm:col-span-2">
                    Create coupon
                </button>
            </form>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-slate-900">Existing coupons</h2>
            <div class="mt-4 space-y-3">
                @forelse($coupons as $coupon)
                    <div class="rounded-xl border border-slate-100 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">{{ $coupon->code }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ $coupon->discount_type === \App\Models\Coupon::TYPE_PERCENT ? rtrim(rtrim(number_format((float) $coupon->discount_value, 2), '0'), '.') . '%' : \App\Support\Money::format((string) $coupon->discount_value, config('services.paystack.currency', 'GHS')) }}
                                    · Used {{ $coupon->used_count }}{{ $coupon->usage_limit ? '/'.$coupon->usage_limit : '' }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('coupons.update', $coupon) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $coupon->is_active ? 0 : 1 }}">
                                <button type="submit" class="rounded-full border px-3 py-1 text-xs font-semibold {{ $coupon->is_active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                    {{ $coupon->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">
                            Min: {{ $coupon->min_order_amount ? \App\Support\Money::format((string) $coupon->min_order_amount, config('services.paystack.currency', 'GHS')) : 'None' }}
                            · Max discount: {{ $coupon->max_discount ? \App\Support\Money::format((string) $coupon->max_discount, config('services.paystack.currency', 'GHS')) : 'None' }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No coupons yet.</p>
                @endforelse
            </div>
            <div class="mt-6">
                {{ $coupons->links() }}
            </div>
        </section>
    </div>
@endsection
