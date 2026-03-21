<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    use HasFactory;

    public const TYPE_LISTING_VIEW = 'listing_view';
    public const TYPE_PRODUCT_IMPRESSION = 'product_impression';
    public const TYPE_PRODUCT_CLICK = 'product_click';
    public const TYPE_ADD_TO_CART = 'add_to_cart';
    public const TYPE_CHECKOUT_STARTED = 'checkout_started';
    public const TYPE_INVOICE_VIEW = 'invoice_view';
    public const TYPE_INVOICE_CLICK = 'invoice_click';

    protected $fillable = [
        'user_id',
        'event_type',
        'entity_type',
        'entity_id',
        'session_hash',
        'ip_hash',
        'user_agent_hash',
        'device_type',
        'referrer_host',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
