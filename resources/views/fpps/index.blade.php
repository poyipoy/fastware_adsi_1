@extends('layout')
@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Productions</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Form Permintaan Perbaikan</li>
                    <li class="breadcrumb-item">Data Form Perbaikan</li>
                </ol>
            </nav>

        </div><!-- End Page Title -->
        <section class="section">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Data Form Perbaikan</h5>
                        {{-- @if (Auth::user()->role_id == 1 || Auth::user()->role_id == 5 || Auth::user()->role_id == 8 || Auth::user()->role_id == 9 || Auth::user()->role_id == 14 || Auth::user()->role_id == 22 || Auth::user()->role_id == 30 || Auth::user()->role_id == 31 || Auth::user()->role_id == 42 || Auth::user()->role_id == 45 || Auth::user()->role_id == 51 || Auth::user()->role_id == 48) --}}
                        @php
                            $acsrole = [1, 5, 8, 9, 14, 22, 30, 31, 42, 45, 51, 48];
                        @endphp
                        @if (in_array(Auth::user()->role_id, $acsrole))
                            <a class="btn btn-success float-right" href="{{ route('formperbaikans.create') }}">
                                <i class="bi bi-file-earmark-plus"></i> Buat Form Perbaikan
                            </a>
                        @endif
                        <!-- Table with stripped rows -->
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
                                                    href="{{ route('fpps.show', $formperbaikan->id) }}">
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
