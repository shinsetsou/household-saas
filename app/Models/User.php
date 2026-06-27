<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'force_change_password',
        'invitation_origin_org_slug',
        'invitation_origin_role',
        'invitation_origin_inviter_email',
        'invitation_origin_revoked_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'force_change_password' => 'boolean',
            'invitation_origin_revoked_at' => 'datetime',
        ];
    }

    public function needsPasswordChange(): bool
    {
        return (bool) $this->force_change_password;
    }

    public function wasInvited(): bool
    {
        return ! is_null($this->invitation_origin_org_slug);
    }

    public function isInviteLocked(): bool
    {
        return ! is_null($this->invitation_origin_revoked_at);
    }

    public function lockInviteOrigin(): void
    {
        if (is_null($this->invitation_origin_revoked_at)) {
            $this->forceFill(['invitation_origin_revoked_at' => now()])->save();
        }
    }

    public function clearInviteOriginLock(): void
    {
        if (! is_null($this->invitation_origin_revoked_at)) {
            $this->forceFill(['invitation_origin_revoked_at' => null])->save();
        }
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function belongsToOrganization(Organization $organization): bool
    {
        return $this->organizations()->whereKey($organization->id)->exists();
    }

    public function roleInOrganization(Organization $organization): ?OrganizationRole
    {
        return $organization->roleFor($this);
    }

    public function hasRoleInOrganization(Organization $organization, OrganizationRole $role): bool
    {
        return $this->roleInOrganization($organization) === $role;
    }

    public function isAdminOf(Organization $organization): bool
    {
        return $this->hasRoleInOrganization($organization, OrganizationRole::Admin);
    }

    public function pendingInvitations()
    {
        return OrganizationInvitation::where('email', $this->email)->pending()->get();
    }
}
