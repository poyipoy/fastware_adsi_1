<?php

namespace App\Enums;

enum TcpdDepartment: string
{
    case Logistik = 'Logistik';
    case Sales = 'Sales';
    case Procurement = 'Procurement';
    case FinanceArHrga = 'Finance, AR, HRGA';
    case Produksi = 'Produksi';

    public function jobPositions(): array
    {
        return match ($this) {
            self::Logistik => [
                'Logistic Foreman',
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder Operator',
                'PPC Staff',
                'Warehouse Staff',
                'Feeder Staff',
            ],
            self::Sales => [
                'Sales Admin',
                'Sales Office Head Region 1',
                'Sales Engineer Region 1',
                'Sales Office Head Region 2',
                'Sales Engineer Region 2',
                'Sales Office Head Region 3&4',
                'Sales Engineer Region 3',
                'Sales Engineer Region 4',
                'Sales Staff',
            ],
            self::Procurement => [
                'Dept Head PDCA Proc Inv IT',
                'Procurement Staff',
                'Inventory Staff',
                'IT Staff',
                'HALOO',
                'IT Support',
            ],
            self::FinanceArHrga => [
                'HRGA Staff',
                'HR & Legal Staff',
                'Accounting Sec Head',
                'Finance Staff',
                'Finance Sec Head',
                'Invoicing Staff',
                'Accounting Staff',
                'Finance Support',
            ],
            self::Produksi => [
                'Prod HT & QC Sec Head',
                'CT MC Foreman',
                'AKU LUCU',
                'Operator Mesin',
                'QC Foreman',
                'HT Leader',
                'CT MC Operator',
                'HT Admin',
                'Maintenance Operator',
                'HT Operator',
                'Cutting Leader',
                'MC Custom Sec Head',
                'MC Leader',
                'Operator MC',
                'Bubut Operator',
                'MC Custom Operator',
                'MC Custom Staff',
                'Machining Operator',
                'MC Custom Leader',
            ],
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $department) => $department->value, self::cases());
    }

    public static function asOptions(): array
    {
        return array_map(
            static fn (self $department) => [
                'label' => $department->value,
                'value' => $department->value,
                'job_positions' => $department->jobPositions(),
            ],
            self::cases()
        );
    }
}

