@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ asset('css/hr/training-follow-up.css') }}">
        <link rel="stylesheet" href="{{ asset('css/hr/training-status-summary.css') }}">
        <script src="{{ asset('js/hr/training-status-summary.js') }}" defer></script>
        <style>
            .container {
                margin-top: 20px;
            }

            .card {
                background-color: #f8f9fa;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 20px;
                margin-bottom: 20px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                width: 100%;
                /* Lebar card 100% */
                box-sizing: border-box;
                /* Memastikan padding tidak menambah lebar card */
            }

            .card-content {
                display: flex;
                flex-direction: column;
            }

            .form-group {
                margin-bottom: 10px;
            }

            label {
                font-weight: bold;
            }
            .styled-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                margin: 25px 0;
                font-size: 14px;
                text-align: left;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
                border-radius: 8px;
                overflow: hidden;
                border: 1px solid #e2e8f0;
            }

            .styled-table thead tr:first-child th {
                border-top: none;
            }

            .styled-table thead th {
                background-color: #e2e8f0;
                color: #334155;
                text-align: left;
                font-weight: 600;
                padding: 14px 16px;
                border-bottom: 2px solid #e2e8f0;
                border-right: 1px solid #edf2f7;
            }
            .styled-table thead th:last-child {
                border-right: none;
            }

            .styled-table tbody td {
                padding: 14px 16px;
                border-bottom: 1px solid #edf2f7;
                border-right: 1px solid #edf2f7;
                vertical-align: middle;
            }
            .styled-table tbody td:last-child {
                border-right: none;
            }

            .styled-table tbody tr {
                transition: background-color 0.2s ease;
                background-color: #ffffff;
            }

            .styled-table tbody tr:hover {
                background-color: #f1f5f9;
            }

            .styled-table tbody tr:last-of-type td {
                border-bottom: none;
            }

            .styled-table tbody tr.active-row {
                font-weight: bold;
                color: #0d6efd;
            }

            /* Divider for Aktual columns */
            .styled-table th.col-aktual-start,
            .styled-table td.col-aktual-start {
                border-left: 2px solid #cbd5e1 !important;
            }

            /* Accent for Aktual header group */
            .styled-table th.aktual-group-header {
                border-top: 3px solid #64748b !important;
                background-color: #cbd5e1; /* Slightly darker gray for the group header */
            }

            /* Form inputs in table */
            .styled-table .form-control-sm,
            .styled-table .form-select-sm {
                border-radius: 6px;
                border: 1px solid #cbd5e1;
            }
            .styled-table .form-control-sm:focus,
            .styled-table .form-select-sm:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
            }

            select.status-dropdown {
                color: white;
            }
            .status-dropdown option {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
        </style>
        <div class="pagetitle" data-training-status-anchor>
            <h1>Halaman Tindak Lanjut Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu Tindak Lanjut Training</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            {{-- Modul 4.2: Year Management Bar --}}
            @php
                $activeYear = \App\Models\MstPdActiveYear::getActiveYear();
            @endphp
            <div class="card mb-3 border-0 shadow-sm bg-primary text-white">
                <div class="card-body py-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-calendar2-check fs-4"></i>
                        <div>
                            <div class="small fw-semibold text-white-50">Tahun Aktif Training</div>
                            <div class="fw-bold fs-5" id="active-year-display">{{ $activeYear ?? date('Y') }}</div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('people_development.export.follow_up', ['tahun' => $activeYear ?? date('Y')] + request()->query()) }}"
                            class="btn btn-outline-light btn-sm rounded-pill px-3 fw-semibold {{ $data->isEmpty() ? 'disabled' : '' }}"
                            @if($data->isEmpty()) aria-disabled="true" tabindex="-1" @endif>
                            <i class="bi bi-file-earmark-excel me-1"></i>Export XLSX
                        </a>
                        @can('manage-pd-year')
                        <select id="year-select" class="form-select form-select-sm" style="width:auto;min-width:90px;">
                            @for($y = date('Y') + 1; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ (string)$activeYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="button" id="btn-set-year" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>Set Tahun
                        </button>
                        <span id="year-set-msg" class="small text-white-50 d-none"><i class="bi bi-check-circle me-1"></i>Tersimpan!</span>
                        @endcan
                    </div>
                </div>
            </div>
            <!-- Filter Bar -->
            <div class="card mb-4 shadow-sm border-0 training-followup-toolbar" id="training-followup-toolbar">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari NPK, nama karyawan, atau program">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="Mencari Vendor">Mencari Vendor</option>
                                <option value="Proses Pendaftaran">Proses Pendaftaran</option>
                                <option value="On Progress">On Progress</option>
                                <option value="Done">Done</option>
                                <option value="Pending">Pending</option>
                                <option value="Ditolak">Ditolak</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2 align-items-center">
                            <!-- Toggle View Buttons -->
                            <div class="btn-group" role="group" aria-label="View mode">
                                <button type="button" id="btn-table-view" class="btn btn-primary btn-sm" title="Tampilan Tabel Ringkasan" aria-pressed="true">
                                    <i class="bi bi-table"></i> Tabel
                                </button>
                                <button type="button" id="btn-card-view" class="btn btn-outline-secondary btn-sm" title="Tampilan Card Detail" aria-pressed="false">
                                    <i class="bi bi-card-list"></i> Card
                                </button>
                            </div>
                        </div>
                        <div class="col-md-1 text-end">
                            <span class="badge bg-primary rounded-pill p-2" id="filterCount">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== TABEL RINGKASAN (Table View) ===== -->
            <div id="table-summary-section" class="card mb-4 shadow-sm border-0" style="display: none;">
                <div class="card-body p-0">
                    <div class="table-responsive training-followup-table-viewport">
                        <table id="summary-table" class="styled-table" style="width:100%; min-width: 1400px; margin: 0;">
                            <thead>
                                <tr>
                                    <th scope="col" rowspan="2" style="min-width:40px;">No</th>
                                    <th scope="col" rowspan="2" style="min-width:130px;">Kategori Usulan</th>
                                    <th scope="col" rowspan="2" style="min-width:100px;">Section</th>
                                    <th scope="col" rowspan="2" style="min-width:130px;">Job Position</th>
                                    <th scope="col" rowspan="2" style="min-width:260px;">NPK — Nama Karyawan / Participant</th>
                                    <th scope="col" rowspan="2" style="min-width:160px;">Program Training</th>
                                    <th scope="col" rowspan="2" style="min-width:140px;">Kategori Competency</th>
                                    <th scope="col" rowspan="2" style="min-width:150px;">Competency</th>
                                    <th scope="col" rowspan="2" style="min-width:100px;">Due Date</th>
                                    <th scope="col" rowspan="2" style="min-width:110px;">Budget Usulan</th>
                                    <th scope="col" rowspan="2" style="min-width:110px;">Lembaga</th>
                                    <th scope="col" rowspan="2" style="min-width:150px;">Keterangan Tujuan</th>
                                    <th scope="col" rowspan="2" style="min-width:150px;">Objective Learning</th>
                                    <!-- Kolom Aktual (header baris ke-2) -->
                                    <th scope="col" colspan="7" class="aktual-group-header col-aktual-start" style="text-align:center;">Tindak Lanjut Aktual</th>
                                    <th scope="col" rowspan="2" style="min-width:80px; text-align:center;">Aksi</th>
                                </tr>
                                <tr>
                                    <th scope="col" class="col-aktual-start" style="min-width:160px;">Nama Program Aktual</th>
                                    <th scope="col" style="min-width:100px;">Date Aktual</th>
                                    <th scope="col" style="min-width:120px;">Biaya Aktual</th>
                                    <th scope="col" style="min-width:110px;">Lembaga Aktual</th>
                                    <th scope="col" style="min-width:150px;">Keterangan</th>
                                    <th scope="col" style="min-width:150px;">Objective Learning Aktual</th>
                                    <th scope="col" style="min-width:160px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="summary-table-body">
                                <!-- Akan diisi oleh JS -->
                            </tbody>
                        </table>
                    </div>
                    <div class="p-3 bg-light border-top">
                        <button type="button" id="add-additional-btn-table" class="btn btn-success rounded-pill px-4" onclick="addAdditionalInlineRow()">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Baris Additional
                        </button>
                    </div>
                </div>
            </div>

            <!-- ===== CARD VIEW (Card Detail Mode) ===== -->
            <div id="card-view-section" style="display: none;">
            <!-- Formulir Data -->
            <div class="card border-0 bg-transparent">
                <!-- Tabel untuk edit data -->
                <form id="trainingForm" action="{{ route('updateData') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST') <!-- Ini penting untuk override ke PUT -->
                        <div id="table-body">
                            <!-- Baris data akan diisi di sini -->
                        </div>
                        
                        @php
                            // Hitung total budget hanya jika tahun_usulan kosong
                            $totalBudget = $data
                                ->filter(function ($item) {
                                    return empty($item->tahun_usulan);
                                })
                                ->sum(function ($item) {
                                    return floatval($item->biaya); // Mengkonversi ke float
                                });

                            $totalBudget2 = $data
                                ->filter(function ($item) {
                                    return empty($item->tahun_usulan);
                                })
                                ->sum(function ($item) {
                                    return floatval($item->biaya_plan); // Mengkonversi ke float
                                });
                        @endphp
                        
                        <div class="card mb-4 bg-light shadow-sm border-0">
                            <div class="card-body">
                                <h6 class="fw-bold m-0 d-flex justify-content-between">
                                    <span>Sub Total Usulan 1: Rp {{ number_format($totalBudget, 0, ',', '.') }}</span>
                                    <span>Sub Total Actual 1: Rp {{ number_format($totalBudget2, 0, ',', '.') }}</span>
                                </h6>
                            </div>
                        </div>

                        <h3 class="mt-4 mb-3 text-secondary"><b>ADDITIONAL</b> <i class="fas fa-chevron-down"></i></h3>
                        <div id="table-body2">
                            <!-- Baris data akan diisi di sini -->
                        </div>
                        <button type="button" id="add-additional-btn" class="btn btn-success rounded-pill px-4 mt-2 mb-4">
                            <i class="fas fa-plus-circle me-1"></i> Tambah Baris
                        </button>

                        @php
                            // Hitung total budget dari tabel pertama
                            $totalBudgetTabelPertama = $data->sum(function ($item) {
                                return floatval($item->biaya); // Pastikan nilai sebagai float
                            });

                            // Filter data untuk tabel kedua
                            $filteredData = $data->filter(function ($item) {
                                return !is_null($item->tahun_usulan);
                            });

                            // Hitung total budget dari tabel pertama
                            $totalBudgetTabelPertama2 = $data->sum(function ($item) {
                                return floatval($item->biaya_plan); // Pastikan nilai sebagai float
                            });

                            // Hitung total budget untuk tabel kedua
                            $totalBudgetTabelKedua = $filteredData->sum(function ($item) {
                                return floatval($item->biaya); // Pastikan nilai sebagai float
                            });

                            $totalBudgetTabelKeduasub2 = $filteredData->sum(function ($item) {
                                return floatval($item->biaya_plan); // Pastikan nilai sebagai float
                            });

                            // Hitung total biaya plan
                            $totalBiayaPlan = $totalBudgetTabelKedua + $totalBudgetTabelPertama;
                            $totalBiayaPlan2 = $totalBudgetTabelKeduasub2 + $totalBudgetTabelPertama2;

                            // Hitung selisih biaya
                            $selisihBiaya = $totalBiayaPlan2 - $totalBiayaPlan;
                        @endphp

                        <!-- Bagian untuk tabel kedua, hanya ditampilkan jika ada data yang memenuhi syarat -->
                        @if ($filteredData->isNotEmpty())
                            <div class="card mb-4 bg-light shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="fw-bold mb-3 d-flex justify-content-between text-muted">
                                        <span>Sub Total Usulan 2: Rp {{ number_format($totalBudgetTabelKedua, 0, ',', '.') }}</span>
                                        <span>Sub Total Actual 2: Rp {{ number_format($totalBudgetTabelKeduasub2, 0, ',', '.') }}</span>
                                    </h6>
                                    <hr>
                                    <h5 class="fw-bold m-0 d-flex justify-content-between text-primary">
                                        <span>Total Usulan Keseluruhan: Rp {{ number_format($totalBiayaPlan, 0, ',', '.') }}</span>
                                        <span>Total Actual Keseluruhan: Rp {{ number_format($totalBiayaPlan2, 0, ',', '.') }}</span>
                                    </h5>
                                </div>
                            </div>
                        @endif

                    </form>
                </div>
            </div>
            </div>{{-- end card-view-section --}}

            <!-- Sticky Footer Actions (Visible in both views) -->
            <div class="position-sticky bg-white p-3 shadow-lg z-3 border-top rounded-top-4 mt-5 d-flex justify-content-between align-items-center" style="bottom: 33px;">
                <div>
                    <h6 class="fw-bold mb-1 text-primary">Total Biaya Plan: Rp {{ number_format($totalBiayaPlan, 0, ',', '.') }}</h6>
                    <h4 class="fw-bold m-0 text-success">Total Biaya Aktual: Rp {{ number_format($totalBiayaPlan2, 0, ',', '.') }}</h4>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('indexPD2') }}" class="btn btn-secondary rounded-pill px-4"><i class="fas fa-arrow-left me-1"></i> Kembali</a>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="save-button" data-action="save">
                        <i class="fas fa-save me-1"></i> Simpan Data
                    </button>
                    @if ($data->where('status_1', '!=', 3)->count() > 0)
                        <button type="button" class="btn btn-success rounded-pill px-4" id="approve-button" data-action="approve">
                            <i class="fas fa-check me-1"></i> Approve
                        </button>
                    @endif
                </div>
            </div>

        </section>
                {{-- status color --}}
                <div class="training-status-summary" data-training-status-summary>
                    <h6 class="fw-bold mb-3" style="font-weight: 600;"><i class="fas fa-chart-pie me-2 text-secondary"></i> Ringkasan Status</h6>
                    <div class="d-flex flex-wrap" style="gap: 12px;">
                        <!-- Biru: Mencari Vendor -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(13, 110, 253, 0.1); color: #0d6efd; border: 1px solid rgba(13, 110, 253, 0.2); font-size: 13px; font-weight: 600;">
                            Mencari Vendor
                            <span class="mx-2 text-primary" style="opacity: 0.3;">|</span>
                            <span id="status-blue-percentage">
                                {{ $countStatusBlue }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusBlue, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Orange: Proses Pendaftaran -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(253, 126, 20, 0.1); color: #fd7e14; border: 1px solid rgba(253, 126, 20, 0.2); font-size: 13px; font-weight: 600;">
                            Proses Pendaftaran
                            <span class="mx-2" style="opacity: 0.3; color: #fd7e14;">|</span>
                            <span id="status-orange-percentage">
                                {{ $countStatusOrange }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusOrange, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Kuning: On Progress -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(255, 193, 7, 0.15); color: #d39e00; border: 1px solid rgba(255, 193, 7, 0.3); font-size: 13px; font-weight: 600;">
                            On Progress
                            <span class="mx-2" style="opacity: 0.3; color: #d39e00;">|</span>
                            <span id="status-yellow-percentage">
                                {{ $countStatusYellow }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusYellow, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Hijau: Done -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(25, 135, 84, 0.1); color: #198754; border: 1px solid rgba(25, 135, 84, 0.2); font-size: 13px; font-weight: 600;">
                            Done
                            <span class="mx-2" style="opacity: 0.3; color: #198754;">|</span>
                            <span id="status-green-percentage">
                                {{ $countStatusGreen }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusGreen, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Abu: Pending -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(108, 117, 125, 0.1); color: #6c757d; border: 1px solid rgba(108, 117, 125, 0.2); font-size: 13px; font-weight: 600;">
                            Pending
                            <span class="mx-2" style="opacity: 0.3; color: #6c757d;">|</span>
                            <span id="status-gray-percentage">
                                {{ $countStatusGray }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusGray, 0) }}%)</span>
                            </span>
                        </div>

                        <!-- Merah: Ditolak -->
                        <div class="d-inline-flex align-items-center px-3 py-1" style="border-radius: 20px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2); font-size: 13px; font-weight: 600;">
                            Ditolak
                            <span class="mx-2" style="opacity: 0.3; color: #dc3545;">|</span>
                            <span id="status-red-percentage">
                                {{ $countStatusRed }} <span style="font-weight: 400; opacity: 0.8;">({{ number_format($percentageStatusRed, 0) }}%)</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- jQuery (single import) -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        
        <!-- TomSelect CSS & JS -->
        <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>

        <script>
            var existingData = @json($data);
            var jobPositions = @json($jobPositions);
            const masterCompetencies = @json($masterCompetencies);

            const displayNpk = (value) => {
                const npk = String(value ?? '').trim();
                return !npk || npk === '0' ? '-' : npk;
            };
            const employeeLabel = (user) => `${displayNpk(user?.npk)} - ${String(user?.name || '-').trim()}`;
            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            function participantCandidates() {
                const candidates = new Map();
                jobPositions.forEach((position) => (position.active_users || []).forEach((user) => {
                    if (!candidates.has(Number(user.id))) candidates.set(Number(user.id), user);
                }));
                return candidates;
            }

            function initializeParticipantSelect(select, selectedIds = [], legacyUsers = [], onChange = null) {
                if (!select) return;
                const candidates = participantCandidates();
                const selectedIdSet = new Set((selectedIds || []).map(Number));
                (legacyUsers || []).filter(Boolean).forEach((user) => {
                    if (!candidates.has(Number(user.id))) {
                        candidates.set(Number(user.id), {...user, __legacy: true});
                    }
                });

                select.replaceChildren();
                [...candidates.values()].sort((a, b) => employeeLabel(a).localeCompare(employeeLabel(b), 'id')).forEach((user) => {
                    const option = document.createElement('option');
                    option.value = String(user.id);
                    option.textContent = employeeLabel(user) + (user.__legacy ? ' (data lama - tidak aktif)' : '');
                    option.selected = selectedIdSet.has(Number(user.id));
                    option.dataset.npk = displayNpk(user.npk);
                    option.dataset.name = user.name || '';
                    select.appendChild(option);
                });

                if (select.tomselect) select.tomselect.destroy();
                return new TomSelect(select, {
                    plugins: ['remove_button'],
                    maxItems: null,
                    closeAfterSelect: false,
                    searchField: ['text'],
                    placeholder: 'Cari NPK atau nama participant',
                    dropdownParent: 'body',
                    dropdownClass: 'ts-dropdown training-followup-participant-dropdown',
                    onChange(value) {
                        if (typeof onChange !== 'function') return;
                        const values = Array.isArray(value)
                            ? value
                            : (value ? [value] : []);
                        onChange(values.map(String));
                    },
                });
            }

            // view tabel 1
            const activeYear = '{{ \App\Models\MstPdActiveYear::getActiveYear() }}';
            document.addEventListener('DOMContentLoaded', function() {
                var tableBody = document.getElementById('table-body');

                const updateEvaluasiRoute = "{{ route('update-evaluasi', ':id') }}";

                existingData.forEach(function(item, index) {
                    // Cek apakah tahun_usulan tidak ada (null, undefined, atau kosong)
                    if (!item.tahun_usulan) {
                        var newRow = document.createElement('div');
                        newRow.id = `row-${item.id}`; // Set a unique ID for each row
                        var borderClass = '';
                        switch (item.status_2) {
                            case 'Done': borderClass = 'border-success'; break;
                            case 'On Progress': borderClass = 'border-warning'; break;
                            case 'Mencari Vendor': borderClass = 'border-info'; break;
                            case 'Proses Pendaftaran': borderClass = 'border-primary'; break;
                            case 'Ditolak': borderClass = 'border-danger'; break;
                            case 'Pending': borderClass = 'border-secondary'; break;
                            default: borderClass = 'border-light'; break;
                        }

                        newRow.className = `card mb-4 shadow-sm border-0 border-start border-5 ${borderClass} dynamic-card`;
                        newRow.dataset.employeeName = item.user ? `${displayNpk(item.user.npk)} ${item.user.name}`.toLowerCase() : '';
                        newRow.dataset.programName = item.program_training ? item.program_training.toLowerCase() : '';
                        newRow.dataset.status = item.status_2 || '';

                        var userOptions = '<option value="">---- Pilih Karyawan ----</option>';
                        var competencyOptions = '<option value="">---- Pilih Competency ----</option>';

                        if (item.user) {
                            userOptions +=
                                `<option value="${item.user.id}" selected>${escapeHtml(employeeLabel(item.user))}</option>`;
                        }

                        if (item.competency) {
                            competencyOptions +=
                                `<option value="${item.competency}" selected>${item.competency}</option>`;
                        }

                        var headerTitle = escapeHtml(
                            `${item.user ? employeeLabel(item.user) : 'Unknown User'} - ${item.program_training || 'Unknown Program'}`
                        );

                        newRow.innerHTML = `
                            <div class="card-header bg-light d-flex justify-content-between align-items-center border-bottom-0 pt-3 pb-0">
                                <h5 class="m-0 fw-bold text-primary"><i class="fas fa-user-graduate me-2"></i>${headerTitle}</h5>
                            </div>
                            <div class="card-body">
                                <input type="hidden" id="modified_at" name="modified_at" value="${item.modified_at}" />
                                <input type="hidden" name="id[]" value="${item.id}" /> <!-- Hidden ID field -->
                                
                                <div class="row">
                                    <!-- KIRI: DATA USULAN -->
                                    <div class="col-lg-6 mb-4 mb-lg-0">
                                        <h6 class="text-secondary border-bottom pb-2 mb-3"><i class="fas fa-file-alt me-1"></i> 1. Data Usulan</h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select section-dropdown" name="section_id[]" disabled>
                                                        <option value="">---- Pilih Section ----</option>
                                                        @foreach ($sections as $section)
                                                            <option value="{{ $section->id }}" ${item.section_id == {{ $section->id }} ? 'selected' : ''}>
                                                                {{ $section->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <label>Section</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select job-position-dropdown" name="id_job_position[]" disabled>
                                                        <option value="">---- Pilih Job Position ----</option>
                                                    </select>
                                                    <label>Job Position</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <select class="form-select user-dropdown" name="id_user[]" disabled>
                                                        ${userOptions}
                                                    </select>
                                                    <label>NPK - Nama Karyawan</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="program_training_${item.id}" name="program_training[]" value="${item.program_training}" placeholder="Program Training" disabled>
                                                    <label>Program Training</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="kategori_competency[]" class="form-select competency-category-dropdown" disabled>
                                                        <option value="">---- Pilih Kategori ----</option>
                                                        <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                                        <option value="softskill" ${item.kategori_competency == 'softskill' || item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Soft Skill</option>
                                                        <option value="additional" ${item.kategori_competency == 'additional' ? 'selected' : ''}>Additional</option>
                                                        <option value="Others" ${item.kategori_competency == 'Others' ? 'selected' : ''}>Others</option>
                                                    </select>
                                                    <label>Kategori Competency</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="competency[]" class="form-select competency-dropdown" disabled>
                                                        ${competencyOptions}
                                                    </select>
                                                    <label>Competency</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="date" class="form-control" id="due_date_${item.id}" name="due_date[]" value="${item.due_date}" disabled>
                                                    <label>Due Date</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="biaya_${item.id}" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget" disabled>
                                                    <label>Budget (Rp)</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="lembaga_${item.id}" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga" disabled>
                                                    <label>Lembaga</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="keterangan_tujuan_${item.id}" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan" disabled>
                                                    <label>Keterangan Tujuan</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-secondary">Objective Learning</label>
                                                    <textarea class="form-control" id="objective_learning_${item.id}" name="objective_learning[]" placeholder="Peserta mampu menerapkan............" style="height:80px;" disabled>${item.objective_learning || ''}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KANAN: TINDAK LANJUT AKTUAL -->
                                    <div class="col-lg-6 border-start-lg">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                            <h6 class="text-success m-0"><i class="fas fa-edit me-1"></i> 2. Tindak Lanjut / Aktual</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="copyAllToAktual(${item.id})">
                                                <i class="fas fa-copy"></i> Salin Semua Usulan
                                            </button>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="program_training_plan_${item.id}" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan">
                                                        <label>Nama Program Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'program_training')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="date" class="form-control" id="due_date_plan_${item.id}" name="due_date_plan[]" value="${item.due_date_plan || ''}" min="${activeYear}-01-01" max="${activeYear}-12-31">
                                                        <label>Date Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'due_date')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="biaya_plan_${item.id}" name="biaya_plan[]" value="${item.biaya_plan || ''}" placeholder="Biaya Plan">
                                                        <label>Biaya Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'biaya')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="lembaga_plan_${item.id}" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan">
                                                        <label>Lembaga Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'lembaga')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="keterangan_plan_${item.id}" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan">
                                                        <label>Keterangan Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'keterangan_tujuan')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select id="status_2_${item.id}" name="status_2[]" class="form-select status-dropdown border-${borderClass.replace('border-', '')}" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};">
                                                        <option value=""> ----- Pilih Status ----- </option>
                                                        <option value="Mencari Vendor" ${item.status_2 == 'Mencari Vendor' ? 'selected' : ''}>Mencari Vendor</option>
                                                        <option value="Proses Pendaftaran" ${item.status_2 == 'Proses Pendaftaran' ? 'selected' : ''}>Proses Pendaftaran</option>
                                                        <option value="On Progress" ${item.status_2 == 'On Progress' ? 'selected' : ''}>On progress</option>
                                                        <option value="Done" ${item.status_2 == 'Done' ? 'selected' : ''}>Done</option>
                                                        <option value="Pending" ${item.status_2 == 'Pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="Ditolak" ${item.status_2 == 'Ditolak' ? 'selected' : ''}>Ditolak</option>
                                                    </select>
                                                    <label>Status</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label d-block text-muted small mb-1">Upload File (PDF)</label>
                                                <input type="file" class="form-control form-control-sm" name="file[${item.id}]" accept=".pdf" ${item.status_2 !== 'Done' ? 'disabled' : ''}>
                                            </div>
                                            {{-- Objective Learning Aktual (menggantikan Tindak Lanjut Pasca Training) --}}
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <textarea class="form-control" id="objective_learning_aktual_${item.id}" name="objective_learning_aktual[]" placeholder="Objective Learning Aktual" style="height:80px;">${item.objective_learning_aktual || ''}</textarea>
                                                        <label>Objective Learning Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${item.id}', 'objective_learning')" title="Salin dari Usulan (Objective Learning)"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                                                ${item.file ? `<button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="downloadPdf(${item.id})"><i class="bi bi-filetype-pdf"></i> Download File</button>` : ''}
                                                ${item.status_2 === 'Done' ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn btn-sm rounded-pill ${item.evaluation_completed ? 'btn-success' : 'btn-danger'}"><i class="fas fa-eye"></i> Evaluasi</a>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        tableBody.appendChild(newRow);

                        var sectionDropdown = newRow.querySelector('.section-dropdown');
                        var jobPositionDropdown = newRow.querySelector('.job-position-dropdown');
                        var userDropdown = newRow.querySelector('.user-dropdown');
                        var competencyCategoryDropdown = newRow.querySelector('.competency-category-dropdown');
                        var competencyDropdown = newRow.querySelector('.competency-dropdown');

                        sectionDropdown.addEventListener('change', function() {
                            var selectedSectionId = parseInt(this.value);
                            jobPositionDropdown.innerHTML =
                                '<option value="">---- Pilih Job Position ----</option>';
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            if (!selectedSectionId) return;

                            var uniqueJobs = [];
                            jobPositions.forEach(function(jp) {
                                if (jp.section_id == selectedSectionId && !uniqueJobs.includes(jp.job_position)) {
                                    uniqueJobs.push(jp.job_position);
                                    var option = document.createElement('option');
                                    option.value = jp.id;
                                    option.text = jp.job_position;
                                    jobPositionDropdown.appendChild(option);
                                }
                            });

                            if (item.id_job_position) {
                                jobPositionDropdown.value = item.id_job_position;
                            }
                        });

                        jobPositionDropdown.addEventListener('change', function() {
                            var selectedJobPositionId = parseInt(this.value);
                            var selectedSectionId = parseInt(sectionDropdown.value);
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            if (!selectedJobPositionId) return;

                            var uniqueUserIds = [];
                            jobPositions.forEach(function(jp) {
                                if (jp.section_id == selectedSectionId && jp.id == selectedJobPositionId) {
                                    if (jp.active_users) {
                                        jp.active_users.forEach(function(u) {
                                            if (!uniqueUserIds.includes(u.id)) {
                                                uniqueUserIds.push(u.id);
                                                var option = document.createElement('option');
                                                option.value = u.id;
                                                option.text = employeeLabel(u);
                                                userDropdown.appendChild(option);
                                            }
                                        });
                                    }
                                }
                            });

                            if (item.user) {
                                userDropdown.value = item.user.id;
                            }
                        });

                        // Memastikan data tersimpan ditampilkan ketika halaman dimuat
                        if (item.section_id) {
                            sectionDropdown.value = item.section_id;
                            sectionDropdown.dispatchEvent(new Event('change'));
                            setTimeout(function() {
                                if (item.id_job_position) {
                                    jobPositionDropdown.value = item.id_job_position;
                                    jobPositionDropdown.dispatchEvent(new Event('change'));
                                }
                            }, 50);
                        }
                    }
                });
            });

            var existingEmployeeData = @json($data);
            var availableJobPositions = @json($jobPositions);
            var employeePenilaians = @json($penilaians);

            // view tabel 2
            document.addEventListener('DOMContentLoaded', function() {
                var employeeTableBody = document.getElementById('table-body2');
                const updateEvaluasiRoute = "{{ route('update-evaluasi', ':id') }}";
                // Populate rows with existing data
                existingEmployeeData.forEach(function(item, index) {
                    // Cek apakah tahun_usulan memiliki nilai
                    if (item.tahun_usulan) {
                        var newEmployeeRow = document.createElement('div');
                        newEmployeeRow.id = `row-${item.id}`; // Set a unique ID for each row
                        var borderClass = '';
                        switch (item.status_2) {
                            case 'Done': borderClass = 'border-success'; break;
                            case 'On Progress': borderClass = 'border-warning'; break;
                            case 'Mencari Vendor': borderClass = 'border-info'; break;
                            case 'Proses Pendaftaran': borderClass = 'border-primary'; break;
                            case 'Ditolak': borderClass = 'border-danger'; break;
                            case 'Pending': borderClass = 'border-secondary'; break;
                            default: borderClass = 'border-light'; break;
                        }

                        newEmployeeRow.className = `card mb-4 shadow-sm border-0 border-start border-5 ${borderClass} dynamic-card`;
                        newEmployeeRow.dataset.employeeName = item.user ? `${displayNpk(item.user.npk)} ${item.user.name}`.toLowerCase() : '';
                        newEmployeeRow.dataset.programName = item.program_training ? item.program_training.toLowerCase() : '';
                        newEmployeeRow.dataset.status = item.status_2 || '';

                        var userOptionsList = '<option value="">---- Pilih Karyawan ----</option>';
                        var competencyOptionsList = '<option value="">---- Pilih Competency ----</option>';

                        if (item.user) {
                            userOptionsList +=
                                `<option value="${item.user.id}" selected>${escapeHtml(employeeLabel(item.user))}</option>`;
                        }

                        if (item.competency) {
                            competencyOptionsList +=
                                `<option value="${item.competency}" selected>${item.competency}</option>`;
                        }

                        var headerTitle = escapeHtml(
                            `${item.user ? employeeLabel(item.user) : 'Unknown User'} - ${item.program_training || 'Unknown Program'}`
                        );

                        newEmployeeRow.innerHTML = `
                            <div class="card-header bg-light d-flex justify-content-between align-items-center border-bottom-0 pt-3 pb-0">
                                <h5 class="m-0 fw-bold text-primary"><i class="fas fa-user-graduate me-2"></i>${headerTitle} (Additional)</h5>
                            </div>
                            <div class="card-body">
                                <input type="hidden" name="modified_at" value="${item.modified_at || ''}" />
                                <input type="hidden" name="id[]" value="${item.id}" />
                                
                                <div class="row">
                                    <!-- KIRI: DATA USULAN -->
                                    <div class="col-lg-6 mb-4 mb-lg-0">
                                        <h6 class="text-secondary border-bottom pb-2 mb-3"><i class="fas fa-file-alt me-1"></i> 1. Data Usulan</h6>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <select class="form-select" name="kategori_usulan[]" disabled>
                                                        <option value="0" ${!item.is_sharing_knowledge ? 'selected' : ''}>Training</option>
                                                        <option value="1" ${item.is_sharing_knowledge ? 'selected' : ''}>Sharing Knowledge</option>
                                                    </select>
                                                    <label>Kategori Usulan</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6" style="${item.is_sharing_knowledge ? 'display:none;' : ''}">
                                                <div class="form-floating">
                                                    <select class="form-select employee-section-dropdown" name="section_id[]" disabled>
                                                        <option value="">---- Pilih Section ----</option>
                                                        @foreach ($sections as $section)
                                                            <option value="{{ $section->id }}" ${item.section_id == {{ $section->id }} ? 'selected' : ''}>{{ $section->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <label>Section</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6" style="${item.is_sharing_knowledge ? 'display:none;' : ''}">
                                                <div class="form-floating">
                                                    <select class="form-select employee-job-position-dropdown" name="id_job_position[]" disabled>
                                                        <option value="">---- Pilih Job Position ----</option>
                                                    </select>
                                                    <label>Job Position</label>
                                                </div>
                                            </div>
                                            <div class="col-12 training-user-wrapper" style="${item.is_sharing_knowledge ? 'display:none;' : ''}">
                                                <div class="form-floating">
                                                    <select class="form-select employee-user-dropdown" name="id_user[]" disabled>
                                                        ${userOptionsList}
                                                    </select>
                                                    <label>NPK - Nama Karyawan</label>
                                                </div>
                                            </div>
                                            <div class="col-12 participant-user-wrapper" style="${item.is_sharing_knowledge ? '' : 'display:none;'}">
                                                <label class="form-label fw-semibold">Participant Sharing Knowledge</label>
                                                <select class="form-select participant-user-dropdown" name="participant_user_ids[]" multiple></select>
                                                <div class="form-text">Participant lama yang tidak aktif harus dihapus atau diganti sebelum menyimpan.</div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="program_training_${item.id}" name="program_training[]" value="${item.program_training || ''}" placeholder="Program Training" disabled>
                                                    <label>Program Training</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="kategori_competency[]" class="form-select employee-competency-category-dropdown" disabled>
                                                        <option value="">---- Pilih Kategori ----</option>
                                                        <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                                        <option value="softskill" ${item.kategori_competency == 'softskill' || item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Soft Skill</option>
                                                        <option value="additional" ${item.kategori_competency == 'additional' ? 'selected' : ''}>Additional</option>
                                                        <option value="Others" ${item.kategori_competency == 'Others' ? 'selected' : ''}>Others</option>
                                                    </select>
                                                    <label>Kategori Competency</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select name="competency[]" class="form-select employee-competency-dropdown" disabled>
                                                        ${competencyOptionsList}
                                                    </select>
                                                    <label>Competency</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="date" class="form-control" id="due_date_${item.id}" name="due_date[]" value="${item.due_date || ''}" disabled>
                                                    <label>Due Date</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="biaya_${item.id}" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget" disabled>
                                                    <label>Budget (Rp)</label>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="lembaga_${item.id}" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga" disabled>
                                                    <label>Lembaga</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <input type="text" class="form-control" id="keterangan_tujuan_${item.id}" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan" disabled>
                                                    <label>Keterangan Tujuan</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <textarea class="form-control" id="objective_learning_${item.id}" name="objective_learning[]" placeholder="Peserta mampu menerapkan............" style="height:80px;" disabled>${item.objective_learning || ''}</textarea>
                                                    <label>Objective Learning</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- KANAN: TINDAK LANJUT AKTUAL -->
                                    <div class="col-lg-6 border-start-lg">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                            <h6 class="text-success m-0"><i class="fas fa-edit me-1"></i> 2. Tindak Lanjut / Aktual</h6>
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="copyAllToAktual(${item.id})">
                                                <i class="fas fa-copy"></i> Salin Semua Usulan
                                            </button>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="program_training_plan_${item.id}" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan">
                                                        <label>Nama Program Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'program_training')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="date" class="form-control" id="due_date_plan_${item.id}" name="due_date_plan[]" value="${item.due_date_plan || ''}" min="${activeYear}-01-01" max="${activeYear}-12-31">
                                                        <label>Date Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'due_date')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="biaya_plan_${item.id}" name="biaya_plan[]" value="${item.biaya_plan || ''}" placeholder="Biaya Plan">
                                                        <label>Biaya Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'biaya')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="lembaga_plan_${item.id}" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan">
                                                        <label>Lembaga Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'lembaga')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="input-group">
                                                    <div class="form-floating flex-grow-1">
                                                        <input type="text" class="form-control" id="keterangan_plan_${item.id}" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan">
                                                        <label>Keterangan Aktual</label>
                                                    </div>
                                                    <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual(${item.id}, 'keterangan_tujuan')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select id="status_2_${item.id}" name="status_2[]" class="form-select status-dropdown border-${borderClass.replace('border-', '')}" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};">
                                                        <option value=""> ----- Pilih Status ----- </option>
                                                        <option value="Mencari Vendor" ${item.status_2 == 'Mencari Vendor' ? 'selected' : ''}>Mencari Vendor</option>
                                                        <option value="Proses Pendaftaran" ${item.status_2 == 'Proses Pendaftaran' ? 'selected' : ''}>Proses Pendaftaran</option>
                                                        <option value="On Progress" ${item.status_2 == 'On Progress' ? 'selected' : ''}>On progress</option>
                                                        <option value="Done" ${item.status_2 == 'Done' ? 'selected' : ''}>Done</option>
                                                        <option value="Pending" ${item.status_2 == 'Pending' ? 'selected' : ''}>Pending</option>
                                                        <option value="Ditolak" ${item.status_2 == 'Ditolak' ? 'selected' : ''}>Ditolak</option>
                                                    </select>
                                                    <label>Status</label>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label d-block text-muted small mb-1">Upload File (PDF)</label>
                                                <input type="file" class="form-control form-control-sm" name="file[${item.id}]" accept=".pdf" ${item.status_2 !== 'Done' ? 'disabled' : ''}>
                                            </div>
                                             <div class="col-12">
                                                 <div class="card border-0 bg-light rounded-3 mt-2">
                                                     <div class="card-body p-2">
                                                         <h6 class="small fw-bold text-muted mb-2"><i class="bi bi-lightbulb-fill me-1 text-warning"></i>Tindak Lanjut Pasca Training</h6>
                                                         <div class="row g-2">
                                                             <div class="col-12">
                                                                 <div class="form-floating">
                                                                     <textarea class="form-control form-control-sm" id="objective_learning_aktual_${item.id}" name="objective_learning_aktual[]" placeholder="Objective Learning Aktual" style="height:80px;">${item.objective_learning_aktual || ''}</textarea>
                                                                     <label class="small"><i class="bi bi-people me-1"></i>Sharing Knowledge</label>
                                                                 </div>
                                                             </div>
                                                         </div>
                                                     </div>
                                                 </div>
                                             </div>
                                            <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                                                ${item.file ? `<button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="downloadPdf(${item.id})"><i class="bi bi-filetype-pdf"></i> Download File</button>` : ''}
                                                ${item.status_2 === 'Done' ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn btn-sm rounded-pill ${item.evaluation_completed ? 'btn-success' : 'btn-danger'}"><i class="fas fa-eye"></i> Evaluasi</a>` : ''}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;

                        employeeTableBody.appendChild(newEmployeeRow);

                        var sectionDropdown = newEmployeeRow.querySelector('.employee-section-dropdown');
                        var jobPositionDropdown = newEmployeeRow.querySelector('.employee-job-position-dropdown');
                        var userDropdown = newEmployeeRow.querySelector('.employee-user-dropdown');
                        var participantDropdown = newEmployeeRow.querySelector('.participant-user-dropdown');
                        var participantUsers = Array.isArray(item.participants) && item.participants.length
                            ? item.participants
                            : (item.is_sharing_knowledge && item.user ? [item.user] : []);
                        initializeParticipantSelect(
                            participantDropdown,
                            participantUsers.map((user) => Number(user.id)),
                            participantUsers,
                        );
                        if (participantUsers.length) {
                            newEmployeeRow.dataset.employeeName = participantUsers.map(employeeLabel).join(' ').toLowerCase();
                        }

                        sectionDropdown.addEventListener('change', function() {
                            var selectedSectionId = parseInt(this.value);
                            jobPositionDropdown.innerHTML =
                                '<option value="">---- Pilih Job Position ----</option>';
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            if (!selectedSectionId) return;

                            var uniqueJobs = [];
                            jobPositions.forEach(function(jp) {
                                if (jp.section_id == selectedSectionId && !uniqueJobs.includes(jp.job_position)) {
                                    uniqueJobs.push(jp.job_position);
                                    var option = document.createElement('option');
                                    option.value = jp.id;
                                    option.text = jp.job_position;
                                    jobPositionDropdown.appendChild(option);
                                }
                            });

                            if (item.id_job_position) {
                                jobPositionDropdown.value = item.id_job_position;
                            }
                        });

                        jobPositionDropdown.addEventListener('change', function() {
                            var selectedJobPositionId = parseInt(this.value);
                            var selectedSectionId = parseInt(sectionDropdown.value);
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            if (!selectedJobPositionId) return;

                            var uniqueUserIds = [];
                            jobPositions.forEach(function(jp) {
                                if (jp.section_id == selectedSectionId && jp.id == selectedJobPositionId) {
                                    if (jp.active_users) {
                                        jp.active_users.forEach(function(u) {
                                            if (!uniqueUserIds.includes(u.id)) {
                                                uniqueUserIds.push(u.id);
                                                var option = document.createElement('option');
                                                option.value = u.id;
                                                option.text = employeeLabel(u);
                                                userDropdown.appendChild(option);
                                            }
                                        });
                                    }
                                }
                            });

                            if (item.user) {
                                userDropdown.value = item.user.id;
                            }
                        });

                        if (item.section_id) {
                            sectionDropdown.value = item.section_id;
                            sectionDropdown.dispatchEvent(new Event('change'));
                            setTimeout(function() {
                                if (item.id_job_position) {
                                    jobPositionDropdown.value = item.id_job_position;
                                    jobPositionDropdown.dispatchEvent(new Event('change'));
                                }
                            }, 50);
                        }
                    }
                });

                var additionalCount = 0;
                function addAdditionalRow() {
                    additionalCount++;
                    var tempId = 'new_' + additionalCount + '_' + Date.now();
                    var newEmployeeRow = document.createElement('div');
                    newEmployeeRow.id = `row-${tempId}`;
                    newEmployeeRow.className = `card mb-4 shadow-sm border-0 border-start border-5 border-success dynamic-card`;
                    
                    var sectionOptions = '<option value="">---- Pilih Section ----</option>';
                    @foreach ($sections as $section)
                        sectionOptions += `<option value="{{ $section->id }}">{{ $section->name }}</option>`;
                    @endforeach

                    newEmployeeRow.innerHTML = `
                        <div class="card-header bg-light d-flex justify-content-between align-items-center border-bottom-0 pt-3 pb-0">
                            <h5 class="m-0 fw-bold text-success"><i class="fas fa-plus me-2"></i>Usulan Tambahan Baru (Additional)</h5>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="removeNewRow('${tempId}')"><i class="bi bi-trash"></i> Hapus</button>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="modified_at" value="" />
                            <input type="hidden" name="id[]" value="${tempId}" />
                            
                            <div class="row">
                                <!-- KIRI: DATA USULAN -->
                                <div class="col-lg-6 mb-4 mb-lg-0">
                                    <h6 class="text-secondary border-bottom pb-2 mb-3"><i class="fas fa-file-alt me-1"></i> 1. Data Usulan</h6>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <select class="form-select kategori-usulan-dropdown" name="kategori_usulan[]" id="kategori_usulan_${tempId}" required>
                                                    <option value="0" selected>Training</option>
                                                    <option value="1">Sharing Knowledge</option>
                                                </select>
                                                <label>Kategori Usulan</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="wrapper_section_${tempId}">
                                            <div class="form-floating">
                                                <select class="form-select employee-section-dropdown" name="section_id[]" id="section_id_${tempId}" required>
                                                    ${sectionOptions}
                                                </select>
                                                <label>Section</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6" id="wrapper_job_${tempId}">
                                            <div class="form-floating">
                                                <select class="form-select employee-job-position-dropdown" name="id_job_position[]" id="id_job_position_${tempId}" required>
                                                    <option value="">---- Pilih Job Position ----</option>
                                                </select>
                                                <label>Job Position</label>
                                            </div>
                                        </div>
                                        <div class="col-12 training-user-wrapper" id="wrapper_user_${tempId}">
                                            <div class="form-floating">
                                                <select class="form-select employee-user-dropdown" name="id_user[]" id="id_user_${tempId}" required>
                                                    <option value="">---- Pilih Karyawan ----</option>
                                                </select>
                                                <label>NPK - Nama Karyawan</label>
                                            </div>
                                        </div>
                                        <div class="col-12 participant-user-wrapper" id="wrapper_participants_${tempId}" style="display:none;">
                                            <label class="form-label fw-semibold" for="participant_user_ids_${tempId}">Participant Sharing Knowledge</label>
                                            <select class="form-select participant-user-dropdown" name="participant_user_ids[]" id="participant_user_ids_${tempId}" multiple></select>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="program_training[]" id="program_training_${tempId}" placeholder="Program Training" required>
                                                <label>Program Training</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select name="kategori_competency[]" class="form-select employee-competency-category-dropdown" id="kategori_competency_${tempId}" required>
                                                    <option value="">---- Pilih Kategori ----</option>
                                                    <option value="technical">Technical Competency</option>
                                                    <option value="softskill">Soft Skill</option>
                                                    <option value="additional" selected>Additional</option>
                                                    <option value="Others">Others</option>
                                                </select>
                                                <label>Kategori Competency</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <label style="font-size: 0.85rem; color: rgba(var(--bs-body-color-rgb), .65);">Competency</label>
                                                <select name="competency[]" class="form-select employee-competency-dropdown" id="competency_${tempId}" required style="width: 100%;">
                                                    <option value="additional" selected>Additional</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="date" class="form-control" name="due_date[]" id="due_date_${tempId}" min="${activeYear}-01-01" max="${activeYear}-12-31" required>
                                                <label>Due Date</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="biaya[]" id="biaya_${tempId}" placeholder="Budget" required>
                                                <label>Budget (Rp)</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="lembaga[]" id="lembaga_${tempId}" placeholder="Lembaga" required>
                                                <label>Lembaga</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" name="keterangan_tujuan[]" id="keterangan_tujuan_${tempId}" placeholder="Keterangan Tujuan">
                                                <label>Keterangan Tujuan</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold text-secondary">Objective Learning</label>
                                                <textarea class="form-control" name="objective_learning[]" id="objective_learning_${tempId}" placeholder="Peserta mampu menerapkan............" style="height:80px;"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- KANAN: TINDAK LANJUT AKTUAL -->
                                <div class="col-lg-6 border-start-lg">
                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                        <h6 class="text-success m-0"><i class="fas fa-edit me-1"></i> 2. Tindak Lanjut / Aktual</h6>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill" onclick="copyAllToAktual('${tempId}')">
                                            <i class="fas fa-copy"></i> Salin Semua Usulan
                                        </button>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <div class="input-group">
                                                <div class="form-floating flex-grow-1">
                                                    <input type="text" class="form-control" id="program_training_plan_${tempId}" name="program_training_plan[]" placeholder="Nama Program Plan">
                                                    <label>Nama Program Aktual</label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${tempId}', 'program_training')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <div class="form-floating flex-grow-1">
                                                    <input type="date" class="form-control" id="due_date_plan_${tempId}" name="due_date_plan[]" min="${activeYear}-01-01" max="${activeYear}-12-31">
                                                    <label>Date Aktual</label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${tempId}', 'due_date')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <div class="form-floating flex-grow-1">
                                                    <input type="text" class="form-control" id="biaya_plan_${tempId}" name="biaya_plan[]" placeholder="Biaya Plan">
                                                    <label>Biaya Aktual</label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${tempId}', 'biaya')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="input-group">
                                                <div class="form-floating flex-grow-1">
                                                    <input type="text" class="form-control" id="lembaga_plan_${tempId}" name="lembaga_plan[]" placeholder="Lembaga Plan">
                                                    <label>Lembaga Aktual</label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${tempId}', 'lembaga')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="input-group">
                                                <div class="form-floating flex-grow-1">
                                                    <input type="text" class="form-control" id="keterangan_plan_${tempId}" name="keterangan_plan[]" placeholder="Keterangan Plan">
                                                    <label>Keterangan Aktual</label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${tempId}', 'keterangan_tujuan')" title="Salin dari Usulan"><i class="fas fa-copy"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select id="status_2_${tempId}" name="status_2[]" class="form-select status-dropdown" onchange="updateDropdownColor(this); toggleFileUpload(this);">
                                                    <option value=""> ----- Pilih Status ----- </option>
                                                    <option value="Mencari Vendor">Mencari Vendor</option>
                                                    <option value="Proses Pendaftaran">Proses Pendaftaran</option>
                                                    <option value="On Progress">On progress</option>
                                                    <option value="Done">Done</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="Ditolak">Ditolak</option>
                                                </select>
                                                <label>Status</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label d-block text-muted small mb-1">Upload File (PDF)</label>
                                            <input type="file" class="form-control form-control-sm" name="file[${tempId}]" accept=".pdf" disabled>
                                        </div>
                                        {{-- Objective Learning Aktual (menggantikan Tindak Lanjut Pasca Training) --}}
                                        <div class="col-12">
                                            <div class="input-group">
                                                <div class="form-floating flex-grow-1">
                                                    <textarea class="form-control" id="objective_learning_aktual_${tempId}" name="objective_learning_aktual[]" placeholder="Objective Learning Aktual" style="height:80px;"></textarea>
                                                    <label><i class="bi bi-journal-check me-1"></i>Objective Learning Aktual</label>
                                                </div>
                                                <button type="button" class="btn btn-outline-secondary" onclick="copyToAktual('${tempId}', 'objective_learning')" title="Salin dari Usulan (Objective Learning)"><i class="fas fa-copy"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    `;

                    employeeTableBody.appendChild(newEmployeeRow);

                    var sectionDropdown = newEmployeeRow.querySelector('.employee-section-dropdown');
                    var jobPositionDropdown = newEmployeeRow.querySelector('.employee-job-position-dropdown');
                    var userDropdown = newEmployeeRow.querySelector('.employee-user-dropdown');
                    var participantDropdown = newEmployeeRow.querySelector('.participant-user-dropdown');
                    initializeParticipantSelect(participantDropdown);

                    sectionDropdown.addEventListener('change', function() {
                        var selectedSectionId = parseInt(this.value);
                        jobPositionDropdown.innerHTML = '<option value="">---- Pilih Job Position ----</option>';
                        userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';

                        if (!selectedSectionId) return;

                        var uniqueJobs = [];
                        availableJobPositions.forEach(function(jp) {
                            if (jp.section_id == selectedSectionId && !uniqueJobs.includes(jp.job_position)) {
                                uniqueJobs.push(jp.job_position);
                                var option = document.createElement('option');
                                option.value = jp.id;
                                option.text = jp.job_position;
                                jobPositionDropdown.appendChild(option);
                            }
                        });
                    });

                    jobPositionDropdown.addEventListener('change', function() {
                        var selectedJobPositionId = parseInt(this.value);
                        var selectedSectionId = parseInt(sectionDropdown.value);
                        userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';

                        if (!selectedJobPositionId) return;

                        var uniqueUserIds = [];
                        availableJobPositions.forEach(function(jp) {
                            if (jp.section_id == selectedSectionId && jp.id == selectedJobPositionId) {
                                if (jp.active_users) {
                                    jp.active_users.forEach(function(u) {
                                        if (!uniqueUserIds.includes(u.id)) {
                                            uniqueUserIds.push(u.id);
                                            var option = document.createElement('option');
                                            option.value = u.id;
                                            option.text = employeeLabel(u);
                                            userDropdown.appendChild(option);
                                        }
                                    });
                                }
                            }
                        });
                    });

                    var kategoriUsulanDropdown = newEmployeeRow.querySelector('.kategori-usulan-dropdown');
                    var wrapperSection = newEmployeeRow.querySelector('#wrapper_section_' + tempId);
                    var wrapperJob = newEmployeeRow.querySelector('#wrapper_job_' + tempId);
                    var wrapperUser = newEmployeeRow.querySelector('#wrapper_user_' + tempId);
                    var wrapperParticipants = newEmployeeRow.querySelector('#wrapper_participants_' + tempId);

                    kategoriUsulanDropdown.addEventListener('change', function() {
                        if (this.value == '1') {
                            // Sharing Knowledge
                            wrapperSection.style.display = 'none';
                            wrapperJob.style.display = 'none';
                            wrapperUser.style.display = 'none';
                            wrapperParticipants.style.display = '';
                            sectionDropdown.removeAttribute('required');
                            jobPositionDropdown.removeAttribute('required');
                            sectionDropdown.value = '';
                            jobPositionDropdown.value = '';
                            userDropdown.removeAttribute('required');
                            
                            // Populate users with ALL active users
                            userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                            var uniqueUserIds = [];
                            jobPositions.forEach(function(jp) {
                                if (jp.active_users) {
                                    jp.active_users.forEach(function(u) {
                                        if (!uniqueUserIds.includes(u.id)) {
                                            uniqueUserIds.push(u.id);
                                            var option = document.createElement('option');
                                            option.value = u.id;
                                            option.text = employeeLabel(u);
                                            userDropdown.appendChild(option);
                                        }
                                    });
                                }
                            });
                        } else {
                            // Training
                            wrapperSection.style.display = '';
                            wrapperJob.style.display = '';
                            wrapperUser.style.display = '';
                            wrapperParticipants.style.display = 'none';
                            sectionDropdown.setAttribute('required', 'required');
                            jobPositionDropdown.setAttribute('required', 'required');
                            userDropdown.setAttribute('required', 'required');
                            if (participantDropdown.tomselect) participantDropdown.tomselect.clear();
                            
                            // Reset users because section & job are empty now
                            userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                        }
                    });

                    var kategoriCompDropdown = newEmployeeRow.querySelector('.employee-competency-category-dropdown');
                    var compDropdown = newEmployeeRow.querySelector('.employee-competency-dropdown');

                    kategoriCompDropdown.addEventListener('change', function() {
                        var selectedKategori = this.value;
                        
                        // If TomSelect is initialized, we must clear options and add new ones via its API
                        if (compDropdown.tomselect) {
                            compDropdown.tomselect.clearOptions();
                            compDropdown.tomselect.addOption({value: "", text: "---- Pilih Competency ----"});
                            if (selectedKategori && masterCompetencies[selectedKategori]) {
                                masterCompetencies[selectedKategori].forEach(function(comp) {
                                    compDropdown.tomselect.addOption({value: comp, text: comp});
                                });
                            }
                            compDropdown.tomselect.refreshOptions(false);
                        } else {
                            compDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                            if (selectedKategori && masterCompetencies[selectedKategori]) {
                                masterCompetencies[selectedKategori].forEach(function(comp) {
                                    var option = document.createElement('option');
                                    option.value = comp;
                                    option.text = comp;
                                    compDropdown.appendChild(option);
                                });
                            }
                        }
                    });

                    // Trigger change to populate default 'additional' competencies
                    kategoriCompDropdown.dispatchEvent(new Event('change'));

                    // Initialize TomSelect on the new competency dropdown
                    new TomSelect(compDropdown, {
                        create: false,
                        sortField: {
                            field: "text",
                            direction: "asc"
                        },
                        placeholder: "---- Pilih Competency ----"
                    });
                }
                
                window.addAdditionalRow = addAdditionalRow; // Expose globally

                var addAdditionalBtn = document.getElementById('add-additional-btn');
                if (addAdditionalBtn) {
                    addAdditionalBtn.addEventListener('click', function() {
                        addAdditionalRow();
                    });
                }
            });


            // ====================================================================
            // REVISI 07: Tabel Ringkasan (Table View Toggle)
            // ====================================================================
            function getStatusBadge(status) {
                var colorMap = {
                    'Mencari Vendor': 'background:#1d6ae5;color:#fff;',
                    'Proses Pendaftaran': 'background:#fd7e14;color:#fff;',
                    'On Progress': 'background:#ffc107;color:#212529;',
                    'Done': 'background:#198754;color:#fff;',
                    'Pending': 'background:#6c757d;color:#fff;',
                    'Ditolak': 'background:#dc3545;color:#fff;',
                };
                var style = colorMap[status] || 'background:#dee2e6;color:#212529;';
                return '<span style="display:inline-block;padding:3px 10px;border-radius:5px;font-size:12px;' + style + '">' + (status || '-') + '</span>';
            }

            function formatRupiah(val) {
                if (!val) return '-';
                var num = parseFloat(String(val).replace(/[^0-9.-]/g, ''));
                if (isNaN(num)) return '-';
                return 'Rp ' + num.toLocaleString('id-ID');
            }

            window.syncToCard = function(id, fieldName, value) {
                var input = document.getElementById(fieldName + '_' + id);
                if (input) {
                    input.value = value;
                    if (fieldName === 'status_2') {
                        updateDropdownColor(input);
                        toggleFileUpload(input);
                    }
                }
            };

            function selectedValuesFromSelect(select) {
                if (!select) return [];
                const value = select.tomselect
                    ? select.tomselect.getValue()
                    : Array.from(select.selectedOptions || []).map((option) => option.value);

                return (Array.isArray(value) ? value : (value ? [value] : []))
                    .map(String)
                    .filter(Boolean);
            }

            function additionalCardFor(id) {
                return document.getElementById('row-' + id);
            }

            function syncInlineParticipantsToCard(id, selectedIds) {
                const card = additionalCardFor(id);
                const cardSelect = card?.querySelector('select[name="participant_user_ids[]"]');
                const normalizedIds = [...new Set((selectedIds || []).map(String).filter(Boolean))];

                if (!cardSelect) return;

                if (cardSelect.tomselect) {
                    cardSelect.tomselect.setValue(normalizedIds, true);
                } else {
                    Array.from(cardSelect.options).forEach((option) => {
                        option.selected = normalizedIds.includes(String(option.value));
                    });
                }

                const candidates = participantCandidates();
                card.dataset.employeeName = normalizedIds
                    .map((participantId) => candidates.get(Number(participantId)))
                    .filter(Boolean)
                    .map(employeeLabel)
                    .join(' ')
                    .toLowerCase();
            }

            function renderInlineEmployeeControl(id, isSharing) {
                const cell = document.getElementById('tbl_employee_td_' + id);
                const card = additionalCardFor(id);

                if (!cell || !card) return;

                const currentParticipantSelect = cell.querySelector('select[id^="tbl_participants_"]');
                if (currentParticipantSelect?.tomselect) {
                    currentParticipantSelect.tomselect.destroy();
                }

                cell.replaceChildren();

                if (isSharing) {
                    const wrapper = document.createElement('div');
                    const participantSelect = document.createElement('select');
                    const helper = document.createElement('div');
                    const cardParticipantSelect = card.querySelector('select[name="participant_user_ids[]"]');

                    wrapper.className = 'training-followup-inline-participant';
                    participantSelect.id = 'tbl_participants_' + id;
                    participantSelect.className = 'form-select form-select-sm';
                    participantSelect.multiple = true;
                    participantSelect.setAttribute('aria-label', 'Participant Sharing Knowledge');
                    helper.className = 'form-text';
                    helper.textContent = 'Pilih minimal satu participant.';

                    wrapper.append(participantSelect, helper);
                    cell.appendChild(wrapper);

                    initializeParticipantSelect(
                        participantSelect,
                        selectedValuesFromSelect(cardParticipantSelect),
                        [],
                        (values) => syncInlineParticipantsToCard(id, values),
                    );

                    return;
                }

                const sectionValue = card.querySelector('select[name="section_id[]"]')?.value || '';
                const jobPositionValue = card.querySelector('select[name="id_job_position[]"]')?.value || '';
                const userValue = card.querySelector('select[name="id_user[]"]')?.value || '';
                const userSelect = document.createElement('select');

                userSelect.id = 'tbl_user_' + id;
                userSelect.className = 'form-select form-select-sm';
                userSelect.setAttribute('aria-label', 'NPK dan nama karyawan');
                userSelect.innerHTML = buildUserOptions(sectionValue, jobPositionValue, userValue, false);
                userSelect.addEventListener('change', function() {
                    syncToCard(id, 'id_user', this.value);
                });
                cell.appendChild(userSelect);
            }

            function destroyInlineParticipantSelects(container) {
                container.querySelectorAll('select[id^="tbl_participants_"]').forEach((select) => {
                    if (select.tomselect) select.tomselect.destroy();
                });
            }

            function renderSummaryTable() {
                var tbody = document.getElementById('summary-table-body');
                if (!tbody) return;

                destroyInlineParticipantSelects(tbody);

                var html = '';
                var no = 0;
                
                // --- First Group: Table 1 (Usulan Biasa) ---
                var cardsTable1 = $('#table-body .dynamic-card');
                cardsTable1.each(function() {
                    no++;
                    var card = $(this);
                    var id = card.find('input[name="id[]"]').val();
                    
                    var sectionName = card.find('select[name="section_id[]"] option:selected').text().trim() || '-';
                    if (sectionName.includes('---- Pilih')) sectionName = '-';
                    
                    var jobPosName = card.find('select[name="id_job_position[]"] option:selected').text().trim() || '-';
                    if (jobPosName.includes('---- Pilih')) jobPosName = '-';
                    
                    var userName = card.find('select[name="id_user[]"] option:selected').text().trim() || '-';
                    if (userName.includes('---- Pilih')) userName = '-';
                    
                    var progTrain = card.find('input[name="program_training[]"]').val() || '-';
                    
                    var katComp = card.find('select[name="kategori_competency[]"] option:selected').text().trim() || '-';
                    if (katComp.includes('---- Pilih')) katComp = '-';
                    
                    var comp = card.find('select[name="competency[]"] option:selected').text().trim() || '-';
                    if (comp.includes('---- Pilih')) comp = '-';
                    
                    var dueDate = card.find('input[name="due_date[]"]').val() || '-';
                    var biaya = card.find('input[name="biaya[]"]').val() || '';
                    var lembaga = card.find('input[name="lembaga[]"]').val() || '-';
                    var ketTujuan = card.find('input[name="keterangan_tujuan[]"]').val() || '-';
                    var objLearning = card.find('textarea[name="objective_learning[]"]').val() || '-';
                    
                    var planProg = card.find('input[name="program_training_plan[]"]').val() || '';
                    var planDate = card.find('input[name="due_date_plan[]"]').val() || '';
                    var planBiaya = card.find('input[name="biaya_plan[]"]').val() || '';
                    var planLembaga = card.find('input[name="lembaga_plan[]"]').val() || '';
                    var planKet = card.find('input[name="keterangan_plan[]"]').val() || '';
                    var planObjLearning = card.find('textarea[name="objective_learning_aktual[]"]').val() || '';
                    var planStatus = card.find('select[name="status_2[]"]').val() || '';

                    // For Table 1, it's always Training (unless there is a select input)
                    var katUsulanSel = card.find('select[name="kategori_usulan[]"]');
                    var katUsulan = katUsulanSel.length > 0 ? (katUsulanSel.find('option:selected').text().trim() || 'Training') : 'Training';

                    html += '<tr>';
                    html += '<td>' + no + '</td>';
                    html += '<td><span class="badge bg-secondary">' + katUsulan + '</span></td>';
                    html += '<td>' + sectionName + '</td>';
                    html += '<td>' + jobPosName + '</td>';
                    html += '<td>' + userName + '</td>';
                    html += '<td>' + progTrain + '</td>';
                    html += '<td>' + katComp + '</td>';
                    html += '<td>' + comp + '</td>';
                    html += '<td>' + dueDate + '</td>';
                    html += '<td>' + formatRupiah(biaya) + '</td>';
                    html += '<td>' + lembaga + '</td>';
                    html += '<td style="max-width:150px;white-space:pre-wrap;font-size:12px;">' + ketTujuan + '</td>';
                    html += '<td style="max-width:150px;white-space:pre-wrap;font-size:12px;">' + objLearning + '</td>';
                    
                    // Aktual (Editable Inputs) — col pertama include tombol Salin
                    html += '<td class="col-aktual-start"><div class="input-group input-group-sm">' +
                            '<input type="text" class="form-control form-control-sm" value="' + planProg.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'program_training_plan\', this.value)">' +
                            '<button type="button" class="btn btn-outline-secondary btn-sm px-2" onclick="copyAllToAktualTable(\'' + id + '\')" title="Salin semua data Usulan ke Aktual"><i class=\"fas fa-copy\"></i></button>' +
                            '</div></td>';
                    html += '<td><input type="date" class="form-control form-control-sm" value="' + planDate + '" oninput="syncToCard(\'' + id + '\', \'due_date_plan\', this.value)"></td>';
                    html += '<td><input type="text" class="form-control form-control-sm" value="' + planBiaya.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'biaya_plan\', this.value)"></td>';
                    html += '<td><input type="text" class="form-control form-control-sm" value="' + planLembaga.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'lembaga_plan\', this.value)"></td>';
                    html += '<td><input type="text" class="form-control form-control-sm" value="' + planKet.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'keterangan_plan\', this.value)"></td>';
                    html += '<td><input type="text" class="form-control form-control-sm" value="' + planObjLearning.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'objective_learning_aktual\', this.value)"></td>';
                    
                    html += '<td>' + 
                            '<select class="form-select form-select-sm" onchange="syncToCard(\'' + id + '\', \'status_2\', this.value)">' +
                            '<option value="">-</option>' +
                            '<option value="Mencari Vendor" ' + (planStatus === 'Mencari Vendor' ? 'selected' : '') + '>Mencari Vendor</option>' +
                            '<option value="Proses Pendaftaran" ' + (planStatus === 'Proses Pendaftaran' ? 'selected' : '') + '>Proses Pendaftaran</option>' +
                            '<option value="On Progress" ' + (planStatus === 'On Progress' ? 'selected' : '') + '>On progress</option>' +
                            '<option value="Done" ' + (planStatus === 'Done' ? 'selected' : '') + '>Done</option>' +
                            '<option value="Pending" ' + (planStatus === 'Pending' ? 'selected' : '') + '>Pending</option>' +
                            '<option value="Ditolak" ' + (planStatus === 'Ditolak' ? 'selected' : '') + '>Ditolak</option>' +
                            '</select>' +
                            '</td>';
                    
                    // Tombol Jump
                    html += '<td><button type="button" class="btn btn-sm btn-outline-info" onclick="jumpToCard(\'row-' + id + '\')" title="Buka Detail di Card (Upload & Sharing)"><i class="bi bi-card-heading"></i></button></td>';
                    html += '</tr>';
                });
                
                // --- Second Group: Table 2 (Additional Rows) ---
                var hasAdditionalDivider = false;
                var cardsTable2 = $('#table-body2 .dynamic-card');
                var newRowIds = []; // Track IDs yang perlu di-attach cascading logic
                
                cardsTable2.each(function() {
                    if (!hasAdditionalDivider) {
                        hasAdditionalDivider = true;
                        html += '<tr style="background:#e8f5e9;">' +
                                '<td colspan="21" style="font-weight:bold;padding:8px 15px;">' +
                                '<i class="fas fa-plus-circle me-1 text-success"></i> ADDITIONAL' +
                                '</td></tr>';
                        no = 0;
                    }
                    no++;
                    
                    var card = $(this);
                    var id = card.find('input[name="id[]"]').val();
                    var isNewRow = String(id).indexOf('new_') === 0;
                    
                    // Ambil values dari card (sumber kebenaran)
                    var sectionVal = card.find('select[name="section_id[]"]').val() || '';
                    var sectionName = card.find('select[name="section_id[]"] option:selected').text().trim() || '-';
                    if (sectionName.includes('---- Pilih')) sectionName = '-';
                    
                    var jobPosVal = card.find('select[name="id_job_position[]"]').val() || '';
                    var jobPosName = card.find('select[name="id_job_position[]"] option:selected').text().trim() || '-';
                    if (jobPosName.includes('---- Pilih')) jobPosName = '-';
                    
                    var userVal = card.find('select[name="id_user[]"]').val() || '';
                    var userName = card.find('select[name="id_user[]"] option:selected').text().trim() || '-';
                    if (userName.includes('---- Pilih')) userName = '-';
                    
                    var progTrain = card.find('input[name="program_training[]"]').val() || '';
                    
                    var katCompVal = card.find('select[name="kategori_competency[]"]').val() || '';
                    var katComp = card.find('select[name="kategori_competency[]"] option:selected').text().trim() || '-';
                    if (katComp.includes('---- Pilih')) katComp = '-';
                    
                    var compVal = card.find('select[name="competency[]"]').val() || '';
                    var comp = card.find('select[name="competency[]"] option:selected').text().trim() || '-';
                    if (comp.includes('---- Pilih')) comp = '-';
                    
                    var dueDate = card.find('input[name="due_date[]"]').val() || '';
                    var biaya = card.find('input[name="biaya[]"]').val() || '';
                    var lembaga = card.find('input[name="lembaga[]"]').val() || '';
                    var ketTujuan = card.find('input[name="keterangan_tujuan[]"]').val() || '';
                    var objLearning = card.find('textarea[name="objective_learning[]"]').val() || '';
                    
                    var planProg = card.find('input[name="program_training_plan[]"]').val() || '';
                    var planDate = card.find('input[name="due_date_plan[]"]').val() || '';
                    var planBiaya = card.find('input[name="biaya_plan[]"]').val() || '';
                    var planLembaga = card.find('input[name="lembaga_plan[]"]').val() || '';
                    var planKet = card.find('input[name="keterangan_plan[]"]').val() || '';
                    var planObjLearning = card.find('textarea[name="objective_learning_aktual[]"]').val() || '';
                    var planStatus = card.find('select[name="status_2[]"]').val() || '';

                    var katUsulanSel = card.find('select[name="kategori_usulan[]"]');
                    var katUsulanVal = katUsulanSel.length > 0 ? katUsulanSel.val() : '0';
                    var katUsulan = katUsulanSel.length > 0 ? (katUsulanSel.find('option:selected').text().trim() || 'Training') : 'Training';
                    var isSharing = (katUsulanVal == '1');
                    if (isSharing) {
                        const participantNames = card.find('select[name="participant_user_ids[]"] option:selected')
                            .map(function() { return $(this).text().trim(); })
                            .get();
                        userName = participantNames.join(', ') || '-';
                    }

                    if (isNewRow) {
                        // ====== INLINE EDITABLE ROW ======
                        newRowIds.push(id);
                        html += '<tr style="background:#fffde7;">';
                        html += '<td>' + no + '</td>';

                        // Kategori Usulan (inline dropdown)
                        html += '<td><select id="tbl_kat_usulan_' + id + '" class="form-select form-select-sm" aria-label="Kategori Usulan">' +
                                '<option value="0"' + (katUsulanVal == '0' ? ' selected' : '') + '>Training</option>' +
                                '<option value="1"' + (katUsulanVal == '1' ? ' selected' : '') + '>Sharing Knowledge</option>' +
                                '</select></td>';
                        
                        // Section (inline dropdown)
                        html += '<td id="tbl_section_td_' + id + '">' +
                                (isSharing
                                    ? '<span class="text-muted">-</span>'
                                    : '<select id="tbl_section_' + id + '" class="form-select form-select-sm" onchange="handleInlineSectionChange(\'' + id + '\')">' + buildSectionOptions(sectionVal) + '</select>') +
                                '</td>';
                        
                        // Job Position (inline dropdown)
                        html += '<td id="tbl_job_td_' + id + '">' +
                                (isSharing
                                    ? '<span class="text-muted">-</span>'
                                    : '<select id="tbl_job_pos_' + id + '" class="form-select form-select-sm" onchange="handleInlineJobChange(\'' + id + '\')">' + buildJobPositionOptions(sectionVal, jobPosVal) + '</select>') +
                                '</td>';
                        
                        // Karyawan tunggal atau multi-participant diinisialisasi setelah row masuk ke DOM.
                        html += '<td id="tbl_employee_td_' + id + '" class="training-followup-employee-cell"></td>';
                        
                        // Program Training (inline input)
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + (progTrain || '').replace(/"/g, '&quot;') + '" placeholder="Program Training" oninput="syncToCard(\'' + id + '\', \'program_training\', this.value)"></td>';
                        
                        // Kategori Competency (inline dropdown)
                        html += '<td><select id="tbl_kat_comp_' + id + '" class="form-select form-select-sm">' +
                                '<option value="">-- Pilih --</option>' +
                                '<option value="technical"' + (katCompVal === 'technical' ? ' selected' : '') + '>Technical Competency</option>' +
                                '<option value="softskill"' + (katCompVal === 'softskill' ? ' selected' : '') + '>Soft Skill</option>' +
                                '<option value="additional"' + (katCompVal === 'additional' ? ' selected' : '') + '>Additional</option>' +
                                '<option value="Others"' + (katCompVal === 'Others' ? ' selected' : '') + '>Others</option>' +
                                '</select></td>';
                        
                        // Competency (inline dropdown)
                        html += '<td><select id="tbl_comp_' + id + '" class="form-select form-select-sm">' + buildCompetencyOptions(katCompVal, compVal) + '</select></td>';
                        
                        // Due Date (inline input)
                        html += '<td><input type="date" class="form-control form-control-sm" value="' + dueDate + '" min="' + activeYear + '-01-01" max="' + activeYear + '-12-31" oninput="syncToCard(\'' + id + '\', \'due_date\', this.value)"></td>';
                        
                        // Biaya (inline input)
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + (biaya || '').replace(/"/g, '&quot;') + '" placeholder="Budget" oninput="syncToCard(\'' + id + '\', \'biaya\', this.value)"></td>';
                        
                        // Lembaga (inline input)
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + (lembaga || '').replace(/"/g, '&quot;') + '" placeholder="Lembaga" oninput="syncToCard(\'' + id + '\', \'lembaga\', this.value)"></td>';
                        
                        // Keterangan Tujuan (inline input)
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + (ketTujuan || '').replace(/"/g, '&quot;') + '" placeholder="Keterangan Tujuan" oninput="syncToCard(\'' + id + '\', \'keterangan_tujuan\', this.value)" style="min-width:120px;"></td>';
                        
                        // Objective Learning (inline input)
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + (objLearning || '').replace(/"/g, '&quot;') + '" placeholder="Objective" oninput="syncToCard(\'' + id + '\', \'objective_learning\', this.value)" style="min-width:120px;"></td>';
                        
                        // Aktual (Editable Inputs) — col pertama include tombol Salin
                        html += '<td class="col-aktual-start"><div class="input-group input-group-sm">' +
                                '<input type="text" class="form-control form-control-sm" value="' + planProg.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'program_training_plan\', this.value)">' +
                                '<button type="button" class="btn btn-outline-secondary btn-sm px-2" onclick="copyAllToAktualTable(\'' + id + '\')" title="Salin semua data Usulan ke Aktual"><i class=\"fas fa-copy\"></i></button>' +
                                '</div></td>';
                        html += '<td><input type="date" class="form-control form-control-sm" value="' + planDate + '" oninput="syncToCard(\'' + id + '\', \'due_date_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planBiaya.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'biaya_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planLembaga.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'lembaga_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planKet.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'keterangan_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planObjLearning.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'objective_learning_aktual\', this.value)"></td>';
                        
                        html += '<td>' + 
                                '<select class="form-select form-select-sm" onchange="syncToCard(\'' + id + '\', \'status_2\', this.value)">' +
                                '<option value="">-</option>' +
                                '<option value="Mencari Vendor" ' + (planStatus === 'Mencari Vendor' ? 'selected' : '') + '>Mencari Vendor</option>' +
                                '<option value="Proses Pendaftaran" ' + (planStatus === 'Proses Pendaftaran' ? 'selected' : '') + '>Proses Pendaftaran</option>' +
                                '<option value="On Progress" ' + (planStatus === 'On Progress' ? 'selected' : '') + '>On progress</option>' +
                                '<option value="Done" ' + (planStatus === 'Done' ? 'selected' : '') + '>Done</option>' +
                                '<option value="Pending" ' + (planStatus === 'Pending' ? 'selected' : '') + '>Pending</option>' +
                                '<option value="Ditolak" ' + (planStatus === 'Ditolak' ? 'selected' : '') + '>Ditolak</option>' +
                                '</select>' +
                                '</td>';
                        
                        // Aksi (Jump to card + Hapus)
                        html += '<td>' + 
                                '<button type="button" class="btn btn-sm btn-outline-info me-1" onclick="jumpToCard(\'row-' + id + '\')" title="Buka Detail di Card"><i class="bi bi-card-heading"></i></button>' +
                                '<button type="button" class="btn btn-sm btn-outline-danger" onclick="removeNewRow(\'' + id + '\')" title="Hapus Baris"><i class="bi bi-trash"></i></button>' +
                                '</td>';
                        html += '</tr>';
                        
                    } else {
                        // ====== READ-ONLY ROW (existing saved data) ======
                        var katUsulanBadge = katUsulan.includes('Sharing') ? 'bg-primary' : 'bg-secondary';
                        
                        html += '<tr>';
                        html += '<td>' + no + '</td>';
                        html += '<td><span class="badge ' + katUsulanBadge + '">' + katUsulan + '</span></td>';
                        html += '<td>' + sectionName + '</td>';
                        html += '<td>' + jobPosName + '</td>';
                        html += '<td>' + userName + '</td>';
                        html += '<td>' + (progTrain || '-') + '</td>';
                        html += '<td>' + katComp + '</td>';
                        html += '<td>' + comp + '</td>';
                        html += '<td>' + (dueDate || '-') + '</td>';
                        html += '<td>' + formatRupiah(biaya) + '</td>';
                        html += '<td>' + (lembaga || '-') + '</td>';
                        html += '<td style="max-width:150px;white-space:pre-wrap;font-size:12px;">' + (ketTujuan || '-') + '</td>';
                        html += '<td style="max-width:150px;white-space:pre-wrap;font-size:12px;">' + (objLearning || '-') + '</td>';
                        
                        // Aktual (Editable Inputs) — col pertama include tombol Salin
                        html += '<td class="col-aktual-start"><div class="input-group input-group-sm">' +
                                '<input type="text" class="form-control form-control-sm" value="' + planProg.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'program_training_plan\', this.value)">' +
                                '<button type="button" class="btn btn-outline-secondary btn-sm px-2" onclick="copyAllToAktualTable(\'' + id + '\')" title="Salin semua data Usulan ke Aktual"><i class=\"fas fa-copy\"></i></button>' +
                                '</div></td>';
                        html += '<td><input type="date" class="form-control form-control-sm" value="' + planDate + '" oninput="syncToCard(\'' + id + '\', \'due_date_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planBiaya.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'biaya_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planLembaga.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'lembaga_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planKet.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'keterangan_plan\', this.value)"></td>';
                        html += '<td><input type="text" class="form-control form-control-sm" value="' + planObjLearning.replace(/"/g, '&quot;') + '" oninput="syncToCard(\'' + id + '\', \'objective_learning_aktual\', this.value)"></td>';
                        
                        html += '<td>' + 
                                '<select class="form-select form-select-sm" onchange="syncToCard(\'' + id + '\', \'status_2\', this.value)">' +
                                '<option value="">-</option>' +
                                '<option value="Mencari Vendor" ' + (planStatus === 'Mencari Vendor' ? 'selected' : '') + '>Mencari Vendor</option>' +
                                '<option value="Proses Pendaftaran" ' + (planStatus === 'Proses Pendaftaran' ? 'selected' : '') + '>Proses Pendaftaran</option>' +
                                '<option value="On Progress" ' + (planStatus === 'On Progress' ? 'selected' : '') + '>On progress</option>' +
                                '<option value="Done" ' + (planStatus === 'Done' ? 'selected' : '') + '>Done</option>' +
                                '<option value="Pending" ' + (planStatus === 'Pending' ? 'selected' : '') + '>Pending</option>' +
                                '<option value="Ditolak" ' + (planStatus === 'Ditolak' ? 'selected' : '') + '>Ditolak</option>' +
                                '</select>' +
                                '</td>';
                        
                        // Tombol Jump
                        html += '<td><button type="button" class="btn btn-sm btn-outline-info" onclick="jumpToCard(\'row-' + id + '\')" title="Buka Detail di Card (Upload & Sharing)"><i class="bi bi-card-heading"></i></button></td>';
                        html += '</tr>';
                    }
                });

                if (html === '') {
                    html = '<tr><td colspan="21" class="text-center text-muted py-4">Belum ada data pengajuan.</td></tr>';
                }

                tbody.innerHTML = html;
                
                // Attach cascading logic untuk baris baru
                newRowIds.forEach(function(rowId) {
                    attachInlineCascadingLogic(rowId);
                });
            }

            function jumpToCard(rowId) {
                // Switch ke Card view
                switchToCardView();
                // Scroll ke card tersebut
                setTimeout(function() {
                    var el = document.getElementById(rowId);
                    if (el) {
                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        el.classList.add('border-warning');
                        setTimeout(function() { el.classList.remove('border-warning'); }, 2000);
                    }
                }, 300);
            }

            // Fungsi untuk menghapus baris additional yang baru (sebelum di-save)
            window.removeNewRow = function(id) {
                Swal.fire({
                    title: 'Apakah Anda yakin ingin menghapus baris usulan tambahan ini?',
                    text: 'Tindakan ini tidak dapat dibatalkan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        var el = document.getElementById('row-' + id);
                        if (el) {
                            el.remove();
                            // Re-render table agar baris tersebut hilang dari tampilan tabel
                            if (document.getElementById('table-summary-section').style.display !== 'none') {
                                renderSummaryTable();
                            }
                        }
                    }
                });
            };

            function switchToTableView() {
                document.getElementById('table-summary-section').style.display = 'block';
                document.getElementById('card-view-section').style.display = 'none';
                document.getElementById('btn-table-view').classList.remove('btn-outline-secondary');
                document.getElementById('btn-table-view').classList.add('btn-primary');
                document.getElementById('btn-card-view').classList.remove('btn-primary');
                document.getElementById('btn-card-view').classList.add('btn-outline-secondary');
                renderSummaryTable();
            }

            function switchToCardView() {
                document.getElementById('table-summary-section').style.display = 'none';
                document.getElementById('card-view-section').style.display = 'block';
                document.getElementById('btn-card-view').classList.remove('btn-outline-secondary');
                document.getElementById('btn-card-view').classList.add('btn-primary');
                document.getElementById('btn-table-view').classList.remove('btn-primary');
                document.getElementById('btn-table-view').classList.add('btn-outline-secondary');
            }

            document.getElementById('btn-table-view').addEventListener('click', switchToTableView);
            document.getElementById('btn-card-view').addEventListener('click', switchToCardView);


            // ====================================================================
            // Fungsi untuk mengumpulkan semua data form dari kedua tabel
            // ====================================================================
            function hasMeaningfulFollowUpInput(row) {
                const participantIds = Array.isArray(row.participant_user_ids)
                    ? row.participant_user_ids
                    : [];
                const hasIdentity = Boolean(String(row.id_user || '').trim())
                    || participantIds.some((value) => Boolean(String(value || '').trim()));

                if (hasIdentity) return true;

                const hasContent = [
                    row.program_training,
                    row.program_training_plan,
                    row.competency,
                    row.due_date,
                    row.due_date_plan,
                    row.lembaga,
                    row.lembaga_plan,
                    row.keterangan_tujuan,
                    row.keterangan_plan,
                    row.objective_learning,
                    row.objective_learning_aktual,
                    row.status_2,
                ].some((value) => Boolean(String(value || '').trim()));

                if (hasContent) return true;

                return [row.biaya, row.biaya_plan].some((value) => {
                    const normalized = String(value || '').replace(/[^0-9.-]/g, '');
                    const numericValue = Number(normalized);
                    return normalized !== '' && Number.isFinite(numericValue) && numericValue !== 0;
                });
            }

            function shouldIgnoreInertFollowUpRow(row) {
                const rowId = String(row.id || '');
                const card = document.getElementById('row-' + rowId);
                const hasSelectedFile = Boolean(
                    card?.querySelector('input[type="file"]')?.files?.length,
                );
                const hasMeaningfulInput = hasMeaningfulFollowUpInput(row) || hasSelectedFile;

                if (rowId.startsWith('new_')) {
                    return !hasMeaningfulInput;
                }

                return row.is_sharing_knowledge === true && !hasMeaningfulInput;
            }

            function collectFormData() {
                var formData = [];

                // Get data from first table (table-body)
                $('#table-body .dynamic-card').each(function() {
                    var row = $(this);
                    formData.push({
                        id: row.find('input[name="id[]"]').val(),
                        section_id: row.find('select[name="section_id[]"]').val() || null,
                        id_job_position: row.find('select[name="id_job_position[]"]').val() || null,
                        id_user: row.find('select[name="id_user[]"]').val() || null,
                        participant_user_ids: [],
                        is_sharing_knowledge: false,
                        program_training: row.find('input[name="program_training[]"]').val() || '',
                        kategori_competency: row.find('select[name="kategori_competency[]"]').val() || null,
                        competency: row.find('select[name="competency[]"]').val() || null,
                        due_date: row.find('input[name="due_date[]"]').val().trim(),
                        biaya: row.find('input[name="biaya[]"]').val().trim() || '0',
                        lembaga: row.find('input[name="lembaga[]"]').val().trim(),
                        keterangan_tujuan: row.find('input[name="keterangan_tujuan[]"]').val().trim(),
                        program_training_plan: row.find('input[name="program_training_plan[]"]').val().trim(),
                        due_date_plan: row.find('input[name="due_date_plan[]"]').val().trim(),
                        biaya_plan: row.find('input[name="biaya_plan[]"]').val().trim() || '0',
                        lembaga_plan: row.find('input[name="lembaga_plan[]"]').val().trim(),
                        keterangan_plan: row.find('input[name="keterangan_plan[]"]').val().trim(),
                        status_2: row.find('select[name="status_2[]"]').val(),
                        objective_learning_aktual: row.find('textarea[name="objective_learning_aktual[]"]').val() || '',
                        objective_learning: row.find('textarea[name="objective_learning[]"]').val() || ''
                    });
                });

                // Get data from second table (table-body2)
                $('#table-body2 .dynamic-card').each(function() {
                    var row = $(this);
                    formData.push({
                        id: row.find('input[name="id[]"]').val(),
                        section_id: row.find('select[name="section_id[]"]').val() || null,
                        id_job_position: row.find('select[name="id_job_position[]"]').val() || null,
                        id_user: row.find('select[name="id_user[]"]').val() || null,
                        participant_user_ids: row.find('select[name="participant_user_ids[]"]').val() || [],
                        program_training: row.find('input[name="program_training[]"]').val() ? row.find('input[name="program_training[]"]').val().trim() : '',
                        kategori_competency: row.find('select[name="kategori_competency[]"]').val() || null,
                        competency: row.find('select[name="competency[]"]').val() || null,
                        due_date: row.find('input[name="due_date[]"]').val().trim(),
                        biaya: row.find('input[name="biaya[]"]').val().trim() || '0',
                        lembaga: row.find('input[name="lembaga[]"]').val().trim(),
                        keterangan_tujuan: row.find('input[name="keterangan_tujuan[]"]').val().trim(),
                        program_training_plan: row.find('input[name="program_training_plan[]"]').val().trim(),
                        due_date_plan: row.find('input[name="due_date_plan[]"]').val().trim(),
                        biaya_plan: row.find('input[name="biaya_plan[]"]').val().trim() || '0',
                        lembaga_plan: row.find('input[name="lembaga_plan[]"]').val().trim(),
                        keterangan_plan: row.find('input[name="keterangan_plan[]"]').val().trim(),
                        status_2: row.find('select[name="status_2[]"]').val(),
                        objective_learning_aktual: row.find('textarea[name="objective_learning_aktual[]"]').val() || '',
                        objective_learning: row.find('textarea[name="objective_learning[]"]').val() || '',
                        is_sharing_knowledge: row.find('select[name="kategori_usulan[]"]').val() === '1'
                    });
                });

                return formData.filter((row) => !shouldIgnoreInertFollowUpRow(row));
            }

            // ====================================================================
            // Fungsi utama untuk mengirim data via AJAX
            // ====================================================================
            function buildSubmissionError(xhr, submittedRows) {
                const response = xhr.responseJSON || {};
                const messages = [];
                let firstInvalidRowId = null;

                Object.entries(response.errors || {}).forEach(([field, fieldMessages]) => {
                    const rowMatch = field.match(/^rows\.(\d+)(?:\.|$)/);
                    const rowIndex = rowMatch ? Number(rowMatch[1]) : null;
                    const submittedRow = Number.isInteger(rowIndex)
                        ? submittedRows[rowIndex]
                        : null;

                    if (!firstInvalidRowId && submittedRow?.id) {
                        firstInvalidRowId = String(submittedRow.id);
                    }

                    const rowLabel = Number.isInteger(rowIndex)
                        ? `Baris ${rowIndex + 1}${submittedRow?.id ? ` (ID ${submittedRow.id})` : ''}`
                        : 'Data';

                    (Array.isArray(fieldMessages) ? fieldMessages : [fieldMessages])
                        .filter(Boolean)
                        .forEach((message) => messages.push(`${rowLabel}: ${message}`));
                });

                const uniqueMessages = [...new Set(messages)];
                if (uniqueMessages.length === 0) {
                    const fallback = response.error
                        || (response.message !== 'The given data was invalid.' ? response.message : null)
                        || 'Data belum dapat diproses. Periksa kembali isian Anda.';
                    uniqueMessages.push(fallback);
                }

                return {
                    messages: uniqueMessages,
                    firstInvalidRowId,
                };
            }

            function submitFormData(action) {
                var formData = collectFormData();

                // Validasi: jika action 'reject', set semua status ke Ditolak
                if (action === 'reject') {
                    formData = formData.map(function(row) {
                        row.status_2 = 'Ditolak';
                        return row;
                    });
                }

                var formDataObject = new FormData();
                formDataObject.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formDataObject.append('data', JSON.stringify(formData));
                formDataObject.append('tahun_aktual', '{{ $tahun_aktual }}');
                formDataObject.append('action', action); // Tambahkan action ke request

                // Append files from both tables
                $('#table-body .dynamic-card, #table-body2 .dynamic-card').each(function() {
                    var row = $(this);
                    var id = row.find('input[name="id[]"]').val();
                    var fileInput = row.find('input[type="file"]')[0];
                    if (fileInput && fileInput.files[0]) {
                        formDataObject.append('file[' + id + ']', fileInput.files[0]);
                    }
                });

                // Show loading
                Swal.fire({
                    title: 'Menyimpan data...',
                    text: 'Mohon tunggu sebentar.',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });

                $.ajax({
                    url: '{{ route('updateData') }}',
                    type: 'POST',
                    processData: false,
                    contentType: false,
                    data: formDataObject,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data training berhasil diperbarui.',
                            confirmButtonColor: '#0d6efd'
                        }).then(() => { location.reload(); });
                    },
                    error: function(xhr, status, error) {
                        const failure = buildSubmissionError(xhr, formData);
                        const visibleMessages = failure.messages.slice(0, 8);
                        const remainingCount = failure.messages.length - visibleMessages.length;
                        const options = {
                            icon: 'error',
                            title: xhr.status === 422 ? 'Data Belum Dapat Disimpan' : 'Gagal!',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: failure.firstInvalidRowId ? 'Periksa Data' : 'OK',
                        };

                        if (xhr.status === 422) {
                            options.html = `
                                <div class="text-start">
                                    <p class="mb-2">Periksa data berikut:</p>
                                    <ul class="mb-0 ps-3">
                                        ${visibleMessages.map((message) => `<li>${escapeHtml(message)}</li>`).join('')}
                                    </ul>
                                    ${remainingCount > 0 ? `<p class="mt-2 mb-0 text-muted">Dan ${remainingCount} kesalahan lainnya.</p>` : ''}
                                </div>
                            `;
                        } else {
                            options.text = failure.messages[0] || error;
                        }

                        Swal.fire(options).then(() => {
                            if (
                                failure.firstInvalidRowId
                                && document.getElementById('row-' + failure.firstInvalidRowId)
                            ) {
                                jumpToCard('row-' + failure.firstInvalidRowId);
                            }
                        });
                    }
                });
            }

            $(document).ready(function() {
                // ---- HANDLER LAMA (untuk backward compat jika ada #updateButton di view lain) ----
                $('#updateButton').on('click', function(e) {
                    e.preventDefault();
                    submitFormData('save');
                });

                // ---- HANDLER BARU untuk tombol-tombol di sticky footer ----
                $('#save-button').on('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Simpan Data?',
                        text: 'Semua perubahan akan disimpan.',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#0d6efd',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Simpan',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) submitFormData('save');
                    });
                });

                $('#approve-button').on('click', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Approve Pengajuan?',
                        text: 'Data akan disimpan dan status pengajuan disetujui.',
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#198754',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, Approve',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) submitFormData('approve');
                    });
                });


            });

            function updateRowNumbers() {
                var tableBody = document.getElementById('table-body');
                var rows = tableBody.getElementsByTagName('tr');

                // Update nomor urut untuk tabel pertama
                for (var i = 0; i < rows.length; i++) {
                    rows[i].getElementsByTagName('td')[0].innerText = i + 1; // Menggunakan indeks untuk nomor urut
                }

                // Update nomor urut untuk tabel kedua jika ada
                var tableBody2 = document.getElementById('table-body2');
                if (tableBody2) {
                    var rows2 = tableBody2.getElementsByTagName('tr');
                    // Menghitung nomor awal untuk tabel kedua
                    var startNumber = rows.length + 1; // Mulai dari jumlah baris tabel pertama + 1
                    for (var j = 0; j < rows2.length; j++) {
                        rows2[j].getElementsByTagName('td')[0].innerText = startNumber +
                            j; // Menggunakan indeks untuk nomor urut
                    }
                }
            }

            window.onload = function() {
                updateRowNumbers(); // Memperbarui nomor urut saat halaman dimuat
            };

            // Object untuk menyimpan konfigurasi warna status dan teks
            const colorConfig = {
                'Mencari Vendor': {
                    bg: 'blue',
                    text: 'white'
                },
                'Proses Pendaftaran': {
                    bg: 'orange',
                    text: 'white'
                },
                'On Progress': {
                    bg: 'yellow',
                    text: 'black'
                },
                'Done': {
                    bg: 'green',
                    text: 'white'
                },
                'Pending': {
                    bg: 'gray',
                    text: 'white'
                },
                'Ditolak': {
                    bg: 'red',
                    text: 'white'
                }
            };

            // Function to determine the color based on status
            function getStatusColor(status) {
                return colorConfig[status]?.bg || '';
            }

            // Function to determine text color
            function getTextColor(status) {
                return colorConfig[status]?.text || '';
            }

            // Function to update dropdown color based on selection
            function updateDropdownColor(dropdown) {
                const status = dropdown.value;
                if (status && colorConfig[status]) {
                    dropdown.style.backgroundColor = getStatusColor(status);
                    dropdown.style.color = getTextColor(status);
                } else {
                    // Reset background color dan set text color ke hitam jika status kosong atau tidak ada di colorConfig
                    dropdown.style.backgroundColor = '';
                    dropdown.style.color = 'black';
                }
            }

            // Function to toggle file upload input disabled state
            function toggleFileUpload(dropdown) {
                // Cari elemen input file di dalam baris/card yang sama
                const card = dropdown.closest('.dynamic-card');
                if (card) {
                    const fileInput = card.querySelector('input[type="file"]');
                    if (fileInput) {
                        // Buka (enable) jika status = Done, selain itu kunci (disable)
                        fileInput.disabled = (dropdown.value !== 'Done');
                    }
                }
            }

            // Download function used by download buttons
            function downloadPdf(id) {
                var downloadPdfUrl = "{{ route('download.pdf', ['id' => ':id'], false) }}";
                var url = downloadPdfUrl.replace(':id', id);
                window.location.href = url;
            }

            // Fungsi untuk memformat angka sebagai mata uang
            function formatCurrency(amount) {
                return amount.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                });
            }

            // Fungsi menyalin satu field usulan ke aktual
            // Mapping khusus:
            //   keterangan_tujuan -> keterangan_plan
            //   objective_learning -> objective_learning_aktual (kolom "Objective Learning Aktual")
            //   lainnya: field -> field_plan
            function copyToAktual(id, field) {
                const sourceInput = document.getElementById(`${field}_${id}`);
                let targetInput;
                if (field === 'keterangan_tujuan') {
                    targetInput = document.getElementById(`keterangan_plan_${id}`);
                } else if (field === 'objective_learning') {
                    targetInput = document.getElementById(`objective_learning_aktual_${id}`);
                } else {
                    targetInput = document.getElementById(`${field}_plan_${id}`);
                }
                if (sourceInput && targetInput) {
                    targetInput.value = sourceInput.value;
                }
            }

            // Fungsi menyalin semua usulan ke aktual untuk satu id (Card View)
            function copyAllToAktual(id) {
                const fields = ['program_training', 'due_date', 'biaya', 'lembaga', 'keterangan_tujuan', 'objective_learning'];
                fields.forEach(field => copyToAktual(id, field));
            }

            // Fungsi menyalin semua usulan ke aktual dari Table View
            // Menyalin ke hidden card lalu re-render tabel agar perubahan langsung terlihat
            window.copyAllToAktualTable = function(id) {
                var fields = ['program_training', 'due_date', 'biaya', 'lembaga', 'keterangan_tujuan', 'objective_learning'];
                fields.forEach(function(field) { copyToAktual(id, field); });
                renderSummaryTable();
            };

            // Filter & Search Logic
            function applyFilters() {
                const searchValue = document.getElementById('searchInput').value.toLowerCase();
                const statusValue = document.getElementById('statusFilter').value;
                const cards = document.querySelectorAll('.dynamic-card');
                let count = 0;

                cards.forEach(card => {
                    const empName = card.dataset.employeeName || '';
                    const progName = card.dataset.programName || '';
                    const status = card.dataset.status || '';

                    const matchesSearch = empName.includes(searchValue) || progName.includes(searchValue);
                    const matchesStatus = statusValue === '' || status === statusValue;

                    if (matchesSearch && matchesStatus) {
                        card.style.display = '';
                        count++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                document.getElementById('filterCount').innerText = `${count} Data Ditampilkan`;
            }

            document.addEventListener('DOMContentLoaded', function() {
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');

                if (searchInput) searchInput.addEventListener('keyup', applyFilters);
                if (statusFilter) statusFilter.addEventListener('change', applyFilters);

                // Initial count delay untuk memastikan element di-render dari JSON template
                setTimeout(function() {
                    applyFilters();
                    // Default: tampilkan tabel ringkasan
                    switchToTableView();
                }, 600);
            });

            // ===== Modul 4.2: Year Management JS =====
            (function() {
                var btnSetYear = document.getElementById('btn-set-year');
                if (!btnSetYear) return;
                btnSetYear.addEventListener('click', function() {
                    var year = document.getElementById('year-select').value;
                    if (!year) return;
                    var csrfToken = document.querySelector('meta[name="csrf-token"]') ?
                        document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';
                    fetch('{{ route("pd.active-year.set") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ year: year })
                    })
                    .then(function(res) { return res.json(); })
                    .then(function(data) {
                        if (data.success) {
                            var display = document.getElementById('active-year-display');
                            if (display) display.textContent = year;
                            var msg = document.getElementById('year-set-msg');
                            if (msg) {
                                msg.classList.remove('d-none');
                                setTimeout(function() { msg.classList.add('d-none'); }, 2500);
                            }
                        } else {
                            alert(data.message || 'Gagal mengubah tahun aktif.');
                        }
                    })
                    .catch(function() { alert('Koneksi gagal. Silakan coba lagi.'); });
                });
            })();

            // ===== Inline Tambah Additional di Table View =====
            // Sections data dari Blade (dibutuhkan untuk inline dropdown)
            var inlineSections = @json($sections->map(function($s) { return ['id' => $s->id, 'name' => $s->name]; }));

            function addAdditionalInlineRow() {
                // 1. Buat card tersembunyi di table-body2 (data backend)
                window.addAdditionalRow();
                // 2. Re-render tabel ringkasan (baris baru akan di-render sebagai inline editable)
                renderSummaryTable();
                // 3. Scroll ke baris baru
                setTimeout(function() {
                    var lastRow = document.querySelector('#summary-table-body tr:last-child');
                    if (lastRow) {
                        lastRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        lastRow.style.backgroundColor = '#fff9c4';
                        setTimeout(function() { lastRow.style.backgroundColor = ''; }, 2000);
                    }
                }, 100);
            }

            // Fungsi untuk generate section options HTML
            function buildSectionOptions(selectedVal) {
                var html = '<option value="">-- Pilih --</option>';
                inlineSections.forEach(function(s) {
                    html += '<option value="' + s.id + '"' + (s.id == selectedVal ? ' selected' : '') + '>' + s.name + '</option>';
                });
                return html;
            }

            // Fungsi untuk generate job position options HTML
            function buildJobPositionOptions(sectionId, selectedVal) {
                var html = '<option value="">-- Pilih --</option>';
                if (!sectionId) return html;
                var uniqueJobs = [];
                jobPositions.forEach(function(jp) {
                    if (jp.section_id == sectionId && !uniqueJobs.includes(jp.job_position)) {
                        uniqueJobs.push(jp.job_position);
                        html += '<option value="' + jp.id + '"' + (jp.id == selectedVal ? ' selected' : '') + '>' + jp.job_position + '</option>';
                    }
                });
                return html;
            }

            // Fungsi untuk generate user options HTML
            function buildUserOptions(sectionId, jobPosId, selectedVal, isSharing) {
                var html = '<option value="">-- Pilih --</option>';
                var uniqueUserIds = [];
                if (isSharing) {
                    // All users
                    jobPositions.forEach(function(jp) {
                        if (jp.active_users) {
                            jp.active_users.forEach(function(u) {
                                if (!uniqueUserIds.includes(u.id)) {
                                    uniqueUserIds.push(u.id);
                                    html += '<option value="' + u.id + '"' + (u.id == selectedVal ? ' selected' : '') + '>' + escapeHtml(employeeLabel(u)) + '</option>';
                                }
                            });
                        }
                    });
                } else {
                    jobPositions.forEach(function(jp) {
                        if (jp.section_id == sectionId && jp.id == jobPosId) {
                            if (jp.active_users) {
                                jp.active_users.forEach(function(u) {
                                    if (!uniqueUserIds.includes(u.id)) {
                                        uniqueUserIds.push(u.id);
                                        html += '<option value="' + u.id + '"' + (u.id == selectedVal ? ' selected' : '') + '>' + escapeHtml(employeeLabel(u)) + '</option>';
                                    }
                                });
                            }
                        }
                    });
                }
                return html;
            }

            // Fungsi untuk generate competency options HTML
            function buildCompetencyOptions(kategori, selectedVal) {
                var html = '<option value="">-- Pilih --</option>';
                if (kategori && masterCompetencies[kategori]) {
                    masterCompetencies[kategori].forEach(function(comp) {
                        html += '<option value="' + comp + '"' + (comp == selectedVal ? ' selected' : '') + '>' + comp + '</option>';
                    });
                }
                return html;
            }

            // Attach cascading logic ke baris inline baru setelah DOM diisi
            function attachInlineCascadingLogic(id) {
                var katUsulanSel = document.getElementById('tbl_kat_usulan_' + id);
                var katCompSel = document.getElementById('tbl_kat_comp_' + id);
                var compSel = document.getElementById('tbl_comp_' + id);

                var sectionTd = document.getElementById('tbl_section_td_' + id);
                var jobPosTd = document.getElementById('tbl_job_td_' + id);

                if (!katUsulanSel) return;

                renderInlineEmployeeControl(id, katUsulanSel.value === '1');

                // Kategori Usulan → toggle Section/Job + kontrol karyawan/participant.
                katUsulanSel.addEventListener('change', function() {
                    const isSharing = this.value === '1';
                    syncToCard(id, 'kategori_usulan', this.value);

                    // Trigger card change event
                    var cardKat = document.getElementById('kategori_usulan_' + id);
                    if (cardKat) { cardKat.value = this.value; cardKat.dispatchEvent(new Event('change')); }

                    if (isSharing) {
                        // Sharing Knowledge: section/job tidak berlaku dan employee menjadi multi-participant.
                        if (sectionTd) sectionTd.innerHTML = '<span class="text-muted">-</span>';
                        if (jobPosTd) jobPosTd.innerHTML = '<span class="text-muted">-</span>';
                    } else {
                        // Training: kembalikan section/job dan bersihkan participant.
                        syncInlineParticipantsToCard(id, []);
                        if (sectionTd) sectionTd.innerHTML = '<select id="tbl_section_' + id + '" class="form-select form-select-sm" onchange="handleInlineSectionChange(\'' + id + '\')">' + buildSectionOptions('') + '</select>';
                        if (jobPosTd) jobPosTd.innerHTML = '<select id="tbl_job_pos_' + id + '" class="form-select form-select-sm" onchange="handleInlineJobChange(\'' + id + '\')">' + buildJobPositionOptions('', '') + '</select>';
                    }

                    renderInlineEmployeeControl(id, isSharing);
                });

                // Kategori Competency → populate Competency
                if (katCompSel) {
                    katCompSel.addEventListener('change', function() {
                        syncToCard(id, 'kategori_competency', this.value);
                        // Juga trigger change di card untuk TomSelect
                        var cardKatComp = document.getElementById('kategori_competency_' + id);
                        if (cardKatComp) { cardKatComp.value = this.value; cardKatComp.dispatchEvent(new Event('change')); }
                        if (compSel) {
                            compSel.innerHTML = buildCompetencyOptions(this.value, '');
                        }
                    });
                }

                // Competency → sync to card
                if (compSel) {
                    compSel.addEventListener('change', function() {
                        syncToCard(id, 'competency', this.value);
                        // Also sync via TomSelect if available
                        var cardComp = document.getElementById('competency_' + id);
                        if (cardComp && cardComp.tomselect) {
                            cardComp.tomselect.setValue(this.value);
                        } else if (cardComp) {
                            cardComp.value = this.value;
                        }
                    });
                }
            }

            // Handler global untuk perubahan Section di tabel inline
            window.handleInlineSectionChange = function(id) {
                var sectionSel = document.getElementById('tbl_section_' + id);
                var jobPosTd = document.getElementById('tbl_job_td_' + id);
                var userSel = document.getElementById('tbl_user_' + id);

                if (!sectionSel) return;
                var sectionId = sectionSel.value;
                syncToCard(id, 'section_id', sectionId);
                // Trigger card section change
                var cardSection = document.getElementById('section_id_' + id);
                if (cardSection) { cardSection.value = sectionId; cardSection.dispatchEvent(new Event('change')); }

                // Rebuild job pos dropdown
                if (jobPosTd) {
                    jobPosTd.innerHTML = '<select id="tbl_job_pos_' + id + '" class="form-select form-select-sm" onchange="handleInlineJobChange(\'' + id + '\')">' + buildJobPositionOptions(sectionId, '') + '</select>';
                }
                // Reset users
                if (userSel) { userSel.innerHTML = buildUserOptions(sectionId, '', '', false); }
            };

            // Handler global untuk perubahan Job Position di tabel inline
            window.handleInlineJobChange = function(id) {
                var sectionSel = document.getElementById('tbl_section_' + id);
                var jobPosSel = document.getElementById('tbl_job_pos_' + id);
                var userSel = document.getElementById('tbl_user_' + id);

                if (!jobPosSel) return;
                var sectionId = sectionSel ? sectionSel.value : '';
                var jobPosId = jobPosSel.value;
                syncToCard(id, 'id_job_position', jobPosId);
                // Trigger card job change
                var cardJob = document.getElementById('id_job_position_' + id);
                if (cardJob) { cardJob.value = jobPosId; cardJob.dispatchEvent(new Event('change')); }

                // Rebuild users
                if (userSel) { userSel.innerHTML = buildUserOptions(sectionId, jobPosId, '', false); }
            };

        </script>
        <script src="{{ asset('js/hr/training-follow-up-ui.js') }}"></script>
    </main><!-- End #main -->
@endsection
