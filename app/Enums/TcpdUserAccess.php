<?php

namespace App\Enums;

enum TcpdUserAccess: string
{
    case HardiSaputra = 'HARDI SAPUTRA';
    case AbdurRahmanAlFaaiz = 'ABDUR RAHMAN AL FAAIZ';
    case AryaRodjoPrasetyo = 'ARYA RODJO PRASETYO';
    case MugiPramono = 'MUGI PRAMONO';
    case RagilIshaRahmanto = 'RAGIL ISHA RAHMANTO';
    case MartinusCahyoRahasto = 'MARTINUS CAHYO RAHASTO';
    case AdhiPrasetyo = 'ADHI PRASETYO';
    case Richardus = 'RICHARDUS';
    case JessicaPaune = 'JESSICA PAUNE';
    case SitiMariaUlfa = 'SITI MARIA ULFA';

    public function jobPositions(): array
    {
        return match ($this) {
            self::HardiSaputra,
            self::AbdurRahmanAlFaaiz => self::warehouseAndLogisticJobs(),
            self::AryaRodjoPrasetyo => self::aryaJobs(),
            self::MugiPramono => self::mugiJobs(),
            self::RagilIshaRahmanto => self::ragilJobs(),
            self::MartinusCahyoRahasto => self::martinusJobs(),
            self::AdhiPrasetyo,
            self::Richardus => self::financeClusterJobs(),
            self::JessicaPaune,
            self::SitiMariaUlfa => self::executiveAccessJobs(),
        };
    }

    public static function resolve(string $userName): ?self
    {
        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $userName) === 0) {
                return $case;
            }
        }

        return null;
    }

    private static function warehouseAndLogisticJobs(): array
    {
        return [
            'Warehouse Foreman',
            'Admin Cutting Sheet (ACS)',
            'Delivery Staff',
            'Feeder',
            'Warehouse Admin',
            'PPIC Staff',
        ];
    }

    private static function aryaJobs(): array
    {
        return [
            'Cutting Leader',
            'Cutting Operator',
            'Foreman QC',
            'Production HT Leader',
            'production HT Admin',
            'Admin HT & PPC',
            'Production HT Operator',
            'Maintenance Operator',
            'MC Custom & Bubut Leader',
            'MC Custom Staff',
            'Operator Mc. Custom',
            'Operator Machining',
            'Leader MC',
            'MC Operator',
            'Bubut Operator',
        ];
    }

    private static function mugiJobs(): array
    {
        return [
            'Leader HT',
            'Admin HT & PPC',
            'Operator HT',
            'Operator MTN',
            'Foreman CT',
            'Foreman QC',
            'Leader Cutting',
            'Operator CT',
        ];
    }

    private static function ragilJobs(): array
    {
        return [
            'Leader MC',
            'Operator Mc. Custom',
            'MC Custom Staff',
            'Operator Machining',
            'Operator Bubut',
            'Foreman Machining Custom',
        ];
    }

    private static function martinusJobs(): array
    {
        return [
            'Accounting Staff & Kasir',
            'AR Staff',
            'Invoicing Staff',
            'HR & Legal Staff',
            'HRGA & CSR Staff',
            'Procurement Material Staff',
            'IT Staff',
            'PDCA, Inventory, Procurement & IT Sec. Head',
            'HR & GA Section Head',
            'PDCA & Procurement Non Material Staff',
            'Procurement Administration',
            'Inventory Staff',
        ];
    }

    private static function financeClusterJobs(): array
    {
        return [
            'Accounting Staff & Kasir',
            'AR Staff',
            'Invoicing Staff',
            'Accounting Staff',
        ];
    }

    private static function executiveAccessJobs(): array
    {
        return [
            'Feeder',
            'Admin Cutting Sheet (ACS)',
            'Logistic Admin',
            'Delivery Staff',
            'Logistic Foreman',
            'Finance & Accounting Sec. Head',
            'HR & Legal Staff',
            'Finance & Treasury Sec. Head',
            'HRGA & CSR Staff',
            'Accounting Staff & Kasir',
            'Invoicing Staff',
            'SOH Region 1',
            'Sales Admin',
            'Machining Custom Sec. Head',
            'Produksi HT Sec. Head',
            'Foreman CT & MC',
            'Foreman QC',
            'PPIC Staff',
            'Leader MC',
            'Leader HT',
            'Operator CT',
            'Operator Bubut',
            'Operator Mc. Custom',
            'MC Custom Staff',
            'Operator Machining',
            'Admin HT & PPC',
            'Operator MTN',
            'Operator HT',
            'Procurement Material Staff',
            'Sales Engineer Reg 3',
            'Sales Engineer Reg 4',
            'Foreman Machining Custom',
            'Sales Engineer Reg 1',
            'SOH Region 2',
            'AR Staff',
            'IT Staff',
            'Sales Engineer Reg 2',
            'SOH Region 3',
            'SOH Region 4',
            'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
            'HR & GA Section Head',
            'Leader Cutting',
            'PDCA & Procurement Non Material Staff',
            'Procurement Administration',
            'Inventory Section Head',
        ];
    }
}

