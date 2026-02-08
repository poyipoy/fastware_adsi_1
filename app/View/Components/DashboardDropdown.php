<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DashboardDropdown extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $label,
        public string $dropdownId,
        public bool $visible = true,
        public array $items = [],
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard-dropdown');
    }
}

