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
            <!-- Card untuk Departemen dan Nama -->
            <div class="card">
                <!-- Tabel untuk edit data -->
                <div class="table-container" style="overflow-x:auto;">
                    <p style="color: red">*scroll kesamping dalam pengisian data</p>

                    <table class="styled-table" style="width:100%;">
                        <thead>
                            <tr>
                                <th scope="col" rowspan="2">NO</th>
                                <th scope="col" rowspan="2">Section</th>
                                <th scope="col" rowspan="2">Job Position</th>
                                <th scope="col" rowspan="2">Nama Karyawan</th>
                                <th scope="col" rowspan="2">Program Training</th>
                                <th scope="col" rowspan="2">Kategori Competency</th>
                                <th scope="col" rowspan="2">Competency</th>
                                <th scope="col" rowspan="2">Due Date</th>
                                <th scope="col" rowspan="2">Budget</th>
                                <th scope="col" rowspan="2">Lembaga</th>
                                <th scope="col" rowspan="2">Keterangan Tujuan</th>
                            </tr>
                            <tr style="background-color: #f0ad4e;">
                                <th scope="col">Nama Program</th>
                                <th scope="col">Date Actual</th>
                                <th scope="col">Lembaga</th>
                                <th scope="col">Keterangan</th>
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
                                    <th scope="col" rowspan="2">Nama Karyawan</th>
                                    <th scope="col" rowspan="2">Program Training</th>
                                    <th scope="col" rowspan="2">Kategori Competency</th>
                                    <th scope="col" rowspan="2">Competency</th>
                                    <th scope="col" rowspan="2">Due Date</th>
                                    <th scope="col" rowspan="2">Budget</th>
                                    <th scope="col" rowspan="2">Lembaga</th>
                                    <th scope="col" rowspan="2">Keterangan Tujuan</th>
                                </tr>
                                <tr hidden style="background-color: #f0ad4e;">
                                    <th scope="col">Nama Program</th>
                                    <th scope="col">Date Actual</th>
                                    <th scope="col">Lembaga</th>
                                    <th scope="col">Keterangan</th>
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
                                    // Hitung total budget dari tabel pertama
                                    $totalBudgetTabelPertama = $data->sum(function ($item) {
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
                <div class="mt-3 mb-4">
                    @if ($data->contains('status_1', 2))
                        <button type="button" id="addRowBtn" class="btn btn-success">
                            <i class="fas fa-plus"></i> Tambah Baris
                        </button>
                    @endif

                    <a href="{{ route('indexPD') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
                </form>
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

            // view tabel 1
            document.addEventListener('DOMContentLoaded', function() {
                var tableBody = document.getElementById('table-body');

                const updateEvaluasiRoute = "{{ route('update-evaluasi', ':id') }}";
                existingData.forEach(function(item, index) {
                    // Cek apakah tahun_usulan tidak ada (null, undefined, atau kosong)
                    if (!item.tahun_usulan) {
                        var newRow = document.createElement('tr');
                        newRow.id = `row-${item.id}`; // Set a unique ID for each row

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

                        newRow.innerHTML = `
                                <td>${index + 1}</td>
                                <input type="hidden" id="modified_at" name="modified_at" value="${item.modified_at}" />
                                <input type="hidden" name="id[]" value="${item.id}" /> <!-- Hidden ID field -->
                                <td>
                                    <select class="section-dropdown" name="section[]" disabled>
                                        <option value="">---- Pilih Section ----</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section }}" ${item.section == '{{ $section }}' ? 'selected' : ''}>
                                                {{ $section }}
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
                                    <select class="user-dropdown" name="id_user[]" style="width: 250px" disabled>
                                        ${userOptions}
                                    </select>
                                </td>
                                <td><input type="text" id="program_training" name="program_training[]" value="${item.program_training}" placeholder="Program Training" disabled></td>
                                <td>
                                    <select name="kategori_competency[]" class="competency-category-dropdown" disabled>
                                        <option value="">---- Pilih Kategori Competency ----</option>
                                        <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                        <option value="nontechnical" ${item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Non Technical</option>
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
                                <td><input type="text" id="program_training_plan" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan" disabled></td>
                                <td><input type="date" id="due_date_plan" name="due_date_plan[]" value="${item.due_date_plan || ''}" disabled></td>
                                <td><input type="text" id="lembaga_plan" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan" disabled></td>
                                <td><input type="text" id="keterangan_plan" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan" disabled></td>
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
                                            ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn ${
                                                                                item.diketahui ? 'btn-success' : 'btn-danger'
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
                            var selectedSection = this.value;
                            jobPositionDropdown.innerHTML =
                                '<option value="">---- Pilih Job Position ----</option>';
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            var addedUsers = [];

                            jobPositions.forEach(function(jobPosition) {
                                if (jobPosition.user && jobPosition.user.section ===
                                    selectedSection) {
                                    var jobOption = document.createElement('option');
                                    jobOption.value = jobPosition.job_position;
                                    jobOption.text = jobPosition.job_position;
                                    jobPositionDropdown.appendChild(jobOption);

                                    if (!addedUsers.includes(jobPosition.user.id)) {
                                        var userOption = document.createElement('option');
                                        userOption.value = jobPosition.user.id;
                                        userOption.text = jobPosition.user.name;
                                        userDropdown.appendChild(userOption);
                                        addedUsers.push(jobPosition.user.id);
                                    }
                                }
                            });

                            if (item.id_job_position) {
                                jobPositionDropdown.value = item.id_job_position;
                            }

                            if (item.user) {
                                userDropdown.value = item.user.id;
                            }
                        });

                        // Memastikan data tersimpan ditampilkan ketika halaman dimuat
                        if (item.section) {
                            sectionDropdown.value = item.section;
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

                        newEmployeeRow.innerHTML = `
                            <td>${index + 1}</td>
                            <input type="hidden" name="id[]" value="${newEmployeeId}" />
                            <input type="hidden" name="modified_at[]" value="${item.modified_at || ''}" />
                            <td>
                                <select class="employee-section-dropdown" name="section[]" disabled>
                                    <option value="">---- Pilih Section ----</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section }}" ${item.section == '{{ $section }}' ? 'selected' : ''}>{{ $section }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select class="employee-job-position-dropdown" name="id_job_position[]" disabled>
                                    <option value="">---- Pilih Job Position ----</option>
                                </select>
                            </td>
                            <td>
                                <select class="employee-user-dropdown" name="id_user[]" style="width: 250px" disabled>
                                    ${userOptionsList}
                                </select>
                            </td>
                            <td><input type="text" name="program_training[]" value="${item.program_training || ''}" placeholder="Program Training" disabled></td>
                            <td>
                                <select name="kategori_competency[]" class="employee-competency-category-dropdown" disabled>
                                    <option value="">---- Pilih Kategori Competency ----</option>
                                    <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                    <option value="nontechnical" ${item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Non Technical</option>
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
                            <td><input type="text" id="program_training_plan" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan" disabled></td>
                            <td><input type="date" id="due_date_plan" name="due_date_plan[]" value="${item.due_date_plan || ''}" disabled></td>
                            <td><input type="text" id="lembaga_plan" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan" disabled></td>
                            <td><input type="text" id="keterangan_plan" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan" disabled></td>
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
                                            ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn ${
                                                                                        item.diketahui ? 'btn-success' : 'btn-danger'
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
                            var selectedSection = this.value;
                            jobPositionDropdown.innerHTML =
                                '<option value="">---- Pilih Job Position ----</option>';
                            userDropdown.innerHTML =
                                '<option value="">---- Pilih Karyawan ----</option>';

                            var addedUsers = [];

                            jobPositions.forEach(function(jobPosition) {
                                if (jobPosition.user && jobPosition.user.section ===
                                    selectedSection) {
                                    var jobOption = document.createElement('option');
                                    jobOption.value = jobPosition.job_position;
                                    jobOption.text = jobPosition.job_position;
                                    jobPositionDropdown.appendChild(jobOption);

                                    if (!addedUsers.includes(jobPosition.user.id)) {
                                        var userOption = document.createElement('option');
                                        userOption.value = jobPosition.user.id;
                                        userOption.text = jobPosition.user.name;
                                        userDropdown.appendChild(userOption);
                                        addedUsers.push(jobPosition.user.id);
                                    }
                                }
                            });

                            // Set nilai default jika ada
                            if (item.id_job_position) {
                                jobPositionDropdown.value = item.id_job_position;
                            }

                            if (item.user) {
                                userDropdown.value = item.user.id;
                            }
                        });

                        // Memastikan data tersimpan ditampilkan ketika halaman dimuat
                        if (item.section) {
                            sectionDropdown.value = item.section;
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
                        userOptionsList += `<option value="${item.user.id}" selected>${item.user.name}</option>`;
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
                                <select class="employee-section-dropdown" name="section[]">
                                    <option value="">---- Pilih Section ----</option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section }}">{{ $section }}</option>
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
                                    <option value="nontechnical">Non Technical</option>
                                    <option value="additional">Additional</option>
                                    <option value="Others">Others</option>
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
                        var selectedSection = this.value;
                        jobPositionDropdown.innerHTML =
                            '<option value="">---- Pilih Job Position ----</option>';
                        userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';

                        var addedUsers = [];

                        availableJobPositions.forEach(function(jobPosition) {
                            if (jobPosition.user.section === selectedSection) {
                                var jobOption = document.createElement('option');
                                jobOption.value = jobPosition.job_position;
                                jobOption.text = jobPosition.job_position;
                                jobPositionDropdown.appendChild(jobOption);

                                if (!addedUsers.includes(jobPosition.user.id)) {
                                    var userOption = document.createElement('option');
                                    userOption.value = jobPosition.user.id;
                                    userOption.text = jobPosition.user.name;
                                    userDropdown.appendChild(userOption);
                                    addedUsers.push(jobPosition.user.id);
                                }
                            }
                        });
                    });

                    competencyCategoryDropdown.addEventListener('change', function() {
                        var selectedCategory = this.value;
                        var selectedUserId = userDropdown.value;
                        competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

                        if (selectedUserId && selectedCategory) {
                            var addedCompetencies = [];
                            employeePenilaians.forEach(function(penilaian) {
                                if (penilaian.id_user == selectedUserId) {
                                    let optionText = '';
                                    if (selectedCategory === 'technical' && penilaian.id_tc) {
                                        optionText =
                                            `${penilaian.keterangan} - std: ${penilaian.nilai_standard} - aktual: ${penilaian.nilai_aktual}`;
                                    } else if (selectedCategory === 'nontechnical' && penilaian.id_sk) {
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

                document.getElementById('addRowBtn').addEventListener('click', function() {
                    addEmployeeRow(); // Menambahkan baris kosong baru
                });

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
                            formData.append('new_section[]', $(this).find('select[name="section[]"]')
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
                            formData.append('new_keterangan[]', $(this).find(
                                'input[name="keterangan_tujuan[]"]').val());

                            // Additional fields from the new row
                            formData.append('new_kategori_competency[]', $(this).find(
                                'select[name="kategori_competency[]"]').val());
                            formData.append('new_competency[]', $(this).find(
                                'select[name="competency[]"]').val());
                            formData.append('new_due_date_plan[]', $(this).find(
                                'input[name="due_date_plan[]"]').val());
                            formData.append('new_biaya_plan[]', $(this).find(
                                'input[name="biaya_plan[]"]').val());
                            formData.append('new_lembaga_plan[]', $(this).find(
                                'input[name="lembaga_plan[]"]').val());
                            formData.append('new_keterangan_plan[]', $(this).find(
                                'input[name="keterangan_plan[]"]').val());
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
                                alert('Data berhasil diperbarui!');
                                window.location.href = "{{ route('indexPD') }}";
                            } else {
                                alert('Terjadi kesalahan: ' + response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Terjadi kesalahan: ' + error);
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
