<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\DashboardController as WebDashboardController;
// use App\Models\TcJobPosition; // DISABLED
use App\Services\HR\TcpdDashboardAccessService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends WebDashboardController
{
    /**
     * Return the aggregated TCPD dashboard data for Android clients.
     */
    public function tcpdOverview(Request $request): JsonResponse
    {
        $accessScope = app(TcpdDashboardAccessService::class)->scope($request->user(), true);
        $allowedJobPositionIds = $accessScope['job_position_ids'];
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

        if (! $companyStartDate && ! $companyEndDate) {
            $defaultYear = ! empty($yearOptions) ? (int) end($yearOptions) : Carbon::now()->year;

            $companyStartDate = Carbon::create($defaultYear, 1, 1, 0, 0, 0)->startOfDay();
            $companyEndDate = Carbon::create($defaultYear, 12, 31, 23, 59, 59)->endOfDay();
            $companyYearFromInput = $defaultYear;
            $companyYearToInput = $defaultYear;
        }

        $jobDateFromInput = $request->input('job_date_from');
        $jobDateToInput = $request->input('job_date_to');

        if (empty($jobDateFromInput) && empty($jobDateToInput)) {
            $now = Carbon::now();
            $jobDateFromInput = $now->copy()->startOfYear()->format('Y-m-d');
            $jobDateToInput = $now->copy()->endOfYear()->format('Y-m-d');
        }

        [$jobStartDate, $jobEndDate] = $this->resolveDateRange($jobDateFromInput, $jobDateToInput);

        if ($jobStartDate) {
            $jobDateFromInput = $jobStartDate->format('Y-m-d');
        }

        if ($jobEndDate) {
            $jobDateToInput = $jobEndDate->format('Y-m-d');
        }

        $normalizeName = static fn ($value) => mb_strtolower(trim((string) $value));

        $rawJobPositions = \App\Models\MstJobPosition::query()
            ->whereIn('id', $allowedJobPositionIds)
            ->where('is_active', true)
            ->whereRaw('LOWER(position_name) NOT LIKE ?', ['%head%'])
            ->select(DB::raw('MIN(id) as id'), 'position_name as job_position')
            ->groupBy('position_name')
            ->orderBy('position_name')
            ->get()
            ->map(function ($row) {
                $row->job_position = trim((string) $row->job_position);

                return $row;
            })
            ->filter(fn ($row) => $row->job_position !== '')
            ->unique(fn ($row) => mb_strtolower($row->job_position))
            ->values();

        if ($rawJobPositions->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'Belum ada data job position yang dapat ditampilkan.',
                'data' => [
                    'filters' => [
                        'year_options' => $yearOptions,
                        'company_year_from' => $companyYearFromInput,
                        'company_year_to' => $companyYearToInput,
                        'job_date_from' => $jobDateFromInput,
                        'job_date_to' => $jobDateToInput,
                        'departments' => [],
                        'selected_department' => null,
                        'job_positions' => [],
                        'selected_job_position_id' => null,
                        'selected_job_position_name' => null,
                    ],
                    'company_overview' => $this->emptyCompanyOverview(),
                    'department_summaries' => [],
                    'job_summary' => null,
                    'prefetch_flags' => [
                        'company' => false,
                        'departments' => false,
                        'job' => false,
                    ],
                ],
            ]);
        }

        $departmentDefinitions = $this->departmentDefinitions();
        $jobPositionsByName = $rawJobPositions->keyBy(fn ($row) => $normalizeName($row->job_position));

        $jobDepartments = collect();

        foreach ($departmentDefinitions as $departmentName => $jobNames) {
            $options = collect($jobNames)
                ->map(function ($jobName) use ($jobPositionsByName, $normalizeName) {
                    $normalized = $normalizeName($jobName);
                    $matched = $jobPositionsByName->get($normalized);

                    if (! $matched) {
                        return null;
                    }

                    return [
                        'id' => (int) $matched->id,
                        'name' => $matched->job_position,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            if (! empty($options)) {
                $jobDepartments->push([
                    'department' => $departmentName,
                    'job_positions' => $options,
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

        $jobDepartmentOptions = $jobDepartments->map(function (array $group) {
            $positions = collect($group['job_positions'] ?? [])
                ->map(function ($job) {
                    return [
                        'id' => (int) ($job['id'] ?? 0),
                        'name' => (string) ($job['name'] ?? ''),
                    ];
                })
                ->filter(fn ($job) => $job['id'] > 0 && $job['name'] !== '')
                ->values()
                ->all();

            return [
                'department' => $group['department'] ?? null,
                'job_positions' => $positions,
                'is_fallback' => (bool) ($group['is_fallback'] ?? false),
            ];
        })->filter(fn ($group) => $group['department'] !== null && ! empty($group['job_positions']))->values();

        if ($jobDepartmentOptions->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'filters' => [
                        'year_options' => $yearOptions,
                        'company_year_from' => $companyYearFromInput,
                        'company_year_to' => $companyYearToInput,
                        'job_date_from' => $jobDateFromInput,
                        'job_date_to' => $jobDateToInput,
                        'departments' => [],
                        'selected_department' => null,
                        'job_positions' => [],
                        'selected_job_position_id' => null,
                        'selected_job_position_name' => null,
                    ],
                    'company_overview' => $this->emptyCompanyOverview(),
                    'department_summaries' => [],
                    'job_summary' => null,
                    'prefetch_flags' => [
                        'company' => false,
                        'departments' => false,
                        'job' => false,
                    ],
                ],
            ]);
        }

        $requestedDepartment = $request->input('department');
        if ($requestedDepartment !== null
            && $requestedDepartment !== ''
            && ! $jobDepartmentOptions->contains(fn ($group) => $group['department'] === $requestedDepartment)) {
            abort(403, 'Department berada di luar scope Dashboard TCPD Anda.');
        }

        $requestedJobPositionId = $request->input('job_position_id');
        if ($requestedJobPositionId !== null
            && $requestedJobPositionId !== ''
            && ! in_array((int) $requestedJobPositionId, $allowedJobPositionIds, true)) {
            abort(403, 'Job position berada di luar scope Dashboard TCPD Anda.');
        }

        $selectedDepartmentGroup = $jobDepartmentOptions->first(function ($group) use ($requestedDepartment) {
            return $requestedDepartment !== null && $group['department'] === $requestedDepartment;
        }) ?? $jobDepartmentOptions->first();

        $selectedDepartment = $selectedDepartmentGroup['department'] ?? null;
        $jobPositionsForDepartment = collect($selectedDepartmentGroup['job_positions'] ?? []);

        $selectedJobEntry = null;

        if ($requestedJobPositionId !== null && $requestedJobPositionId !== '') {
            $selectedJobEntry = $jobPositionsForDepartment->firstWhere('id', (int) $requestedJobPositionId);

            if (! $selectedJobEntry) {
                $fallback = $jobDepartmentOptions->first(function ($group) use ($requestedJobPositionId) {
                    return collect($group['job_positions'] ?? [])->contains(function ($job) use ($requestedJobPositionId) {
                        return (int) ($job['id'] ?? 0) === (int) $requestedJobPositionId;
                    });
                });

                if ($fallback) {
                    $selectedDepartment = $fallback['department'];
                    $jobPositionsForDepartment = collect($fallback['job_positions'] ?? []);
                    $selectedJobEntry = $jobPositionsForDepartment->firstWhere('id', (int) $requestedJobPositionId);
                }
            }
        }

        if (! $selectedJobEntry) {
            $selectedJobEntry = $jobPositionsForDepartment->first();
        }

        $selectedJobPositionId = $selectedJobEntry['id'] ?? null;
        $selectedJobPositionName = $selectedJobEntry['name'] ?? null;

        $jobPositionsList = $jobPositionsForDepartment
            ->map(function ($job) {
                return [
                    'id' => (int) ($job['id'] ?? 0),
                    'name' => (string) ($job['name'] ?? ''),
                ];
            })
            ->filter(fn ($job) => $job['id'] > 0 && $job['name'] !== '')
            ->values()
            ->all();

        $jobSummary = null;

        if ($selectedJobPositionName) {
            $snapshot = $this->buildTcpdSnapshot(
                $selectedJobPositionName,
                $jobStartDate,
                $jobEndDate,
                $allowedJobPositionIds,
            );

            $jobSummary = [
                'job_position' => $selectedJobPositionName,
                'job_position_id' => $selectedJobPositionId !== null ? (int) $selectedJobPositionId : null,
                'qty' => $snapshot['qty'] ?? 0,
                'total_percentage' => $snapshot['totalPercentage'] ?? null,
                'has_total_percentage' => $snapshot['hasTotalPercentage'] ?? false,
                'competencies' => $snapshot['competencies'] ?? [],
                'user_summaries' => $snapshot['userSummaries'] ?? [],
                'date_range' => [
                    'from' => $jobStartDate ? $jobStartDate->format('Y-m-d') : null,
                    'to' => $jobEndDate ? $jobEndDate->format('Y-m-d') : null,
                ],
            ];
        }

        $scopedDepartmentDefinitions = $jobDepartmentOptions
            ->mapWithKeys(fn ($group) => [
                $group['department'] => collect($group['job_positions'])
                    ->pluck('name')
                    ->filter()
                    ->values()
                    ->all(),
            ])
            ->all();

        $allJobNames = collect($scopedDepartmentDefinitions)
            ->flatten()
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $jobSnapshotData = ! empty($allJobNames)
            ? $this->buildTcpdJobData(
                $allJobNames,
                $companyStartDate,
                $companyEndDate,
                $allowedJobPositionIds,
            )
            : ['years' => [], 'aggregate' => [], 'per_year' => []];

        $departmentSummaries = $this->buildDepartmentSummaries($scopedDepartmentDefinitions, $jobSnapshotData);
        $companyOverview = $this->buildCompanyOverview($departmentSummaries, $jobSnapshotData['years'] ?? []);

        return response()->json([
            'success' => true,
            'data' => [
                'filters' => [
                    'year_options' => $yearOptions,
                    'company_year_from' => $companyYearFromInput,
                    'company_year_to' => $companyYearToInput,
                    'job_date_from' => $jobDateFromInput,
                    'job_date_to' => $jobDateToInput,
                    'departments' => $jobDepartmentOptions->values()->all(),
                    'selected_department' => $selectedDepartment,
                    'job_positions' => $jobPositionsList,
                    'selected_job_position_id' => $selectedJobPositionId !== null ? (int) $selectedJobPositionId : null,
                    'selected_job_position_name' => $selectedJobPositionName,
                ],
                'company_overview' => $companyOverview,
                'department_summaries' => $departmentSummaries,
                'job_summary' => $jobSummary,
                'prefetch_flags' => [
                    'company' => true,
                    'departments' => true,
                    'job' => $jobSummary !== null,
                ],
            ],
        ]);
    }

    protected function emptyCompanyOverview(): array
    {
        return [
            'chartRows' => [],
            'average' => null,
            'hasData' => false,
            'departmentCount' => 0,
            'years' => [],
            'mode' => 'aggregate',
        ];
    }
}
