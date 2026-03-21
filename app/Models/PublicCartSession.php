<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicCartSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_slug',
        'session_token',
        'cart_payload',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }
}

