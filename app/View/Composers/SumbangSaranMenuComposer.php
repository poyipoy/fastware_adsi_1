<?php

namespace App\View\Composers;

use App\Services\SumbangSaranMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SumbangSaranMenuComposer
{
    protected SumbangSaranMenuService $sumbangSaranMenuService;

    public function __construct(SumbangSaranMenuService $sumbangSaranMenuService)
    {
        $this->sumbangSaranMenuService = $sumbangSaranMenuService;
    }

    public function compose(View $view): void
    {
        if (!Auth::check()) {
            $view->with('sumbangSaranMenu', collect([
                'visible' => false,
                'items' => [],
            ]));
            return;
        }

        $userName = Auth::user()->name ?? '';
        $sumbangSaranMenu = $this->sumbangSaranMenuService->getMenuStructure($userName);

        $view->with('sumbangSaranMenu', $sumbangSaranMenu);
    }
}

