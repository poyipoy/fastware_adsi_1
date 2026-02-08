<?php

namespace App\View\Composers;

use App\Services\SalesMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SalesMenuComposer
{
    protected SalesMenuService $salesMenuService;

    public function __construct(SalesMenuService $salesMenuService)
    {
        $this->salesMenuService = $salesMenuService;
    }

    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('salesMenu', collect([
                'visible' => false,
                'items' => [],
            ]));
            return;
        }

        $userName = Auth::user()->name ?? '';
        $salesMenu = $this->salesMenuService->getMenuStructure($userName);

        $view->with('salesMenu', $salesMenu);
    }
}

