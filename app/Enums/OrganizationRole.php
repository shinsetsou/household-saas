<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Admin = 'admin';
    case Treasurer = 'treasurer';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Treasurer => 'Treasurer',
            self::Viewer => 'Viewer',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Full access including billing and member management.',
            self::Treasurer => 'Verify contributions, record expenses, and run reports.',
            self::Viewer => 'Read-only access to dashboard and reports.',
        };
    }

    public function canManageBilling(): bool
    {
        return $this === self::Admin;
    }

    public function canVerifyContributions(): bool
    {
        return in_array($this, [self::Admin, self::Treasurer], true);
    }

    public function canRecordExpenses(): bool
    {
        return in_array($this, [self::Admin, self::Treasurer], true);
    }
}