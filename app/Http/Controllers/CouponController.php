<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CouponController extends Controller
{
    public function index(Request $request): View
    {
        $coupons = $request->user()
            ->coupons()
            ->latest()
            ->paginate(15);

        return view('dashboard.coupons.index', [
            'coupons' => $coupons,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'discount_type' => ['required', 'in:'.Coupon::TYPE_PERCENT.','.Coupon::TYPE_FIXED],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $request->user()->coupons()->create([
            'code' => strtoupper(trim((string) $data['code'])),
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'max_discount' => $data['max_discount'] ?? null,
            'min_order_amount' => $data['min_order_amount'] ?? null,
            'usage_limit' => $data['usage_limit'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('status', 'coupon-created');
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        abort_unless($coupon->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $coupon->is_active = (bool) $data['is_active'];
        $coupon->save();

        return back()->with('status', 'coupon-updated');
    }
}
