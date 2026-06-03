<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'organization_id',
        'action',
        'auditable_type',
        'auditable_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public static function record(Organization $organization, string $action, ?User $user = null, ?Model $auditable = null, array $metadata = []): self
    {
        return self::create([
            'user_id' => $user?->id,
            'organization_id' => $organization->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'metadata' => $metadata,
        ]);
    }
}
