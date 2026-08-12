<?php

namespace App\View\Composers;

use App\Services\DashboardMenuService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardMenuComposer
{
    protected DashboardMenuService $dashboardMenuService;

    /**
     * Create a new menu composer.
     *
     * @param DashboardMenuService $dashboardMenuService
     */
    public function __construct(DashboardMenuService $dashboardMenuService)
    {
        $this->dashboardMenuService = $dashboardMenuService;
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
            $view->with('dashboardMenu', collect([
                'kelola_data' => ['visible' => false, 'items' => []],
                'dashboard' => ['visible' => false, 'items' => []],
            ]));
            return;
        }

        $user = Auth::user();
        $roleId = $user->role_id ?? null;
        $userName = $user->name ?? '';

        $dashboardMenu = $this->dashboardMenuService->getMenuStructure($roleId, $userName, $user);

        $view->with('dashboardMenu', $dashboardMenu);
    }
}
