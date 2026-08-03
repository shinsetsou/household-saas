<?php

namespace App\View\Components\Emails;

use Illuminate\View\Component;
use Illuminate\View\View;

class Heading extends Component
{
    public function __construct(
        public ?string $kicker = null,
        public string $accent = 'indigo',
    ) {}

    public function render(): View
    {
        return view('components.emails.heading');
    }
}
