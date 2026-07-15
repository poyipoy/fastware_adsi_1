<?php

namespace App\Enums;

enum SalesMenuAccessGroup: string
{
    case SALES_MAIN = 'sales_main';
    case FORM_PERMINTAAN_PERBAIKAN = 'form_permintaan_perbaikan';
    case FORM_PERMINTAAN_PERBAIKAN_DATA = 'form_permintaan_perbaikan_data';
    case FORM_PERMINTAAN_PERBAIKAN_RIWAYAT = 'form_permintaan_perbaikan_riwayat';
    case HANDLING_KLAIM_KOMPLAIN = 'handling_klaim_komplain';
    case HANDLING_FORM_PENGAJUAN = 'handling_form_pengajuan';
    case HANDLING_RIWAYAT = 'handling_riwayat';
    case HANDLING_JADWAL = 'handling_jadwal';
    case INQUIRY_STATUS = 'inquiry_status';
    case CUSTOM_REQUEST = 'custom_request';
    case CUSTOM_REQUEST_FORM = 'custom_request_form';
    case CUSTOM_REQUEST_APPROVE_MARKETING = 'custom_request_approve_marketing';
    case CUSTOM_REQUEST_APPROVE_FINANCE = 'custom_request_approve_finance';

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        return match($this) {
            self::FORM_PERMINTAAN_PERBAIKAN_DATA => [
                'ADMINSTRATOR',
                'MAMIK ABIDIN',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'RUSITO',
                'SUDIYATNO',
                'ARY RODJO PRASETYO',
                'BANGUN SUTOPO',
                'JESSICA PAUNE',
                'RAGIL ISHA RAHMANTO',
                'ZAENAL ARIFIN',
                'ABDUR RAHMAN AL FAAIZ',
                'SONY STIAWAN',
            ],
            self::FORM_PERMINTAAN_PERBAIKAN_RIWAYAT => [
                'ADMINSTRATOR',
                'MAMIK ABIDIN',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'RUSITO',
                'SUDIYATNO',
                'ARY RODJO PRASETYO',
                'BANGUN SUTOPO',
                'JESSICA PAUNE',
                'RAGIL ISHA RAHMANTO',
                'ZAENAL ARIFIN',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::HANDLING_FORM_PENGAJUAN => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'ADHI PRASETIYO',
                'AHMAD RIDWAN',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'AVI SHENNA',
                'DANIA ISNAWATI',
                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                'DWI KUNTORO',
                'FIKRI SYAHBANA',
                'GUNAWAN',
                'HARDI SAPUTRA',
                'HUSEIN ABDULLAH',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JONI SETIAWAN',
                'JUN JOHAMIN PD',
                'LINA UNIARSIH',
                'M. RIDWAN GUNAWAN',
                'MARTINUS CAHYO RAHASTO',
                'MOHAMMAD FATKHURROHMAN',
                'NUR DWITA SURA WIJAYA',
                'PUTRI ANINDIA',
                'RIADUS SOLIHIN',
                'RICHARDUS CHRISTIAN',
                'RISFAN FAISAL',
                'RUSLAN M.ALI',
                'SENDY PRABOWO',
                'SUKIMIN',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YUDHI PRASETYO RAHMAWANTO',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'SONY STIAWAN',
            ],
            self::HANDLING_RIWAYAT, self::HANDLING_JADWAL => [
                'ADMINSTRATOR',
                'ADHI PRASETIYO',
                'AHMAD RIDWAN',
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                'DWI KUNTORO',
                'GUNAWAN',
                'HUSEIN ABDULLAH',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'LINA UNIARSIH',
                'M. RIDWAN GUNAWAN',
                'MARTINUS CAHYO RAHASTO',
                'NUR DWITA SURA WIJAYA',
                'PUTRI ANINDIA',
                'RICHARDUS CHRISTIAN',
                'RISFAN FAISAL',
                'SENDY PRABOWO',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YUDHI PRASETYO RAHMAWANTO',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'Sony Stiawan',
            ],
            self::INQUIRY_STATUS => [
                'ADMINSTRATOR',
                'ADHI PRASETIYO',
                'AHMAD RIDWAN',
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                'DWI KUNTORO',
                'GUNAWAN',
                'HUSEIN ABDULLAH',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'LINA UNIARSIH',
                'M. RIDWAN GUNAWAN',
                'MARTINUS CAHYO RAHASTO',
                'NUR DWITA SURA WIJAYA',
                'PUTRI ANINDIA',
                'RICHARDUS CHRISTIAN',
                'RISFAN FAISAL',
                'SENDY PRABOWO',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YUDHI PRASETYO RAHMAWANTO',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'Sony Stiawan',
            ],
            self::CUSTOM_REQUEST_FORM => [
                'ADMINSTRATOR',
                'ANDIK TOTOK SISWOYO',
                'DANIA ISNAWATI',
                'DIMAS ADITYA PRIANDANA',
                'DWI KUNTORO',
                'FISKA CHRISMAS YUDHA',
                'FRISILIA CLAUDIA HUTAMA',
                'HARDI SAPUTRA',
                'HERLIANA',
                'HERY HERMAWAN',
                'HEXAPA DARMADI',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'LINA UNIARSIH',
                'PUTRI ANINDIA',
                'SARAH EGA BUDI ASTUTI',
                'SENDY PRABOWO',
                'SONY STIAWAN',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'RAGIL ISHA RAHMANTO',
                'AIS DUTA PRAMANDA',
                'ARY RODJO PRASETYO',
                'RIFQI RAHMAT DZATNIKA',
            ],
            self::CUSTOM_REQUEST_APPROVE_MARKETING => [
                'ADMINSTRATOR',
                'ANDIK TOTOK SISWOYO',
                'YULMAI RIDO WINANDA',
            ],
            self::CUSTOM_REQUEST_APPROVE_FINANCE => [
                'ADMINSTRATOR',
                'MARTINUS CAHYO RAHASTO',
            ],
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
        return in_array($userName, $allowedUsers, true);
    }
}

