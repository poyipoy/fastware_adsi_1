<?php

namespace App\Enums;

enum LeadTimeCategory: string
{
    case Total = 'Total';
    case IT = 'IT';
    case Spareparts = 'Spareparts';
    case Consumable = 'Consumable';
    case GA = 'GA';
    case Subcont = 'Subcont';

    public static function labels(): array
    {
        return array_map(
            static fn (self $category): string => $category->value,
            self::cases()
        );
    }
}

