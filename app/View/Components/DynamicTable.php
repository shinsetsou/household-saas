<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class DynamicTable extends Component
{
    public $columns;

    public $items;

    public $itemsPerPage;

    /**
     * Create a new component instance.
     */
    public function __construct($columns, $items, $itemsPerPage)
    {
        //
        $this->columns = $columns;
        $this->items = $items;
        $this->itemsPerPage = $itemsPerPage;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.dynamic-table');
    }
}
