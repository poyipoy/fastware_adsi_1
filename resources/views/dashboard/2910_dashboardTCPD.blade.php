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
            background: repeating-linear-gradient(
                    135deg,
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

        #tcpdCarousel .carousel-inner {
            cursor: grab;
        }

        #tcpdCarousel .carousel-inner.grabbing {
            cursor: grabbing;
        }

        @media (max-width: 992px) {
            .dashboard-tcpd .dashboard-card {
                min-height: 220px;
            }
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
                    $jobPositions = $jobPositions ?? collect();
                    $selectedJobPositionId = $selectedJobPositionId ?? null;
                    $selectedJobPositionName = $selectedJobPositionName ?? null;
                    $competencyRows = $competencyRows ?? [];
                    $userCountByJobPosition = $userCountByJobPosition ?? 0;
                    $totalPercentage = isset($totalPercentage) && is_numeric($totalPercentage) ? (float) $totalPercentage : null;
                    $userSummaries = collect($userSummaries ?? [])->values()->all();
                    $hasChartData = ($totalPercentage !== null) || collect($userSummaries)->some(fn ($u) => ($u['tc_percentage'] ?? null) !== null || ($u['sk_percentage'] ?? null) !== null || ($u['ad_percentage'] ?? null) !== null);
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
                    $companyYears = collect($companyOverview['years'] ?? [])->map(fn ($year) => (int) $year)->sort()->values();
                    $companyMode = $companyOverview['mode'] ?? ($companyYears->isNotEmpty() ? 'yearly' : 'aggregate');
                    $companyEmptyMessage = $companyHasData ? '' : 'Data persentase departemen belum tersedia.';

                    $yearOptions = collect($yearOptions ?? [])->map(fn ($year) => (int) $year)->sort()->values();
                    $companyYearFrom = $companyYearFrom ?? null;
                    $companyYearTo = $companyYearTo ?? null;
                    $jobDateFrom = $jobDateFrom ?? null;
                    $jobDateTo = $jobDateTo ?? null;

                    $prefetchFlags = [
                        'company' => !empty($shouldPrefetchCompany),
                        'departments' => !empty($shouldPrefetchDepartments),
                        'job' => !empty($shouldPrefetchJob),
                    ];

                    $departmentMeta = [
                        'Logistik Dept' => [
                            'chart_id' => 'department-chart-logistik',
                            'empty_id' => 'department-chart-logistik-empty',
                            'summary_id' => 'department-chart-logistik-summary',
                            'legend_id' => 'department-legend-logistik',
                        ],
                        'Sales Dept' => [
                            'chart_id' => 'department-chart-sales',
                            'empty_id' => 'department-chart-sales-empty',
                            'summary_id' => 'department-chart-sales-summary',
                            'legend_id' => 'department-legend-sales',
                        ],
                        'PDCA, Procurement, Inventory & IT Dept' => [
                            'chart_id' => 'department-chart-procurement',
                            'empty_id' => 'department-chart-procurement-empty',
                            'summary_id' => 'department-chart-procurement-summary',
                            'legend_id' => 'department-legend-procurement',
                        ],
                        'Finance, Accounting, HRGA Dept' => [
                            'chart_id' => 'department-chart-finance',
                            'empty_id' => 'department-chart-finance-empty',
                            'summary_id' => 'department-chart-finance-summary',
                            'legend_id' => 'department-legend-finance',
                        ],
                        'Productions Dept' => [
                            'chart_id' => 'department-chart-produksi',
                            'empty_id' => 'department-chart-produksi-empty',
                            'summary_id' => 'department-chart-produksi-summary',
                            'legend_id' => 'department-legend-produksi',
                        ],
                    ];

                    $departmentGroups = [
                        'logistics-sales' => ['Logistik', 'Sales'],
                        'procurement-finance' => ['Procurement', 'Finance, AR, HRGA'],
                        'produksi' => ['Produksi'],
                    ];

                    $detailCompetencyUrl = route('dsDetailCompetency');
                @endphp
                <div id="tcpdCarousel" class="carousel slide dashboard-carousel" data-bs-ride="carousel" data-bs-interval="10000">
                    <div class="carousel-inner">
                        <div class="carousel-item active" data-slide-key="company">
                            <div class="carousel-stage">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card dashboard-card level-1 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                                                        <div>
                                                            <h5 class="card-title mb-2">Company</h5>
                                                            <p class="text-muted mb-0 small">
                                                                Perbandingan rata-rata pencapaian perusahaan dan setiap departemen.
                                                            </p>
                                                        </div>
                                                        <form id="company-filter-form" class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                                                            <div class="d-flex align-items-center gap-1">
                                                                <span class="year-filter-label mb-0">Year</span>
                                                                <select
                                                                    id="company-year-from"
                                                                    name="company_year_from"
                                                                    class="form-select form-select-sm year-filter-select"
                                                                >
                                                                    <option value="">All</option>
                                                                    @foreach ($yearOptions as $year)
                                                                        <option value="{{ $year }}" {{ (string) $year === (string) $companyYearFrom ? 'selected' : '' }}>
                                                                            {{ $year }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                <span class="small text-muted">to</span>
                                                                <select
                                                                    id="company-year-to"
                                                                    name="company_year_to"
                                                                    class="form-select form-select-sm year-filter-select"
                                                                >
                                                                    <option value="">All</option>
                                                                    @foreach ($yearOptions as $year)
                                                                        <option value="{{ $year }}" {{ (string) $year === (string) $companyYearTo ? 'selected' : '' }}>
                                                                            {{ $year }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button type="button" id="company-filter-apply" class="btn btn-sm btn-outline-primary">Apply</button>
                                                                <button type="button" id="company-filter-reset" class="btn btn-sm btn-link text-decoration-none">Reset</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                                <div class="mt-1">
                                                    <div
                                                        id="company-chart"
                                                        class="w-100"
                                                        style="height: 320px;"
                                                    ></div>
                                                    <div
                                                        id="company-chart-empty"
                                                        class="text-center text-muted small py-4 d-none"
                                                        data-empty-message="{{ $companyEmptyMessage ?: 'Data persentase departemen belum tersedia.' }}"
                                                    >
                                                        {{ $companyEmptyMessage ?: 'Data persentase departemen belum tersedia.' }}
                                                    </div>
                                                </div>
                                                <div id="company-chart-legend" class="tcpd-legend d-none"></div>
                                                <p
                                                    id="company-chart-summary"
                                                    class="text-muted small mb-0 d-none"
                                                >
                                                    <span class="fw-semibold">Company Average:</span>
                                                        N/A
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item" data-slide-key="logistics-sales">
                            <div class="carousel-stage">
                                <div class="row g-3">
                                    <div class="col-12 col-xl-6">
                                        <div class="card dashboard-card level-2 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">Logistic Dept</h6>
                                                        <p class="text-muted mb-0 small">
                                                            Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                                        </p>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div
                                                            id="department-chart-0"
                                                            class="w-100"
                                                            style="height: 340px;"
                                                        ></div>
                                                        <div
                                                            id="department-chart-0-empty"
                                                            class="text-center text-muted small py-4 d-none"
                                                        >
                                                            Data akan dimuat ketika slide aktif.
                                                        </div>
                                                    </div>
                                                    <div id="department-chart-0-legend" class="tcpd-legend d-none"></div>
                                                    <p id="department-chart-0-summary" class="text-muted small mb-0">
                                                        <span class="fw-semibold">Total Pencapaian:</span> N/A
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-xl-6">
                                        <div class="card dashboard-card level-2 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">Sales Dept</h6>
                                                        <p class="text-muted mb-0 small">
                                                            Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                                        </p>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div
                                                            id="department-chart-1"
                                                            class="w-100"
                                                            style="height: 340px;"
                                                        ></div>
                                                        <div
                                                            id="department-chart-1-empty"
                                                            class="text-center text-muted small py-4 d-none"
                                                        >
                                                            Data akan dimuat ketika slide aktif.
                                                        </div>
                                                    </div>
                                                    <div id="department-chart-1-legend" class="tcpd-legend d-none"></div>
                                                    <p id="department-chart-1-summary" class="text-muted small mb-0">
                                                        <span class="fw-semibold">Total Pencapaian:</span> N/A
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item" data-slide-key="procurement-finance">
                            <div class="carousel-stage">
                                <div class="row g-3">
                                    <div class="col-12 col-xl-6">
                                        <div class="card dashboard-card level-2 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">PDCA, Procurement, Inventory & IT Dept</h6>
                                                        <p class="text-muted mb-0 small">
                                                            Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                                        </p>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div
                                                            id="department-chart-2"
                                                            class="w-100"
                                                            style="height: 340px;"
                                                        ></div>
                                                        <div
                                                            id="department-chart-2-empty"
                                                            class="text-center text-muted small py-4 d-none"
                                                        >
                                                            Data akan dimuat ketika slide aktif.
                                                        </div>
                                                    </div>
                                                    <div id="department-chart-2-legend" class="tcpd-legend d-none"></div>
                                                    <p id="department-chart-2-summary" class="text-muted small mb-0">
                                                        <span class="fw-semibold">Total Pencapaian:</span> N/A
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-xl-6">
                                        <div class="card dashboard-card level-2 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">Finance, Accounting, HRGA Dept</h6>
                                                        <p class="text-muted mb-0 small">
                                                            Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                                        </p>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div
                                                            id="department-chart-3"
                                                            class="w-100"
                                                            style="height: 340px;"
                                                        ></div>
                                                        <div
                                                            id="department-chart-3-empty"
                                                            class="text-center text-muted small py-4 d-none"
                                                        >
                                                            Data akan dimuat ketika slide aktif.
                                                        </div>
                                                    </div>
                                                    <div id="department-chart-3-legend" class="tcpd-legend d-none"></div>
                                                    <p id="department-chart-3-summary" class="text-muted small mb-0">
                                                        <span class="fw-semibold">Total Pencapaian:</span> N/A
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item" data-slide-key="produksi">
                            <div class="carousel-stage">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card dashboard-card level-2 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column gap-2">
                                                    <div>
                                                        <h6 class="card-title mb-1">Productions Dept</h6>
                                                        <p class="text-muted mb-0 small">
                                                            Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                                        </p>
                                                    </div>
                                                    <div class="mt-1">
                                                        <div
                                                            id="department-chart-4"
                                                            class="w-100"
                                                            style="height: 360px;"
                                                        ></div>
                                                        <div
                                                            id="department-chart-4-empty"
                                                            class="text-center text-muted small py-4 d-none"
                                                        >
                                                            Data akan dimuat ketika slide aktif.
                                                        </div>
                                                    </div>
                                                    <div id="department-chart-4-legend" class="tcpd-legend d-none"></div>
                                                    <p id="department-chart-4-summary" class="text-muted small mb-0">
                                                        <span class="fw-semibold">Total Pencapaian:</span> N/A
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item" data-slide-key="job-overview">
                            <div class="carousel-stage">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card dashboard-card level-4 h-100">
                                            <div class="card-body">
                                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                                                    <div>
                                                        <h6 class="card-title mb-2">Job Position Overview</h6>
                                                        <p class="text-muted mb-0 small">
                                                            Persentase pencapaian kompetensi berdasarkan standar TC, SK, dan AD pada job position terpilih.
                                                        </p>
                                                    </div>
                                                    @if ($jobPositions->isNotEmpty())
                                                        <form id="job-filter-form" class="d-flex align-items-end gap-3 flex-wrap justify-content-end">
                                                            <div class="d-flex flex-column">
                                                                <label for="job_position_id" class="form-label mb-1 small text-muted">Job Position</label>
                                                                <select
                                                                    id="job_position_id"
                                                                    name="job_position_id"
                                                                    class="form-select form-select-sm"
                                                                >
                                                                    @foreach ($jobPositions as $position)
                                                                        <option value="{{ $position->id }}" {{ (int) $position->id === (int) $selectedJobPositionId ? 'selected' : '' }}>
                                                                            {{ $position->job_position }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <label for="job-date-from" class="form-label mb-1 small text-muted">Tanggal Dari</label>
                                                                <input
                                                                    type="date"
                                                                    id="job-date-from"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $jobDateFrom }}"
                                                                >
                                                            </div>
                                                            <div class="d-flex flex-column">
                                                                <label for="job-date-to" class="form-label mb-1 small text-muted">Tanggal Sampai</label>
                                                                <input
                                                                    type="date"
                                                                    id="job-date-to"
                                                                    class="form-control form-control-sm"
                                                                    value="{{ $jobDateTo }}"
                                                                >
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <button type="button" id="job-filter-apply" class="btn btn-sm btn-outline-primary">Apply</button>
                                                                <button type="button" id="job-filter-reset" class="btn btn-sm btn-link text-decoration-none">Reset</button>
                                                            </div>
                                                        </form>
                                                    @endif
                                                </div>
                                                <div class="mt-3 position-relative">
                                                    <div
                                                        id="job-position-chart"
                                                        class="w-100"
                                                        style="height: 360px;"
                                                    ></div>
                                                    <div
                                                        id="job-position-chart-empty"
                                                        class="text-center text-muted small py-4 d-none"
                                                        data-empty-message="{{ $chartEmptyMessage }}"
                                                    >
                                                        {{ $chartEmptyMessage }}
                                                    </div>
                                                </div>
                                                <div id="job-position-chart-legend" class="tcpd-legend d-none"></div>
                                                <p
                                                    id="job-position-chart-summary"
                                                    class="text-muted small mb-0 mt-3 d-none"
                                                ></p>
                                                <div id="job-position-user-links" class="d-flex flex-wrap gap-2 mt-3 d-none"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-12">
                                        <div class="card dashboard-card level-5 h-100">
                                            <div class="card-body">
                                                <div>
                                                    <h6 class="card-title mb-2">Area Development</h6>
                                                    <p class="text-muted mb-0 small">
                                                        Tabel ini menampilkan competency, standar, dan jumlah user yang berada di bawah standar pada job position terpilih.
                                                    </p>
                                                </div>

                                                <div class="table-responsive mt-3">
                                                    <table class="table table-sm table-striped mb-0 align-middle">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th style="width: 60px;">No.</th>
                                                                <th>Competency</th>
                                                                <th style="width: 120px;">Average</th>
                                                                <th style="width: 120px;">Standard</th>
                                                                <th style="width: 140px;">Person (Below Std)</th>
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

                                                <p
                                                    id="tcpd-summary"
                                                    class="text-muted small mb-0 mt-3 d-none"
                                                ></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        $(document).ready(function() {
            // Hover function for dropdowns
            $('.nav-item.dropdown').hover(function() {
                $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
            }, function() {
                $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
            });
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
                    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'" : '&#039;' };
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

            const endpoints = @json(['company' => route('dashboardTCPD.companyData'), 'job' => route('dashboardTCPD.data'), 'detail' => route('dsDetailCompetency')]);

            const companyYearFromInput = document.getElementById('company-year-from');
            const companyYearToInput = document.getElementById('company-year-to');
            const companyResetButton = document.getElementById('company-filter-reset');
            const companyApplyButton = document.getElementById('company-filter-apply');

            const jobSelect = document.getElementById('job_position_id');
            const jobDateFromInput = document.getElementById('job-date-from');
            const jobDateToInput = document.getElementById('job-date-to');
            const jobResetButton = document.getElementById('job-filter-reset');
            const jobApplyButton = document.getElementById('job-filter-apply');
            const initialJobPositionName = @json($selectedJobPositionName);
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

            let currentDepartmentData = [];
            let isCompanyLoading = false;
            let isJobLoading = false;

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

            function updateCompetencyTable(competencies, tableBodyId = 'tcpd-competency-body') {
                const tbody = document.getElementById(tableBodyId);
                if (!tbody) return;

                const filtered = Array.isArray(competencies)
                    ? competencies.filter((row) => Number(row.qty) > 0)
                    : [];

                if (!filtered.length) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted small py-4">Semua competency memenuhi standar untuk filter ini.</td></tr>';
                    return;
                }

                tbody.innerHTML = filtered.map((row, index) => `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${escapeHtml(row.name)}</td>
                        <td>${formatNumber(row.average, 2)}</td>
                        <td>${formatNumber(row.standard, 2)}</td>
                        <td>${row.qty}</td>
                    </tr>
                `).join('');
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
                            const label = escapeHtml(user.name);
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

                const rotateLabels = categories.length > 6 ? 25 : 0;
                const legendLabels = series.map((serie) => serie.name);
                const option = {
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' },
                        formatter: (params) => params
                            .map(p => `${p.marker}${p.seriesName}: ${formatPercent(p.value)}`)
                            .join('<br/>'),
                    },
                    legend: { data: legendLabels, top: 0 },
                    grid: {
                        top: '10%',
                        left: '3%',
                        right: '3%',
                        bottom: rotateLabels ? '18%' : '10%',
                        containLabel: true,
                    },
                    xAxis: {
                        type: 'category',
                        data: categories,
                        axisTick: { alignWithLabel: true },
                        axisLabel: {
                            interval: 0,
                            rotate: rotateLabels,
                            hideOverlap: true,
                        },
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
                            formatter: (params) => params
                                .map(p => `${p.marker}${p.name}: ${formatPercent(p.value)}`)
                                .join('<br/>'),
                        },
                        legend: { show: false },
                        grid: { top: '10%', left: '3%', right: '4%', bottom: hasYearMode ? '14%' : '12%', containLabel: true },
                        xAxis: {
                            type: 'category',
                            data: categories,
                            axisLabel: { interval: 0, rotate: hasYearMode ? 20 : 28, hideOverlap: true },
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
                    grid: { top: '16%', left: '3%', right: '4%', bottom: '10%', containLabel: true },
                    xAxis: { type: 'category', data: categories, axisLabel: { interval: 0, rotate: 28, hideOverlap: true } },
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

            const fetchCompanyData = () => {
                if (isCompanyLoading) return;
                setButtonLoading(companyApplyButton, true);
                isCompanyLoading = true;

                const url = new URL(endpoints.company);
                url.searchParams.set('year_from', companyYearFromInput.value);
                url.searchParams.set('year_to', companyYearToInput.value);

                fetch(url)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data) {
                            const { company_chart_rows, department_summaries, ...meta } = result.data;
                            currentDepartmentData = department_summaries || [];
                            initCompanyChart(company_chart_rows || [], {
                                hasData: meta.company_has_data,
                                average: meta.company_average,
                                departmentCount: meta.company_department_count,
                                years: meta.company_years,
                                mode: meta.company_chart_mode,
                            });
                            const activeSlide = document.querySelector('#tcpdCarousel .carousel-item.active');
                            if (activeSlide) {
                                const slideKey = activeSlide.dataset.slideKey;
                                if (slideKey === 'logistics-sales') initDepartmentCharts(currentDepartmentData.slice(0, 2), 0);
                                else if (slideKey === 'procurement-finance') initDepartmentCharts(currentDepartmentData.slice(2, 4), 2);
                                else if (slideKey === 'produksi') initDepartmentCharts(currentDepartmentData.slice(4, 5), 4);
                            }
                        }
                    })
                    .catch(error => console.error('Error fetching company data:', error))
                    .finally(() => {
                        setButtonLoading(companyApplyButton, false);
                        isCompanyLoading = false;
                    });
            };

            const fetchJobData = () => {
                if (isJobLoading) return;
                if (!jobSelect) return;
                setButtonLoading(jobApplyButton, true);
                isJobLoading = true;
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
                if (!currentJobPositionName && typeof initialJobPositionName === 'string') {
                    currentJobPositionName = initialJobPositionName;
                }
                if (currentJobPositionId === null) {
                    setButtonLoading(jobApplyButton, false);
                    isJobLoading = false;
                    return;
                }

                const url = new URL(endpoints.job);
                url.searchParams.set('job_position_id', jobSelect.value);
                url.searchParams.set('date_from', jobDateFromInput.value);
                url.searchParams.set('date_to', jobDateToInput.value);

                fetch(url)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data) {
                            const context = {
                                jobPositionId: currentJobPositionId,
                                jobPositionName: currentJobPositionName,
                            };
                            initJobPositionChart(result.data.user_summaries || [], context);
                            updateCompetencyTable(result.data.competencies || []);
                            updateJobSummary(result.data, currentJobPositionName);
                        }
                    })
                    .catch(error => console.error('Error fetching job data:', error))
                    .finally(() => {
                        setButtonLoading(jobApplyButton, false);
                        isJobLoading = false;
                    });
            };

            if (companyApplyButton) companyApplyButton.addEventListener('click', fetchCompanyData);
            if (companyResetButton) companyResetButton.addEventListener('click', () => {
                if (companyYearFromInput) companyYearFromInput.value = '';
                if (companyYearToInput) companyYearToInput.value = '';
                fetchCompanyData();
            });
            if (companyYearFromInput) companyYearFromInput.addEventListener('change', () => fetchCompanyData());
            if (companyYearToInput) companyYearToInput.addEventListener('change', () => fetchCompanyData());

            if (jobApplyButton) jobApplyButton.addEventListener('click', fetchJobData);
            if (jobSelect) jobSelect.addEventListener('change', fetchJobData);
            if (jobDateFromInput) jobDateFromInput.addEventListener('change', () => fetchJobData());
            if (jobDateToInput) jobDateToInput.addEventListener('change', () => fetchJobData());
            if (jobResetButton) jobResetButton.addEventListener('click', () => {
                if (jobSelect) {
                    let fallbackValue = '';
                    if (defaultJobPositionId !== null && defaultJobPositionId !== undefined) {
                        fallbackValue = String(defaultJobPositionId);
                    } else if (jobSelect.options && jobSelect.options.length) {
                        fallbackValue = jobSelect.options[0].value;
                    }
                    jobSelect.value = fallbackValue;
                    const option = jobSelect.options[jobSelect.selectedIndex];
                    currentJobPositionName = option && option.text ? option.text.trim() : '';
                }
                if (!currentJobPositionName && typeof initialJobPositionName === 'string') {
                    currentJobPositionName = initialJobPositionName;
                }
                if (jobDateFromInput) jobDateFromInput.value = defaultJobDates.from || '';
                if (jobDateToInput) jobDateToInput.value = defaultJobDates.to || '';
                fetchJobData();
            });

            const carouselEl = document.getElementById('tcpdCarousel');
            if (carouselEl) {
                const carousel = new bootstrap.Carousel(carouselEl, { ride: false });
                const AUTO_SLIDE_DELAY = 10000;
                let autoSlideTimer = null;

                const clearAutoSlide = () => {
                    if (autoSlideTimer) {
                        clearTimeout(autoSlideTimer);
                        autoSlideTimer = null;
                    }
                };

                const scheduleAutoSlide = () => {
                    clearAutoSlide();
                    autoSlideTimer = setTimeout(() => {
                        carousel.next();
                    }, AUTO_SLIDE_DELAY);
                };

                carouselEl.addEventListener('slide.bs.carousel', clearAutoSlide);
                carouselEl.addEventListener('slid.bs.carousel', (event) => {
                    const slideKey = event.relatedTarget.dataset.slideKey;
                    if (slideKey === 'logistics-sales') initDepartmentCharts(currentDepartmentData.slice(0, 2), 0);
                    else if (slideKey === 'procurement-finance') initDepartmentCharts(currentDepartmentData.slice(2, 4), 2);
                    else if (slideKey === 'produksi') initDepartmentCharts(currentDepartmentData.slice(4, 5), 4);
                    else if (slideKey === 'job-overview') fetchJobData();
                    scheduleAutoSlide();
                });

                const carouselInner = carouselEl.querySelector('.carousel-inner');
                const interactiveSelector = 'button, a, input, select, textarea, .tcpd-legend__item';
                const dragVisualTarget = carouselInner || carouselEl;
                const pointerSurface = carouselEl;
                scheduleAutoSlide();

                if (pointerSurface) {
                    if (carouselInner) {
                        carouselInner.style.touchAction = 'pan-y';
                    }
                    if (window.PointerEvent) {
                        let pointerDown = false;
                        let startX = 0;
                        let swipeTriggered = false;

                        const resetPointerState = () => {
                            pointerDown = false;
                            swipeTriggered = false;
                            dragVisualTarget.classList.remove('grabbing');
                        };

                        const pointerDownHandler = (event) => {
                            if (event.pointerType === 'mouse' && event.button !== 0) return;
                            if (event.target && event.target.closest(interactiveSelector)) return;
                            clearAutoSlide();
                            pointerDown = true;
                            swipeTriggered = false;
                            startX = event.clientX;
                            dragVisualTarget.classList.add('grabbing');
                        };

                        const pointerMoveHandler = (event) => {
                            if (!pointerDown || swipeTriggered) return;
                            const deltaX = event.clientX - startX;
                            if (Math.abs(deltaX) > 40) {
                                swipeTriggered = true;
                                dragVisualTarget.classList.remove('grabbing');
                                if (deltaX < 0) carousel.next();
                                else carousel.prev();
                            }
                        };

                        const pointerUpHandler = (event) => {
                            if (!pointerDown) return;
                            resetPointerState();
                            scheduleAutoSlide();
                        };

                        pointerSurface.addEventListener('pointerdown', pointerDownHandler, true);
                        pointerSurface.addEventListener('pointermove', pointerMoveHandler);
                        window.addEventListener('pointerup', pointerUpHandler);
                        window.addEventListener('pointercancel', pointerUpHandler);
                    } else {
                        let isDragging = false;
                        let startX = 0;

                        const dragStart = (event) => {
                            const target = event.target;
                            if (target && target.closest(interactiveSelector)) return;
                            clearAutoSlide();
                            isDragging = true;
                            startX = event.pageX || (event.touches && event.touches[0]?.pageX) || 0;
                            dragVisualTarget.classList.add('grabbing');
                        };

                        const dragEnd = (event) => {
                            if (!isDragging) return;
                            isDragging = false;
                            dragVisualTarget.classList.remove('grabbing');
                            const currentX = event.pageX || (event.changedTouches && event.changedTouches[0]?.pageX) || 0;
                            const deltaX = currentX - startX;
                            if (deltaX < -40) carousel.next();
                            else if (deltaX > 40) carousel.prev();
                            scheduleAutoSlide();
                        };

                        pointerSurface.addEventListener('mousedown', dragStart, true);
                        pointerSurface.addEventListener('touchstart', dragStart, { passive: true, capture: true });
                        window.addEventListener('mouseup', dragEnd);
                        window.addEventListener('touchend', dragEnd);
                        window.addEventListener('touchcancel', dragEnd);
                    }
                }
            }

            const prefetchFlags = @json($prefetchFlags);
            if (prefetchFlags.company) {
                fetchCompanyData();
            }
            if (prefetchFlags.job) {
                fetchJobData();
            }
        });
    </script>
@endsection
