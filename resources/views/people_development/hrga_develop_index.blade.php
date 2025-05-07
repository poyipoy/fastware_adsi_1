@extends('layout')

@section('content')
    <main id="main" class="main">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Training</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active">Menu List Training</li>
                </ol>
            </nav>
        </div>
        <section class="section">
            <div class="container">
                <table class="datatable table">
                    <thead>
                        <tr>
                            <th scope="col">NO</th>
                            <th scope="col">Tahun</th>
                            <th scope="col">Status</th>
                            <th scope="col">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $index => $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td> <!-- Nomor urut otomatis -->
                                <td>{{ $item->tahun_aktual }}</td>
                                <td>
                                    @if ($item->status_1 == 1)
                                        <span class="badge rounded-pill bg-primary">Draf</span>
                                    @elseif ($item->status_1 == 2)
                                        <span class="badge rounded-pill bg-warning">Menunggu Konfirmasi HRGA</span>
                                    @elseif ($item->status_1 == 3)
                                        <span class="badge rounded-pill bg-success">Telah Disetujui</span>
                                    @else
                                        <!-- Tambahkan opsi lain jika diperlukan -->
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('editPdPengajuanHRGA', ['tahun_aktual' => $item->tahun_aktual]) }}" class="btn btn-primary"
                                        title="Edit Form"> <i class="bi bi-clipboard2-check-fill"></i>
                                    </a>
                                    <a href="{{ route('viewPD2', $item->tahun_aktual) }}" class="btn btn-sm btn-info"
                                        title="View Form">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('sendPD2', $item->tahun_aktual) }}" class="btn btn-sm btn-success"
                                        title="Kirim Form">
                                        <i class="fas fa-paper-plane"></i>
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


    </main><!-- End #main -->
@endsection
