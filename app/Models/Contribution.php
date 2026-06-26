<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'household_id',
        'amount',
        'photo',
        'status',
        'verification_status',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'reference_number',
        'created_at',
        'updated_at',
    ];

    protected $guarded = ['admin_verified'];

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

    /**
     * Public URL for the receipt photo on the configured S3/B2 disk.
     * Returns null when no photo is attached.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? Storage::disk('s3')->url($this->photo) : null;
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
