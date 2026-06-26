<?php

namespace App\Models;

use App\Enums\OrganizationRole;
use App\Models\OrganizationInvitation;
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
        ];
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