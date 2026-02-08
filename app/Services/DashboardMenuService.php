<?php

namespace App\Services;

use App\Enums\DashboardMenuAccessGroup;
use App\Enums\DashboardMenuItem;
use Illuminate\Support\Collection;

class DashboardMenuService
{
    /**
     * Get Dashboard menu structure with access control
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(?int $roleId, string $userName): Collection
    {
        return collect([
            'kelola_data' => $this->getKelolaDataMenu($roleId, $userName),
            'dashboard' => $this->getDashboardMenu($roleId, $userName),
        ]);
    }

    /**
     * Get Kelola Data menu
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return array
     */
    private function getKelolaDataMenu(?int $roleId, string $userName): array
    {
        $hasAccess = DashboardMenuAccessGroup::KELOLA_DATA->hasAccess($roleId, $userName);

        return [
            'visible' => $hasAccess,
            'items' => [
                DashboardMenuItem::KELOLA_AKUN->toArray($roleId, $userName),
                DashboardMenuItem::KELOLA_CUSTOMER->toArray($roleId, $userName),
            ],
        ];
    }

    /**
     * Get Dashboard menu
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return array
     */
    private function getDashboardMenu(?int $roleId, string $userName): array
    {
        $hasMainAccess = DashboardMenuAccessGroup::DASHBOARD_MAIN->hasAccess($roleId, $userName);

        $items = collect([
            DashboardMenuItem::MAINTENANCE,
            DashboardMenuItem::HANDLING_KLAIM,
            DashboardMenuItem::PEOPLE_DEVELOPMENT,
            DashboardMenuItem::SUMBANG_SARAN,
            DashboardMenuItem::KNOWLEDGE_MANAGEMENT,
            DashboardMenuItem::PENGAJUAN_BARANG,
            DashboardMenuItem::DASHBOARD_TCPD,
            DashboardMenuItem::DASHBOARD_BOPM,
        ])
            ->map(fn($item) => $item->toArray($roleId, $userName))
            ->filter(fn($item) => $item['visible'])
            ->values()
            ->all();

        // Dashboard menu is visible if user has main access OR has at least one visible item (including TCPD)
        return [
            'visible' => $hasMainAccess || !empty($items),
            'items' => $items,
        ];
    }

    /**
     * Get visible menu items from a menu structure
     * 
     * @param array $menuItems
     * @return Collection
     */
    public function getVisibleItems(array $menuItems): Collection
    {
        return collect($menuItems)
            ->filter(fn($item) => $item['visible'] ?? false);
    }

    /**
     * Check if user has access to any Dashboard menu
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return bool
     */
    public function hasAnyAccess(?int $roleId, string $userName): bool
    {
        $menuStructure = $this->getMenuStructure($roleId, $userName);
        
        return $menuStructure->get('kelola_data')['visible'] 
            || $menuStructure->get('dashboard')['visible'];
    }
}

