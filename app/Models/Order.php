<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_PENDING_PAYMENT = 'PENDING_PAYMENT';
    public const STATUS_PAID = 'PAID';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_CANNOT_FULFILL = 'CANNOT_FULFILL';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'payment_status',
        'customer_name',
        'customer_phone',
        'customer_location',
        'delivery_required',
        'delivery_note',
        'subtotal',
        'coupon_code',
        'discount_amount',
        'total',
        'currency',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'delivery_required' => 'boolean',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
