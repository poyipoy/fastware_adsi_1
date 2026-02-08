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

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
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
                'RICHARDUS',
                'ILHAM CHOLID',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::PERSETUJUAN_FINANCE => [
                'ADMINSTRATOR',
                'ADHI PRASETIYO',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'RICHARDUS',
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
        return in_array($userName, $allowedUsers, true);
    }
}

