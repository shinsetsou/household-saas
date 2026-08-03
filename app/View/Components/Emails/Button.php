<?php

namespace App\View\Components\Emails;

use Illuminate\View\Component;
use Illuminate\View\View;

class Button extends Component
{
    public function __construct(
        public string $href,
        public string $color = 'indigo',
        public bool $full = false,
    ) {}

    public function render(): View
    {
        return view('components.emails.button');
    }
}
