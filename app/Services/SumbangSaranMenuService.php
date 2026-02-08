<?php

namespace App\Services;

use App\Enums\SumbangSaranMenuAccessGroup;
use Illuminate\Support\Collection;

class SumbangSaranMenuService
{
    /**
     * Get Sumbang Saran menu structure with access control
     * 
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(string $userName): Collection
    {
        $directItems = [
            [
                'label' => 'Form Sumbang Saran',
                'route' => 'showSS',
                'visible' => SumbangSaranMenuAccessGroup::FORM_SUMBANG_SARAN->hasAccess($userName),
            ],
            [
                'label' => 'Overview Sumbang Saran',
                'route' => 'forumSS',
                'visible' => SumbangSaranMenuAccessGroup::OVERVIEW_SUMBANG_SARAN->hasAccess($userName),
            ],
            [
                'label' => 'Upload JSON to CSV',
                'route' => 'upload.json',
                'visible' => SumbangSaranMenuAccessGroup::UPLOAD_JSON->hasAccess($userName),
            ],
        ];

        $submenus = [
            $this->getPersetujuanAtasanMenu($userName),
            $this->getPenilaianMenu($userName),
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
     * Get Persetujuan Atasan submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getPersetujuanAtasanMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Ka. Sie',
                'route' => 'showKonfirmasiForeman',
                'visible' => SumbangSaranMenuAccessGroup::PERSETUJUAN_KASIE->hasAccess($userName),
            ],
            [
                'label' => 'Ka. Dept',
                'route' => 'showKonfirmasiDeptHead',
                'visible' => SumbangSaranMenuAccessGroup::PERSETUJUAN_KADEPT->hasAccess($userName),
            ],
        ];

        $visible = collect($items)->contains(fn($item) => $item['visible']);
        $shouldShow = $visible || (
            SumbangSaranMenuAccessGroup::PENILAIAN_KOMITE->hasAccess($userName) ||
            SumbangSaranMenuAccessGroup::PENILAIAN_HRGA->hasAccess($userName)
        );

        return [
            'title' => 'Persetujuan Atasan',
            'visible' => $shouldShow,
            'items' => $items,
        ];
    }

    /**
     * Get Penilaian submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getPenilaianMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Penilaian Komite',
                'route' => 'showKonfirmasiKomite',
                'visible' => SumbangSaranMenuAccessGroup::PENILAIAN_KOMITE->hasAccess($userName),
            ],
            [
                'label' => 'Penilaian HRGA',
                'route' => 'showKonfirmasiHRGA',
                'visible' => SumbangSaranMenuAccessGroup::PENILAIAN_HRGA->hasAccess($userName),
            ],
        ];

        $visible = collect($items)->contains(fn($item) => $item['visible']);
        $shouldShow = $visible || (
            SumbangSaranMenuAccessGroup::PERSETUJUAN_KASIE->hasAccess($userName) ||
            SumbangSaranMenuAccessGroup::PERSETUJUAN_KADEPT->hasAccess($userName)
        );

        return [
            'title' => 'Penilaian',
            'visible' => $shouldShow,
            'items' => $items,
        ];
    }

    /**
     * Check if user has access to any Sumbang Saran menu
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

