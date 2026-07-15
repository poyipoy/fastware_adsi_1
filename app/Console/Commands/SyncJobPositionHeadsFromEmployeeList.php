<?php

namespace App\Console\Commands;

// use App\Models\TcJobPosition; // DISABLED
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncJobPositionHeadsFromEmployeeList extends Command
{
    protected $signature = 'hr:sync-job-position-heads
        {file=Employee All Dept(Employee All Dept).csv : Employee CSV path}
        {--apply : Persist updates to tc_job_positions}
        {--only-missing : Do not overwrite existing mapping fields}
        {--report= : Export current active job-position mapping to CSV}';

    protected $description = 'Sync tc_job_positions head mapping from the Employee All Dept CSV.';

    private const BLOCKS = [
        ['start' => 0, 'department' => 'Sales'],
        ['start' => 6, 'department' => 'Productions'],
        ['start' => 12, 'department' => 'Finance Accounting HRGA'],
        ['start' => 18, 'department' => 'Procurement Inventory IT HRGA'],
        ['start' => 24, 'department' => 'Logistic'],
    ];

    private const EMPLOYEE_NAME_ALIASES = [
        'RICHARDUS CHRISTIAN' => 'RICHARDUSCHRISTIANWIDJOJO',
        'HAERULIKHSAN' => 'HAERULIHSAN',
    ];

    private const APPROVER_NAME_ALIASES = [
        'ARYRODJOPRASETIYO' => 'ARY RODJO PRASETYO',
        'JUNJOHAMIN' => 'JUN JOHAMIN PD',
        'RICHARDUSCHRISTIANWIDJOJO' => 'RICHARDUS CHRISTIAN',
    ];

    private const JOB_POSITION_ALIASES = [
        'DEPTHEADFINACCHRGA' => 'FINANCEACCOOUNTINGHRGADEPARTEMENTHEAD',
        'DEPTHEADPDCAPROCINVIT' => 'PDCAPROCUREMENTINVENTORYITDEPARTEMENTHEAD',
        'FEEDEROPERATOR' => 'FEEDER',
        'FINANCESTAFF' => 'ARSTAFF',
        'HTADMIN' => 'HEATTREATMENTADMIN',
        'HTLEADER' => 'HEATTREATMENTLEADER',
        'INVOICINGSTAFF' => 'INVOICESTAFF',
        'LOGISTICWAREHOUSESECHEAD' => 'LOGISTICSSECTIONHEAD',
        'MAINTENANCEOPERATOR' => 'MAINTENANCE',
        'MCCUSTOMLEADER' => 'MACHININGLEADER',
        'MCCUSTOMOPERATOR' => 'MACHININGCUSTOMOPERATOR',
        'MCCUSTOMSECHEAD' => 'MACHININGMACHININGCUSTOMSECTIONHEAD',
        'MCCUSTOMSTAFF' => 'MACHININGCUSTOMSTAFF',
        'MCLEADER' => 'MACHININGLEADER',
        'PRODHTQCSECHEAD' => 'HEATTREATMENTLEADER',
        'PROCUREMENTSTAFF' => 'PDCAPROCUREMENTSTAFF',
        'PRODUCTIONDEPTHEAD' => 'PRODUCTIONDEPARTMENTHEAD',
        'QCFOREMAN' => 'QUALITYCONTROL',
        'QCOPERATOR' => 'QUALITYCONTROL',
        'SALESDEPT12' => 'SALESOFFICEHEADREGION12DEPARTEMENTHEAD',
        'SALESDEPT34' => 'SALESOFFICEHEADREGION34DEPARTEMENTHEAD',
        'SALESDIVHEADLOGISTICSDEPTHEAD' => 'SALESDIVISION',
        'SALESOFFICEHEADREGION12' => 'SALESOFFICEHEADREGION12DEPARTEMENTHEAD',
        'SALESOFFICEHEADREGION34' => 'SALESOFFICEHEADREGION34DEPARTEMENTHEAD',
    ];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('file'));

        if (!is_file($path)) {
            $this->error("Employee CSV not found: {$path}");
            return self::FAILURE;
        }

        $users = User::query()->pluck('name')
            ->mapWithKeys(fn($name) => [$this->key($name) => $name]);

        $sourceRows = $this->readEmployeeRows($path, $users);
        $sourceByEmployee = $sourceRows->groupBy(fn($row) => $row['employee_key']);
        $sourceByJob = $sourceRows->groupBy(fn($row) => $row['job_key']);

        $jobPositions = TcJobPosition::query()
            ->with('user')
            ->where('status', 1)
            ->orderBy('job_position')
            ->orderBy('id')
            ->get();

        $apply = (bool) $this->option('apply');
        $onlyMissing = (bool) $this->option('only-missing');

        $changed = 0;
        $mapped = 0;
        $unmapped = [];
        $ambiguous = [];

        foreach ($jobPositions as $jobPosition) {
            $result = $this->findMapping($jobPosition, $sourceByEmployee, $sourceByJob);

            if ($result['status'] === 'ambiguous') {
                $ambiguous[] = $this->resultRow($jobPosition, $result);
                continue;
            }

            if ($result['status'] === 'unmapped') {
                $unmapped[] = $this->resultRow($jobPosition, $result);
                continue;
            }

            $mapped++;
            $updates = [
                'department' => $this->nullable($result['mapping']['department']),
                'section' => $this->nullable($result['mapping']['section']),
                'department_head_name' => $this->nullable($result['mapping']['department_head_name']),
                'section_head_name' => $this->nullable($result['mapping']['section_head_name']),
            ];

            if ($onlyMissing) {
                $updates = collect($updates)
                    ->filter(fn($_value, $field) => blank($jobPosition->{$field}))
                    ->all();
            }

            if (empty($updates)) {
                continue;
            }

            $jobPosition->fill($updates);

            if (!$jobPosition->isDirty(array_keys($updates))) {
                continue;
            }

            $changed++;
            $this->warn('Ambiguous rows skipped:');
            $this->table($this->reportHeaders(), array_slice($ambiguous, 0, 25));
        }

        if (!empty($unmapped)) {
            $this->warn('Unmapped rows skipped:');
            $this->table($this->reportHeaders(), array_slice($unmapped, 0, 25));
        }

        if (!$apply) {
            $this->line('Dry-run only. Re-run with --apply to persist unambiguous mappings.');
        }

        if ($this->option('report')) {
            $reportPath = $this->exportReport((string) $this->option('report'));
            $this->info("Mapping report exported: {$reportPath}");
        }

        return self::SUCCESS;
    }

    private function readEmployeeRows(string $path, Collection $users): Collection
    {
        $handle = fopen($path, 'r');
        $rows = collect();
        $line = 0;

        while (($columns = fgetcsv($handle, 0, ';')) !== false) {
            $line++;

            if ($line <= 3) {
                continue;
            }

            foreach (self::BLOCKS as $block) {
                $start = $block['start'];
                $sourcePosition = $this->cell($columns, $start + 4);

                if ($sourcePosition === '') {
                    continue;
                }

                $employeeName = $this->cell($columns, $start);
                $sectionHead = $this->resolveApproverName($this->cell($columns, $start + 2), $users);
                $departmentHead = $this->resolveApproverName($this->cell($columns, $start + 3), $users);
                foreach ($this->splitSourcePositions($sourcePosition) as $singleSourcePosition) {
                    $parts = array_map('trim', explode('/', $singleSourcePosition));
                    $jobPosition = $parts[0] ?? '';

                    $rows->push([
                        'line' => $line,
                        'block' => $block['department'],
                        'employee_name' => $employeeName,
                        'employee_key' => $this->key($employeeName),
                        'login' => $this->cell($columns, $start + 1),
                        'source_position' => $singleSourcePosition,
                        'job_position' => $jobPosition,
                        'job_key' => $this->key($jobPosition),
                        'section' => $this->nullable($parts[1] ?? null),
                        'department' => $this->nullable($parts[2] ?? $block['department']),
                        'section_head_name' => $this->nullable($sectionHead),
                        'department_head_name' => $this->nullable($departmentHead),
                    ]);
                }
            }
        }

        fclose($handle);

        return $rows;
    }

    private function findMapping(TcJobPosition $jobPosition, Collection $sourceByEmployee, Collection $sourceByJob): array
    {
        $employeeName = $jobPosition->user->name ?? '';
        $employeeKey = self::EMPLOYEE_NAME_ALIASES[$this->key($employeeName)] ?? $this->key($employeeName);

        if ($employeeKey !== '' && $sourceByEmployee->has($employeeKey)) {
            $mapping = $this->singleMapping($sourceByEmployee->get($employeeKey));

            if ($mapping !== null) {
                return [
                    'status' => 'mapped',
                    'reason' => 'employee',
                    'mapping' => $mapping,
                ];
            }

            return [
                'status' => 'ambiguous',
                'reason' => 'employee has multiple source mappings',
            ];
        }

        $jobKey = $this->key($jobPosition->job_position);
        $sourceJobKey = self::JOB_POSITION_ALIASES[$jobKey] ?? $jobKey;

        if ($sourceByJob->has($sourceJobKey)) {
            $mapping = $this->singleMapping($sourceByJob->get($sourceJobKey));

            if ($mapping !== null) {
                return [
                    'status' => 'mapped',
                    'reason' => $sourceJobKey === $jobKey ? 'job_position' : 'job_position_alias',
                    'mapping' => $mapping,
                ];
            }

            return [
                'status' => 'ambiguous',
                'reason' => 'job position has multiple source mappings',
            ];
        }

        return [
            'status' => 'unmapped',
            'reason' => 'not found in employee CSV',
        ];
    }

    private function singleMapping(Collection $rows): ?array
    {
        $unique = $rows
            ->map(function ($row) {
                return [
                    'source_position' => $row['source_position'],
                    'department' => $row['department'],
                    'section' => $row['section'],
                    'department_head_name' => $row['department_head_name'],
                    'section_head_name' => $row['section_head_name'],
                ];
            })
            ->unique(fn($row) => implode('|', array_map(fn($value) => (string) $value, $row)))
            ->values();

        if ($unique->count() === 1) {
            return $unique->first();
        }

        $heads = $unique
            ->map(fn($row) => [
                'department' => $row['department'],
                'department_head_name' => $row['department_head_name'],
                'section_head_name' => $row['section_head_name'],
            ])
            ->unique(fn($row) => implode('|', array_map(fn($value) => (string) $value, $row)))
            ->values();

        if ($heads->count() !== 1) {
            return null;
        }

        $head = $heads->first();

        return [
            'source_position' => $unique->pluck('source_position')->filter()->unique()->implode('; '),
            'department' => $head['department'],
            'section' => $unique->pluck('section')->filter()->unique()->implode('; ') ?: null,
            'department_head_name' => $head['department_head_name'],
            'section_head_name' => $head['section_head_name'],
        ];
    }

    private function resolveApproverName(string $name, Collection $users): ?string
    {
        if ($name === '') {
            return null;
        }

        $key = $this->key($name);

        if (isset(self::APPROVER_NAME_ALIASES[$key])) {
            return self::APPROVER_NAME_ALIASES[$key];
        }

        return $users->get($key, $name);
    }

    private function resultRow(TcJobPosition $jobPosition, array $result): array
    {
        return [
            $jobPosition->id,
            $jobPosition->job_position,
            $jobPosition->user->name ?? '',
            $result['reason'] ?? '',
            $result['mapping']['source_position'] ?? '',
            $result['mapping']['section_head_name'] ?? '',
            $result['mapping']['department_head_name'] ?? '',
        ];
    }

    private function reportHeaders(): array
    {
        return [
            'ID',
            'DB Job Position',
            'Employee',
            'Reason',
            'Source Position',
            'Ka. Sie',
            'Ka. Dept',
        ];
    }

    private function exportReport(string $path): string
    {
        $fullPath = $this->resolvePath($path);
        $directory = dirname($fullPath);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $handle = fopen($fullPath, 'w');
        fputcsv($handle, [
            'id',
            'job_position',
            'employee',
            'department',
            'section',
            'section_head_name',
            'department_head_name',
            'mapping_status',
        ]);

        TcJobPosition::query()
            ->with('user')
            ->where('status', 1)
            ->orderBy('job_position')
            ->orderBy('id')
            ->get()
            ->each(function (TcJobPosition $jobPosition) use ($handle) {
                fputcsv($handle, [
                    $jobPosition->id,
                    $jobPosition->job_position,
                    $jobPosition->user->name ?? '',
                    $jobPosition->department,
                    $jobPosition->section,
                    $jobPosition->section_head_name,
                    $jobPosition->department_head_name,
                    blank($jobPosition->section_head_name) ? 'UNMAPPED' : 'MAPPED',
                ]);
            });

        fclose($handle);

        return $fullPath;
    }

    private function cell(array $columns, int $index): string
    {
        return trim((string) ($columns[$index] ?? ''));
    }

    private function splitSourcePositions(string $sourcePosition): array
    {
        return collect(preg_split('/\R+/', $sourcePosition) ?: [])
            ->map(fn($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function nullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function key(?string $value): string
    {
        return preg_replace('/[^A-Z0-9]+/', '', mb_strtoupper(trim((string) $value))) ?? '';
    }

    private function resolvePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:[\/\\\\]/', $path) || str_starts_with($path, '/') || str_starts_with($path, '\\')) {
            return $path;
        }

        return base_path($path);
    }
}
