<?php

namespace App\View\Components;

use Illuminate\View\Component;

class MainMenuDropdown extends Component
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
    public function render()
    {
        return view('components.main-menu-dropdown');
    }
}

