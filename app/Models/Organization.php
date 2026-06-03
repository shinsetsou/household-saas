<?php

// app/Models/Organization.php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['name', 'slug', 'monthly_due_amount'];

    protected function casts(): array
    {
        return [
            'monthly_due_amount' => 'decimal:2',
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

    public function households()
    {
        return $this->hasMany(Household::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
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
}
