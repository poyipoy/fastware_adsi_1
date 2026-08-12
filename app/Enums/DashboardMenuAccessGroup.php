<?php

namespace App\Enums;

enum DashboardMenuAccessGroup: string
{
    case KELOLA_DATA = 'kelola_data';
    case DASHBOARD_MAIN = 'dashboard_main';
    case DASHBOARD_MAINTENANCE = 'dashboard_maintenance';
    case DASHBOARD_HANDLING = 'dashboard_handling';
    case DASHBOARD_COMPETENCY = 'dashboard_competency';
    case DASHBOARD_SS = 'dashboard_ss';
    case DASHBOARD_KNOWLEDGE = 'dashboard_knowledge';
    case DASHBOARD_FPB = 'dashboard_fpb';
    case DASHBOARD_TCPD = 'dashboard_tcpd';
    case DASHBOARD_BOPM = 'dashboard_bopm';

    /**
     * Get allowed role IDs for this access group
     * 
     * @return array
     */
    public function getAllowedRoleIds(): array
    {
        return match($this) {
            self::KELOLA_DATA => [1, 14, 15],
            default => [],
        };
    }

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        return match($this) {
            self::DASHBOARD_MAIN => [
                'ADMINSTRATOR',
                'ANDI SIMPONI',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'VITRI HANDAYANI',
                'ADHI PRASETIYO',
                'AHMAD RIDWAN',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'DANIA ISNAWATI',
                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                'DWI KUNTORO',
                'HARDI SAPUTRA',
                'HUSEIN ABDULLAH',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'LINA UNIARSIH',
                'M. RIDWAN GUNAWAN',
                'MARTINUS CAHYO RAHASTO',
                'MUHAMMAD MAHBUB',
                'NUR DWITA SURA WIJAYA',
                'PUTRI ANINDIA',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'RISFAN FAISAL',
                'SENDY PRABOWO',
                'SITI MARIA ULFA',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'ABDUR RAHMAN AL FAAIZ',
                'SONY STIAWAN',
                'HERLIANA',
            ],
            self::DASHBOARD_MAINTENANCE, self::DASHBOARD_HANDLING, self::DASHBOARD_COMPETENCY => [
                'ADMINSTRATOR',
                'ANDI SIMPONI',
                'MUGI PRAMONO',
                'RANGGA FADILLAH',
                'VITRI HANDAYANI',
                'ADHI PRASETIYO',
                'AHMAD RIDWAN',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'DANIA ISNAWATI',
                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                'DWI KUNTORO',
                'HARDI SAPUTRA',
                'HUSEIN ABDULLAH',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'LINA UNIARSIH',
                'M. RIDWAN GUNAWAN',
                'MARTINUS CAHYO RAHASTO',
                'MUHAMMAD MAHBUB',
                'NUR DWITA SURA WIJAYA',
                'PUTRI ANINDIA',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'RISFAN FAISAL',
                'SENDY PRABOWO',
                'SITI MARIA ULFA',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'ABDUR RAHMAN AL FAAIZ',
                'SONY STIAWAN',
                'HERLIANA',
            ],
            self::DASHBOARD_SS, self::DASHBOARD_KNOWLEDGE => [
                'ADMINSTRATOR',
                'AFILIANDI',
                'AGUNG PANGESTU YUSUF',
                'AGUS PRIYANTO',
                'AGUS ROSIDIN',
                'ANDI SANTOSO',
                'ANDI SIMPONI',
                'ARRY SOEBHEKTI',
                'AWING',
                'DASUKI',
                'DEDY SETIAWAN',
                'DIAMAN DARMAWINATA',
                'ELI HANDOYO',
                'FAIZAL AFDAU',
                'FATUL MUKMIN',
                'HAERUL IKHSAN',
                'HENDRIO',
                'JAKA RARA SUKMA',
                'JAKARIA',
                'KARYA WIJAYA',
                'LUKMAN AHMAD',
                'MAMIK ABIDIN',
                'MEDI KRISNANTO',
                'MIFTAKHUROHMAN',
                'MUGI PRAMONO',
                'NUR SUPRIYANTO',
                'NURSAID',
                'NURSALIM',
                'R.WAWAN HIMAWAN',
                'RAHMAT NUGROHO',
                'RANGGA FADILLAH',
                'RIZKY ANDREA RAHMAWAN',
                'RUKMAN',
                'RUSITO',
                'SABAR WASIRAN',
                'SEPTIADI PRATOMO',
                'SUDIYATNO',
                'UMAR HADI',
                'VITRI HANDAYANI',
                'YANUARDIN SALEH SIREGAR',
                'YUSUF SYAFAAT',
                'ADHI PRASETIYO',
                'AHMAD RIDWAN',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'AVI SHENNA',
                'BANGUN SUTOPO',
                'CECEP ISKANDAR',
                'DANIA ISNAWATI',
                'DINA NIMAS AYU NAWAWULAN PRIHANTINI',
                'DWI KUNTORO',
                'FIKRI SYAHBANA',
                'FRISILIA CLAUDIA HUTAMA',
                'GUNAWAN',
                'HARDI SAPUTRA',
                'HARRY SUPRIYADI',
                'HERLIANA',
                'HERY HERMAWAN',
                'HUSEIN ABDULLAH',
                'ILHAM CHOLID',
                'ILHAM SETIA DARMA',
                'IMAM PRASETYO',
                'IMAM SOPYAN',
                'JEFRY WASTON E',
                'JESSICA PAUNE',
                'JONI SETIAWAN',
                'JUN JOHAMIN PD',
                'KUSTIONO',
                'LINA UNIARSIH',
                'M. RIDWAN GUNAWAN',
                'MARTINUS CAHYO RAHASTO',
                'MOCHAMMAD ANDRIANSYAH',
                'MOHAMMAD FATKHURROHMAN',
                'MUHAMMAD DINAR FARISI',
                'MUHAMMAD MAHBUB',
                'NUR DWITA SURA WIJAYA',
                'PUTRI ANINDIA',
                'RAGIL ISHA RAHMANTO',
                'RIADUS SOLIHIN',
                'RICHARDUS CHRISTIAN',
                'RISFAN FAISAL',
                'RUSLAN M.ALI',
                'SENDY PRABOWO',
                'SETIYAWAN',
                'SITI MARIA ULFA',
                'SUKIMIN',
                'WULYO EKO PRASETYO',
                'YAN WELEM MANGINSELA',
                'YUDHI PRASETYO RAHMAWANTO',
                'YULMAI RIDO WINANDA',
                'YUNASIS PALGUNADI',
                'ZAENAL ARIFIN',
                'ABDUR RAHMAN AL FAAIZ',
                'YAN WALEM MANGINSELA',
                'VIVIAN ANGELIKA',
                'SONY STIAWAN',
                'FAJAR BAGASKARA',
            ],
            self::DASHBOARD_FPB => [
                'ADMINSTRATOR',
                'MEDI KRISNANTO',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'VIVIAN ANGELIKA',
            ],
            self::DASHBOARD_TCPD => [], // Resolved from active organization mapping by DashboardMenuService.
            self::DASHBOARD_BOPM => [
                'ADMINSTRATOR',
                'ILYAS NOOR FIRDAUS',
                'JESSICA PAUNE',
            ],
            default => [],
        };
    }

    /**
     * Check if a user has access to this group by role ID
     * 
     * @param int|null $roleId
     * @return bool
     */
    public function hasAccessByRole(?int $roleId): bool
    {
        if ($roleId === null) {
            return false;
        }

        $allowedRoles = $this->getAllowedRoleIds();
        return in_array($roleId, $allowedRoles, true);
    }

    /**
     * Check if a user has access to this group by user name
     * 
     * @param string $userName
     * @return bool
     */
    public function hasAccessByUser(string $userName): bool
    {
        $allowedUsers = $this->getAllowedUsers();
        return in_array($userName, $allowedUsers, true);
    }

    /**
     * Check if a user has access (by role or user name)
     * 
     * @param int|null $roleId
     * @param string $userName
     * @return bool
     */
    public function hasAccess(?int $roleId, string $userName): bool
    {
        // Check by role first
        if ($this->hasAccessByRole($roleId)) {
            return true;
        }

        // Then check by user name
        return $this->hasAccessByUser($userName);
    }
}
