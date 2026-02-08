@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            .form-row {
                display: flex;
                align-items: flex-start;
                margin-bottom: 10px;
            }

            .form-row>div {
                flex: 1;
                margin-right: 10px;
            }

            .form-row>div:last-child {
                margin-right: 0;
            }

            .form-row .btn {
                margin-top: 30px;
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
                    <div class="card">
                        <div class="card-body">
                            <h5 style="margin-top: 3%"><b>Tambah Data Technical Competency</b></h5>
                            <div id="fieldsContainer">
                                <div id="poinFieldsContainer">
                                    <div class="form-group" style="margin-top: 2%">
                                        <div class="form-row align-items-end">
                                            <div class="col-md-8">
                                                <label for="job_position_tc">Job Position</label>
                                                <select name="tc[id_job_position]" id="job_position_tc" class="form-control">
                                                    <option value="">---- Pilih Job Posisi ----</option>
                                                    @foreach ($jobPositions as $position)
                                                        <option value="{{ $position->id }}">{{ $position->job_position }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-primary mt-2" id="lihatEmployeeButton" onclick="fetchEmployees()">
                                                    <i class="fas fa-users"></i> Lihat Employee
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Bootstrap Modal -->
                                        <div class="modal fade" id="employeeModal" tabindex="-1" role="dialog"
                                            aria-labelledby="employeeModalLabel" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="employeeModalLabel">Daftar Employee</h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <ul id="employeeList">
                                                            <!-- Employee list will be loaded here dynamically -->
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row" style="margin-top: 2%">
                                        <div class="form-group">
                                            <label for="keterangan_tc_0">Competency</label>
                                            <input type="text" name="tc[keterangan_tc][]" id="keterangan_tc_0"
                                                class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="deskripsi_tc_0">Deskripsi</label>
                                            <input type="text" name="tc[deskripsi_tc][]" id="deskripsi_tc_0"
                                                class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="id_poin_kategori_tc_0">Kategori Nilai</label>
                                            <select name="tc[id_poin_kategori][]" id="id_poin_kategori_tc_0"
                                                class="form-control">
                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                <option value="1">Skill of Process Plant</option>
                                                <option value="2">Skill of Process Office & Quality</option>
                                                <option value="3">Skill of EHS</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="nilai_tc_0">Standar Nilai</label>
                                            <select name="tc[nilai][]" id="nilai_tc_0" class="form-control">
                                                <option value=""> ---- Pilih Standar Nilai ----</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-success" onclick="addFields()">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="form-group" style="margin-top: 2%">
                                    <div class="card">
                                        <div class="card-header bg-secondary text-white">
                                            Keterangan Penilaian
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Deskripsi Technical Competency -->
                                                <div class="col-md-4">
                                                    <div class="card card-equal-height mb-3">
                                                        <div class="card-header bg-primary text-white">
                                                            {{ $dataTc1->judul_keterangan }}
                                                        </div>
                                                        <div class="card-body">
                                                            <table class="table table-borderless">
                                                                <tbody>
                                                                    <tr>
                                                                        <td>1.</td>
                                                                        <td>{{ $dataTc1->deskripsi_1 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>2.</td>
                                                                        <td>{{ $dataTc1->deskripsi_2 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>3.</td>
                                                                        <td>{{ $dataTc1->deskripsi_3 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>4.</td>
                                                                        <td>{{ $dataTc1->deskripsi_4 }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Deskripsi Soft Skills -->
                                                <div class="col-md-4">
                                                    <div class="card card-equal-height mb-3">
                                                        <div class="card-header bg-success text-white">
                                                            {{ $dataTc2->judul_keterangan }}
                                                        </div>
                                                        <div class="card-body">
                                                            <table class="table table-borderless">
                                                                <tbody>
                                                                    <tr>
                                                                        <td>1.</td>
                                                                        <td>{{ $dataTc2->deskripsi_1 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>2.</td>
                                                                        <td>{{ $dataTc2->deskripsi_2 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>3.</td>
                                                                        <td>{{ $dataTc2->deskripsi_3 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>4.</td>
                                                                        <td>{{ $dataTc2->deskripsi_4 }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Deskripsi Additional -->
                                                <div class="col-md-4">
                                                    <div class="card card-equal-height mb-3">
                                                        <div class="card-header"
                                                            style="background-color: orange; color: white;">
                                                            {{ $dataTc3->judul_keterangan }}
                                                        </div>
                                                        <div class="card-body">
                                                            <table class="table table-borderless">
                                                                <tbody>
                                                                    <tr>
                                                                        <td>1.</td>
                                                                        <td>{{ $dataTc3->deskripsi_1 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>2.</td>
                                                                        <td>{{ $dataTc3->deskripsi_2 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>3.</td>
                                                                        <td>{{ $dataTc3->deskripsi_3 }}</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td>4.</td>
                                                                        <td>{{ $dataTc3->deskripsi_4 }}</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 style="margin-top: 3%"><b>Tambah Data Non-Competency(Soft Skills)</b></h5>
                                <div id="fieldsContainer2">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="keterangan_sk_0">Soft Skills</label>
                                            <input type="text" name="soft_skills[keterangan_sk][]"
                                                id="keterangan_sk_0" class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="keterangan_tc_0">Deskripsi</label>
                                            <input type="text" name="soft_skills[deskripsi_sk][]" id="deskripsi_sk_0"
                                                class="form-control" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="id_poin_kategori_sk_0">Kategori Nilai</label>
                                            <select name="soft_skills[id_poin_kategori][]" id="id_poin_kategori_sk_0"
                                                class="form-control">
                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                <option value="1">Skill of Process Plant</option>
                                                <option value="2">Skill of Process Office & Quality</option>
                                                <option value="3">Skill of EHS</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="nilai_sk_0">Standar Nilai</label>
                                            <select name="soft_skills[nilai][]" id="nilai_sk_0" class="form-control">
                                                <option value=""> ---- Pilih Standar Nilai ----</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-success" onclick="addFields2()">+</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group" style="margin-top: 2%">
                                        <div class="card">
                                            <div class="card-header bg-secondary text-white">
                                                Keterangan Penilaian
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <!-- Deskripsi Technical Competency -->
                                                    <div class="col-md-4">
                                                        <div class="card card-equal-height mb-3">
                                                            <div class="card-header bg-primary text-white">
                                                                {{ $dataTc1->judul_keterangan }}
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1.</td>
                                                                            <td>{{ $dataTc1->deskripsi_1 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>2.</td>
                                                                            <td>{{ $dataTc1->deskripsi_2 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>3.</td>
                                                                            <td>{{ $dataTc1->deskripsi_3 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>4.</td>
                                                                            <td>{{ $dataTc1->deskripsi_4 }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Deskripsi Soft Skills -->
                                                    <div class="col-md-4">
                                                        <div class="card card-equal-height mb-3">
                                                            <div class="card-header bg-success text-white">
                                                                {{ $dataTc2->judul_keterangan }}
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1.</td>
                                                                            <td>{{ $dataTc2->deskripsi_1 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>2.</td>
                                                                            <td>{{ $dataTc2->deskripsi_2 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>3.</td>
                                                                            <td>{{ $dataTc2->deskripsi_3 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>4.</td>
                                                                            <td>{{ $dataTc2->deskripsi_4 }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Deskripsi Additional -->
                                                    <div class="col-md-4">
                                                        <div class="card card-equal-height mb-3">
                                                            <div class="card-header"
                                                                style="background-color: orange; color: white;">
                                                                {{ $dataTc3->judul_keterangan }}
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1.</td>
                                                                            <td>{{ $dataTc3->deskripsi_1 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>2.</td>
                                                                            <td>{{ $dataTc3->deskripsi_2 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>3.</td>
                                                                            <td>{{ $dataTc3->deskripsi_3 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>4.</td>
                                                                            <td>{{ $dataTc3->deskripsi_4 }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 style="margin-top: 3%"><b>Tambah Data Additional</b></h5>
                                <div id="fieldsContainer3">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="keterangan_ad_0">Additional</label>
                                            <input type="text" name="additional[keterangan_ad][]" id="keterangan_ad_0"
                                                class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="deskripsi_ad_0">Deskripsi</label>
                                            <input type="text" name="additional[deskripsi_ad][]" id="deskripsi_ad_0"
                                                class="form-control">
                                        </div>
                                        <div class="form-group">
                                            <label for="id_poin_kategori_ad_0">Kategori Nilai</label>
                                            <select name="additional[id_poin_kategori][]" id="id_poin_kategori_ad_0"
                                                class="form-control">
                                                <option value="">---- Pilih Kategori Nilai ----</option>
                                                <option value="1">Skill of Process Plant</option>
                                                <option value="2">Skill of Process Office & Quality</option>
                                                <option value="3">Skill of EHS</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="nilai_ad_0">Standar Nilai</label>
                                            <select name="additional[nilai][]" id="nilai_ad_0" class="form-control">
                                                <option value=""> ---- Pilih Standar Nilai ----</option>
                                                <option value="1">1</option>
                                                <option value="2">2</option>
                                                <option value="3">3</option>
                                                <option value="4">4</option>
                                            </select>
                                        </div>
                                        <button type="button" class="btn btn-success" onclick="addFields3()">+</button>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="form-group" style="margin-top: 2%">
                                        <div class="card">
                                            <div class="card-header bg-secondary text-white">
                                                Keterangan Penilaian
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <!-- Deskripsi Technical Competency -->
                                                    <div class="col-md-4">
                                                        <div class="card card-equal-height mb-3">
                                                            <div class="card-header bg-primary text-white">
                                                                {{ $dataTc1->judul_keterangan }}
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1.</td>
                                                                            <td>{{ $dataTc1->deskripsi_1 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>2.</td>
                                                                            <td>{{ $dataTc1->deskripsi_2 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>3.</td>
                                                                            <td>{{ $dataTc1->deskripsi_3 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>4.</td>
                                                                            <td>{{ $dataTc1->deskripsi_4 }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Deskripsi Soft Skills -->
                                                    <div class="col-md-4">
                                                        <div class="card card-equal-height mb-3">
                                                            <div class="card-header bg-success text-white">
                                                                {{ $dataTc2->judul_keterangan }}
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1.</td>
                                                                            <td>{{ $dataTc2->deskripsi_1 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>2.</td>
                                                                            <td>{{ $dataTc2->deskripsi_2 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>3.</td>
                                                                            <td>{{ $dataTc2->deskripsi_3 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>4.</td>
                                                                            <td>{{ $dataTc2->deskripsi_4 }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Deskripsi Additional -->
                                                    <div class="col-md-4">
                                                        <div class="card card-equal-height mb-3">
                                                            <div class="card-header"
                                                                style="background-color: orange; color: white;">
                                                                {{ $dataTc3->judul_keterangan }}
                                                            </div>
                                                            <div class="card-body">
                                                                <table class="table table-borderless">
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>1.</td>
                                                                            <td>{{ $dataTc3->deskripsi_1 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>2.</td>
                                                                            <td>{{ $dataTc3->deskripsi_2 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>3.</td>
                                                                            <td>{{ $dataTc3->deskripsi_3 }}</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>4.</td>
                                                                            <td>{{ $dataTc3->deskripsi_4 }}</td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center align-items-center" style="margin-top: 3%">
                                <a href="{{ route('tcShow') }}" class="btn btn-secondary mx-2">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
                                <button type="submit" class="btn btn-primary mx-2" style="background-color: #007BFF">
                                    <i class="fas fa-save me-2"></i>Submit
                                </button>
                            </div>
                        </div>
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

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>
        <script>
            document.getElementById('combinedForm').addEventListener('submit', function(event) {
                event.preventDefault();

                // Ambil elemen yang diperlukan
                const jobPositionElement = document.getElementById('job_position_tc');
                const tcKeteranganElements = document.querySelectorAll('input[name="tc[keterangan_tc][]"]');
                const tcDeskripsiElements = document.querySelectorAll('input[name="tc[deskripsi_tc][]"]');
                const tcKategoriElements = document.querySelectorAll('select[name="tc[id_poin_kategori][]"]');
                const tcNilaiElements = document.querySelectorAll('select[name="tc[nilai][]"]');

                const softSkillsKeteranganElements = document.querySelectorAll(
                    'input[name="soft_skills[keterangan_sk][]"]');
                const softSkillsDeskripsiElements = document.querySelectorAll(
                    'input[name="soft_skills[deskripsi_sk][]"]');
                const softSkillsKategoriElements = document.querySelectorAll(
                    'select[name="soft_skills[id_poin_kategori][]"]');
                const softSkillsNilaiElements = document.querySelectorAll('select[name="soft_skills[nilai][]"]');

                const additionalKeteranganElements = document.querySelectorAll(
                    'input[name="additional[keterangan_ad][]"]');
                const additionalDeskripsiElements = document.querySelectorAll(
                    'input[name="additional[deskripsi_ad][]"]');
                const additionalKategoriElements = document.querySelectorAll(
                    'select[name="additional[id_poin_kategori][]"]');
                const additionalNilaiElements = document.querySelectorAll('select[name="additional[nilai][]"]');

                // Kumpulkan data dari form
                const data = {
                    tc: {
                        id_job_position: jobPositionElement ? jobPositionElement.value : '',
                        keterangan_tc: Array.from(tcKeteranganElements).map(el => el.value),
                        deskripsi_tc: Array.from(tcDeskripsiElements).map(el => el.value),
                        id_poin_kategori: Array.from(tcKategoriElements).map(el => el.value),
                        nilai: Array.from(tcNilaiElements).map(el => el.value)
                    },
                    soft_skills: {
                        keterangan_sk: Array.from(softSkillsKeteranganElements).map(el => el.value),
                        deskripsi_sk: Array.from(softSkillsDeskripsiElements).map(el => el.value),
                        id_poin_kategori: Array.from(softSkillsKategoriElements).map(el => el.value),
                        nilai: Array.from(softSkillsNilaiElements).map(el => el.value)
                    },
                    additional: {
                        keterangan_ad: Array.from(additionalKeteranganElements).map(el => el.value),
                        deskripsi_ad: Array.from(additionalDeskripsiElements).map(el => el.value),
                        id_poin_kategori: Array.from(additionalKategoriElements).map(el => el.value),
                        nilai: Array.from(additionalNilaiElements).map(el => el.value)
                    }
                };

                fetch('{{ route('mst_tc.store') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}' // Pastikan token CSRF disertakan
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log('Sukses:', data);
                        // Tampilkan SweetAlert dengan pesan sukses dan pengalihan otomatis
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data berhasil disimpan.',
                            didClose: () => {
                                // Kembali ke route setelah SweetAlert ditutup
                                window.location.href = '{{ route('tcShow') }}';
                            }
                        });
                        document.getElementById('combinedForm').reset();
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                        // Tampilkan SweetAlert dengan pesan kesalahan
                        Swal.fire({
                            icon: 'error',
                            title: 'Terjadi kesalahan!',
                            text: 'Data tidak berhasil disimpan.',
                            timer: 2000, // Durasi tampilan pesan (dalam milidetik)
                            timerProgressBar: true,
                            showConfirmButton: false // Hapus tombol OK
                        });
                    });

            });

            // Fungsi untuk menambahkan field baru untuk Technical Competency
            function addFields() {
                const container = document.getElementById('poinFieldsContainer');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'form-row';
                newFieldGroup.style.marginTop = '2%';
                newFieldGroup.innerHTML = `
        <div class="form-group">
            <label for="keterangan_tc_${container.children.length}">Competency</label>
            <input type="text" name="tc[keterangan_tc][]" id="keterangan_tc_${container.children.length}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="deskripsi_tc_${container.children.length}">Deskripsi</label>
            <input type="text" name="tc[deskripsi_tc][]" id="deskripsi_tc_${container.children.length}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="id_poin_kategori_tc_${container.children.length}">Kategori Nilai</label>
            <select name="tc[id_poin_kategori][]" id="id_poin_kategori_tc_${container.children.length}" class="form-control">
                <option value="">---- Pilih Kategori Nilai ----</option>
                <option value="1">Skill of Process Plant</option>
                <option value="2">Skill of Process Office & Quality</option>
                <option value="3">Skill of EHS</option>
            </select>
        </div>
        <div class="form-group">
            <label for="nilai_tc_${container.children.length}">Standar Nilai</label>
            <select name="tc[nilai][]" id="nilai_tc_${container.children.length}" class="form-control">
                 <option value=""> ---- Pilih Standar Nilai ----</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>
        <button type="button" class="btn btn-danger" onclick="removeFields(this)">-</button>
    `;
                container.appendChild(newFieldGroup);
            }

            // Fungsi untuk menambahkan field baru untuk Soft Skills
            function addFields2() {
                const container = document.getElementById('fieldsContainer2');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'form-row';
                newFieldGroup.style.marginTop = '2%';
                newFieldGroup.innerHTML = `
        <div class="form-group">
            <label for="keterangan_sk_${container.children.length}">Soft Skills</label>
            <input type="text" name="soft_skills[keterangan_sk][]" id="keterangan_sk_${container.children.length}" class="form-control">
        </div>
        <div class="form-group">
            <label for="deskripsi_sk_${container.children.length}">Deskripsi</label>
            <input type="text" name="soft_skills[deskripsi_sk][]" id="deskripsi_sk_${container.children.length}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="id_poin_kategori_sk_${container.children.length}">Kategori Nilai</label>
            <select name="soft_skills[id_poin_kategori][]" id="id_poin_kategori_sk_${container.children.length}" class="form-control">
                <option value="">---- Pilih Kategori Nilai ----</option>
                <option value="1">Skill of Process Plant</option>
                <option value="2">Skill of Process Office & Quality</option>
                <option value="3">Skill of EHS</option>
            </select>
        </div>
        <div class="form-group">
            <label for="nilai_sk_${container.children.length}">Standar Nilai</label>
            <select name="soft_skills[nilai][]" id="nilai_sk_${container.children.length}" class="form-control">
                 <option value=""> ---- Pilih Standar Nilai ----</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>
        <button type="button" class="btn btn-danger" onclick="removeFields(this)">-</button>
    `;
                container.appendChild(newFieldGroup);
            }

            // Fungsi untuk menambahkan field baru untuk Additional
            function addFields3() {
                const container = document.getElementById('fieldsContainer3');
                const newFieldGroup = document.createElement('div');
                newFieldGroup.className = 'form-row';
                newFieldGroup.style.marginTop = '2%';
                newFieldGroup.innerHTML = `
        <div class="form-group">
            <label for="keterangan_ad_${container.children.length}">Additional</label>
            <input type="text" name="additional[keterangan_ad][]" id="keterangan_ad_${container.children.length}" class="form-control">
        </div>
        <div class="form-group">
            <label for="deskripsi_ad_${container.children.length}">Deskripsi</label>
            <input type="text" name="additional[deskripsi_ad][]" id="deskripsi_ad_${container.children.length}" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="id_poin_kategori_ad_${container.children.length}">Kategori Nilai</label>
            <select name="additional[id_poin_kategori][]" id="id_poin_kategori_ad_${container.children.length}" class="form-control">
                <option value="">---- Pilih Kategori Nilai ----</option>
                <option value="1">Skill of Process Plant</option>
                <option value="2">Skill of Process Office & Quality</option>
                <option value="3">Skill of EHS</option>
            </select>
        </div>
        <div class="form-group">
            <label for="nilai_ad_${container.children.length}">Standar Nilai</label>
            <select name="additional[nilai][]" id="nilai_ad_${container.children.length}" class="form-control">
                 <option value=""> ---- Pilih Standar Nilai ----</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
        </div>
        <button type="button" class="btn btn-danger" onclick="removeFields(this)">-</button>
    `;
                container.appendChild(newFieldGroup);
            }

            // Fungsi untuk menghapus field
            function removeFields(button) {
                button.parentElement.remove();
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
                                    li.textContent = employee.name; // Mengambil nama dari data employee
                                    employeeList.appendChild(li);
                                });

                                $('#employeeModal').modal('show');
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
        </script>
    </main><!-- End #main -->
@endsection
