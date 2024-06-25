<?php
// app/Models/Organization.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = ['name', 'slug'];

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'name'
            ]
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

    public function totalContributions()
    {
        return $this->households->flatMap->contributions->sum('amount');
    }

    public function yearlyContributions($year)
    {
        return $this->households->flatMap->contributions->where('created_at', '>=', "{$year}-01-01")
                                                      ->where('created_at', '<=', "{$year}-12-31")
                                                      ->sum('amount');
    }
}
