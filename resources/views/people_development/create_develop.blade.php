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
                    <div class="card-content">
                        <div class="form-group">
                            <label for="department">Departemen:
                                <span id="department">
                                    @php
                                        $roleId = auth()->user()->role_id; // Get the role_id of the logged-in user
                                        switch ($roleId) {
                                            case 11:
                                                echo 'Fin, Acc, Proc, HRGA & IT';
                                                break;
                                            case 7:
                                                echo 'Logistics';
                                                break;
                                            case 5:
                                                echo 'Production';
                                                break;
                                            case 2:
                                                echo 'Sales';
                                                break;
                                            case 14:
                                                echo 'PDCA, Procurement, Inventory, IT';
                                                break;
                                            default:
                                                echo 'Unknown Department'; // Fallback for any undefined roles
                                                break;
                                        }
                                    @endphp
                                </span>
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="name">Nama: <span id="name">{{ auth()->user()->name }}</span></label>
                        </div>

                    </div>
                    <button id="add-row-btn" style="width: 10%; margin-top:3%">Tambah baris</button>

                    <!-- Tabel di dalam card -->
                    <form id="trainingForm" method="POST" action="{{ route('savePdPengajuan') }}">
                        @csrf
                        <div class="table-container" style="overflow-x:auto;">
                            <p style="color: red">*scroll kesamping dalam pengisian data</p>
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
                                    <!-- Data Rows -->
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 3%">
                            <button type="submit" class="btn btn-primary">Submit</button>
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
            document.getElementById('add-row-btn').addEventListener('click', function() {
                var tableBody = document.getElementById('table-body');

                var newRow = document.createElement('tr');

                newRow.innerHTML = `
                    <td></td>
                    <td>
                        <select class="section-dropdown" name="section[]">
                            <option value="">---- Pilih Section ----</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section }}">{{ $section }}</option>
                            @endforeach
                        </select>
                    </td>

                    <!-- Job Position dropdown -->
                    <td>
                        <select class="job-position-dropdown" name="id_job_position[]">
                            <option value="">---- Pilih Job Position ----</option>
                        </select>
                    </td>

                    <!-- User dropdown -->
                    <td>
                        <select class="user-dropdown" name="id_user[]">
                            <option value="">---- Pilih Karyawan ----</option>
                        </select>
                    </td>

                    <td><input type="text" id="program_training" name="program_training[]" placeholder="Program Training"></td> 

                    <td>
                        <select name="kategori_competency[]" class="competency-category-dropdown"> 
                            <option value="">---- Pilih Kategori Competency ----</option>
                            <option value="technical">Technical Competency</option>
                            <option value="nontechnical">Non Technical</option>
                            <option value="additional">Additional</option>
                            <option value="Others">Others</option>
                        </select>
                    </td>

                    <td>
                        <select name="competency[]" class="competency-dropdown">
                            <option value="">---- Pilih Competency ----</option>
                        </select>
                    </td>
                    <td><input type="date" id="due_date" name="due_date[]" ></td> 
                    <td><input type="text" id="lembaga" name="lembaga[]" placeholder="Lembaga" ></td>
                    <td><input type="text" id="keterangan_tujuan" name="keterangan_tujuan[]" placeholder="Keterangan Tujuan"></td>
                `;

                tableBody.appendChild(newRow);
                updateRowNumbers();

                var sectionDropdown = newRow.querySelector('.section-dropdown');
                var jobPositionDropdown = newRow.querySelector('.job-position-dropdown');
                var userDropdown = newRow.querySelector('.user-dropdown');
                var competencyCategoryDropdown = newRow.querySelector('.competency-category-dropdown');
                var competencyDropdown = newRow.querySelector('.competency-dropdown');

                var allJobPositions = @json($jobPositions);
                var allPenilaians = @json($penilaians);

                // Track selected user for competency filtering
                var currentSelectedUserId = null;

                // Section change → populate unique Job Positions
                sectionDropdown.addEventListener('change', function() {
                    var selectedSection = this.value;

                    // Reset dropdowns
                    jobPositionDropdown.innerHTML = '<option value="">---- Pilih Job Position ----</option>';
                    userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    currentSelectedUserId = null;

                    if (!selectedSection) return;

                    // Filter job positions by section, unique by job_position name
                    var uniqueJobs = [];
                    allJobPositions.forEach(function(jp) {
                        if (jp.user && jp.user.section === selectedSection && !uniqueJobs.includes(jp.job_position)) {
                            uniqueJobs.push(jp.job_position);
                            var option = document.createElement('option');
                            option.value = jp.job_position;
                            option.text = jp.job_position;
                            jobPositionDropdown.appendChild(option);
                        }
                    });
                });

                // Job Position change → populate Users yang punya job position tersebut
                jobPositionDropdown.addEventListener('change', function() {
                    var selectedJobPosition = this.value;
                    var selectedSection = sectionDropdown.value;

                    // Reset user & competency
                    userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    currentSelectedUserId = null;

                    if (!selectedJobPosition) return;

                    // Filter users by section + job_position, unique by user id
                    var uniqueUserIds = [];
                    allJobPositions.forEach(function(jp) {
                        if (jp.user && jp.user.section === selectedSection && jp.job_position === selectedJobPosition) {
                            if (!uniqueUserIds.includes(jp.user.id)) {
                                uniqueUserIds.push(jp.user.id);
                                var option = document.createElement('option');
                                option.value = jp.user.id;
                                option.text = jp.user.name;
                                userDropdown.appendChild(option);
                            }
                        }
                    });
                });

                // User change → update selected user for competency
                userDropdown.addEventListener('change', function() {
                    currentSelectedUserId = this.value;
                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    // Re-populate competency if category already selected
                    populateCompetency();
                });

                // Competency Category change → populate competencies
                competencyCategoryDropdown.addEventListener('change', function() {
                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    populateCompetency();
                });

                function populateCompetency() {
                    var selectedCategory = competencyCategoryDropdown.value;
                    if (!currentSelectedUserId || !selectedCategory) return;

                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

                    allPenilaians.forEach(function(penilaian) {
                        if (penilaian.id_user == currentSelectedUserId) {
                            var optionText = '';

                            if (selectedCategory === 'technical' && penilaian.id_tc) {
                                optionText = penilaian.keterangan + ' - std: ' + penilaian.nilai_standard + ' - aktual: ' + penilaian.nilai_aktual;
                            } else if (selectedCategory === 'nontechnical' && penilaian.id_sk) {
                                optionText = penilaian.keterangan + ' - std: ' + penilaian.nilai_standard + ' - aktual: ' + penilaian.nilai_aktual;
                            } else if (selectedCategory === 'additional' && penilaian.id_ad) {
                                optionText = penilaian.keterangan + ' - std: ' + penilaian.nilai_standard + ' - aktual: ' + penilaian.nilai_aktual;
                            }

                            if (optionText !== '') {
                                var option = document.createElement('option');
                                option.value = optionText;
                                option.text = optionText;
                                competencyDropdown.appendChild(option);
                            }
                        }
                    });
                }
            });

            // Function to update row numbers
            function updateRowNumbers() {
                var rows = document.querySelectorAll('#table-body tr');
                rows.forEach(function(row, index) {
                    row.cells[0].innerText = index + 1;
                });
            }
        </script>
    </main><!-- End #main -->
@endsection
