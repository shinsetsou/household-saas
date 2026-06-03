<?php

namespace App\Policies;

use App\Models\Household;
use App\Models\User;

class HouseholdPolicy
{
    public function view(User $user, Household $household): bool
    {
        return $user->belongsToOrganization($household->organization);
    }

    public function manage(User $user, Household $household): bool
    {
        return $this->view($user, $household);
    }
}
