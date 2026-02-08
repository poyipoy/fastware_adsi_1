@extends('layout')

@section('content')
    <main id="main" class="main">

        <style>
            .section {
                font-family: Cambria, serif;
            }

            td,
            thead {
                text-align: center;
            }

            h1,
            h4 {
                font-family: Cambria, serif;
            }

            .dataTable-pagination {
                padding: 0.25rem;
                /* Padding lebih kecil untuk pagination */
                font-size: 0.5rem;
                /* Ukuran font lebih kecil */
            }

            .dataTable-search {
                margin-bottom: 0.5rem;
                /* Jarak antara input pencarian dan tabel */
                font-family: 'Cambria', serif;
            }

            input[type="search"] {
                padding: 0.3rem;
                /* Padding lebih kecil untuk input pencarian */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
                border-radius: 10px;
                /* Sudut membulat */
                border: 1px solid #ddd;
                /* Border untuk input */
            }

            .datatable-selector {
                padding: 0.2rem;
                /* Padding lebih kecil pada dropdown pagination */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
                border-radius: 4px;
                /* Sudut membulat */
                border: 1px solid #ddd;
                /* Border untuk dropdown */
                font-family: 'Cambria', serif;
            }
        </style>
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Data Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Competency</li>
                </ol>
            </nav>
        </div>
        <a href="{{ route('tcCreate') }}" class="btn btn-success">
            <i class="fas fa-plus"></i> Tambah Data
        </a>
        <section class="section m-3">
            <div class="row">
                <div class="col font1">
                    <h4 style="margin-top: 3%; font-family: Cambria, serif;"> <b>Table Data Technical Competency</b></h4>
                    <table class="datatable table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Job Position</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach ($technicalData as $index => $data)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $data->jobPosition->job_position }}</td>
                                    <td>
                                        <a href="{{ route('mst_tc.edit', $data->id) }}" class="btn btn-warning btn-sm"> <i
                                                class="fas fa-edit fs-6"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col">
                    <h4 style="margin-top: 3%;font-family: Cambria, serif;"> <b>Table Data Soft Skills</b></h4>
                    <table class="datatable table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Job Position</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach ($softSkillsData as $index => $data)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $data->jobPosition->job_position }}</td>
                                    <td>
                                        <a href="{{ route('mst_sk.editSoftSKills', $data->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit fs-6"></i> </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="col">
                    <h4 style="margin-top: 3%;font-family: Cambria, serif;"> <b>Table Data Additional</b></h4>
                    <table class="datatable table">
                        <thead>
                            <tr>
                                <th scope="col">No</th>
                                <th scope="col">Job Position</th>
                                <th scope="col">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $i = 1; @endphp
                            @foreach ($additionalData as $index => $data)
                                <tr>
                                    <td>{{ $i++ }}</td>
                                    <td>{{ $data->jobPosition->job_position }}</td>
                                    <td>
                                        <a href="{{ route('mst_ad.editAdditionals', $data->id) }}"
                                            class="btn btn-warning btn-sm">
                                            <i class="fas fa-edit fs-6"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- jQuery -->
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


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

        <!-- SimpleDataTables JS -->
        <script src="{{ asset('assets/vendor/simple-datatables/simple-datatables.js') }}"></script>

    </main><!-- End #main -->
@endsection
