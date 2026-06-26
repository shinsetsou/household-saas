<?php

namespace App\Support;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;

/**
 * Per-request role context for the main tenant layout.
 *
 * Computed once per request (in AppServiceProvider's View Composer for
 * `layouts.layout`) and shared to the template. Avoids ad-hoc pivot
 * queries inside Blade, keeps the layout declarative, and makes role
 * checks unit-testable without rendering.
 */
class NavContext
{
    public function __construct(
        public ?Organization $organization,
        public ?User $user,
        public ?OrganizationRole $role,
        public bool $isAdmin,
        public bool $canVerify,
        public string $userInitials,
    ) {
    }

    public static function build(?Organization $organization, ?User $user): self
    {
        $role = $organization && $user ? $organization->roleFor($user) : null;
        $initials = $user
            ? collect(explode(' ', trim($user->name)))
                ->map(fn ($w) => mb_substr($w, 0, 1))
                ->take(2)
                ->join('')
            : '';

        return new self(
            organization: $organization,
            user: $user,
            role: $role,
            isAdmin: $role === OrganizationRole::Admin,
            canVerify: $role !== null && in_array($role, [OrganizationRole::Admin, OrganizationRole::Treasurer], true),
            userInitials: $initials ?: '?',
        );
    }
}