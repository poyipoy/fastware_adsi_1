<?php
/*
namespace App\Enums; 

enum TcpdUserAccess: string
{
    case HardiSaputra = 'HARDI SAPUTRA';
    case AbdurRahmanAlFaaiz = 'ABDUR RAHMAN AL FAAIZ';
    case AryaRodjoPrasetyo = 'ARY RODJO PRASETYO'; // Fixed mismatch (was ARYA)
    case MugiPramono = 'MUGI PRAMONO';
    case RagilIshaRahmanto = 'RAGIL ISHA RAHMANTO';
    case MartinusCahyoRahasto = 'MARTINUS CAHYO RAHASTO';
    case AdhiPrasetyo = 'ADHI PRASETIYO'; // Fixed mismatch (was PRASETYO)
    case Richardus = 'RICHARDUS CHRISTIAN';
    case JessicaPaune = 'JESSICA PAUNE';
    case SitiMariaUlfa = 'SITI MARIA ULFA';

    public function jobPositions(): array
    {
        return match ($this) {
            self::HardiSaputra,
            self::AbdurRahmanAlFaaiz => $this->jobsByDepartments(['Logistic & Warehouse']),
            
            self::AryaRodjoPrasetyo => $this->jobsByDepartments(['Production']),
            
            self::MugiPramono => $this->jobsBySections([
                'Production Cutting',
                'Production Heat Treatment',
                'Technical Support QC & Maintenance'
            ]),
            
            self::RagilIshaRahmanto => $this->jobsBySections([
                'Production MC & Machining Custom'
            ]),
            
            self::MartinusCahyoRahasto => $this->jobsByDepartments([
                'Finance, Accounting & HRGA',
                'PDCA, Inventory, Procurement & IT'
            ]),
            
            self::AdhiPrasetyo,
            self::Richardus => $this->jobsByDepartments(['Finance, Accounting & HRGA']),
            
            self::JessicaPaune,
            self::SitiMariaUlfa => $this->allActiveJobs(),
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

    private function jobsByDepartments(array $departmentNames): array
    {
        return \App\Models\MstJobPosition::query()
            ->join('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
            ->whereIn('mst_departments.name', $departmentNames)
            ->where('mst_job_positions.is_active', true)
            ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
            ->pluck('mst_job_positions.position_name')
            ->map(fn($n) => trim($n))
            ->unique()
            ->values()
            ->all();
    }

    private function jobsBySections(array $sectionNames): array
    {
        return \App\Models\MstJobPosition::query()
            ->join('mst_sections', 'mst_job_positions.section_id', '=', 'mst_sections.id')
            ->whereIn('mst_sections.name', $sectionNames)
            ->where('mst_job_positions.is_active', true)
            ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
            ->pluck('mst_job_positions.position_name')
            ->map(fn($n) => trim($n))
            ->unique()
            ->values()
            ->all();
    }

    private function allActiveJobs(): array
    {
        return \App\Models\MstJobPosition::query()
            ->where('mst_job_positions.is_active', true)
            ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
            ->pluck('mst_job_positions.position_name')
            ->map(fn($n) => trim($n))
            ->unique()
            ->values()
            ->all();
    }
}
