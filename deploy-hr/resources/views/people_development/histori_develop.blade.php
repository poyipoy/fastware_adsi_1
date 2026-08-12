@extends('layout')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/hr/training-history.css') }}">
@endpush

@section('content')
    @php
        $historyPageConfig = [
            'initial' => $historyPayload,
            'filters' => $historyFilters,
            'endpoints' => [
                'filter' => route('people_development.filter'),
                'export_csv' => route('people_development.export.csv'),
                'export_xlsx' => route('people_development.export.history'),
            ],
            'page_size' => 25,
        ];
    @endphp

    <main id="main" class="main training-history-page" data-training-history-page>
        <div class="pagetitle training-history-breadcrumb">
            <h1>History Development</h1>
            <nav aria-label="Breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <span>Human Resource</span>
                    </li>
                    <li class="breadcrumb-item">
                        <span>Training Development</span>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">History Development</li>
                </ol>
            </nav>
        </div>

        <section class="section" aria-labelledby="historyPageTitle">
            <div class="training-history-heading">
                <div class="training-history-heading__identity">
                    <span class="training-history-heading__icon" aria-hidden="true">
                        <i class="bi bi-clock-history"></i>
                    </span>
                    <div>
                        <h2 id="historyPageTitle">Riwayat Training Karyawan</h2>
                        <p>Telusuri program pengembangan yang telah selesai berdasarkan departemen, tahun, atau karyawan.</p>
                    </div>
                </div>
                <span class="training-history-count" id="resultCount" aria-live="polite">
                    {{ number_format($historyPayload['meta']['total'], 0, ',', '.') }} Data
                </span>
            </div>

            <div class="training-history-filter-card">
                <form id="historyFilterForm" class="training-history-filter-form" novalidate>
                    <div class="training-history-field training-history-field--department">
                        <label for="department_id">Departemen</label>
                        <select id="department_id" name="department_id" class="form-select">
                            <option value="">Semua Departemen</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}"
                                    @selected((int) ($historyFilters['department_id'] ?? 0) === (int) $department->id)>
                                    {{ $department->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="training-history-field training-history-field--year">
                        <label for="year">Tahun</label>
                        <select id="year" name="year" class="form-select">
                            <option value="">Semua Tahun</option>
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}" @selected((int) ($historyFilters['year'] ?? 0) === $year)>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="training-history-field training-history-field--search">
                        <label for="searchInput">Cari Data</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">
                                <i class="bi bi-search"></i>
                            </span>
                            <input
                                type="search"
                                id="searchInput"
                                name="search"
                                class="form-control"
                                value="{{ $historyFilters['search'] ?? '' }}"
                                maxlength="150"
                                autocomplete="off"
                                placeholder="Cari NPK, nama karyawan, atau program"
                            >
                        </div>
                    </div>

                    <div class="training-history-filter-actions">
                        <button type="submit" id="btnFilter" class="btn btn-primary">
                            <i class="bi bi-funnel" aria-hidden="true"></i>
                            <span>Terapkan Filter</span>
                        </button>
                        <button type="button" id="btnReset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                            <span>Reset</span>
                        </button>
                    </div>
                </form>

                <div class="training-history-export-actions" aria-label="Export history development">
                    <span class="training-history-export-label">Export data:</span>
                    <a
                        id="exportCsv"
                        class="btn btn-outline-success{{ ($historyPayload['meta']['total'] ?? 0) === 0 ? ' disabled' : '' }}"
                        href="{{ route('people_development.export.csv', array_filter($historyFilters)) }}"
                        @if (($historyPayload['meta']['total'] ?? 0) === 0) aria-disabled="true" tabindex="-1" @endif
                    >
                        <i class="bi bi-filetype-csv" aria-hidden="true"></i>
                        <span>CSV</span>
                    </a>
                    <a
                        id="exportXlsx"
                        class="btn btn-success{{ ($historyPayload['meta']['total'] ?? 0) === 0 ? ' disabled' : '' }}"
                        href="{{ route('people_development.export.history', array_filter($historyFilters)) }}"
                        @if (($historyPayload['meta']['total'] ?? 0) === 0) aria-disabled="true" tabindex="-1" @endif
                    >
                        <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                        <span>XLSX</span>
                    </a>
                </div>
            </div>

            <div id="activeFilters" class="training-history-active-filters" aria-live="polite"></div>

            <div class="training-history-table-card">
                <div class="training-history-table-toolbar">
                    <div>
                        <h3>Daftar History Development</h3>
                        <p id="tableRange">Menyiapkan data...</p>
                    </div>
                    <div class="training-history-view-options">
                        <div class="training-history-compact-field">
                            <label for="sortOrder">Urutkan</label>
                            <select id="sortOrder" class="form-select">
                                <option value="newest">Terbaru</option>
                                <option value="oldest">Terlama</option>
                                <option value="name_asc">Nama A–Z</option>
                                <option value="program_asc">Program A–Z</option>
                            </select>
                        </div>
                        <div class="training-history-compact-field">
                            <label for="pageSize">Baris</label>
                            <select id="pageSize" class="form-select">
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                </div>

                <p id="historyScrollHint" class="training-history-scroll-hint">
                    <i class="bi bi-arrow-left-right" aria-hidden="true"></i>
                    Geser tabel ke samping untuk melihat semua kolom.
                </p>

                <div
                    id="historyTableViewport"
                    class="training-history-table-viewport"
                    tabindex="0"
                    role="region"
                    aria-label="Tabel History Development"
                    aria-describedby="historyScrollHint"
                >
                    <table class="table training-history-table mb-0" id="historyTable">
                        <caption class="visually-hidden">
                            Daftar riwayat training karyawan yang telah selesai.
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">NPK</th>
                                <th scope="col">Nama Karyawan</th>
                                <th scope="col">Nama Program</th>
                                <th scope="col">Kategori Kompetensi</th>
                                <th scope="col">Kompetensi</th>
                                <th scope="col">Lembaga</th>
                                <th scope="col">Periode Aktual</th>
                                <th scope="col" class="text-center">Bukti</th>
                            </tr>
                        </thead>
                        <tbody id="peopleDevTabel">
                            <tr>
                                <td colspan="8" class="training-history-state">
                                    <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                                    <span>Menyiapkan data...</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="training-history-pagination-bar">
                    <p id="paginationSummary">0 data</p>
                    <nav aria-label="Pagination History Development">
                        <ul id="historyPagination" class="pagination mb-0"></ul>
                    </nav>
                </div>
            </div>

            <div id="historyStatus" class="visually-hidden" aria-live="polite" aria-atomic="true"></div>

            <noscript>
                <div class="alert alert-warning mt-3" role="alert">
                    Aktifkan JavaScript untuk menggunakan filter dan pagination History Development.
                </div>
            </noscript>
        </section>

        <script id="trainingHistoryConfig" type="application/json">@json($historyPageConfig)</script>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/hr/training-history-ui.js') }}"></script>
@endpush
