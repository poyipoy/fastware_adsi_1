<?php

namespace App\Enums;

enum CrpMenuAccessGroup: string
{
    case CRP_MAIN = 'crp_main';

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        return match($this) {
            self::CRP_MAIN => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'HARDI SAPUTRA',
                'MARTINUS CAHYO RAHASTO',
                'YULMAI RIDO WINANDA',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'ADHI PRASETIYO',
                'MUGI PRAMONO',
                'ILHAM CHOLID',
                'JUN JOHAMIN PD',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS CHRISTIAN',
                'VIVIAN ANGELIKA',
                'ABDUR RAHMAN AL FAAIZ',
                'FAJAR BAGASKARA',
            ],
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

