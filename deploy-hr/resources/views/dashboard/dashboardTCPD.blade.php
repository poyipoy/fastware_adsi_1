@extends('layout')

@section('content')
    <style>
        .dashboard-tcpd .dashboard-card {
            min-height: 260px;
            border-radius: 12px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }

        .dashboard-tcpd .dashboard-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.5rem;
        }

        .dashboard-tcpd .placeholder-chart {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8c9aa5;
            background: repeating-linear-gradient(135deg,
                    rgba(108, 117, 125, 0.08),
                    rgba(108, 117, 125, 0.08) 12px,
                    rgba(108, 117, 125, 0.12) 12px,
                    rgba(108, 117, 125, 0.12) 24px);
            border-radius: 10px;
        }

        .dashboard-tcpd .year-filter-select {
            width: 96px;
            min-width: 72px;
        }

        .dashboard-tcpd .year-filter-label {
            color: #6c757d;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .dashboard-tcpd .department-section-header {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .dashboard-tcpd .department-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        .dashboard-tcpd .department-grid__item {
            display: flex;
        }

        .dashboard-tcpd .department-grid__card {
            width: 100%;
        }

        .dashboard-tcpd .department-empty {
            text-align: center;
            color: #6c757d;
            font-size: 0.875rem;
        }

        .dashboard-tcpd .company-chart {
            height: 400px;
        }

        .dashboard-tcpd .department-chart {
            height: 400px;
        }

        .dashboard-tcpd .department-grid__item--span-2 {
            grid-column: span 2;
        }

        @media (max-width: 992px) {
            .dashboard-tcpd .dashboard-card {
                min-height: 220px;
            }

            .dashboard-tcpd .department-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Scorecards styling */
        .scorecard {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .scorecard:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
        }

        .scorecard-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .scorecard-content {
            flex: 1;
        }

        .scorecard-title {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .scorecard-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0;
            line-height: 1.2;
        }

        .scorecard-subtitle {
            font-size: 0.75rem;
            color: #adb5bd;
            margin-top: 0.25rem;
        }

        /* Modern Table styling */
        .table-custom {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
        }

        .table-custom thead th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
            padding: 1rem;
        }

        .table-custom tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f8f9fa;
            color: #495057;
        }

        .table-custom tbody tr {
            transition: all 0.2s ease;
        }

        .table-custom tbody tr:hover {
            background-color: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.02);
        }

        /* Global Filter Bar */
        .global-filter-bar {
            background: #ffffff;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
    </style>

    <main id="main" class="main">
        <div class="pagetitle mb-4">
            <h1 class="mb-1">Dashboard TCPD</h1>
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">TCPD</li>
                </ol>
            </nav>
        </div>

        <section class="section dashboard">
            <div class="container-fluid dashboard-tcpd">
                @php
                    $jobPositions = collect($jobPositions ?? []);
                    $jobDepartmentOptions = collect($jobDepartmentOptions ?? [])->map(function ($group) {
                        $jobPositionsForGroup = collect($group['job_positions'] ?? [])
                            ->map(function ($job) {
                                return [
                                    'id' => (int) ($job['id'] ?? 0),
                                    'name' => (string) ($job['name'] ?? ''),
                                ];
                            })
                            ->filter(fn($job) => $job['id'] > 0 && $job['name'] !== '')
                            ->values();

                        return [
                            'department' => $group['department'] ?? null,
                            'job_positions' => $jobPositionsForGroup,
                        ];
                    })->filter(fn($group) => !empty($group['department']) && $group['job_positions']->isNotEmpty())->values();

                    $firstDepartmentOption = $jobDepartmentOptions->first();
                    if (!is_array($firstDepartmentOption)) {
                        $firstDepartmentOption = null;
                    }
                    $selectedDepartment = $selectedDepartment ?? ($firstDepartmentOption['department'] ?? null);
                    if ($selectedDepartment && !$jobDepartmentOptions->contains(fn($group) => $group['department'] === $selectedDepartment)) {
                        $selectedDepartment = $firstDepartmentOption['department'] ?? null;
                    }
                    $jobDepartmentData = $jobDepartmentOptions->map(function ($group) {
                        return [
                            'department' => $group['department'],
                            'job_positions' => $group['job_positions']->map(fn($job) => [
                                'id' => $job['id'],
                                'name' => $job['name'],
                            ])->all(),
                        ];
                    })->all();

                    $selectedJobPositionId = $selectedJobPositionId ?? null;
                    $selectedJobPositionName = $selectedJobPositionName ?? null;
                    $competencyRows = $competencyRows ?? [];
                    $userCountByJobPosition = $userCountByJobPosition ?? 0;
                    $totalPercentage = isset($totalPercentage) && is_numeric($totalPercentage) ? (float) $totalPercentage : null;
                    $userSummaries = collect($userSummaries ?? [])->values()->all();
                    $hasChartData = ($totalPercentage !== null) || collect($userSummaries)->some(fn($u) => ($u['tc_percentage'] ?? null) !== null || ($u['sk_percentage'] ?? null) !== null || ($u['ad_percentage'] ?? null) !== null);
                    $evaluatedUsersCount = count($userSummaries);
                    $chartEmptyMessage = $selectedJobPositionName
                        ? 'Data persentase belum tersedia untuk job position ini.'
                        : 'Silakan pilih job position.';

                    $departmentSummaries = collect($departmentSummaries ?? []);

                    $companyOverview = $companyOverview ?? [];
                    $companyChartRows = collect($companyOverview['chartRows'] ?? []);
                    $companyAverage = isset($companyOverview['average']) ? (float) $companyOverview['average'] : 0.0;
                    $companyHasData = (bool) ($companyOverview['hasData'] ?? false);
                    $companyRowsCount = (int) ($companyOverview['departmentCount'] ?? 0);
                    $companyYears = collect($companyOverview['years'] ?? [])->map(fn($year) => (int) $year)->sort()->values();
                    $companyMode = $companyOverview['mode'] ?? ($companyYears->isNotEmpty() ? 'yearly' : 'aggregate');
                    $companyEmptyMessage = $companyHasData ? '' : 'Data persentase departemen belum tersedia.';

                    $yearOptions = collect($yearOptions ?? [])->map(fn($year) => (int) $year)->sort()->values();
                    $companyYearFrom = $companyYearFrom ?? null;
                    $companyYearTo = $companyYearTo ?? null;
                    $jobDateFrom = $jobDateFrom ?? null;
                    $jobDateTo = $jobDateTo ?? null;

                    $prefetchFlags = [
                        'company' => !empty($shouldPrefetchCompany),
                        'departments' => !empty($shouldPrefetchDepartments),
                        'job' => !empty($shouldPrefetchJob),
                    ];

                @endphp
                <!-- Global Filter Bar -->
                <div class="global-filter-bar flex-column align-items-stretch">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-filter-square text-primary fs-5"></i>
                            <h6 class="mb-0 fw-bold">Filter Dashboard</h6>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button id="btn-export-all" type="button" class="btn btn-sm btn-success rounded-pill px-3"
                                title="Export semua data ke Excel (4 Sheet: Departemen, Area Dev, Top Jobs, Crit Focus)">
                                <i class="bi bi-file-earmark-excel me-1"></i> Export Semua (4 Sheet)
                            </button>
                            @if ($canClearTcpdCache ?? false)
                                <button id="btn-refresh-cache" type="button"
                                    class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                    title="Hapus cache dan muat ulang data terbaru">
                                    <i class="bi bi-arrow-repeat me-1"></i> Refresh Data
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Company Filter Section -->
                        <div class="col-lg-5">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <label class="form-label small fw-bold text-muted mb-2">Periode TCPD (Company Level)</label>
                                <form id="company-filter-form" class="d-flex align-items-center gap-2 flex-wrap">
                                    <select id="company-year-from" name="company_year_from"
                                        class="form-select form-select-sm border-primary shadow-sm" style="width: auto;">
                                        @foreach ($yearOptions as $year)
                                            <option value="{{ $year }}" {{ (string) $year === (string) $companyYearFrom ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    <span class="small text-muted fw-semibold">-</span>
                                    <select id="company-year-to" name="company_year_to"
                                        class="form-select form-select-sm border-primary shadow-sm" style="width: auto;">
                                        @foreach ($yearOptions as $year)
                                            <option value="{{ $year }}" {{ (string) $year === (string) $companyYearTo ? 'selected' : '' }}>{{ $year }}</option>
                                        @endforeach
                                    </select>
                                    <div class="ms-auto d-flex gap-2">
                                        <button type="button" id="company-filter-apply"
                                            class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">Terapkan</button>
                                        <button type="button" id="company-filter-reset"
                                            class="btn btn-sm btn-outline-secondary rounded-pill px-3">Reset</button>
                                        <button type="button" id="btn-export-company"
                                            class="btn btn-sm btn-outline-success rounded-pill px-3"
                                            title="Export data Company & Department ke Excel">
                                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>

                        <!-- Job Position Filter Section -->
                        <div class="col-lg-7">
                            <div class="p-3 bg-light rounded-3 h-100">
                                <label class="form-label small fw-bold text-muted mb-2">Area Development (Job Position
                                    Level)</label>
                                @if ($jobDepartmentOptions->isNotEmpty())
                                    <form id="job-filter-form" class="d-flex align-items-center gap-2 flex-wrap">
                                        <select id="job-department" name="department" class="form-select form-select-sm"
                                            style="width: auto; min-width: 120px;">
                                            @foreach ($jobDepartmentOptions as $departmentOption)
                                                <option value="{{ $departmentOption['department'] }}" {{ $departmentOption['department'] === $selectedDepartment ? 'selected' : '' }}>
                                                    {{ $departmentOption['department'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <select id="job_position_id" name="job_position_id" class="form-select form-select-sm"
                                            style="width: auto; min-width: 150px;">
                                            @forelse ($jobPositions as $position)
                                                <option value="{{ $position->id }}" {{ (int) $position->id === (int) $selectedJobPositionId ? 'selected' : '' }}>
                                                    {{ $position->job_position }}
                                                </option>
                                            @empty
                                                <option value="" disabled selected>Job position kosong</option>
                                            @endforelse
                                        </select>
                                        <input type="date" id="job-date-from" class="form-control form-control-sm"
                                            value="{{ $jobDateFrom }}" style="width: auto;">
                                        <span class="small text-muted fw-semibold">-</span>
                                        <input type="date" id="job-date-to" class="form-control form-control-sm"
                                            value="{{ $jobDateTo }}" style="width: auto;">
                                        <div class="ms-auto d-flex gap-2">
                                            <button type="button" id="job-filter-apply"
                                                class="btn btn-sm btn-outline-primary rounded-pill px-3">Terapkan</button>
                                            <button type="button" id="job-filter-reset"
                                                class="btn btn-sm btn-link text-decoration-none">Reset</button>
                                        </div>
                                    </form>
                                @else
                                    <div class="small text-muted">Data job position tidak tersedia.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scorecards Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="scorecard">
                            <div class="scorecard-icon bg-primary text-white bg-opacity-75">
                                <i class="bi bi-graph-up-arrow"></i>
                            </div>
                            <div class="scorecard-content">
                                <div class="scorecard-title">Rata-rata Perusahaan</div>
                                <div class="scorecard-value fs-4" id="scorecard-average">-</div>
                                <div class="scorecard-subtitle">Keseluruhan</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="scorecard">
                            <div class="scorecard-icon bg-info text-white bg-opacity-75">
                                <i class="bi bi-diagram-3"></i>
                            </div>
                            <div class="scorecard-content">
                                <div class="scorecard-title">Dept. Dievaluasi</div>
                                <div class="scorecard-value fs-4" id="scorecard-dept-count">-</div>
                                <div class="scorecard-subtitle">Memiliki Data</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="scorecard">
                            <div class="scorecard-icon bg-success text-white bg-opacity-75">
                                <i class="bi bi-trophy"></i>
                            </div>
                            <div class="scorecard-content">
                                <div class="scorecard-title">Dept. Tertinggi</div>
                                <div class="scorecard-value fs-5" id="scorecard-top-dept">-</div>
                                <div class="scorecard-subtitle" id="scorecard-top-val">Performance</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="scorecard">
                            <div class="scorecard-icon bg-warning text-dark bg-opacity-75">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                            <div class="scorecard-content">
                                <div class="scorecard-title">Perlu Perhatian</div>
                                <div class="scorecard-value fs-5" id="scorecard-lowest-dept">-</div>
                                <div class="scorecard-subtitle" id="scorecard-lowest-val">Dept. Terendah</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Smart Insights Row -->
                <div class="row g-3 mb-4" id="smart-insights-row" style="display: none;">
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100"
                            style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="text-success fw-bold text-uppercase mb-0"><i
                                            class="bi bi-award-fill me-2"></i>Top 5 Job Positions</h6>
                                    <button type="button" id="btn-export-top-jobs"
                                        class="btn btn-sm btn-outline-success rounded-pill px-2"
                                        title="Export Top Jobs ke Excel" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Export
                                    </button>
                                </div>
                                <p class="small text-success text-opacity-75 mb-3" style="font-size: 0.8rem;">Job position
                                    dengan pencapaian tertinggi. <i class="bi bi-hand-index-thumb"></i> Klik untuk melihat
                                    detail karyawan.</p>
                                <div id="insight-top-jobs" class="d-flex flex-column gap-2">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 shadow-sm rounded-4 h-100"
                            style="background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="text-danger fw-bold text-uppercase mb-0"><i
                                            class="bi bi-exclamation-octagon-fill me-2"></i>Critical Focus Area</h6>
                                    <button type="button" id="btn-export-critical-focus"
                                        class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                        title="Export Critical Focus Area ke Excel" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Export
                                    </button>
                                </div>
                                <p class="small text-danger text-opacity-75 mb-3" style="font-size: 0.8rem;">Kompetensi
                                    dengan defisit terbanyak (min. 5 karyawan defisit). <i
                                        class="bi bi-hand-index-thumb"></i> Klik untuk melihat detail karyawan.</p>
                                <div id="insight-critical-focus" class="d-flex flex-column gap-2">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal: Critical Focus Employee Detail -->
                <div class="modal fade" id="criticalFocusModal" tabindex="-1" aria-labelledby="criticalFocusModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title fw-bold" id="criticalFocusModalLabel">
                                    <i class="bi bi-people-fill me-2"></i><span id="cfm-title">Detail Karyawan</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="cfm-badge-row" class="d-flex gap-2 mb-3 flex-wrap"></div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle">
                                        <thead class="table-danger">
                                            <tr>
                                                <th style="width:40px">No.</th>
                                                <th>NPK — Nama Karyawan</th>
                                                <th>Job Position</th>
                                                <th style="width:110px">Nilai Aktual</th>
                                                <th style="width:110px">Standar</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cfm-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal: Top Jobs Employee Detail -->
                <div class="modal fade" id="topJobsModal" tabindex="-1" aria-labelledby="topJobsModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title fw-bold" id="topJobsModalLabel">
                                    <i class="bi bi-people-fill me-2"></i><span id="tjm-title">Detail Karyawan</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="tjm-badge-row" class="d-flex gap-2 mb-3 flex-wrap"></div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle">
                                        <thead class="table-success text-dark">
                                            <tr>
                                                <th style="width:40px">No.</th>
                                                <th>NPK — Nama Karyawan</th>
                                                <th style="width:140px; text-align:center;">Technical</th>
                                                <th style="width:140px; text-align:center;">Soft Skill</th>
                                                <th style="width:140px; text-align:center;">Additional</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tjm-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($canViewTcpdSensitive ?? false)
                <!-- Modal: Key Position Employee Detail -->
                <div class="modal fade" id="keyPositionModal" tabindex="-1" aria-labelledby="keyPositionModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title fw-bold" id="keyPositionModalLabel">
                                    <i class="bi bi-people-fill me-2"></i><span id="kp-tjm-title">Detail Karyawan</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div id="kp-tjm-badge-row" class="d-flex gap-2 mb-3 flex-wrap"></div>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm align-middle">
                                        <thead class="table-primary text-dark">
                                            <tr>
                                                <th style="width:40px">No.</th>
                                                <th>NPK — Nama Karyawan</th>
                                                <th style="width:140px; text-align:center;">Technical</th>
                                                <th style="width:140px; text-align:center;">Soft Skill</th>
                                                <th style="width:140px; text-align:center;">Additional</th>
                                            </tr>
                                        </thead>
                                        <tbody id="kp-tjm-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card dashboard-card level-1 h-100">
                            <div class="card-body">
                                <div class="d-flex flex-column gap-3">
                                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h5 class="card-title mb-2"><i
                                                    class="bi bi-bar-chart-fill text-primary me-2"></i>COMPANY PERFORMANCE
                                            </h5>
                                            <p class="text-muted mb-0 small">Rata-rata performa kompetensi untuk seluruh
                                                perusahaan</p>
                                        </div>
                                        <button type="button" id="btn-export-company"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                            title="Export data Departemen & Company ke Excel">
                                            <i class="bi bi-file-earmark-excel me-1"></i> Export Departemen
                                        </button>
                                    </div>
                                    <div class="mt-1">
                                        <div id="company-chart" class="w-100" style="height: 400px;"></div>
                                        <div id="company-chart-empty" class="text-center text-muted small py-4 d-none"
                                            data-empty-message="{{ $companyEmptyMessage ?: 'Data persentase departemen belum tersedia.' }}">
                                            {{ $companyEmptyMessage ?: 'Data persentase departemen belum tersedia.' }}
                                        </div>
                                    </div>
                                    <div id="company-chart-legend" class="tcpd-legend d-none"></div>
                                    <p id="company-chart-summary" class="text-muted small mb-0 d-none">
                                        <span class="fw-semibold">Company Average:</span>
                                        N/A
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if ($canViewTcpdSensitive ?? false)
                <!-- Key Position Stats Row (Modul 2.1) -->
                <div class="row g-3 mb-4" id="key-position-row" style="display: none;">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 h-100 bg-primary-subtle">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <h6 class="text-primary fw-bold text-uppercase mb-0"><i
                                            class="bi bi-key-fill me-2"></i>Key Position Status</h6>
                                    <span class="badge bg-primary text-white rounded-pill px-3 py-2 shadow-sm"
                                        style="font-weight: 600;" id="kp-total-badge">-</span>
                                </div>
                                <p class="small text-primary text-opacity-75 mb-3" style="font-size: 0.8rem;">Kompetensi
                                    karyawan pada posisi-posisi kunci (key position) perusahaan.</p>
                                <div class="row g-2" id="key-position-stats">
                                    <!-- Populated by JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Training Effectiveness Row -->
                <div class="row g-3 mb-4" id="training-effectiveness-row" style="display: none;">
                    <div class="col-12">
                        <div class="card dashboard-card level-1 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                                    <div>
                                        <h5 class="card-title mb-1"><i
                                                class="bi bi-currency-dollar text-success me-2"></i>TRAINING EFFECTIVENESS
                                            (ROI)</h5>
                                        <p class="text-muted mb-0 small">Korelasi antara Biaya Training Disetujui (Rp) dan
                                            Rata-rata Pemenuhan Kompetensi (%) per Tahun</p>
                                    </div>
                                </div>
                                <div id="effectiveness-chart" class="w-100" style="height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <div class="mt-4">
                    <div class="department-section-header mb-3">
                        <div>


                        </div>
                        {{-- <span class="text-muted small">Klik legend untuk menampilkan atau menyembunyikan job
                            position.</span> --}}
                    </div>
                    <div id="department-grid" class="department-grid"></div>
                    <div id="department-empty" class="department-empty py-4 d-none">
                        Data departemen belum tersedia.
                    </div>
                </div>

                <div class="row g-3 mt-4">
                    <div class="col-12">
                        <div class="card dashboard-card level-3 h-100">
                            <div class="card-body">
                                <div
                                    class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                    <div>
                                        <h5 class="card-title mb-2">Job Position Overview</h5>
                                        {{-- <p class="text-muted mb-0 small">
                                            Persentase pencapaian kompetensi berdasarkan standar TC, SK, dan AD pada job
                                            position terpilih.
                                        </p> --}}
                                    </div>
                                </div>
                                <div class="mt-3 position-relative">
                                    <div id="job-position-chart" class="w-100" style="height: 400px;"></div>
                                    <div id="job-position-chart-empty" class="text-center text-muted small py-4 d-none"
                                        data-empty-message="{{ $chartEmptyMessage }}">
                                        {{ $chartEmptyMessage }}
                                    </div>
                                </div>
                                <div id="job-position-chart-legend" class="tcpd-legend d-none"></div>
                                <p id="job-position-chart-summary" class="text-muted small mb-0 mt-3 d-none"></p>
                                <div id="job-position-user-links" class="d-flex flex-wrap gap-2 mt-3 d-none"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-12">
                        <div class="card dashboard-card level-4 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-2">
                                    <div>
                                        <h5 class="card-title mb-2"><i class="bi bi-list-check text-primary me-2"></i>Area
                                            Development</h5>
                                        <p class="text-muted mb-0 small">
                                            Menampilkan competency, standar, dan jumlah user di bawah standar.
                                        </p>
                                    </div>
                                    <button type="button" id="btn-export-competency"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                        title="Export data Area Development ke Excel">
                                        <i class="bi bi-file-earmark-excel me-1"></i> Export Area Development
                                    </button>
                                </div>

                                <div class="table-responsive mt-3">
                                    <table class="table table-custom table-hover table-borderless align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 60px;">No.</th>
                                                <th>NPK — Nama Karyawan</th>
                                                <th>Competency</th>
                                                <th style="width: 120px;">Aktual</th>
                                                <th style="width: 120px;">Standard</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tcpd-competency-body">
                                            <tr>
                                                <td colspan="5" class="text-center text-muted small py-4">
                                                    {{ $selectedJobPositionName ? 'Memuat data...' : 'Silakan pilih job position untuk melihat data.' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {{-- Pagination Controls --}}
                                <div id="tcpd-competency-pagination"
                                    class="d-flex align-items-center justify-content-between mt-3 d-none flex-wrap gap-2">
                                    <div class="text-muted small" id="tcpd-competency-pagination-info"></div>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="text-muted small mb-0">Baris per halaman:</label>
                                        <select id="tcpd-competency-per-page" class="form-select form-select-sm"
                                            style="width:auto;">
                                            <option value="10" selected>10</option>
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="0">Semua</option>
                                        </select>
                                        <nav>
                                            <ul class="pagination pagination-sm mb-0" id="tcpd-competency-pages"></ul>
                                        </nav>
                                    </div>
                                </div>

                                <p id="tcpd-summary" class="text-muted small mb-0 mt-3 d-none"></p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>



    </main>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
    <script>
        $(document).ready(function () {
            // Hover function for dropdowns
            $('.nav-item.dropdown').hover(function () {
                $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
            }, function () {
                $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
            });
        });
    </script>
    <script>
        // Refresh Data button — clears TCPD 60-minute cache then reloads the page.
        document.addEventListener('DOMContentLoaded', function () {
            const refreshBtn = document.getElementById('btn-refresh-cache');
            if (!refreshBtn) return;

            refreshBtn.addEventListener('click', function () {
                const originalHtml = refreshBtn.innerHTML;
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Memperbarui...';

                fetch('{{ route("dashboardTCPD.clearCache") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            refreshBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Berhasil!';
                            setTimeout(() => window.location.reload(), 800);
                        } else {
                            refreshBtn.innerHTML = originalHtml;
                            refreshBtn.disabled = false;
                            alert('Gagal menghapus cache: ' + (data.message ?? ''));
                        }
                    })
                    .catch(() => {
                        refreshBtn.innerHTML = originalHtml;
                        refreshBtn.disabled = false;
                        alert('Koneksi gagal. Silakan coba lagi.');
                    });
            });
        });
    </script>
    <script>
        /**
         * TCPD Export Button Handlers
         * Each button reads the currently active filter inputs and builds the export URL.
         */
        document.addEventListener('DOMContentLoaded', function () {

            /**
             * Collect current active filter params from the page DOM.
             */
            function getActiveFilters() {
                const params = new URLSearchParams();

                // Company year filters
                const yearFrom = document.getElementById('company-year-from');
                const yearTo = document.getElementById('company-year-to');
                if (yearFrom && yearFrom.value) params.set('company_year_from', yearFrom.value);
                if (yearTo && yearTo.value) params.set('company_year_to', yearTo.value);

                // Also expose as year_from / year_to for company-export endpoint
                if (yearFrom && yearFrom.value) params.set('year_from', yearFrom.value);
                if (yearTo && yearTo.value) params.set('year_to', yearTo.value);

                // Job position filters
                const dept = document.getElementById('job-department');
                const jobPos = document.getElementById('job_position_id');
                const dateFrom = document.getElementById('job-date-from');
                const dateTo = document.getElementById('job-date-to');
                if (dept && dept.value) params.set('department', dept.value);
                if (jobPos && jobPos.value) params.set('job_position_id', jobPos.value);
                if (dateFrom && dateFrom.value) params.set('date_from', dateFrom.value);
                if (dateTo && dateTo.value) params.set('date_to', dateTo.value);

                return params.toString();
            }

            function makeExportUrl(base) {
                const qs = getActiveFilters();
                return qs ? base + '?' + qs : base;
            }

            // Export Semua (4 Sheet)
            const btnExportAll = document.getElementById('btn-export-all');
            if (btnExportAll) {
                btnExportAll.addEventListener('click', function () {
                    window.location.href = makeExportUrl('{{ route("dashboardTCPD.exportAll") }}');
                });
            }

            // Export Company & Department
            const btnExportCompany = document.getElementById('btn-export-company');
            if (btnExportCompany) {
                btnExportCompany.addEventListener('click', function () {
                    window.location.href = makeExportUrl('{{ route("dashboardTCPD.companyExport") }}');
                });
            }

            // Export Area Development (Competency)
            const btnExportCompetency = document.getElementById('btn-export-competency');
            if (btnExportCompetency) {
                btnExportCompetency.addEventListener('click', function () {
                    window.location.href = makeExportUrl('{{ route("dashboardTCPD.export") }}');
                });
            }

            // Export Top Jobs
            const btnExportTopJobs = document.getElementById('btn-export-top-jobs');
            if (btnExportTopJobs) {
                btnExportTopJobs.addEventListener('click', function () {
                    window.location.href = makeExportUrl('{{ route("dashboardTCPD.exportTopJobs") }}');
                });
            }

            // Export Critical Focus Area
            const btnExportCriticalFocus = document.getElementById('btn-export-critical-focus');
            if (btnExportCriticalFocus) {
                btnExportCriticalFocus.addEventListener('click', function () {
                    window.location.href = makeExportUrl('{{ route("dashboardTCPD.exportCriticalFocus") }}');
                });
            }

            // Export Employees (Combined fallback)
            const btnExportEmployees = document.getElementById('btn-export-employees');
            if (btnExportEmployees) {
                btnExportEmployees.addEventListener('click', function () {
                    window.location.href = makeExportUrl('{{ route("dashboardTCPD.exportEmployees") }}');
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5/dist/echarts.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof window.echarts === 'undefined') {
                console.error('ECharts library is not loaded.');
                return;
            }

            const formatNumber = (value, decimals = 2) => {
                if (value === null || value === undefined || value === '') return '-';
                const numeric = Number(value);
                if (Number.isNaN(numeric)) return '-';
                const fixed = numeric.toFixed(decimals);
                return Number(fixed) === Math.trunc(Number(fixed)) ? Number(fixed).toString() : fixed;
            };

            const formatPercent = (value, decimals = 2) => {
                if (value === null || value === undefined || value === '') return 'N/A';
                const numeric = Number(value);
                if (Number.isNaN(numeric)) return 'N/A';
                return `${formatNumber(numeric, decimals)}%`;
            };

            const escapeHtml = (value) => {
                if (value === null || value === undefined) return '';
                return String(value).replace(/[&<>'"\/]/g, (char) => {
                    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                    return entities[char] || char;
                });
            };

            function ensureLegendStyles() {
                if (document.getElementById('tcpd-legend-style')) return;
                const s = document.createElement('style');
                s.id = 'tcpd-legend-style';
                s.textContent = `
                      .tcpd-legend{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}
                      .tcpd-legend__item{display:inline-flex;align-items:center;gap:6px;
                        padding:4px 10px;border:1px solid #dee2e6;border-radius:9999px;
                        background:#fff;color:#495057;cursor:pointer;user-select:none}
                      .tcpd-legend__item.is-off{opacity:.45}
                      .tcpd-legend__dot{width:10px;height:10px;border-radius:50%}
                    `;
                document.head.appendChild(s);
            }

            const hideLegend = (element) => {
                if (!element) return;
                element.classList.add('d-none');
                element.innerHTML = '';
            };

            const TCPD_COLOR_PALETTE = [
                '#2563eb', '#f97316', '#22c55e', '#ec4899', '#8b5cf6', '#f59e0b',
                '#14b8a6', '#0ea5e9', '#6366f1', '#fb7185', '#10b981', '#facc15',
            ];

            const isPlainObject = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

            const deepClone = (value) => {
                if (Array.isArray(value)) return value.map(deepClone);
                if (isPlainObject(value)) {
                    const clone = {};
                    for (const key in value) {
                        clone[key] = deepClone(value[key]);
                    }
                    return clone;
                }
                return value;
            };

            const createSeriesSnapshot = (series = []) => series.map((serie) => {
                const { data = [], ...meta } = serie;
                return {
                    meta: deepClone(meta),
                    data: data.map(item => deepClone(item)),
                };
            });

            const keyFor = (value) => String(value ?? '');

            const attachCategoryLegend = ({ chartInstance, legendElement, categories, colors, seriesSnapshot }) => {
                if (!legendElement) return;
                legendElement.innerHTML = '';

                if (!chartInstance || !Array.isArray(categories) || categories.length === 0 || !Array.isArray(seriesSnapshot) || seriesSnapshot.length === 0) {
                    legendElement.classList.add('d-none');
                    return;
                }

                ensureLegendStyles();
                legendElement.classList.remove('d-none');

                const palette = Array.isArray(colors) && colors.length > 0 ? colors : TCPD_COLOR_PALETTE;
                const visibility = {};
                categories.forEach((name) => { visibility[keyFor(name)] = true; });

                const initialOption = chartInstance.getOption();
                const initialXAxis = initialOption && initialOption.xAxis
                    ? (Array.isArray(initialOption.xAxis) ? initialOption.xAxis : [initialOption.xAxis])
                    : [];
                const axisLabelBases = initialXAxis.map((axis) => ({ ...(axis.axisLabel || {}) }));

                const buildUpdatedSeries = () => seriesSnapshot.map((snapshot) => {
                    const updatedData = snapshot.data.map((item, categoryIndex) => {
                        const categoryName = categories[categoryIndex];
                        const visible = visibility[keyFor(categoryName)];
                        const baseColor = palette[categoryIndex % palette.length];

                        if (isPlainObject(item)) {
                            const clone = deepClone(item);
                            const baseValue = isPlainObject(item) ? item.value ?? null : item;
                            clone.value = visible ? baseValue : null;
                            clone.itemStyle = { ...(clone.itemStyle || {}) };
                            clone.itemStyle.color = clone.itemStyle.color || baseColor;
                            const baseOpacity = typeof item.itemStyle?.opacity === 'number' ? item.itemStyle.opacity : 1;
                            clone.itemStyle.opacity = visible ? baseOpacity : 0.2;
                            return clone;
                        }

                        return visible ? item : null;
                    });
                    return {
                        ...snapshot.meta,
                        data: updatedData,
                    };
                });

                const applyVisibility = () => {
                    const updatedSeries = buildUpdatedSeries();
                    const xAxisOption = chartInstance.getOption().xAxis;
                    const xAxisArray = xAxisOption
                        ? (Array.isArray(xAxisOption) ? xAxisOption : [xAxisOption])
                        : [];
                    const newXAxis = xAxisArray.map((axis, axisIndex) => {
                        const baseAxisLabel = axisLabelBases[axisIndex] || {};
                        const axisLabel = {
                            ...baseAxisLabel,
                            formatter: (value) => {
                                const key = keyFor(value);
                                if (visibility[key] === false) return '';
                                if (typeof baseAxisLabel.formatter === 'function') {
                                    try {
                                        return baseAxisLabel.formatter(value);
                                    } catch (err) {
                                        return value;
                                    }
                                }
                                return value;
                            },
                        };
                        return { axisLabel };
                    });

                    const payload = { series: updatedSeries };
                    if (newXAxis.length > 0) {
                        payload.xAxis = newXAxis;
                    }
                    chartInstance.setOption(payload, false, true);
                };

                categories.forEach((name, index) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'tcpd-legend__item';
                    button.dataset.legend = name;

                    const dot = document.createElement('span');
                    dot.className = 'tcpd-legend__dot';
                    dot.style.background = palette[index % palette.length];

                    const label = document.createElement('span');
                    label.textContent = name;

                    button.append(dot, label);
                    button.addEventListener('click', () => {
                        const key = keyFor(name);
                        visibility[key] = !visibility[key];
                        button.classList.toggle('is-off', !visibility[key]);
                        applyVisibility();
                    });

                    legendElement.appendChild(button);
                });

                applyVisibility();
            };

            const adjustColor = (hex, amount = 0) => {
                if (typeof hex !== 'string') return hex;
                let color = hex.replace('#', '');
                if (![3, 6].includes(color.length)) return hex;
                if (color.length === 3) {
                    color = color.split('').map((char) => char + char).join('');
                }
                const num = parseInt(color, 16);
                let r = (num >> 16) + amount;
                let g = ((num >> 8) & 0x00ff) + amount;
                let b = (num & 0x0000ff) + amount;
                r = Math.max(0, Math.min(255, r));
                g = Math.max(0, Math.min(255, g));
                b = Math.max(0, Math.min(255, b));
                const newColor = (b | (g << 8) | (r << 16)).toString(16).padStart(6, '0');
                return `#${newColor}`;
            };

            const createPercentLabel = () => ({
                show: true,
                position: 'top',
                distance: 6,
                color: '#0f172a',
                fontSize: 11,
                formatter: (params) => {
                    const rawValue = typeof params.value === 'number'
                        ? params.value
                        : (params.data && typeof params.data.value === 'number' ? params.data.value : null);
                    if (rawValue === null || rawValue === 0) return '';
                    return formatPercent(rawValue);
                },
            });

            const companyLegendEl = document.getElementById('company-chart-legend');
            const jobLegendEl = document.getElementById('job-position-chart-legend');

            function showEmpty(emptyElement, chartElement) {
                if (chartElement) chartElement.classList.add('d-none');
                if (emptyElement) emptyElement.classList.remove('d-none');
            }

            function showChart(emptyElement, chartElement) {
                if (chartElement) chartElement.classList.remove('d-none');
                if (emptyElement) emptyElement.classList.add('d-none');
            }

            const cleanupDepartmentGrid = () => {
                if (!departmentGridEl) return;
                const chartNodes = departmentGridEl.querySelectorAll('.department-chart');
                chartNodes.forEach((node) => {
                    const instance = echarts.getInstanceByDom(node);
                    if (instance) instance.dispose();
                });
                departmentGridEl.innerHTML = '';
            };

            const createDepartmentCard = (department, index) => {
                const item = document.createElement('div');
                item.className = 'department-grid__item';

                const card = document.createElement('div');
                card.className = 'card dashboard-card level-2 h-100 department-grid__card';

                const departmentName = escapeHtml((department && department.department) || `Department ${index + 1}`);
                card.innerHTML = `
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                <div>
                                    <h6 class="card-title mb-1">${departmentName}</h6>
                                    <p class="text-muted mb-0 small">
                                        Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                    </p>
                                </div>
                                <div class="mt-1">
                                    <div id="department-chart-${index}" class="w-100 department-chart" style="height: 350px;"></div>
                                    <div id="department-chart-${index}-empty" class="text-center text-muted small py-4 d-none">
                                        Data departemen belum tersedia.
                                    </div>
                                </div>
                                <div id="department-chart-${index}-legend" class="tcpd-legend d-none"></div>
                                <p id="department-chart-${index}-summary" class="text-muted small mb-0">
                                    <span class="fw-semibold">Total Pencapaian:</span> N/A
                                </p>
                            </div>
                        </div>
                    `;

                item.appendChild(card);
                return item;
            };

            const renderDepartmentGrid = (departments = []) => {
                if (!departmentGridEl) return;
                cleanupDepartmentGrid();

                const list = Array.isArray(departments) ? departments : [];
                if (!list.length) {
                    if (departmentEmptyEl) departmentEmptyEl.classList.remove('d-none');
                    return;
                }

                if (departmentEmptyEl) departmentEmptyEl.classList.add('d-none');

                list.forEach((dept, index) => {
                    const card = createDepartmentCard(dept, index);
                    departmentGridEl.appendChild(card);
                });

                if (list.length % 2 === 1) {
                    const lastItem = departmentGridEl.lastElementChild;
                    if (lastItem) lastItem.classList.add('department-grid__item--span-2');
                }

                initDepartmentCharts(list, 0);
            };

            @php
                $tcpdEndpoints = [
                    'company' => route('dashboardTCPD.companyData'),
                    'job' => route('dashboardTCPD.data'),
                    'detail' => route('dsDetailCompetency'),
                ];

                if ($canViewTcpdSensitive ?? false) {
                    $tcpdEndpoints['sensitive'] = route('dashboardTCPD.sensitiveData');
                }
            @endphp
            const endpoints = @json($tcpdEndpoints);

            const companyYearFromInput = document.getElementById('company-year-from');
            const companyYearToInput = document.getElementById('company-year-to');
            const companyResetButton = document.getElementById('company-filter-reset');
            const companyApplyButton = document.getElementById('company-filter-apply');

            const jobDepartmentSelect = document.getElementById('job-department');
            const jobSelect = document.getElementById('job_position_id');
            const jobDateFromInput = document.getElementById('job-date-from');
            const jobDateToInput = document.getElementById('job-date-to');
            const jobApplyButton = document.getElementById('job-filter-apply');
            const jobResetButton = document.getElementById('job-filter-reset');

            const departmentGridEl = document.getElementById('department-grid');
            const departmentEmptyEl = document.getElementById('department-empty');
            const initialJobPositionName = @json($selectedJobPositionName);
            const initialDepartmentData = @json($departmentSummaries->values()->all());
            const jobDepartmentMatrix = @json($jobDepartmentData ?? []);
            let currentDepartment = jobDepartmentSelect ? (jobDepartmentSelect.value || null) : null;
            const defaultDepartment = currentDepartment;
            let currentJobPositionId = (() => {
                if (!jobSelect) return null;
                const rawValue = jobSelect.value;
                if (rawValue === '' || rawValue === null || rawValue === undefined) return null;
                const parsed = Number(rawValue);
                return Number.isFinite(parsed) ? parsed : null;
            })();
            const defaultJobPositionId = currentJobPositionId;
            const defaultJobDates = {
                from: jobDateFromInput ? jobDateFromInput.value : '',
                to: jobDateToInput ? jobDateToInput.value : '',
            };
            const defaultCompanyYears = {
                from: companyYearFromInput ? companyYearFromInput.value : '',
                to: companyYearToInput ? companyYearToInput.value : '',
            };

            let currentJobPositionName = (() => {
                if (jobSelect) {
                    const option = jobSelect.options[jobSelect.selectedIndex];
                    if (option && option.text) return option.text.trim();
                }
                return typeof initialJobPositionName === 'string' ? initialJobPositionName : '';
            })();

            const buildDetailUrl = (userId, jobPositionName = '') => {
                if (!endpoints.detail) return null;
                const params = new URLSearchParams();
                if (userId !== null && userId !== undefined && userId !== '') {
                    params.set('id_user', userId);
                }
                if (jobPositionName) {
                    params.set('id_job_position', jobPositionName);
                }
                try {
                    const baseUrl = new URL(endpoints.detail, window.location.origin);
                    params.forEach((value, key) => baseUrl.searchParams.set(key, value));
                    return baseUrl.toString();
                } catch (error) {
                    const separator = endpoints.detail.includes('?') ? '&' : '?';
                    return `${endpoints.detail}${separator}${params.toString()}`;
                }
            };

            let currentDepartmentData = Array.isArray(initialDepartmentData) ? [...initialDepartmentData] : [];
            let isCompanyLoading = false;
            let isJobLoading = false;

            const findDepartmentGroup = (departmentName) => {
                if (!Array.isArray(jobDepartmentMatrix)) return null;
                return jobDepartmentMatrix.find((group) => group && group.department === departmentName) || null;
            };

            const clearJobData = () => {
                initJobPositionChart([], {});
                updateCompetencyTable([]);
                updateJobSummary(null, '');
            };

            const updateJobOptions = (departmentValue, options = {}) => {
                if (!jobSelect) return;
                const { preserveSelection = false, triggerFetch = true } = options;
                const group = findDepartmentGroup(departmentValue);
                currentDepartment = departmentValue || null;

                const currentValue = preserveSelection ? jobSelect.value : null;
                jobSelect.innerHTML = '';

                if (!group || !Array.isArray(group.job_positions) || group.job_positions.length === 0) {
                    jobSelect.disabled = true;
                    currentJobPositionId = null;
                    currentJobPositionName = '';
                    if (triggerFetch) {
                        clearJobData();
                    }
                    return;
                }

                jobSelect.disabled = false;

                const docFragment = document.createDocumentFragment();
                group.job_positions.forEach((job) => {
                    const option = document.createElement('option');
                    option.value = job.id;
                    option.textContent = job.name;
                    docFragment.appendChild(option);
                });
                jobSelect.appendChild(docFragment);

                let targetValue = null;
                if (preserveSelection && currentValue && group.job_positions.some((job) => String(job.id) === String(currentValue))) {
                    targetValue = String(currentValue);
                } else if (departmentValue === defaultDepartment && defaultJobPositionId !== null && group.job_positions.some((job) => Number(job.id) === Number(defaultJobPositionId))) {
                    targetValue = String(defaultJobPositionId);
                } else {
                    targetValue = String(group.job_positions[0].id);
                }

                jobSelect.value = targetValue;
                const option = jobSelect.options[jobSelect.selectedIndex];
                currentJobPositionName = option && option.text ? option.text.trim() : '';
                currentJobPositionId = jobSelect.value ? Number(jobSelect.value) : null;

                if (!currentJobPositionName && typeof initialJobPositionName === 'string' && currentJobPositionId !== null) {
                    currentJobPositionName = initialJobPositionName;
                }

                if (triggerFetch) {
                    jobSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            const setButtonLoading = (button, isLoading) => {
                if (!button) return;
                button.disabled = !!isLoading;
                button.classList.toggle('disabled', !!isLoading);
            };

            const updateCompanySummary = (meta = {}) => {
                const summaryEl = document.getElementById('company-chart-summary');
                if (!summaryEl) return;
                if (!meta.hasData) {
                    summaryEl.classList.add('d-none');
                    summaryEl.innerHTML = '<span class="fw-semibold">Company Average:</span> N/A';
                    return;
                }
                const average = Number(meta.average || 0);
                const count = Number(meta.count || 0);
                const parts = [`<span class="fw-semibold">Company Average:</span> ${formatNumber(average, 2)}%`];
                if (count > 0) {
                    parts.push(`- <span class="fw-semibold">${count}</span> departemen dinilai`);
                }
                summaryEl.classList.remove('d-none');
                summaryEl.innerHTML = parts.join(' ');
            };

            // ── Area Development Table dengan Pagination ─────────────────
            const competencyPagination = {
                data: [],
                currentPage: 1,
                perPage: 10,

                init() {
                    const perPageEl = document.getElementById('tcpd-competency-per-page');
                    if (perPageEl) {
                        perPageEl.addEventListener('change', () => {
                            this.perPage = parseInt(perPageEl.value, 10);
                            this.currentPage = 1;
                            this.render();
                        });
                    }
                },

                setData(deficiencies) {
                    this.data = deficiencies;
                    this.currentPage = 1;
                    this.render();
                },

                render() {
                    const tbody = document.getElementById('tcpd-competency-body');
                    const paginationEl = document.getElementById('tcpd-competency-pagination');
                    const pagesEl = document.getElementById('tcpd-competency-pages');
                    const infoEl = document.getElementById('tcpd-competency-pagination-info');
                    if (!tbody) return;

                    if (!this.data.length) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-4">Semua karyawan memenuhi standar untuk filter ini.</td></tr>';
                        if (paginationEl) paginationEl.classList.add('d-none');
                        return;
                    }

                    const showAll = this.perPage === 0;
                    const totalItems = this.data.length;
                    const totalPages = showAll ? 1 : Math.ceil(totalItems / this.perPage);

                    // Clamp current page
                    if (this.currentPage > totalPages) this.currentPage = totalPages;

                    const startIdx = showAll ? 0 : (this.currentPage - 1) * this.perPage;
                    const endIdx = showAll ? totalItems : Math.min(startIdx + this.perPage, totalItems);
                    const pageData = this.data.slice(startIdx, endIdx);

                    tbody.innerHTML = pageData.map((row, i) => {
                        const globalIndex = startIdx + i + 1;
                        let badgeClass = 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
                        let typeLabel = row.compType || '';
                        if (row.compType === 'technical') {
                            badgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary';
                            typeLabel = 'Technical';
                        } else if (row.compType === 'soft_skill') {
                            badgeClass = 'bg-success bg-opacity-10 text-success border border-success';
                            typeLabel = 'Soft Skill';
                        } else if (row.compType === 'additional') {
                            badgeClass = 'bg-info bg-opacity-10 text-info border border-info';
                            typeLabel = 'Additional';
                        }

                        // Exclude current employee from mentor list
                        const validMentors = (row.mentors || []).filter(m => m.id != row.employeeId && m.name != row.employeeName);
                        const maxDisplay = 5;
                        const displayMentors = validMentors.slice(0, maxDisplay);
                        const remainingCount = validMentors.length - displayMentors.length;

                        let mentorHtml = '';
                        if (validMentors.length > 0) {
                            const badgesHtml = displayMentors.map(m => {
                                const titleInfo = m.job_position ? `Jabatan: ${escapeHtml(m.job_position)} (Nilai: ${m.actual ?? '-'})` : `Nilai: ${m.actual ?? '-'}`;
                                return `<span class="badge bg-white text-info border border-info me-1 mb-1 d-inline-flex align-items-center rounded-pill px-2 py-1 shadow-sm" title="${titleInfo}" style="font-size: 0.72rem; font-weight: 500;">
                                        <i class="bi bi-person-check-fill text-info me-1"></i>${escapeHtml(m.name)}
                                    </span>`;
                            }).join('');
                            const mentorsJson = escapeHtml(JSON.stringify(validMentors));
                            const extraBadge = remainingCount > 0 ? `<a href="javascript:void(0)" class="text-decoration-underline ms-1 text-primary view-all-mentors fw-medium" data-mentors="${mentorsJson}" style="font-size: 0.75rem;">Lihat ${remainingCount} mentor lainnya...</a>` : '';
                            mentorHtml = `<div class="mt-2 pt-1 border-top border-light d-flex flex-wrap align-items-center">
                                    <span class="text-muted small me-2" style="font-size:0.75rem;"><i class="bi bi-lightbulb-fill text-warning me-1"></i>Saran Mentor:</span>
                                    ${badgesHtml}${extraBadge}
                                </div>`;
                        } else {
                            mentorHtml = `<div class="mt-2 pt-1 border-top border-light">
                                    <span class="text-muted small fst-italic" style="font-size:0.75rem;"><i class="bi bi-lightbulb text-muted me-1"></i>Saran Mentor: Belum ada mentor memenuhi standar</span>
                                </div>`;
                        }

                        return `
                                <tr>
                                    <td>${globalIndex}</td>
                                    <td><i class="bi bi-person-fill text-muted me-1"></i><strong>${escapeHtml(row.employeeName)}</strong></td>
                                    <td>
                                        <div>
                                            <span class="badge ${badgeClass} me-2" style="font-size:0.65rem;text-transform:uppercase;">${typeLabel}</span>
                                            <strong class="text-dark">${escapeHtml(row.compName)}</strong>
                                        </div>
                                        ${mentorHtml}
                                    </td>
                                    <td><span class="badge bg-warning text-dark">${row.actual !== null ? Number(row.actual).toFixed(2) : '-'}</span></td>
                                    <td><span class="badge bg-primary">${row.standard !== null ? Number(row.standard).toFixed(2) : '-'}</span></td>
                                </tr>
                            `;
                    }).join('');

                    // Bind event listeners for view all mentors modal
                    tbody.querySelectorAll('.view-all-mentors').forEach(link => {
                        link.addEventListener('click', function (e) {
                            e.preventDefault();
                            try {
                                const mentors = JSON.parse(this.dataset.mentors);
                                showMentorsModal(mentors);
                            } catch (err) {
                                console.error('Error parsing mentors data', err);
                            }
                        });
                    });

                    if (infoEl) {
                        if (showAll) {
                            infoEl.textContent = `Menampilkan ${totalItems} dari ${totalItems} baris`;
                        } else {
                            infoEl.textContent = `Menampilkan ${startIdx + 1}–${endIdx} dari ${totalItems} baris`;
                        }
                    }

                    if (pagesEl) {
                        let pagesHtml = '';
                        pagesHtml += `<li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                                <button class="page-link" data-page="${this.currentPage - 1}">&laquo;</button>
                            </li>`;

                        const maxVisible = 5;
                        let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2));
                        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
                        if (endPage - startPage < maxVisible - 1) {
                            startPage = Math.max(1, endPage - maxVisible + 1);
                        }

                        if (startPage > 1) {
                            pagesHtml += `<li class="page-item"><button class="page-link" data-page="1">1</button></li>`;
                            if (startPage > 2) pagesHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                        }
                        for (let p = startPage; p <= endPage; p++) {
                            pagesHtml += `<li class="page-item ${p === this.currentPage ? 'active' : ''}">
                                    <button class="page-link" data-page="${p}">${p}</button>
                                </li>`;
                        }
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) pagesHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                            pagesHtml += `<li class="page-item"><button class="page-link" data-page="${totalPages}">${totalPages}</button></li>`;
                        }

                        pagesHtml += `<li class="page-item ${this.currentPage === totalPages || totalPages <= 1 ? 'disabled' : ''}">
                                <button class="page-link" data-page="${this.currentPage + 1}">&raquo;</button>
                            </li>`;

                        pagesEl.innerHTML = pagesHtml;

                        pagesEl.querySelectorAll('button[data-page]').forEach(btn => {
                            btn.addEventListener('click', (e) => {
                                e.preventDefault();
                                const p = parseInt(btn.getAttribute('data-page'), 10);
                                if (p >= 1 && p <= totalPages) {
                                    this.currentPage = p;
                                    this.render();
                                }
                            });
                        });
                    }

                    if (paginationEl) {
                        if (showAll || totalPages <= 1) {
                            paginationEl.classList.toggle('d-none', totalItems <= 10 && showAll);
                            paginationEl.classList.remove('d-none');
                        } else {
                            paginationEl.classList.remove('d-none');
                        }
                    }
                },
            };

            competencyPagination.init();

            function updateCompetencyTable(competencies, tableBodyId = 'tcpd-competency-body') {
                const deficiencies = [];
                if (Array.isArray(competencies)) {
                    competencies.forEach(comp => {
                        if (Array.isArray(comp.employees)) {
                            comp.employees.forEach(emp => {
                                deficiencies.push({
                                    employeeId: emp.id,
                                    employeeName: emp.name,
                                    compName: comp.name,
                                    compType: comp.type,
                                    actual: emp.actual,
                                    standard: comp.standard,
                                    mentors: comp.mentors || []
                                });
                            });
                        }
                    });
                }

                deficiencies.sort((a, b) => {
                    const nameComp = a.employeeName.localeCompare(b.employeeName);
                    if (nameComp !== 0) return nameComp;
                    return a.compName.localeCompare(b.compName);
                });

                competencyPagination.setData(deficiencies);
            }

            function updateJobSummary(data, jobPositionName = '') {
                const summaryEl = document.getElementById('job-position-chart-summary');
                const userLinksEl = document.getElementById('job-position-user-links');

                if (!summaryEl || !userLinksEl) return;

                if (!data || !(data.hasTotalPercentage ?? false)) {
                    summaryEl.classList.add('d-none');
                    userLinksEl.classList.add('d-none');
                    return;
                }

                summaryEl.classList.remove('d-none');
                summaryEl.innerHTML = `<strong>Total Pencapaian:</strong> ${formatPercent(data.totalPercentage)} (dari ${data.qty} user dievaluasi)`;

                const userSummaries = data.userSummaries || [];
                const userRows = userSummaries.filter(u => u.id !== 'average');

                const averageEntry = (Array.isArray(data.userSummaries) ? data.userSummaries : []).find(u => u.id === 'average');
                const rawJobPositionLabel = jobPositionName || averageEntry?.name || 'Total Pencapaian';
                const jobPositionLabel = escapeHtml(rawJobPositionLabel);

                if (userRows.length > 0) {
                    userLinksEl.classList.remove('d-none');
                    const buttons = [
                        `<span class="btn btn-sm btn-outline-primary disabled text-uppercase">${jobPositionLabel}</span>`,
                        ...userRows.map((user) => {
                            const userId = Number(user.id);
                            const detailUrl = buildDetailUrl(userId, rawJobPositionLabel);
                            const npk = String(user.npk || '').trim();
                            const label = escapeHtml(`${npk && npk !== '0' ? npk : '-'} — ${user.name}`);
                            if (detailUrl) {
                                return `<a href="${detailUrl}" class="btn btn-sm btn-outline-secondary">${label}</a>`;
                            }
                            return `<span class="btn btn-sm btn-outline-secondary disabled" aria-disabled="true">${label}</span>`;
                        }),
                    ];
                    userLinksEl.innerHTML = buttons.join('');
                } else {
                    userLinksEl.classList.add('d-none');
                    userLinksEl.innerHTML = '';
                }
            }

            function initCompanyChart(entries, summaryMeta = {}) {
                const container = document.getElementById('company-chart');
                const emptyEl = document.getElementById('company-chart-empty');
                if (!container) return;

                const toPercentage = (value) => {
                    const numeric = Number(value);
                    return Number.isFinite(numeric) ? Number(numeric.toFixed(2)) : null;
                };

                const toYear = (value) => {
                    if (value === null || value === undefined || value === '') return null;
                    const numeric = Number(value);
                    return Number.isNaN(numeric) ? null : Math.trunc(numeric);
                };

                const normalizeEntry = (raw = {}) => {
                    const percentage = toPercentage(raw.percentage);
                    const values = Array.isArray(raw.values)
                        ? raw.values.map((value) => ({
                            ...value,
                            year: toYear(value.year ?? value.key),
                            percentage: toPercentage(value.percentage),
                        }))
                        : [];

                    const hasDataFlag = raw.hasData ?? raw.has_data;
                    const derivedHasData = values.some(v => v.percentage !== null) || percentage !== null;

                    return {
                        label: raw.label ?? 'Unnamed',
                        isCompany: Boolean(raw.is_company ?? raw.isCompany),
                        hasData: typeof hasDataFlag === 'boolean' ? hasDataFlag : derivedHasData,
                        percentage,
                        values,
                    };
                };

                let instance = echarts.getInstanceByDom(container);
                if (instance) instance.dispose();
                instance = echarts.init(container, null, { renderer: 'canvas' });

                const normalizedEntries = Array.isArray(entries)
                    ? entries.filter(Boolean).map(normalizeEntry)
                    : [];

                const departmentEntries = normalizedEntries.filter(item => !item.isCompany);
                const companyEntry = normalizedEntries.find(item => item.isCompany);

                const mode = summaryMeta.mode || 'aggregate';
                let years = Array.isArray(summaryMeta.years)
                    ? summaryMeta.years
                        .map(year => Number(year))
                        .filter(year => !Number.isNaN(year))
                    : [];
                years = Array.from(new Set(years)).sort((a, b) => a - b);
                let hasYearMode = mode === 'yearly' && years.length > 0;

                if (mode === 'yearly' && !hasYearMode) {
                    const inferredYears = Array.from(new Set(
                        departmentEntries.flatMap(entry => entry.values.map(v => v.year).filter(year => year !== null))
                    )).sort((a, b) => a - b);
                    if (inferredYears.length > 0) {
                        years = inferredYears;
                        hasYearMode = true;
                    }
                }

                const departmentLabels = departmentEntries.map(item => item.label);
                const includeCompanyCategory = Boolean(companyEntry);
                const companyLabel = companyEntry?.label || 'Company';
                const categories = includeCompanyCategory
                    ? [companyLabel, ...departmentLabels]
                    : [...departmentLabels];

                const hasDepartmentSeries = departmentEntries.some((item) => {
                    if (!item.hasData) return false;
                    if (hasYearMode) {
                        if (!item.values.length) return item.percentage !== null;
                        return years.some(year => {
                            const match = item.values.find(v => v.year === year);
                            return match && match.percentage !== null;
                        });
                    }
                    return item.percentage !== null;
                });

                const hasCompanySeries = (() => {
                    if (!includeCompanyCategory || !companyEntry) return false;
                    if (hasYearMode) {
                        if (!companyEntry.values.length) return companyEntry.percentage !== null;
                        return years.some(year => {
                            const match = companyEntry.values.find(v => v.year === year);
                            return match && match.percentage !== null;
                        });
                    }
                    return companyEntry.percentage !== null;
                })();

                if (!hasDepartmentSeries && !hasCompanySeries) {
                    showEmpty(emptyEl, container);
                    hideLegend(companyLegendEl);
                    updateCompanySummary({ hasData: false });
                    return;
                }

                showChart(emptyEl, container);
                updateCompanySummary({
                    hasData: summaryMeta.hasData,
                    average: summaryMeta.average,
                    count: summaryMeta.departmentCount,
                });

                const categoryColors = categories.map((_, idx) => {
                    const isCompanyCategory = includeCompanyCategory && idx === 0;
                    if (isCompanyCategory) return '#1f2937';
                    const paletteIndex = includeCompanyCategory ? idx - 1 : idx;
                    const normalizedIndex = ((paletteIndex % TCPD_COLOR_PALETTE.length) + TCPD_COLOR_PALETTE.length) % TCPD_COLOR_PALETTE.length;
                    return TCPD_COLOR_PALETTE[normalizedIndex];
                });
                const categoryGap = categories.length <= 3 ? '18%' : categories.length <= 6 ? '26%' : '32%';

                const resolveDepartmentValue = (entry, year = null) => {
                    if (!entry) return null;
                    if (typeof year === 'number') {
                        if (!Array.isArray(entry.values) || entry.values.length === 0) return entry.percentage;
                        const match = entry.values.find(v => v.year === year);
                        return match ? match.percentage : null;
                    }
                    return entry.percentage;
                };

                const resolveCompanyValue = (entry, year = null) => {
                    if (!entry) return null;
                    if (typeof year === 'number') {
                        if (!Array.isArray(entry.values) || entry.values.length === 0) return entry.percentage;
                        const match = entry.values.find(v => v.year === year);
                        return match ? match.percentage : null;
                    }
                    return entry.percentage;
                };

                const makeDataItem = (value, categoryIndex) => {
                    const numeric = Number(value);
                    const parsed = Number.isFinite(numeric) ? Number(numeric.toFixed(2)) : null;
                    const isCompanyCategory = includeCompanyCategory && categoryIndex === 0;
                    const borderWidth = isCompanyCategory && parsed !== null ? 1 : 0;
                    return {
                        value: parsed,
                        itemStyle: {
                            color: categoryColors[categoryIndex],
                            opacity: parsed === null ? 0.4 : 1,
                            borderColor: borderWidth ? adjustColor(categoryColors[categoryIndex], -30) : undefined,
                            borderWidth,
                        },
                    };
                };

                const series = [];
                if (hasYearMode) {
                    years.forEach((year) => {
                        const data = categories.map((_, cIndex) => {
                            const isCompanyCategory = includeCompanyCategory && cIndex === 0;
                            const departmentIndex = includeCompanyCategory ? cIndex - 1 : cIndex;
                            const rawValue = isCompanyCategory
                                ? resolveCompanyValue(companyEntry, year)
                                : resolveDepartmentValue(departmentEntries[departmentIndex], year);
                            return makeDataItem(rawValue, cIndex);
                        });
                        series.push({
                            name: String(year),
                            type: 'bar',
                            barMinWidth: 32,
                            barMaxWidth: 64,
                            barGap: '20%',
                            barCategoryGap: categoryGap,
                            emphasis: { focus: 'series' },
                            label: createPercentLabel(),
                            data,
                        });
                    });
                } else {
                    const data = categories.map((_, cIndex) => {
                        const isCompanyCategory = includeCompanyCategory && cIndex === 0;
                        const departmentIndex = includeCompanyCategory ? cIndex - 1 : cIndex;
                        const rawValue = isCompanyCategory
                            ? resolveCompanyValue(companyEntry)
                            : resolveDepartmentValue(departmentEntries[departmentIndex]);
                        return makeDataItem(rawValue, cIndex);
                    });
                    series.push({
                        name: 'Overall',
                        type: 'bar',
                        barMinWidth: 40,
                        barMaxWidth: 72,
                        barGap: '20%',
                        barCategoryGap: categoryGap,
                        emphasis: { focus: 'series' },
                        label: createPercentLabel(),
                        data,
                    });
                }

                const rotateLabels = categories.length > 3 ? 30 : 0;
                const legendLabels = series.map((serie) => serie.name);
                const option = {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' },
                        formatter: (params) => {
                            const label = params?.[0]?.axisValueLabel || params?.[0]?.name || '';
                            const rows = params
                                .map(p => `${p.marker}${p.seriesName}: ${formatPercent(p.value)}`)
                                .join('<br/>');
                            return `<strong>${escapeHtml(label)}</strong><br/>${rows}`;
                        },
                    },
                    legend: { data: legendLabels, top: 0 },
                    grid: {
                        top: '12%',
                        left: '4%',
                        right: '4%',
                        bottom: rotateLabels ? '22%' : '12%',
                        containLabel: true,
                    },
                    xAxis: {
                        type: 'category',
                        data: categories,
                        axisLabel: { interval: 0, rotate: rotateLabels, hideOverlap: false },
                        axisTick: { alignWithLabel: true },
                    },

                    yAxis: {
                        type: 'value',
                        axisLabel: { formatter: '{value}%' },
                        max: 100,
                    },
                    series,
                };

                try {
                    instance.setOption(option);
                } catch (e) {
                    console.error("ECharts setOption error in company chart:", e);
                }

                const seriesSnapshot = createSeriesSnapshot(series);
                attachCategoryLegend({
                    chartInstance: instance,
                    legendElement: companyLegendEl,
                    categories,
                    colors: categoryColors,
                    seriesSnapshot,
                });

                // Interactive Drill-down
                instance.off('click');
                instance.on('click', function (params) {
                    const deptName = params.name;
                    if (deptName && deptName !== 'Company' && deptName !== 'Total' && deptName !== 'Overall') {
                        const headers = document.querySelectorAll('.department-header h6');
                        for (let i = 0; i < headers.length; i++) {
                            if (headers[i].innerText.trim() === deptName) {
                                const card = headers[i].closest('.card');
                                if (card) {
                                    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    card.classList.add('border', 'border-primary', 'shadow-lg');
                                    setTimeout(() => card.classList.remove('border', 'border-primary', 'shadow-lg'), 2000);
                                    break;
                                }
                            }
                        }
                    }
                });
            }

            function initDepartmentCharts(departments, startIndex = 0) {
                if (!Array.isArray(departments)) return;
                departments.forEach((dept, i) => {
                    const index = startIndex + i;
                    const container = document.getElementById(`department-chart-${index}`);
                    const emptyEl = document.getElementById(`department-chart-${index}-empty`);
                    const summaryEl = document.getElementById(`department-chart-${index}-summary`);
                    const legendEl = document.getElementById(`department-chart-${index}-legend`);

                    if (!container) return;

                    let instance = echarts.getInstanceByDom(container);
                    if (instance) instance.dispose();
                    instance = echarts.init(container, null, { renderer: 'canvas' });

                    const hasData = dept && Array.isArray(dept.entries) && dept.entries.some(e => e.has_data);

                    if (!hasData) {
                        showEmpty(emptyEl, container);
                        if (summaryEl) summaryEl.innerHTML = '<span class="fw-semibold">Total Pencapaian:</span> N/A';
                        hideLegend(legendEl);
                        return;
                    }

                    showChart(emptyEl, container);
                    const totalEntry = dept.entries.find(e => e.is_total);
                    const jobEntries = dept.entries.filter(e => !e.is_total);

                    if (jobEntries.length === 0) {
                        hideLegend(legendEl);
                        showEmpty(emptyEl, container);
                        if (summaryEl) summaryEl.innerHTML = '<span class="fw-semibold">Total Pencapaian:</span> N/A';
                        return;
                    }

                    const numericValues = jobEntries
                        .map(item => Number(item.percentage))
                        .filter(value => Number.isFinite(value));
                    const averageValue = numericValues.length > 0
                        ? Number((numericValues.reduce((sum, value) => sum + value, 0) / numericValues.length).toFixed(2))
                        : null;

                    const collectYearNumbers = new Set();
                    const normalizeYear = (value) => {
                        const numeric = Number(value);
                        return Number.isFinite(numeric) ? numeric : null;
                    };

                    if (Array.isArray(dept.years)) {
                        dept.years.forEach((year) => {
                            const numeric = normalizeYear(year);
                            if (numeric !== null) collectYearNumbers.add(numeric);
                        });
                    }
                    const harvestEntryYears = (entry) => {
                        if (!entry || !Array.isArray(entry.values)) return;
                        entry.values.forEach((value) => {
                            const numeric = normalizeYear(value?.year ?? value?.key);
                            if (numeric !== null) collectYearNumbers.add(numeric);
                        });
                    };
                    harvestEntryYears(totalEntry);
                    jobEntries.forEach(harvestEntryYears);

                    const orderedYears = Array.from(collectYearNumbers).sort((a, b) => a - b);
                    const yearsWithData = orderedYears.filter((year) => {
                        const totalHas = totalEntry && Array.isArray(totalEntry.values) && totalEntry.values.some(v => normalizeYear(v.year) === year && v.percentage !== null);
                        const jobHas = jobEntries.some(entry => Array.isArray(entry.values) && entry.values.some(v => normalizeYear(v.year) === year && v.percentage !== null));
                        return totalHas || jobHas;
                    });
                    const hasYearMode = yearsWithData.length > 0;

                    const categories = ['Total', ...jobEntries.map(item => item.label)];
                    const categoryColors = categories.map((_, idx) => {
                        if (idx === 0) return '#1f2937';
                        const paletteIndex = idx - 1;
                        const normalizedIndex = ((paletteIndex % TCPD_COLOR_PALETTE.length) + TCPD_COLOR_PALETTE.length) % TCPD_COLOR_PALETTE.length;
                        return TCPD_COLOR_PALETTE[normalizedIndex];
                    });
                    const categoryGap = categories.length <= 3 ? '40%' : '48%';

                    const resolveEntryValue = (entry, year = null) => {
                        if (!entry) return null;
                        if (typeof year === 'number') {
                            if (!Array.isArray(entry.values) || entry.values.length === 0) return entry.percentage ?? null;
                            const match = entry.values.find(v => normalizeYear(v.year) === year);
                            return match && isFinite(Number(match.percentage)) ? Number(match.percentage) : null;
                        }
                        return isFinite(Number(entry.percentage)) ? Number(entry.percentage) : null;
                    };

                    const computeAverageFromEntries = (entries, year = null) => {
                        const values = entries
                            .map(entry => resolveEntryValue(entry, year))
                            .filter(value => value !== null && value !== undefined);
                        if (!values.length) return null;
                        const sum = values.reduce((total, val) => total + val, 0);
                        return Number((sum / values.length).toFixed(2));
                    };

                    const totalOverallValue = (() => {
                        const explicit = resolveEntryValue(totalEntry, null);
                        if (explicit !== null && explicit !== undefined) return explicit;
                        if (averageValue !== null) return averageValue;
                        return null;
                    })();

                    const createDataItem = (value, categoryIndex) => {
                        const numeric = Number(value);
                        const parsed = Number.isFinite(numeric) ? Number(numeric.toFixed(2)) : null;
                        const isTotalCategory = categoryIndex === 0;
                        const borderWidth = isTotalCategory && parsed !== null ? 1 : 0;
                        return {
                            value: parsed,
                            itemStyle: {
                                color: categoryColors[categoryIndex],
                                opacity: parsed === null ? 0.4 : 1,
                                borderColor: borderWidth ? adjustColor(categoryColors[categoryIndex], -30) : undefined,
                                borderWidth,
                            },
                        };
                    };

                    const series = [];
                    if (hasYearMode) {
                        yearsWithData.forEach((year) => {
                            const data = categories.map((_, idx) => {
                                if (idx === 0) {
                                    const totalValue = resolveEntryValue(totalEntry, year) ?? computeAverageFromEntries(jobEntries, year);
                                    return createDataItem(totalValue, idx);
                                }
                                const entry = jobEntries[idx - 1];
                                return createDataItem(resolveEntryValue(entry, year), idx);
                            });
                            series.push({
                                name: String(year),
                                type: 'bar',
                                barMinWidth: 28,
                                barMaxWidth: 60,
                                barGap: '18%',
                                barCategoryGap: categoryGap,
                                emphasis: { focus: 'series' },
                                label: createPercentLabel(),
                                data,
                            });
                        });
                    } else {
                        const data = categories.map((_, idx) => {
                            if (idx === 0) return createDataItem(totalOverallValue, idx);
                            const entry = jobEntries[idx - 1];
                            return createDataItem(resolveEntryValue(entry, null), idx);
                        });
                        series.push({
                            name: 'Overall',
                            type: 'bar',
                            barMinWidth: 32,
                            barMaxWidth: 70,
                            barGap: '18%',
                            barCategoryGap: categoryGap,
                            emphasis: { focus: 'series' },
                            label: createPercentLabel(),
                            data,
                        });
                    }

                    const option = {
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: { type: 'shadow' },
                            formatter: (params) => {
                                const label = params?.[0]?.axisValueLabel || params?.[0]?.name || '';
                                const rows = params
                                    .map(p => `${p.marker}${p.seriesName}: ${formatPercent(p.value)}`)
                                    .join('<br/>');
                                return `<strong>${escapeHtml(label)}</strong><br/>${rows}`;
                            },
                        },
                        legend: { show: false },
                        grid: { top: '12%', left: '4%', right: '4%', bottom: categories.length > 4 ? '25%' : (hasYearMode ? '16%' : '14%'), containLabel: true },
                        xAxis: {
                            type: 'category',
                            data: categories,
                            axisLabel: { interval: 0, rotate: categories.length > 4 ? 30 : 0, hideOverlap: false },
                            axisTick: { alignWithLabel: true },
                        },
                        yAxis: { type: 'value', axisLabel: { formatter: '{value}%' }, max: 100 },
                        series,
                    };

                    try {
                        instance.setOption(option);
                    } catch (e) {
                        console.error(`ECharts setOption error in department chart ${index}:`, e);
                    }

                    const summaryPercentage = totalOverallValue ?? averageValue ?? null;
                    if (summaryEl) summaryEl.innerHTML = `<span class="fw-semibold">Total Pencapaian:</span> ${formatPercent(summaryPercentage)}`;

                    const seriesSnapshot = createSeriesSnapshot(series);
                    attachCategoryLegend({
                        chartInstance: instance,
                        legendElement: legendEl,
                        categories,
                        colors: categoryColors,
                        seriesSnapshot,
                    });
                });
            }

            function initJobPositionChart(entries, context = {}) {
                const container = document.getElementById('job-position-chart');
                const emptyEl = document.getElementById('job-position-chart-empty');
                if (!container) return;

                let instance = echarts.getInstanceByDom(container);
                if (instance) instance.dispose();
                instance = echarts.init(container, null, { renderer: 'canvas' });

                const hasData = Array.isArray(entries) && entries.length > 1; // more than just average row

                if (!hasData) {
                    showEmpty(emptyEl, container);
                    hideLegend(jobLegendEl);
                    return;
                }

                showChart(emptyEl, container);
                const averageEntry = entries.find(entry => entry && entry.id === 'average');
                const jobPositionName =
                    (typeof context.jobPositionName === 'string' && context.jobPositionName.trim()) ||
                    (averageEntry && typeof averageEntry.name === 'string' ? averageEntry.name : '') ||
                    currentJobPositionName ||
                    'Total';
                const userRows = entries.filter(e => e.id !== 'average');
                const categories = [jobPositionName, ...userRows.map(u => u.name)];
                if (userRows.length === 0 && !averageEntry) {
                    hideLegend(jobLegendEl);
                    showEmpty(emptyEl, container);
                    return;
                }

                const categoryColors = categories.map((_, idx) => {
                    if (idx === 0) return '#1f2937';
                    const paletteIndex = (idx - 1 + TCPD_COLOR_PALETTE.length) % TCPD_COLOR_PALETTE.length;
                    return TCPD_COLOR_PALETTE[paletteIndex];
                });

                const metricDefinitions = [
                    { name: 'Technical', key: 'tc_percentage', tint: -18 },
                    { name: 'Soft Skill', key: 'sk_percentage', tint: 0 },
                    { name: 'Additional', key: 'ad_percentage', tint: 18 },
                ];

                const series = metricDefinitions.map((metric) => {
                    const barCategoryGap = categories.length <= 3 ? '24%' : '32%';
                    const data = [];

                    const makeDataItem = (value, categoryIndex, userId = null) => {
                        const numeric = Number(value);
                        const parsed = Number.isFinite(numeric) ? Number(numeric.toFixed(2)) : null;
                        const baseColor = categoryColors[categoryIndex];
                        const color = metric.tint !== 0 ? adjustColor(baseColor, metric.tint) : baseColor;
                        const item = {
                            value: parsed,
                            itemStyle: {
                                color,
                                opacity: parsed === null ? 0.4 : 1,
                            },
                        };
                        if (categoryIndex === 0) {
                            item.itemStyle.borderColor = parsed !== null ? adjustColor(color, -30) : undefined;
                            item.itemStyle.borderWidth = parsed !== null ? 1 : 0;
                        }
                        if (userId !== null && userId !== undefined) {
                            const detailUrl = buildDetailUrl(userId, jobPositionName);
                            item.__meta = detailUrl
                                ? { userId, jobPositionName, url: detailUrl }
                                : { userId, jobPositionName };
                        }
                        return item;
                    };

                    const totalValue = averageEntry ? averageEntry[metric.key] : null;
                    data.push(makeDataItem(totalValue, 0));

                    userRows.forEach((user, index) => {
                        const dataIndex = index + 1;
                        data.push(makeDataItem(user[metric.key], dataIndex, Number(user.id)));
                    });

                    return {
                        name: metric.name,
                        type: 'bar',
                        barGap: '18%',
                        barCategoryGap,
                        emphasis: { focus: 'series' },
                        label: createPercentLabel(),
                        data,
                    };
                });

                const option = {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' },
                        formatter: (params) => {
                            const label = params?.[0]?.axisValueLabel || '';
                            const rows = params
                                .map(p => `${p.marker}${p.seriesName}: ${formatPercent(p.value)}`)
                                .join('<br/>');
                            return `<strong>${escapeHtml(label)}</strong><br/>${rows}`;
                        },
                    },
                    legend: { show: false },
                    grid: { top: '18%', left: '4%', right: '4%', bottom: categories.length > 4 ? '25%' : '16%', containLabel: true },
                    xAxis: { type: 'category', data: categories, axisLabel: { interval: 0, rotate: categories.length > 4 ? 30 : 0, hideOverlap: false } },
                    yAxis: { type: 'value', axisLabel: { formatter: '{value}%' } },
                    series,
                };
                try {
                    instance.setOption(option);
                } catch (e) {
                    console.error("ECharts setOption error in job position chart:", e);
                }

                const seriesSnapshot = createSeriesSnapshot(series);
                attachCategoryLegend({
                    chartInstance: instance,
                    legendElement: jobLegendEl,
                    categories,
                    colors: categoryColors,
                    seriesSnapshot,
                });

                if (instance.__tcpdJobClickHandler) {
                    instance.off('click', instance.__tcpdJobClickHandler);
                }
                instance.__tcpdJobClickHandler = (params) => {
                    if (!params || params.componentType !== 'series') return;
                    const meta = params.data && params.data.__meta;
                    if (meta && meta.userId && meta.url) {
                        window.location.href = meta.url;
                    }
                };
                instance.on('click', instance.__tcpdJobClickHandler);
            }

            @if ($canViewTcpdSensitive ?? false)
            function renderEffectivenessChart(effectivenessData, years, companyRows) {
                const container = document.getElementById('effectiveness-chart');
                if (!container) return;

                let instance = echarts.getInstanceByDom(container);
                if (instance) instance.dispose();
                instance = echarts.init(container, null, { renderer: 'canvas' });

                if (!years || years.length === 0) {
                    container.innerHTML = '<div class="d-flex h-100 align-items-center justify-content-center text-muted">Data tahun tidak tersedia</div>';
                    return;
                }

                // Prepare Data
                const categories = years.map(String);
                const costData = categories.map(y => Number(effectivenessData[y] || 0));

                // Get company average per year
                const companyRow = companyRows.find(r => r.is_company);
                const compData = categories.map(y => {
                    const v = companyRow ? companyRow.values.find(val => val.key === String(y)) : null;
                    return v ? Number(v.percentage || 0) : 0;
                });

                // Helper format currency short
                const formatShortCurrency = (val) => {
                    if (val >= 1000000000) return (val / 1000000000).toFixed(val % 1000000000 === 0 ? 0 : 1) + ' M';
                    if (val >= 1000000) return (val / 1000000).toFixed(val % 1000000 === 0 ? 0 : 1) + ' Jt';
                    if (val >= 1000) return (val / 1000).toFixed(0) + ' Rb';
                    return val.toLocaleString('id-ID');
                };

                const option = {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: {
                            type: 'shadow',
                            shadowStyle: { color: 'rgba(16, 185, 129, 0.05)' }
                        },
                        backgroundColor: 'rgba(255, 255, 255, 0.98)',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: [12, 16],
                        textStyle: { color: '#1e293b', fontSize: 13 },
                        extraCssText: 'box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); border-radius: 8px;',
                        formatter: function (params) {
                            const dataIndex = params[0].dataIndex;
                            const year = params[0].name;

                            const cost = costData[dataIndex] || 0;
                            const comp = compData[dataIndex] || 0;

                            // YoY calculations
                            let costYoYHtml = '';
                            let compYoYHtml = '';
                            if (dataIndex > 0) {
                                const prevCost = costData[dataIndex - 1] || 0;
                                const prevComp = compData[dataIndex - 1] || 0;

                                if (prevCost > 0) {
                                    const diffCost = ((cost - prevCost) / prevCost) * 100;
                                    const sign = diffCost >= 0 ? '+' : '';
                                    costYoYHtml = ` <span style="font-size:11px;color:${diffCost >= 0 ? '#64748b' : '#10b981'};">(${sign}${diffCost.toFixed(1)}% YoY)</span>`;
                                }
                                if (prevComp > 0) {
                                    const diffComp = comp - prevComp;
                                    const sign = diffComp >= 0 ? '+' : '';
                                    compYoYHtml = ` <span style="font-size:11px;color:${diffComp >= 0 ? '#10b981' : '#ef4444'};">(${sign}${diffComp.toFixed(2)}% YoY)</span>`;
                                }
                            }

                            // Cost per 1% competency
                            let costPerCompHtml = '';
                            if (comp > 0 && cost > 0) {
                                const costPerOnePercent = cost / comp;
                                costPerCompHtml = `
                                        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0; font-size: 12px; color: #475569;">
                                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i>
                                            <strong>Cost per 1% Competency:</strong> Rp ${formatShortCurrency(costPerOnePercent)}
                                        </div>
                                    `;
                            }

                            return `
                                    <div style="font-weight: 700; margin-bottom: 8px; color: #0f172a; font-size: 14px; border-bottom: 2px solid #f1f5f9; padding-bottom: 4px;">
                                        Tahun ${year}
                                    </div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 6px;">
                                        <span>
                                            <span style="display:inline-block;margin-right:6px;border-radius:50%;width:10px;height:10px;background-color:#10b981;"></span>
                                            Total Biaya Training:
                                        </span>
                                        <strong>Rp ${cost.toLocaleString('id-ID')}${costYoYHtml}</strong>
                                    </div>
                                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px;">
                                        <span>
                                            <span style="display:inline-block;margin-right:6px;border-radius:50%;width:10px;height:10px;background-color:#3b82f6;"></span>
                                            Pemenuhan Kompetensi:
                                        </span>
                                        <strong>${comp.toFixed(2)}%${compYoYHtml}</strong>
                                    </div>
                                    ${costPerCompHtml}
                                `;
                        }
                    },
                    legend: {
                        data: ['Total Biaya Training', 'Pemenuhan Kompetensi'],
                        top: 0,
                        textStyle: { color: '#475569', fontWeight: 600 }
                    },
                    grid: { top: '18%', left: '3%', right: '3%', bottom: '5%', containLabel: true },
                    xAxis: {
                        type: 'category',
                        data: categories,
                        axisLine: { lineStyle: { color: '#cbd5e1' } },
                        axisLabel: { color: '#475569', fontWeight: 600, margin: 12 },
                        axisPointer: { type: 'shadow' }
                    },
                    yAxis: [
                        {
                            type: 'value',
                            name: 'Biaya Training (Rp)',
                            nameTextStyle: { color: '#64748b', fontWeight: 600, padding: [0, 0, 0, 20] },
                            alignTicks: true,
                            splitLine: {
                                show: true,
                                lineStyle: { type: 'dashed', color: '#f1f5f9' }
                            },
                            axisLine: { show: false },
                            axisTick: { show: false },
                            axisLabel: {
                                color: '#64748b',
                                formatter: formatShortCurrency
                            }
                        },
                        {
                            type: 'value',
                            name: 'Kompetensi (%)',
                            nameTextStyle: { color: '#64748b', fontWeight: 600, padding: [0, 20, 0, 0] },
                            min: 0,
                            max: 100,
                            alignTicks: true,
                            splitLine: { show: false },
                            axisLine: { show: false },
                            axisTick: { show: false },
                            axisLabel: {
                                color: '#64748b',
                                formatter: '{value}%'
                            }
                        }
                    ],
                    series: [
                        {
                            name: 'Total Biaya Training',
                            type: 'bar',
                            yAxisIndex: 0,
                            data: costData,
                            barMaxWidth: 50,
                            itemStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                    { offset: 0, color: '#34d399' }, // emerald-400
                                    { offset: 0.5, color: '#10b981' }, // emerald-500
                                    { offset: 1, color: '#059669' }  // emerald-600
                                ]),
                                borderRadius: [6, 6, 0, 0],
                                shadowColor: 'rgba(16, 185, 129, 0.25)',
                                shadowBlur: 8,
                                shadowOffsetY: 4
                            },
                            label: {
                                show: true,
                                position: 'top',
                                color: '#059669',
                                fontWeight: 600,
                                fontSize: 11,
                                formatter: (params) => formatShortCurrency(params.value)
                            }
                        },
                        {
                            name: 'Pemenuhan Kompetensi',
                            type: 'line',
                            yAxisIndex: 1,
                            data: compData,
                            smooth: true,
                            symbol: 'circle',
                            symbolSize: 10,
                            itemStyle: {
                                color: '#2563eb', // blue-600
                                borderColor: '#ffffff',
                                borderWidth: 2,
                                shadowColor: 'rgba(37, 99, 235, 0.4)',
                                shadowBlur: 6
                            },
                            lineStyle: {
                                width: 3.5,
                                color: '#3b82f6', // blue-500
                                shadowColor: 'rgba(59, 130, 246, 0.35)',
                                shadowBlur: 10,
                                shadowOffsetY: 4
                            },
                            areaStyle: {
                                color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                                    { offset: 0, color: 'rgba(59, 130, 246, 0.25)' },
                                    { offset: 0.8, color: 'rgba(59, 130, 246, 0.02)' },
                                    { offset: 1, color: 'rgba(59, 130, 246, 0.0)' }
                                ])
                            },
                            label: {
                                show: true,
                                position: 'top',
                                distance: 8,
                                color: '#1d4ed8', // blue-700
                                fontWeight: 700,
                                fontSize: 12,
                                backgroundColor: 'rgba(239, 246, 255, 0.9)', // blue-50
                                borderColor: '#bfdbfe', // blue-200
                                borderWidth: 1,
                                borderRadius: 4,
                                padding: [2, 6],
                                formatter: (params) => Number(params.value).toFixed(1) + '%'
                            }
                        }
                    ]
                };

                instance.setOption(option);
            }
            @endif

            const fetchCompanyData = () => {
                if (isCompanyLoading) return;
                setButtonLoading(companyApplyButton, true);
                isCompanyLoading = true;

                const url = new URL(endpoints.company);
                url.searchParams.set('year_from', companyYearFromInput.value);
                url.searchParams.set('year_to', companyYearToInput.value);
                url.searchParams.set('_t', Date.now());

                const companyRequest = fetch(url).then(response => response.json());
                @if ($canViewTcpdSensitive ?? false)
                const sensitiveUrl = new URL(endpoints.sensitive);
                sensitiveUrl.searchParams.set('year_from', companyYearFromInput.value);
                sensitiveUrl.searchParams.set('year_to', companyYearToInput.value);
                sensitiveUrl.searchParams.set('_t', Date.now());
                const request = Promise.all([
                    companyRequest,
                    fetch(sensitiveUrl).then(response => response.ok ? response.json() : Promise.reject(new Error('Sensitive TCPD access ditolak.'))),
                ]).then(([companyResult, sensitiveResult]) => {
                    if (companyResult.success && companyResult.data && sensitiveResult.success && sensitiveResult.data) {
                        companyResult.data.insights = {
                            ...(companyResult.data.insights || {}),
                            key_position_stats: sensitiveResult.data.key_position_stats || [],
                        };
                        companyResult.data.training_effectiveness = sensitiveResult.data.training_effectiveness || {};
                    }
                    return companyResult;
                });
                @else
                const request = companyRequest;
                @endif

                request
                    .then(result => {
                        if (result.success && result.data) {
                            const { company_chart_rows, department_summaries, ...meta } = result.data;
                            currentDepartmentData = Array.isArray(department_summaries) ? [...department_summaries] : [];
                            initCompanyChart(company_chart_rows || [], {
                                hasData: meta.company_has_data,
                                average: meta.company_average,
                                departmentCount: meta.company_department_count,
                                years: meta.company_years,
                                mode: meta.company_chart_mode,
                            });
                            renderDepartmentGrid(currentDepartmentData);

                            // Update Scorecards
                            const formatNum = (val) => Number.isFinite(val) ? Number(val).toFixed(2) + '%' : 'N/A';
                            document.getElementById('scorecard-average').innerText = formatNum(meta.company_average);
                            document.getElementById('scorecard-dept-count').innerText = meta.company_department_count || '0';

                            let topDept = { name: '-', val: null };
                            let lowestDept = { name: '-', val: null };

                            if (currentDepartmentData.length > 0) {
                                let validDepts = currentDepartmentData.filter(d => d.overall !== null && isFinite(d.overall));
                                if (validDepts.length > 0) {
                                    validDepts.sort((a, b) => b.overall - a.overall);
                                    topDept = { name: validDepts[0].department, val: validDepts[0].overall };
                                    lowestDept = { name: validDepts[validDepts.length - 1].department, val: validDepts[validDepts.length - 1].overall };
                                }
                            }

                            document.getElementById('scorecard-top-dept').innerText = topDept.name;
                            document.getElementById('scorecard-top-val').innerText = topDept.val !== null ? formatNum(topDept.val) : 'N/A';
                            document.getElementById('scorecard-lowest-dept').innerText = lowestDept.name;
                            document.getElementById('scorecard-lowest-val').innerText = lowestDept.val !== null ? formatNum(lowestDept.val) : 'N/A';

                            // Populate Smart Insights
                            if (meta.insights) {
                                document.getElementById('smart-insights-row').style.display = 'flex';

                                const topJobsEl = document.getElementById('insight-top-jobs');
                                if (meta.insights.top_jobs && meta.insights.top_jobs.length > 0) {
                                    topJobsEl.innerHTML = meta.insights.top_jobs.map((job, idx) => {
                                        const employeesJson = escapeHtml(JSON.stringify(job.employees || []));
                                        const jobName = escapeHtml(job.job_position);
                                        const percentage = Number(job.percentage).toFixed(2) + '%';
                                        return `
                                            <div class="d-flex justify-content-between align-items-center bg-white bg-opacity-75 p-2 rounded shadow-sm top-job-item"
                                                 role="button" style="cursor:pointer;"
                                                 data-job-name="${jobName}" data-percentage="${percentage}"
                                                 data-employees="${employeesJson}">
                                                <span class="text-dark fw-semibold"><span class="badge bg-success bg-opacity-25 text-success me-2">#${idx + 1}</span>${jobName}</span>
                                                <span class="badge bg-success rounded-pill px-3 py-2 fs-6 shadow-sm">${percentage}</span>
                                            </div>`;
                                    }).join('');

                                    // Attach click handler for top jobs modal
                                    topJobsEl.querySelectorAll('.top-job-item').forEach(item => {
                                        item.addEventListener('click', () => {
                                            const jobName = item.dataset.jobName;
                                            const percentage = item.dataset.percentage;
                                            const employees = JSON.parse(item.dataset.employees || '[]');

                                            document.getElementById('tjm-title').textContent = jobName;
                                            document.getElementById('tjm-badge-row').innerHTML =
                                                `<span class="badge bg-success">Overall: ${percentage}</span>
                                                     <span class="badge bg-secondary">${employees.length} karyawan</span>`;

                                            const tbody = document.getElementById('tjm-tbody');
                                            if (employees.length === 0) {
                                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Data karyawan tidak tersedia.</td></tr>';
                                            } else {
                                                tbody.innerHTML = employees.map((emp, idx) => {
                                                    const name = `${escapeHtml(emp.npk || '-')} — ${escapeHtml(emp.name || '-')}`;
                                                    const tc = emp.tc !== null ? Number(emp.tc).toFixed(2) + '%' : '0.00%';
                                                    const sk = emp.sk !== null ? Number(emp.sk).toFixed(2) + '%' : '0.00%';
                                                    const ad = emp.ad !== null ? Number(emp.ad).toFixed(2) + '%' : '0.00%';
                                                    return `
                                                            <tr>
                                                                <td>${idx + 1}</td>
                                                                <td><i class="bi bi-person-fill text-success me-1"></i>${name}</td>
                                                                <td style="text-align:center;"><span class="badge bg-primary rounded-pill px-2 py-1">${tc}</span></td>
                                                                <td style="text-align:center;"><span class="badge bg-success rounded-pill px-2 py-1">${sk}</span></td>
                                                                <td style="text-align:center;"><span class="badge bg-info rounded-pill px-2 py-1">${ad}</span></td>
                                                            </tr>
                                                        `;
                                                }).join('');
                                            }

                                            const modal = new bootstrap.Modal(document.getElementById('topJobsModal'));
                                            modal.show();
                                        });
                                    });
                                } else {
                                    topJobsEl.innerHTML = '<span class="text-muted small">Data tidak tersedia</span>';
                                }

                                const criticalFocusEl = document.getElementById('insight-critical-focus');
                                if (meta.insights.critical_focus && meta.insights.critical_focus.length > 0) {
                                    const cfItems = meta.insights.critical_focus;
                                    const cfPerPage = 5;
                                    let cfCurrentPage = 1;
                                    const cfTotalPages = Math.ceil(cfItems.length / cfPerPage);

                                    const renderCFPage = (page) => {
                                        const start = (page - 1) * cfPerPage;
                                        const end = start + cfPerPage;
                                        const itemsToShow = cfItems.slice(start, end);

                                        let html = itemsToShow.map((comp) => {
                                            const employeesJson = escapeHtml(JSON.stringify(comp.employees || []));
                                            const compName = escapeHtml(comp.name);
                                            return `
                                                <div class="d-flex justify-content-between align-items-center bg-white bg-opacity-75 p-2 rounded border border-danger border-opacity-25 shadow-sm
                                                             critical-focus-item" role="button" style="cursor:pointer;"
                                                     data-comp-name="${compName}" data-comp-type="${escapeHtml(comp.type)}"
                                                     data-employees="${employeesJson}">
                                                    <div class="text-dark fw-semibold text-truncate" style="max-width: 70%;" title="${compName}">
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary me-2" style="font-size:0.65rem;">${comp.type}</span>${compName}
                                                    </div>
                                                    <span class="badge bg-danger rounded-pill shadow-sm"><i class="bi bi-people-fill me-1"></i>${comp.qty}</span>
                                                </div>`;
                                        }).join('');

                                        if (cfTotalPages > 1) {
                                            html += `
                                                <div class="d-flex justify-content-between align-items-center mt-2 border-top pt-2 border-danger border-opacity-10">
                                                    <span class="small text-danger text-opacity-75">Halaman ${page} dari ${cfTotalPages}</span>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-outline-danger cf-prev" ${page === 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>
                                                        <button class="btn btn-outline-danger cf-next" ${page === cfTotalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>
                                                    </div>
                                                </div>`;
                                        }

                                        criticalFocusEl.innerHTML = html;

                                        criticalFocusEl.querySelectorAll('.critical-focus-item').forEach(item => {
                                            item.addEventListener('click', () => {
                                                const name = item.dataset.compName;
                                                const type = item.dataset.compType;
                                                const employees = JSON.parse(item.dataset.employees || '[]');

                                                document.getElementById('cfm-title').textContent = name;
                                                document.getElementById('cfm-badge-row').innerHTML =
                                                    `<span class="badge bg-danger">${type}</span>
                                                         <span class="badge bg-secondary">${employees.length} karyawan defisit</span>`;

                                                const tbody = document.getElementById('cfm-tbody');
                                                if (employees.length === 0) {
                                                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Data karyawan tidak tersedia.</td></tr>';
                                                } else {
                                                    tbody.innerHTML = employees.map((emp, idx) => `
                                                            <tr>
                                                                <td>${idx + 1}</td>
                                                                <td><i class="bi bi-person-fill text-danger me-1"></i>${escapeHtml(emp.npk || '-')} — ${escapeHtml(emp.name || '-')}</td>
                                                                <td><span class="badge bg-light text-dark border">${escapeHtml(emp.job_position || '-')}</span></td>
                                                                <td><span class="badge bg-warning text-dark">${emp.actual !== null ? Number(emp.actual).toFixed(2) : '-'}</span></td>
                                                                <td><span class="badge bg-primary">${emp.standard !== null ? Number(emp.standard).toFixed(2) : '-'}</span></td>
                                                            </tr>
                                                        `).join('');
                                                }

                                                const modal = new bootstrap.Modal(document.getElementById('criticalFocusModal'));
                                                modal.show();
                                            });
                                        });

                                        const prevBtn = criticalFocusEl.querySelector('.cf-prev');
                                        const nextBtn = criticalFocusEl.querySelector('.cf-next');
                                        if (prevBtn) {
                                            prevBtn.addEventListener('click', (e) => {
                                                e.stopPropagation();
                                                if (cfCurrentPage > 1) {
                                                    cfCurrentPage--;
                                                    renderCFPage(cfCurrentPage);
                                                }
                                            });
                                        }
                                        if (nextBtn) {
                                            nextBtn.addEventListener('click', (e) => {
                                                e.stopPropagation();
                                                if (cfCurrentPage < cfTotalPages) {
                                                    cfCurrentPage++;
                                                    renderCFPage(cfCurrentPage);
                                                }
                                            });
                                        }
                                    };

                                    renderCFPage(cfCurrentPage);
                                } else {
                                    criticalFocusEl.innerHTML = '<span class="text-muted small">Data tidak tersedia</span>';
                                }

                                @if ($canViewTcpdSensitive ?? false)
                                // --- Modul 2.1: Key Position Stats ---
                                const kpStatsEl = document.getElementById('key-position-stats');
                                const kpRowEl = document.getElementById('key-position-row');
                                const kpTotalBadge = document.getElementById('kp-total-badge');
                                if (meta.insights.key_position_stats && meta.insights.key_position_stats.length > 0) {
                                    kpRowEl.style.display = 'flex';
                                    const stats = meta.insights.key_position_stats;
                                    kpTotalBadge.textContent = stats.length + ' Key Position';
                                    kpStatsEl.innerHTML = stats.map(kp => {
                                        const name = escapeHtml(kp.job_position || '-');
                                        const empCount = kp.employee_count || 0;
                                        const strengthCount = kp.strength_count || 0;
                                        const deficitCount = kp.deficit_count || 0;
                                        const pct = kp.percentage !== undefined ? parseFloat(kp.percentage).toFixed(1) : '0.0';
                                        const barWidth = Math.min(100, parseFloat(pct));
                                        const barColor = barWidth >= 70 ? '#198754' : barWidth >= 40 ? '#ffc107' : '#dc3545';
                                        const employeesJson = escapeHtml(JSON.stringify(kp.employees || []));
                                        return `
                                            <div class="col-md-4 col-sm-6 key-position-item" role="button" style="cursor:pointer;"
                                                data-job-name="${name}" 
                                                data-percentage="${pct}" 
                                                data-employees="${employeesJson}">
                                                <div class="bg-white rounded-3 p-3 shadow-sm h-100">
                                                    <div class="fw-semibold text-dark mb-2 text-wrap" style="font-size:0.85rem;line-height:1.4;word-break:break-word;white-space:normal;" title="${name}">
                                                        <i class="bi bi-briefcase-fill text-primary me-1"></i>
                                                        <span>${name}</span>
                                                    </div>
                                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                                        <span><i class="bi bi-people me-1"></i>${empCount} karyawan</span>
                                                        <span class="text-success"><i class="bi bi-check-circle me-1"></i>${strengthCount} terpenuhi</span>
                                                        <span class="text-danger"><i class="bi bi-x-circle me-1"></i>${deficitCount} defisit</span>
                                                    </div>
                                                    <div class="progress" style="height:6px;border-radius:3px;">
                                                        <div class="progress-bar" role="progressbar"
                                                            style="width:${barWidth}%;background-color:${barColor};"
                                                            aria-valuenow="${barWidth}" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <div class="text-end small mt-1 fw-semibold" style="color:${barColor};">${pct}%</div>
                                                </div>
                                            </div>`;
                                    }).join('');

                                    // Attach event listener for key position modal
                                    kpStatsEl.querySelectorAll('.key-position-item').forEach(item => {
                                        item.addEventListener('click', (e) => {
                                            const jobName = item.getAttribute('data-job-name');
                                            const pct = item.getAttribute('data-percentage');
                                            let employees = [];
                                            try {
                                                employees = JSON.parse(item.getAttribute('data-employees'));
                                            } catch (err) {
                                                console.error('Failed to parse employees JSON', err);
                                            }

                                            document.getElementById('kp-tjm-title').textContent = jobName;
                                            const badgeRow = document.getElementById('kp-tjm-badge-row');
                                            badgeRow.innerHTML = `
                                                <span class="badge bg-primary text-white p-2">Total Karyawan: ${employees.length}</span>
                                                <span class="badge bg-light text-dark border p-2">Nilai Rata-rata: ${pct}%</span>
                                            `;

                                            const tbody = document.getElementById('kp-tjm-tbody');
                                            if (employees.length === 0) {
                                                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Belum ada karyawan pada posisi ini</td></tr>';
                                            } else {
                                                tbody.innerHTML = employees.map((emp, index) => {
                                                    const tc = (emp.tc != null ? emp.tc : 0).toFixed(1);
                                                    const sk = (emp.sk != null ? emp.sk : 0).toFixed(1);
                                                    const ad = (emp.ad != null ? emp.ad : 0).toFixed(1);
                                                    return `
                                                        <tr>
                                                            <td class="text-center text-muted">${index + 1}</td>
                                                            <td class="fw-medium">${escapeHtml(emp.npk || '-')} — ${escapeHtml(emp.name || '-')}</td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light text-dark border" style="width:50px">${tc}%</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light text-dark border" style="width:50px">${sk}%</span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-light text-dark border" style="width:50px">${ad}%</span>
                                                            </td>
                                                        </tr>
                                                    `;
                                                }).join('');
                                            }
                                            
                                            const modal = new bootstrap.Modal(document.getElementById('keyPositionModal'));
                                            modal.show();
                                        });
                                    });
                                } else {
                                    if (kpRowEl) kpRowEl.style.display = 'flex';
                                    if (kpTotalBadge) kpTotalBadge.textContent = '0 Key Position';
                                    if (kpStatsEl) kpStatsEl.innerHTML = '<div class="col-12"><div class="bg-white rounded-3 p-3 shadow-sm text-center text-muted small h-100 d-flex align-items-center justify-content-center">Data tidak tersedia</div></div>';
                                }
                                @endif

                            } else {
                                document.getElementById('smart-insights-row').style.display = 'flex';
                                document.getElementById('insight-top-jobs').innerHTML = '<span class="text-muted small">Data tidak tersedia</span>';
                                document.getElementById('insight-critical-focus').innerHTML = '<span class="text-muted small">Data tidak tersedia</span>';

                                @if ($canViewTcpdSensitive ?? false)
                                const kpStatsEl = document.getElementById('key-position-stats');
                                const kpRowEl = document.getElementById('key-position-row');
                                const kpTotalBadge = document.getElementById('kp-total-badge');
                                if (kpRowEl) kpRowEl.style.display = 'flex';
                                if (kpTotalBadge) kpTotalBadge.textContent = '0 Key Position';
                                if (kpStatsEl) kpStatsEl.innerHTML = '<div class="col-12"><div class="bg-white rounded-3 p-3 shadow-sm text-center text-muted small h-100 d-flex align-items-center justify-content-center">Data tidak tersedia</div></div>';
                                @endif
                            }

                            @if ($canViewTcpdSensitive ?? false)
                            // Render Training Effectiveness Chart
                            document.getElementById('training-effectiveness-row').style.display = 'flex';
                            if (meta.training_effectiveness && Object.keys(meta.training_effectiveness).length > 0 && meta.company_years && meta.company_years.length > 0) {
                                renderEffectivenessChart(meta.training_effectiveness, meta.company_years, company_chart_rows);
                            } else {
                                const chartContainer = document.getElementById('effectiveness-chart');
                                if (chartContainer) {
                                    let instance = echarts.getInstanceByDom(chartContainer);
                                    if (instance) instance.clear();
                                    chartContainer.innerHTML = '<div class="d-flex align-items-center justify-content-center w-100 h-100"><span class="text-muted small">Data tidak tersedia</span></div>';
                                }
                            }
                            @endif

                        } else {
                            currentDepartmentData = [];
                            renderDepartmentGrid([]);
                            document.getElementById('scorecard-average').innerText = 'N/A';
                            document.getElementById('scorecard-dept-count').innerText = '0';
                            document.getElementById('scorecard-top-dept').innerText = '-';
                            document.getElementById('scorecard-top-val').innerText = 'N/A';
                            document.getElementById('scorecard-lowest-dept').innerText = '-';
                            document.getElementById('scorecard-lowest-val').innerText = 'N/A';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching company data:', error);
                        currentDepartmentData = [];
                        renderDepartmentGrid([]);
                    })
                    .finally(() => {
                        setButtonLoading(companyApplyButton, false);
                        isCompanyLoading = false;
                    });
            };

            const fetchJobData = () => {
                if (isJobLoading) return;
                if (!jobSelect) {
                    clearJobData();
                    return;
                }
                setButtonLoading(jobApplyButton, true);
                isJobLoading = true;
                if (jobDepartmentSelect) {
                    currentDepartment = jobDepartmentSelect.value || null;
                }
                const rawJobValue = jobSelect.value;
                if (rawJobValue === '' || rawJobValue === null || rawJobValue === undefined) {
                    currentJobPositionId = null;
                } else {
                    const parsedJobId = Number(rawJobValue);
                    currentJobPositionId = Number.isFinite(parsedJobId) ? parsedJobId : null;
                }
                if (jobSelect) {
                    const selectedOption = jobSelect.options[jobSelect.selectedIndex];
                    currentJobPositionName = selectedOption && selectedOption.text ? selectedOption.text.trim() : '';
                }
                if (!currentJobPositionName && typeof initialJobPositionName === 'string' && currentJobPositionId !== null) {
                    currentJobPositionName = initialJobPositionName;
                }
                if (currentJobPositionId === null) {
                    setButtonLoading(jobApplyButton, false);
                    isJobLoading = false;
                    clearJobData();
                    return;
                }

                const url = new URL(endpoints.job);
                url.searchParams.set('job_position_id', jobSelect.value);
                url.searchParams.set('date_from', jobDateFromInput ? jobDateFromInput.value : '');
                url.searchParams.set('date_to', jobDateToInput ? jobDateToInput.value : '');
                if (jobDepartmentSelect) {
                    url.searchParams.set('department', jobDepartmentSelect.value || '');
                }
                url.searchParams.set('_t', Date.now());

                fetch(url)

                    .then(response => response.json())
                    .then(result => {
                        if (result && result.success && result.data) {
                            const context = {
                                jobPositionId: currentJobPositionId,
                                jobPositionName: currentJobPositionName,
                                department: currentDepartment,
                            };
                            initJobPositionChart(result.data.user_summaries || [], context);
                            updateCompetencyTable(result.data.competencies || []);
                            updateJobSummary(result.data, currentJobPositionName);
                        } else {
                            clearJobData();
                            if (result && result.message) {
                                console.warn('Job data response:', result.message);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching job data:', error);
                        clearJobData();
                    })
                    .finally(() => {
                        setButtonLoading(jobApplyButton, false);
                        isJobLoading = false;
                    });
            };

            if (companyApplyButton) companyApplyButton.addEventListener('click', fetchCompanyData);
            if (companyResetButton) companyResetButton.addEventListener('click', () => {
                companyYearFromInput.value = defaultCompanyYears.from || '';
                companyYearToInput.value = defaultCompanyYears.to || '';
                fetchCompanyData();
            });
            if (companyYearFromInput) companyYearFromInput.addEventListener('change', () => fetchCompanyData());
            if (companyYearToInput) companyYearToInput.addEventListener('change', () => fetchCompanyData());

            if (jobApplyButton) jobApplyButton.addEventListener('click', fetchJobData);
            if (jobSelect) jobSelect.addEventListener('change', fetchJobData);
            if (jobDepartmentSelect) {
                jobDepartmentSelect.addEventListener('change', () => {
                    const selectedValue = jobDepartmentSelect.value || null;
                    updateJobOptions(selectedValue, { preserveSelection: false, triggerFetch: true });
                });
            }
            if (jobDateFromInput) jobDateFromInput.addEventListener('change', () => fetchJobData());
            if (jobDateToInput) jobDateToInput.addEventListener('change', () => fetchJobData());
            if (jobResetButton) jobResetButton.addEventListener('click', () => {
                if (jobDepartmentSelect) {
                    const fallbackDepartment = defaultDepartment ?? (jobDepartmentSelect.options && jobDepartmentSelect.options.length ? jobDepartmentSelect.options[0].value : '');
                    jobDepartmentSelect.value = fallbackDepartment;
                    updateJobOptions(fallbackDepartment || null, { preserveSelection: false, triggerFetch: false });
                } else if (jobSelect) {
                    let fallbackValue = '';
                    if (defaultJobPositionId !== null && defaultJobPositionId !== undefined) {
                        fallbackValue = String(defaultJobPositionId);
                    } else if (jobSelect.options && jobSelect.options.length) {
                        fallbackValue = jobSelect.options[0].value;
                    }
                    jobSelect.value = fallbackValue;
                    const option = jobSelect.options[jobSelect.selectedIndex];
                    currentJobPositionName = option && option.text ? option.text.trim() : '';
                    currentJobPositionId = jobSelect.value ? Number(jobSelect.value) : null;
                }
                if (!currentJobPositionName && typeof initialJobPositionName === 'string' && currentJobPositionId !== null) {
                    currentJobPositionName = initialJobPositionName;
                }
                if (jobDateFromInput) jobDateFromInput.value = defaultJobDates.from || '';
                if (jobDateToInput) jobDateToInput.value = defaultJobDates.to || '';
                fetchJobData();
            });

            if (jobDepartmentSelect) {
                updateJobOptions(jobDepartmentSelect.value || null, { preserveSelection: true, triggerFetch: false });
            }

            renderDepartmentGrid(currentDepartmentData);
            const prefetchFlags = @json($prefetchFlags);
            if (prefetchFlags.company) {
                fetchCompanyData();
            }
            if (prefetchFlags.job) {
                fetchJobData();
            }

            function showMentorsModal(mentors) {
                const tbody = document.getElementById('mentors-modal-tbody');
                if (!tbody) return;

                if (!mentors || mentors.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted">Data mentor tidak tersedia.</td></tr>';
                } else {
                    tbody.innerHTML = mentors.map((m, idx) => {
                        const name = escapeHtml(m.name || '-');
                        const job = escapeHtml(m.job_position || '-');
                        const actual = m.actual !== null ? Number(m.actual).toFixed(2) : '-';
                        return `
                            <tr>
                                <td class="text-center">${idx + 1}</td>
                                <td><div class="fw-semibold text-primary"><i class="bi bi-person-check-fill me-2"></i>${name}</div></td>
                                <td>${job}</td>
                                <td class="text-center"><span class="badge bg-success bg-opacity-25 text-success rounded-pill px-3">${actual}</span></td>
                            </tr>`;
                    }).join('');
                }

                const modalEl = document.getElementById('all-mentors-modal');
                if (modalEl) {
                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                }
            }
        });
    </script>

    <!-- Modal Daftar Mentor -->
    <div class="modal fade" id="all-mentors-modal" tabindex="-1" aria-labelledby="allMentorsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title fw-bold" id="allMentorsModalLabel"><i class="bi bi-people-fill me-2"></i>Daftar
                        Saran Mentor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-secondary">
                                <tr>
                                    <th class="text-center" width="5%">No</th>
                                    <th width="40%">Nama Mentor</th>
                                    <th width="35%">Jabatan</th>
                                    <th class="text-center" width="20%">Nilai Aktual</th>
                                </tr>
                            </thead>
                            <tbody id="mentors-modal-tbody">
                                <!-- Populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top-0 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
