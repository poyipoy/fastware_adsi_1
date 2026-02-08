<?php

namespace App\View\Composers;

use App\Services\CrpMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class CrpMenuComposer
{
    protected CrpMenuService $crpMenuService;

    public function __construct(CrpMenuService $crpMenuService)
    {
        $this->crpMenuService = $crpMenuService;
    }

    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('crpMenu', collect([
                'visible' => false,
                'items' => [],
            ]));
            return;
        }

        $userName = Auth::user()->name ?? '';
        $crpMenu = $this->crpMenuService->getMenuStructure($userName);

        $view->with('crpMenu', $crpMenu);
    }
}

