<?php

namespace App\View\Composers;

use App\Services\HRMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class HRMenuComposer
{
    protected HRMenuService $hrMenuService;

    /**
     * Create a new menu composer.
     *
     * @param HRMenuService $hrMenuService
     */
    public function __construct(HRMenuService $hrMenuService)
    {
        $this->hrMenuService = $hrMenuService;
    }

    /**
     * Bind data to the view.
     *
     * @param View $view
     * @return void
     */
    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('hrMenu', collect([
                'show_main_menu' => false,
                'knowledge_management' => ['items' => []],
                'base_competency' => ['items' => []],
                'training_development' => ['items' => []],
            ]));
            return;
        }

        $userName = Auth::user()->name;
        $hrMenu = $this->hrMenuService->getMenuStructure($userName);

        $view->with('hrMenu', $hrMenu);
    }
}

