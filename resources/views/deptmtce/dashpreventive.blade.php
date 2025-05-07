@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Data Tables</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.html">Home</a></li>
                    <li class="breadcrumb-item">Tables</li>
                    <li class="breadcrumb-item active">Data</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">List Data Jadwal Preventive</h5>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table class="datatables datatable" style="table-layout: responsive;">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Nama Mesin</th>
                                            <th scope="col">Type</th>
                                            <th scope="col">No Mesin</th>
                                            <th scope="col">Mulai Operasi</th>
                                            <th scope="col">Issue</th>
                                            <th scope="col">Rencana Perbaikan</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Created Date</th>
                                            <th scope="col">Last Update</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($mesins as $mesin)
                                            <tr>
                                                <td>{{ ++$i }}</td>
                                                <td>{{ $mesin->nama_mesin }}</td> <!-- Kolom dari tabel Mesin -->
                                                <td>{{ $mesin->type }}</td> <!-- Kolom dari tabel Mesin -->
                                                <td>{{ $mesin->no_mesin }}</td> <!-- Kolom dari tabel Mesin -->
                                                <td>{{ $mesin->mfg_date }}</td> <!-- Kolom dari tabel Mesin -->
                                                <td>
                                                    <a class="btn btn-primary"
                                                        href="{{ route('mesins.lihatissue', $mesin->id) }}">
                                                        <i class="bi bi-exclamation-triangle-fill"></i>
                                                    </a>
                                                </td>
                                                <td>
                                                    <a class="btn btn-primary"
                                                        href="{{ route('mesins.lihatperbaikan', $mesin->id) }}">
                                                        <i class="bi bi-gear-fill"></i>
                                                    </a>
                                                <td>
                                                    <div
                                                        style="background-color: {{ $mesin->status_background_color }};
                border-radius: 5px; /* Rounded corners */
                padding: 5px 10px; /* Padding inside the div */
                color: white; /* Text color, adjust as needed */
                font-weight: bold; /* Bold text */
                text-align: center; /* Center-align text */
                text-transform: uppercase; /* Uppercase text */
                ">
                                                        {{ $mesin->ubahtext() }}
                                                    </div>
                                                </td>
                                                <td>{{ $mesin->created_at }}</td> <!-- Kolom dari tabel Mesin -->
                                                <td>{{ $mesin->updated_at }}</td> <!-- Kolom dari tabel Mesin -->
                                                <td>
                                                    <a class="btn btn-warning"
                                                        href="{{ route('deptmtce.lihatpreventive', $mesin->id) }}">
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

