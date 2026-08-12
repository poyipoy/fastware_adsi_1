<?php

namespace App\Services;

use App\Enums\ProcurementMenuAccessGroup;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ProcurementMenuService
{
    public function __construct(
        private ?OutstandingMaterialAccessService $outstandingMaterialAccess = null,
    ) {
    }

    /**
     * Get Procurement menu structure with access control
     * 
     * @param User|string $user
     * @return Collection
     */
    public function getMenuStructure(User|string $user): Collection
    {
        $userName = $user instanceof User
            ? (string) $user->getAttribute('name')
            : (string) $user;

        $resolvedUser = $user instanceof User
            ? $user
            : (Auth::user() instanceof User && (string) Auth::user()->getAttribute('name') === $userName
                ? Auth::user()
                : null);

        // String callers retain the legacy whitelist fallback. Authenticated
        // object callers (the normal web/API path) use the same Sales-aware
        // capability service as the route middleware.
        $outstandingAccess = $resolvedUser
            ? $this->outstandingMaterialAccess()->canView($resolvedUser)
            : ProcurementMenuAccessGroup::OUTSTANDING_MATERIAL->hasAccess($userName);

        $directItems = [
            [
                'label' => 'Overview FPB',
                'route' => 'overviewfpb',
                'visible' => ProcurementMenuAccessGroup::OVERVIEW_FPB->hasAccess($userName),
            ],
            [
                'label' => 'Form Pengajuan Barang/Jasa',
                'route' => 'index.PO',
                'visible' => ProcurementMenuAccessGroup::FORM_PENGAJUAN_BARANG->hasAccess($userName),
            ],
            [
                'label' => 'Outstanding Material',
                'route' => 'outstanding-materials.index',
                'visible' => $outstandingAccess,
            ],
        ];

        $submenus = [
            $this->getPersetujuanFormMenu($userName),
            $this->getPenawaranSubcontMenu($userName),
            $this->getClaimSubmissionMenu($userName),
            $this->getItemCodeMenu($userName),
            $this->getInquiryOrderLocalMenu($userName),
            $this->getInquiryOrderImportMenu($userName),
            $this->getImportAdministrationMenu($userName),
            $this->getSupplierFormMenu($userName),
        ];

        $items = collect($directItems)
            ->merge(collect($submenus)->filter(fn($item) => $item['visible'] ?? false))
            ->values()
            ->all();

        return collect([
            'visible' => true, // Always visible if authenticated
            'items' => $items,
        ]);
    }

    private function outstandingMaterialAccess(): OutstandingMaterialAccessService
    {
        return $this->outstandingMaterialAccess ??= new OutstandingMaterialAccessService();
    }

    /**
     * Get Persetujuan Form submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getPersetujuanFormMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'User Section',
                'route' => 'index.PO.user',
                'visible' => ProcurementMenuAccessGroup::PERSETUJUAN_USER->hasAccess($userName),
            ],
            [
                'label' => 'Ka. Dept',
                'route' => 'index.PO.Dept',
                'visible' => ProcurementMenuAccessGroup::PERSETUJUAN_DEPT->hasAccess($userName),
            ],
            [
                'label' => 'Finance Section',
                'route' => 'index.PO.finance',
                'visible' => ProcurementMenuAccessGroup::PERSETUJUAN_FINANCE->hasAccess($userName),
            ],
            [
                'label' => 'Procurement Menu 1',
                'route' => 'index.PO.procurement',
                'visible' => ProcurementMenuAccessGroup::PERSETUJUAN_PROCUREMENT_1->hasAccess($userName),
            ],
            [
                'label' => 'Procurement Menu 2',
                'route' => 'index.PO.procurement2',
                'visible' => ProcurementMenuAccessGroup::PERSETUJUAN_PROCUREMENT_2->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Persetujuan Form',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Penawaran Subcont Project Sales submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getPenawaranSubcontMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Penawaran Subcont',
                'route' => 'indexSales',
                'visible' => ProcurementMenuAccessGroup::PENAWARAN_SUBCONT_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Subcont',
                'route' => 'indexProc',
                'visible' => ProcurementMenuAccessGroup::PENAWARAN_SUBCONT_PERSETUJUAN->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Panawaran Subcont Project Sales',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Claim Submission submenu
     *
     * @param string $userName
     * @return array
     */
    private function getClaimSubmissionMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Claim Submission',
                'route' => 'claim.indexUser',
                'visible' => ProcurementMenuAccessGroup::CLAIM_SUBMISSION_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Claim Submission',
                'route' => 'claim.indexProc',
                'visible' => ProcurementMenuAccessGroup::CLAIM_SUBMISSION_APPROVAL->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Claim Submission',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Item Code submenu
     *
     * @param string $userName
     * @return array
     */
    private function getItemCodeMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Item Code',
                'route' => 'item-code.form',
                'visible' => ProcurementMenuAccessGroup::ITEM_CODE_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Item Code',
                'route' => 'item-code.approval',
                'visible' => ProcurementMenuAccessGroup::ITEM_CODE_APPROVAL->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Item Code',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Inquiry Order Local submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getInquiryOrderLocalMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Inquiry Local',
                'route' => 'createinquiry',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_LOCAL_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Ka. Sie',
                'route' => 'showApprovalKaSie',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_LOCAL_APPROVAL_SIE->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Ka. Dept',
                'route' => 'showApprovalKaDept',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_LOCAL_APPROVAL_DEPT->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Inventory',
                'route' => 'showApprovalInventory',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_LOCAL_APPROVAL_INVENTORY->hasAccess($userName),
            ],
            [
                'label' => 'Overview Purchase',
                'route' => 'overviewPurchase',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_LOCAL_OVERVIEW_PURCHASE->hasAccess($userName),
            ],
            [
                'label' => 'Overview Inquiry Order Local',
                'route' => 'overviewInquiry',
                'visible' => true, // Always visible
            ],
            [
                'label' => 'Overview Purchase 2',
                'route' => 'overviewPurchase2',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_LOCAL_OVERVIEW_PURCHASE_2->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Inquiry Order Local',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Inquiry Order Import submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getInquiryOrderImportMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Inquiry Import',
                'route' => 'createinquiryImport',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_IMPORT_FORM->hasAccess($userName),
            ],
            [
                'label' => 'Persetujuan Inventory',
                'route' => 'showApprovalInventoryImport',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_IMPORT_APPROVAL_INVENTORY->hasAccess($userName),
            ],
            [
                'label' => 'Overview Purchasing Import',
                'route' => 'overviewPurchaseImport',
                'visible' => ProcurementMenuAccessGroup::INQUIRY_IMPORT_OVERVIEW_PURCHASE->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Inquiry Order Import',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Import Administration submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getImportAdministrationMenu(string $userName): array
    {
        $items = [
            [
                'label' => 'Form Administration Purchasing',
                'route' => 'createadministration',
                'visible' => ProcurementMenuAccessGroup::ADMINISTRATION_FORM_PURCHASING->hasAccess($userName),
            ],
            [
                'label' => 'Form Administration Admin',
                'route' => 'createadministration',
                'visible' => ProcurementMenuAccessGroup::ADMINISTRATION_FORM_ADMIN->hasAccess($userName),
            ],
        ];

        return [
            'title' => 'Import Administration',
            'visible' => collect($items)->contains(fn($item) => $item['visible']),
            'items' => $items,
        ];
    }

    /**
     * Get Supplier Form submenu
     * 
     * @param string $userName
     * @return array
     */
    private function getSupplierFormMenu(string $userName): array
    {
        return [
            'title' => 'Supplier Form',
            'visible' => ProcurementMenuAccessGroup::SUPPLIER_FORM_INDEX->hasAccess($userName),
            'items' => [
                [
                    'label' => 'Index Supplier',
                    'route' => 'supplierform.index',
                    'visible' => ProcurementMenuAccessGroup::SUPPLIER_FORM_INDEX->hasAccess($userName),
                ],
            ],
        ];
    }
}
