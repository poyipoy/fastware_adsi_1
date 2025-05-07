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
                <form id="trainingForm" action="{{ route('updateData') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('POST') <!-- Ini penting untuk override ke PUT -->
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
                                    <th scope="col">Biaya Actual</th>
                                    <th scope="col">Lembaga</th>
                                    <th scope="col">Keterangan</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Upload File</th>
                                    <th scope="col">Download</th>
                                    <th scope="col">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                <!-- Baris data akan diisi di sini -->
                            </tbody>
                            <tfoot>
                                @php
                                    // Hitung total budget hanya jika tahun_usulan kosong
                                    $totalBudget = $data
                                        ->filter(function ($item) {
                                            return empty($item->tahun_usulan);
                                        })
                                        ->sum('biaya');

                                    $totalBudget2 = $data
                                        ->filter(function ($item) {
                                            return empty($item->tahun_usulan);
                                        })
                                        ->sum('biaya_plan');
                                @endphp
                                <tr>
                                    <th></th>
                                    <th colspan="8" style="text-align:right;">Sub Total 1: Rp
                                        {{ number_format($totalBudget, 0, ',', '.') }}</th>

                                    <th colspan="5" style="text-align:right;">Sub Total Actual 1: Rp
                                        {{ number_format($totalBudget2, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
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
                                </tr>
                            </thead>
                            <tbody id="table-body2">
                                <h3><b>ADDITIONAL</b> <i class="fas fa-chevron-down"></i></h3>
                                <!-- Baris data akan diisi di sini -->
                            </tbody>
                            <tfoot>
                                @php
                                    // Hitung total budget dari tabel pertama
                                    $totalBudgetTabelPertama = $data->sum('biaya');

                                    // Hitung total budget, total biaya actual, dan selisih biaya untuk tabel kedua
                                    $filteredData = $data->filter(function ($item) {
                                        return !is_null($item->tahun_usulan);
                                    });

                                    // Hitung total budget dari tabel pertama
                                    $totalBudgetTabelPertama2 = $data->sum('biaya_plan');

                                    // Hitung total budget, total biaya actual, dan selisih biaya untuk tabel kedua
                                    $filteredData2 = $data->filter(function ($item) {
                                        return !is_null($item->tahun_usulan);
                                    });

                                    $totalBudgetTabelKedua = $filteredData->sum('biaya');

                                    $totalBudgetTabelKeduasub2 = $filteredData->sum('biaya_plan');

                                    $totalBiayaPlan = $filteredData->sum('biaya') + $totalBudgetTabelPertama;
                                    $totalBiayaPlan2 = $filteredData2->sum('biaya_plan') + $totalBudgetTabelPertama2;

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

                                        <th colspan="5" style="text-align:right;">Sub Total 2: Rp
                                            {{ number_format($totalBudgetTabelKeduasub2, 0, ',', '.') }}</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th colspan="8" style="text-align:right;">Total: Rp
                                            {{ number_format($totalBiayaPlan, 0, ',', '.') }}</th>
                                        <th colspan="5" style="text-align:right;">Total Actual: Rp
                                            {{ number_format($totalBiayaPlan2, 0, ',', '.') }}</th>
                                        <th colspan="2"></th>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-3 mb-4">
                        <a href="{{ route('indexPD2') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>

                        <button id="updateButton" type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Submit
                        </button>
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
                                <input type="hidden" id="modified_at" name="modified_at" value="${item.modified_at}" />
                                <input type="hidden" name="id[]" value="${item.id}" /> <!-- Hidden ID field -->
                                <td></td>
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
                                <td><input type="date" id="due_date" name="due_date[]" value="${item.due_date}"></td>
                                <td><input type="text" id="biaya" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget"></td>
                                <td><input type="text" id="lembaga" name="lembaga[]" value="${item.lembaga}" placeholder="Lembaga"></td>
                                <td><input type="text" id="keterangan_tujuan" name="keterangan_tujuan[]" value="${item.keterangan_tujuan}" placeholder="Keterangan Tujuan"></td>
                                <td><input type="text" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan"></td>
                                <td><input type="date" name="due_date_plan[]" value="${item.due_date_plan || ''}"></td>
                                <td><input type="text" name="biaya_plan[]" value="${item.biaya_plan || ''}" placeholder="Biaya Plan"></td>
                                <td><input type="text" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan"></td>
                                <td><input type="text" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan"></td>
                                <td>
                                    <select name="status_2[]" class="status-dropdown" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};">
                                        <option value=""> ----- Pilih Status ----- </option>
                                        <option value="Mencari Vendor" ${item.status_2 == 'Mencari Vendor' ? 'selected' : ''}>Mencari Vendor</option>
                                        <option value="Proses Pendaftaran" ${item.status_2 == 'Proses Pendaftaran' ? 'selected' : ''}>Proses Pendaftaran</option>
                                        <option value="On Progress" ${item.status_2 == 'On Progress' ? 'selected' : ''}>On progress</option>
                                        <option value="Done" ${item.status_2 == 'Done' ? 'selected' : ''}>Done</option>
                                        <option value="Pending" ${item.status_2 == 'Pending' ? 'selected' : ''}>Pending</option>
                                        <option value="Ditolak" ${item.status_2 == 'Ditolak' ? 'selected' : ''}>Ditolak</option>
                                    </select>
                                </td>
                                <td><input type="file" name="file[${item.id}]" accept=".pdf" ${item.status_2 !== 'Done' ? 'disabled' : ''}></td>
                                <td>${item.file ? `<a href="#" class="btn btn-primary" onclick="downloadPdf(${item.id})"><i class="bi bi-filetype-pdf fs-6"></i></a>` : 'No file'}</td>
                                <td>
                                     ${
                                        item.status_2 === 'Done'
                                            ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn ${
                                                item.diketahui ? 'btn-success' : 'btn-danger'
                                            } btn-sm">
                                                <i class="fas fa-eye"></i> 
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
                        newEmployeeRow.id = `row-${item.id}`; // Set a unique ID for each row

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
                            <input type="hidden" name="id[]" value="${item.id}" />
                            <input type="hidden" name="modified_at[]" value="${item.modified_at || ''}" />
                             <td></td>
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
                            <td><input type="date" name="due_date[]" value="${item.due_date || ''}" ></td>
                            <td><input type="text" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget"></td>
                            <td><input type="text" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga"></td>
                            <td><input type="text" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan"></td>
                            <td><input type="text" name="program_training_plan[]" value="${item.program_training_plan || ''}" placeholder="Nama Program Plan"></td>
                            <td><input type="date" name="due_date_plan[]" value="${item.due_date_plan || ''}"></td>
                            <td><input type="text" name="biaya_plan[]" value="${item.biaya_plan || ''}" placeholder="Biaya Plan"></td>
                            <td><input type="text" name="lembaga_plan[]" value="${item.lembaga_plan || ''}" placeholder="Lembaga Plan"></td>
                            <td><input type="text" name="keterangan_plan[]" value="${item.keterangan_plan || ''}" placeholder="Keterangan Plan"></td>
                            <td>
                                <select name="status_2[]" class="status-dropdown" onchange="updateDropdownColor(this); toggleFileUpload(this);" style="background-color: ${getStatusColor(item.status_2)}; color: ${getTextColor(item.status_2)};">
                                    <option value=""> ----- Pilih Status ----- </option>
                                    <option value="Mencari Vendor" ${item.status_2 == 'Mencari Vendor' ? 'selected' : ''}>Mencari Vendor</option>
                                    <option value="Proses Pendaftaran" ${item.status_2 == 'Proses Pendaftaran' ? 'selected' : ''}>Proses Pendaftaran</option>
                                    <option value="On Progress" ${item.status_2 == 'On Progress' ? 'selected' : ''}>On progress</option>
                                    <option value="Done" ${item.status_2 == 'Done' ? 'selected' : ''}>Done</option>
                                    <option value="Pending" ${item.status_2 == 'Pending' ? 'selected' : ''}>Pending</option>
                                    <option value="Ditolak" ${item.status_2 == 'Ditolak' ? 'selected' : ''}>Ditolak</option>
                                </select>
                            </td>
                            <td><input type="file" name="file[${item.id}]" accept=".pdf" ${item.status_2 !== 'Done' ? 'disabled' : ''}></td>
                            <td>${item.file ? `<a href="#" class="btn btn-primary" onclick="downloadPdf(${item.id})"><i class="bi bi-filetype-pdf fs-6"></i></a>` : 'No file'}</td>
                            <td>
                                ${
                                    item.status_2 === 'Done'
                                        ? `<a href="${updateEvaluasiRoute.replace(':id', item.id)}" class="btn ${
                                            item.diketahui ? 'btn-success' : 'btn-danger'
                                        } btn-sm">
                                            <i class="fas fa-eye"></i> 
                                            Evaluasi
                                        </a>`
                                        : ''
                                }
                            </td>
                            `;

                        employeeTableBody.appendChild(newEmployeeRow);

                        var sectionDropdown = newEmployeeRow.querySelector('.employee-section-dropdown');
                        var jobPositionDropdown = newEmployeeRow.querySelector(
                            '.employee-job-position-dropdown');
                        var userDropdown = newEmployeeRow.querySelector('.employee-user-dropdown');

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

                        if (item.section) {
                            sectionDropdown.value = item.section;
                            sectionDropdown.dispatchEvent(new Event('change'));
                        }
                    }
                });
            });


            $(document).ready(function() {
                $('#updateButton').on('click', function(e) {
                    e.preventDefault();
                    var formData = [];
                    var hasError = false;

                    // Get data from first table (table-body)
                    $('#table-body tr').each(function() {
                        var row = $(this);
                        var rowData = {
                            id: row.find('input[name="id[]"]').val(),
                            due_date: row.find('input[name="due_date[]"]').val().trim(),
                            biaya: row.find('input[name="biaya[]"]').val().trim() || '0',
                            lembaga: row.find('input[name="lembaga[]"]').val().trim(),
                            keterangan_tujuan: row.find('input[name="keterangan_tujuan[]"]').val()
                                .trim(),
                            program_training_plan: row.find('input[name="program_training_plan[]"]')
                                .val().trim(),
                            due_date_plan: row.find('input[name="due_date_plan[]"]').val().trim(),
                            biaya_plan: row.find('input[name="biaya_plan[]"]').val().trim() || '0',
                            lembaga_plan: row.find('input[name="lembaga_plan[]"]').val().trim(),
                            keterangan_plan: row.find('input[name="keterangan_plan[]"]').val()
                                .trim(),
                            status_2: row.find('select[name="status_2[]"]').val()
                        };
                        formData.push(rowData);
                    });

                    // Get data from second table (table-body2)
                    $('#table-body2 tr').each(function() {
                        var row = $(this);
                        var rowData = {
                            id: row.find('input[name="id[]"]').val(),
                            due_date: row.find('input[name="due_date[]"]').val().trim(),
                            biaya: row.find('input[name="biaya[]"]').val().trim() || '0',
                            lembaga: row.find('input[name="lembaga[]"]').val().trim(),
                            keterangan_tujuan: row.find('input[name="keterangan_tujuan[]"]').val()
                                .trim(),
                            program_training_plan: row.find('input[name="program_training_plan[]"]')
                                .val().trim(),
                            due_date_plan: row.find('input[name="due_date_plan[]"]').val().trim(),
                            biaya_plan: row.find('input[name="biaya_plan[]"]').val().trim() || '0',
                            lembaga_plan: row.find('input[name="lembaga_plan[]"]').val().trim(),
                            keterangan_plan: row.find('input[name="keterangan_plan[]"]').val()
                                .trim(),
                            status_2: row.find('select[name="status_2[]"]').val()
                        };
                        formData.push(rowData);
                    });

                    if (hasError) {
                        return;
                    }

                    var formDataObject = new FormData();
                    formDataObject.append('_token', $('meta[name="csrf-token"]').attr('content'));
                    formDataObject.append('data', JSON.stringify(formData));

                    // Append files from both tables
                    $('#table-body tr, #table-body2 tr').each(function(index) {
                        var row = $(this);
                        var id = row.find('input[name="id[]"]').val();
                        var fileInput = row.find('input[type="file"]')[0];
                        if (fileInput && fileInput.files[0]) {
                            formDataObject.append('file[' + id + ']', fileInput.files[0]);
                        }
                    });

                    $.ajax({
                        url: '{{ route('updateData') }}',
                        type: 'POST',
                        processData: false,
                        contentType: false,
                        data: formDataObject,
                        success: function(response) {
                            console.log("Data berhasil diperbarui");
                            alert("Data berhasil diperbarui");
                            location.reload();
                        },
                        error: function(xhr, status, error) {
                            console.log("Terjadi kesalahan: ", xhr.responseJSON);
                            alert("Terjadi kesalahan saat memperbarui data: " +
                                (xhr.responseJSON?.error || error));
                        }
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
