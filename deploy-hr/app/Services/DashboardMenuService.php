<?php

namespace App\Services;

use App\Enums\DashboardMenuAccessGroup;
use App\Enums\DashboardMenuItem;
use App\Models\User;
use App\Services\HR\TcpdDashboardAccessService;
use Illuminate\Support\Collection;

class DashboardMenuService
{
    public function __construct(private readonly TcpdDashboardAccessService $tcpdAccess)
    {
    }

    /**
     * Get Dashboard menu structure with access control
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(?int $roleId, string $userName, ?User $user = null): Collection
    {
        return collect([
            'kelola_data' => $this->getKelolaDataMenu($roleId, $userName),
            'dashboard' => $this->getDashboardMenu($roleId, $userName, $user),
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
    private function getDashboardMenu(?int $roleId, string $userName, ?User $user): array
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
            ->map(function ($item) use ($roleId, $userName, $user) {
                $menuItem = $item->toArray($roleId, $userName);

                if ($item === DashboardMenuItem::DASHBOARD_TCPD) {
                    $menuItem['visible'] = $this->tcpdAccess->canView($user);
                }

                return $menuItem;
            })
            ->filter(fn($item) => $item['visible'])
            ->values()
            ->all();

        // Dashboard menu is visible if user has main access OR has at least one visible item (including TCPD)
        return [
            'visible' => $hasMainAccess || !empty($items),
            'show_legacy_items' => $hasMainAccess,
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
