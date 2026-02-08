<?php

namespace App\Enums;

enum ProcessType: string
{
    case HEAT_TREATMENT = 'Heat Treatment';
    case CUTTING = 'Cutting';
    case MACHINING = 'Machining';

    /**
     * Get process type label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::HEAT_TREATMENT => 'Heat Treatment',
            self::CUTTING => 'Cutting',
            self::MACHINING => 'Machining',
        };
    }

    /**
     * Get all process type values
     * 
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

