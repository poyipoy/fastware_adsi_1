<?php

namespace App\Services\Dashboard;

use App\Enums\TcpdDepartment;
use App\Services\Dashboard\TcpdAccessResolver;
use App\Models\MstAdditionals;
use App\Models\MstSoftSkill;
use App\Models\MstTc;
// use App\Models\TcJobPosition; // DISABLED
use App\Models\TrsPenilaianTc;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/** Cache TTL for TCPD snapshot calculations (in seconds). */
const TCPD_CACHE_TTL = 3600; // 60 minutes

class TcpdDashboardService
{
    public function buildDashboardData(Request $request, ?User $user): array
    {
        $yearOptions = $this->availableCompanyYears();

        $companyYearFromInput = $request->input('company_year_from');
        $companyYearToInput = $request->input('company_year_to');
        [$companyStartDate, $companyEndDate] = $this->resolveYearRange($companyYearFromInput, $companyYearToInput);

        if ($companyStartDate) {
            $companyYearFromInput = $companyStartDate->year;
        }
        if ($companyEndDate) {
            $companyYearToInput = $companyEndDate->year;
        }

        if (!$companyStartDate && !$companyEndDate) {
            $defaultYear = !empty($yearOptions) ? (int) end($yearOptions) : Carbon::now()->year;
            if (!empty($yearOptions)) {
                reset($yearOptions);
            }
            $companyYearFromInput = $defaultYear;
            $companyYearToInput = $defaultYear;
        }

        $jobDateFromInput = $request->input('job_date_from');
        $jobDateToInput = $request->input('job_date_to');

        if (empty($jobDateFromInput) && empty($jobDateToInput)) {
            $defaultYear = !empty($yearOptions) ? (int) end($yearOptions) : Carbon::now()->year;
            $jobDateFromInput = Carbon::create($defaultYear, 1, 1, 0, 0, 0)->startOfDay()->format('Y-m-d');
            $jobDateToInput = Carbon::create($defaultYear, 12, 31, 23, 59, 59)->endOfDay()->format('Y-m-d');
        }

        [$jobStartDate, $jobEndDate] = $this->resolveDateRange($jobDateFromInput, $jobDateToInput);
        if ($jobStartDate) {
            $jobDateFromInput = $jobStartDate->format('Y-m-d');
        }
        if ($jobEndDate) {
            $jobDateToInput = $jobEndDate->format('Y-m-d');
        }

        $normalizeName = static fn ($value) => strtolower(trim((string) $value));

        $rawJobPositions = \App\Models\MstJobPosition::query()
            ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
            ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
            ->select(
                DB::raw('MIN(mst_job_positions.id) as id'),
                'mst_job_positions.position_name as job_position',
                DB::raw('MAX(mst_departments.name) as department')
            )
            ->groupBy('mst_job_positions.position_name')
            ->orderBy('mst_job_positions.position_name')
            ->get()
            ->map(function ($row) {
                $row->job_position = trim((string) $row->job_position);
                return $row;
            });

        $allowedJobNames = $this->resolveAllowedJobNames($user);
        if ($allowedJobNames === []) {
            $rawJobPositions = collect();
        } elseif (is_array($allowedJobNames)) {
            $allowedNormalized = array_map($normalizeName, $allowedJobNames);
            $rawJobPositions = $rawJobPositions->filter(
                fn ($row) => in_array($normalizeName($row->job_position), $allowedNormalized, true)
            );
        }

        $initialOverview = [
            'chartRows' => [],
            'average' => null,
            'hasData' => false,
            'departmentCount' => 0,
            'years' => [],
            'mode' => 'aggregate',
        ];

        if ($rawJobPositions->isEmpty()) {
            return [
                'jobPositions' => collect(),
                'selectedJobPositionId' => null,
                'selectedJobPositionName' => null,
                'competencyRows' => [],
                'userCountByJobPosition' => 0,
                'userSummaries' => [],
                'totalPercentage' => null,
                'departmentSummaries' => collect(),
                'companyOverview' => $initialOverview,
                'yearOptions' => $yearOptions,
                'companyYearFrom' => $companyYearFromInput,
                'companyYearTo' => $companyYearToInput,
                'jobDateFrom' => $jobDateFromInput,
                'jobDateTo' => $jobDateToInput,
                'selectedDepartment' => null,
                'jobDepartmentOptions' => [],
                'shouldPrefetchCompany' => true,
                'shouldPrefetchDepartments' => true,
                'shouldPrefetchJob' => false,
            ];
        }

        $jobDepartments = collect();
        $grouped = $rawJobPositions->groupBy(function($row) {
             return $row->department ?: 'Uncategorized';
        });

        foreach ($grouped as $departmentName => $jobs) {
            $options = $jobs->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'name' => $row->job_position,
                ];
            })->values();

            if ($options->isNotEmpty()) {
                $jobDepartments->push([
                    'department' => $departmentName,
                    'job_positions' => $options->all(),
                ]);
            }
        }

        if ($jobDepartments->isEmpty() && $rawJobPositions->isNotEmpty()) {
            $jobDepartments->push([
                'department' => 'All',
                'job_positions' => $rawJobPositions
                    ->map(fn ($row) => [
                        'id' => (int) $row->id,
                        'name' => $row->job_position,
                    ])
                    ->values()
                    ->all(),
                'is_fallback' => true,
            ]);
        }

        if ($jobDepartments->isEmpty()) {
            return [
                'jobPositions' => collect(),
                'selectedJobPositionId' => null,
                'selectedJobPositionName' => null,
                'competencyRows' => [],
                'userCountByJobPosition' => 0,
                'userSummaries' => [],
                'totalPercentage' => null,
                'departmentSummaries' => collect(),
                'companyOverview' => $initialOverview,
                'yearOptions' => $yearOptions,
                'companyYearFrom' => $companyYearFromInput,
                'companyYearTo' => $companyYearToInput,
                'jobDateFrom' => $jobDateFromInput,
                'jobDateTo' => $jobDateToInput,
                'selectedDepartment' => null,
                'jobDepartmentOptions' => [],
                'shouldPrefetchCompany' => true,
                'shouldPrefetchDepartments' => true,
                'shouldPrefetchJob' => false,
            ];
        }

        $requestedDepartment = $request->input('department');
        $selectedDepartmentGroup = $jobDepartments->first(function ($group) use ($requestedDepartment) {
            return $requestedDepartment !== null && $group['department'] === $requestedDepartment;
        }) ?? $jobDepartments->first();

        $selectedDepartment = $selectedDepartmentGroup['department'] ?? null;
        $jobPositionsForDepartment = collect($selectedDepartmentGroup['job_positions'] ?? []);

        $requestedJobPositionId = $request->input('job_position_id');
        $selectedJobEntry = null;

        if ($requestedJobPositionId !== null && $requestedJobPositionId !== '') {
            $selectedJobEntry = $jobPositionsForDepartment->firstWhere('id', (int) $requestedJobPositionId);

            if (!$selectedJobEntry) {
                foreach ($jobDepartments as $group) {
                    $match = collect($group['job_positions'] ?? [])->firstWhere('id', (int) $requestedJobPositionId);
                    if ($match) {
                        $selectedDepartment = $group['department'];
                        $jobPositionsForDepartment = collect($group['job_positions'] ?? []);
                        $selectedJobEntry = $match;
                        break;
                    }
                }
            }
        }

        if (!$selectedJobEntry) {
            $selectedJobEntry = $jobPositionsForDepartment->first();
        }

        if (!$selectedJobEntry) {
            return [
                'jobPositions' => collect(),
                'selectedJobPositionId' => null,
                'selectedJobPositionName' => null,
                'competencyRows' => [],
                'userCountByJobPosition' => 0,
                'userSummaries' => [],
                'totalPercentage' => null,
                'departmentSummaries' => collect(),
                'companyOverview' => $initialOverview,
                'yearOptions' => $yearOptions,
                'companyYearFrom' => $companyYearFromInput,
                'companyYearTo' => $companyYearToInput,
                'jobDateFrom' => $jobDateFromInput,
                'jobDateTo' => $jobDateToInput,
                'selectedDepartment' => $selectedDepartment,
                'jobDepartmentOptions' => $jobDepartments->values()->all(),
                'shouldPrefetchCompany' => true,
                'shouldPrefetchDepartments' => true,
                'shouldPrefetchJob' => false,
            ];
        }

        $selectedJobPositionId = (int) ($selectedJobEntry['id'] ?? null);
        $selectedJobPositionName = $selectedJobEntry['name'] ?? null;

        $jobPositions = $jobPositionsForDepartment
            ->map(fn ($job) => (object) [
                'id' => (int) $job['id'],
                'job_position' => $job['name'],
            ]);

        return [
            'jobPositions' => $jobPositions,
            'selectedJobPositionId' => $selectedJobPositionId,
            'selectedJobPositionName' => $selectedJobPositionName,
            'competencyRows' => [],
            'userCountByJobPosition' => 0,
            'userSummaries' => [],
            'totalPercentage' => null,
            'departmentSummaries' => collect(),
            'companyOverview' => $initialOverview,
            'yearOptions' => $yearOptions,
            'companyYearFrom' => $companyYearFromInput,
            'companyYearTo' => $companyYearToInput,
            'jobDateFrom' => $jobDateFromInput,
            'jobDateTo' => $jobDateToInput,
            'selectedDepartment' => $selectedDepartment,
            'jobDepartmentOptions' => $jobDepartments->values()->all(),
            'shouldPrefetchCompany' => true,
            'shouldPrefetchDepartments' => true,
            'shouldPrefetchJob' => $selectedJobPositionId !== null,
            'jobDateRange' => [
                'from' => $jobDateFromInput,
                'to' => $jobDateToInput,
            ],
        ];
    }

    public function getCompetencyPayload(Request $request): array
    {
        $jobPositionId = (int) $request->input('job_position_id');
        $jobPosition = \App\Models\MstJobPosition::select('position_name as job_position')->find($jobPositionId);

        if (!$jobPosition) {
            throw (new ModelNotFoundException('Job position not found.'))->setModel(\App\Models\MstJobPosition::class, $jobPositionId);
        }

        $departmentFilter = $request->input('department');
        if ($departmentFilter) {
            $dbMappings = \App\Models\MstJobPosition::query()
                ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
                ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
                ->select('mst_job_positions.position_name as job_position', 'mst_departments.name as department')
                ->distinct()
                ->get();
            $departmentDefinitions = [];
            foreach ($dbMappings as $mapping) {
                $dept = $mapping->department ?: 'Uncategorized';
                $departmentDefinitions[$dept][] = trim((string)$mapping->job_position);
            }
            if (array_key_exists($departmentFilter, $departmentDefinitions)) {
                $normalize = static fn ($value) => strtolower(trim((string) $value));
                $allowedJobs = collect($departmentDefinitions[$departmentFilter] ?? [])
                    ->map($normalize)
                    ->filter()
                    ->values();
                if ($allowedJobs->isNotEmpty() && !$allowedJobs->contains($normalize($jobPosition->job_position))) {
                    throw new InvalidArgumentException('Job position tidak sesuai dengan departemen yang dipilih.');
                }
            }
        }

        [$startDate, $endDate] = $this->resolveDateRange(
            $request->input('date_from'),
            $request->input('date_to')
        );

        $snapshot = $this->buildTcpdSnapshot($jobPosition->job_position, $startDate, $endDate);

        return [
            'job_position' => $jobPosition->job_position,
            'qty' => $snapshot['qty'],
            'competencies' => $snapshot['competencies'],
            'user_summaries' => $snapshot['userSummaries'],
            'total_percentage' => $snapshot['totalPercentage'],
        ];
    }

    public function getCompanyPayload(Request $request, ?User $user): array
    {
        $yearOptions = $this->availableCompanyYears();

        [$startDate, $endDate] = $this->resolveYearRange(
            $request->input('year_from'),
            $request->input('year_to')
        );

        if (!$startDate && !$endDate) {
            $defaultYear = !empty($yearOptions) ? (int) end($yearOptions) : Carbon::now()->year;
            $startDate = Carbon::create($defaultYear, 1, 1, 0, 0, 0)->startOfDay();
            $endDate = Carbon::create($defaultYear, 12, 31, 23, 59, 59)->endOfDay();
        }

        // Build a cache key scoped to this user's access level and date range.
        $userId = $user ? $user->id : 'public';
        $fromKey = $startDate ? $startDate->format('Ymd') : 'all';
        $toKey   = $endDate   ? $endDate->format('Ymd')   : 'all';
        $companyCacheKey = "tcpd_company_{$userId}_{$fromKey}_{$toKey}";

        return Cache::remember($companyCacheKey, TCPD_CACHE_TTL, function () use ($startDate, $endDate, $user) {
            $dbMappings = \App\Models\MstJobPosition::query()
                ->leftJoin('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
                ->whereRaw('LOWER(mst_job_positions.position_name) NOT LIKE ?', ['%head%'])
                ->select('mst_job_positions.position_name as job_position', 'mst_departments.name as department')
                ->distinct()
                ->get();
            $departmentDefinitions = [];
            foreach ($dbMappings as $mapping) {
                if (empty($mapping->department)) {
                    continue;
                }
                $dept = $mapping->department;
                $departmentDefinitions[$dept][] = trim((string)$mapping->job_position);
            }

            $allowedJobNames = $this->resolveAllowedJobNames($user);
            if (is_array($allowedJobNames)) {
                $allowedJobNamesSet = array_map(static fn ($value) => strtolower(trim((string) $value)), $allowedJobNames);
                $filteredDepartmentDefinitions = [];
                foreach ($departmentDefinitions as $deptName => $jobNames) {
                    $filteredJobs = collect($jobNames)->filter(function ($jobName) use ($allowedJobNamesSet) {
                        return in_array(strtolower(trim((string) $jobName)), $allowedJobNamesSet, true);
                    })->values()->all();
                    if (!empty($filteredJobs)) {
                        $filteredDepartmentDefinitions[$deptName] = $filteredJobs;
                    }
                }
            } else {
                $filteredDepartmentDefinitions = $departmentDefinitions;
            }

            $allJobNames = collect($filteredDepartmentDefinitions)
                ->flatten()
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $jobSnapshotData = $this->buildTcpdJobData($allJobNames, $startDate, $endDate);
            $departmentSummaries = $this->buildDepartmentSummaries($filteredDepartmentDefinitions, $jobSnapshotData);
            $companyOverview = $this->buildCompanyOverview($departmentSummaries, $jobSnapshotData['years'] ?? []);
            
            $insights = $this->buildCompanyInsights($allJobNames, $jobSnapshotData);
            $trainingEffectiveness = $this->buildTrainingEffectiveness($allJobNames, $jobSnapshotData['years'] ?? []);

            return [
                'company_chart_rows' => $companyOverview['chartRows'],
                'company_average' => $companyOverview['average'],
                'company_years' => $companyOverview['years'],
                'company_chart_mode' => $companyOverview['mode'],
                'company_has_data' => $companyOverview['hasData'],
                'company_department_count' => $companyOverview['departmentCount'],
                'department_summaries' => $departmentSummaries,
                'insights' => $insights,
                'training_effectiveness' => $trainingEffectiveness,
            ];
        });
    }

    /**
     * Flush all TCPD-related cache entries.
     * Call this after a new assessment is saved so that the next dashboard load is fresh.
     */
    public function clearTcpdCache(): void
    {
        // Flush all keys matching the tcpd_ prefix via cache tags (Redis) or a manual pattern (file).
        // We use a tag-less approach here for maximum driver compatibility.
        Cache::flush(); // Flushes all app cache. Suitable for apps using a dedicated cache store for TCPD.
    }

    private function resolveAllowedJobNames(?User $user): ?array
    {
        return TcpdAccessResolver::resolve($user);
    }

    private function buildCompanyInsights(array $allJobNames, array $jobSnapshotData): array
    {
        $jobRankings = [];
        $competencyIssues = [];

        foreach ($allJobNames as $jobName) {
            $snapshot = $jobSnapshotData['aggregate'][$jobName] ?? null;
            if (!$snapshot || !($snapshot['hasTotalPercentage'] ?? false)) {
                continue;
            }

            $users = [];
            if (isset($snapshot['userSummaries']) && is_array($snapshot['userSummaries'])) {
                foreach ($snapshot['userSummaries'] as $summary) {
                    if (empty($summary['id']) || $summary['id'] === 'average') {
                        continue;
                    }
                    $users[] = [
                        'id'   => $summary['id'],
                        'name' => $summary['name'],
                        'tc'   => $summary['tc_percentage'],
                        'sk'   => $summary['sk_percentage'],
                        'ad'   => $summary['ad_percentage'],
                    ];
                }
            }
            usort($users, fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));

            $jobRankings[] = [
                'job_position' => $jobName,
                'percentage'   => $snapshot['totalPercentage'],
                'employees'    => $users,
            ];

            foreach ($snapshot['competencies'] ?? [] as $comp) {
                if (($comp['qty'] ?? 0) > 0) {
                    $id = $comp['type'] . '_' . $comp['id'];
                    if (!isset($competencyIssues[$id])) {
                        $competencyIssues[$id] = [
                            'name'      => $comp['name'],
                            'type'      => $comp['type'],
                            'qty'       => 0,
                            'employees' => [],
                        ];
                    }
                    $competencyIssues[$id]['qty'] += $comp['qty'];
                    // Aggregate employee detail across job positions
                    foreach ($comp['employees'] ?? [] as $emp) {
                        $uid = (int) ($emp['id'] ?? 0);
                        if (!isset($competencyIssues[$id]['employees'][$uid])) {
                            $competencyIssues[$id]['employees'][$uid] = $emp;
                        }
                    }
                }
            }
        }

        // Re-index employees arrays
        foreach ($competencyIssues as &$issue) {
            $issue['employees'] = array_values($issue['employees']);
            usort($issue['employees'], fn($a, $b) => strcmp($a['name'] ?? '', $b['name'] ?? ''));
        }
        unset($issue);

        usort($jobRankings, fn ($a, $b) => $b['percentage'] <=> $a['percentage']);
        $topJobs = array_slice($jobRankings, 0, 3);

        usort($competencyIssues, fn ($a, $b) => $b['qty'] <=> $a['qty']);
        $criticalFocus = array_slice($competencyIssues, 0, 3);

        return [
            'top_jobs'       => $topJobs,
            'critical_focus' => $criticalFocus,
        ];
    }

    private function buildTrainingEffectiveness(array $allJobNames, array $years): array
    {
        if (empty($years) || empty($allJobNames)) {
            return [];
        }

        $jobPositionIds = \App\Models\MstJobPosition::whereIn('position_name', $allJobNames)->pluck('id')->toArray();
        if (empty($jobPositionIds)) {
            return [];
        }

        $costData = \App\Models\TcPeopleDevelopment::whereIn('tahun_aktual', $years)
            ->whereIn('status_1', [2, 3]) // Proses or Disetujui
            ->whereIn('id_job_position', $jobPositionIds)
            ->select('tahun_aktual', \Illuminate\Support\Facades\DB::raw('SUM(biaya) as total_biaya'))
            ->groupBy('tahun_aktual')
            ->get();

        $trainingCosts = [];
        foreach ($years as $year) {
            $found = $costData->first(fn($item) => (int)$item->tahun_aktual === (int)$year);
            $trainingCosts[(string)$year] = $found ? (float)$found->total_biaya : 0.0;
        }

        return $trainingCosts;
    }

    private function resolveDateRange(?string $from, ?string $to): array
    {
        $start = null;
        $end = null;

        try {
            if ($from) {
                $start = Carbon::parse($from)->startOfDay();
            }
        } catch (\Throwable) {
            $start = null;
        }

        try {
            if ($to) {
                $end = Carbon::parse($to)->endOfDay();
            }
        } catch (\Throwable) {
            $end = null;
        }

        if ($start && $end && $start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function resolveYearRange(?string $from, ?string $to): array
    {
        $start = null;
        $end = null;

        try {
            if ($from && is_numeric($from)) {
                $start = Carbon::create((int) $from, 1, 1, 0, 0, 0)->startOfDay();
            }
        } catch (\Throwable) {
            $start = null;
        }

        try {
            if ($to && is_numeric($to)) {
                $end = Carbon::create((int) $to, 12, 31, 23, 59, 59)->endOfDay();
            }
        } catch (\Throwable) {
            $end = null;
        }

        if ($start && $end && $start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    private function extractYearSegments(?Carbon $startDate, ?Carbon $endDate): array
    {
        if (!$startDate && !$endDate) {
            return [];
        }

        $startYear = $startDate ? (int) $startDate->copy()->startOfYear()->year : null;
        $endYear = $endDate ? (int) $endDate->copy()->startOfYear()->year : null;

        if ($startYear === null && $endYear !== null) {
            return [$endYear];
        }

        if ($startYear !== null && $endYear === null) {
            return [$startYear];
        }

        if ($startYear === null || $endYear === null) {
            return [];
        }

        if ($startYear > $endYear) {
            [$startYear, $endYear] = [$endYear, $startYear];
        }

        return range($startYear, $endYear);
    }

    private function applyDateConstraint(Builder $query, ?Carbon $startDate, ?Carbon $endDate): void
    {
        if (!$startDate && !$endDate) {
            return;
        }

        $table = (new TrsPenilaianTc())->getTable();
        if (Schema::hasColumn($table, 'tahun_penilaian')) {
            // Check if the range represents a whole calendar year (Jan 1 to Dec 31)
            $isWholeYear = false;
            if ($startDate && $endDate) {
                $isWholeYear = $startDate->month === 1 && $startDate->day === 1 && $endDate->month === 12 && $endDate->day === 31;
            }

            if ($isWholeYear) {
                $startYear = $startDate->year;
                $endYear = $endDate->year;
                $query->whereBetween('tahun_penilaian', [$startYear, $endYear]);
                return;
            }
        }

        static $dateColumns = null;

        if ($dateColumns === null) {
            $dateColumns = collect(['created_at', 'modified_updated'])
                ->filter(fn ($column) => Schema::hasColumn($table, $column))
                ->values()
                ->all();
        }

        if (empty($dateColumns)) {
            return;
        }

        $query->where(function ($outer) use ($dateColumns, $startDate, $endDate) {
            foreach ($dateColumns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $outer->{$method}(function ($inner) use ($column, $startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $inner->whereBetween($column, [$startDate, $endDate]);
                    } elseif ($startDate) {
                        $inner->where($column, '>=', $startDate);
                    } else {
                        $inner->where($column, '<=', $endDate);
                    }
                });
            }
        });
    }

    private function availableCompanyYears(): array
    {
        $table = (new TrsPenilaianTc())->getTable();
        $years = collect();

        if (Schema::hasColumn($table, 'tahun_penilaian')) {
            $years = $years->merge(
                TrsPenilaianTc::selectRaw('DISTINCT tahun_penilaian as year')
                    ->whereNotNull('tahun_penilaian')
                    ->pluck('year')
            );
        } else {
            if (Schema::hasColumn($table, 'created_at')) {
                $years = $years->merge(
                    TrsPenilaianTc::selectRaw('DISTINCT YEAR(created_at) as year')
                        ->whereNotNull('created_at')
                        ->pluck('year')
                );
            }

            if (Schema::hasColumn($table, 'modified_updated')) {
                $years = $years->merge(
                    TrsPenilaianTc::selectRaw('DISTINCT YEAR(modified_updated) as year')
                        ->whereNotNull('modified_updated')
                        ->pluck('year')
                );
            }
        }


        $cleanYears = $years
            ->filter(fn ($year) => $year !== null)
            ->map(fn ($year) => (int) $year)
            ->filter(fn ($year) => $year > 0)
            ->unique()
            ->sort()
            ->values();

        if ($cleanYears->isEmpty()) {
            $cleanYears = collect([Carbon::now()->year]);
        }

        return $cleanYears->all();
    }

    private function buildCompanyOverview(array $departmentSummaries, array $years): array
    {
        $years = array_map('intval', $years);
        sort($years);

        $rows = [];
        $departmentAverages = [];
        $companyYearTotals = [];

        foreach ($departmentSummaries as $department) {
            $departmentName = $department['department'] ?? 'Department';
            $entries = $department['entries'] ?? [];
            $totalEntry = collect($entries)->first(fn ($entry) => ($entry['is_total'] ?? false) === true);

            if (!$totalEntry) {
                $rows[] = [
                    'label' => $departmentName,
                    'percentage' => null,
                    'has_data' => false,
                    'values' => [],
                    'is_company' => false,
                ];
                continue;
            }

            $percentage = isset($totalEntry['percentage']) && is_numeric($totalEntry['percentage'])
                ? round((float) $totalEntry['percentage'], 2)
                : null;

            if ($percentage !== null) {
                $departmentAverages[] = $percentage;
            }

            $values = [];
            foreach ($totalEntry['values'] ?? [] as $value) {
                $key = isset($value['key']) ? (string) $value['key'] : null;
                if (!$key) {
                    continue;
                }

                $valuePercentage = isset($value['percentage']) && is_numeric($value['percentage'])
                    ? round((float) $value['percentage'], 2)
                    : null;

                $values[] = [
                    'key' => $key,
                    'label' => $value['label'] ?? $key,
                    'year' => $value['year'] ?? null,
                    'percentage' => $valuePercentage,
                    'has_data' => $valuePercentage !== null,
                ];

                if ($valuePercentage !== null && $key !== 'all') {
                    $companyYearTotals[$key][] = $valuePercentage;
                }
            }

            if (empty($values)) {
                $values[] = [
                    'key' => 'all',
                    'label' => 'All',
                    'year' => null,
                    'percentage' => $percentage,
                    'has_data' => $percentage !== null,
                ];
            }

            $rows[] = [
                'label' => $departmentName,
                'percentage' => $percentage,
                'has_data' => $percentage !== null,
                'values' => $values,
                'is_company' => false,
            ];
        }

        $companyAverage = !empty($departmentAverages)
            ? round(array_sum($departmentAverages) / count($departmentAverages), 2)
            : null;

        $companyValues = [];
        if (!empty($years)) {
            foreach ($years as $year) {
                $key = (string) $year;
                $yearBucket = $companyYearTotals[$key] ?? [];
                $yearAverage = !empty($yearBucket)
                    ? round(array_sum($yearBucket) / count($yearBucket), 2)
                    : null;
                $companyValues[] = [
                    'key' => $key,
                    'label' => (string) $year,
                    'year' => $year,
                    'percentage' => $yearAverage,
                    'has_data' => $yearAverage !== null,
                ];
            }
        }

        if (empty($companyValues)) {
            $companyValues[] = [
                'key' => 'all',
                'label' => 'All',
                'year' => null,
                'percentage' => $companyAverage,
                'has_data' => $companyAverage !== null,
            ];
        }

        array_unshift($rows, [
            'label' => 'Company',
            'percentage' => $companyAverage,
            'has_data' => $companyAverage !== null,
            'values' => $companyValues,
            'is_company' => true,
        ]);

        $departmentCount = count(array_filter($rows, fn ($row) => !$row['is_company'] && $row['has_data']));

        return [
            'chartRows' => $rows,
            'average' => $companyAverage ?? 0.0,
            'hasData' => $companyAverage !== null,
            'departmentCount' => $departmentCount,
            'years' => $years,
            'mode' => !empty($years) ? 'yearly' : 'aggregate',
        ];
    }

    private function buildTcpdSnapshot(string $jobPositionName, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        // Build a deterministic cache key from the job position name and date range.
        $fromKey = $startDate ? $startDate->format('Ymd') : 'all';
        $toKey   = $endDate   ? $endDate->format('Ymd')   : 'all';
        $cacheKey = 'tcpd_snapshot_' . md5($jobPositionName) . "_{$fromKey}_{$toKey}";

        return Cache::remember($cacheKey, TCPD_CACHE_TTL, function () use ($jobPositionName, $startDate, $endDate) {
            return $this->buildTcpdSnapshotInner($jobPositionName, $startDate, $endDate);
        });
    }

    private function buildTcpdSnapshotInner(string $jobPositionName, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $jobPositionIds = \App\Models\MstJobPosition::where('position_name', $jobPositionName)->pluck('id');

        if ($jobPositionIds->isEmpty()) {
            return [
                'qty' => 0, 'competencies' => [], 'userSummaries' => [],
                'totalPercentage' => 0.0, 'hasTotalPercentage' => false,
            ];
        }

        $userIds = \Illuminate\Support\Facades\DB::table('user_job_positions')
            ->join('mst_job_positions', 'user_job_positions.mst_job_position_id', '=', 'mst_job_positions.id')
            ->where('mst_job_positions.position_name', $jobPositionName)
            ->distinct()
            ->pluck('user_job_positions.user_id')
            ->values();

        $userCount = $userIds->count();
        $userLookup = $userIds->isEmpty()
            ? collect()
            : User::whereIn('id', $userIds)->get(['id', 'name'])->keyBy('id');

        $getCompetencies = function (string $model, string $nameCol, string $valueCol, string $type) use ($jobPositionIds) {
            return app($model)->whereIn('id_job_position', $jobPositionIds)
                ->select('id', DB::raw("$nameCol as name"), DB::raw("$valueCol as standard"))
                ->get()
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'name' => $row->name,
                    'standard' => $row->standard !== null ? (float) $row->standard : null,
                    'type' => $type,
                ])
                ->values();
        };

        $technical = $getCompetencies(MstTc::class, 'keterangan_tc', 'nilai', 'technical');
        $softSkills = $getCompetencies(MstSoftSkill::class, 'keterangan_sk', 'nilai', 'soft_skill');
        $additionals = $getCompetencies(MstAdditionals::class, 'keterangan_ad', 'nilai', 'additional');

        $competencies = collect()->concat($technical)->concat($softSkills)->concat($additionals)
            ->filter(fn ($row) => isset($row['name']) && trim((string) $row['name']) !== '')
            ->values();

        if ($competencies->isEmpty() || $userIds->isEmpty()) {
            return [
                'qty' => $userCount, 'competencies' => [], 'userSummaries' => [],
                'totalPercentage' => 0.0, 'hasTotalPercentage' => false,
            ];
        }

        $sumStandards = fn ($collection) => $collection->reduce(fn ($sum, $row) => $sum + (is_numeric($row['standard']) ? (float) $row['standard'] : 0), 0.0);
        $technicalStandardsSum = $sumStandards($technical);
        $softSkillStandardsSum = $sumStandards($softSkills);
        $additionalStandardsSum = $sumStandards($additionals);

        $userTotals = [];
        foreach ($userIds as $userId) {
            $userTotals[(int) $userId] = ['technical' => 0.0, 'soft_skill' => 0.0, 'additional' => 0.0];
        }

        $scoreRowsQuery = TrsPenilaianTc::whereIn('id_user', $userIds)
            ->select('id_user', 'id_tc', 'id_sk', 'id_ad', 'nilai_tc', 'nilai_sk', 'nilai_ad');
        $this->applyDateConstraint($scoreRowsQuery, $startDate, $endDate);
        $scoreRows = $scoreRowsQuery->get();

        $technicalSet = $technical->pluck('id')->flip();
        $softSkillSet = $softSkills->pluck('id')->flip();
        $additionalSet = $additionals->pluck('id')->flip();

        foreach ($scoreRows as $scoreRow) {
            $userId = (int) $scoreRow->id_user;
            if (!isset($userTotals[$userId])) {
                continue;
            }

            if ($scoreRow->id_tc !== null && isset($technicalSet[$scoreRow->id_tc]) && is_numeric($scoreRow->nilai_tc)) {
                $userTotals[$userId]['technical'] += (float) $scoreRow->nilai_tc;
            }
            if ($scoreRow->id_sk !== null && isset($softSkillSet[$scoreRow->id_sk]) && is_numeric($scoreRow->nilai_sk)) {
                $userTotals[$userId]['soft_skill'] += (float) $scoreRow->nilai_sk;
            }
            if ($scoreRow->id_ad !== null && isset($additionalSet[$scoreRow->id_ad]) && is_numeric($scoreRow->nilai_ad)) {
                $userTotals[$userId]['additional'] += (float) $scoreRow->nilai_ad;
            }
        }

        $userSummaries = $userIds->map(function ($userId) use ($userTotals, $userLookup, $technicalStandardsSum, $softSkillStandardsSum, $additionalStandardsSum) {
            $totals = $userTotals[(int) $userId] ?? ['technical' => 0.0, 'soft_skill' => 0.0, 'additional' => 0.0];

            // Cap at 100%: nilai aktual tidak boleh melebihi standar dalam hitungan persentase
            $calcPercentage = fn ($total, $standardSum) => $standardSum > 0 ? min(100.0, round(($total / $standardSum) * 100, 2)) : null;

            return [
                'id' => (int) $userId,
                'name' => optional($userLookup->get($userId))->name ?? ('User ' . $userId),
                'tc_percentage' => $calcPercentage($totals['technical'], $technicalStandardsSum),
                'sk_percentage' => $calcPercentage($totals['soft_skill'], $softSkillStandardsSum),
                'ad_percentage' => $calcPercentage($totals['additional'], $additionalStandardsSum),
            ];
        })->values()->all();

        $userSummariesCollection = collect($userSummaries);
        if ($userSummariesCollection->isNotEmpty()) {
            $avg_tc = $userSummariesCollection->pluck('tc_percentage')->filter(fn ($v) => $v !== null)->avg();
            $avg_sk = $userSummariesCollection->pluck('sk_percentage')->filter(fn ($v) => $v !== null)->avg();
            $avg_ad = $userSummariesCollection->pluck('ad_percentage')->filter(fn ($v) => $v !== null)->avg();

            array_unshift($userSummaries, [
                'id' => 'average',
                'name' => $jobPositionName,
                'tc_percentage' => $avg_tc !== null ? round($avg_tc, 2) : null,
                'sk_percentage' => $avg_sk !== null ? round($avg_sk, 2) : null,
                'ad_percentage' => $avg_ad !== null ? round($avg_ad, 2) : null,
            ]);
        }

        // Hitung totalPercentage hanya dari baris user nyata (bukan baris 'average' yang di-prepend)
        // agar tidak terjadi double-counting nilai rata-rata.
        $allPercentages = [];
        foreach ($userSummaries as $summary) {
            // Lewati baris average (id = 'average') yang sudah di-prepend
            if (($summary['id'] ?? null) === 'average') {
                continue;
            }
            if ($summary['tc_percentage'] !== null) {
                $allPercentages[] = $summary['tc_percentage'];
            }
            if ($summary['sk_percentage'] !== null) {
                $allPercentages[] = $summary['sk_percentage'];
            }
            if ($summary['ad_percentage'] !== null) {
                $allPercentages[] = $summary['ad_percentage'];
            }
        }

        // totalPercentage = rata-rata dari semua persentase per user per kategori, max 100%
        $totalPercentage = !empty($allPercentages) ? min(100.0, round(array_sum($allPercentages) / count($allPercentages), 2)) : 0.0;

        $technicalStandards = $technical->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();
        $softSkillStandards = $softSkills->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();
        $additionalStandards = $additionals->mapWithKeys(fn ($r) => [$r['id'] => $r['standard']])->all();

        $belowStandardUsers = ['technical' => [], 'soft_skill' => [], 'additional' => []];
        $userScoresPerCompetency = [];

        // Per-user scores per competency (for employee detail in Area Development)
        $userScoresMapPerCompetency = ['technical' => [], 'soft_skill' => [], 'additional' => []];

        foreach ($scoreRows as $scoreRow) {
            $userId = (int) $scoreRow->id_user;
            if ($scoreRow->id_tc !== null && isset($technicalSet[$scoreRow->id_tc]) && is_numeric($scoreRow->nilai_tc)) {
                $value = (float) $scoreRow->nilai_tc;
                $compId = (int) $scoreRow->id_tc;
                $standard = $technicalStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['technical'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['technical'][$compId][] = $value;
                // Track per-user score for detail
                if (!isset($userScoresMapPerCompetency['technical'][$compId][$userId])) {
                    $userScoresMapPerCompetency['technical'][$compId][$userId] = [];
                }
                $userScoresMapPerCompetency['technical'][$compId][$userId][] = $value;
            }
            if ($scoreRow->id_sk !== null && isset($softSkillSet[$scoreRow->id_sk]) && is_numeric($scoreRow->nilai_sk)) {
                $value = (float) $scoreRow->nilai_sk;
                $compId = (int) $scoreRow->id_sk;
                $standard = $softSkillStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['soft_skill'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['soft_skill'][$compId][] = $value;
                if (!isset($userScoresMapPerCompetency['soft_skill'][$compId][$userId])) {
                    $userScoresMapPerCompetency['soft_skill'][$compId][$userId] = [];
                }
                $userScoresMapPerCompetency['soft_skill'][$compId][$userId][] = $value;
            }
            if ($scoreRow->id_ad !== null && isset($additionalSet[$scoreRow->id_ad]) && is_numeric($scoreRow->nilai_ad)) {
                $value = (float) $scoreRow->nilai_ad;
                $compId = (int) $scoreRow->id_ad;
                $standard = $additionalStandards[$compId] ?? null;
                if ($standard !== null && $value < $standard) {
                    $belowStandardUsers['additional'][$compId][$userId] = true;
                }
                $userScoresPerCompetency['additional'][$compId][] = $value;
                if (!isset($userScoresMapPerCompetency['additional'][$compId][$userId])) {
                    $userScoresMapPerCompetency['additional'][$compId][$userId] = [];
                }
                $userScoresMapPerCompetency['additional'][$compId][$userId][] = $value;
            }
        }

        $rows = $competencies->map(function (array $row) use ($userScoresPerCompetency, $belowStandardUsers, $userScoresMapPerCompetency, $userLookup, $jobPositionName) {
            $scores = $userScoresPerCompetency[$row['type']][$row['id']] ?? [];
            $average = !empty($scores) ? round(array_sum($scores) / count($scores), 2) : null;

            // Build employees list: users below standard for this competency
            $belowUserIds = array_keys($belowStandardUsers[$row['type']][$row['id']] ?? []);
            $employees = [];
            foreach ($belowUserIds as $uid) {
                $uid = (int) $uid;
                $userScores = $userScoresMapPerCompetency[$row['type']][$row['id']][$uid] ?? [];
                $actualVal = !empty($userScores) ? round(array_sum($userScores) / count($userScores), 2) : null;
                $employees[] = [
                    'id'           => $uid,
                    'name'         => optional($userLookup->get($uid))->name ?? ('User ' . $uid),
                    'job_position' => $jobPositionName,
                    'actual'       => $actualVal,
                    'standard'     => $row['standard'],
                ];
            }
            usort($employees, fn($a, $b) => strcmp($a['name'], $b['name']));

            $mentors = app(\App\Services\Competency\CompetencyAssessmentService::class)->getEmployeesMeetingStandard($row['type'], (int) $row['id']);

            return [
                'id'        => $row['id'],
                'name'      => $row['name'],
                'type'      => $row['type'],
                'standard'  => $row['standard'],
                'average'   => $average,
                'qty'       => count($belowUserIds),
                'employees' => $employees,
                'mentors'   => $mentors,
            ];
        })->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();

        return [
            'qty' => $userCount,
            'competencies' => $rows,
            'userSummaries' => $userSummaries,
            'totalPercentage' => $totalPercentage,
            'hasTotalPercentage' => true,
        ];
    }

    private function extractSnapshotPercentage(?array $snapshot): ?float
    {
        if (!$snapshot) {
            return null;
        }

        $hasTotal = $snapshot['hasTotalPercentage'] ?? false;
        if (!$hasTotal) {
            return null;
        }

        $value = $snapshot['totalPercentage'] ?? null;
        if (!is_numeric($value)) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function buildTcpdJobData(array $jobNames, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $availableJobPositions = \App\Models\MstJobPosition::query()
            ->where('is_active', true)
            ->distinct()
            ->pluck('position_name')
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $availableLookup = array_flip($availableJobPositions);

        $normalized = collect($jobNames)
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '' && array_key_exists($name, $availableLookup))
            ->unique()
            ->values()
            ->all();

        $years = array_map('intval', $this->extractYearSegments($startDate, $endDate));
        $aggregateSnapshots = [];
        $yearSnapshots = [];

        foreach ($normalized as $jobName) {
            $aggregateSnapshots[$jobName] = $this->buildTcpdSnapshot($jobName, $startDate, $endDate);

            if (!empty($years)) {
                foreach ($years as $year) {
                    $yearStart = Carbon::create($year, 1, 1, 0, 0, 0)->startOfDay();
                    $yearEnd = Carbon::create($year, 12, 31, 23, 59, 59)->endOfDay();
                    $yearSnapshots[$jobName][$year] = $this->buildTcpdSnapshot($jobName, $yearStart, $yearEnd);
                }
            }
        }

        return [
            'years' => $years,
            'aggregate' => $aggregateSnapshots,
            'per_year' => $yearSnapshots,
        ];
    }

    private function buildDepartmentSummaries(array $departmentDefinitions, array $jobSnapshotData): array
    {
        $years = array_map('intval', $jobSnapshotData['years'] ?? []);
        sort($years);

        $aggregateSnapshots = $jobSnapshotData['aggregate'] ?? [];
        $perYearSnapshots = $jobSnapshotData['per_year'] ?? [];

        $summaries = [];

        foreach ($departmentDefinitions as $departmentName => $jobNames) {
            $jobNames = collect($jobNames ?? [])
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->filter(fn ($name) => array_key_exists($name, $aggregateSnapshots))
                ->unique()
                ->values()
                ->all();

            if (empty($jobNames)) {
                $summaries[] = [
                    'department' => $departmentName,
                    'entries' => [],
                    'overall' => null,
                    'has_total_data' => false,
                    'years' => $years,
                ];
                continue;
            }

            $entries = [];
            $overallContributions = [];
            $departmentYearTotals = [];

            foreach ($jobNames as $jobName) {
                $snapshot = $aggregateSnapshots[$jobName] ?? null;
                $percentage = $this->extractSnapshotPercentage($snapshot);
                $hasData = $percentage !== null;

                $values = [];
                if (!empty($years)) {
                    foreach ($years as $year) {
                        $yearSnapshot = $perYearSnapshots[$jobName][$year] ?? null;
                        $yearPercentage = $this->extractSnapshotPercentage($yearSnapshot);
                        $values[] = [
                            'key' => (string) $year,
                            'label' => (string) $year,
                            'year' => $year,
                            'percentage' => $yearPercentage,
                            'has_data' => $yearPercentage !== null,
                        ];
                        if ($yearPercentage !== null) {
                            $departmentYearTotals[$year][] = $yearPercentage;
                        }
                    }
                }

                if (empty($values)) {
                    $values[] = [
                        'key' => 'all',
                        'label' => 'All',
                        'year' => null,
                        'percentage' => $percentage,
                        'has_data' => $hasData,
                    ];
                }

                if ($hasData) {
                    $overallContributions[] = $percentage;
                }

                $entries[] = [
                    'label' => $jobName,
                    'percentage' => $percentage,
                    'has_data' => $hasData,
                    'values' => $values,
                ];
            }

            if (empty($entries)) {
                $summaries[] = [
                    'department' => $departmentName,
                    'entries' => [],
                    'overall' => null,
                    'has_total_data' => false,
                    'years' => $years,
                ];
                continue;
            }

            $overall = !empty($overallContributions)
                ? round(array_sum($overallContributions) / count($overallContributions), 2)
                : null;

            $totalValues = [];
            if (!empty($years)) {
                foreach ($years as $year) {
                    $yearBucket = $departmentYearTotals[$year] ?? [];
                    $yearAverage = !empty($yearBucket)
                        ? round(array_sum($yearBucket) / count($yearBucket), 2)
                        : null;
                    $totalValues[] = [
                        'key' => (string) $year,
                        'label' => (string) $year,
                        'year' => $year,
                        'percentage' => $yearAverage,
                        'has_data' => $yearAverage !== null,
                    ];
                }
            }

            if (empty($totalValues)) {
                $totalValues[] = [
                    'key' => 'all',
                    'label' => 'All',
                    'year' => null,
                    'percentage' => $overall,
                    'has_data' => $overall !== null,
                ];
            }

            array_unshift($entries, [
                'label' => 'Total',
                'percentage' => $overall,
                'is_total' => true,
                'has_data' => $overall !== null,
                'values' => $totalValues,
            ]);

            $summaries[] = [
                'department' => $departmentName,
                'entries' => $entries,
                'overall' => $overall,
                'has_total_data' => $overall !== null,
                'years' => $years,
            ];
        }

        return $summaries;
    }

    private function departmentDefinitions(): array
    {
        $definitions = [];
        foreach (TcpdDepartment::cases() as $department) {
            $definitions[$department->value] = $department->jobPositions();
        }

        return $definitions;
    }
}

