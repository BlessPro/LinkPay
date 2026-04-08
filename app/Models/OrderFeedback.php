<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFeedback extends Model
{
    use HasFactory;
    use HasUuids;

    public const TYPE_RECEIVED = 'RECEIVED';
    public const TYPE_REPORTED = 'REPORTED';

    public const ADMIN_PENDING = 'PENDING';
    public const ADMIN_REFUND_APPROVED = 'REFUND_APPROVED';
    public const ADMIN_IGNORED = 'IGNORED';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'order_id',
        'order_feedback_token_id',
        'type',
        'rating',
        'note',
        'issue_note',
        'issue_photo_path',
        'admin_status',
        'admin_note',
        'reviewed_by_admin_id',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function token()
    {
        return $this->belongsTo(OrderFeedbackToken::class, 'order_feedback_token_id');
    }

    public function reviewedByAdmin()
    {
        return $this->belongsTo(User::class, 'reviewed_by_admin_id');
    }
}

