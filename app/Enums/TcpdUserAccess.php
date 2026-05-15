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
            'Logistic Foreman',
            'Admin Cutting Sheet (ACS)',
            'Delivery Staff',
            'Feeder Operator',
            'Warehouse Staff',
            'PPC Staff',
            'Feeder Staff',
        ];
    }

    private static function aryaJobs(): array
    {
        return [
            'Cutting Leader',
            'Cutting Operator',
            'QC Foreman',
            'HT Leader',
            'HT Admin',
            'HT Operator',
            'Maintenance Operator',
            'MC Custom Leader',
            'MC Custom Staff',
            'MC Custom Operator',
            'Machining Operator',
            'MC Leader',
            'MC Operator',
            'Bubut Operator',
            'CT MC Foreman',
            'CT MC Operator',
            'MC Custom Sec Head',
            'Operator MC',
            'Prod HT & QC Sec Head',
        ];
    }

    private static function mugiJobs(): array
    {
        return [
            'HT Leader',
            'HT Admin',
            'HT Operator',
            'AKU LUCU',
            'Maintenance Operator',
            'CT MC Foreman',
            'QC Foreman',
            'Cutting Leader',
            'CT MC Operator',
            'Prod HT & QC Sec Head',
            'Operator MC',
        ];
    }

    private static function ragilJobs(): array
    {
        return [
            'MC Leader',
            'MC Custom Operator',
            'MC Custom Staff',
            'Machining Operator',
            'Bubut Operator',
            'MC Custom Leader',
            'MC Custom Sec Head',
            'Operator MC',
        ];
    }

    private static function martinusJobs(): array
    {
        return [
            'Accounting Staff',
            'Accounting Sec Head',
            'Invoicing Staff',
            'HR & Legal Staff',
            'HRGA Staff',
            'Procurement Staff',
            'IT Staff',
            'Dept Head PDCA Proc Inv IT',
            'Finance Sec Head',
            'Finance Staff',
            'Inventory Staff',
            'Finance Support',
        ];
    }

    private static function financeClusterJobs(): array
    {
        return [
            'Accounting Staff',
            'Accounting Sec Head',
            'Invoicing Staff',
            'Finance Sec Head',
            'Finance Staff',
            'Finance Support',
        ];
    }

    private static function executiveAccessJobs(): array
    {
        return [
            'Feeder Operator',
            'Admin Cutting Sheet (ACS)',
            'Logistic Foreman',
            'Delivery Staff',
            'Accounting Sec Head',
            'HR & Legal Staff',
            'Finance Sec Head',
            'HRGA Staff',
            'Accounting Staff',
            'Invoicing Staff',
            'Sales Office Head Region 1',
            'Sales Office Head Region 2',
            'Sales Office Head Region 3&4',
            'Sales Admin',
            'MC Custom Sec Head',
            'Prod HT & QC Sec Head',
            'CT MC Foreman',
            'QC Foreman',
            'Operator MC',
            'PPC Staff',
            'MC Leader',
            'HT Leader',
            'CT MC Operator',
            'Bubut Operator',
            'MC Custom Operator',
            'MC Custom Staff',
            'Machining Operator',
            'HT Admin',
            'Maintenance Operator',
            'HT Operator',
            'Procurement Staff',
            'HALOO',
            'Sales Engineer Region 1',
            'Sales Engineer Region 2',
            'Sales Engineer Region 3',
            'Sales Engineer Region 4',
            'MC Custom Leader',
            'IT Staff',
            'HR, GA, Legal, PDCA, Procurement & IT Se. Head',
            'Cutting Leader',
            'Dept Head PDCA Proc Inv IT',
            'Inventory Staff',
            'Finance Staff',
            'Sales Staff',
            'Finance Support',
            'Feeder Staff',
            'IT Support',
        ];
    }
}

