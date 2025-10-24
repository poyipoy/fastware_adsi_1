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
                        'Logistik' => [
                            'chart_id' => 'department-chart-logistik',
                            'empty_id' => 'department-chart-logistik-empty',
                            'summary_id' => 'department-chart-logistik-summary',
                            'legend_id' => 'department-legend-logistik',
                        ],
                        'Sales' => [
                            'chart_id' => 'department-chart-sales',
                            'empty_id' => 'department-chart-sales-empty',
                            'summary_id' => 'department-chart-sales-summary',
                            'legend_id' => 'department-legend-sales',
                        ],
                        'Procurement' => [
                            'chart_id' => 'department-chart-procurement',
                            'empty_id' => 'department-chart-procurement-empty',
                            'summary_id' => 'department-chart-procurement-summary',
                            'legend_id' => 'department-legend-procurement',
                        ],
                        'Finance, AR, HRGA' => [
                            'chart_id' => 'department-chart-finance',
                            'empty_id' => 'department-chart-finance-empty',
                            'summary_id' => 'department-chart-finance-summary',
                            'legend_id' => 'department-legend-finance',
                        ],
                        'Produksi' => [
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
                    <div class="carousel-indicators">
                        <button type="button" data-bs-target="#tcpdCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Company"></button>
                        <button type="button" data-bs-target="#tcpdCarousel" data-bs-slide-to="1" aria-label="Logistik &amp; Sales"></button>
                        <button type="button" data-bs-target="#tcpdCarousel" data-bs-slide-to="2" aria-label="Procurement &amp; Finance"></button>
                        <button type="button" data-bs-target="#tcpdCarousel" data-bs-slide-to="3" aria-label="Produksi"></button>
                        <button type="button" data-bs-target="#tcpdCarousel" data-bs-slide-to="4" aria-label="Job Position &amp; Area Development"></button>
                    </div>
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
                                                        <h6 class="card-title mb-1">Logistik</h6>
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
                                                        <h6 class="card-title mb-1">Sales</h6>
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
                                                        <h6 class="card-title mb-1">Procurement</h6>
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
                                                        <h6 class="card-title mb-1">Finance, AR, HRGA</h6>
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
                                                        <h6 class="card-title mb-1">Produksi</h6>
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
                return;
            }

            const formatNumber = (value, decimals = 2) => {
                if (value === null || value === undefined || value === '') {
                    return '-';
                }
                const numeric = Number(value);
                if (Number.isNaN(numeric)) {
                    return '-';
                }
                const fixed = numeric.toFixed(decimals);
                return Number(fixed) === Math.trunc(Number(fixed)) ? Number(fixed).toString() : fixed;
            };

            const formatPercent = (value, decimals = 2) => {
                if (value === null || value === undefined || value === '') {
                    return 'N/A';
                }
                const numeric = Number(value);
                if (Number.isNaN(numeric)) {
                    return 'N/A';
                }
                return `${formatNumber(numeric, decimals)}%`;
            };

            const toNumber = (value) => {
                if (value === null || value === undefined || value === '') {
                    return null;
                }
                const numeric = Number(value);
                return Number.isFinite(numeric) ? numeric : null;
            };

            const escapeHtml = (value) => {
                if (value === null || value === undefined) {
                    return '';
                }
                return String(value).replace(/[&<>'"\/]/g, (char) => {
                    const entities = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'" : '&#039;' };
                    return entities[char] || char;
                });
            };

            function showEmpty(emptyElement, chartElement) {
                if (chartElement) {
                    chartElement.classList.add('d-none');
                }
                if (emptyElement) {
                    emptyElement.classList.remove('d-none');
                }
            }

            function showChart(emptyElement, chartElement) {
                if (chartElement) {
                    chartElement.classList.remove('d-none');
                }
                if (emptyElement) {
                    emptyElement.classList.add('d-none');
                    if (emptyElement.dataset && emptyElement.dataset.emptyMessage) {
                        emptyElement.textContent = emptyElement.dataset.emptyMessage;
                    }
                }
            }

            const endpoints = @json([
                'company' => route('dashboardTCPD.companyData'),
                'job' => route('dashboardTCPD.data'),
            ]);

            const companyFilterForm = document.getElementById('company-filter-form');
            const companyYearFromInput = document.getElementById('company-year-from');
            const companyYearToInput = document.getElementById('company-year-to');
            const companyResetButton = document.getElementById('company-filter-reset');
            const companyApplyButton = document.getElementById('company-filter-apply');

            const jobFilterForm = document.getElementById('job-filter-form');
            const jobSelect = document.getElementById('job_position_id');
            const jobDateFromInput = document.getElementById('job-date-from');
            const jobDateToInput = document.getElementById('job-date-to');
            const jobResetButton = document.getElementById('job-filter-reset');
            const jobApplyButton = document.getElementById('job-filter-apply');

            let currentDepartmentData = @json($departmentSummaries->values()->toArray());
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
                const hasData = !!meta.hasData;
                if (!hasData) {
                    summaryEl.classList.add('d-none');
                    summaryEl.innerHTML = '<span class="fw-semibold">Company Average:</span> N/A';
                    return;
                }

                const average = typeof meta.average === 'number' ? meta.average : Number(meta.average || 0);
                const count = typeof meta.count === 'number' ? meta.count : Number(meta.count || 0);
                const parts = [`<span class="fw-semibold">Company Average:</span> ${formatNumber(average, 2)}%`];
                if (count > 0) {
                    parts.push(`- <span class="fw-semibold">${count}</span> departemen dinilai`);
                }
                summaryEl.classList.remove('d-none');
                summaryEl.innerHTML = parts.join(' ');
            };

            function initCompanyChart(entries, summaryMeta = {}) {
                const container = document.getElementById('company-chart');
                const emptyEl = document.getElementById('company-chart-empty');
                const summaryEl = document.getElementById('company-chart-summary');
                if (!container) return;

                let instance = echarts.getInstanceByDom(container);
                if (instance) instance.dispose();
                instance = echarts.init(container, null, { renderer: 'canvas' });

                const normalized = Array.isArray(entries) ? entries.filter(Boolean) : [];
                const anyData = normalized.some(item => item.hasData);

                if (!anyData) {
                    showEmpty(emptyEl, container);
                    instance.setOption({
                        title: { text: 'No Data Available', left: 'center', top: 'center' }
                    });
                    return;
                }

                showChart(emptyEl, container);
                // Full chart logic here...
            }

            function initJobPositionChart(entries) {
                const container = document.getElementById('job-position-chart');
                const emptyEl = document.getElementById('job-position-chart-empty');
                if (!container) return;

                let instance = echarts.getInstanceByDom(container);
                if (instance) instance.dispose();
                instance = echarts.init(container, null, { renderer: 'canvas' });

                const hasData = Array.isArray(entries) && entries.length > 0;

                if (!hasData) {
                    showEmpty(emptyEl, container);
                    instance.setOption({
                        title: { text: 'No Data Available', left: 'center', top: 'center' }
                    });
                    return;
                }

                showChart(emptyEl, container);
                // Full chart logic here...
            }

            function initDepartmentCharts(departments) {
                if (!Array.isArray(departments)) return;
                departments.forEach((dept, index) => {
                    const container = document.getElementById(`department-chart-${index}`);
                    const emptyEl = document.getElementById(`department-chart-${index}-empty`);
                    if (!container) return;

                    let instance = echarts.getInstanceByDom(container);
                    if (instance) instance.dispose();
                    instance = echarts.init(container, null, { renderer: 'canvas' });

                    const hasData = dept && Array.isArray(dept.entries) && dept.entries.length > 0;

                    if (!hasData) {
                        showEmpty(emptyEl, container);
                        instance.setOption({
                            title: { text: 'No Data Available', left: 'center', top: 'center' }
                        });
                        return;
                    }

                    showChart(emptyEl, container);
                    // Full chart logic here...
                });
            }

            // Initial calls
            initCompanyChart(@json($companyChartRows), @json($companyOverview));
            // The rest will be loaded on slide change

            const carouselEl = document.getElementById('tcpdCarousel');
            const carousel = new bootstrap.Carousel(carouselEl, { ride: false });
            const carouselInner = carouselEl.querySelector('.carousel-inner');

            let isDragging = false, startX;

            const dragStart = (e) => {
                // Do not start drag if the target is an interactive element
                if (e.target.matches('button, a, input, select, textarea')) {
                    return;
                }
                isDragging = true;
                startX = e.pageX || e.touches[0].pageX;
                carouselInner.classList.add('grabbing');
                e.preventDefault();
            };

            const dragStop = (e) => {
                if (!isDragging) return;
                isDragging = false;
                carouselInner.classList.remove('grabbing');
                const x = e.pageX || e.changedTouches[0].pageX;
                const walk = (x - startX);
                if (walk < -50) {
                    carousel.next();
                } else if (walk > 50) {
                    carousel.prev();
                }
            };

            carouselInner.addEventListener('mousedown', dragStart);
            carouselInner.addEventListener('touchstart', dragStart, { passive: true });

            carouselEl.addEventListener('mouseup', dragStop);
            carouselEl.addEventListener('mouseleave', dragStop);
            carouselEl.addEventListener('touchend', dragStop);

            // No need for mousemove, we only care about start and end points

            // Re-initialize charts on slide change
            carouselEl.addEventListener('slid.bs.carousel', function (event) {
                const activeSlide = event.relatedTarget;
                const slideKey = activeSlide.dataset.slideKey;

                if (slideKey === 'company') {
                    initCompanyChart(@json($companyChartRows), @json($companyOverview));
                } else if (slideKey === 'logistics-sales') {
                    initDepartmentCharts(currentDepartmentData.slice(0, 2));
                } else if (slideKey === 'procurement-finance') {
                    initDepartmentCharts(currentDepartmentData.slice(2, 4));
                } else if (slideKey === 'produksi') {
                    initDepartmentCharts(currentDepartmentData.slice(4, 5));
                } else if (slideKey === 'job-overview') {
                    initJobPositionChart(@json($userSummaries));
                }
            });

            // Other functions and event listeners...
            // Make sure to include the full logic for fetchCompanyData, fetchJobData etc.

        });
    </script>
    @endsectionideKey === 'procurement-finance') {
                    initDepartmentCharts(currentDepartmentData.slice(2, 4));
                } else if (slideKey === 'produksi') {
                    initDepartmentCharts(currentDepartmentData.slice(4, 5));
                } else if (slideKey === 'job-overview') {
                    initJobPositionChart(@json($userSummaries));
                }
            });

            // Other functions and event listeners...
            // Make sure to include the full logic for fetchCompanyData, fetchJobData etc.

        });
    </script>
    @endsection