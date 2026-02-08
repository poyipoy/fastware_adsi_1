<?php

namespace App\Enums;

enum FormFppStatus: int
{
    case OPEN = 0;
    case ON_PROGRESS = 1;
    case FINISH = 2;
    case CLOSED = 3;

    /**
     * Get status label
     * 
     * @return string
     */
    public function getLabel(): string
    {
        return match($this) {
            self::OPEN => 'Open',
            self::ON_PROGRESS => 'On Progress',
            self::FINISH => 'Finish',
            self::CLOSED => 'Closed',
        };
    }

    /**
     * Get all status values
     * 
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}

