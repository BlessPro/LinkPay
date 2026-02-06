<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SellerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'phone',
        'public_slug',
        'paystack_subaccount_code',
        'payout_method',
        'settlement_bank_code',
        'account_number',
        'account_name',
        'percent_charge',
    ];

    protected function casts(): array
    {
        return [
            'percent_charge' => 'decimal:2',
        ];
    }

    public static function generateUniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base !== '' ? $base : Str::random(8);

        $suffix = 1;
        $candidate = $slug;
        while (self::where('public_slug', $candidate)->exists()) {
            $suffix++;
            $candidate = $slug.'-'.$suffix;
        }

        return $candidate;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
