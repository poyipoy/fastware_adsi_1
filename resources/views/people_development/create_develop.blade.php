@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">
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

            /* Efek Animasi Kartu */
            .dynamic-card {
                opacity: 0;
                transform: translateY(20px);
                animation: slideUpFade 0.4s ease forwards;
                border: none;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
            }

            .dynamic-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            }

            @keyframes slideUpFade {
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Tombol Tambah Baris */
            .btn-add-row {
                width: 100%;
                padding: 15px;
                background-color: transparent;
                border: 2px dashed #0d6efd;
                color: #0d6efd;
                border-radius: 12px;
                font-weight: 600;
                transition: all 0.3s ease;
                margin-bottom: 30px;
            }

            .btn-add-row:hover {
                background-color: rgba(13, 110, 253, 0.05);
                transform: scale(1.01);
            }

            /* Form Floating Enhancements */
            .form-floating > .form-control:focus,
            .form-floating > .form-select:focus {
                border-color: #0d6efd;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            }
            
            .card-header-custom {
                background-color: #ffffff;
                border-bottom: 1px solid #f0f0f0;
                border-radius: 12px 12px 0 0 !important;
                padding: 15px 20px;
                cursor: pointer;
                user-select: none;
            }
            .toggle-icon {
                transition: transform 0.2s ease;
            }
            .card-collapsed .toggle-icon {
                transform: rotate(-90deg);
            }
            .row-summary-badge {
                font-weight: 500;
                font-size: 13px;
                background-color: #f8f9fa;
                border: 1px solid #dee2e6;
                color: #212529;
                padding: 6px 14px;
                border-radius: 20px;
                max-width: 450px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        </style>
        <div class="pagetitle">
            <h1>Halaman Tambah Data Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('indexPD') }}">People Development</a></li>
                    <li class="breadcrumb-item active">Tambah Training</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                <!-- Profile Section -->
                <div class="profile-card">
                    <div class="profile-avatar">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="profile-info">
                        <h4>{{ auth()->user()->name }}</h4>
                        <p>
                            <i class="fas fa-building me-1"></i> Departemen: 
                            @php
                                $userDept = \Illuminate\Support\Facades\DB::table('user_job_positions')
                                    ->join('mst_job_positions', 'user_job_positions.mst_job_position_id', '=', 'mst_job_positions.id')
                                    ->join('mst_departments', 'mst_job_positions.department_id', '=', 'mst_departments.id')
                                    ->where('user_job_positions.user_id', auth()->user()->id)
                                    ->value('mst_departments.name');
                                    
                                echo $userDept ?: 'Unknown Department';
                            @endphp
                        </p>
                    </div>
                </div>

                <form id="trainingForm" method="POST" action="{{ route('savePdPengajuan') }}">
                    @csrf
                    
                    <!-- Container untuk dinamis form -->
                    <div id="table-body">
                        <!-- Data Rows (Cards) -->
                    </div>

                    <!-- Tombol Tambah Baris -->
                    <button type="button" id="add-row-btn" class="btn-add-row">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Usulan Training Baru
                    </button>

                    <!-- Sticky Footer Actions -->
                    <div class="position-sticky bg-white p-3 shadow-lg z-3 border-top rounded-top-4 mt-5 d-flex justify-content-end gap-2" style="bottom: 60px;">
                        <a href="{{ route('indexPD') }}" class="btn btn-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-paper-plane me-1"></i> Submit Pengajuan
                        </button>
                    </div>
                </form>
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
            // Ambil Active Year dari backend
            const activeYear = '{{ \App\Models\MstPdActiveYear::getActiveYear() }}';
            
            document.getElementById('add-row-btn').addEventListener('click', function() {
                var tableBody = document.getElementById('table-body');

                // Collapse all existing cards first to save space
                document.querySelectorAll('#table-body .card').forEach(card => {
                    const header = card.querySelector('.card-header-custom');
                    const body = card.querySelector('.card-body-collapsible');
                    if (body && !body.classList.contains('d-none')) {
                        toggleCollapse(header);
                    }
                });

                var newRow = document.createElement('div');
                newRow.className = 'card dynamic-card mb-4';
                
                newRow.innerHTML = `
                    <div class="card-header-custom d-flex justify-content-between align-items-center" onclick="toggleCollapse(this)">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fas fa-chevron-down toggle-icon text-muted"></i>
                            <h6 class="m-0 fw-bold row-number text-primary"><i class="fas fa-tasks me-2"></i>Usulan Training</h6>
                            <span class="row-summary-badge d-none"></span>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row rounded-pill px-3"><i class="bi bi-trash"></i> Hapus</button>
                    </div>
                    <div class="card-body pt-4 card-body-collapsible">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select section-dropdown" name="section_id[]" required>
                                        <option value="">---- Pilih Section ----</option>
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                                        @endforeach
                                    </select>
                                    <label>Section</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select job-position-dropdown" name="id_job_position[]" required>
                                        <option value="">---- Pilih Job Position ----</option>
                                    </select>
                                    <label>Job Position</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select user-dropdown" name="id_user[]" required>
                                        <option value="">---- Pilih Karyawan ----</option>
                                    </select>
                                    <label>Nama Karyawan</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="program_training" name="program_training[]" placeholder="Program Training" required>
                                    <label>Program Training</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select competency-category-dropdown" name="kategori_competency[]" required> 
                                        <option value="">---- Pilih Kategori ----</option>
                                        <option value="technical">Technical Competency</option>
                                        <option value="softskill">Soft Skill</option>
                                        <option value="additional">Additional</option>
                                    </select>
                                    <label>Kategori Competency</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <select class="form-select competency-dropdown" name="competency[]" required>
                                        <option value="">---- Pilih Competency ----</option>
                                    </select>
                                    <label>Competency</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="due_date" name="due_date[]" min="${activeYear}-01-01" max="${activeYear}-12-31" required>
                                    <label>Due Date</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="biaya" name="biaya[]" placeholder="Budget" required>
                                    <label>Budget (Rp)</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="lembaga" name="lembaga[]" placeholder="Lembaga" required>
                                    <label>Lembaga</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="keterangan_tujuan" name="keterangan_tujuan[]" placeholder="Keterangan Tujuan" required>
                                    <label>Keterangan Tujuan</label>
                                </div>
                            </div>
                            {{-- Modul 4.4: Objective Learning (full width) --}}
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-secondary"><i class="bi bi-bullseye me-1"></i> Objective Learning (Hasil yang Diharapkan)</label>
                                    <textarea class="form-control" name="objective_learning[]" id="objective_learning"
                                        placeholder="Peserta mampu menerapkan............"
                                        style="min-height: 90px;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                tableBody.appendChild(newRow);
                updateRowNumbers();

                var sectionDropdown = newRow.querySelector('.section-dropdown');
                var jobPositionDropdown = newRow.querySelector('.job-position-dropdown');
                var userDropdown = newRow.querySelector('.user-dropdown');
                var competencyCategoryDropdown = newRow.querySelector('.competency-category-dropdown');
                var competencyDropdown = newRow.querySelector('.competency-dropdown');

                // Listener for remove button with animation
                newRow.querySelector('.btn-remove-row').addEventListener('click', function(e) {
                    e.stopPropagation(); // Stop toggling collapse when deleting card
                    newRow.style.opacity = '0';
                    newRow.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        newRow.remove();
                        updateRowNumbers();
                    }, 300);
                });

                var allJobPositions = @json($jobPositions);
                var allPenilaians = @json($penilaians);

                // Track selected user for competency filtering
                var currentSelectedUserId = null;

                // Section change → populate unique Job Positions
                sectionDropdown.addEventListener('change', function() {
                    var selectedSectionId = parseInt(this.value);

                    // Reset dropdowns
                    jobPositionDropdown.innerHTML = '<option value="">---- Pilih Job Position ----</option>';
                    userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    currentSelectedUserId = null;

                    if (!selectedSectionId) return;

                    // Filter job positions by section_id, unique by position_name
                    var uniqueJobs = [];
                    allJobPositions.forEach(function(jp) {
                        var jpSectionId = jp.section_id;
                        if (jpSectionId == selectedSectionId && !uniqueJobs.includes(jp.job_position)) {
                            uniqueJobs.push(jp.job_position);
                            var option = document.createElement('option');
                            option.value = jp.id;  // use integer ID
                            option.text = jp.job_position;
                            jobPositionDropdown.appendChild(option);
                        }
                    });
                });

                // Job Position change → populate Users yang punya job position tersebut
                jobPositionDropdown.addEventListener('change', function() {
                    var selectedJobPositionId = parseInt(this.value);
                    var selectedSectionId = parseInt(sectionDropdown.value);

                    // Reset user & competency
                    userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    currentSelectedUserId = null;

                    if (!selectedJobPositionId) return;

                    // Filter users by section_id + job position ID
                    var uniqueUserIds = [];
                    allJobPositions.forEach(function(jp) {
                        if (jp.section_id == selectedSectionId && jp.id == selectedJobPositionId) {
                            // Get active users via userJobPositions embedded in jobPosition
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
                    var selectedJobPositionId = parseInt(jobPositionDropdown.value);
                    if (!currentSelectedUserId || !selectedCategory || !selectedJobPositionId) return;

                    competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';
                    var addedCompetencies = [];

                    allPenilaians.forEach(function(penilaian) {
                        if (penilaian.id_user == currentSelectedUserId && penilaian.id_job_position == selectedJobPositionId) {
                            var optionText = '';

                            if (selectedCategory === 'technical' && penilaian.id_tc) {
                                optionText = penilaian.keterangan + ' - std: ' + penilaian.nilai_standard + ' - aktual: ' + penilaian.nilai_aktual;
                            } else if ((selectedCategory === 'softskill' || selectedCategory === 'soft skill' || selectedCategory === 'nontechnical') && penilaian.id_sk) {
                                optionText = penilaian.keterangan + ' - std: ' + penilaian.nilai_standard + ' - aktual: ' + penilaian.nilai_aktual;
                            } else if (selectedCategory === 'additional' && penilaian.id_ad) {
                                optionText = penilaian.keterangan + ' - std: ' + penilaian.nilai_standard + ' - aktual: ' + penilaian.nilai_aktual;
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

            // Function to update row numbers
            function updateRowNumbers() {
                var rows = document.querySelectorAll('#table-body .card');
                rows.forEach(function(row, index) {
                    var title = row.querySelector('.row-number');
                    if(title) title.innerText = 'Training Item #' + (index + 1);
                });
            }

            // Function to collapse/expand cards
            function toggleCollapse(header) {
                const card = header.closest('.card');
                const body = card.querySelector('.card-body-collapsible');
                const summaryBadge = header.querySelector('.row-summary-badge');

                if (body.classList.contains('d-none')) {
                    // Expand
                    body.classList.remove('d-none');
                    card.classList.remove('card-collapsed');
                    summaryBadge.classList.add('d-none');
                } else {
                    // Collapse
                    body.classList.add('d-none');
                    card.classList.add('card-collapsed');
                    
                    // Update and show summary text
                    const userSel = card.querySelector('.user-dropdown');
                    const userName = userSel && userSel.options[userSel.selectedIndex] && userSel.value ? userSel.options[userSel.selectedIndex].text : '';
                    const trainingVal = card.querySelector('#program_training') ? card.querySelector('#program_training').value.trim() : '';
                    
                    let summaryText = '';
                    if (userName && trainingVal) {
                        summaryText = `${userName} — ${trainingVal}`;
                    } else if (userName) {
                        summaryText = userName;
                    } else if (trainingVal) {
                        summaryText = trainingVal;
                    } else {
                        summaryText = 'Belum ada data detail';
                    }
                    
                    summaryBadge.innerText = summaryText;
                    summaryBadge.classList.remove('d-none');
                }
            }

            // Trigger first row addition automatically on load
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('add-row-btn').click();

                // Handle HTML5 hidden validation errors by expanding collapsed cards with invalid fields
                const form = document.getElementById('trainingForm');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!this.checkValidity()) {
                            // Find the first invalid element
                            const invalidElement = this.querySelector(':invalid');
                            if (invalidElement) {
                                // Find the collapsible card container
                                const card = invalidElement.closest('.card');
                                if (card) {
                                    const body = card.querySelector('.card-body-collapsible');
                                    const header = card.querySelector('.card-header-custom');
                                    if (body && body.classList.contains('d-none')) {
                                        toggleCollapse(header);
                                        // Wait a moment for UI to update, then focus
                                        setTimeout(() => {
                                            invalidElement.focus();
                                        }, 100);
                                    }
                                }
                            }
                        }
                    });
                }
            });
        </script>

        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: "{{ session('success') }}",
                        timer: 3000,
                        showConfirmButton: false
                    });
                });
            </script>
        @endif

        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: "{{ session('error') }}",
                        confirmButtonColor: '#0d6efd'
                    });
                });
            </script>
        @endif
    </main><!-- End #main -->
@endsection
