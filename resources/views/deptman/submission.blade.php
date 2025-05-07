@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Menu Tindak Lanjut</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Tindak Lanjut</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tampilan Data Handling</h5>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table id="" class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px">NO</th>
                                            <th class="text-center" width="100px">NO WO</th>
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">Kode Pelanggan</th>
                                            <th class="text-center" width="150px">Nama Pelanggan</th>
                                            <th class="text-center" width="100px">Area Pelanggan</th>
                                            <th class="text-center" width="100px">Request</th>
                                            <th class="text-center" width="100px">Tipe Bahan</th>
                                            <th class="text-center" width="105px">Kategori (NG)</th>
                                            <th class="text-center" width="100px">Tipe proses</th>
                                            <th class="text-center" width="95px">Jenis 1</th>
                                            <th class="text-center" width="100px">Pembaruan Terakhir</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($view1 as $row)
                                            <tr>
                                                <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                <td class="text-center py-3">{{ $row->no_wo }}</td>
                                                <td class="text-center py-3">{{ $row->user->name ?? '' }}</td>
                                                <td class="text-center py-3">{{ $row->customers->customer_code ?? '' }}</td>
                                                <td class="text-center py-3">{{ $row->customers->name_customer ?? '' }}</td>
                                                <td class="text-center py-3">{{ $row->customers->area ?? '' }}</td>
                                                <td class="text-center py-3">{{ $row->notes }}</td>
                                                <td class="text-center py-3">{{ $row->type_materials->type_name ?? '' }}
                                                </td>
                                                <td class="text-center py-3">{{ $row->category }}</td>
                                                <td class="text-center py-3">{{ $row->process_type }}</td>
                                                <td class="text-center py-3">{{ $row->type_1 }}</td>
                                                <td class="text-center py-3">{{ $row->created_at }}</td>
                                                <td class="text-center py-4">
                                                    @if ($row->status == 0)
                                                        <span class="badge bg-danger align-items-center"
                                                            style="font-size: 18px;">Open</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1)
                                                        <form onsubmit="return confirm('Apakah Anda Yakin ?');"
                                                            method="POST">
                                                            <a href="{{ route('showConfirm', $row->id) }}"
                                                                class="btn btn-sm btn-primary" title="Konfirmasi Data">
                                                                <i class="fas fa-folder-open fa-2x"></i>
                                                                <!-- Gantilah 'fas fa-eye' dengan kelas ikon yang sesuai -->
                                                            </a>
                                                        </form>
                                                    @endif
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


            <div class="row">
                <div class="col-lg-12">
                    {{-- form tindak lanjut --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tampilan Data Tindak Lanjut</h5>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive">
                                <table id="tabel_tindak_lanjut" class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px">NO</th>
                                            <th class="text-center" width="100px">NO WO</th>
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">Kode Pelanggan</th>
                                            <th class="text-center" width="150px">Nama Pelanggan</th>
                                            <th class="text-center" width="100px">Area Pelanggan</th>
                                            <th class="text-center" width="100px">Request</th>
                                            <th class="text-center" width="100px">Tipe Bahan</th>
                                            <th class="text-center" width="105px">Kategori (NG)</th>
                                            <th class="text-center" width="100px">Tipe proses</th>
                                            <th class="text-center" width="95px">Jenis 1</th>
                                            <th class="text-center" width="95px">Jenis 2</th>
                                            <th class="text-center" width="100px">Pembaruan Terakhir</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($view2 as $row)
                                            <tr>
                                                <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                <td class="text-center py-3">{{ $row->no_wo }}</td>
                                                <td class="text-center py-3">{{ $row->user->name ?? '' }}</td>
                                                <td class="text-center py-3">{{ $row->customers->customer_code ?? '' }}
                                                </td>
                                                <td class="text-center py-3">{{ $row->customers->name_customer ?? '' }}
                                                </td>
                                                <td class="text-center py-3">{{ $row->customers->area ?? '' }}</td>
                                                <td class="text-center py-3">{{ $row->notes }}</td>
                                                <td class="text-center py-3">{{ $row->type_materials->type_name ?? '' }}
                                                </td>
                                                <td class="text-center py-3">{{ $row->category }}</td>
                                                <td class="text-center py-3">{{ $row->process_type }}</td>
                                                <td class="text-center py-3">{{ $row->type_1 }}</td>
                                                <td class="text-center py-3">{{ $row->type_2 }}</td>
                                                <td class="text-center py-3">{{ $row->created_at }}</td>
                                                <td class="text-center py-3"
                                                    style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    @if ($row->status == 0)
                                                        <span class="badge bg-danger align-items-center"
                                                            style="font-size: 18px;">Open</span>
                                                    @elseif ($row->status == 1)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">On Progress</span>
                                                    @elseif($row->status == 2)
                                                        <span class="badge bg-info align-items-center"
                                                            style="font-size: 18px;">Finish</span>
                                                    @elseif($row->status == 3)
                                                        <span class="badge bg-success align-items-center"
                                                            style="font-size: 18px;">Close</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">

                                                    @if ($row->status == 1)
                                                        @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1)
                                                            <a href="{{ route('showFollowUp', $row->id) }}"
                                                                class="btn btn-sm btn-primary" title="Tindak Lanjut">
                                                                <i class="fa fa-file-text fa-2x" aria-hidden="true"></i>
                                                            </a>
                                                        @endif
                                                    @elseif ($row->status == 2)
                                                        @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1 || Auth::user()->role_id == 14)
                                                            <a href="{{ route('showHistoryProgres', $row->id) }}"
                                                                class="btn btn-sm btn-warning" title="Lihat Progres">
                                                                <i class="fa fa-eye fa-2x" aria-hidden="true"></i>
                                                            </a>
                                                        @endif
                                                    @elseif ($row->status == 3)
                                                        @if (Auth::user()->role_id == 5 || Auth::user()->role_id == 1 || Auth::user()->role_id == 14)
                                                            <a href="{{ route('showHistoryProgres', $row->id) }}"
                                                                class="btn btn-sm btn-warning" title="Lihat Progres">
                                                                <i class="fa fa-eye fa-2x" aria-hidden="true"></i>
                                                            </a>
                                                        @endif
                                                    @endif
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
        <script>
            $(document).ready(function() {
                $('#tabel_tindak_lanjut').datatable({
                    "pageLength": 100,
                    "lengthMenu": [
                        [10, 25, 50, 100],
                        [10, 25, 50, 100]
                    ],
                    "searching": true,
                    "ordering": true,
                    "info": true,
                    "paging": true
                });
            });
        </script>
    </main><!-- End #main -->
@endsection
