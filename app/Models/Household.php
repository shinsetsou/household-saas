<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Household extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'block', 'lot', 'organization_id'];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function scopeForOrganization($query, Organization $organization)
    {
        return $query->where('organization_id', $organization->id);
    }
}
