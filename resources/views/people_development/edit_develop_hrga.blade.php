@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
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
                border-collapse: collapse;
                margin: 25px 0;
                font-size: 14px;
                text-align: left;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            }

            .styled-table thead tr {
                background-color: #1072a0;
                color: #ffffff;
                text-align: left;
            }

            .styled-table th,
            .styled-table td {
                padding: 12px 15px;
            }

            .styled-table tbody tr {
                border-bottom: 1px solid #dddddd;
            }

            .styled-table tbody tr:nth-of-type(even) {
                background-color: #f3f3f3;
            }

            .styled-table tbody tr:last-of-type {
                border-bottom: 2px solid #009879;
            }

            .styled-table tbody tr.active-row {
                font-weight: bold;
                color: #009879;
            }

            select.status-dropdown {
                color: white;
                /* Default text color */
            }
            .status-dropdown option {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
        </style>
        <div class="pagetitle">
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
            <div class="card mb-3 border-0 shadow-sm" style="background: linear-gradient(90deg,#1e3a5f 0%,#2563eb 100%);color:white;">
                <div class="card-body py-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-calendar2-check fs-4"></i>
                        <div>
                            <div class="small fw-semibold text-white-50">Tahun Aktif Training</div>
                            <div class="fw-bold fs-5" id="active-year-display">{{ $activeYear ?? date('Y') }}</div>
                        </div>
                    </div>
                    @can('manage-pd-year')
                    <div class="d-flex align-items-center gap-2">
                        <select id="year-select" class="form-select form-select-sm" style="width:auto;min-width:90px;">
                            @for($y = date('Y') + 1; $y >= 2020; $y--)
                                <option value="{{ $y }}" {{ (string)$activeYear === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                        <button type="button" id="btn-set-year" class="btn btn-light btn-sm rounded-pill px-3 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i>Set Tahun
                        </button>
                        <span id="year-set-msg" class="small text-white-50 d-none"><i class="bi bi-check-circle me-1"></i>Tersimpan!</span>
                    </div>
                    @endcan
                </div>
            </div>
            <!-- Filter Bar -->
            <div class="card mb-4 shadow-sm border-0">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari Nama Karyawan atau Program...">
                            </div>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-2 text-end">
                            <span class="badge bg-primary rounded-pill p-2" id="filterCount">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>

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

                        <!-- Sticky Footer Actions -->
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
                                <button type="button" class="btn btn-success rounded-pill px-4" id="approve-button" data-action="approve">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </section>
                {{-- status color --}}
                <div style="margin-top: 20px;">
                    <strong>Keterangan Status:</strong>
                    <ul
                        style="list-style-type: none; padding-left: 0; margin-top: 10px; display: flex; flex-direction: column; gap: 15px;">
                        <li style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center;">
                                <span
                                    style="background-color: blue; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;">
                                    <b>Biru</b>
                                </span> - Mencari Vendor =
                                <span id="status-blue-percentage">{{ number_format($percentageStatusBlue, 2) }}%
                                    ({{ $countStatusBlue }} dari {{ $totalRecords }})</span>
                            </span>
                        </li>
                        <li style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center;">
                                <span
                                    style="background-color: orange; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;">
                                    <b>Orange</b>
                                </span> - Proses Pendaftaran =
                                <span id="status-orange-percentage">{{ number_format($percentageStatusOrange, 2) }}%
                                    ({{ $countStatusOrange }} dari {{ $totalRecords }})</span>
                            </span>
                        </li>
                        <li style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center;">
                                <span
                                    style="background-color: yellow; color: black; padding: 5px 15px; border-radius: 5px; margin-right: 5px;">
                                    <b>Kuning</b>
                                </span> - On Progress =
                                <span id="status-yellow-percentage">{{ number_format($percentageStatusYellow, 2) }}%
                                    ({{ $countStatusYellow }} dari {{ $totalRecords }})</span>
                            </span>
                        </li>
                        <li style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center;">
                                <span
                                    style="background-color: green; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;">
                                    <b>Hijau</b>
                                </span> - Done =
                                <span id="status-green-percentage">{{ number_format($percentageStatusGreen, 2) }}%
                                    ({{ $countStatusGreen }} dari {{ $totalRecords }})</span>
                            </span>
                        </li>
                        <li style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center;">
                                <span
                                    style="background-color: rgb(154, 150, 150); color: rgb(251, 251, 251); padding: 5px 15px; border-radius: 5px; margin-right: 5px;">
                                    <b>Abu</b>
                                </span> - Pending =
                                <span id="status-gray-percentage">{{ number_format($percentageStatusGray, 2) }}%
                                    ({{ $countStatusGray }} dari {{ $totalRecords }})</span>
                            </span>
                        </li>
                        <li style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="display: flex; align-items: center;">
                                <span
                                    style="background-color: red; color: white; padding: 5px 15px; border-radius: 5px; margin-right: 5px;">
                                    <b>Merah</b>
                                </span> - Ditolak =
                                <span id="status-red-percentage">{{ number_format($percentageStatusRed, 2) }}%
                                    ({{ $countStatusRed }} dari {{ $totalRecords }})</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- jQuery (single import) -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

        <script>
            var existingData = @json($data);
            var jobPositions = @json($jobPositions);

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
                        newRow.dataset.employeeName = item.user ? item.user.name.toLowerCase() : '';
                        newRow.dataset.programName = item.program_training ? item.program_training.toLowerCase() : '';
                        newRow.dataset.status = item.status_2 || '';

                        var userOptions = '<option value="">---- Pilih Karyawan ----</option>';
                        var competencyOptions = '<option value="">---- Pilih Competency ----</option>';

                        if (item.user) {
                            userOptions +=
                                `<option value="${item.user.id}" selected>${item.user.name}</option>`;
                        }

                        if (item.competency) {
                            competencyOptions +=
                                `<option value="${item.competency}" selected>${item.competency}</option>`;
                        }

                        var headerTitle = item.user ? item.user.name : 'Unknown User';
                        headerTitle += ' - ' + (item.program_training || 'Unknown Program');

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
                                                    <label>Nama Karyawan</label>
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
                                                    <select name="status_2[]" class="form-select status-dropdown border-${borderClass.replace('border-', '')}" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};">
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
                                            {{-- Modul 4.1: Sharing Knowledge & Objective Learning --}}
                                            <div class="col-12">
                                                <div class="card border-0 bg-light rounded-3 mt-2">
                                                    <div class="card-body p-2">
                                                        <h6 class="small fw-bold text-muted mb-2"><i class="bi bi-lightbulb-fill me-1 text-warning"></i>Tindak Lanjut Pasca Training</h6>
                                                        <div class="row g-2">
                                                            <div class="col-md-6">
                                                                <div class="form-floating">
                                                                    <textarea class="form-control form-control-sm" id="sharing_knowledge_${item.id}" name="sharing_knowledge[]" placeholder="Sharing Knowledge" style="height:80px;">${item.sharing_knowledge || ''}</textarea>
                                                                    <label class="small"><i class="bi bi-people me-1"></i>Sharing Knowledge</label>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-floating">
                                                                    <textarea class="form-control form-control-sm" id="objective_learning_${item.id}" name="objective_learning[]" placeholder="Objective Learning" style="height:80px;">${item.objective_learning || ''}</textarea>
                                                                    <label class="small"><i class="bi bi-bullseye me-1"></i>Objective Learning</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                                                ${item.file ? `<button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="downloadPdf(${item.id})"><i class="bi bi-filetype-pdf"></i> Download File</button>` : ''}
                                                ${item.status_2 === 'Done' ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn btn-sm rounded-pill ${item.diketahui ? 'btn-success' : 'btn-danger'}"><i class="fas fa-eye"></i> Evaluasi</a>` : ''}
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
                                                option.text = u.name;
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
                        newEmployeeRow.dataset.employeeName = item.user ? item.user.name.toLowerCase() : '';
                        newEmployeeRow.dataset.programName = item.program_training ? item.program_training.toLowerCase() : '';
                        newEmployeeRow.dataset.status = item.status_2 || '';

                        var userOptionsList = '<option value="">---- Pilih Karyawan ----</option>';
                        var competencyOptionsList = '<option value="">---- Pilih Competency ----</option>';

                        if (item.user) {
                            userOptionsList +=
                                `<option value="${item.user.id}" selected>${item.user.name}</option>`;
                        }

                        if (item.competency) {
                            competencyOptionsList +=
                                `<option value="${item.competency}" selected>${item.competency}</option>`;
                        }

                        var headerTitle = item.user ? item.user.name : 'Unknown User';
                        headerTitle += ' - ' + (item.program_training || 'Unknown Program');

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
                                            <div class="col-md-6">
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
                                            <div class="col-md-6">
                                                <div class="form-floating">
                                                    <select class="form-select employee-job-position-dropdown" name="id_job_position[]" disabled>
                                                        <option value="">---- Pilih Job Position ----</option>
                                                    </select>
                                                    <label>Job Position</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-floating">
                                                    <select class="form-select employee-user-dropdown" name="id_user[]" disabled>
                                                        ${userOptionsList}
                                                    </select>
                                                    <label>Nama Karyawan</label>
                                                </div>
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
                                                    <select name="status_2[]" class="form-select status-dropdown border-${borderClass.replace('border-', '')}" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};">
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
                                            <div class="col-12 mt-3 d-flex justify-content-end gap-2">
                                                ${item.file ? `<button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="downloadPdf(${item.id})"><i class="bi bi-filetype-pdf"></i> Download File</button>` : ''}
                                                ${item.status_2 === 'Done' ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn btn-sm rounded-pill ${item.diketahui ? 'btn-success' : 'btn-danger'}"><i class="fas fa-eye"></i> Evaluasi</a>` : ''}
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
                                                option.text = u.name;
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
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" onclick="$('#row-${tempId}').remove()"><i class="bi bi-trash"></i> Hapus</button>
                        </div>
                        <div class="card-body">
                            <input type="hidden" name="modified_at" value="" />
                            <input type="hidden" name="id[]" value="${tempId}" />
                            
                            <div class="row">
                                <!-- KIRI: DATA USULAN -->
                                <div class="col-lg-6 mb-4 mb-lg-0">
                                    <h6 class="text-secondary border-bottom pb-2 mb-3"><i class="fas fa-file-alt me-1"></i> 1. Data Usulan</h6>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select employee-section-dropdown" name="section_id[]" id="section_id_${tempId}" required>
                                                    ${sectionOptions}
                                                </select>
                                                <label>Section</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <select class="form-select employee-job-position-dropdown" name="id_job_position[]" id="id_job_position_${tempId}" required>
                                                    <option value="">---- Pilih Job Position ----</option>
                                                </select>
                                                <label>Job Position</label>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <select class="form-select employee-user-dropdown" name="id_user[]" id="id_user_${tempId}" required>
                                                    <option value="">---- Pilih Karyawan ----</option>
                                                </select>
                                                <label>Nama Karyawan</label>
                                            </div>
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
                                            <div class="form-floating">
                                                <select name="competency[]" class="form-select employee-competency-dropdown" id="competency_${tempId}" required>
                                                    <option value="additional" selected>Additional</option>
                                                </select>
                                                <label>Competency</label>
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
                                                <select name="status_2[]" class="form-select status-dropdown" onchange="updateDropdownColor(this); toggleFileUpload(this);">
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
                                            option.text = u.name;
                                            userDropdown.appendChild(option);
                                        }
                                    });
                                }
                            }
                        });
                    });
                }

                var addAdditionalBtn = document.getElementById('add-additional-btn');
                if (addAdditionalBtn) {
                    addAdditionalBtn.addEventListener('click', function() {
                        addAdditionalRow();
                    });
                }
            });


            // ====================================================================
            // Fungsi untuk mengumpulkan semua data form dari kedua tabel
            // ====================================================================
            function collectFormData() {
                var formData = [];

                // Get data from first table (table-body)
                $('#table-body .dynamic-card').each(function() {
                    var row = $(this);
                    formData.push({
                        id: row.find('input[name="id[]"]').val(),
                        due_date: row.find('input[name="due_date[]"]').val().trim(),
                        biaya: row.find('input[name="biaya[]"]').val().trim() || '0',
                        lembaga: row.find('input[name="lembaga[]"]').val().trim(),
                        keterangan_tujuan: row.find('input[name="keterangan_tujuan[]"]').val().trim(),
                        program_training_plan: row.find('input[name="program_training_plan[]"]').val().trim(),
                        due_date_plan: row.find('input[name="due_date_plan[]"]').val().trim(),
                        biaya_plan: row.find('input[name="biaya_plan[]"]').val().trim() || '0',
                        lembaga_plan: row.find('input[name="lembaga_plan[]"]').val().trim(),
                        keterangan_plan: row.find('input[name="keterangan_plan[]"]').val().trim(),
                        status_2: row.find('select[name="status_2[]"]').val()
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
                        status_2: row.find('select[name="status_2[]"]').val()
                    });
                });

                return formData;
            }

            // ====================================================================
            // Fungsi utama untuk mengirim data via AJAX
            // ====================================================================
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan: ' + (xhr.responseJSON?.error || error),
                            confirmButtonColor: '#dc3545'
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
            function copyToAktual(id, field) {
                const sourceInput = document.getElementById(`${field}_${id}`);
                const targetField = field === 'keterangan_tujuan' ? 'keterangan' : field;
                const targetInput = document.getElementById(`${targetField}_plan_${id}`);
                if (sourceInput && targetInput) {
                    targetInput.value = sourceInput.value;
                }
            }

            // Fungsi menyalin semua usulan ke aktual untuk satu id
            function copyAllToAktual(id) {
                const fields = ['program_training', 'due_date', 'biaya', 'lembaga', 'keterangan_tujuan'];
                fields.forEach(field => copyToAktual(id, field));
                alert('Semua data usulan telah disalin ke aktual.');
            }

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
                setTimeout(applyFilters, 500);
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

        </script>
    </main><!-- End #main -->
@endsection
