<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerNotification extends Model
{
    use HasFactory;

    public const TYPE_PAYSTACK_CONNECTED = 'PAYSTACK_CONNECTED';
    public const TYPE_INVOICE_CREATED = 'INVOICE_CREATED';
    public const TYPE_PAYMENT_RECEIVED = 'PAYMENT_RECEIVED';
    public const TYPE_INVOICE_PARTIAL = 'INVOICE_PARTIALLY_PAID';
    public const TYPE_INVOICE_PAID = 'INVOICE_FULLY_PAID';
    public const TYPE_LEAD_CAPTURED = 'LEAD_CAPTURED';
    public const TYPE_ADMIN_MESSAGE = 'ADMIN_MESSAGE';
    public const TYPE_ORDER_ACCEPTED = 'ORDER_ACCEPTED';
    public const TYPE_ORDER_REJECTED = 'ORDER_REJECTED';
    public const TYPE_STOCK_ALERT = 'STOCK_ALERT';

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
