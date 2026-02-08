<?php

namespace App\Enums;

enum HandlingType: string
{
    case CLAIM = 'Claim';
    case COMPLAIN = 'Complain';

    /**
     * Get type label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CLAIM => 'Claim',
            self::COMPLAIN => 'Complain',
        };
    }

    /**
     * Get all type values
     * 
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

