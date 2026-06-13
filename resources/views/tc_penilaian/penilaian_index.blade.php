@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Penilaian Competency</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">
                        @if ($level === 'kasie')
                            Penilaian Technical Competency Ka. Sie
                        @elseif ($level === 'hr')
                            Penilaian Technical Competency HR
                        @else
                            Penilaian Technical Competency
                        @endif
                    </li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                @if ($level === 'kasie')
                    <button class="btn btn-success" onclick="window.location.href='{{ route('create.penilaian') }}'">
                        <i class="fas fa-plus"></i> Buat Penilaian
                    </button>
                @endif
                <table class="datatable table">
                    <thead>
                        <tr>
                            <th scope="col">NO</th>
                            <th scope="col">Job Position</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $user = Auth::user(); // Ambil pengguna yang sedang login
                            $userName = $user->name;
                        @endphp

                        @foreach ($penilaianData as $item)
                            <tr>
                                <th scope="row">{{ $loop->iteration }}</th>
                                <td>{{ $item->id_job_position }}</td>
                                <td>
                                    @if ($item->status == 1)
                                        <span class="badge rounded-pill bg-secondary">Draf</span>
                                    @elseif ($item->status == 2)
                                        <span class="badge rounded-pill bg-warning">Menunggu Konfirmasi Dept. Head</span>
                                    @elseif ($item->status == 3)
                                        <span class="badge rounded-pill bg-success">Telah Disetujui</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($level === 'kasie' && $item->status == 1)
                                        <a href="{{ route('penilaian.edit', $item->id_job_position) }}"
                                            class="btn btn-warning">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <button type="button" class="btn btn-success"
                                            onclick="kirimData('{{ $item->id_job_position }}')">
                                            <i class="fas fa-paper-plane"></i> Kirim
                                        </button>
                                    @endif

                                    <a href="{{ route('penilaian.view', $item->id_job_position) }}" class="btn btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
            function kirimData(id_job_position) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda Ingin Mengirim Competency?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna memilih Yes, lakukan AJAX request
                        $.ajax({
                            url: '{{ route('update.status', ':id_job_position') }}'.replace(':id_job_position',
                                id_job_position),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}' // Sertakan token CSRF
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            location.reload(); // contoh aksi: merefresh halaman
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Error: ' + response.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Request failed!',
                                    text: 'Request failed: ' + xhr.statusText,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    } else {
                        Swal.fire(
                            'Dibatalkan',
                            'Data Tidak DiKirim',
                            'info'
                        );
                    }
                });
            }

            function kirimData2(id_job_position) {
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Anda Ingin Mengirim Competency?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna memilih Yes, lakukan AJAX request
                        $.ajax({
                            url: '{{ route('update.status2', ':id_job_position') }}'.replace(
                                ':id_job_position',
                                id_job_position),
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}' // Sertakan token CSRF
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Berhasil!',
                                        text: response.message,
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            location.reload(); // contoh aksi: merefresh halaman
                                        }
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Error: ' + response.message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            },
                            error: function(xhr) {
                                Swal.fire({
                                    title: 'Request failed!',
                                    text: 'Request failed: ' + xhr.statusText,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        });
                    } else {
                        Swal.fire(
                            'Dibatalkan',
                            'Data Tidak DiKirim',
                            'info'
                        );
                    }
                });
            }
        </script>

    </main><!-- End #main -->
@endsection
