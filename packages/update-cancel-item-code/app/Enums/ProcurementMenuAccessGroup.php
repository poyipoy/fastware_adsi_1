<?php

namespace App\Enums;

enum ProcurementMenuAccessGroup: string
{
    case PROCUREMENT_MAIN = 'procurement_main';
    case OVERVIEW_FPB = 'overview_fpb';
    case FORM_PENGAJUAN_BARANG = 'form_pengajuan_barang';
    case PERSETUJUAN_USER = 'persetujuan_user';
    case PERSETUJUAN_DEPT = 'persetujuan_dept';
    case PERSETUJUAN_FINANCE = 'persetujuan_finance';
    case PERSETUJUAN_PROCUREMENT_1 = 'persetujuan_procurement_1';
    case PERSETUJUAN_PROCUREMENT_2 = 'persetujuan_procurement_2';
    case PENAWARAN_SUBCONT_FORM = 'penawaran_subcont_form';
    case PENAWARAN_SUBCONT_PERSETUJUAN = 'penawaran_subcont_persetujuan';
    case CLAIM_SUBMISSION_FORM = 'claim_submission_form';
    case CLAIM_SUBMISSION_APPROVAL = 'claim_submission_approval';
    case ITEM_CODE_ACCESS = 'item_code_access';
    case ITEM_CODE_FORM = 'item_code_form';
    case ITEM_CODE_APPROVAL = 'item_code_approval';
    case ITEM_CODE_APPROVER = 'item_code_approver';
    case ITEM_CODE_APPROVER_1 = 'item_code_approver_1';
    case ITEM_CODE_APPROVER_2 = 'item_code_approver_2';
    case ITEM_CODE_FINISHER = 'item_code_finisher';
    case ITEM_CODE_CANCELLER = 'item_code_canceller';
    case INQUIRY_LOCAL_FORM = 'inquiry_local_form';
    case INQUIRY_LOCAL_APPROVAL_SIE = 'inquiry_local_approval_sie';
    case INQUIRY_LOCAL_APPROVAL_DEPT = 'inquiry_local_approval_dept';
    case INQUIRY_LOCAL_APPROVAL_INVENTORY = 'inquiry_local_approval_inventory';
    case INQUIRY_LOCAL_OVERVIEW_PURCHASE = 'inquiry_local_overview_purchase';
    case INQUIRY_LOCAL_OVERVIEW_PURCHASE_2 = 'inquiry_local_overview_purchase_2';
    case INQUIRY_IMPORT_FORM = 'inquiry_import_form';
    case INQUIRY_IMPORT_APPROVAL_INVENTORY = 'inquiry_import_approval_inventory';
    case INQUIRY_IMPORT_OVERVIEW_PURCHASE = 'inquiry_import_overview_purchase';
    case ADMINISTRATION_FORM_PURCHASING = 'administration_form_purchasing';
    case ADMINISTRATION_FORM_ADMIN = 'administration_form_admin';
    case SUPPLIER_FORM_INDEX = 'supplier_form_index';
    case OUTSTANDING_MATERIAL = 'outstanding_material';

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        $adminUsers = [
            'ADMINISTRATOR',
            'ADMINSTRATOR',
        ];

        $itemCodeFormUsers = [
            'ILYAS NOOR FIRDAUS',
            'VIVIAN ANGELIKA',
            'FAJAR BAGASKARA',
        ];

        $itemCodeApprover1Users = [
            'JESSICA PAUNE',
        ];

        $itemCodeApprover2Users = [
            'MARTINUS CAHYO RAHASTO',
        ];

        $itemCodeFinisherUsers = [
            'ADHI PRASETIYO',
            'ADHI PRASETYO',
        ];

        $itemCodeCancellerUsers = [
            'ILYAS NOOR FIRDAUS',
        ];

        $itemCodeApproverUsers = array_values(array_unique(array_merge(
            $adminUsers,
            $itemCodeApprover1Users,
            $itemCodeApprover2Users
        )));

        $itemCodeApprovalUsers = array_values(array_unique(array_merge(
            $itemCodeApproverUsers,
            $itemCodeFinisherUsers
        )));

        $itemCodeAccessUsers = array_values(array_unique(array_merge(
            $itemCodeApprovalUsers,
            $itemCodeFormUsers
        )));

        $itemCodeFormAccessUsers = array_values(array_unique(array_merge(
            $adminUsers,
            $itemCodeFormUsers
        )));

        $itemCodeApprover1AccessUsers = array_values(array_unique(array_merge(
            $adminUsers,
            $itemCodeApprover1Users
        )));

        $itemCodeApprover2AccessUsers = array_values(array_unique(array_merge(
            $adminUsers,
            $itemCodeApprover2Users
        )));

        $itemCodeFinisherAccessUsers = array_values(array_unique(array_merge(
            $adminUsers,
            $itemCodeFinisherUsers
        )));

