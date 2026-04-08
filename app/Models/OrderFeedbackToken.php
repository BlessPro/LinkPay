<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFeedbackToken extends Model
{
    use HasFactory;
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'token',
        'phone',
        'expires_at',
        'used_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

