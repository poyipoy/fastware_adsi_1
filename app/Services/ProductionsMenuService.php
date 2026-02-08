<?php

namespace App\Services;

use App\Enums\ProductionsMenuAccessGroup;
use Illuminate\Support\Collection;

class ProductionsMenuService
{
    /**
     * Get Productions menu structure with access control
     * 
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(string $userName): Collection
    {
        $directItems = [
            [
                'label' => 'Form Permintaan Perbaikan',
                'route' => 'fpps.index',
                'visible' => ProductionsMenuAccessGroup::FPP_INDEX->hasAccess($userName),
            ],
            [
                'label' => 'Riwayat Permintaan Perbaikan',
                'route' => 'fpps.history',
                'visible' => ProductionsMenuAccessGroup::FPP_HISTORY->hasAccess($userName),
            ],
        ];

        $submenus = [
            $this->getBagianMaintenanceMenu($userName),
            $this->getBagianEngineeringMenu($userName),
            $this->getMaintenanceKorektifMenu($userName),
            $this->getMaintenancePreventifMenu($userName),
        ];

        $items = collect($directItems)
            ->merge(collect($submenus)->filter(fn($item) => $item['visible'] ?? false))
            ->values()
            ->all();

        return collect([
            'visible' => !empty($items),
            'items' => $items,
        ]);
    }

    /**
     * Get Bagian Maintenance submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getBagianMaintenanceMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Kelola DMI',
                'route' => 'dashboardmesins',
                'visible' => ProductionsMenuAccessGroup::MAINTENANCE_DMI->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan FPP',
                'route' => 'deptmtce.index',
                'visible' => ProductionsMenuAccessGroup::MAINTENANCE_PERSETUJUAN_FPP->hasAccess($userName),
            ],
            [
                'label' => 'Riwayat Form Perbaikan',
                'route' => 'fpps.history',
                'visible' => ProductionsMenuAccessGroup::MAINTENANCE_FPP_HISTORY->hasAccess($userName),
            ],
            [
                'label' => 'Tabel Preventif',
                'route' => 'dashboardPreventive',
                'visible' => ProductionsMenuAccessGroup::MAINTENANCE_TABEL_PREVENTIF->hasAccess($userName),
            ],
        ];

        $visible = collect($items)->contains(fn($item) => $item['visible']);

        return [
            'title' => 'Bagian Maintenance',
            'visible' => $visible,
            'items' => $items,
        ];
    }

    /**
     * Get Bagian Engineering submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getBagianEngineeringMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Tindak Lanjut',
                'route' => 'submission',
                'visible' => ProductionsMenuAccessGroup::ENGINEERING_FORM_TINDAK_LANJUT->hasAccess($userName),
            ],
            [
                'label' => 'Riwayat Klaim & Komplain',
                'route' => 'showHistoryCLaimComplain',
                'visible' => ProductionsMenuAccessGroup::ENGINEERING_RIWAYAT_KLAIM->hasAccess($userName),
            ],
            [
                'label' => 'Jadwal Kunjungan',
                'route' => 'scheduleVisit',
                'visible' => ProductionsMenuAccessGroup::ENGINEERING_JADWAL_KUNJUNGAN->hasAccess($userName),
            ],
        ];

        $visible = collect($items)->contains(fn($item) => $item['visible']);

        return [
            'title' => 'Bagian Engineering',
            'visible' => $visible,
            'items' => $items,
        ];
    }

    /**
     * Get Maintenance Korektif submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getMaintenanceKorektifMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Terima Form Perbaikan',
                'route' => 'dashboardMaintenance',
                'visible' => ProductionsMenuAccessGroup::KOREKTIF_TERIMA_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Riwayat Form Perbaikan',
                'route' => 'fpps.history',
                'visible' => ProductionsMenuAccessGroup::KOREKTIF_RIWAYAT_FORM->hasAccess($userName),
            ],
        ];

        $visible = collect($items)->contains(fn($item) => $item['visible']);

        return [
            'title' => 'Maintenance Korektif',
            'visible' => $visible,
            'items' => $items,
        ];
    }

    /**
     * Get Maintenance Preventif submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getMaintenancePreventifMenu(string $userName): array
    {
        $hasAccess = ProductionsMenuAccessGroup::PREVENTIF_JADWAL->hasAccess($userName);

        return [
            'title' => 'Maintenance Preventif',
            'visible' => $hasAccess,
            'items' => [
                [
                    'label' => 'Jadwal Preventif',
                    'route' => 'dashboardPreventiveMaintenance',
                    'visible' => $hasAccess,
                ],
            ],
        ];
    }

    /**
     * Check if user has access to any Productions menu
     * 
     * @param string $userName
     * @return bool
     */
    public function hasAnyAccess(string $userName): bool
    {
        $menuStructure = $this->getMenuStructure($userName);
        
        // Check direct items
        $directItems = collect($menuStructure['items'])
            ->filter(fn($item) => is_array($item) && isset($item['visible']) && $item['visible'])
            ->isNotEmpty();

        // Check submenu items
        $submenuItems = collect($menuStructure['items'])
            ->filter(fn($item) => is_array($item) && isset($item['title']))
            ->contains(fn($item) => $item['visible'] ?? false);

        return $directItems || $submenuItems;
    }
}

