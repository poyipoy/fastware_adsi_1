<?php

namespace App\Enums;

enum HRMenuAccessGroup: string
{
    case HR_MAIN = 'hr_main';
    case KNOWLEDGE_MANAGEMENT = 'knowledge_management';
    case KNOWLEDGE_APPROVAL = 'knowledge_approval';
    case JOB_POSITION = 'job_position';
    case TECHNICAL_COMPETENCY = 'technical_competency';
    case COMPETENCY_KASIE = 'competency_kasie';
    case COMPETENCY_KADEPT = 'competency_kadept';
    case COMPETENCY_HR = 'competency_hr';
    case SUMMARY_COMPETENCY = 'summary_competency';
    case TRAINING_DEVELOPMENT = 'training_development';
    case TRAINING_APPROVAL = 'training_approval';
    case TRAINING_HISTORY = 'training_history';

    /**
     * Get allowed users for this access group
     * 
     * @return array
     */
    public function getAllowedUsers(): array
    {
        return match($this) {
            self::HR_MAIN => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'HARDI SAPUTRA',
                'MARTINUS CAHYO RAHASTO',
                'YULMAI RIDO WINANDA',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'MUGI PRAMONO',
                'ADHI PRASETIYO',
                'ILHAM CHOLID',
                'JUN JOHAMIN PD',
                'SITI MARIA ULFA',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::KNOWLEDGE_MANAGEMENT, self::KNOWLEDGE_APPROVAL => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'YULMAI RIDO WINANDA',
                'MARTINUS CAHYO RAHASTO',
                'SITI MARIA ULFA',
                'JESSICA PAUNE',
                'ADHI PRASETIYO',
                'ANDIK TOTOK SISWOYO',
                'RICHARDUS',
            ],
            self::JOB_POSITION => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'SITI MARIA ULFA',
            ],
            self::TECHNICAL_COMPETENCY => [
                'ADMINSTRATOR',
                'YULMAI RIDO WINANDA',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'SITI MARIA ULFA',
                'RICHARDUS',
                'MUGI PRAMONO',
                'ABDUR RAHMAN AL FAAIZ',
                'RAGIL ISHA RAHMANTO',
            ],
            self::COMPETENCY_KASIE, self::COMPETENCY_KADEPT, self::COMPETENCY_HR, self::SUMMARY_COMPETENCY => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'YULMAI RIDO WINANDA',
                'ADHI PRASETIYO',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'MARTINUS CAHYO RAHASTO',
                'SITI MARIA ULFA',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS',
                'ABDUR RAHMAN AL FAAIZ',
            ],
            self::TRAINING_DEVELOPMENT => [
                'ADMINSTRATOR',
                'YULMAI RIDO WINANDA',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'JESSICA PAUNE',
                'MARTINUS CAHYO RAHASTO',
                'SITI MARIA ULFA',
                'RICHARDUS',
                'ADHI PRASETYO',
            ],
            self::TRAINING_APPROVAL => [
                'ADMINSTRATOR',
                'JESSICA PAUNE',
                'SITI MARIA ULFA',
            ],
            self::TRAINING_HISTORY => [
                'ADMINSTRATOR',
                'MUGI PRAMONO',
                'YULMAI RIDO WINANDA',
                'ADHI PRASETIYO',
                'ANDIK TOTOK SISWOYO',
                'ARY RODJO PRASETYO',
                'HARDI SAPUTRA',
                'ILHAM CHOLID',
                'JESSICA PAUNE',
                'JUN JOHAMIN PD',
                'MARTINUS CAHYO RAHASTO',
                'SITI MARIA ULFA',
                'RAGIL ISHA RAHMANTO',
                'RICHARDUS',
                'ABDUR RAHMAN AL FAAIZ',
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
        return in_array($userName, $this->getAllowedUsers());
    }
}

