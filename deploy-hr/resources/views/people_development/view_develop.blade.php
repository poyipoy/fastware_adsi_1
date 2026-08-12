@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ asset('css/hr/training-status-summary.css') }}">
        <script src="{{ asset('js/hr/training-status-summary.js') }}" defer></script>
        <style>
            .container {
                margin-top: 20px;
                padding-bottom: 80px; /* Ruang untuk sticky footer */
            }

            .profile-card {
                background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
                border-left: 5px solid #0d6efd;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
                padding: 20px;
                margin-bottom: 25px;
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .profile-avatar {
                width: 60px;
                height: 60px;
                background-color: #0d6efd;
                color: white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 24px;
                font-weight: bold;
                text-transform: uppercase;
            }

            .profile-info h4 {
                margin: 0;
                font-weight: 700;
                color: #333;
            }

            .profile-info p {
                margin: 0;
                color: #6c757d;
                font-size: 14px;
            }

            .styled-table {
                width: 100%;
                border-collapse: collapse;
                margin: 25px 0;
                font-size: 14px;
                text-align: left;
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
                border-radius: 8px;
                overflow: hidden;
            }

            .styled-table thead tr {
                background-color: #0d6efd; /* Modern blue */
                color: #ffffff;
                text-align: left;
            }

            .styled-table th,
            .styled-table td {
                padding: 12px 15px;
                white-space: nowrap;
            }

            .styled-table tbody tr {
                border-bottom: 1px solid #f0f0f0;
            }

            .styled-table tbody tr:nth-of-type(even) {
                background-color: #f8f9fa;
            }

            .styled-table tbody tr:hover {
                background-color: rgba(13, 110, 253, 0.05);
            }

            select.status-dropdown, select.status-dropdown1, select.status-dropdown2 {
                color: white;
            }
            .status-dropdown option, .status-dropdown1 option, .status-dropdown2 option {
                background-color: #ffffff !important;
                color: #212529 !important;
            }
        </style>
        <div class="pagetitle" data-training-status-anchor>
            <h1>Halaman Tindak Lanjut Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('indexPD') }}">People Development</a></li>
                    <li class="breadcrumb-item active">View Tindak Lanjut</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                <!-- Profile Section -->
                @php
                    $picName = $data->first()->modified_at ?? auth()->user()->name;
                    $picUser = \App\Models\User::where('name', $picName)->first();
                    $userDept = 'Unknown Department';
                    if ($picUser) {
                        $userDept = \Illuminate\Support\Facades\DB::table('user_job_positions')
                            ->join('mst_job_positions', 'user_job_positions.mst_job_position_id', '=', 'mst_job_positions.id')
                            ->join('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
                            ->where('user_job_positions.user_id', $picUser->id)
                            ->value('mst_departments.name') ?: 'Unknown Department';
                    }
                @endphp
                <div class="profile-card">
                    <div class="profile-avatar">
                        {{ substr($picName, 0, 1) }}
                    </div>
                    <div class="profile-info">
                        <h4>{{ $picName }}</h4>
                        <p>
                            <i class="fas fa-building me-1"></i> Departemen: {{ $userDept }}
                        </p>
                    </div>
                </div>

                <!-- Card utama tabel -->
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <div class="table-container" style="overflow-x:auto;">
                        <p class="text-danger small mb-3"><i class="fas fa-info-circle me-1"></i>*Scroll kesamping dalam pengisian data</p>

                        <table class="styled-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col" rowspan="2">NO</th>
                                <th scope="col" rowspan="2">Section</th>
                                <th scope="col" rowspan="2">Job Position</th>
                                <th scope="col" rowspan="2">NPK — Nama Karyawan</th>
                                <th scope="col" rowspan="2">Program Training</th>
                                <th scope="col" rowspan="2">Kategori Competency</th>
                                <th scope="col" rowspan="2">Competency</th>
                                <th scope="col" rowspan="2">Due Date</th>
                                <th scope="col" rowspan="2">Budget</th>
                                <th scope="col" rowspan="2">Lembaga</th>
                                <th scope="col" rowspan="2">Keterangan Tujuan</th>
                                <th scope="col" rowspan="2">Objective Learning</th>
                            </tr>
                            <tr style="background-color: #f0ad4e;">
                                <th scope="col">Nama Program</th>
                                <th scope="col">Date Actual</th>
                                <th scope="col">Lembaga</th>
                                <th scope="col">Keterangan</th>
                                <th scope="col" style="min-width:150px;">Sharing Knowledge</th>
                                <th scope="col">Status</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="table-body">
                            <!-- Baris data akan diisi di sini -->
                        </tbody>
                        {{-- <tfoot>
                            @php
                                // Hitung total budget hanya jika tahun_usulan kosong
                                $totalBudget = $data
                                    ->filter(function ($item) {
                                        return empty($item->tahun_usulan);
                                    })
                                    ->sum('biaya');
                            @endphp
                            <tr>
                                <th></th>
                                <th colspan="8" style="text-align:right;">Sub Total 1: Rp
                                    {{ number_format($totalBudget, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot> --}}

                        <tfoot>
                            @php
                                // Hitung total budget hanya jika tahun_usulan kosong
                                $totalBudget = $data
                                    ->filter(function ($item) {
                                        // Memastikan bahwa $item->tahun_usulan memang kosong
                                        return empty($item->tahun_usulan);
                                    })
                                    ->sum(function ($item) {
                                        // Konversi nilai 'biaya' dari string ke angka
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya);
                                    });
                            @endphp
                            <tr>
                                <th></th>
                                <th colspan="8" style="text-align:right;">Sub Total 1: Rp
                                    {{ number_format($totalBudget, 0, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    </table>
                    <form id="trainingForm" method="POST" enctype="multipart/form-data"
                        action="{{ route('updatePdPlan2') }}">
                        @csrf
                        @method('PUT')
                        <table class="styled-table" style="width:100%;">
                            <thead>
                                <tr hidden>
                                    <th scope="col" rowspan="2">NO</th>
                                    <th scope="col" rowspan="2">Section</th>
                                    <th scope="col" rowspan="2">Job Position</th>
                                    <th scope="col" rowspan="2">NPK — Nama Karyawan</th>
                                    <th scope="col" rowspan="2">Program Training</th>
                                    <th scope="col" rowspan="2">Kategori Competency</th>
                                    <th scope="col" rowspan="2">Competency</th>
                                    <th scope="col" rowspan="2">Due Date</th>
                                    <th scope="col" rowspan="2">Budget</th>
                                    <th scope="col" rowspan="2">Lembaga</th>
                                    <th scope="col" rowspan="2">Keterangan Tujuan</th>
                                    <th scope="col" rowspan="2">Objective Learning</th>
                                </tr>
                                <tr hidden style="background-color: #f0ad4e;">
                                    <th scope="col">Nama Program</th>
                                    <th scope="col">Date Actual</th>
                                    <th scope="col">Lembaga</th>
                                    <th scope="col">Keterangan</th>
                                    <th scope="col" style="min-width:150px;">Sharing Knowledge</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="table-body2">
                                <h3><b>ADDITIONAL</b> <i class="fas fa-chevron-down"></i></h3>
                                <!-- Baris data akan diisi di sini -->
                            </tbody>
                            {{-- <tfoot>
                                @php
                                    // Hitung total budget dari tabel pertama
                                    $totalBudgetTabelPertama = $data->sum('biaya');

                                    // Hitung total budget, total biaya actual, dan selisih biaya untuk tabel kedua
                                    $filteredData = $data->filter(function ($item) {
                                        return !is_null($item->tahun_usulan);
                                    });

                                    $totalBudgetTabelKedua = $filteredData->sum('biaya');
                                    $totalBiayaPlan = $filteredData->sum('biaya') + $totalBudgetTabelPertama;
                                    $selisihBiaya = $totalBudgetTabelKedua - $totalBiayaPlan;

                                    // Total biaya keseluruhan dari tabel pertama dan kedua
                                    $totalKeseluruhan = $totalBudgetTabelPertama + $totalBudgetTabelKedua;
                                @endphp

                                <!-- Bagian untuk tabel kedua, hanya ditampilkan jika ada data yang memenuhi syarat -->
                                @if ($filteredData->isNotEmpty())
                                    <tr>
                                        <th></th>
                                        <th colspan="8" style="text-align:right;">Sub Total 2: Rp
                                            {{ number_format($totalBudgetTabelKedua, 0, ',', '.') }}</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th colspan="8" style="text-align:right;">Total: Rp
                                            {{ number_format($totalBiayaPlan, 0, ',', '.') }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                @endif
                            </tfoot> --}}
                            <tfoot>
                                @php
                                    // Hitung total budget dari tabel pertama (hanya yang TIDAK memiliki tahun_usulan)
                                    $totalBudgetTabelPertama = $data->filter(function ($item) {
                                        return empty($item->tahun_usulan);
                                    })->sum(function ($item) {
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya); // Konversi biaya ke float
                                    });

                                    // Hitung total budget, total biaya aktual, dan selisih biaya untuk tabel kedua
                                    $filteredData = $data->filter(function ($item) {
                                        return !is_null($item->tahun_usulan);
                                    });

                                    $totalBudgetTabelKedua = $filteredData->sum(function ($item) {
                                        return (float) str_replace(['Rp', '.', ' '], '', $item->biaya); // Konversi biaya ke float
                                    });

                                    $totalBiayaPlan = $totalBudgetTabelKedua + $totalBudgetTabelPertama; // Total biaya rencana
                                    $selisihBiaya = $totalBudgetTabelKedua - $totalBiayaPlan; // Selisih biaya
                                    // Total biaya keseluruhan dari tabel pertama dan kedua
                                    $totalKeseluruhan = $totalBudgetTabelPertama + $totalBudgetTabelKedua;
                                @endphp

                                <!-- Bagian untuk tabel kedua, hanya ditampilkan jika ada data yang memenuhi syarat -->
                                @if ($filteredData->isNotEmpty())
                                    <tr>
                                        <th></th>
                                        <th colspan="8" style="text-align:right;">Sub Total 2: Rp
                                            {{ number_format($totalBudgetTabelKedua, 0, ',', '.') }}</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th colspan="8" style="text-align:right;">Total: Rp
                                            {{ number_format($totalBiayaPlan, 0, ',', '.') }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- Sticky Footer Actions -->
                    <div class="position-sticky bg-white p-3 shadow-lg z-3 border-top rounded-top-4 mt-5 d-flex justify-content-end gap-2" style="bottom: 60px;">
                        <a href="{{ route('indexPD') }}" class="btn btn-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        
                        @if ($data->contains('status_1', 2))
                            <button type="button" id="addRowBtn" class="btn btn-success rounded-pill px-4">
                                <i class="fas fa-plus-circle me-1"></i> Tambah Baris
                            </button>
                        @endif
                    </div>
                </form>
            </div>

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

        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
            const canEditSharingEvaluation = @json(
                app(\App\Services\HR\HRRoleAccessService::class)->hasFullAccess(auth()->user())
            );
            const displayNpk = (value) => {
                const npk = String(value ?? '').trim();
                return !npk || npk === '0' ? '-' : npk;
            };
            const employeeLabel = (user) => `${displayNpk(user?.npk)} — ${String(user?.name || '-').trim()}`;
            const participantUsers = (item) => {
                const source = item?.is_sharing_knowledge && Array.isArray(item.participants)
                    ? item.participants
                    : (item?.user ? [item.user] : []);
                const uniqueUsers = new Map();

                source.forEach((user) => {
                    const id = Number(user?.id);
                    if (id > 0 && !uniqueUsers.has(id)) {
                        uniqueUsers.set(id, user);
                    }
                });

                if (item?.is_sharing_knowledge && uniqueUsers.size === 0 && item?.user) {
                    uniqueUsers.set(Number(item.user.id), item.user);
                }

                return Array.from(uniqueUsers.values());
            };
            const employeeOptions = (item) => {
                const options = participantUsers(item)
                    .map((user) => `<option value="${Number(user.id)}" selected>${escapeHtml(employeeLabel(user))}</option>`)
                    .join('');

                return `<option value="">---- Pilih Karyawan ----</option>${options}`;
            };
            const employeeSelectAttributes = (item) => item?.is_sharing_knowledge
                ? `multiple size="${Math.min(Math.max(participantUsers(item).length, 2), 5)}" aria-label="Participant Sharing Knowledge"`
                : 'aria-label="Karyawan training"';
            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            // view tabel 1
            document.addEventListener('DOMContentLoaded', function() {
                var tableBody = document.getElementById('table-body');

                const updateEvaluasiRoute = "{{ route('update-evaluasi', ':id') }}";
                existingData.forEach(function(item, index) {
                    // Cek apakah tahun_usulan tidak ada (null, undefined, atau kosong)
                    if (!item.tahun_usulan) {
                        var newRow = document.createElement('tr');
                        newRow.id = `row-${item.id}`; // Set a unique ID for each row

                        var userOptions = employeeOptions(item);
                        var competencyOptions = '<option value="">---- Pilih Competency ----</option>';

                        if (item.competency) {
                            competencyOptions +=
                                `<option value="${item.competency}" selected>${item.competency}</option>`;
                        }

                        newRow.innerHTML = `
                                <td>${index + 1}</td>
                                <input type="hidden" id="modified_at" name="modified_at" value="${item.modified_at}" />
                                <input type="hidden" name="id[]" value="${item.id}" /> <!-- Hidden ID field -->
                                <td>
                                    <select class="section-dropdown" name="section_id[]" disabled>
                                        <option value="">---- Pilih Section ----</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}" ${item.section_id == {{ $section->id }} ? 'selected' : ''}>
                                                {{ $section->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select class="job-position-dropdown" name="id_job_position[]" disabled>
                                        <option value="">---- Pilih Job Position ----</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="user-dropdown" name="id_user[]" style="width: 250px" ${employeeSelectAttributes(item)} disabled>
                                        ${userOptions}
                                    </select>
                                </td>
                                <td><input type="text" id="program_training" name="program_training[]" value="${item.program_training}" placeholder="Program Training" disabled></td>
                                <td>
                                    <select name="kategori_competency[]" class="competency-category-dropdown" disabled>
                                        <option value="">---- Pilih Kategori Competency ----</option>
                                        <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                        <option value="softskill" ${item.kategori_competency == 'softskill' || item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Soft Skill</option>
                                        <option value="additional" ${item.kategori_competency == 'additional' ? 'selected' : ''}>Additional</option>
                                        <option value="Others" ${item.kategori_competency == 'Others' ? 'selected' : ''}>Others</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="competency[]" class="competency-dropdown" style="width: 300px" disabled>
                                        ${competencyOptions}
                                    </select>
                                </td>
                                <td><input type="date" id="due_date" name="due_date[]" value="${item.due_date}" disabled></td>
                                <td><input type="text" id="biaya" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget" disabled></td>
                                <td><input type="text" id="lembaga" name="lembaga[]" value="${item.lembaga}" placeholder="Lembaga" disabled></td>
                                <td><input type="text" id="keterangan_tujuan" name="keterangan_tujuan[]" value="${item.keterangan_tujuan}" placeholder="Keterangan Tujuan" disabled></td>
                                <td><textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>${item.objective_learning || '-'}</textarea></td>
                                <td><input type="text" id="program_training_plan" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan" disabled></td>
                                <td><input type="date" id="due_date_plan" name="due_date_plan[]" value="${item.due_date_plan || ''}" disabled></td>
                                <td><input type="text" id="lembaga_plan" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan" disabled></td>
                                <td><input type="text" id="keterangan_plan" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan" disabled></td>
                                <td style="min-width:150px;"><textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>${item.objective_learning_aktual || '-'}</textarea></td>
                                <td>
                                    <select name="status_2[]" class="status-dropdown1" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};" disabled>
                                        <option value="">Status Belum Tersedia</option>
                                        <option value="Mencari Vendor" ${item.status_2 == 'Mencari Vendor' ? 'selected' : ''}>Mencari Vendor</option>
                                        <option value="Proses Pendaftaran" ${item.status_2 == 'Proses Pendaftaran' ? 'selected' : ''}>Proses Pendaftaran</option>
                                        <option value="On Progress" ${item.status_2 == 'On Progress' ? 'selected' : ''}>On progress</option>
                                        <option value="Done" ${item.status_2 == 'Done' ? 'selected' : ''}>Done</option>
                                        <option value="Pending" ${item.status_2 == 'Pending' ? 'selected' : ''}>Pending</option>
                                        <option value="Ditolak" ${item.status_2 == 'Ditolak' ? 'selected' : ''}>Ditolak</option>
                                    </select>
                                </td>
                                 <td>
                                     ${
                                        item.status_2 === 'Done'
                                            && (!item.is_sharing_knowledge || canEditSharingEvaluation)
                                            ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn ${
                                                                                item.evaluation_completed ? 'btn-success' : 'btn-danger'
                                                                            } btn-sm">
                                                                                <i class="fas fa-file-alt"></i> <!-- Ikon form -->
                                                                                Evaluasi
                                                                            </a>`
                                            : ''
                                    }
                                </td>

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

                            var addedUsers = [];
                            var addedJobs = [];

                            jobPositions.forEach(function(jobPosition) {
                                if (jobPosition.section_id == selectedSectionId) {
                                    if (!addedJobs.includes(jobPosition.job_position)) {
                                        var jobOption = document.createElement('option');
                                        jobOption.value = jobPosition.id;
                                        jobOption.text = jobPosition.job_position;
                                        jobPositionDropdown.appendChild(jobOption);
                                        addedJobs.push(jobPosition.job_position);
                                    }

                                    if (jobPosition.active_users) {
                                        jobPosition.active_users.forEach(function(u) {
                                            if (!addedUsers.includes(u.id)) {
                                                var userOption = document.createElement('option');
                                                userOption.value = u.id;
                                                userOption.textContent = employeeLabel(u);
                                                userDropdown.appendChild(userOption);
                                                addedUsers.push(u.id);
                                            }
                                        });
                                    }
                                }
                            });

                            if (item.is_sharing_knowledge) {
                                userDropdown.innerHTML = employeeOptions(item);
                            }

                            if (item.id_job_position) {
                                jobPositionDropdown.value = item.id_job_position;
                            }

                            if (!item.is_sharing_knowledge && item.user) {
                                userDropdown.value = item.user.id;
                            }
                        });

                        // Memastikan data tersimpan ditampilkan ketika halaman dimuat
                        if (item.section_id) {
                            sectionDropdown.value = item.section_id;
                            sectionDropdown.dispatchEvent(new Event('change'));
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
                        var newEmployeeRow = document.createElement('tr');
                        var newEmployeeId = ''; // ID dibiarkan kosong karena akan autoincrement di database

                        var userOptionsList = employeeOptions(item);
                        var competencyOptionsList = '<option value="">---- Pilih Competency ----</option>';

                        if (item.competency) {
                            competencyOptionsList +=
                                `<option value="${item.competency}" selected>${item.competency}</option>`;
                        }

                        newEmployeeRow.innerHTML = `
                            <td>${index + 1}</td>
                            <input type="hidden" name="id[]" value="${newEmployeeId}" />
                            <input type="hidden" name="modified_at[]" value="${item.modified_at || ''}" />
                            <td>
                                <select class="employee-section-dropdown" name="section_id[]" disabled>
                                    <option value="">---- Pilih Section ----</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}" ${item.section_id == {{ $section->id }} ? 'selected' : ''}>{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="employee-job-position-dropdown" name="id_job_position[]" disabled>
                                    <option value="">---- Pilih Job Position ----</option>
                                </select>
                            </td>
                            <td>
                                <select class="employee-user-dropdown" name="id_user[]" style="width: 250px" ${employeeSelectAttributes(item)} disabled>
                                    ${userOptionsList}
                                </select>
                            </td>
                            <td><input type="text" name="program_training[]" value="${item.program_training || ''}" placeholder="Program Training" disabled></td>
                            <td>
                                <select name="kategori_competency[]" class="employee-competency-category-dropdown" disabled>
                                    <option value="">---- Pilih Kategori Competency ----</option>
                                    <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                    <option value="softskill" ${item.kategori_competency == 'softskill' || item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Soft Skill</option>
                                    <option value="additional" ${item.kategori_competency == 'additional' ? 'selected' : ''}>Additional</option>
                                    <option value="Others" ${item.kategori_competency == 'Others' ? 'selected' : ''}>Others</option>
                                </select>
                            </td>
                            <td>
                                <select name="competency[]" class="employee-competency-dropdown" style="width: 300px;" disabled>
                                    ${competencyOptionsList}
                                </select>
                            </td>
                            <td><input type="date" name="due_date[]" value="${item.due_date || ''}" disabled></td>
                            <td><input type="text" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget" disabled></td>
                            <td><input type="text" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga" disabled></td>
                            <td><input type="text" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan" disabled></td>
                            <td><textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>${item.objective_learning || '-'}</textarea></td>
                            <td><input type="text" id="program_training_plan" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan" disabled></td>
                            <td><input type="date" id="due_date_plan" name="due_date_plan[]" value="${item.due_date_plan || ''}" disabled></td>
                            <td><input type="text" id="lembaga_plan" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan" disabled></td>
                            <td><input type="text" id="keterangan_plan" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan" disabled></td>
                            <td style="min-width:150px;"><textarea class="form-control" style="min-width: 150px; white-space: normal; height: 80px;" disabled>${item.objective_learning_aktual || '-'}</textarea></td>
                            <td>
                                <select name="status_2[]" class="status-dropdown2" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};" disabled>
                                    <option value="">Status Belum Tersedia</option>
                                    <option value="Mencari Vendor" ${item.status_2 == 'Mencari Vendor' ? 'selected' : ''}>Mencari Vendor</option>
                                    <option value="Proses Pendaftaran" ${item.status_2 == 'Proses Pendaftaran' ? 'selected' : ''}>Proses Pendaftaran</option>
                                    <option value="On Progress" ${item.status_2 == 'On Progress' ? 'selected' : ''}>On progress</option>
                                    <option value="Done" ${item.status_2 == 'Done' ? 'selected' : ''}>Done</option>
                                    <option value="Pending" ${item.status_2 == 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Ditolak" ${item.status_2 == 'Ditolak' ? 'selected' : ''}>Ditolak</option>
                                </select>
                            </td>
                            <td>
                                     ${
                                        item.status_2 === 'Done'
                                            && (!item.is_sharing_knowledge || canEditSharingEvaluation)
                                            ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn ${
                                                                                        item.evaluation_completed ? 'btn-success' : 'btn-danger'
                                                                                    } btn-sm">
                                                                                        <i class="fas fa-file-alt"></i> <!-- Ikon form -->
                                                                                        Evaluasi
                                                                                    </a>`
                                            : ''
                                    }
                            </td>
                        `;

                        employeeTableBody.appendChild(newEmployeeRow);

                        // Event listener untuk dropdown section
                        var sectionDropdown = newEmployeeRow.querySelector('.employee-section-dropdown');
                        var jobPositionDropdown = newEmployeeRow.querySelector(
                            '.employee-job-position-dropdown');
                        var userDropdown = newEmployeeRow.querySelector('.employee-user-dropdown');
                        var competencyCategoryDropdown = newEmployeeRow.querySelector(
                            '.employee-competency-category-dropdown');
                        var competencyDropdown = newEmployeeRow.querySelector('.employee-competency-dropdown');

                        sectionDropdown.addEventListener('change', function() {
                            var selectedSectionId = parseInt(this.value);
                            jobPositionDropdown.innerHTML =
                                '<option value="">---- Pilih Job Position ----</option>';
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            var addedUsers = [];
                            var addedJobs = [];

                            jobPositions.forEach(function(jobPosition) {
                                if (jobPosition.section_id == selectedSectionId) {
                                    if (!addedJobs.includes(jobPosition.job_position)) {
                                        var jobOption = document.createElement('option');
                                        jobOption.value = jobPosition.id;
                                        jobOption.text = jobPosition.job_position;
                                        jobPositionDropdown.appendChild(jobOption);
                                        addedJobs.push(jobPosition.job_position);
                                    }

                                    if (jobPosition.active_users) {
                                        jobPosition.active_users.forEach(function(u) {
                                            if (!addedUsers.includes(u.id)) {
                                                var userOption = document.createElement('option');
                                                userOption.value = u.id;
                                                userOption.textContent = employeeLabel(u);
                                                userDropdown.appendChild(userOption);
                                                addedUsers.push(u.id);
                                            }
                                        });
                                    }
                                }
                            });

                            if (item.is_sharing_knowledge) {
                                userDropdown.innerHTML = employeeOptions(item);
                            }

                            // Set nilai default jika ada
                            if (item.id_job_position) {
                                jobPositionDropdown.value = item.id_job_position;
                            }

                            if (!item.is_sharing_knowledge && item.user) {
                                userDropdown.value = item.user.id;
                            }
                        });

                        // Memastikan data tersimpan ditampilkan ketika halaman dimuat
                        if (item.section_id) {
                            sectionDropdown.value = item.section_id;
                            sectionDropdown.dispatchEvent(new Event('change'));
                        }
                    }
                });
            });

            // add row
            document.addEventListener('DOMContentLoaded', function() {
                var employeeTableBody = document.getElementById('table-body2');
                var statusProgressDivs = {
                    'Mencari Vendor': document.getElementById('status-blue-percentage'),
                    'Proses Pendaftaran': document.getElementById('status-orange-percentage'),
                    'On Progress': document.getElementById('status-yellow-percentage'),
                    'Done': document.getElementById('status-green-percentage'),
                    'Pending': document.getElementById('status-gray-percentage'),
                    'Ditolak': document.getElementById('status-red-percentage')
                };

                function updateEmployeeStatusProgress() {
                    var totalEmployeeRows = employeeTableBody.querySelectorAll('tr').length;
                    if (totalEmployeeRows === 0) {
                        Object.values(statusProgressDivs).forEach(function(div) {
                            div.innerText = '0% (0 dari 0)';
                        });
                        return;
                    }

                    var statusRowCounts = {
                        'Mencari Vendor': 0,
                        'Proses Pendaftaran': 0,
                        'On Progress': 0,
                        'Done': 0,
                        'Pending': 0,
                        'Ditolak': 0
                    };

                    employeeTableBody.querySelectorAll('tr').forEach(function(row) {
                        var statusDropdown = row.querySelector('.status-dropdown');
                        if (statusDropdown) {
                            var status = statusDropdown.value;
                            if (statusRowCounts[status] !== undefined) {
                                statusRowCounts[status]++;
                            }
                        }
                    });

                    // Update percentages
                    for (var status in statusRowCounts) {
                        var percentage = (statusRowCounts[status] / totalEmployeeRows * 100).toFixed(2) + '%';
                        var countText = `${statusRowCounts[status]} dari ${totalEmployeeRows}`;
                        if (statusProgressDivs[status]) {
                            statusProgressDivs[status].innerText = `${percentage} (${countText})`;
                        }
                    }
                }

                function createEmployeeRow(item = {}) {
                    var newEmployeeId = ''; // ID dibiarkan kosong karena akan autoincrement di database

                    var userOptionsList = '<option value="">---- Pilih Karyawan ----</option>';
                    var competencyOptionsList = '<option value="">---- Pilih Competency ----</option>';

                    if (item.user) {
                        userOptionsList += `<option value="${Number(item.user.id)}" selected>${escapeHtml(employeeLabel(item.user))}</option>`;
                    }

                    if (item.competency) {
                        competencyOptionsList +=
                            `<option value="${item.competency}" selected>${item.competency}</option>`;
                    }

                    return `
                            <td>${employeeTableBody.rows.length + 1}</td>
                            <input type="hidden" name="id[]" value="${newEmployeeId}" />
                            <input type="hidden" name="modified_at[]" value="${item.modified_at || ''}" />
                            <td>
                                <select class="employee-section-dropdown" name="section_id[]">
                                    <option value="">---- Pilih Section ----</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section->id }}">{{ $section->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="employee-job-position-dropdown" name="id_job_position[]">
                                    <option value="">---- Pilih Job Position ----</option>
                                </select>
                            </td>
                            <td>
                                <select class="employee-user-dropdown" name="id_user[]" style="width: 250px">
                                    ${userOptionsList}
                                </select>
                            </td>
                            <td><input type="text" name="program_training[]" value="${item.program_training || ''}" placeholder="Program Training"></td>
                            <td>
                                <select name="kategori_competency[]" class="employee-competency-category-dropdown">
                                    <option value="">---- Pilih Kategori Competency ----</option>
                                    <option value="technical">Technical Competency</option>
                                    <option value="softskill">Soft Skill</option>
                                    <option value="additional">Additional</option>
                                </select>
                            </td>
                            <td>
                                <select name="competency[]" class="employee-competency-dropdown" style="width: 300px;">
                                    ${competencyOptionsList}
                                </select>
                            </td>
                            <td><input type="date" name="due_date[]" value="${item.due_date || ''}"></td>
                            <td><input type="text" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget"></td>
                            <td><input type="text" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga"></td>
                            <td>
                                <input type="text" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan">
                            </td>
                            <td>
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                            </td>
                        `;
                }

                function addEmployeeRow() {
                    var newEmployeeRow = document.createElement('tr');
                    newEmployeeRow.innerHTML = createEmployeeRow();
                    employeeTableBody.appendChild(newEmployeeRow);

                    // Set up event listeners for dropdowns
                    var sectionDropdown = newEmployeeRow.querySelector('.employee-section-dropdown');
                    var jobPositionDropdown = newEmployeeRow.querySelector('.employee-job-position-dropdown');
                    var userDropdown = newEmployeeRow.querySelector('.employee-user-dropdown');
                    var competencyCategoryDropdown = newEmployeeRow.querySelector(
                        '.employee-competency-category-dropdown');
                    var competencyDropdown = newEmployeeRow.querySelector('.employee-competency-dropdown');

                        sectionDropdown.addEventListener('change', function() {
                            var selectedSectionId = parseInt(this.value);
                            jobPositionDropdown.innerHTML =
                                '<option value="">---- Pilih Job Position ----</option>';
                            userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';

                            var addedUsers = [];
                            var addedJobs = [];

                            availableJobPositions.forEach(function(jobPosition) {
                                if (jobPosition.section_id == selectedSectionId) {
                                    if (!addedJobs.includes(jobPosition.job_position)) {
                                        var jobOption = document.createElement('option');
                                        jobOption.value = jobPosition.id;
                                        jobOption.text = jobPosition.job_position;
                                        jobPositionDropdown.appendChild(jobOption);
                                        addedJobs.push(jobPosition.job_position);
                                    }

                                    if (jobPosition.active_users) {
                                        jobPosition.active_users.forEach(function(u) {
                                            if (!addedUsers.includes(u.id)) {
                                                var userOption = document.createElement('option');
                                                userOption.value = u.id;
                                                userOption.textContent = employeeLabel(u);
                                                userDropdown.appendChild(userOption);
                                                addedUsers.push(u.id);
                                            }
                                        });
                                    }
                                }
                            });
                        });

                    competencyCategoryDropdown.addEventListener('change', function() {
                        var selectedCategory = this.value;
                        var selectedUserId = userDropdown.value;
                        var selectedJobPosition = jobPositionDropdown.value;
                        competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

                        if (selectedUserId && selectedCategory && selectedJobPosition) {
                            var addedCompetencies = [];
                            employeePenilaians.forEach(function(penilaian) {
                                if (penilaian.id_user == selectedUserId && penilaian.id_job_position == selectedJobPosition) {
                                    let optionText = '';
                                    if (selectedCategory === 'technical' && penilaian.id_tc) {
                                        optionText =
                                            `${penilaian.keterangan} - std: ${penilaian.nilai_standard} - aktual: ${penilaian.nilai_aktual}`;
                                    } else if ((selectedCategory === 'softskill' || selectedCategory === 'soft skill' || selectedCategory === 'nontechnical') && penilaian.id_sk) {
                                        optionText =
                                            `${penilaian.keterangan} - std: ${penilaian.nilai_standard} - aktual: ${penilaian.nilai_aktual}`;
                                    } else if (selectedCategory === 'additional' && penilaian.id_ad) {
                                        optionText =
                                            `${penilaian.keterangan} - std: ${penilaian.nilai_standard} - aktual: ${penilaian.nilai_aktual}`;
                                    }

                                    if (optionText !== '' && !addedCompetencies.includes(optionText)) {
                                        var option = document.createElement('option');
                                        option.value = optionText;
                                        option.text = optionText;
                                        competencyDropdown.appendChild(option);
                                        addedCompetencies.push(optionText);
                                    }
                                }
                            });
                        }
                    });
                }

                var addRowBtn = document.getElementById('addRowBtn');
                if (addRowBtn) {
                    addRowBtn.addEventListener('click', function() {
                        addEmployeeRow(); // Menambahkan baris kosong baru
                    });
                }

                // Tidak ada data awal yang dimuat
                // updateEmployeeStatusProgress() akan dipanggil saat ada perubahan status
            });

            $(document).ready(function() {
                $('#trainingForm').submit(function(event) {
                    event.preventDefault(); // Prevent default form submission

                    var formData = new FormData(this);

                    // Loop through each row in the table
                    $('#trainingForm tbody tr').each(function() {
                        var id = $(this).find('input[name="id[]"]')
                            .val(); // Check ID to determine if it's new or existing
                        var isNew = id === ""; // If ID is empty, it means the row is new

                        if (isNew) {
                            // New data - gather all necessary fields
                            formData.append('new_section_id[]', $(this).find('select[name="section_id[]"]')
                                .val());
                            formData.append('new_id_job_position[]', $(this).find(
                                'select[name="id_job_position[]"]').val());
                            formData.append('new_id_user[]', $(this).find('select[name="id_user[]"]')
                                .val());
                            formData.append('new_program_training[]', $(this).find(
                                'input[name="program_training[]"]').val());
                            formData.append('new_due_date[]', $(this).find('input[name="due_date[]"]')
                                .val());
                            formData.append('new_biaya[]', $(this).find('input[name="biaya[]"]').val());
                            formData.append('new_lembaga[]', $(this).find('input[name="lembaga[]"]')
                                .val());
                            formData.append('new_keterangan_tujuan[]', $(this).find(
                                'input[name="keterangan_tujuan[]"]').val());

                            // Additional fields from the new row
                            formData.append('new_kategori_competency[]', $(this).find(
                                'select[name="kategori_competency[]"]').val());
                            formData.append('new_competency[]', $(this).find(
                                'select[name="competency[]"]').val());
                        }
                    });

                    $.ajax({
                        url: $(this).attr('action'),
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Data berhasil diperbarui!',
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = "{{ route('indexPD') }}";
                                    }
                                });
                            } else {
                                Swal.fire('Error!', 'Terjadi kesalahan: ' + response.message, 'error');
                            }
                        },
                        error: function(xhr, status, error) {
                            Swal.fire('Error!', 'Terjadi kesalahan: ' + error, 'error');
                        }
                    });
                });
            });

            function updateRowNumbers() {
                var tableBody = document.getElementById('table-body');
                var rows = tableBody.getElementsByTagName('tr');
                for (var i = 0; i < rows.length; i++) {
                    rows[i].getElementsByTagName('td')[0].innerText = i + 1;
                }
            }

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
                    text: 'Black'
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
                dropdown.style.backgroundColor = getStatusColor(status);
                // Menambahkan ini untuk mengubah warna teks
                dropdown.style.color = getTextColor(status);

            }

            // Fungsi untuk memformat angka sebagai mata uang
            function formatCurrency(amount) {
                return amount.toLocaleString('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                });
            }
        </script>
    </main><!-- End #main -->
@endsection
