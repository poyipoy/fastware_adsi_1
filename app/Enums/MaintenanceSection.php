<?php

namespace App\Enums;

enum MaintenanceSection: string
{
    case CUTTING = 'cutting';
    case MACHINING = 'machining';
    case MACHINING_CUSTOM = 'machining custom';
    case HEAT_TREATMENT = 'heat treatment';

    /**
     * Get section label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::CUTTING => 'Cutting',
            self::MACHINING => 'Machining',
            self::MACHINING_CUSTOM => 'Machining Custom',
            self::HEAT_TREATMENT => 'Heat Treatment',
        };
    }

    /**
     * Get all section values
     * 
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

