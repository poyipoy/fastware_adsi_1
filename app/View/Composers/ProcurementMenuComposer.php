<?php

namespace App\View\Composers;

use App\Services\ProcurementMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ProcurementMenuComposer
{
    protected ProcurementMenuService $procurementMenuService;

    public function __construct(ProcurementMenuService $procurementMenuService)
    {
        $this->procurementMenuService = $procurementMenuService;
    }

    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('procurementMenu', collect([
                'visible' => false,
                'items' => [],
            ]));
            return;
        }

        $procurementMenu = $this->procurementMenuService->getMenuStructure(Auth::user());

        $view->with('procurementMenu', $procurementMenu);
    }
}
