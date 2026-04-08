<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const STATUS_IN_STOCK = 'in_stock';
    public const STATUS_SOLD_OUT = 'sold_out';
    public const STATUS_UNAVAILABLE = 'unavailable';
    public const STATUS_TRASHED = 'trashed';
    public const STATUS_LOW_STOCK = 'low_stock';
    public const STATUS_PRE_ORDER = 'pre_order';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'price',
        'stock_quantity',
        'low_stock_threshold',
        'image_path',
        'is_active',
        'status',
        'stock_alert_state',
        'stock_alerted_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_active' => 'boolean',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'stock_alerted_at' => 'datetime',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_IN_STOCK => 'In stock',
            self::STATUS_LOW_STOCK => 'Low stock',
            self::STATUS_PRE_ORDER => 'Pre-order',
            self::STATUS_SOLD_OUT => 'Sold out',
            self::STATUS_UNAVAILABLE => 'Unavailable',
        ];
    }

    public function statusLabel(): string
    {
        $status = $this->status ?: self::STATUS_IN_STOCK;

        if ($status === self::STATUS_TRASHED) {
            return 'Trashed';
        }

        return self::statusOptions()[$status] ?? 'In stock';
    }

    public function statusBadgeClass(): string
    {
        $status = $this->status ?: self::STATUS_IN_STOCK;

        $styles = [
            self::STATUS_IN_STOCK => 'bg-emerald-50 text-emerald-700',
            self::STATUS_LOW_STOCK => 'bg-amber-50 text-amber-700',
            self::STATUS_PRE_ORDER => 'bg-indigo-50 text-indigo-700',
            self::STATUS_SOLD_OUT => 'bg-rose-50 text-rose-700',
            self::STATUS_UNAVAILABLE => 'bg-slate-100 text-slate-600',
            self::STATUS_TRASHED => 'bg-slate-200 text-slate-700',
        ];

        return $styles[$status] ?? 'bg-emerald-50 text-emerald-700';
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [self::STATUS_IN_STOCK, self::STATUS_LOW_STOCK, self::STATUS_PRE_ORDER], true);
    }

    public function isInventoryManaged(): bool
    {
        return ! in_array($this->status, [self::STATUS_PRE_ORDER, self::STATUS_UNAVAILABLE], true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
