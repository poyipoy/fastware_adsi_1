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
                            <div class="card-header" style="margin-bottom: 20px;">
                                <a href="{{ route('pengajuan-subcont.create') }}" class="btn btn-success btn-sm"
                                    style="font-size: 20px;">
                                    <i class="fas fa-plus"></i> Tambah Data
                                </a>
                            </div>

                            @if (!empty($namaCustomerTerakhir))
                                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        Swal.fire({
                                            title: 'File Quotation Diterima!',
                                            text: 'Customer {{ $namaCustomerTerakhir }} telah memiliki File Quotation.',
                                            icon: 'info',
                                            confirmButtonText: 'OK'
                                        });
                                    });
                                </script>
                            @endif
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px">NO</th>
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
                                            <tr>
                                                <td class="text-center">{{ $key + 1 }}</td>
                                                <td class="text-center">{{ $pengajuan->modified_at }}</td>
                                                <!-- Sesuaikan jika ada kolom 'pic' -->
                                                <td class="text-center">{{ $pengajuan->nama_customer }}</td>
                                                <td class="text-center">{{ $pengajuan->qty }}</td>
                                                <td class="text-center">{{ $pengajuan->nama_project }}</td>
                                                <td class="text-center">{{ $pengajuan->keterangan }}</td>
                                                <td class="text-center">{{ $pengajuan->jenis_proses_subcont }}</td>
                                                <td class="text-center">{{ $pengajuan->created_at->format('d-m-Y') }}</td>
                                                <td class="text-center">
                                                    @if ($pengajuan->status_1 == 1)
                                                        <span class="badge bg-secondary align-items-center"
                                                            style="font-size: 18px;">Draf</span>
                                                    @elseif($pengajuan->status_1 == 2)
                                                        <span class="badge bg-primary align-items-center"
                                                            style="font-size: 18px;">Open</span>
                                                    @elseif($pengajuan->status_1 == 3)
                                                        <span class="badge bg-warning align-items-center"
                                                            style="font-size: 18px;">On Progress</span>
                                                    @elseif($pengajuan->status_1 == 4)
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
                                                    <!-- Cek jika status_1 memiliki nilai 1 -->
                                                    @if ($pengajuan->status_1 == 1)
                                                        <!-- Tombol Edit -->
                                                        <a href="{{ route('pengajuan-subcont.edit', $pengajuan->id) }}"
                                                            class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>

                                                        <!-- Tombol Delete -->
                                                        <a href="#" class="btn btn-sm btn-danger"
                                                            onclick="event.preventDefault(); deleteData('{{ route('pengajuan-subcont.destroy', $pengajuan->id) }}');">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>

                                                        <!-- Tombol Kirim -->
                                                        <a href="#" class="btn btn-sm btn-success"
                                                            onclick="event.preventDefault(); kirimData('{{ route('pengajuan-subcont.kirim', $pengajuan->id) }}');">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('pengajuan-subcont.view', $pengajuan->id) }}"
                                                        class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
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

            function deleteData(url) {
                // Menampilkan konfirmasi dengan SweetAlert
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: "Data ini akan dihapus secara permanen!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Kirim permintaan AJAX menggunakan Fetch API
                        fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}', // Sertakan token CSRF
                                'Content-Type': 'application/json'
                            }
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire(
                                    'Terhapus!',
                                    'Data berhasil dihapus.',
                                    'success'
                                ).then(() => {
                                    window.location
                                        .reload(); // Refresh halaman setelah penghapusan berhasil
                                });
                            } else {
                                Swal.fire(
                                    'Gagal!',
                                    'Data gagal dihapus.',
                                    'error'
                                );
                            }
                        }).catch(error => {
                            console.error('Error:', error);
                            Swal.fire(
                                'Kesalahan!',
                                'Terjadi kesalahan saat menghapus data!',
                                'error'
                            );
                        });
                    }
                });
            }

            // Fungsi untuk mengubah status
            function kirimData(url) {
                Swal.fire({
                    title: 'Apakah Anda yakin ingin mengirim data ini?',
                    text: "Status akan diubah menjadi 'Dikirim'.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, kirim!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                status_1: 2,
                                status_2: 2
                            })
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire(
                                    'Terkirim!',
                                    'Status berhasil diubah menjadi "Dikirim".',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire(
                                    'Gagal!',
                                    'Gagal mengubah status.',
                                    'error'
                                );
                            }
                        }).catch(error => {
                            Swal.fire(
                                'Kesalahan!',
                                'Terjadi kesalahan saat mengubah status!',
                                'error'
                            );
                        });
                    }
                });
            }
        </script>

    </main><!-- End #main -->
@endsection
