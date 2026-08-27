@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            .container {
                margin-top: 20px;
                padding-bottom: 50px; /* Space for sticky footer */
            }
            .skill-row {
                transition: all 0.3s ease;
            }
            #editCompetencyTabs .nav-link {
                background-color: #ffffff;
                color: #6c757d;
                border: 1px solid #dee2e6;
                transition: all 0.3s ease;
                margin-right: 5px;
            }
            #editCompetencyTabs .nav-link:hover {
                background-color: #f8f9fa;
                color: #495057;
            }
            #editCompetencyTabs .nav-link.active {
                background-color: #0d6efd;
                color: #ffffff;
                border-color: #0d6efd;
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('tcShow') }}">Menu List Competency</a></li>
                    <li class="breadcrumb-item active">Edit Semua Competency</li>
                </ol>
            </nav>
        </div>
        
        <section class="section">
            <div class="container">
                <h3 class="mb-4"><b>Form Edit Data Competency</b></h3>
                
                <form id="editAllForm" action="{{ route('mst_tc.update_all', $jobPosition->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body pt-3">
                            <div class="form-group mb-2">

                                <label for="job_position_tc" class="fw-bold mb-2">Job Position</label>
                                <select name="tc[id_job_position]" id="job_position_tc" class="form-select border-primary fw-semibold bg-light">
                                    <option value="">---- Pilih Job Posisi ----</option>
                                    @foreach ($jobPositions as $position)
                                        <option value="{{ $position->id }}" {{ $masterJpId == $position->id ? 'selected' : '' }}>
                                            {{ $position->job_position }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tabs Navigation -->
                    <ul class="nav nav-pills mb-3 gap-2" id="editCompetencyTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold px-4 shadow-sm" id="tc-tab" data-bs-toggle="pill" data-bs-target="#tc-panel" type="button" role="tab" aria-controls="tc-panel" aria-selected="true">
                                Technical Competency <span class="badge bg-white text-primary ms-1" id="tc-count-badge">{{ count($tcs) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 shadow-sm" id="sk-tab" data-bs-toggle="pill" data-bs-target="#sk-panel" type="button" role="tab" aria-controls="sk-panel" aria-selected="false">
                                Soft Skills <span class="badge bg-white text-success ms-1" id="sk-count-badge">{{ count($softSkills) }}</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold px-4 shadow-sm" id="ad-tab" data-bs-toggle="pill" data-bs-target="#ad-panel" type="button" role="tab" aria-controls="ad-panel" aria-selected="false">
                                Additional <span class="badge bg-white text-info ms-1" id="ad-count-badge">{{ count($additionals) }}</span>
                            </button>
                        </li>
                    </ul>

                    <!-- Tabs Content -->
                    <div class="tab-content" id="editCompetencyTabsContent">
                        
                        <!-- Technical Competency Panel -->
                        <div class="tab-pane fade show active" id="tc-panel" role="tabpanel" aria-labelledby="tc-tab">
                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold m-0 text-primary">Technical Competency</h5>
                                        <button type="button" class="btn btn-success rounded-pill shadow-sm" onclick="addTcField()">
                                            <i class="fas fa-plus me-1"></i> Tambah Baris
                                        </button>
                                    </div>
                                    <div id="tcFieldsContainer">
                                        @foreach ($tcs as $index => $data)
                                            <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                                <div class="col-md-11">
                                                    <div class="row g-2">
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <input type="text" name="tc[keterangan_tc][]" id="keterangan_tc_{{ $data->id }}" class="form-control bg-light" value="{{ $data->keterangan_tc }}" placeholder="Technical Competency">
                                                            <label for="keterangan_tc_{{ $data->id }}">Technical Competency</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <input type="text" name="tc[deskripsi_tc][]" id="deskripsi_tc_{{ $data->id }}" class="form-control" value="{{ $data->deskripsi_tc }}" placeholder="Deskripsi Competency">
                                                            <label for="deskripsi_tc_{{ $data->id }}">Deskripsi Competency</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <select name="tc[id_poin_kategori][]" id="id_poin_kategori_tc_{{ $data->id }}" class="form-select bg-light">
                                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                                <option value="1" {{ $data->id_poin_kategori == 1 ? 'selected' : '' }}>Skill of Process Plant</option>
                                                                <option value="2" {{ $data->id_poin_kategori == 2 ? 'selected' : '' }}>Skill of Process Office & Quality</option>
                                                                <option value="3" {{ $data->id_poin_kategori == 3 ? 'selected' : '' }}>Skill of EHS</option>
                                                            </select>
                                                            <label for="id_poin_kategori_tc_{{ $data->id }}">Kategori Nilai</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <select name="tc[nilai][]" id="nilai_{{ $data->id }}" class="form-select bg-light">
                                                                <option value="">-- Nilai --</option>
                                                                @foreach (range(1, 4) as $nilai)
                                                                    <option value="{{ $nilai }}" {{ $data->nilai == $nilai ? 'selected' : '' }}>{{ $nilai }}</option>
                                                                @endforeach
                                                            </select>
                                                            <label for="nilai_{{ $data->id }}">Standar Nilai</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 d-flex justify-content-center">
                                                    <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeTcField(this, {{ $data->id }})">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Soft Skills Panel -->
                        <div class="tab-pane fade" id="sk-panel" role="tabpanel" aria-labelledby="sk-tab">
                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold m-0 text-success">Soft Skills</h5>
                                        <button type="button" class="btn btn-success rounded-pill shadow-sm" onclick="addSkField()">
                                            <i class="fas fa-plus me-1"></i> Tambah Baris
                                        </button>
                                    </div>
                                    <div id="skFieldsContainer">
                                        @foreach ($softSkills as $index => $data)
                                            <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                                <div class="col-md-11">
                                                    <div class="row g-2">
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <input type="text" name="sk[keterangan_sk][]" id="keterangan_sk_{{ $data->id }}" class="form-control bg-light" value="{{ $data->keterangan_sk }}" placeholder="Soft Skills">
                                                            <label for="keterangan_sk_{{ $data->id }}">Soft Skills</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <input type="text" name="sk[deskripsi_sk][]" id="deskripsi_sk_{{ $data->id }}" class="form-control" value="{{ $data->deskripsi_sk }}" placeholder="Deskripsi Soft Skill">
                                                            <label for="deskripsi_sk_{{ $data->id }}">Deskripsi Soft Skill</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <select name="sk[id_poin_kategori][]" id="id_poin_kategori_sk_{{ $data->id }}" class="form-select bg-light">
                                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                                <option value="1" {{ $data->id_poin_kategori == 1 ? 'selected' : '' }}>Skill of Process Plant</option>
                                                                <option value="2" {{ $data->id_poin_kategori == 2 ? 'selected' : '' }}>Skill of Process Office & Quality</option>
                                                                <option value="3" {{ $data->id_poin_kategori == 3 ? 'selected' : '' }}>Skill of EHS</option>
                                                            </select>
                                                            <label for="id_poin_kategori_sk_{{ $data->id }}">Kategori Nilai</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <select name="sk[nilai][]" id="nilai_{{ $data->id }}" class="form-select bg-light">
                                                                <option value="">-- Nilai --</option>
                                                                @foreach (range(1, 4) as $nilai)
                                                                    <option value="{{ $nilai }}" {{ $data->nilai == $nilai ? 'selected' : '' }}>{{ $nilai }}</option>
                                                                @endforeach
                                                            </select>
                                                            <label for="nilai_{{ $data->id }}">Standar Nilai</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 d-flex justify-content-center">
                                                    <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeSkField(this, {{ $data->id }})">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Panel -->
                        <div class="tab-pane fade" id="ad-panel" role="tabpanel" aria-labelledby="ad-tab">
                            <div class="card shadow-sm border-0 rounded-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="fw-bold m-0 text-info">Additional</h5>
                                        <button type="button" class="btn btn-success rounded-pill shadow-sm" onclick="addAdField()">
                                            <i class="fas fa-plus me-1"></i> Tambah Baris
                                        </button>
                                    </div>
                                    <div id="adFieldsContainer">
                                        @foreach ($additionals as $index => $data)
                                            <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                                <div class="col-md-11">
                                                    <div class="row g-2">
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <input type="text" name="ad[keterangan_ad][]" id="keterangan_ad_{{ $data->id }}" class="form-control bg-light" value="{{ $data->keterangan_ad }}" placeholder="Additional">
                                                            <label for="keterangan_ad_{{ $data->id }}">Additional</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <input type="text" name="ad[deskripsi_ad][]" id="deskripsi_ad_{{ $data->id }}" class="form-control" value="{{ $data->deskripsi_ad }}" placeholder="Deskripsi Additional">
                                                            <label for="deskripsi_ad_{{ $data->id }}">Deskripsi Additional</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <select name="ad[id_poin_kategori][]" id="id_poin_kategori_ad_{{ $data->id }}" class="form-select bg-light">
                                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                                <option value="1" {{ $data->id_poin_kategori == 1 ? 'selected' : '' }}>Skill of Process Plant</option>
                                                                <option value="2" {{ $data->id_poin_kategori == 2 ? 'selected' : '' }}>Skill of Process Office & Quality</option>
                                                                <option value="3" {{ $data->id_poin_kategori == 3 ? 'selected' : '' }}>Skill of EHS</option>
                                                            </select>
                                                            <label for="id_poin_kategori_ad_{{ $data->id }}">Kategori Nilai</label>
                                                        </div>
                                                        <div class="col-md-3 form-floating mb-2">
                                                            <select name="ad[nilai][]" id="nilai_{{ $data->id }}" class="form-select bg-light">
                                                                <option value="">-- Nilai --</option>
                                                                @foreach (range(1, 4) as $nilai)
                                                                    <option value="{{ $nilai }}" {{ $data->nilai == $nilai ? 'selected' : '' }}>{{ $nilai }}</option>
                                                                @endforeach
                                                            </select>
                                                            <label for="nilai_{{ $data->id }}">Standar Nilai</label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 d-flex justify-content-center">
                                                    <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeAdField(this, {{ $data->id }})">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Sticky Footer Actions -->
                    <div class="position-sticky bg-white p-3 shadow-lg border-top rounded-top-4 rounded-bottom-4 mt-5 d-flex justify-content-end gap-2" style="bottom: 46px; z-index: 1030;">
                        <a href="{{ route('tcShow') }}" class="btn btn-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-1"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="fas fa-save me-1"></i> Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </section>

        <!-- Spacer to prevent copyright footer from overlapping sticky action bar at the bottom -->
        <div style="height: 90px;"></div>

        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            // Counters to avoid duplicate IDs for dynamic items
            let tcCounter = 1000;
            let skCounter = 1000;
            let adCounter = 1000;

            function updateCountBadges() {
                document.getElementById('tc-count-badge').innerText = document.querySelectorAll('#tcFieldsContainer .skill-row').length;
                document.getElementById('sk-count-badge').innerText = document.querySelectorAll('#skFieldsContainer .skill-row').length;
                document.getElementById('ad-count-badge').innerText = document.querySelectorAll('#adFieldsContainer .skill-row').length;
            }

            function addTcField() {
                tcCounter++;
                const container = document.getElementById('tcFieldsContainer');
                const div = document.createElement('div');
                div.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm';
                div.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-3 form-floating mb-2">
                                <input type="text" name="tc[keterangan_tc][]" id="keterangan_tc_new_${tcCounter}" class="form-control bg-light" placeholder="Technical Competency">
                                <label for="keterangan_tc_new_${tcCounter}">Technical Competency</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <input type="text" name="tc[deskripsi_tc][]" id="deskripsi_tc_new_${tcCounter}" class="form-control" placeholder="Deskripsi Competency">
                                <label for="deskripsi_tc_new_${tcCounter}">Deskripsi Competency</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <select name="tc[id_poin_kategori][]" id="id_poin_kategori_tc_new_${tcCounter}" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori Nilai ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label for="id_poin_kategori_tc_new_${tcCounter}">Kategori Nilai</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <select name="tc[nilai][]" id="nilai_new_${tcCounter}" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label for="nilai_new_${tcCounter}">Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeDynamicField(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(div);
                updateCountBadges();
            }

            function addSkField() {
                skCounter++;
                const container = document.getElementById('skFieldsContainer');
                const div = document.createElement('div');
                div.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm';
                div.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-3 form-floating mb-2">
                                <input type="text" name="sk[keterangan_sk][]" id="keterangan_sk_new_${skCounter}" class="form-control bg-light" placeholder="Soft Skills">
                                <label for="keterangan_sk_new_${skCounter}">Soft Skills</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <input type="text" name="sk[deskripsi_sk][]" id="deskripsi_sk_new_${skCounter}" class="form-control" placeholder="Deskripsi Soft Skill">
                                <label for="deskripsi_sk_new_${skCounter}">Deskripsi Soft Skill</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <select name="sk[id_poin_kategori][]" id="id_poin_kategori_sk_new_${skCounter}" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori Nilai ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label for="id_poin_kategori_sk_new_${skCounter}">Kategori Nilai</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <select name="sk[nilai][]" id="nilai_sk_new_${skCounter}" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label for="nilai_sk_new_${skCounter}">Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeDynamicField(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(div);
                updateCountBadges();
            }

            function addAdField() {
                adCounter++;
                const container = document.getElementById('adFieldsContainer');
                const div = document.createElement('div');
                div.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm';
                div.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-3 form-floating mb-2">
                                <input type="text" name="ad[keterangan_ad][]" id="keterangan_ad_new_${adCounter}" class="form-control bg-light" placeholder="Additional">
                                <label for="keterangan_ad_new_${adCounter}">Additional</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <input type="text" name="ad[deskripsi_ad][]" id="deskripsi_ad_new_${adCounter}" class="form-control" placeholder="Deskripsi Additional">
                                <label for="deskripsi_ad_new_${adCounter}">Deskripsi Additional</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <select name="ad[id_poin_kategori][]" id="id_poin_kategori_ad_new_${adCounter}" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori Nilai ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label for="id_poin_kategori_ad_new_${adCounter}">Kategori Nilai</label>
                            </div>
                            <div class="col-md-3 form-floating mb-2">
                                <select name="ad[nilai][]" id="nilai_ad_new_${adCounter}" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label for="nilai_ad_new_${adCounter}">Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeDynamicField(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(div);
                updateCountBadges();
            }

            function removeDynamicField(button) {
                button.closest('.skill-row').remove();
                updateCountBadges();
            }

            function executeDeleteRequest(url, button) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data ini akan dihapus secara permanen dari database!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'Content-Type': 'application/json'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Dihapus!', 'Data berhasil dihapus.', 'success')
                                .then(() => {
                                    button.closest('.skill-row').remove();
                                    updateCountBadges();
                                });
                            } else {
                                Swal.fire('Gagal!', data.message || 'Terjadi kesalahan saat menghapus.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Gagal!', 'Terjadi kesalahan server.', 'error');
                        });
                    }
                });
            }

            function removeTcField(button, id) {
                // If it is the last item, prevent deletion to obey "minimal 1" rule
                if (document.querySelectorAll('#tcFieldsContainer .skill-row').length <= 1) {
                    Swal.fire('Peringatan', 'Kategori Technical Competency wajib memiliki minimal satu data!', 'warning');
                    return;
                }
                executeDeleteRequest(`/delete-tc-row/${id}`, button);
            }

            function removeSkField(button, id) {
                // If it is the last item, prevent deletion to obey "minimal 1" rule
                if (document.querySelectorAll('#skFieldsContainer .skill-row').length <= 1) {
                    Swal.fire('Peringatan', 'Kategori Soft Skills wajib memiliki minimal satu data!', 'warning');
                    return;
                }
                executeDeleteRequest(`/delete-sk-row/${id}`, button);
            }

            function removeAdField(button, id) {
                executeDeleteRequest(`/delete-ad-row/${id}`, button);
            }

            // Client-side Validation and Submit Handler
            document.getElementById('editAllForm').addEventListener('submit', function(event) {
                event.preventDefault();

                // Validate Technical Competency
                const tcRows = document.querySelectorAll('#tcFieldsContainer .skill-row');
                if (tcRows.length === 0) {
                    Swal.fire('Peringatan', 'Harap isi minimal satu data Technical Competency!', 'warning');
                    return;
                }
                
                let tcEmpty = false;
                tcRows.forEach(row => {
                    const ket = row.querySelector('input[name="tc[keterangan_tc][]"]').value.trim();
                    const cat = row.querySelector('select[name="tc[id_poin_kategori][]"]').value;
                    const val = row.querySelector('select[name="tc[nilai][]"]').value;

                    if (!ket || !cat || !val) {
                        tcEmpty = true;
                    }
                });

                if (tcEmpty) {
                    Swal.fire('Peringatan', 'Harap isi semua field Technical Competency (Kompetensi, Kategori, Nilai)!', 'warning');
                    return;
                }

                // Validate Soft Skills
                const skRows = document.querySelectorAll('#skFieldsContainer .skill-row');
                if (skRows.length === 0) {
                    Swal.fire('Peringatan', 'Harap isi minimal satu data Soft Skills!', 'warning');
                    return;
                }
                
                let skEmpty = false;
                skRows.forEach(row => {
                    const ket = row.querySelector('input[name="sk[keterangan_sk][]"]').value.trim();
                    const cat = row.querySelector('select[name="sk[id_poin_kategori][]"]').value;
                    const val = row.querySelector('select[name="sk[nilai][]"]').value;

                    if (!ket || !cat || !val) {
                        skEmpty = true;
                    }
                });

                if (skEmpty) {
                    Swal.fire('Peringatan', 'Harap isi semua field Soft Skills (Kompetensi, Kategori, Nilai)!', 'warning');
                    return;
                }

                // Validate Additional (Optional, but if row exists, check that it's complete)
                const adRows = document.querySelectorAll('#adFieldsContainer .skill-row');
                let adEmpty = false;
                adRows.forEach(row => {
                    const ketInput = row.querySelector('input[name="ad[keterangan_ad][]"]');
                    const catSelect = row.querySelector('select[name="ad[id_poin_kategori][]"]');
                    const valSelect = row.querySelector('select[name="ad[nilai][]"]');
                    
                    const ket = ketInput ? ketInput.value.trim() : '';
                    const cat = catSelect ? catSelect.value : '';
                    const val = valSelect ? valSelect.value : '';

                    // If any additional field is filled, all are required
                    if (ket || cat || val) {
                        if (!ket || !cat || !val) {
                            adEmpty = true;
                        }
                    }
                });

                if (adEmpty) {
                    Swal.fire('Peringatan', 'Harap lengkapi data Additional (Kompetensi, Kategori, dan Standar Nilai)!', 'warning');
                    return;
                }

                // Collect and send data via AJAX
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST', // Laravel uses POST with _method = PUT
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Semua perubahan competency berhasil disimpan.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            window.location.href = "{{ route('tcShow') }}";
                        });
                    } else {
                        Swal.fire('Gagal!', data.message || 'Terjadi kesalahan saat menyimpan data.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Gagal!', 'Terjadi kesalahan server.', 'error');
                });
            });
        </script>
    </main>
@endsection