        return match($this) {
            self::OVERVIEW_FPB => [], // Available for all authenticated users
            self::FORM_PENGAJUAN_BARANG => [
                'ADMINSTRATOR',
                'MEDI KRISNANTO',
                'MUGI PRAMONO',
            ],
            self::PERSETUJUAN_USER => [
                'ADMINSTRATOR',
                'MEDI KRISNANTO',
                'JESSICA PAUNE',
                'MUHAMMAD DINAR FARISI',
                'SITI MARIA ULFA',
                'NURSALIM',
                'MARTINUS CAHYO RAHASTO',
            ],
            self::PERSETUJUAN_DEPT => [
                'ADMINSTRATOR',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'YULMAI RIDO WINANDA',
                'ADHI PRASETIYO',
                'RICHARDUS CHRISTIAN',
                'ILHAM CHOLID',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::PERSETUJUAN_FINANCE => [
                'ADMINSTRATOR',
                'ADHI PRASETIYO',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'RICHARDUS CHRISTIAN',
            ],
            self::PERSETUJUAN_PROCUREMENT_1, self::PERSETUJUAN_PROCUREMENT_2 => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'VIVIAN ANGELIKA',
                'FAJAR BAGASKARA',
            ],
            self::PENAWARAN_SUBCONT_FORM => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'RAGIL ISHA RAHMANTO',
                'SARAH EGA BUDI ASTUTI',
                'AIS DUTA PRAMANDA',
            ],
            self::PENAWARAN_SUBCONT_PERSETUJUAN => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'VIVIAN ANGELIKA',
                'FAJAR BAGASKARA',
            ],
            self::CLAIM_SUBMISSION_FORM => [
                // form bisa diakses semua user, kosong berarti allow all
            ],
            self::CLAIM_SUBMISSION_APPROVAL => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'VIVIAN ANGELIKA',
                'FAJAR BAGASKARA',
            ],
            self::ITEM_CODE_ACCESS => $itemCodeAccessUsers,
            self::ITEM_CODE_FORM => $itemCodeFormAccessUsers,
            self::ITEM_CODE_APPROVAL => $itemCodeApprovalUsers,
            self::ITEM_CODE_APPROVER => $itemCodeApproverUsers,
            self::ITEM_CODE_APPROVER_1 => $itemCodeApprover1AccessUsers,
            self::ITEM_CODE_APPROVER_2 => $itemCodeApprover2AccessUsers,
            self::ITEM_CODE_FINISHER => $itemCodeFinisherAccessUsers,
            self::ITEM_CODE_CANCELLER => $itemCodeCancellerUsers,
            self::INQUIRY_LOCAL_FORM => [
                'ADMINSTRATOR',
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'DWI KUNTORO',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'LINA UNIARSIH',
                'MARTINUS CAHYO RAHASTO',
                'RISFAN FAISAL',
                'SENDY PRABOWO',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'SARAH EGA BUDI ASTUTI',
                'DIMAS ADITYA PRIANDANA',
                'HEXAPA DARMADI',
                'HERY HERMAWAN',
                'WULYO EKO PRASETYO',
                'SONY STIAWAN',
                'FISKA CHRISMAS YUDHA',
                'RIFQI RAHMAT DZATNIKA',
            ],
            self::INQUIRY_LOCAL_APPROVAL_SIE => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'ANDIK TOTOK SISWOYO',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
            ],
            self::INQUIRY_LOCAL_APPROVAL_DEPT => [
                'ADMINSTRATOR',
                'ANDIK TOTOK SISWOYO',
                'JESSICA PAUNE',
                'YULMAI RIDO WINANDA',
            ],
            self::INQUIRY_LOCAL_APPROVAL_INVENTORY => [
                'ADMINSTRATOR',
                'ILYAS NOOR FIRDAUS',
            ],
            self::INQUIRY_LOCAL_OVERVIEW_PURCHASE, self::INQUIRY_LOCAL_OVERVIEW_PURCHASE_2 => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'VIVIAN ANGELIKA',
                'M. IQBAL',
            ],
            self::INQUIRY_IMPORT_FORM => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'DWI KUNTORO',
                'ILHAM CHOLID',
                'JUN JOHAMIN PD',
                'LINA UNIARSIH',
                'RISFAN FAISAL',
                'SENDY PRABOWO',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'SARAH EGA BUDI ASTUTI',
                'DIMAS ADITYA PRIANDANA',
                'HEXAPA DARMADI',
                'HERY HERMAWAN',
                'WULYO EKO PRASETYO',
                'SONY STIAWAN',
                'FISKA CHRISMAS YUDHA',
                'RIFQI RAHMAT DZATNIKA',
            ],
            self::INQUIRY_IMPORT_APPROVAL_INVENTORY, self::INQUIRY_IMPORT_OVERVIEW_PURCHASE => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'M. IQBAL',
                'ILYAS NOOR FIRDAUS',
            ],
            self::ADMINISTRATION_FORM_PURCHASING, self::ADMINISTRATION_FORM_ADMIN => [], // Available for all
            self::SUPPLIER_FORM_INDEX => [], // Available for all
            self::OUTSTANDING_MATERIAL => array_values(array_unique(array_merge($adminUsers, [
                'ILYAS NOOR FIRDAUS',
                'JESSICA PAUNE',
                'FAJAR BAGASKARA',
                'VIVIAN ANGELIKA',
            ]))),
            default => [],
        };
    }

    /**
     * Check if a user has access to this group
     * 
     * @param string $userName
     * @return bool
     */
    public function hasAccess(string $userName): bool
    {
        $allowedUsers = $this->getAllowedUsers();
        // Empty array means available for all authenticated users
        if (empty($allowedUsers)) {
            return true;
        }
        $normalizedUser = self::normalizeUserName($userName);
        $normalizedAllowedUsers = array_map([self::class, 'normalizeUserName'], $allowedUsers);

        return in_array($normalizedUser, $normalizedAllowedUsers, true);
    }

    private static function normalizeUserName(string $userName): string
    {
        return strtoupper(trim($userName));
    }
}
