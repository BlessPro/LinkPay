<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwilioMessageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_id',
        'channel',
        'direction',
        'to',
        'from',
        'status',
        'provider_sid',
        'error_code',
        'error_message',
        'context_type',
        'context_id',
        'payload',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}

