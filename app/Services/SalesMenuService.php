<?php

namespace App\Services;

use App\Enums\SalesMenuAccessGroup;
use Illuminate\Support\Collection;

class SalesMenuService
{
    /**
     * Get Sales menu structure with access control
     * 
     * @param string $userName
     * @return Collection
     */
    public function getMenuStructure(string $userName): Collection
    {
        $items = collect([
            $this->getFormPermintaanPerbaikanMenu($userName),
            $this->getHandlingKlaimKomplainMenu($userName),
            $this->getInquiryStatusMenu($userName),
            $this->getCustomRequestMenu($userName),
        ])->filter(fn($item) => $item['visible'] ?? false)->values()->all();

        return collect([
            'visible' => !empty($items),
            'items' => $items,
        ]);
    }

    /**
     * Get Form Permintaan Perbaikan submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getFormPermintaanPerbaikanMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Data Form Perbaikan',
                'route' => 'sales.index',
                'visible' => SalesMenuAccessGroup::FORM_PERMINTAAN_PERBAIKAN_DATA->hasAccess($userName),
            ],
            [
                'label' => 'Riwayat Form Perbaikan',
                'route' => 'fpps.history',
                'visible' => SalesMenuAccessGroup::FORM_PERMINTAAN_PERBAIKAN_RIWAYAT->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Form Permintaan Perbaikan',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Handling Klaim dan Komplain submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getHandlingKlaimKomplainMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Pengajuan Klaim/Komplain',
                'route' => 'index',
                'visible' => SalesMenuAccessGroup::HANDLING_FORM_PENGAJUAN->hasAccess($userName),
            ],
            [
                'label' => 'Riwayat Klaim/Komplain',
                'route' => 'showHistoryCLaimComplain',
                'visible' => SalesMenuAccessGroup::HANDLING_RIWAYAT->hasAccess($userName),
            ],
            [
                'label' => 'Jadwal Kunjungan',
                'route' => 'scheduleVisit',
                'visible' => SalesMenuAccessGroup::HANDLING_JADWAL->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Handling Klaim dan Komplain',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Inquiry Status submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getInquiryStatusMenu(string $userName): array
    {
        $hasAccess = SalesMenuAccessGroup::INQUIRY_STATUS->hasAccess($userName);
        
        return [
            'title' => 'Inquiry Status',
            'visible' => $hasAccess,
            'items' => [
                [
                    'label' => 'Form Inquiry Material',
                    'route' => 'createinquiry',
                    'visible' => $hasAccess,
                ],
            ],
        ];
    }

    /**
     * Get Custom Request submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getCustomRequestMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Custom Request',
                'route' => 'showCustomRequest',
                'visible' => SalesMenuAccessGroup::CUSTOM_REQUEST_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Marketing',
                'route' => 'showApproveMarketing',
                'visible' => SalesMenuAccessGroup::CUSTOM_REQUEST_APPROVE_MARKETING->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Finance',
                'route' => 'showApproveFinance',
                'visible' => SalesMenuAccessGroup::CUSTOM_REQUEST_APPROVE_FINANCE->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Custom Request',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Check if user has access to any Sales menu
     * 
     * @param string $userName
     * @return bool
     */
    public function hasAnyAccess(string $userName): bool
    {
        $menuStructure = $this->getMenuStructure($userName);
        return $menuStructure['visible'];
    }
}

