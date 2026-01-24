<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Money;

class Invoice extends Model
{
    use HasFactory;
    use HasUuids;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PARTIAL = 'PARTIAL';
    public const STATUS_PAID = 'PAID';

    public const MODE_FULL = 'FULL';
    public const MODE_PARTIAL = 'PARTIAL';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'token',
        'title',
        'description',
        'image_path',
        'total_amount',
        'paid_total',
        'payment_mode',
        'deposit_amount',
        'status',
        'customer_name',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_total' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function amountDue(): string
    {
        $total = (string) $this->total_amount;
        $paid = (string) $this->paid_total;

        if ($this->status === self::STATUS_PAID) {
            return '0.00';
        }

        if ($this->payment_mode === self::MODE_PARTIAL && Money::compare($paid, '0.00') === 0) {
            return (string) $this->deposit_amount;
        }

        return Money::compare($total, $paid) === 1 ? Money::subtract($total, $paid) : '0.00';
    }

    public function balanceRemaining(): string
    {
        $total = (string) $this->total_amount;
        $paid = (string) $this->paid_total;

        return Money::compare($total, $paid) === 1 ? Money::subtract($total, $paid) : '0.00';
    }

    public function refreshPaymentStatus(): void
    {
        $paidTotal = (string) $this->payments()
            ->where('status', Payment::STATUS_SUCCESS)
            ->sum('amount');

        $this->paid_total = $paidTotal;

        $remaining = Money::compare((string) $this->total_amount, $paidTotal) === 1
            ? Money::subtract((string) $this->total_amount, $paidTotal)
            : '0.00';

        if (Money::compare($paidTotal, '0.00') <= 0) {
            $this->status = self::STATUS_PENDING;
        } elseif (Money::compare($remaining, '0.00') === 1) {
            $this->status = self::STATUS_PARTIAL;
        } else {
            $this->status = self::STATUS_PAID;
        }

        $this->save();
    }
}
