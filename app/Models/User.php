<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const PLAN_FREE_TRIAL = 'FREE_TRIAL';
    public const PLAN_PROMOTION = 'PROMOTION';
    public const PLAN_PAYMENTS = 'PAYMENTS';
    public const PLAN_ENTERPRISE = 'ENTERPRISE';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'trial_started_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'plan_started_at' => 'datetime',
            'plan_ends_at' => 'datetime',
        ];
    }

    public function startTrialIfMissing(): void
    {
        if ($this->trial_started_at || $this->trial_ends_at || $this->plan_type) {
            return;
        }

        $days = (int) config('plans.trial_days', 7);
        $this->plan_type = self::PLAN_FREE_TRIAL;
        $this->trial_started_at = now();
        $this->trial_ends_at = now()->addDays($days);
        $this->save();
    }

    public function isOnTrial(): bool
    {
        if ($this->trial_ends_at === null) {
            return $this->plan_type === self::PLAN_FREE_TRIAL;
        }

        return now()->lt($this->trial_ends_at);
    }

    public function trialExpired(): bool
    {
        return $this->trial_ends_at !== null && now()->gte($this->trial_ends_at);
    }

    public function hasActivePlan(): bool
    {
        if (! in_array($this->plan_type, [self::PLAN_PROMOTION, self::PLAN_PAYMENTS], true)) {
            return false;
        }

        if ($this->plan_ends_at === null) {
            return true;
        }

        return now()->lt($this->plan_ends_at);
    }

    public function hasActiveAccess(): bool
    {
        return $this->isOnTrial() || $this->hasActivePlan();
    }

    public function canUsePaymentsFeature(): bool
    {
        return $this->isOnTrial() || ($this->hasActivePlan() && $this->plan_type === self::PLAN_PAYMENTS);
    }

    public function canUsePromotionFeatures(): bool
    {
        return $this->isOnTrial() || $this->hasActivePlan();
    }

    public function sellerProfile()
    {
        return $this->hasOne(SellerProfile::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function sellerNotifications()
    {
        return $this->hasMany(SellerNotification::class);
    }

    public function analyticsEvents()
    {
        return $this->hasMany(AnalyticsEvent::class);
    }
}
