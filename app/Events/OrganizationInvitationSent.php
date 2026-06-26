<?php

namespace App\Events;

use App\Models\OrganizationInvitation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrganizationInvitationSent
{
    use Dispatchable, SerializesModels;

    public function __construct(public OrganizationInvitation $invitation)
    {
    }
}