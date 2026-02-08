<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Collection;

class MenuDropdownSubmenu extends Component
{
    public string $title;
    public Collection $items;
    public string $childDropdownId;

    /**
     * Create a new component instance.
     *
     * @param string $title
     * @param array $items
     * @param string|null $childDropdownId
     */
    public function __construct(string $title, array $items, ?string $childDropdownId = null)
    {
        $this->title = $title;
        $this->items = collect($items)->filter(fn($item) => $item['visible'] ?? false);
        $this->childDropdownId = $childDropdownId ?? 'childDropdown' . uniqid();
    }

    /**
     * Check if submenu has visible items
     * 
     * @return bool
     */
    public function hasVisibleItems(): bool
    {
        return $this->items->isNotEmpty();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.menu-dropdown-submenu');
    }
}

