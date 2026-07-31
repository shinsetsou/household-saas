<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OrganizationInvitation extends Model
{
    use HasFactory;

    public const INVITATION_TTL_DAYS = 14;

    protected $fillable = [
        'organization_id', 'email', 'role', 'token', 'verification_code',
        'invited_by', 'accepted_at', 'revoked_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invitation) {
            if (! $invitation->token) {
                $invitation->token = Str::random(48);
            }

            if (! $invitation->verification_code) {
                $invitation->verification_code = self::generateVerificationCode();
            }

            if (! $invitation->expires_at) {
                $invitation->expires_at = Carbon::now()->addDays(self::INVITATION_TTL_DAYS);
            }
        });
    }

    public static function generateVerificationCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);
        } while (static::where('verification_code', $code)
            ->where('expires_at', '>', now())
            ->exists());

        return $code;
    }

    public function matchesVerificationCode(string $candidate): bool
    {
        return $this->verification_code !== null
            && hash_equals($this->verification_code, $candidate);
    }

    public function getRouteKeyName(): string
    {
        return 'token';
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function role(): OrganizationRole
    {
        return OrganizationRole::from($this->role);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() && $this->accepted_at === null;
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '>', Carbon::now());
    }
}
