<?php

namespace App\View\Components\Emails;

use Illuminate\View\Component;
use Illuminate\View\View;

class Layout extends Component
{
    public function __construct(
        public ?string $preview = null,
        public ?string $title = null,
        public string $accent = 'indigo',
    ) {}

    public function render(): View
    {
        return view('components.emails.layout');
    }
}
