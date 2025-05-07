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
        </style>
        <div class="pagetitle">
            <h1>Halaman Tambah Data Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu Tambah Training</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                <!-- Card untuk Departemen dan Nama -->
                <div class="card">
                    <!-- Tabel untuk edit data -->
                    <button id="add-row" class="btn btn-primary" style="width: 15%">Tambah Baris</button>
                    <form id="trainingForm" method="POST" action="{{ route('updatePD') }}">
                        @csrf
                        @method('PUT')
                        <div class="table-container" style="overflow-x:auto;">
                            <table class="styled-table" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th scope="col">NO</th>
                                        <th scope="col">Section</th>
                                        <th scope="col">Job Position</th>
                                        <th scope="col">Nama Karyawan</th>
                                        <th scope="col">Program Training</th>
                                        <th scope="col">Kategori Competency</th>
                                        <th scope="col">Competency</th>
                                        <th scope="col">Due Date</th>
                                        <th scope="col">Lembaga</th>
                                        <th scope="col">Keterangan Tujuan</th>
                                    </tr>
                                </thead>
                                <tbody id="table-body">
                                    @foreach ($data as $item)
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 3%">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('indexPD') }}" class="btn btn-secondary">Back</a>
                        </div>
                    </form>
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
            var penilaians = @json($penilaians);

            document.addEventListener('DOMContentLoaded', function() {
                var tableBody = document.getElementById('table-body');

                // Fungsi untuk menambahkan baris baru
                function addRow(item = {}) {
                    var newRow = document.createElement('tr');
                    var newId = item.id || Date.now(); // Generate a unique ID based on timestamp
                    newRow.id = `row-${newId}`;

                    var userOptions = '<option value="">---- Pilih Karyawan ----</option>';
                    var competencyOptions = '<option value="">---- Pilih Competency ----</option>';

                    if (item.user) {
                        userOptions += `<option value="${item.user.id}" selected>${item.user.name}</option>`;
                    }

                    if (item.competency) {
                        competencyOptions += `<option value="${item.competency}" selected>${item.competency}</option>`;
                    }

                    newRow.innerHTML = `
                        <td>${tableBody.rows.length + 1}</td>

                        <input type="hidden" id="modified_at" name="modified_at" value="${item.modified_at || ''}" />
                        <input type="hidden" name="id[]" value="${newId}" />

                        <td>
                            <select class="section-dropdown" name="section[]">
                                <option value="">---- Pilih Section ----</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section }}" ${item.section == '{{ $section }}' ? 'selected' : ''}>
                                        {{ $section }}
                                    </option>
                                @endforeach
                            </select>
                        </td>

                        <td>
                            <select class="job-position-dropdown" name="id_job_position[]">
                                <option value="">---- Pilih Job Position ----</option>
                            </select>
                        </td>

                        <td>
                            <select class="user-dropdown" name="id_user[]">
                                ${userOptions}
                            </select>
                        </td>

                        <td><input type="text" id="program_training" name="program_training[]" value="${item.program_training || ''}" placeholder="Program Training"></td>

                        <td>
                            <select name="kategori_competency[]" class="competency-category-dropdown">
                                <option value="">---- Pilih Kategori Competency ----</option>
                                <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                <option value="nontechnical" ${item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Non Technical</option>
                                <option value="additional" ${item.kategori_competency == 'additional' ? 'selected' : ''}>Additional</option>
                                <option value="Others" ${item.kategori_competency == 'Others' ? 'selected' : ''}>Others</option>
                            </select>
                        </td>

                        <td>
                            <select name="competency[]" class="competency-dropdown" style="width: 250px;">
                                ${competencyOptions}
                            </select>
                        </td>

                        <td><input type="date" id="due_date" name="due_date[]" value="${item.due_date || ''}"></td>
                        <td><input type="text" id="lembaga" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga"></td>
                        <td><input type="text" id="keterangan_tujuan" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan"></td>
                        `;

                    tableBody.appendChild(newRow);

                    var sectionDropdown = newRow.querySelector('.section-dropdown');
                    var jobPositionDropdown = newRow.querySelector('.job-position-dropdown');
                    var userDropdown = newRow.querySelector('.user-dropdown');
                    var competencyCategoryDropdown = newRow.querySelector('.competency-category-dropdown');
                    var competencyDropdown = newRow.querySelector('.competency-dropdown');

                    // Handle section change and populate job position and user
                    sectionDropdown.addEventListener('change', function() {
                        var selectedSection = this.value;
                        jobPositionDropdown.innerHTML =
                        '<option value="">---- Pilih Job Position ----</option>';
                        userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';

                        var addedUsers = [];

                        jobPositions.forEach(function(jobPosition) {
                            if (jobPosition.user && jobPosition.user.section === selectedSection) {
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

                    // Handle competency category change and populate competencies
                    competencyCategoryDropdown.addEventListener('change', function() {
                        var selectedCategory = this.value;
                        var selectedUserId = userDropdown.value;

                        competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

                        if (selectedUserId && selectedCategory) {
                            var addedCompetencies = [];

                            penilaians.forEach(function(penilaian) {
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

                            if (item.competency) {
                                competencyDropdown.value = item.competency;
                            }
                        }
                    });

                    // Populate the fields with previously saved data
                    if (item.section) {
                        sectionDropdown.value = item.section;
                        setTimeout(function() {
                            sectionDropdown.dispatchEvent(new Event('change'));
                        }, 100);
                    }

                    if (item.kategori_competency) {
                        competencyCategoryDropdown.value = item.kategori_competency;
                        setTimeout(function() {
                            competencyCategoryDropdown.dispatchEvent(new Event('change'));
                        }, 100);
                    }
                }

                // Load existing data
                existingData.forEach(function(item) {
                    addRow(item);
                });

                // Add a new row when 'add-row' button is clicked
                document.getElementById('add-row').addEventListener('click', function() {
                    addRow(); // Add an empty row
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
