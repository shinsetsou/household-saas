<?php

namespace App\View\Components\Emails;

use Illuminate\View\Component;
use Illuminate\View\View;

class DataList extends Component
{
    public function __construct(public array $rows = []) {}

    public function render(): View
    {
        return view('components.emails.data-list');
    }
}
