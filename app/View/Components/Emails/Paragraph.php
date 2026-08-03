<?php

namespace App\View\Components\Emails;

use Illuminate\View\Component;
use Illuminate\View\View;

class Paragraph extends Component
{
    public function __construct(public bool $muted = false) {}

    public function render(): View
    {
        return view('components.emails.paragraph');
    }
}
