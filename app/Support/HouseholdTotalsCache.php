<?php

namespace App\Support;

use App\Models\Household;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

class HouseholdTotalsCache
{
    public static function invalidate(Organization $organization, Household $household): void
    {
        Cache::forget("organization:{$organization->id}:expenses_sum");
        Cache::forget("household:{$household->id}:contributions_sum");
    }
}