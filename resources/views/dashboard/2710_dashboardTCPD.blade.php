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
                    $hasChartData = ($totalPercentage !== null) || collect($userSummaries)->some(fn($u) => ($u['tc_percentage'] ?? null) !== null || ($u['sk_percentage'] ?? null) !== null || ($u['ad_percentage'] ?? null) !== null);
                    $evaluatedUsersCount = count($userSummaries);
                    $chartEmptyMessage = $selectedJobPositionName
                        ? 'Data persentase belum tersedia untuk job position ini.'
                        : 'Silakan pilih job position.';
                    $departmentSummaries = collect($departmentSummaries ?? []);
                    $departmentChunks = $departmentSummaries->chunk(2)->values();

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
                @endphp
                <div class="row g-3 mb-1">
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
                                            class="w-100 {{ $companyHasData ? '' : 'd-none' }}"
                                            style="height: 320px;"
                                        ></div>
                                        <div
                                            id="company-chart-empty"
                                            class="text-center text-muted small py-4 {{ $companyHasData ? 'd-none' : '' }}"
                                            data-empty-message="{{ $companyEmptyMessage ?: 'Data persentase departemen belum tersedia.' }}"
                                        >
                                            {{ $companyEmptyMessage ?: 'Data persentase departemen belum tersedia.' }}
                                        </div>
                                    </div>
                                    <p
                                        id="company-chart-summary"
                                        class="text-muted small mb-0 {{ $companyHasData ? '' : 'd-none' }}"
                                    >
                                        <span class="fw-semibold">Company Average:</span>
                                        {{ $companyHasData ? number_format($companyAverage, 2) . '%' : 'N/A' }}
                                        @if ($companyRowsCount)
                                            - <span class="fw-semibold">{{ $companyRowsCount }}</span> departemen dinilai
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @php $deptIndex = 0; @endphp

                @php $deptIndex = 0; @endphp

                @foreach ($departmentChunks as $chunkIndex => $chunkCollection)
    @php $chunk = $chunkCollection->values(); @endphp
    <div class="row g-2 mb-2">
        @foreach ($chunk as $departmentIndex => $department)
            @php
                $entries = collect($department['entries'] ?? []);
                $hasDepartmentData = $entries->contains(fn ($entry) => is_numeric($entry['percentage'] ?? null));

                // FIX: gunakan counter global yang selalu naik
                $chartId  = 'department-chart-' . $deptIndex;
                $emptyId  = $chartId . '-empty';
                $summaryId = $chartId . '-summary';
                $deptIndex++; // increment di akhir tiap kartu
            @endphp
            <div class="{{ $chunk->count() === 1 ? 'col-12' : 'col-12 col-lg-6' }}">
                <div class="card dashboard-card level-2 h-100">
                    <div class="card-body">
                        <div class="d-flex flex-column gap-2">
                            <div>
                                <h6 class="card-title mb-1">{{ $department['department'] }}</h6>
                                <p class="text-muted mb-0 small">
                                    Persentase rata-rata kompetensi untuk setiap job position dalam departemen ini.
                                </p>
                            </div>
                            <div class="mt-1">
                                <div
                                    id="{{ $chartId }}"
                                    class="w-100 {{ $hasDepartmentData ? '' : 'd-none' }}"
                                    style="height: 340px;"
                                ></div>
                                <div
                                    id="{{ $emptyId }}"
                                    class="text-center text-muted small py-4 {{ $hasDepartmentData ? 'd-none' : '' }}"
                                >
                                    Data persentase belum tersedia untuk departemen ini.
                                </div>
                            </div>
                            <p id="{{ $summaryId }}" class="text-muted small mb-0">
                                <span class="fw-semibold">Total Pencapaian:</span>
                                {{ isset($department['overall']) && is_numeric($department['overall']) ? number_format($department['overall'], 2) . '%' : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endforeach


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
                                <div class="mt-3">
                                    <div
                                        id="job-position-chart"
                                        class="w-100 {{ $hasChartData ? '' : 'd-none' }}"
                                        style="height: 360px;"
                                    ></div>
                                    <div
                                        id="job-position-chart-empty"
                                        class="text-center text-muted small py-4 {{ $hasChartData ? 'd-none' : '' }}"
                                        data-empty-message="{{ $chartEmptyMessage }}"
                                    >
                                        {{ $chartEmptyMessage }}
                                    </div>
                                </div>
                                <p
                                    id="job-position-chart-summary"
                                    class="text-muted small mb-0 mt-3 {{ $selectedJobPositionName ? '' : 'd-none' }}"
                                >
                                    @if ($selectedJobPositionName)
                                        <span class="fw-semibold">Total Pencapaian:</span>
                                        {{-- {{ $totalPercentage !== null ? number_format($totalPercentage, 2) . '%' : 'N/A' }} --}}
                                        @if ($evaluatedUsersCount > 0)
                                            - <span class="fw-semibold">{{ $evaluatedUsersCount }}</span> user dinilai
                                        @endif
                                    @endif
                                </p>
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
                                                <th style="width: 140px;">Person <br> (Below Std)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tcpd-competency-body">
                                            @php
                                                $hasDevelopmentRows = false;
                                                $rowNumber = 1;
                                            @endphp
                                            @foreach ($competencyRows as $index => $row)
                                                @php
                                                    $standardValue = $row['standard'] ?? null;
                                                    $averageValue = $row['average'] ?? null;
                                                    $displayStandard = is_numeric($standardValue)
                                                        ? number_format((float) $standardValue, 2)
                                                        : ($standardValue !== null && $standardValue !== '' ? $standardValue : '-');
                                                    $displayAverage = is_numeric($averageValue)
                                                        ? number_format((float) $averageValue, 2)
                                                        : '-';
                                                    $belowCount = isset($row['qty']) && is_numeric($row['qty']) ? (int) $row['qty'] : 0;
                                                @endphp
                                                @if ($belowCount <= 0)
                                                    @continue
                                                @endif
                                                @php $hasDevelopmentRows = true; @endphp
                                                <tr>
                                                    <td>{{ $rowNumber++ }}</td>
                                                    <td>{{ $row['name'] }}</td>
                                                    <td>{{ $displayAverage }}</td>
                                                    <td>{{ $displayStandard }}</td>
                                                    <td>{{ $belowCount }}</td>
                                                </tr>
                                            @endforeach
                                            @if (! $hasDevelopmentRows)
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted small py-4">
                                                        {{ $selectedJobPositionName ? 'Tidak ada user di bawah standar untuk periode ini.' : 'Silakan pilih job position untuk melihat data.' }}
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                <p
                                    id="tcpd-summary"
                                    class="text-muted small mb-0 mt-3 {{ $selectedJobPositionName ? '' : 'd-none' }}"
                                >
                                    @if ($selectedJobPositionName)
                                        <span class="fw-semibold">Job Position:</span> {{ $selectedJobPositionName }}
                                        @if ($userCountByJobPosition > 0)
                                            - <span class="fw-semibold">{{ $userCountByJobPosition }}</span> user terdaftar
                                        @else
                                            - Belum ada user terdaftar
                                        @endif
                                    @endif
                                </p>
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

            initJobPositionChart(@json($userSummaries));

            initCompanyChart(@json($companyChartRows), @json($companyOverview));
            initDepartmentCharts(currentDepartmentData);

            function initCompanyChart(entries, summaryMeta = {}) {
                const container = document.getElementById('company-chart');
                const emptyEl = document.getElementById('company-chart-empty');
                const summaryEl = document.getElementById('company-chart-summary');
                if (!container || !emptyEl) return;

                (function ensureLegendStyles() {
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
                })();

                const normalized = Array.isArray(entries)
                    ? entries
                        .map((item) => {
                            const label = item?.label ? String(item.label).trim() : '';
                            if (!label) return null;

                            const rawValues = Array.isArray(item?.values) ? item.values : [];
                            const valueMap = new Map();

                            rawValues.forEach((value) => {
                                const rawKey = value?.key ?? value?.year ?? value?.label;
                                if (rawKey === null || typeof rawKey === 'undefined') return;
                                const key = String(rawKey).trim();
                                if (!key) return;
                                const percentage = toNumber(value?.percentage);
                                const hasData = percentage !== null
                                    ? true
                                    : value?.has_data === true;

                                valueMap.set(key, {
                                    key,
                                    year: typeof value?.year === 'number' ? value.year : null,
                                    label: value?.label ? String(value.label) : key,
                                    percentage,
                                    hasData,
                                });
                            });

                            if (valueMap.size === 0) {
                                const fallbackValue = (() => {
                                    const summaryFromField = toNumber(item?.summary_percentage);
                                    if (summaryFromField !== null) return summaryFromField;
                                    return toNumber(item?.percentage);
                                })();

                                valueMap.set('all', {
                                    key: 'all',
                                    year: null,
                                    label: 'All',
                                    percentage: fallbackValue,
                                    hasData: fallbackValue !== null,
                                });
                            }

                            const hasRowData = Array.from(valueMap.values()).some((value) => typeof value.percentage === 'number');

                            return {
                                label,
                                isCompany: item?.is_company === true || label.toLowerCase() === 'company',
                                values: valueMap,
                                hasData: hasRowData,
                            };
                        })
                        .filter(Boolean)
                    : [];

                const metaInfo = {
                    average: toNumber(summaryMeta.average) ?? 0,
                    count: toNumber(summaryMeta.count) ?? 0,
                    hasData: Boolean(summaryMeta.hasData),
                    years: Array.isArray(summaryMeta.years) ? summaryMeta.years : [],
                    mode: typeof summaryMeta.mode === 'string' ? summaryMeta.mode : 'aggregate',
                };

                const labels = normalized.map((item) => item.label);
                let yearKeys = Array.isArray(metaInfo.years) ? metaInfo.years.map((year) => String(year)) : [];

                if (!yearKeys.length) {
                    const uniqueKeys = new Set();
                    normalized.forEach((item) => {
                        item.values.forEach((value) => {
                            uniqueKeys.add(value.key);
                        });
                    });
                    yearKeys = Array.from(uniqueKeys);
                }

                if (!yearKeys.length) {
                    yearKeys = ['all'];
                }

                const cleanYearKeys = (() => {
                    const numericKeys = yearKeys.filter((key) => key !== 'all').sort((a, b) => Number(a) - Number(b));
                    return yearKeys.includes('all') ? ['all', ...numericKeys] : numericKeys;
                })();

                const anyData = normalized.some((item) => item.hasData);
                metaInfo.hasData = metaInfo.hasData || anyData;

                if (!normalized.length || !anyData) {
                    showEmpty(emptyEl, container);
                    updateCompanySummary({ hasData: false, average: 0, count: 0 });
                    if (summaryEl) summaryEl.classList.add('d-none');
                    return;
                }

                const labelPalette = ['#0ea5e9', '#8b5cf6', '#10b981', '#f97316', '#ec4899', '#facc15', '#14b8a6', '#dc3545', '#6c757d'];
                const colorMap = new Map();
                let colorIndex = 0;
                normalized.forEach((item) => {
                    if (item.isCompany) {
                        colorMap.set(item.label, '#2563eb');
                        return;
                    }
                    const color = labelPalette[colorIndex % labelPalette.length];
                    colorMap.set(item.label, color);
                    colorIndex += 1;
                });

                const yearPalette = ['#1d4ed8', '#0ea5e9', '#22c55e', '#f97316', '#a855f7', '#ec4899', '#facc15', '#14b8a6', '#dc2626'];
                const yearColors = new Map();
                cleanYearKeys.forEach((key, index) => {
                    yearColors.set(key, yearPalette[index % yearPalette.length]);
                });

                let selectedState = Object.fromEntries(labels.map((label) => [label, true]));

                const legendId = 'company-chart-legend';
                let legendEl = document.getElementById(legendId);
                if (!legendEl) {
                    legendEl = document.createElement('div');
                    legendEl.id = legendId;
                    legendEl.className = 'tcpd-legend';
                    container.parentElement.appendChild(legendEl);
                }

                const instance = echarts.init(container, null, { renderer: 'canvas' });

                const ensureResize = (inst) => {
                    try { inst.resize(); } catch {}
                    if (typeof requestAnimationFrame === 'function') {
                        requestAnimationFrame(() => { try { inst.resize(); } catch {} });
                    }
                    setTimeout(() => { try { inst.resize(); } catch {} }, 60);
                };

                const buildAndSetOption = () => {
                    const visible = normalized.filter((item) => !!selectedState[item.label]);

                    if (!visible.length) {
                        instance.clear();
                        showEmpty(emptyEl, container);
                        updateCompanySummary({ hasData: false, average: metaInfo.average, count: metaInfo.count });
                        return;
                    }

                    const categories = visible.map((item) => item.label);
                    const valuesForMax = [];

                    cleanYearKeys.forEach((yearKey) => {
                        visible.forEach((item) => {
                            const valueObj = item.values.get(yearKey);
                            if (valueObj && typeof valueObj.percentage === 'number') {
                                valuesForMax.push(valueObj.percentage);
                            }
                        });
                    });

                    const hasActualData = valuesForMax.length > 0;

                    const dataMax = valuesForMax.length ? Math.max(...valuesForMax) : 0;
                    const count = Math.max(1, categories.length);
                    const slot = Math.max(6, Math.floor((container.clientWidth || 600) / count));
                    const groupWidth = Math.max(16, Math.floor(slot * 0.7));
                    const barMaxWidth = Math.max(14, Math.min(36, Math.floor(groupWidth / Math.max(1, cleanYearKeys.length))));
                    const barMinWidth = 10;
                    const rotation = count > 12 ? 35 : count > 8 ? 25 : 15;
                    const fontSize = count > 14 ? 10 : 12;
                    const gridBottom = count > 12 ? 110 : count > 8 ? 90 : 70;
                    const yMax = Math.min(200, Math.max(100, Math.ceil(dataMax / 10) * 10 + 10));
                    const gridTop = 70;

                    const series = cleanYearKeys.map((yearKey, index) => {
                        const displayName = yearKey === 'all'
                            ? 'All'
                            : `Tahun ${yearKey}`;
                        const color = yearColors.get(yearKey) || yearPalette[index % yearPalette.length];

                        const seriesData = visible.map((item) => {
                            const valueObj = item.values.get(yearKey);
                            if (!valueObj || typeof valueObj.percentage !== 'number') {
                                return { value: null, hasData: false };
                            }
                            return {
                                value: valueObj.percentage,
                                hasData: valueObj.hasData,
                                category: item.label,
                                yearKey,
                            };
                        });

                        return {
                            name: displayName,
                            type: 'bar',
                            barMaxWidth,
                            barMinWidth,
                            itemStyle: {
                                color,
                                borderRadius: [4, 4, 0, 0],
                            },
                            label: {
                                show: true,
                                position: 'top',
                                distance: 6,
                                formatter: (params) => {
                                    const value = params?.data?.value;
                                    if (typeof value !== 'number') return '';
                                    return `${formatNumber(value, 2)}%`;
                                },
                            },
                            emphasis: { focus: 'series' },
                            data: seriesData,
                        };
                    });

                    const option = {
                        legend: {
                            top: 0,
                            textStyle: { fontSize: 12 },
                            itemWidth: 14,
                            itemHeight: 8,
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: { type: 'shadow' },
                            confine: true,
                            formatter: (params) => {
                                if (!Array.isArray(params) || params.length === 0) return '';
                                const axisLabel = params[0]?.axisValueLabel ?? '';
                                const lines = [escapeHtml(axisLabel)];
                                params.forEach((item) => {
                                    const value = item?.data?.value;
                                    if (typeof value !== 'number') return;
                                    const label = item?.seriesName ?? '';
                                    lines.push(`${item.marker || ''}${escapeHtml(label)}: ${formatNumber(value, 2)}%`);
                                });
                                return lines.join('<br/>');
                            },
                        },
                        grid: {
                            left: '3%',
                            right: '3%',
                            top: gridTop,
                            bottom: gridBottom,
                            containLabel: true,
                        },
                        xAxis: {
                            type: 'category',
                            data: categories,
                            axisTick: { alignWithLabel: true },
                            axisLabel: {
                                interval: 0,
                                rotate: rotation,
                                fontSize,
                                margin: 10,
                                hideOverlap: true,
                            },
                        },
                        yAxis: {
                            type: 'value',
                            min: 0,
                            max: yMax,
                            axisLabel: { formatter: (v) => `${formatNumber(v, 0)}%` },
                            splitLine: {
                                show: true,
                                lineStyle: { color: '#d0d7de', width: 1, type: 'dashed' },
                            },
                            axisLine: { lineStyle: { color: '#adb5bd' } },
                            axisTick: { lineStyle: { color: '#adb5bd' } },
                        },
                        series,
                        animationDuration: 250,
                        animationDurationUpdate: 200,
                    };

                    showChart(emptyEl, container);
                    instance.setOption(option, true);
                    ensureResize(instance);

                    updateCompanySummary({
                        hasData: hasActualData,
                        average: metaInfo.average,
                        count: metaInfo.count,
                    });
                };

                const renderLegend = () => {
                    legendEl.innerHTML = '';
                    labels.forEach((label) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = `tcpd-legend__item ${selectedState[label] ? '' : 'is-off'}`;
                        btn.setAttribute('aria-pressed', selectedState[label] ? 'true' : 'false');
                        btn.innerHTML = `
                            <span class="tcpd-legend__dot" style="background:${colorMap.get(label) || '#2563eb'}"></span>
                            <span class="tcpd-legend__text">${escapeHtml(label)}</span>
                        `;
                        btn.addEventListener('click', () => {
                            selectedState[label] = !selectedState[label];
                            buildAndSetOption();
                            renderLegend();
                        });
                        legendEl.appendChild(btn);
                    });
                    legendEl.classList.toggle('d-none', labels.length === 0);
                };

                buildAndSetOption();
                renderLegend();

                if (typeof ResizeObserver !== 'undefined') {
                    const ro = new ResizeObserver(() => ensureResize(instance));
                    ro.observe(container);
                } else {
                    window.addEventListener('resize', () => ensureResize(instance));
                }
                window.addEventListener('load', () => ensureResize(instance));
                setTimeout(() => ensureResize(instance), 0);
            }

            function initJobPositionChart(entries) {
                const container = document.getElementById('job-position-chart');
                const emptyEl = document.getElementById('job-position-chart-empty');
                const summaryEl = document.getElementById('job-position-chart-summary');
                if (!container || !emptyEl) return;

                const normalized = Array.isArray(entries)
                    ? entries.filter(item => item && typeof item.name === 'string' && item.name.trim() !== '')
                    : [];

                const hasData = normalized.some(item =>
                    toNumber(item.tc_percentage) !== null ||
                    toNumber(item.sk_percentage) !== null ||
                    toNumber(item.ad_percentage) !== null
                );

                if (!hasData) {
                    showEmpty(emptyEl, container);
                    if (summaryEl) summaryEl.classList.add('d-none');
                    const legendEl = document.getElementById('job-position-legend');
                    if (legendEl) legendEl.remove();
                    return;
                }

                const categories = normalized.map(item => item.name);
                const seriesConfig = [
                    { key: 'tc_percentage', name: 'Technical Competency', color: '#2563eb' },
                    { key: 'sk_percentage', name: 'Soft Skill', color: '#0ea5e9' },
                    { key: 'ad_percentage', name: 'Additional', color: '#10b981' },
                ];

                const series = seriesConfig.map(config => ({
                    name: config.name,
                    type: 'bar',
                    barMaxWidth: 24,
                    barMinWidth: 8,
                    itemStyle: {
                        color: config.color,
                        borderRadius: [4, 4, 0, 0],
                    },
                    label: {
                        show: true,
                        position: 'top',
                        distance: 6,
                        formatter: (params) => {
                            const value = params?.data?.value;
                            return typeof value === 'number' ? `${formatNumber(value, 1)}%` : '';
                        },
                        fontSize: 10,
                    },
                    emphasis: { focus: 'series' },
                    data: normalized.map(item => ({
                        value: toNumber(item[config.key]),
                        name: item.name,
                    })),
                }));

                const allValues = series.flatMap(s => s.data.map(d => d.value)).filter(v => v !== null);
                const maxValue = allValues.length > 0 ? Math.max(...allValues) : 0;
                const n = Math.max(1, categories.length);
                const slot = Math.max(6, Math.floor((container.clientWidth || 600) / n));
                const rot = n > 10 ? 30 : n > 5 ? 20 : 15;
                const fontSize = n > 15 ? 10 : 11;
                const gridBottom = n > 10 ? 110 : 90;

                const option = {
                    legend: {
                        show: true,
                        top: 0,
                        textStyle: { fontSize: 12 },
                        itemWidth: 14,
                        itemHeight: 10,
                    },
                    tooltip: {
                        trigger: 'axis',
                        axisPointer: { type: 'shadow' },
                        confine: true,
                        formatter: (params) => {
                            if (!Array.isArray(params) || params.length === 0) return '';
                            const name = params[0]?.name ?? '';
                            const lines = [escapeHtml(name)];
                            params.forEach(p => {
                                const value = p?.data?.value;
                                if (typeof value === 'number') {
                                    lines.push(`${p.marker || ''}${escapeHtml(p.seriesName)}: ${formatNumber(value, 2)}%`);
                                }
                            });
                            return lines.join('<br/>');
                        },
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        top: '18%',
                        bottom: gridBottom,
                        containLabel: true,
                    },
                    xAxis: {
                        type: 'category',
                        data: categories,
                        axisTick: { alignWithLabel: true },
                        axisLabel: {
                            interval: 0,
                            rotate: rot,
                            fontSize: fontSize,
                            margin: 10,
                            hideOverlap: true,
                            overflow: 'truncate',
                            width: Math.max(60, slot - 8),
                        },
                    },
                    yAxis: {
                        type: 'value',
                        min: 0,
                        max: Math.min(150, Math.max(100, Math.ceil(maxValue / 10) * 10 + 10)),
                        axisLabel: { formatter: (v) => `${formatNumber(v, 0)}%` },
                        splitLine: { show: true, lineStyle: { color: '#e9ecef', width: 1, type: 'dashed' } },
                    },
                    series,
                    animationDuration: 250,
                    animationDurationUpdate: 200,
                };

                const existingInstance = echarts.getInstanceByDom(container);
                if (existingInstance) existingInstance.dispose();
                const instance = echarts.init(container, null, { renderer: 'canvas' });

                const ensureResize = (inst) => {
                    try { inst.resize(); } catch {}
                    if (typeof requestAnimationFrame === 'function') {
                        requestAnimationFrame(() => { try { inst.resize(); } catch {} });
                    }
                    setTimeout(() => { try { inst.resize(); } catch {} }, 60);
                };

                showChart(emptyEl, container);
                if (summaryEl) summaryEl.classList.remove('d-none');
                instance.setOption(option, true);
                ensureResize(instance);

                if (typeof ResizeObserver !== 'undefined') {
                    const observer = new ResizeObserver(() => ensureResize(instance));
                    observer.observe(container);
                } else {
                    window.addEventListener('resize', () => ensureResize(instance));
                }
                window.addEventListener('load', () => ensureResize(instance));
                setTimeout(() => ensureResize(instance), 0);
            }


            function initDepartmentCharts(departments) {
                if (!Array.isArray(departments) || !departments.length) return;

                const jobPalette = ['#0ea5e9', '#8b5cf6', '#10b981', '#f97316', '#ec4899', '#facc15', '#14b8a6'];
                const colorOf = (isTotal, idx) => (isTotal ? '#2563eb' : jobPalette[idx % jobPalette.length]);
                const yearPalette = ['#1d4ed8', '#0ea5e9', '#22c55e', '#f97316', '#a855f7', '#ec4899', '#facc15', '#14b8a6', '#dc2626'];

                // ---- Legend HTML styles (sekali saja)
                (function ensureLegendStyles(){
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
                })();

                const ensureResize = (inst) => {
                    try { inst.resize(); } catch {}
                    if (typeof requestAnimationFrame === 'function') {
                    requestAnimationFrame(() => { try { inst.resize(); } catch {} });
                    }
                    setTimeout(() => { try { inst.resize(); } catch {} }, 60);
                };

                departments.forEach((department, index) => {
                    const container = document.getElementById(`department-chart-${index}`);
                    const emptyEl   = document.getElementById(`department-chart-${index}-empty`);
                    const summaryEl = document.getElementById(`department-chart-${index}-summary`);
                    if (!container || !emptyEl) return;

                    const legendId = `department-legend-${index}`;
                    let legendEl = document.getElementById(legendId);
                    if (legendEl) {
                        legendEl.remove();
                        legendEl = null;
                    }

                    const existingInstance = echarts.getInstanceByDom(container);
                    if (existingInstance) existingInstance.dispose();

                    // --- Normalisasi data
                    const raw = Array.isArray(department?.entries) ? department.entries : [];
                    const normalized = raw
                        .map((entry) => {
                            const label = entry?.label ? String(entry.label).trim() : '';
                            if (!label) return null;

                            const isTotal =
                                String(entry?.label ?? '').toLowerCase() === 'total' ||
                                entry?.is_total === true;

                            const summaryValue = toNumber(entry?.percentage);

                            const values = new Map();
                            const rawValues = Array.isArray(entry?.values) ? entry.values : [];

                            rawValues.forEach((value) => {
                                const rawKey = value?.key ?? value?.year ?? value?.label;
                                if (rawKey === null || typeof rawKey === 'undefined') return;

                                const key = String(rawKey).trim();
                                if (!key) return;
                                const percentage = toNumber(value?.percentage);
                                const hasValueData = percentage !== null;

                                values.set(key, {
                                    key,
                                    year: typeof value?.year === 'number' ? value.year : null,
                                    label: value?.label ? String(value.label) : key,
                                    percentage,
                                    hasData: hasValueData,
                                });
                            });

                            if (values.size === 0 && summaryValue !== null) {
                                values.set('all', {
                                    key: 'all',
                                    year: null,
                                    label: 'All',
                                    percentage: summaryValue,
                                    hasData: true,
                                });
                            }

                            const hasData = Array.from(values.values()).some((value) => typeof value.percentage === 'number');

                            return {
                                label,
                                isTotal,
                                summaryValue,
                                values,
                                hasData,
                            };
                        })
                        .filter(Boolean);

                    const availableEntries = normalized.filter((entry) => entry.hasData);

                    if (!availableEntries.length) {
                        showEmpty(emptyEl, container);
                        if (summaryEl) summaryEl.innerHTML = '<span class="fw-semibold">Total Pencapaian:</span> N/A';
                        return;
                    }

                    const totalIndex = availableEntries.findIndex((entry) => entry.isTotal);
                    if (totalIndex > 0) {
                        const [totalEntry] = availableEntries.splice(totalIndex, 1);
                        availableEntries.unshift(totalEntry);
                    }

                    const colorMap = new Map();
                    let colorIndex = 0;
                    availableEntries.forEach((entry) => {
                        colorMap.set(entry.label, colorOf(entry.isTotal, colorIndex));
                        if (!entry.isTotal) colorIndex += 1;
                    });

                    const allCategories = availableEntries.map((entry) => entry.label);
                    let selectedState = Object.fromEntries(allCategories.map((label) => [label, true]));

                    // Siapkan legend host DOM (tepat setelah div chart)
                    if (!legendEl) {
                    legendEl = document.createElement('div');
                    legendEl.id = legendId;
                    legendEl.className = 'tcpd-legend';
                    // taruh di container parent agar tepat di bawah chart
                    container.parentElement.appendChild(legendEl);
                    }

                    // Render legend HTML kustom
                    const renderLegend = () => {
                    legendEl.innerHTML = '';
                    allCategories.forEach(lbl => {
                        const item = document.createElement('button');
                        item.type = 'button';
                        item.className = `tcpd-legend__item ${selectedState[lbl] ? '' : 'is-off'}`;
                        item.setAttribute('aria-pressed', selectedState[lbl] ? 'true' : 'false');
                        item.innerHTML = `
                        <span class="tcpd-legend__dot" style="background:${colorMap.get(lbl) || '#2563eb'}"></span>
                        <span class="tcpd-legend__text">${lbl}</span>
                        `;
                        item.addEventListener('click', () => {
                        selectedState[lbl] = !selectedState[lbl];
                        buildAndSetOption(); // re-render chart
                        renderLegend();      // re-render legend (state visual)
                        });
                        legendEl.appendChild(item);
                    });
                    };

                    const instance = echarts.init(container, null, { renderer: 'canvas' });

                    const buildAndSetOption = () => {
                        const visibleEntries = availableEntries.filter((entry) => !!selectedState[entry.label]);

                        if (!visibleEntries.length) {
                            instance.clear();
                            showEmpty(emptyEl, container);
                            if (summaryEl) summaryEl.innerHTML = '<span class="fw-semibold">Total Pencapaian:</span> N/A';
                            return;
                        }

                        const yearKeySet = new Set();
                        visibleEntries.forEach((entry) => {
                            entry.values.forEach((value, key) => {
                                if (typeof value.percentage === 'number') {
                                    yearKeySet.add(key);
                                }
                            });
                        });

                        if (!yearKeySet.size) {
                            yearKeySet.add('all');
                        }

                        const sortedYearKeys = Array.from(yearKeySet).sort((a, b) => {
                            if (a === 'all') return -1;
                            if (b === 'all') return 1;
                            return Number(a) - Number(b);
                        });

                        const yearColors = new Map();
                        sortedYearKeys.forEach((key, index) => {
                            yearColors.set(key, yearPalette[index % yearPalette.length]);
                        });

                        const categories = visibleEntries.map((entry) => entry.label);
                        const valuesForMax = [];
                        const entryByLabel = new Map(visibleEntries.map((entry) => [entry.label, entry]));

                        const series = sortedYearKeys
                            .map((yearKey, seriesIndex) => {
                                const data = categories.map((label) => {
                                    const entry = entryByLabel.get(label);
                                    const valueObj = entry?.values.get(yearKey);
                                    const numeric = toNumber(valueObj?.percentage);
                                    const hasActual = valueObj ? (valueObj.hasData && numeric !== null) : false;

                                    if (hasActual && numeric !== null) {
                                        valuesForMax.push(numeric);
                                    }

                                    return {
                                        value: hasActual && numeric !== null ? numeric : 0,
                                        displayValue: hasActual && numeric !== null ? numeric : null,
                                        hasActual,
                                        category: label,
                                        isCompany: entry?.isCompany === true,
                                        yearKey,
                                    };
                                });

                                const hasSeriesActual = data.some((item) => item.hasActual);
                                if (!hasSeriesActual) {
                                    return null;
                                }

                                const color = yearColors.get(yearKey) || yearPalette[seriesIndex % yearPalette.length];
                                const labelName = yearKey === 'all' ? 'All' : `Tahun ${yearKey}`;

                                return {
                                    name: labelName,
                                    type: 'bar',
                                    data,
                                    barMaxWidth: 24,
                                    barMinWidth: 12,
                                    itemStyle: {
                                        color,
                                        borderRadius: [4, 4, 0, 0],
                                    },
                                    label: {
                                        show: true,
                                        position: 'top',
                                        distance: 6,
                                        formatter: (params) => {
                                            const displayValue = typeof params?.data?.displayValue === 'number' ? params.data.displayValue : null;
                                            return displayValue !== null ? `${formatNumber(displayValue, 2)}%` : '';
                                        },
                                    },
                                    emphasis: { focus: 'series' },
                                };
                            })
                            .filter(Boolean);

                        const hasActualData = valuesForMax.length > 0;

                        if (!series.length) {
                            instance.clear();
                            showEmpty(emptyEl, container);
                            if (summaryEl) summaryEl.innerHTML = '<span class="fw-semibold">Total Pencapaian:</span> N/A';
                            return;
                        }

                        const categoryCount = Math.max(1, categories.length);
                        const slot = Math.max(6, Math.floor((container.clientWidth || 600) / categoryCount));
                        const groupWidth = Math.max(18, Math.floor(slot * 0.72));
                        const barWidth = Math.max(12, Math.min(36, Math.floor(groupWidth / Math.max(1, series.length))));
                        const barMinWidth = Math.max(8, Math.floor(barWidth * 0.6));

                        series.forEach((serie) => {
                            serie.barMaxWidth = barWidth;
                            serie.barMinWidth = barMinWidth;
                        });

                        const dataMax = valuesForMax.length ? Math.max(...valuesForMax) : 0;
                        const yMax = Math.min(200, Math.max(100, Math.ceil(dataMax / 10) * 10 + 10));

                        const rot = categoryCount > 20 ? 35 : categoryCount > 12 ? 25 : 15;
                        const fs = categoryCount > 24 ? 9 : categoryCount > 12 ? 10 : 12;
                        const gridBottom = categoryCount > 24 ? 140 : categoryCount > 16 ? 120 : 100;

                        const option = {
                            legend: { show: false },
                            tooltip: {
                                trigger: 'axis',
                                axisPointer: { type: 'shadow' },
                                confine: true,
                                formatter: (params) => {
                                    if (!Array.isArray(params) || !params.length) return '';
                                    const axisLabel = params[0]?.axisValueLabel ?? '';
                                    const lines = [escapeHtml(axisLabel)];
                                    let hasValue = false;
                                    params.forEach((item) => {
                                        const displayValue = typeof item?.data?.displayValue === 'number' ? item.data.displayValue : null;
                                        if (displayValue === null) return;
                                        const label = item?.seriesName ?? '';
                                        lines.push(`${item.marker || ''}${escapeHtml(label)}: ${formatNumber(displayValue, 2)}%`);
                                        hasValue = true;
                                    });
                                    if (!hasValue) {
                                        lines.push('Tidak ada data');
                                    }
                                    return lines.join('<br/>');
                                },
                            },
                            grid: {
                                left: '3%',
                                right: '3%',
                                top: 70,
                                bottom: gridBottom,
                                containLabel: true,
                            },
                            xAxis: {
                                type: 'category',
                                data: categories,
                                axisTick: { alignWithLabel: true },
                                axisLabel: {
                                    interval: 0,
                                    rotate: rot,
                                    fontSize: fs,
                                    margin: 10,
                                    hideOverlap: true,
                                    overflow: 'truncate',
                                    width: Math.max(50, slot - 8),
                                },
                            },
                            yAxis: {
                                type: 'value',
                                min: 0,
                                max: yMax,
                                axisLabel: { formatter: (v) => `${formatNumber(v, 0)}%` },
                                splitLine: { show: true, lineStyle: { color: '#d0d7de', width: 1, type: 'dashed' } },
                                axisLine: { lineStyle: { color: '#adb5bd' } },
                                axisTick: { lineStyle: { color: '#adb5bd' } },
                            },
                            series,
                            animationDuration: 250,
                            animationDurationUpdate: 200,
                        };

                        showChart(emptyEl, container);
                        instance.setOption(option, true);
                        ensureResize(instance);

                        if (summaryEl) {
                            const totalValue = department?.overall ?? (visibleEntries.find((entry) => entry.isTotal)?.summaryValue ?? null);
                            const evaluated = visibleEntries.filter((entry) => !entry.isTotal && entry.hasData).length;

                            if (totalValue === null && evaluated === 0) {
                                summaryEl.innerHTML = '<span class="fw-semibold">Total Pencapaian:</span> N/A';
                            } else {
                                const parts = [];
                                if (totalValue !== null) {
                                    parts.push(`<span class="fw-semibold">Total Pencapaian:</span> ${formatNumber(totalValue, 2)}%`);
                                }
                                if (evaluated > 0) {
                                    parts.push(`- <span class="fw-semibold">${evaluated}</span> job positions`);
                                }
                                summaryEl.innerHTML = parts.join(' ');
                            }
                        }
                    };

                    // Init
                    buildAndSetOption();
                    renderLegend();

                    // Responsif
                    if (typeof ResizeObserver !== 'undefined') {
                    const ro = new ResizeObserver(() => ensureResize(instance));
                    ro.observe(container);
                    } else {
                    window.addEventListener('resize', () => ensureResize(instance));
                    }
                    window.addEventListener('load', () => ensureResize(instance));
                    setTimeout(() => ensureResize(instance), 0);
                });
                }





            function updateAreaDevelopmentTable(competencies) {
                const tableBody = document.getElementById('tcpd-competency-body');
                if (!tableBody) return;

                tableBody.innerHTML = ''; // Clear existing rows

                const developmentRows = Array.isArray(competencies)
                    ? competencies.filter(row => (toNumber(row.qty) || 0) > 0)
                    : [];

                if (developmentRows.length === 0) {
                    const selectedJobPositionName = jobSelect ? (jobSelect.options[jobSelect.selectedIndex] ? jobSelect.options[jobSelect.selectedIndex].text : '') : '';
                    const message = selectedJobPositionName
                        ? 'Tidak ada user di bawah standar untuk periode ini.'
                        : 'Silakan pilih job position untuk melihat data.';
                    tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-muted small py-4">${escapeHtml(message)}</td></tr>`;
                    return;
                }

                let rowNumber = 1;
                developmentRows.forEach(row => {
                    const standardValue = row.standard;
                    const averageValue = row.average;

                    const displayStandard = (standardValue !== null && standardValue !== '')
                        ? formatNumber(standardValue, 2)
                        : '-';
                    const displayAverage = (averageValue !== null)
                        ? formatNumber(averageValue, 2)
                        : '-';
                    const belowCount = row.qty || 0;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${rowNumber++}</td>
                        <td>${escapeHtml(row.name)}</td>
                        <td>${displayAverage}</td>
                        <td>${displayStandard}</td>
                        <td>${belowCount}</td>
                    `;
                    tableBody.appendChild(tr);
                });
            }

            function updateJobSummary(summary) {
                const summaryEl = document.getElementById('tcpd-summary');
                if (!summaryEl) return;

                const jobName = summary?.jobPositionName || '';
                const userCount = summary?.qty || 0;

                if (!jobName) {
                    summaryEl.classList.add('d-none');
                    summaryEl.innerHTML = '';
                    return;
                }

                let html = `<span class="fw-semibold">Job Position:</span> ${escapeHtml(jobName)}`;
                if (userCount > 0) {
                    html += ` - <span class="fw-semibold">${userCount}</span> user terdaftar`;
                } else {
                    html += ' - Belum ada user terdaftar';
                }

                summaryEl.innerHTML = html;
                summaryEl.classList.remove('d-none');
            }

            const fetchCompanyData = () => {
                if (isCompanyLoading) return;
                isCompanyLoading = true;
                setButtonLoading(companyApplyButton, true);

                const params = new URLSearchParams();
                if (companyYearFromInput && companyYearFromInput.value) {
                    params.set('year_from', companyYearFromInput.value);
                }
                if (companyYearToInput && companyYearToInput.value) {
                    params.set('year_to', companyYearToInput.value);
                }

                const endpoint = endpoints.company || '';
                const url = params.toString() ? `${endpoint}?${params.toString()}` : endpoint;

                fetch(url, { headers: { Accept: 'application/json' } })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (!payload || payload.success !== true) {
                            throw new Error(payload?.message || 'Gagal memuat data company.');
                        }

                        const dataset = payload.data || {};
                        const entries = Array.isArray(dataset.company_chart_rows) ? dataset.company_chart_rows : [];
                        const hasEntriesData = entries.some((entry) => {
                            if (Array.isArray(entry?.values) && entry.values.length) {
                                return entry.values.some((value) => toNumber(value?.percentage) !== null);
                            }
                            const summary = toNumber(entry?.summary_percentage);
                            const direct = toNumber(entry?.percentage);
                            return summary !== null || direct !== null;
                        });

                        const meta = {
                            average: toNumber(dataset.company_average) ?? 0,
                            count: toNumber(dataset.company_department_count) ?? 0,
                            hasData: hasEntriesData || !!dataset.company_has_data,
                            years: Array.isArray(dataset.company_years) ? dataset.company_years : [],
                            mode: typeof dataset.company_chart_mode === 'string' ? dataset.company_chart_mode : 'aggregate',
                        };

                        const legend = document.getElementById('company-chart-legend');
                        if (legend) legend.remove();
                        const container = document.getElementById('company-chart');
                        if (container) {
                            const existingInstance = echarts.getInstanceByDom(container);
                            if (existingInstance) existingInstance.dispose();
                        }

                        initCompanyChart(entries, meta);

                        const emptyEl = document.getElementById('company-chart-empty');
                        if (emptyEl) {
                            emptyEl.dataset.emptyMessage = meta.hasData ? '' : 'Data persentase departemen belum tersedia.';
                            if (!meta.hasData) {
                                emptyEl.textContent = emptyEl.dataset.emptyMessage || 'Data persentase departemen belum tersedia.';
                            }
                        }

                        if (Array.isArray(meta.years) && meta.years.length) {
                            const sortedYears = [...meta.years].map((year) => String(year)).sort();
                            if (companyYearFromInput) companyYearFromInput.value = sortedYears[0] ?? '';
                            if (companyYearToInput) companyYearToInput.value = sortedYears[sortedYears.length - 1] ?? '';
                        }

                        currentDepartmentData = Array.isArray(dataset.department_summaries) ? dataset.department_summaries : [];
                        initDepartmentCharts(currentDepartmentData);
                    })
                    .catch((error) => {
                        console.error(error);
                    })
                    .finally(() => {
                        isCompanyLoading = false;
                        setButtonLoading(companyApplyButton, false);
                    });
            };

            const fetchJobData = () => {
                if (!jobSelect || isJobLoading) return;
                isJobLoading = true;
                setButtonLoading(jobApplyButton, true);

                const params = new URLSearchParams();
                params.set('job_position_id', jobSelect.value || '');
                if (jobDateFromInput && jobDateFromInput.value) {
                    params.set('date_from', jobDateFromInput.value);
                }
                if (jobDateToInput && jobDateToInput.value) {
                    params.set('date_to', jobDateToInput.value);
                }

                const endpoint = endpoints.job || '';
                const url = `${endpoint}?${params.toString()}`;

                fetch(url, { headers: { Accept: 'application/json' } })
                    .then((response) => response.json())
                    .then((payload) => {
                        if (!payload || payload.success !== true) {
                            throw new Error(payload?.message || 'Gagal memuat data job position.');
                        }

                        const dataset = payload.data || {};
                        const userSummaries = dataset.user_summaries || [];

                        const container = document.getElementById('job-position-chart');
                        if (container) {
                            const existingInstance = echarts.getInstanceByDom(container);
                            if (existingInstance) existingInstance.dispose();
                        }

                        initJobPositionChart(userSummaries);

                        const emptyEl = document.getElementById('job-position-chart-empty');
                        if (emptyEl) {
                            emptyEl.dataset.emptyMessage = 'Data persentase belum tersedia untuk job position ini.';
                            if (!userSummaries.length) {
                                emptyEl.textContent = emptyEl.dataset.emptyMessage;
                            }
                        }

                        updateAreaDevelopmentTable(Array.isArray(dataset.competencies) ? dataset.competencies : []);
                        updateJobSummary({
                            jobPositionName: dataset.job_position,
                            qty: Number(dataset.qty ?? 0),
                        });
                    })
                    .catch((error) => {
                        console.error(error);
                    })
                    .finally(() => {
                        isJobLoading = false;
                        setButtonLoading(jobApplyButton, false);
                    });
            };

            if (companyFilterForm) {
                companyFilterForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    fetchCompanyData();
                });
            }
            if (companyApplyButton) {
                companyApplyButton.addEventListener('click', () => fetchCompanyData());
            }
            if (companyResetButton) {
                companyResetButton.addEventListener('click', () => {
                    if (companyYearFromInput) companyYearFromInput.value = '';
                    if (companyYearToInput) companyYearToInput.value = '';
                    fetchCompanyData();
                });
            }
            if (jobFilterForm) {
                jobFilterForm.addEventListener('submit', (event) => {
                    event.preventDefault();
                    fetchJobData();
                });
            }
            if (jobApplyButton) {
                jobApplyButton.addEventListener('click', () => fetchJobData());
            }
            if (jobSelect) {
                jobSelect.addEventListener('change', () => fetchJobData());
            }
            if (jobResetButton) {
                jobResetButton.addEventListener('click', () => {
                    if (jobDateFromInput) jobDateFromInput.value = '';
                    if (jobDateToInput) jobDateToInput.value = '';
                    fetchJobData();
                });
            }

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
        });
    </script>
    @endsection
