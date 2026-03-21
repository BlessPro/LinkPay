<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    public const TYPE_PERCENT = 'PERCENT';
    public const TYPE_FIXED = 'FIXED';

    protected $fillable = [
        'user_id',
        'code',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_amount',
        'usage_limit',
        'used_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'decimal:2',
            'max_discount' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function isUsableNow(string $subtotal): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && now()->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($this->min_order_amount !== null && Money::compare($subtotal, (string) $this->min_order_amount) === -1) {
            return false;
        }

        return true;
    }

    public function computeDiscount(string $subtotal): string
    {
        if ($this->discount_type === self::TYPE_FIXED) {
            $discount = (string) $this->discount_value;
        } else {
            $percent = max(0.0, min(100.0, (float) $this->discount_value));
            $discount = Money::percent($subtotal, number_format($percent / 100, 4, '.', ''));
        }

        if ($this->max_discount !== null && Money::compare($discount, (string) $this->max_discount) === 1) {
            $discount = (string) $this->max_discount;
        }

        if (Money::compare($discount, $subtotal) === 1) {
            return $subtotal;
        }

        return $discount;
    }

    public static function customerFingerprint(string $phone): string
    {
        return hash('sha256', trim(strtolower($phone)));
    }
}
