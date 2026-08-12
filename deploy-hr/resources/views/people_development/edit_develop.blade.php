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
            }
        </style>
        <div class="pagetitle">
            <h1>Halaman Edit Data Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('indexPD') }}">People Development</a></li>
                    <li class="breadcrumb-item active">Edit Training</li>
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

                <form id="trainingForm" method="POST" action="{{ route('updatePD') }}">
                    @csrf
                    @method('PUT')
                    @foreach ($data as $d)
                        <input type="hidden" name="original_id[]" value="{{ $d->id }}">
                    @endforeach
                    
                    <!-- Container untuk dinamis form -->
                    <div id="table-body">
                        <!-- Data Rows (Cards) -->
                    </div>

                    <!-- Tombol Tambah Baris -->
                    <button type="button" id="add-row" class="btn-add-row">
                        <i class="fas fa-plus-circle me-2"></i> Tambah Usulan Training Baru
                    </button>

                    <!-- Sticky Footer Actions -->
                    <div class="position-sticky bg-white p-3 shadow-lg z-3 border-top rounded-top-4 mt-5 d-flex justify-content-end gap-2" style="bottom: 60px;">
                        <a href="{{ route('indexPD') }}" class="btn btn-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-paper-plane me-1"></i> Update Pengajuan
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
            var existingData = @json($data);
            var jobPositions = @json($jobPositions);
            var penilaians = @json($penilaians);
            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            document.addEventListener('DOMContentLoaded', function() {
                var tableBody = document.getElementById('table-body');

                // Fungsi untuk menambahkan baris baru
                function addRow(item = {}) {
                    var tableBody = document.getElementById('table-body');
                    var indexCount = tableBody.children.length + 1;

                    var newRow = document.createElement('div');
                    var newId = item.id || Date.now(); // Generate a unique ID based on timestamp
                    newRow.id = `row-${newId}`;
                    newRow.className = 'card mb-3 shadow-sm border-0 dynamic-card';

                    var userOptions = '<option value="">---- Pilih Karyawan ----</option>';
                    var competencyOptions = '<option value="">---- Pilih Competency ----</option>';

                    if (item.user) {
                        const selectedNpk = String(item.user.npk || '').trim();
                        const selectedLabel = `${selectedNpk && selectedNpk !== '0' ? selectedNpk : '-'} - ${item.user.name}`;
                        userOptions += `<option value="${Number(item.user.id)}" selected>${escapeHtml(selectedLabel)}</option>`;
                    }

                    if (item.competency) {
                        competencyOptions += `<option value="${item.competency}" selected>${item.competency}</option>`;
                    }

                    newRow.innerHTML = `
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold row-number text-primary"><i class="fas fa-tasks me-2"></i>Training Item #${indexCount}</h6>
                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row rounded-pill px-3"><i class="bi bi-trash"></i> Hapus</button>
                        </div>
                        <div class="card-body pt-4">
                            <input type="hidden" id="modified_at" name="modified_at" value="${item.modified_at || ''}" />
                            <input type="hidden" name="id[]" value="${newId}" />
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select section-dropdown" name="section_id[]" required>
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
                                            ${userOptions}
                                        </select>
                                        <label>NPK - Nama Karyawan</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="program_training" name="program_training[]" value="${item.program_training || ''}" placeholder="Program Training" required>
                                        <label>Program Training</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select competency-category-dropdown" name="kategori_competency[]" required>
                                            <option value="">---- Pilih Kategori ----</option>
                                            <option value="technical" ${item.kategori_competency == 'technical' ? 'selected' : ''}>Technical Competency</option>
                                            <option value="softskill" ${item.kategori_competency == 'softskill' || item.kategori_competency == 'nontechnical' ? 'selected' : ''}>Soft Skill</option>
                                            <option value="additional" ${item.kategori_competency == 'additional' ? 'selected' : ''}>Additional</option>
                                        </select>
                                        <label>Kategori Competency</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <select class="form-select competency-dropdown" name="competency[]" required>
                                            ${competencyOptions}
                                        </select>
                                        <label>Competency</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="date" class="form-control" id="due_date" name="due_date[]" value="${item.due_date || ''}" required>
                                        <label>Due Date</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="biaya" name="biaya[]" value="${item.biaya || ''}" placeholder="Budget" required>
                                        <label>Budget (Rp)</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="lembaga" name="lembaga[]" value="${item.lembaga || ''}" placeholder="Lembaga" required>
                                        <label>Lembaga</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="keterangan_tujuan" name="keterangan_tujuan[]" value="${item.keterangan_tujuan || ''}" placeholder="Keterangan Tujuan" required>
                                        <label>Keterangan Tujuan</label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary"><i class="bi bi-bullseye me-1"></i> Objective Learning (Hasil yang Diharapkan)</label>
                                        <textarea class="form-control" name="objective_learning[]" id="objective_learning_${newId}"
                                            placeholder="Peserta mampu menerapkan............"
                                            style="min-height: 90px;">${item.objective_learning || ''}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;

                    tableBody.appendChild(newRow);

                    // Handle Remove Row and Update Indexing with animation
                    newRow.querySelector('.btn-remove-row').addEventListener('click', function() {
                        newRow.style.opacity = '0';
                        newRow.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            newRow.remove();
                            // Update Indexing
                            var rows = document.querySelectorAll('#table-body .dynamic-card');
                            rows.forEach(function(row, index) {
                                var title = row.querySelector('.row-number');
                                if(title) title.innerHTML = '<i class="fas fa-tasks me-2"></i>Training Item #' + (index + 1);
                            });
                        }, 300);
                    });

                    var sectionDropdown = newRow.querySelector('.section-dropdown');
                    var jobPositionDropdown = newRow.querySelector('.job-position-dropdown');
                    var userDropdown = newRow.querySelector('.user-dropdown');
                    var competencyCategoryDropdown = newRow.querySelector('.competency-category-dropdown');
                    var competencyDropdown = newRow.querySelector('.competency-dropdown');

                    // Handle section change → populate unique job positions
                    sectionDropdown.addEventListener('change', function() {
                        var selectedSectionId = parseInt(this.value);
                        jobPositionDropdown.innerHTML =
                        '<option value="">---- Pilih Job Position ----</option>';
                        userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                        competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

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

                    // Handle job position change → populate users for that job position
                    jobPositionDropdown.addEventListener('change', function() {
                        var selectedJobPositionId = parseInt(this.value);
                        var selectedSectionId = parseInt(sectionDropdown.value);
                        userDropdown.innerHTML = '<option value="">---- Pilih Karyawan ----</option>';
                        competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

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
                                            const npk = String(u.npk || '').trim();
                                            option.text = `${npk && npk !== '0' ? npk : '-'} - ${u.name}`;
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

                    // Handle competency category change and populate competencies
                    competencyCategoryDropdown.addEventListener('change', function() {
                        var selectedCategory = this.value;
                        var selectedUserId = userDropdown.value;
                        var selectedJobPositionId = parseInt(jobPositionDropdown.value);

                        competencyDropdown.innerHTML = '<option value="">---- Pilih Competency ----</option>';

                        if (selectedUserId && selectedCategory && selectedJobPositionId) {
                            var addedCompetencies = [];

                            penilaians.forEach(function(penilaian) {
                                if (penilaian.id_user == selectedUserId && penilaian.id_job_position == selectedJobPositionId) {
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

                            if (item.competency) {
                                competencyDropdown.value = item.competency;
                            }
                        }
                    });

                    // Populate the fields with previously saved data
                    if (item.section_id) {
                        sectionDropdown.value = item.section_id;
                        setTimeout(function() {
                            sectionDropdown.dispatchEvent(new Event('change'));
                            setTimeout(function() {
                                if (item.id_job_position) {
                                    jobPositionDropdown.value = item.id_job_position;
                                    jobPositionDropdown.dispatchEvent(new Event('change'));
                                    
                                    // Wait for userDropdown to be populated by jobPosition change before dispatching category
                                    setTimeout(function() {
                                        if (item.kategori_competency) {
                                            competencyCategoryDropdown.value = item.kategori_competency;
                                            competencyCategoryDropdown.dispatchEvent(new Event('change'));
                                        }
                                    }, 50);
                                }
                            }, 50);
                        }, 100);
                    } else if (item.kategori_competency) {
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
