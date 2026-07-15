<?php

namespace App\Enums;

enum SumbangSaranMenuAccessGroup: string
{
    case SUMBANG_SARAN_MAIN = 'sumbang_saran_main';
    case FORM_SUMBANG_SARAN = 'form_sumbang_saran';
    case OVERVIEW_SUMBANG_SARAN = 'overview_sumbang_saran';
    case UPLOAD_JSON = 'upload_json';
    case PERSETUJUAN_KASIE = 'persetujuan_kasie';
    case PERSETUJUAN_KADEPT = 'persetujuan_kadept';
    case PENILAIAN_KOMITE = 'penilaian_komite';
    case PENILAIAN_HRGA = 'penilaian_hrga';

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        return match($this) {
            self::FORM_SUMBANG_SARAN, self::OVERVIEW_SUMBANG_SARAN => [
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
                'AIS DUTA PRAMANDA',
            ],
            self::UPLOAD_JSON => [
                'ADMINSTRATOR',
            ],
            self::PERSETUJUAN_KASIE => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'MUGI PRAMONO',
                'ADHI PRASETIYO',
                'ANDIK TOTOK SISWOYO',
                'ILHAM CHOLID',
                'JUN JOHAMIN PD',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'SITI MARIA ULFA',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::PERSETUJUAN_KADEPT => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'YULMAI RIDO WINANDA',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
            ],
            self::PENILAIAN_KOMITE, self::PENILAIAN_HRGA => [
                'ADMINSTRATOR',
                'ARY RODJO PRASETYO',
                'JESSICA PAUNE',
                'SITI MARIA ULFA',
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

