@extends('layout')

@section('content')
    <main id="main" class="main">
        <div class="pagetitle">
            <h1>Menu Pengajuan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Pengajuan Penawaran Subcont</li>

                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tampilan Data Pengajuan Penawaran Subcont</h5>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px">NO</th>
                                            <th class="text-center" width="50px">Info</th>
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">Nama Customer</th>
                                            <th class="text-center" width="100px">QTY</th>
                                            <th class="text-center" width="100px">Nama Project</th>
                                            <th class="text-center" width="100px">Keterangan</th>
                                            <th class="text-center" width="100px">Jenis Proses Subcont</th>
                                            <th class="text-center" width="100px">Tgl Pengajuan</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px">Aksi</th>
                                            <th class="text-center" width="100px">Unduh Quotation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($pengajuanSubconts as $key => $pengajuan)
                                            @if (in_array($pengajuan->status_1, [2, 3, 4, 5]))
                                                <!-- Cek status_1 -->
                                                <tr>
                                                    <td class="text-center">{{ $key + 1 }}</td>

                                                    <!-- Logika untuk Info -->
                                                    <td class="text-center">
                                                        @php
                                                            $tgl_pengajuan = $pengajuan->created_at;
                                                            $current_date = now();
                                                            $diff_days = $tgl_pengajuan->diffInDays($current_date);
                                                            $has_quotation_file = !empty($pengajuan->quotation_file);
                                                        @endphp

                                                        @if ($pengajuan->status_1 >= 3 && $pengajuan->status_1 <= 5)
                                                            @if ($diff_days <= 3 || ($diff_days > 3 && $has_quotation_file))
                                                                <span style="color: green; font-size: 24px;">●</span>
                                                                <!-- Bulat hijau -->
                                                            @endif
                                                        @endif

                                                        @if ($diff_days > 3 && !$has_quotation_file)
                                                            <span style="color: red; font-size: 24px;">●</span>
                                                            <!-- Bulat merah -->
                                                        @endif
                                                    </td>

                                                    <td class="text-center">{{ $pengajuan->modified_at }}</td>
                                                    <td class="text-center">{{ $pengajuan->nama_customer }}</td>
                                                    <td class="text-center">{{ $pengajuan->qty }}</td>
                                                    <td class="text-center">{{ $pengajuan->nama_project }}</td>
                                                    <td class="text-center">{{ $pengajuan->keterangan }}</td>
                                                    <td class="text-center">{{ $pengajuan->jenis_proses_subcont }}</td>
                                                    <td class="text-center">{{ $pengajuan->created_at->format('d-m-Y') }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($pengajuan->status_1 == 1)
                                                            <span class="badge bg-secondary align-items-center"
                                                                style="font-size: 18px;">Draf</span>
                                                        @elseif($pengajuan->status_1 == 2)
                                                            <span class="badge bg-primary align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif($pengajuan->status_1 == 3 || $pengajuan->status_1 == 4)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif($pengajuan->status_1 == 5)
                                                            <span class="badge bg-info align-items-center"
                                                                style="font-size: 18px;">Finish</span>
                                                        @else
                                                            <span class="badge bg-danger align-items-center"
                                                                style="font-size: 18px;">Status Tidak Tersedia</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        <a href="{{ route('pengajuan-subcont.editProc', $pengajuan->id) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fa-regular fa-folder-open"></i>
                                                        </a>
                                                    </td>
                                                    <td class="text-center align-middle">
                                                        @if ($pengajuan->quotation_file)
                                                            <a href="{{ asset($pengajuan->quotation_file) }}" target="_blank" 
                                                               class="d-inline-block p-3 bg-white rounded border shadow-sm text-decoration-none" 
                                                               style="color: inherit; transition: transform 0.2s ease;" 
                                                               onmouseover="this.style.transform='scale(1.05)'" 
                                                               onmouseout="this.style.transform='scale(1)'"
                                                               data-bs-toggle="tooltip" title="Click to view or download the quotation file">
                                                                <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                                                <p class="mb-0 fw-bold">View Quotation</p>
                                                            </a>
                                                        @else
                                                            <span class="text-muted fst-italic">Quotation belum tersedia</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endif
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            //datatabelSales
            $(document).ready(function() {
                new DataTable('#viewPoSecHead');
            });
        </script>

    </main><!-- End #main -->
@endsection
