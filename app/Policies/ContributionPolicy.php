<?php

namespace App\Policies;

use App\Models\Contribution;
use App\Models\User;

class ContributionPolicy
{
    public function manage(User $user, Contribution $contribution): bool
    {
        return $user->belongsToOrganization($contribution->household->organization);
    }
}
