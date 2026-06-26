<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Organization extends Model
{
    use HasFactory, Sluggable;

    public const SUBSCRIPTION_TRIALING = 'trialing';
    public const SUBSCRIPTION_ACTIVE = 'active';
    public const SUBSCRIPTION_PAST_DUE = 'past_due';
    public const SUBSCRIPTION_CANCELED = 'canceled';
    public const SUBSCRIPTION_UNPAID = 'unpaid';

    /**
     * Maximum households a trialing organization may add during its free trial.
     * Past the trial the limit falls back to the plan's max_households.
     */
    public const TRIAL_HOUSEHOLD_CAP = 20;

    protected $fillable = [
        'name', 'slug', 'monthly_due_amount',
        'subscription_status', 'trial_ends_at', 'subscription_ends_at',
        'plan_id', 'paymongo_customer_id', 'trial_warning_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'monthly_due_amount' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'trial_warning_sent_at' => 'array',
        ];
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name',
            ],
        ];
    }

    public function households(): HasMany
    {
        return $this->hasMany(Household::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(OrganizationInvitation::class);
    }

    public function pendingInvitations()
    {
        return $this->invitations()->pending()->latest();
    }

    public function roleFor(?User $user): ?OrganizationRole
    {
        if (! $user) {
            return null;
        }

        $role = $this->users()
            ->where('users.id', $user->id)
            ->value('organization_user.role');

        return $role ? OrganizationRole::tryFrom($role) : null;
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function totalContributions()
    {
        return Contribution::forOrganization($this)->sum('amount');
    }

    public function yearlyContributions($year)
    {
        return Contribution::forOrganization($this)
            ->whereYear('created_at', $year)
            ->sum('amount');
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === self::SUBSCRIPTION_TRIALING
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function hasActiveSubscription(): bool
    {
        // An org is "active" while the subscription is paid OR while it's
        // trialing OR while the user has scheduled cancellation at period end
        // (still has access until the period actually closes).
        return in_array($this->subscription_status, [
            self::SUBSCRIPTION_TRIALING,
            self::SUBSCRIPTION_ACTIVE,
        ], true);
    }

    public function isCancelingAtPeriodEnd(): bool
    {
        return (bool) $this->subscription?->isCancelAtPeriodEnd();
    }

    public function currentPeriodEndsAt(): ?\Carbon\Carbon
    {
        return $this->subscription?->canceled_at ?? $this->subscription?->current_period_end;
    }

    public function householdLimit(): ?int
    {
        // Trialing orgs get a tighter cap than the plan's max_households so the
        // upgrade path is visible before the user has committed. After the trial
        // ends (status flips to active/past_due/etc.) we fall back to the plan.
        if ($this->isOnTrial()) {
            return self::TRIAL_HOUSEHOLD_CAP;
        }

        return $this->plan?->max_households;
    }

    public function isOnTrialLimit(): bool
    {
        $limit = $this->householdLimit();

        return $limit !== null && $this->households()->count() >= $limit;
    }

    public function hasReachedHouseholdLimit(): bool
    {
        return $this->isOnTrialLimit();
    }

    public function isExpired(): bool
    {
        return in_array($this->subscription_status, [
            self::SUBSCRIPTION_PAST_DUE,
            self::SUBSCRIPTION_CANCELED,
            self::SUBSCRIPTION_UNPAID,
        ], true);
    }

    public function daysUntilTrialEnds(): ?int
    {
        if (! $this->trial_ends_at || $this->subscription_status !== self::SUBSCRIPTION_TRIALING) {
            return null;
        }

        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    public function hasWarningBeenSent(int $daysBefore): bool
    {
        $sent = $this->trial_warning_sent_at ?? [];

        return in_array($daysBefore, $sent, true);
    }

    public function markWarningSent(int $daysBefore): void
    {
        $sent = $this->trial_warning_sent_at ?? [];

        if (! in_array($daysBefore, $sent, true)) {
            $sent[] = $daysBefore;
            $this->trial_warning_sent_at = $sent;
            $this->save();
        }
    }
}