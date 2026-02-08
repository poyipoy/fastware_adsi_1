<?php

namespace App\Enums;

enum ProcurementCategory: string
{
    case IT = 'IT';
    case Subcont = 'Subcont';
    case Consumable = 'Consumable';
    case RepairMaintenance = 'Repair Maintenance';
    case Utility = 'Utility';
    case HRGA = 'HRGA';
    case MaterialCost = 'Material Cost';
    case IndirectMaterial = 'Indirect Material';
    case Others = 'Others';

    public static function labels(): array
    {
        return array_map(
            static fn (self $category): string => $category->value,
            self::cases()
        );
    }

    public static function withTotalLabel(): array
    {
        return array_merge(['Total'], self::labels());
    }
}

