<?php

namespace App\Traits;

trait HasDepartmentDefinitions
{
    /**
     * Shared definition for mapping departments to job positions.
     */
    protected function departmentDefinitions(): array
    {
        return [
            'Logistik' => [
                'Logistic Admin',
                'Admin Cutting Sheet (ACS)',
                'Delivery Staff',
                'Feeder',
                'Logistic Foreman',
                'PPIC Staff',
            ],
            'Sales' => [
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
            'Procurement' => [
                'PDCA, Inventory, Procurement & IT Sec. Head',
                'PDCA & Procurement Non Material Staff',
                'Procurement Material Staff',
                'Procurement Administration',
                'Inventory Staff',
                'IT Staff',
            ],
            'Finance, AR, HRGA' => [
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
            'Produksi' => [
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
        ];
    }
}

