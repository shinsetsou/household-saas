<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'amount',
        'photo',
        'status',
        'admin_verified',
        'verification_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'reference_number',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function household()
    {
        return $this->belongsTo(Household::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeForOrganization($query, Organization $organization)
    {
        return $query->whereHas('household', function ($householdQuery) use ($organization) {
            $householdQuery->where('organization_id', $organization->id);
        });
    }
}
