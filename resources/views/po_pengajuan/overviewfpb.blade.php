@extends('layout')

@section('content')
    <style>
        .fodabl {
            font-family: 'Cambria', serif;
            font-size: 11pt;
        }

        .fodabl-1 {
            font-family: 'Cambria', serif;
            font-size: 14pt;
        }

        .table thead th {
            background-color: #00587a;
            color: white;
            font-size: 14px;
            border: #000000;
        }

        .btn-custom {
            font-size: 0.7rem;
            padding: 0.15rem 0.3rem;
            border-radius: 0.2rem;
        }

        .btn-i {
            font-size: 10pt;
            padding: 8px 10px;
            border-radius: 0.2rem;
            background: rgb(0, 230, 69);
            color: #000000;
            font-family: 'Cambria', serif;
        }

        .badge {
            padding: 8px 10px;
            /* border-radius: 12px; */
            font-size: 10pt;
            color: white;
            display: inline-flex;
            align-items: center;

        }

        .badge.status-open {
            background-color: #00c206;
            color: rgb(255, 255, 255);
        }

        /* Status On Progress */
        .badge.status-progress {
            background-color: #fbff00;
            color: black;
        }

        /* Status Finish */
        .badge.status-finish {
            background-color: #0063c7;
            color: white;
        }

        .btn.btn-show {
            background-color: #e400c5;
            color: rgb(255, 255, 255);
        }
    </style>

    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Menu Sec. Head</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Pengajuan FPB</li>

                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">

                        <div class="card-body">
                            <!-- Table with stripped rows -->
                            <div class="table-responsive fodabl" style="height: 100%; overflow-y: auto;">
                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px">NO</th>
                                            <th class="text-center" width="100px">NO FPB</th>
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">Kategori</th>
                                            <th class="text-center" width="100px">Nama Barang</th>
                                            <th class="text-center" width="100px">Catatan Cancel</th>
                                            <th class="text-center" width="100px">Tgl Pembaruan</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $no_fpb => $row)
                                            <tr>
                                                <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                <td class="text-center py-3">{{ $row->no_fpb }}</td>
                                                <td class="text-center py-3">{{ $row->modified_at }}</td>
                                                <td class="text-center py-3">{{ $row->kategori_po }}</td>
                                                <td class="text-center py-3">{{ $row->nama_barang }}</td>
                                                <td class="text-center py-3"><b>{{ $row->catatan }}</b></td>
                                                <td class="text-center py-3">
                                                    <b>
                                                        {{ $row->trs_updated_at !== '-' && $row->trs_updated_at
                                                            ? \Carbon\Carbon::parse($row->trs_updated_at)->format('d-m-Y')
                                                            : '-' }}
                                                    </b>
                                                </td>
                                                <td class="text-center py-4"
                                                    style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                    @if ($row->status_1 == 1)
                                                        <span class="badge bg-secondary align-items-center">Draf</span>
                                                    @elseif ($row->status_1 == 2)
                                                        <span class="badge status-open align-items-center">Open</span>
                                                    @elseif ($row->status_1 == 3)
                                                        <span class="badge status-open align-items-center">Open</span>
                                                    @elseif($row->status_1 == 4)
                                                        <span class="badge status-open align-items-center">Open</span>
                                                    @elseif($row->status_1 == 5)
                                                        <span class="badge status-open align-items-center">Open</span>
                                                    @elseif($row->status_1 == 6)
                                                        <span class="badge status-progress align-items-center">On
                                                            Progress</span>
                                                    @elseif($row->status_1 == 7)
                                                        <span class="badge status-progress align-items-center">On
                                                            Progress</span>
                                                    @elseif($row->status_1 == 8)
                                                        <span class="badge status-progress align-items-center">On
                                                            Progress</span>
                                                    @elseif($row->status_1 == 9)
                                                        <span class="badge status-finish align-items-center">Finish</span>
                                                    @endif
                                                </td>

                                                <td class="text-center py-4">
                                                    @if (auth()->user()->name == $row->modified_at && $row->status_1 == 1)
                                                        <!-- Cek kesesuaian nama dan status_1 == 1 -->
                                                        @if (Auth::user()->role_id != 48 && Auth::user()->role_id != 5)
                                                            <a href="{{ route('edit.PoPengajuan', ['id' => $row->id]) }}"
                                                                class="btn btn-warning btn-sm" title="Edit">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-success btn-sm btn-kirim"
                                                                data-no_fpb="{{ $row->no_fpb }}" title="Kirim">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        @endif
                                                    @endif
                                                    <a href="{{ route('view.FormPo', ['id' => $row->id]) }}"
                                                        class="btn btn-show btn-sm" title="View Form">
                                                        <i class="fas fa-eye"></i>
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

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            //datatabelSales
            $(document).ready(function() {
                new DataTable('#viewPoSecHead');
            });

            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.btn-kirim').forEach(button => {
                    button.addEventListener('click', function() {
                        // Mengambil no_fpb dari data attribute dan mengganti "/" dengan "-"
                        var no_fpb = this.getAttribute('data-no_fpb').replace(/\//g, '-');
                        console.log("FPB Number to send:", no_fpb); // Log FPB number yang diambil

                        Swal.fire({
                            title: 'Apakah anda ingin mengirim data?',
                            text: "Data yang telah dikirim tidak dapat dirubah!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Ya, kirim!',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Jika konfirmasi, lakukan AJAX POST request
                                $.ajax({
                                    url: "{{ route('kirim.fpb.secHead', ':no_fpb') }}"
                                        .replace(':no_fpb', no_fpb),
                                    type: 'POST',
                                    data: {
                                        _token: '{{ csrf_token() }}', // CSRF Token Laravel
                                    },
                                    success: function(response) {
                                        console.log("Response from server:",
                                            response); // Log response dari server

                                        Swal.fire(
                                            'Terkirim!',
                                            'Data berhasil dikirim.',
                                            'success'
                                        ).then(() => {
                                            location
                                                .reload(); // Refresh halaman setelah sukses
                                        });
                                    },
                                    error: function(xhr) {
                                        console.log("Error occurred:", xhr
                                            .responseText
                                        ); // Log error jika terjadi kesalahan

                                        Swal.fire(
                                            'Gagal!',
                                            'Terjadi kesalahan saat mengirim data.',
                                            'error'
                                        );
                                    }
                                });
                            }
                        });
                    });
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
