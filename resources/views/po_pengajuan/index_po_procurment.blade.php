@extends('layout')

@section('content')
    <style>
        .fodabl {
            font-family: 'Cambria', serif;
        }

        .table thead th {
            background-color: #00587a;
            color: white;
            font-size: 14px;
            border: #000000;
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
    </style>
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Menu Pre Order</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Pengajuan PO</li>

                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title fodabl fw-bold">Tampilan Data Pengajuan PO</h5>
                            <hr>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <!-- Menambahkan SweetAlert untuk notifikasi pengajuan cancel -->
                                @if (!empty($pengajuanCancel))
                                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            Swal.fire({
                                                title: 'Notifikasi!',
                                                text: 'Terdapat Pengajuan Reject Item Pada No FPB: {{ implode(', ', $pengajuanCancel) }}',
                                                icon: 'warning',
                                                confirmButtonText: 'OK'
                                            });
                                        });
                                    </script>
                                @endif

                                <!-- Menambahkan SweetAlert untuk notifikasi pengajuan terbaru -->
                                @if (!empty($noFpbTerbaru))
                                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                    <script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                            Swal.fire({
                                                title: 'Pengajuan Terbaru!',
                                                text: 'Terdapat pengajuan NO FPB terbaru dengan NO: {{ is_array($noFpbTerbaru) ? implode(', ', $noFpbTerbaru) : $noFpbTerbaru }}',
                                                icon: 'info',
                                                confirmButtonText: 'OK'
                                            });
                                        });
                                    </script>
                                @endif

                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px">NO</th>
                                            <th class="text-center" width="100px">NO FPB</th>
                                            @if (isset($showNamaBarang) && $showNamaBarang == true)
                                                <th class="text-center" width="100px">No PO</th>
                                            @endif
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">Kategori</th>
                                            @if (isset($showNamaBarang) && $showNamaBarang == true)
                                                <th class="text-center" width="100px">Nama Barang</th>
                                            @endif
                                            <th class="text-center" width="100px">Catatan</th>
                                            <th class="text-center" width="100px">Tgl Pembaruan</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($data as $row)
                                            @if (
                                                $row->status_1 == 5 ||
                                                    $row->status_1 == 6 ||
                                                    $row->status_1 == 7 ||
                                                    $row->status_1 == 8 ||
                                                    $row->status_1 == 9 ||
                                                    $row->status_1 == 10 ||
                                                    $row->status_1 == 11 ||
                                                    $row->status_1 == 12)
                                                <!-- Tambahkan kondisi untuk menampilkan hanya jika status_1 == 2 -->
                                                <tr>
                                                    <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                    <td class="text-center py-3">{{ $row->no_fpb }}</td>
                                                    @if (isset($showNamaBarang) && $showNamaBarang == true)
                                                        <td class="text-center py-3">{{ $row->no_po }}</td>
                                                    @endif
                                                    <td class="text-center py-3">{{ $row->modified_at }}</td>
                                                    <td class="text-center py-3">{{ $row->kategori_po }}</td>
                                                    <!-- Conditionally hide/show nama_barang based on controller source -->
                                                    @if (isset($showNamaBarang) && $showNamaBarang == true)
                                                        <td class="text-center py-3">{{ $row->nama_barang }}</td>
                                                    @endif
                                                    <td class="text-center py-3"><b>{{ $row->catatan }}</b></td>
                                                    <td class="text-center py-3">
                                                        <b>
                                                            {{ $row->updated_at !== '-' && $row->updated_at ? \Carbon\Carbon::parse($row->updated_at)->format('d-m-Y') : '-' }}
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
                                                            <span class="badge bg-info align-items-center">Finish</span>
                                                        @elseif($row->status_1 == 10)
                                                            <span class="badge status-open align-items-center">Open</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center py-4">
                                                        <a href="{{ route('view.FormPo.proc', ['id' => $row->id]) }}"
                                                            class="btn btn-primary btn-sm" title="View Form">
                                                            <i class="fa-regular fa-folder-open"></i>
                                                        </a>
                                                        <!-- Pengecekan apakah field quotation_file memiliki nilai -->
                                                        @if ($row->quotation_file && !$row->no_po)
                                                            <!-- Icon bintang merah jika hanya quotation_file yang memiliki nilai dan no_po kosong -->
                                                            <i class="fa-solid fa-star text-danger me-2"
                                                                title="Quotation Available"></i>
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

        <script>
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
                                    url: "{{ route('kirim.fpb.finance', ':no_fpb') }}"
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
