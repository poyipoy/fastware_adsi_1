@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Engineering</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Bag. Maintenance</li>
                    <li class="breadcrumb-item active">Data Approved FPP</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Data Persetujuan FPP</h5>
                            <a class="btn btn-success mb-3" href="{{ url('downtimeExport') }}"
                                style="display: inline-block;">
                                <i class="bi bi-filetype-xlsx"></i> Ekspor Data
                            </a>
                            <div class="table-responsive">
                                <table class="datatables datatable" style="table-layout: responsive;">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Nomor FPP</th>
                                            <th scope="col">Mesin</th>
                                            <th scope="col">Bagian</th>
                                            <th scope="col">Lokasi</th>
                                            <th scope="col">Kendala</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Tanggal Dibuat</th>
                                            <th scope="col">Modifikasi Terakhir</th>
                                            <th scope="col">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($formperbaikans as $formperbaikan)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ $formperbaikan->id_fpp }}</td>
                                                <td>{{ $formperbaikan->mesin }}</td>
                                                <td>{{ $formperbaikan->section }}</td>
                                                <td>{{ $formperbaikan->lokasi }}</td>
                                                <td>{{ $formperbaikan->kendala }}</td>
                                                <td>
                                                    <div
                                                        style="background-color: {{ $formperbaikan->status_background_color }};
                                            border-radius: 5px; /* Rounded corners */
                                            padding: 5px 10px; /* Padding inside the div */
                                            color: white; /* Text color, adjust as needed */
                                            font-weight: bold; /* Bold text */
                                            text-align: center; /* Center-align text */
                                            text-transform: uppercase; /* Uppercase text */
                                            ">
                                                        {{ $formperbaikan->ubahtext() }}
                                                    </div>
                                                </td>

                                                <td>{{ $formperbaikan->created_at }}</td>
                                                <td>{{ $formperbaikan->updated_at }}</td>
                                                <td>
                                                    <a class="btn btn-warning"
                                                        href="{{ route('deptmtce.show', $formperbaikan->id) }}">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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


    </main><!-- End #main -->
@endsection

{{-- .datatables.datatable {
border-collapse: collapse;
/* Pastikan border tidak menggandakan diri */
width: 100%;
/* Sesuaikan lebar tabel */
}

.datatables.datatable th,
.datatables.datatable td {
border: 1px solid #000;
/* Ubah warna border sesuai kebutuhan */
padding: 8px;
/* Tambahkan padding untuk membuat teks lebih mudah dibaca */
text-align: left;
/* Sesuaikan alignment teks sesuai kebutuhan */
}

.datatables.datatable th {
background-color: #f2f2f2;
/* Beri warna latar belakang pada header tabe --}}
