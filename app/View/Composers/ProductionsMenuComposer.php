<?php

namespace App\View\Composers;

use App\Services\ProductionsMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class ProductionsMenuComposer
{
    protected ProductionsMenuService $productionsMenuService;

    public function __construct(ProductionsMenuService $productionsMenuService)
    {
        $this->productionsMenuService = $productionsMenuService;
    }

    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('productionsMenu', collect([
                'visible' => false,
                'items' => [],
            ]));
            return;
        }

        $userName = Auth::user()->name ?? '';
        $productionsMenu = $this->productionsMenuService->getMenuStructure($userName);

        $view->with('productionsMenu', $productionsMenu);
    }
}

