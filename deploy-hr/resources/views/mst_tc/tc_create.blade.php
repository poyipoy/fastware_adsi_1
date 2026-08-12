@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            .top-right-alert {
                position: fixed;
                top: 20px;
                right: 20px;
                background-color: #fc0909;
                color: #ffffff;
                padding: 10px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
                z-index: 1000;
                opacity: 0;
                transition: opacity 0.3s ease, transform 0.3s ease;
                transform: translateY(-20px);
            }
            .top-right-alert.show {
                opacity: 1;
                transform: translateY(0);
            }
            .accordion-button:not(.collapsed) {
                background-color: #f8f9fa;
                color: #333;
                box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
            }
            .nav-pills .nav-link {
                background-color: #f8f9fa;
                color: #6c757d;
                border: 1px solid #dee2e6;
                transition: all 0.2s ease-in-out;
            }
            .nav-pills .nav-link:hover {
                background-color: #e9ecef;
            }
            .nav-pills .nav-link.active {
                background-color: #0d6efd !important;
                color: #fff !important;
                border-color: #0d6efd !important;
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Tambah Data Competency</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                <h3><b> Form Tambah Data Competency</b></h3>
                <form id="combinedForm" action="{{ route('mst_tc.store') }}" method="POST">
                    @csrf
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <!-- Job Position Selection -->
                            <div class="row g-3 align-items-center mb-4">
                                <div class="col-md-8">
                                    <div class="form-floating">
                                        <select name="tc[id_job_position]" id="job_position_tc" class="form-select border-primary">
                                            <option value="">---- Pilih Job Posisi ----</option>
                                            @foreach ($jobPositions as $position)
                                                <option value="{{ $position->id }}">{{ $position->job_position }}</option>
                                            @endforeach
                                        </select>
                                        <label for="job_position_tc" class="text-primary fw-semibold">Target Job Position</label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="button" class="btn btn-outline-primary py-3 rounded-pill w-100 shadow-sm" id="lihatEmployeeButton" onclick="fetchEmployees()">
                                        <i class="fas fa-users me-2"></i> Lihat Daftar Employee
                                    </button>
                                </div>
                            </div>

                            <!-- Nav Pills -->
                            <ul class="nav nav-pills mb-4 nav-fill gap-2" id="competencyTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active rounded-pill shadow-sm" id="tc-tab" data-bs-toggle="pill" data-bs-target="#tc-content" type="button" role="tab" aria-controls="tc-content" aria-selected="true">
                                        <i class="fas fa-tools me-2"></i> Technical Competency
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-pill shadow-sm" id="sk-tab" data-bs-toggle="pill" data-bs-target="#sk-content" type="button" role="tab" aria-controls="sk-content" aria-selected="false">
                                        <i class="fas fa-user-tie me-2"></i> Soft Skills
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-pill shadow-sm" id="ad-tab" data-bs-toggle="pill" data-bs-target="#ad-content" type="button" role="tab" aria-controls="ad-content" aria-selected="false">
                                        <i class="fas fa-plus-circle me-2"></i> Additional
                                    </button>
                                </li>
                            </ul>

                            <!-- Tab Content -->
                            <div class="tab-content" id="competencyTabsContent">
                                
                                <!-- Tab 1: Technical Competency -->
                                <div class="tab-pane fade show active" id="tc-content" role="tabpanel" aria-labelledby="tc-tab">
                                    <div class="card border-0 bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3 text-primary"><i class="fas fa-list-ul me-2"></i>Items Technical Competency</h6>
                                            <div id="poinFieldsContainer">
                                                <!-- Dynamic Fields TC -->
                                                <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                                    <div class="col-md-11">
                                                        <div class="row g-2">
                                                            <div class="col-md-6 form-floating">
                                                                <input type="text" name="tc[keterangan_tc][]" id="keterangan_tc_0" class="form-control bg-light" placeholder="Competency">
                                                                <label for="keterangan_tc_0">Competency</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <input type="text" name="tc[deskripsi_tc][]" id="deskripsi_tc_0" class="form-control bg-light" placeholder="Deskripsi Competency">
                                                                <label for="deskripsi_tc_0">Deskripsi Competency</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <select name="tc[id_poin_kategori][]" id="id_poin_kategori_tc_0" class="form-select bg-light">
                                                                    <option value="">---- Pilih Kategori ----</option>
                                                                    <option value="1">Skill of Process Plant</option>
                                                                    <option value="2">Skill of Process Office & Quality</option>
                                                                    <option value="3">Skill of EHS</option>
                                                                </select>
                                                                <label for="id_poin_kategori_tc_0">Kategori Nilai</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <select name="tc[nilai][]" id="nilai_tc_0" class="form-select bg-light">
                                                                    <option value="">-- Nilai --</option>
                                                                    <option value="1">1</option>
                                                                    <option value="2">2</option>
                                                                    <option value="3">3</option>
                                                                    <option value="4">4</option>
                                                                </select>
                                                                <label for="nilai_tc_0">Standar Nilai</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 d-flex justify-content-center">
                                                        <button type="button" class="btn btn-outline-success py-3 px-3 rounded-pill shadow-sm" onclick="addFields()">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 2: Soft Skills -->
                                <div class="tab-pane fade" id="sk-content" role="tabpanel" aria-labelledby="sk-tab">
                                    <div class="card border-0 bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3 text-success"><i class="fas fa-list-ul me-2"></i>Items Soft Skills</h6>
                                            <div id="fieldsContainer2">
                                                <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                                    <div class="col-md-11">
                                                        <div class="row g-2">
                                                            <div class="col-md-6 form-floating">
                                                                <input type="text" name="soft_skills[keterangan_sk][]" id="keterangan_sk_0" class="form-control bg-light" placeholder="Soft Skills">
                                                                <label for="keterangan_sk_0">Soft Skills</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <input type="text" name="soft_skills[deskripsi_sk][]" id="deskripsi_sk_0" class="form-control bg-light" placeholder="Deskripsi Soft Skills">
                                                                <label for="deskripsi_sk_0">Deskripsi Soft Skills</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <select name="soft_skills[id_poin_kategori][]" id="id_poin_kategori_sk_0" class="form-select bg-light">
                                                                    <option value="">---- Pilih Kategori ----</option>
                                                                    <option value="1">Skill of Process Plant</option>
                                                                    <option value="2">Skill of Process Office & Quality</option>
                                                                    <option value="3">Skill of EHS</option>
                                                                </select>
                                                                <label for="id_poin_kategori_sk_0">Kategori Nilai</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <select name="soft_skills[nilai][]" id="nilai_sk_0" class="form-select bg-light">
                                                                    <option value="">-- Nilai --</option>
                                                                    <option value="1">1</option>
                                                                    <option value="2">2</option>
                                                                    <option value="3">3</option>
                                                                    <option value="4">4</option>
                                                                </select>
                                                                <label for="nilai_sk_0">Standar Nilai</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 d-flex justify-content-center">
                                                        <button type="button" class="btn btn-outline-success py-3 px-3 rounded-pill shadow-sm" onclick="addFields2()">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab 3: Additional -->
                                <div class="tab-pane fade" id="ad-content" role="tabpanel" aria-labelledby="ad-tab">
                                    <div class="card border-0 bg-light mb-3">
                                        <div class="card-body">
                                            <h6 class="fw-bold mb-3 text-warning"><i class="fas fa-list-ul me-2"></i>Items Additional</h6>
                                            <div id="fieldsContainer3">
                                                <div class="row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm">
                                                    <div class="col-md-11">
                                                        <div class="row g-2">
                                                            <div class="col-md-6 form-floating">
                                                                <input type="text" name="additional[keterangan_ad][]" id="keterangan_ad_0" class="form-control bg-light" placeholder="Additional Competency">
                                                                <label for="keterangan_ad_0">Additional Competency</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <input type="text" name="additional[deskripsi_ad][]" id="deskripsi_ad_0" class="form-control bg-light" placeholder="Deskripsi Additional">
                                                                <label for="deskripsi_ad_0">Deskripsi Additional</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <select name="additional[id_poin_kategori][]" id="id_poin_kategori_ad_0" class="form-select bg-light">
                                                                    <option value="">---- Pilih Kategori ----</option>
                                                                    <option value="1">Skill of Process Plant</option>
                                                                    <option value="2">Skill of Process Office & Quality</option>
                                                                    <option value="3">Skill of EHS</option>
                                                                </select>
                                                                <label for="id_poin_kategori_ad_0">Kategori Nilai</label>
                                                            </div>
                                                            <div class="col-md-6 form-floating">
                                                                <select name="additional[nilai][]" id="nilai_ad_0" class="form-select bg-light">
                                                                    <option value="">-- Nilai --</option>
                                                                    <option value="1">1</option>
                                                                    <option value="2">2</option>
                                                                    <option value="3">3</option>
                                                                    <option value="4">4</option>
                                                                </select>
                                                                <label for="nilai_ad_0">Standar Nilai</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 d-flex justify-content-center">
                                                        <button type="button" class="btn btn-outline-success py-3 px-3 rounded-pill shadow-sm" onclick="addFields3()">
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                            </div> <!-- End Tab Content -->

                            <!-- Employee Modal -->
                            <div class="modal fade" id="employeeModal" tabindex="-1" role="dialog" aria-labelledby="employeeModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                                    <div class="modal-content border-0 shadow">
                                        <div class="modal-header bg-light border-bottom-0">
                                            <h5 class="modal-title fw-bold text-primary" id="employeeModalLabel"><i class="fas fa-users me-2"></i>Daftar Employee</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-0">
                                            <ul id="employeeList" class="list-group list-group-flush">
                                                <!-- Employee list will be loaded here dynamically -->
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sticky Action Bar -->
                            <div class="mt-4 border-top bg-white" style="position: sticky; bottom: 0; z-index: 1030; margin: 0 -24px -24px -24px; padding: 20px 24px; border-radius: 0 0 0.375rem 0.375rem; box-shadow: 0 -8px 20px rgba(0,0,0,0.08);">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('tcShow') }}" class="btn btn-light border px-4 py-2 rounded-pill shadow-sm">
                                        <i class="fas fa-arrow-left me-2"></i> Kembali
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                                        <i class="fas fa-save me-2"></i> Simpan Competency Baru
                                    </button>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <!-- Spacer to prevent copyright footer from overlapping sticky action bar at the bottom -->
        <div style="height: 90px;"></div>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            document.getElementById('combinedForm').addEventListener('submit', function(event) {
                event.preventDefault();

                // Ambil elemen yang diperlukan
                const jobPositionElement = document.getElementById('job_position_tc');

                if (!jobPositionElement || !jobPositionElement.value) {
                    Swal.fire('Peringatan', 'Harap pilih Target Job Position terlebih dahulu!', 'warning');
                    return;
                }

                 const collectData = (type, prefix) => {
                    const ket = document.querySelectorAll(`input[name="${type}[keterangan_${prefix}][]"]`);
                    const desc = document.querySelectorAll(`input[name="${type}[deskripsi_${prefix}][]"]`);
                    const cat = document.querySelectorAll(`select[name="${type}[id_poin_kategori][]"]`);
                    const val = document.querySelectorAll(`select[name="${type}[nilai][]"]`);
                    
                    return Array.from(ket).map((el, i) => ({
                        [`keterangan_${prefix}`]: el.value.trim(),
                        [`deskripsi_${prefix}`]: desc[i]?.value.trim() ?? '',
                        id_poin_kategori: cat[i]?.value ?? '',
                        nilai: val[i]?.value ?? ''
                    })).filter(row => row[`keterangan_${prefix}`] !== '' || row.id_poin_kategori !== '' || row.nilai !== '');
                };

                const tcRows = collectData('tc', 'tc');
                const skRows = collectData('soft_skills', 'sk');
                const adRows = collectData('additional', 'ad');

                if (tcRows.length === 0 || skRows.length === 0) {
                    Swal.fire('Peringatan', 'Harap isi minimal satu data Technical Competency dan minimal satu data Soft Skills!', 'warning');
                    return;
                }

                // Warning validation (PERINGATAN) untuk mengecek baris yang terisi sebagian
                let incompleteRows = false;
                tcRows.forEach(row => {
                    if (row.keterangan_tc === '' || row.id_poin_kategori === '' || row.nilai === '') {
                        incompleteRows = true;
                    }
                });
                skRows.forEach(row => {
                    if (row.keterangan_sk === '' || row.id_poin_kategori === '' || row.nilai === '') {
                        incompleteRows = true;
                    }
                });
                adRows.forEach(row => {
                    if (row.keterangan_ad === '' || row.id_poin_kategori === '' || row.nilai === '') {
                        incompleteRows = true;
                    }
                });

                if (incompleteRows) {
                    Swal.fire('Peringatan', 'Harap lengkapi semua kolom (Kompetensi/Nama, Kategori Nilai, dan Standar Nilai) pada baris yang telah Anda buat!', 'warning');
                    return;
                }

                const formatPayload = (rows, prefix) => {
                    let result = {};
                    result[`keterangan_${prefix}`] = rows.map(r => r[`keterangan_${prefix}`]);
                    result[`deskripsi_${prefix}`] = rows.map(r => r[`deskripsi_${prefix}`]);
                    result.id_poin_kategori = rows.map(r => r.id_poin_kategori);
                    result.nilai = rows.map(r => r.nilai);
                    return result;
                };

                const tcData = formatPayload(tcRows, 'tc');
                tcData.id_job_position = jobPositionElement ? jobPositionElement.value : '';

                const data = {
                    tc: tcData,
                    soft_skills: formatPayload(skRows, 'sk')
                };

                if (adRows.length > 0) {
                    data.additional = formatPayload(adRows, 'ad');
                }

                console.log('Payload to send:', data);

                fetch('{{ route('mst_tc.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(async response => {
                        const text = await response.text();
                        let json = null;
                        try { json = JSON.parse(text); } catch (e) { /* not JSON */ }
                        if (!response.ok) {
                            console.error('Server returned', response.status, text);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal menyimpan',
                                html: `<pre style="text-align:left;white-space:pre-wrap">Status: ${response.status}\n${text}</pre>`,
                                width: 600
                            });
                            return Promise.reject({status: response.status, body: json || text});
                        }
                        console.log('Sukses:', json ?? text);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data berhasil disimpan.',
                            didClose: () => { window.location.href = '{{ route('tcShow') }}'; }
                        });
                        document.getElementById('combinedForm').reset();
                    })
                    .catch((error) => {
                        console.error('Fetch error:', error);
                    });

            });

             // Fungsi untuk menambahkan field baru untuk Technical Competency
            function addFields() {
                const container = document.getElementById('poinFieldsContainer');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm mt-3';
                newFieldGroup.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-6 form-floating">
                                <input type="text" name="tc[keterangan_tc][]" id="keterangan_tc_${container.children.length}" class="form-control bg-light" placeholder="Competency">
                                <label for="keterangan_tc_${container.children.length}">Competency</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <input type="text" name="tc[deskripsi_tc][]" id="deskripsi_tc_${container.children.length}" class="form-control bg-light" placeholder="Deskripsi Competency">
                                <label for="deskripsi_tc_${container.children.length}">Deskripsi Competency</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select name="tc[id_poin_kategori][]" id="id_poin_kategori_tc_${container.children.length}" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label for="id_poin_kategori_tc_${container.children.length}">Kategori Nilai</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select name="tc[nilai][]" id="nilai_tc_${container.children.length}" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label for="nilai_tc_${container.children.length}">Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeFields(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(newFieldGroup);
            }

             // Fungsi untuk menambahkan field baru untuk Soft Skills
            function addFields2() {
                const container = document.getElementById('fieldsContainer2');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm mt-3';
                newFieldGroup.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-6 form-floating">
                                <input type="text" name="soft_skills[keterangan_sk][]" id="keterangan_sk_${container.children.length}" class="form-control bg-light" placeholder="Soft Skills">
                                <label for="keterangan_sk_${container.children.length}">Soft Skills</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <input type="text" name="soft_skills[deskripsi_sk][]" id="deskripsi_sk_${container.children.length}" class="form-control bg-light" placeholder="Deskripsi Soft Skills">
                                <label for="deskripsi_sk_${container.children.length}">Deskripsi Soft Skills</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select name="soft_skills[id_poin_kategori][]" id="id_poin_kategori_sk_${container.children.length}" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label for="id_poin_kategori_sk_${container.children.length}">Kategori Nilai</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select name="soft_skills[nilai][]" id="nilai_sk_${container.children.length}" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label for="nilai_sk_${container.children.length}">Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeFields(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(newFieldGroup);
            }

             // Fungsi untuk menambahkan field baru untuk Additional
            function addFields3() {
                const container = document.getElementById('fieldsContainer3');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'row g-3 align-items-center mb-3 skill-row border p-3 rounded bg-white shadow-sm mt-3';
                newFieldGroup.innerHTML = `
                    <div class="col-md-11">
                        <div class="row g-2">
                            <div class="col-md-6 form-floating">
                                <input type="text" name="additional[keterangan_ad][]" id="keterangan_ad_${container.children.length}" class="form-control bg-light" placeholder="Additional Competency">
                                <label for="keterangan_ad_${container.children.length}">Additional Competency</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <input type="text" name="additional[deskripsi_ad][]" id="deskripsi_ad_${container.children.length}" class="form-control bg-light" placeholder="Deskripsi Additional">
                                <label for="deskripsi_ad_${container.children.length}">Deskripsi Additional</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select name="additional[id_poin_kategori][]" id="id_poin_kategori_ad_${container.children.length}" class="form-select bg-light">
                                    <option value="">---- Pilih Kategori ----</option>
                                    <option value="1">Skill of Process Plant</option>
                                    <option value="2">Skill of Process Office & Quality</option>
                                    <option value="3">Skill of EHS</option>
                                </select>
                                <label for="id_poin_kategori_ad_${container.children.length}">Kategori Nilai</label>
                            </div>
                            <div class="col-md-6 form-floating">
                                <select name="additional[nilai][]" id="nilai_ad_${container.children.length}" class="form-select bg-light">
                                    <option value="">-- Nilai --</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                                <label for="nilai_ad_${container.children.length}">Standar Nilai</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex justify-content-center">
                        <button type="button" class="btn btn-outline-danger py-3 px-3 rounded-pill shadow-sm" onclick="removeFields(this)">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                `;
                container.appendChild(newFieldGroup);
            }

            // Fungsi untuk menghapus field
            function removeFields(button) {
                button.closest('.row').remove();
            }

            function fetchEmployees() {
                const jobPositionId = document.getElementById('job_position_tc').value;

                if (jobPositionId) {
                    // Ubah menjadi 'id' sesuai filter di controller
                    const url = "{{ route('employees.by.job.position') }}?id=" + jobPositionId;

                    fetch(url)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const employeeList = document.getElementById('employeeList');
                                employeeList.innerHTML = ''; // Kosongkan list sebelum menambahkan data baru

                                // Iterasi melalui data yang diterima dan tambahkan ke list
                                data.data.forEach(employee => {
                                    const li = document.createElement('li');
                                    li.className = 'list-group-item d-flex align-items-center py-2 text-dark';
                                    li.innerHTML = '<i class="fas fa-user-circle text-primary fs-5 me-3"></i><span class="fw-medium">' + employee.name + '</span>';
                                    employeeList.appendChild(li);
                                });

                                // Tampilkan Modal menggunakan API Bootstrap 5
                                const employeeModalElement = document.getElementById('employeeModal');
                                const modalInstance = bootstrap.Modal.getInstance(employeeModalElement) || new bootstrap.Modal(employeeModalElement);
                                modalInstance.show();
                            } else {
                                alert(data.message || 'Gagal mengambil data employee');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                } else {
                    alert('Silakan pilih job position terlebih dahulu.');
                }
            }

            document.getElementById('combinedForm').addEventListener('submit', function(e) {
                const tcFilled = Array.from(document.querySelectorAll('input[name="tc[keterangan_tc][]"]')).some(input => input.value.trim() !== '');
                const skFilled = Array.from(document.querySelectorAll('input[name="sk[keterangan_sk][]"]')).some(input => input.value.trim() !== '');
                const adFilled = Array.from(document.querySelectorAll('input[name="additional[keterangan_ad][]"]')).some(input => input.value.trim() !== '');

                if (!tcFilled && !skFilled && !adFilled) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Peringatan',
                            text: 'Minimal satu Skill/Kompetensi (Technical, Soft Skills, atau Additional) harus diisi!',
                        });
                    } else {
                        alert('Peringatan: Minimal satu Skill/Kompetensi (Technical, Soft Skills, atau Additional) harus diisi!');
                    }
                }
            });
        </script>
    </main><!-- End #main -->
@endsection
