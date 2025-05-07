@extends('layout')

@section('content')
<main id="main" class="main">
    <style>
        /* Profil pengguna */
        .user-profile {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            width: 100%;
        }

        .profile-icon {
            font-size: 50px;
            color: #555;
            margin-right: 15px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .user-name,
        .user-job {
            font-weight: bold;
            font-size: 14px;
            margin: 2px 0;
        }

        /* Container for the radar charts */
        #radarChartContainer {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: 20px;
            /* Tambahkan gap untuk spasi antar card */
            margin-top: 20px;
        }

        /* Each card containing a radar chart */
        .chart-card {
            background-color: #fff;
            border: 1px solid #000000;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 15px;
            width: calc(33.33% - 20px);
            /* Kalkulasi lebar agar 3 card muat dalam 1 baris */
            box-sizing: border-box;
        }

        /* Card title */
        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }

        /* Adjustments for the canvas to ensure it fits within the card */
        .chart-card canvas {
            width: 100% !important;
            height: auto !important;
            display: block;
            margin: 0 auto;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 992px) {
            .chart-card {
                width: calc(50% - 20px);
                /* Setengah lebar jika ruang terbatas */
            }
        }

        @media (max-width: 768px) {
            .chart-card {
                width: 100%;
                /* Full width untuk layar kecil */
                max-width: 100%;
            }
        }

        /* tabel */
        .card {
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: #f9f9f9;
        }

        .card-header {
            background-color: #007bff;
            color: white;
            padding: 10px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #ccc;
            border-radius: 5px 5px 0 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f1f1f1;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ccc;
        }

        th[rowspan="2"] {
            vertical-align: middle;
        }

        td,
        th {
            padding: 10px;
            border: 1px solid #ccc;
            white-space: nowrap;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        input[type="text"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;

        }

        @media (max-width: 768px) {

            th,
            td {
                font-size: 12px;
                padding: 5px;
            }
        }

        th,
        td {
            text-align: center;
            vertical-align: middle;
        }

        th[rowspan="2"] {
            vertical-align: middle;
        }

        /* Optional: Center the text inside input fields as well */
        input[type="text"] {
            text-align: center;
            font-family: 'Cambria', serif;
        }

        .fonav {
            font-family: 'Cambria', serif;
        }

        .custom-line {
            border: none;
            border-top: 1px solid #000;
            margin: 0;
            padding: 0;
        }
    </style>
    <div class="pagetitle">
        <h1>Profile</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('forumSS') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Data Diri</li>
            </ol>
        </nav>
    </div><!-- End Page Title -->

    <section class="section profile">
        <div class="row">
            <div class="col-xl-12">
                <div class="card">
                    <div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
                        @if ($user->file)
                        <img src="{{ asset('assets/data_diri/' . $user->file) }}" alt="Profile" class="rounded-circle"
                            style="width: 150px; height: 120px; cursor: pointer;" data-bs-toggle="modal"
                            data-bs-target="#imageModal">
                        @else
                        <img src="{{ asset('assets/img/user.png') }}" alt="Default Profile" class="rounded-circle"
                            style="width: 150px; height: 120px; cursor: pointer;" data-bs-toggle="modal"
                            data-bs-target="#imageModal">
                        @endif
                        <h2 class="fonav">{{ Auth::user()->name }} - {{ Auth::user()->km_total_poin }}
                            <span class="fs-5">Poin</span>
                            <br>
                        </h2>

                        <span class="fonav fs-6 fw-bold">--- {{ Auth::user()->roles->role }} ---</span>
                        <h3 class="fonav">
                            @foreach ($jobPositions as $position)
                            <div>{{ $position->job_position }}</div>
                            @endforeach
                        </h3>
                    </div>
                    <!-- Modal -->
                    <div class="modal fade" id="imageModal" tabindex="-1" role="dialog"
                        aria-labelledby="imageModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-body">
                                    <img src="" id="modalImage" style="width: 100%;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body pt-3 fonav">
                        <!-- Bordered Tabs -->
                        <ul class="nav nav-tabs nav-tabs-bordered">

                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab"
                                    data-bs-target="#profile-overview">Data Diri</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Data
                                    Diri</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab"
                                    data-bs-target="#profile-change-password">Ubah Password</button>
                            </li>

                        </ul>

                        <div class="tab-content pt-2">
                            {{-- Interface Data Diri --}}
                            <div class="tab-pane fade show active profile-overview" id="profile-overview">
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">Nama Lengkap</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="{{ $user->name }}" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">Section</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="{{ $user->section }}" disabled>
                                    </div>
                                </div>
                                {{-- <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">Posisi</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="@foreach ($jobPositions as $position)
                                                {{ $position->job_position }} @endforeach" disabled>
                                    </div>
                                </div> --}}
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">Posisi</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control"
                                            value="{{ implode(', ', $jobPositions->pluck('job_position')->toArray()) }}"
                                            disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">NPK</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="{{ $user->npk }}" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">No. Telp</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="{{ $user->telp }}" disabled>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-sm-2 col-form-label">Email</label>
                                    <div class="col-sm-10">
                                        <input type="text" class="form-control" value="{{ $user->email }}" disabled>
                                    </div>
                                </div>
                            </div>
                            {{-- End Interface Data Diri --}}

                            <div class="tab-pane fade profile-edit pt-3" id="profile-edit">
                                <!-- Profile Edit Form -->
                                <form id="ubahDataDiriForm" action="{{ route('ubahDataDiri') }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="row mb-3">
                                        <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Nama
                                            lengkap</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="name" type="text" class="form-control" id="name"
                                                value="{{ $user->name }}" disabled>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="about" class="col-md-4 col-lg-3 col-form-label">Section</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="name" type="text" class="form-control" id="name"
                                                value="{{ $user->section }}" disabled>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="job_positions"
                                            class="col-md-4 col-lg-3 col-form-label">Posisi</label>
                                        <div class="col-md-8 col-lg-9">
                                            @foreach ($jobPositions as $position)
                                            <input name="job_position" type="text" class="form-control mb-2"
                                                value="{{ $position->job_position }}" disabled>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="company" class="col-md-4 col-lg-3 col-form-label">NPK</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="npk" type="text" class="form-control" id="company"
                                                value="{{ $user->npk }}" disabled>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="Job" class="col-md-4 col-lg-3 col-form-label">No.
                                            Telp</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="telp" type="text" class="form-control" id="telp"
                                                value="{{ $user->telp }}">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="Country" class="col-md-4 col-lg-3 col-form-label">Email</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="email" type="text" class="form-control" id="email"
                                                value="{{ $user->email }}">
                                        </div>
                                    </div>

                                    @php
                                    // Mendapatkan role_id dari pengguna yang sedang login
                                    $roleId = auth()->user()->role_id;
                                    @endphp

                                    @if (in_array($roleId, [1, 14, 15]))
                                    <div class="row mb-3">
                                        <label for="file" class="col-md-4 col-lg-3 col-form-label">Upload
                                            Image</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input type="file" name="file" class="form-control" id="file"
                                                accept="image/png, image/jpeg, image/jpg, image/gif">
                                        </div>
                                    </div>
                                    @endif

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary"
                                            onclick="submitDataDiri()">Save</button>
                                    </div>
                                </form><!-- End Profile Edit Form -->
                            </div>
                            <div class="tab-pane fade pt-3" id="profile-change-password">
                                <!-- Change Password Form -->
                                <form action="{{ route('ubahPassword') }}" method="post">
                                    @csrf
                                    <div class="row mb-3">
                                        <label for="currentPassword" class="col-md-4 col-lg-3 col-form-label">Password
                                            Anda Saat Ini</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="currentPassword" type="password" class="form-control"
                                                id="currentPassword" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="newPassword" class="col-md-4 col-lg-3 col-form-label">Password
                                            Baru</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="newPassword" type="password" class="form-control"
                                                id="newPassword" required>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <label for="newPassword_confirmation"
                                            class="col-md-4 col-lg-3 col-form-label">Konfirmasi Password Baru</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="newPassword_confirmation" type="password" class="form-control"
                                                id="newPassword_confirmation" required>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary"
                                            onclick="return validatePasswordChange();">Ubah Password</button>
                                    </div>
                                </form><!-- End Change Password Form -->

                            </div>
                        </div><!-- End Bordered Tabs -->
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card mt-3">
                    <div class="card-header">
                        <div style="justify-content: space-between; align-items: center;">

                            <h4><b>Histori Development</b></h4>
                        </div>

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; gap: 50px; margin-top: 2%;">

                            <!-- Input field for search -->
                            <input type="text" id="searchInput" placeholder="Cari Data Disemua Kolom..."
                                style="padding: 5px; width: 250px;">
                        </div>

                    </div>

                    <div style="overflow-x: auto; white-space: nowrap;">
                        <table border="1" cellpadding="5" cellspacing="0" class="table table-bordered mt-3">
                            <thead>
                                <tr>
                                    <th>Nama Employee</th>
                                    <th>Nama Program</th>
                                    <th>Kategori Competency</th>
                                    <th>Periode Actual</th>
                                    <th>File Download</th>
                                </tr>
                            </thead>
                            <tbody id="peopleDevTabel">
                                <!-- Initially populated from server-side -->
                                @if ($dataTcPeopleDevelopment->isEmpty())
                                <tr>
                                    <td colspan="6" style="text-align: center;">Tidak ada data tersedia</td>
                                </tr>
                                @else
                                @foreach ($dataTcPeopleDevelopment as $dataTcPeopleDevelopment)
                                <tr>
                                    <td>
                                        {{ $dataTcPeopleDevelopment->user->name }}
                                        <input type="hidden" name="data_ids[]"
                                            value="{{ $dataTcPeopleDevelopment->id }}">
                                    </td>
                                    <td>{{ $dataTcPeopleDevelopment->program_training_plan }}</td>
                                    <td>{{ $dataTcPeopleDevelopment->kategori_competency }}</td>
                                    <td>{{ $dataTcPeopleDevelopment->due_date_plan }}</td>
                                    <td>
                                        <button onclick="downloadPdf({{ $dataTcPeopleDevelopment->id }})"
                                            style="cursor: pointer; background-color: transparent; border: none; color: blue; text-decoration: underline;">
                                            <i class="bi bi-download"></i>
                                        </button>
                                        <!-- Tombol Update Evaluasi -->
                                        <button class="btn btn-primary btn-sm"
                                            onclick="showModal({{ $dataTcPeopleDevelopment }})">
                                            <i class="fas fa-eye"></i> View Evaluasi
                                        </button>

                                        <!-- Tombol Print PDF -->
                                        <button id="printPdf" data-id="{{ $dataTcPeopleDevelopment->id }}"
                                            class="btn btn-success">Print PDF</button>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Evaluasi -->
        <div class="modal fade" id="modalEvaluasi" tabindex="-1" aria-labelledby="modalEvaluasiLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modalEvaluasiLabel">Formulir Evaluasi Hasil Pelatihan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form>
                            <!-- Informasi Peserta -->
                            <div class="mb-3">
                                <label for="section" class="form-label"><strong>Seksi</strong></label>
                                <input type="text" id="section" class="form-control" readonly>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="npk" class="form-label"><strong>NPK</strong></label>
                                    <input type="text" id="npk" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="nama" class="form-label"><strong>Nama</strong></label>
                                    <input type="text" id="nama" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="program_training" class="form-label"><strong>Program
                                            Pelatihan</strong></label>
                                    <input type="text" id="program_training" class="form-control" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="penyelenggara" class="form-label"><strong>Penyelenggara</strong></label>
                                    <input type="text" id="penyelenggara" class="form-control" readonly>
                                </div>
                            </div>

                            <!-- Evaluasi Materi -->
                            <div class="card my-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">Evaluasi Materi</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="relevansi" class="form-label"><strong>Relevansi bagi
                                                peserta</strong></label>
                                        <input type="text" id="relevansi" class="form-control" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="alasan_relevansi" class="form-label"><strong>Alasan</strong></label>
                                        <textarea id="alasan_relevansi" class="form-control" rows="3"
                                            readonly></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="rekomendasi" class="form-label"><strong>Rekomendasi
                                                selanjutnya</strong></label>
                                        <input type="text" id="rekomendasi" class="form-control" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label for="alasan_rekomendasi"
                                            class="form-label"><strong>Alasan</strong></label>
                                        <textarea id="alasan_rekomendasi" class="form-control" rows="3"
                                            readonly></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Evaluasi Penyelenggaraan -->
                            <div class="card my-4">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">Evaluasi Penyelenggaraan</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="kelengkapan_materi" class="form-label"><strong>Kelengkapan
                                                    Materi</strong></label>
                                            <input type="text" id="kelengkapan_materi" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lokasi" class="form-label"><strong>Lokasi
                                                    Penyelenggaraan</strong></label>
                                            <input type="text" id="lokasi" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="metode_pengajaran" class="form-label"><strong>Metode
                                                    Pengajaran</strong></label>
                                            <input type="text" id="metode_pengajaran" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fasilitas" class="form-label"><strong>Fasilitas</strong></label>
                                            <input type="text" id="fasilitas" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="lainnya_1" class="form-label"><strong>Lainnya</strong></label>
                                        <textarea id="lainnya_1" class="form-control" rows="3" readonly></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Evaluasi Peserta -->
                            <div class="card my-4">
                                <div class="card-header bg-info text-white">
                                    <h5 class="mb-0">Evaluasi Peserta</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="metode_evaluasi" class="form-label"><strong>Metode
                                                    Evaluasi</strong></label>
                                            <input type="text" id="metode_evaluasi" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="minat" class="form-label"><strong>Minat
                                                    Pelatihan</strong></label>
                                            <input type="text" id="minat" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="daya_serap" class="form-label"><strong>Daya Serap
                                                    Peserta</strong></label>
                                            <input type="text" id="daya_serap" class="form-control" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="penerapan" class="form-label"><strong>Penerapan dalam
                                                    Tugas</strong></label>
                                            <input type="text" id="penerapan" class="form-control" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="lainnya_2" class="form-label"><strong>Lainnya</strong></label>
                                        <textarea id="lainnya_2" class="form-control" rows="3" readonly></textarea>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="efektif" class="form-label"><strong>Apakah Pelatihan Ini
                                                Dinyatakan Efektif?
                                            </strong></label>
                                        <input type="text" id="efektif" class="form-control" readonly>
                                        <label for="lainnya_2" class="form-label"><strong>Catatan
                                                Tambahan</strong></label>
                                        <textarea id="catatan_tambahan" class="form-control" rows="3"
                                            readonly></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Tanda Tangan -->
                            <div class="card my-4">
                                <div class="card-header bg-secondary text-white">
                                    <h5 class="mb-0">Tanda Tangan</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="dievaluasi" class="form-label"><strong>Diketahui
                                                    oleh:</strong></label>
                                            <br>
                                            <label for="tgl_konfirm" class="form-label"><strong>Tgl:</strong></label>
                                            <label id="tgl_konfirm" class="form-control"
                                                style="display: block;"></label>
                                            <br><br>
                                            <label id="dievaluasi" class="form-control"
                                                style="display: block; margin-top: 3%;">Jessica Paune</label>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="diketahui" class="form-label"><strong>Dievaluasi
                                                    oleh:</strong></label>
                                            <br>
                                            <label for="tgl_konfirm" class="form-label"><strong>Tgl:</strong></label>
                                            <label id="tgl_konfirm2" class="form-control"
                                                style="display: block;"></label>
                                            <br><br>
                                            <label id="diketahui" class="form-control"
                                                style="display: block; margin-top: 3%;"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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

    <script>
        function validatePasswordChange() {
                var currentPassword = document.getElementById('currentPassword').value;
                var newPassword = document.getElementById('newPassword').value;
                var confirmPassword = document.getElementById('newPassword_confirmation').value;
                var currentPasswordStored = "{{ Auth::user()->pass }}"; // Password awal yang disimpan di database

                if (!currentPassword || !newPassword || !confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Mohon isi semua field dengan benar!'
                    });
                    return false;
                }

                if (currentPassword !== currentPasswordStored) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Password anda saat ini tidak sesuai!'
                    });
                    return false;
                }

                if (newPassword.length < 8) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Password baru harus memiliki minimal 8 karakter!'
                    });
                    return false;
                }

                if (newPassword === currentPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Password baru harus berbeda dengan password Anda saat ini!'
                    });
                    return false;
                }

                if (newPassword !== confirmPassword) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'Konfirmasi password baru tidak sesuai dengan password baru!'
                    });
                    return false;
                }

                // Jika semua validasi berhasil
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: 'Password berhasil diubah.',
                    showConfirmButton: false // Hilangkan tombol OK
                });
                return true;
            }

            function submitDataDiri() {
                // Submit form menggunakan JavaScript
                document.getElementById('ubahDataDiriForm').submit();

                // Tampilkan SweetAlert setelah form disubmit
                Swal.fire({
                    title: 'Success!',
                    text: 'Data berhasil disimpan.',
                    icon: 'success',
                    showConfirmButton: false
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                var images = document.querySelectorAll('img[data-bs-toggle="modal"]');
                var modalImage = document.getElementById('modalImage');

                images.forEach(function(img) {
                    img.addEventListener('click', function() {
                        modalImage.src = this.src;
                    });
                });

                var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));

                images.forEach(function(img) {
                    img.addEventListener('click', function() {
                        modalImage.src = this.src;
                        imageModal.show();
                    });
                });
            });

            // Tambahkan event listener untuk fitur pencarian
            document.getElementById('searchInput').addEventListener('input', function() {
                var searchTerm = this.value.toLowerCase();
                var tableRows = document.querySelectorAll('#peopleDevTabel tr');

                tableRows.forEach(function(row) {
                    var rowText = row.textContent.toLowerCase();
                    if (rowText.indexOf(searchTerm) > -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            function downloadPdf(id) {
                var downloadPdfUrl = "{{ route('download.pdf', ['id' => ':id']) }}";
                var url = downloadPdfUrl.replace(':id', id);
                window.location.href = url; // Redirect to the download URL
            }

            function showModal(data) {
                // Isi field modal dengan data dari baris yang dipilih
                document.getElementById('section').value = data.section || '';
                document.getElementById('npk').value = data.user ? data.user.npk : '';
                document.getElementById('nama').value = data.user ? data.user.name : '';
                document.getElementById('program_training').value = data.program_training_plan || '';
                document.getElementById('penyelenggara').value = data.lembaga || '-';

                // Evaluasi Materi
                document.getElementById('relevansi').value = data.relevansi || '';
                document.getElementById('alasan_relevansi').value = data.alasan_relevansi || '';
                document.getElementById('rekomendasi').value = data.rekomendasi || '';
                document.getElementById('alasan_rekomendasi').value = data.alasan_rekomendasi || '';

                // Evaluasi Penyelenggaraan
                document.getElementById('kelengkapan_materi').value = data.kelengkapan_materi || '';
                document.getElementById('lokasi').value = data.lokasi || '';
                document.getElementById('metode_pengajaran').value = data.metode_pengajaran || '';
                document.getElementById('fasilitas').value = data.fasilitas || '';
                document.getElementById('lainnya_1').value = data.lainnya_1 || '';
                document.getElementById('efektif').value = data.efektif || '';
                document.getElementById('catatan_tambahan').value = data.catatan_tambahan || '';

                // Evaluasi Peserta
                document.getElementById('metode_evaluasi').value = data.metode_evaluasi || '';
                document.getElementById('minat').value = data.minat || '';
                document.getElementById('daya_serap').value = data.daya_serap || '';
                document.getElementById('penerapan').value = data.penerapan || '';
                document.getElementById('lainnya_2').value = data.lainnya_2 || '';
                document.getElementById('tgl_konfirm').innerText = data.tgl_pengajuan || '';
                document.getElementById('tgl_konfirm2').innerText = data.tgl_pengajuan || '';
                document.getElementById('diketahui').innerText = data.diketahui || '';

                // Tampilkan modal
                const modal = new bootstrap.Modal(document.getElementById('modalEvaluasi'));
                modal.show();
            }

            document.getElementById('printPdf').addEventListener('click', function() {
                const id = this.getAttribute('data-id'); // Ambil ID dari atribut tombol

                // Route ke endpoint Laravel menggunakan ID
                const url = `{{ route('export-pdf', ':id') }}`.replace(':id', id);
                const imgURL = `{{ asset('assets/foto/AdasiLogo.png') }}`; // Path gambar di folder public

                // Fetch data dari server
                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Gagal mengambil data dari server.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        const {
                            jsPDF
                        } = window.jspdf;

                        // Data dari server
                        const section = String(data.section || '-');
                        const peserta = String(data.user ? data.user.name : '-');
                        const npk = String(data.user ? data.user.npk : '-');
                        const program = String(data.program_training_plan || '-');
                        const penyelenggara = String(data.lembaga || '-');

                        const relevansi = String(data.relevansi || '-');
                        const alasanRelevansi = String(data.alasan_relevansi || '-');

                        const rekomendasi = String(data.rekomendasi || '-');
                        const alasanRekomendasi = String(data.alasan_rekomendasi || '-');

                        const kelengkapanMateri = String(data.kelengkapan_materi || '-');
                        const lokasi = String(data.lokasi || '-');
                        const metodePengajaran = String(data.metode_pengajaran || '-');
                        const fasilitas = String(data.fasilitas || '-');
                        const lainnyaPenyelenggara = String(data.lainnya_1 || '-');

                        const metodeEvaluasi = String(data.metode_evaluasi || '-');
                        const minat = String(data.minat || '-');
                        const dayaSerap = String(data.daya_serap || '-');
                        const penerapan = String(data.penerapan || '-');
                        const lainnyaPeserta = String(data.lainnya_2 || '-');

                        const efektif = String(data.efektif || '-');
                        const catatanTambahan = String(data.catatan_tambahan || '-');

                        const diketahuiOleh = String(data.diketahui || '-');
                        const diketahuiTanggal = String(data.tgl_pengajuan || '-');
                        const dievaluasiOleh = String(data.diketahui || '-');
                        const dievaluasiTanggal = String(data.tgl_pengajuan || '-');


                        // Tambahkan gambar menggunakan base64
                        const img = new Image();
                        img.src = imgURL;

                        img.onload = function() {
                            const pdf = new jsPDF({
                                orientation: 'portrait',
                                unit: 'mm',
                                format: 'a4',
                            });

                            // Tambahkan gambar ke header
                            const imgWidth = 60; // Lebar gambar dalam mm
                            const imgHeight = (img.height / img.width) *
                                imgWidth; // Sesuaikan tinggi berdasarkan proporsi

                            // Hitung posisi X untuk menempatkan gambar di tengah
                            const pageWidth = pdf.internal.pageSize.getWidth(); // Lebar halaman PDF
                            const imgX = (pageWidth - imgWidth) / 2; // Posisi X agar gambar berada di tengah

                            // Tambahkan gambar di tengah
                            pdf.addImage(img, 'PNG', imgX, 10, imgWidth, imgHeight);

                            // Tambahkan teks lainnya
                            pdf.setFontSize(12);
                            pdf.text("FORMULIR EVALUASI HASIL PELATIHAN", pageWidth / 2, 25, {
                                align: "center"
                            });


                            // Border utama
                            pdf.setDrawColor(0);
                            pdf.setLineWidth(0.5);
                            pdf.rect(10, 30, 190, 250);

                            // Data Peserta
                            pdf.setFontSize(10);
                            pdf.setFont("helvetica", "normal");
                            pdf.text("Seksi:", 12, 40);
                            pdf.text(section, 50, 40);

                            pdf.text("Peserta:", 12, 50);
                            pdf.text(peserta, 50, 50);
                            pdf.text("NPK:", 110, 50);
                            pdf.text(npk, 140, 50);

                            pdf.text("Program Pelatihan:", 12, 60);
                            pdf.text(program, 50, 60);
                            pdf.text("Penyelenggara:", 12, 70);
                            pdf.text(penyelenggara, 50, 70);

                            // Evaluasi Materi
                            pdf.setFont("helvetica", "bold");
                            pdf.text("EVALUASI - MATERI", 12, 80);
                            pdf.setDrawColor(0);
                            pdf.setLineWidth(0.2);
                            pdf.rect(10, 75, 190, 40);
                            pdf.setFont("helvetica", "normal");
                            pdf.text("1. Relevansi bagi peserta:", 12, 90);
                            pdf.text(`Jawaban: ${relevansi}`, 59, 90);
                            pdf.text("Alasan:", 12, 95);
                            pdf.text(alasanRelevansi, 50, 95);

                            pdf.text("2. Rekomendasi selanjutnya:", 12, 105);
                            pdf.text(`Jawaban: ${rekomendasi}`, 59, 105);
                            pdf.text("Alasan:", 12, 110);
                            pdf.text(alasanRekomendasi, 50, 110);

                            // Evaluasi Penyelenggara
                            pdf.setFont("helvetica", "bold");
                            pdf.text("EVALUASI - PENYELENGGARA", 12, 120);
                            pdf.rect(10, 115, 190, 60);
                            pdf.setFont("helvetica", "normal");
                            pdf.text("1. Kelengkapan Materi:", 12, 130);
                            pdf.text(kelengkapanMateri, 57, 130);

                            pdf.text("2. Lokasi Penyelenggaraan:", 12, 135);
                            pdf.text(lokasi, 57, 135);

                            pdf.text("3. Metode Pengajaran:", 12, 140);
                            pdf.text(metodePengajaran, 57, 140);

                            pdf.text("4. Fasilitas:", 12, 145);
                            pdf.text(fasilitas, 57, 145);

                            pdf.text("5. Lainnya:", 12, 150);
                            pdf.text(lainnyaPenyelenggara, 50, 150);

                            // Evaluasi Peserta
                            pdf.setFont("helvetica", "bold");
                            pdf.text("EVALUASI - PESERTA", 12, 180);
                            pdf.rect(10, 175, 190, 40);
                            pdf.setFont("helvetica", "normal");
                            pdf.text("1. Metode Evaluasi:", 12, 190);
                            pdf.text(metodeEvaluasi, 50, 190);

                            pdf.text("2. Minat Pelatihan:", 12, 195);
                            pdf.text(minat, 50, 195);

                            pdf.text("3. Daya Serap:", 12, 200);
                            pdf.text(dayaSerap, 50, 200);

                            pdf.text("4. Penerapan:", 12, 205);
                            pdf.text(penerapan, 50, 205);

                            pdf.text("5. Lainnya:", 12, 210);
                            pdf.text(lainnyaPeserta, 50, 210);

                            // Efektivitas
                            pdf.setFont("helvetica", "bold");
                            pdf.text("EFEKTIVITAS", 12, 220);
                            pdf.rect(10, 215, 190, 30);
                            pdf.setFont("helvetica", "bold");
                            pdf.text(`Apakah Pelatihan Ini Efektif? ${efektif}`, 12, 230);
                            pdf.text("Catatan Tambahan:", 12, 235);
                            pdf.text(catatanTambahan, 50, 235);

                            // Footer - Tanda Tangan
                            pdf.setFont("helvetica", "bold");
                            pdf.text("Diketahui oleh:", 12, 260);
                            pdf.text(diketahuiOleh, 50, 260);
                            pdf.text("Tgl:", 12, 265);
                            pdf.text(diketahuiTanggal, 50, 265);

                            pdf.text("Dievaluasi oleh:", 110, 260);
                            pdf.text(dievaluasiOleh, 150, 260);
                            pdf.text("Tgl:", 110, 265);
                            pdf.text(dievaluasiTanggal, 150, 265);

                            // Border untuk tanda tangan
                            pdf.rect(10, 255, 90, 15);
                            pdf.rect(110, 255, 90, 15);

                            // Simpan PDF
                            pdf.save(`Evaluasi_${peserta}.pdf`);
                        };

                        img.onerror = function() {
                            alert("Gambar tidak ditemukan. Pastikan path gambar sudah benar.");
                        };
                    })
                    .catch(error => {
                        console.error('Error fetching data:', error);
                        alert(`Terjadi kesalahan: ${error.message}`);
                    });
            });
    </script>
</main><!-- End #main -->
@endsection