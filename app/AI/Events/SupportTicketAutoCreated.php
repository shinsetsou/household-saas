<?php

declare(strict_types=1);

namespace App\AI\Events;

use App\Models\SupportTicket;
use Illuminate\Foundation\Events\Dispatchable;

class SupportTicketAutoCreated
{
    use Dispatchable;

    public function __construct(public readonly SupportTicket $ticket) {}
}
