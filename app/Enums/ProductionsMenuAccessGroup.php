<?php

namespace App\Enums;

enum ProductionsMenuAccessGroup: string
{
    case PRODUCTIONS_MAIN = 'productions_main';
    case FPP_INDEX = 'fpp_index';
    case FPP_HISTORY = 'fpp_history';
    case MAINTENANCE_DMI = 'maintenance_dmi';
    case MAINTENANCE_PERSETUJUAN_FPP = 'maintenance_persetujuan_fpp';
    case MAINTENANCE_FPP_HISTORY = 'maintenance_fpp_history';
    case MAINTENANCE_TABEL_PREVENTIF = 'maintenance_tabel_preventif';
    case ENGINEERING_FORM_TINDAK_LANJUT = 'engineering_form_tindak_lanjut';
    case ENGINEERING_RIWAYAT_KLAIM = 'engineering_riwayat_klaim';
    case ENGINEERING_JADWAL_KUNJUNGAN = 'engineering_jadwal_kunjungan';
    case KOREKTIF_TERIMA_FORM = 'korektif_terima_form';
    case KOREKTIF_RIWAYAT_FORM = 'korektif_riwayat_form';
    case PREVENTIF_JADWAL = 'preventif_jadwal';

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        return match($this) {
            self::FPP_INDEX => [
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
                'Sony Stiawan',
            ],
            self::FPP_HISTORY => [
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
            self::MAINTENANCE_DMI, self::MAINTENANCE_PERSETUJUAN_FPP => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'VITRI HANDAYANI',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'SITI MARIA ULFA',
                'YULMAI RIDO WINANDA',
            ],
            self::MAINTENANCE_FPP_HISTORY, self::MAINTENANCE_TABEL_PREVENTIF => [
                'ADMINSTRATOR',
                'ANDI SIMPONI',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'ADHI PRASETIYO',
                'ARY RODJO PRASETYO',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'SITI MARIA ULFA',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::ENGINEERING_FORM_TINDAK_LANJUT, 
            self::ENGINEERING_RIWAYAT_KLAIM, 
            self::ENGINEERING_JADWAL_KUNJUNGAN => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'ARY RODJO PRASETYO',
                'JESSICA PAUNE',
            ],
            self::KOREKTIF_TERIMA_FORM => [
                'ADMINSTRATOR',
                'ANDI SIMPONI',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'VITRI HANDAYANI',
                'ADHI PRASETIYO',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'MARTINUS CAHYO RAHASTO',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'SITI MARIA ULFA',
                'YULMAI RIDO WINANDA',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::KOREKTIF_RIWAYAT_FORM => [
                'ADMINSTRATOR',
                'ANDI SIMPONI',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'ADHI PRASETIYO',
                'ARY RODJO PRASETYO',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'SITI MARIA ULFA',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::PREVENTIF_JADWAL => [
                'ADMINSTRATOR',
                'ANDI SIMPONI',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'ADHI PRASETIYO',
                'ARY RODJO PRASETYO',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'SITI MARIA ULFA',
                'ABDUR RAHMAN AL FAAIZ',
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

