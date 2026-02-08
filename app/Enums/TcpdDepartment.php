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
                'Logistic Admin',
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder',
                'Logistic Foreman',
                'PPIC Staff',
            ],
            self::Sales => [
                'Sales Admin',
                'SOH Region 1',
                'Sales Engineer Reg 1',
                'SOH Region 2',
                'Sales Engineer Reg 2',
                'SOH Region 3',
                'Sales Engineer Reg 3',
                'SOH Region 4',
                'Sales Engineer Reg 4',
            ],
            self::Procurement => [
                'PDCA, Inventory, Procurement & IT Sec. Head',
                'PDCA & Procurement Non Material Staff',
                'Procurement Material Staff',
                'Procurement Administration',
                'Inventory Staff',
                'IT Staff',
            ],
            self::FinanceArHrga => [
                'HR & GA Section Head',
                'HR & Legal Staff',
                'HRGA & CSR Staff',
                'Finance & Accounting Sec. Head',
                'Finance Admin',
                'Finance & Treasury Sec. Head',
                'Invoicing Staff',
                'AR Staff',
                'Accounting Staff & Kasir',
            ],
            self::Produksi => [
                'Produksi HT Sec. Head',
                'Foreman CT',
                'Foreman QC',
                'Leader HT',
                'Operator CT',
                'Admin HT & PPC',
                'Operator MTN',
                'Operator HT',
                'Leader Cutting',
                'Machining Custom Sec. Head',
                'Leader MC',
                'Operator Bubut',
                'Operator Mc. Custom',
                'MC Custom Staff',
                'Operator Machining',
                'Foreman Machining Custom',
                'Foreman MC',
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

