@extends('layout')
@section('content')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.20.0/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">


<main id="main" class="main">

    <div class="pagetitle">
        <h1>Dept.Head Maintenance</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Engineering</li>
                <li class="breadcrumb-item active">Data Mesin</li>
            </ol>
        </nav>

    </div><!-- End Page Title -->
    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">List Data mesin</h5>

                        <!-- Table with stripped rows -->
                        <div class="table-responsive">
                            <table id="" class="display" style="table-layout: fixed;">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Section</th>
                                        <th scope="col">Tipe</th>
                                        <th scope="col">No Mesin</th>
                                        <th scope="col">Tanggal Dibuat</th>
                                        <th scope="col">Umur</th>
                                        <th scope="col">Spesifikasi</th>
                                        <th scope="col">Lokasi</th>
                                        <th scope="col">Created At</th>
                                        <th scope="col">Last Update</th>
                                        <th scope="col">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($mesins as $mesin)
                                    <tr>
                                        <td>{{ ++$i }}</td>
                                        <td>{{ $mesin->section }}</td>
                                        <td>{{ $mesin->tipe }}</td>
                                        <td>{{ $mesin->no_mesin }}</td>
                                        <td>{{ $mesin->tanggal_dibuat }}</td>
                                        <td>{{ $mesin->umur }}</td>
                                        <td>{{ $mesin->spesifikasi }}</td>
                                        <td>{{ $mesin->lokasi }}</td>
                                        <td>{{ $mesin->created_at }}</td>
                                        <td>{{ $mesin->updated_at }}</td>
                                        <td>
                                            <a class="btn btn-warning" href="{{ route('mesins.showMesinGA', $mesin->id) }}">
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