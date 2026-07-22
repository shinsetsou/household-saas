<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'description', 'price_cents', 'currency',
        'interval', 'max_households', 'csv_import', 'api_access',
        'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'max_households' => 'integer',
            'csv_import' => 'boolean',
            'api_access' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function formattedPrice(): string
    {
        return '&#8369;'.number_format($this->price_cents / 100, 2);
    }

    public function hasUnlimitedHouseholds(): bool
    {
        return $this->max_households === null;
    }
}
