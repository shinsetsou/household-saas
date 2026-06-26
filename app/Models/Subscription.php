<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_TRIALING = 'trialing';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_CANCELED = 'canceled';
    public const STATUS_UNPAID = 'unpaid';

    protected $fillable = [
        'organization_id', 'plan_id', 'paymongo_subscription_id', 'status',
        'current_period_start', 'current_period_end', 'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'current_period_start' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIALING], true);
    }

    /**
     * True when the user has scheduled this subscription for cancellation at the
     * end of the current billing period but it has not been finalized yet.
     */
    public function isCancelAtPeriodEnd(): bool
    {
        return $this->canceled_at !== null
            && $this->status === self::STATUS_ACTIVE
            && $this->canceled_at->isFuture();
    }

    public function isFullyCanceled(): bool
    {
        return $this->status === self::STATUS_CANCELED;
    }
}