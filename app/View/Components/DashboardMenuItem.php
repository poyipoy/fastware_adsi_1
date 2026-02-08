<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DashboardMenuItem extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $label,
        public string $route,
        public bool $visible = true,
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.dashboard-menu-item');
    }
}

