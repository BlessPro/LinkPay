<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_SUCCESS = 'SUCCESS';
    public const STATUS_FAILED = 'FAILED';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'invoice_id',
        'product_id',
        'reference',
        'amount',
        'status',
        'channel',
        'paid_at',
        'verified_at',
        'raw_payload',
        'commission_amount',
        'transaction_fee',
        'tax_amount',
        'receiving_account',
        'transaction_code',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'raw_payload' => 'array',
            'commission_amount' => 'decimal:2',
            'transaction_fee' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'transaction_id' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function webhookEvents()
    {
        return $this->hasMany(WebhookEvent::class);
    }

    public function twilioLogs()
    {
        return $this->hasMany(TwilioMessageLog::class);
    }
}
