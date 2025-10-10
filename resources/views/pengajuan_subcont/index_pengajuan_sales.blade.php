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
                            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                                <a href="{{ route('pengajuan-subcont.create') }}" class="btn btn-success btn-sm"
                                    style="font-size: 20px;">
                                    <i class="fas fa-plus"></i> Tambah Data
                                </a>
                                <button type="button" id="exportExcel" class="btn btn-primary btn-sm"
                                    style="font-size: 20px;">
                                    <i class="fas fa-file-excel"></i> Export Excel
                                </button>
                            </div>

                            @if (session('error'))
                                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                    {{ session('error') }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            @endif

                            <form id="exportForm" method="POST" action="{{ route('indexSales.export') }}">
                                @csrf
                                <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                    <table class="datatable table align-middle">
                                        <thead>
                                            <tr>
                                                <th class="text-center" width="40px">
                                                    <input type="checkbox" id="select-all" class="form-check-input">
                                                </th>
                                                <th class="text-center" width="50px">NO</th>
                                                <th class="text-center" width="100px">PIC</th>
                                                <th class="text-center" width="150px">Nama Customer</th>
                                                <th class="text-center" width="80px">QTY</th>
                                                <th class="text-center" width="150px">Nama Project</th>
                                                <th class="text-center" width="150px">Keterangan</th>
                                                <th class="text-center" width="150px">Jenis Proses Subcont</th>
                                                <th class="text-center" width="120px">Tgl Pengajuan</th>
                                                <th class="text-center" width="160px">Update Terakhir</th>
                                                <th class="text-center" width="120px">Status</th>
                                                <th class="text-center" width="150px">Aksi</th>
                                                <th class="text-center" width="160px">Unduh Quotation</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($pengajuanSubconts as $index => $pengajuan)
                                                <tr>
                                                    <td class="text-center">
                                                        <input type="checkbox" class="form-check-input row-check"
                                                            name="ids[]" value="{{ $pengajuan->id }}">
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $pengajuanSubconts->firstItem() + $index }}
                                                    </td>
                                                    <td class="text-center">{{ $pengajuan->modified_at ?? '-' }}</td>
                                                    <td class="text-center">{{ $pengajuan->nama_customer ?? '-' }}</td>
                                                    <td class="text-center">{{ $pengajuan->qty ?? '-' }}</td>
                                                    <td class="text-center">{{ $pengajuan->nama_project ?? '-' }}</td>
                                                    <td class="text-center">{{ $pengajuan->keterangan ?? '-' }}</td>
                                                    <td class="text-center">{{ $pengajuan->jenis_proses_subcont ?? '-' }}</td>
                                                    <td class="text-center">
                                                        {{ $pengajuan->created_at ?? '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        {{ $pengajuan->latest_keterangan ?? '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($pengajuan->status_1 == 1)
                                                            <span class="badge bg-secondary align-items-center"
                                                                style="font-size: 18px;">Draf</span>
                                                        @elseif ($pengajuan->status_1 == 2)
                                                            <span class="badge bg-primary align-items-center"
                                                                style="font-size: 18px;">Open</span>
                                                        @elseif ($pengajuan->status_1 == 3)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif ($pengajuan->status_1 == 4)
                                                            <span class="badge bg-warning align-items-center"
                                                                style="font-size: 18px;">On Progress</span>
                                                        @elseif ($pengajuan->status_1 == 5)
                                                            <span class="badge bg-info align-items-center"
                                                                style="font-size: 18px;">Finish</span>
                                                        @else
                                                            <span class="badge bg-danger align-items-center"
                                                                style="font-size: 18px;">Status Tidak Tersedia</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($pengajuan->status_1 == 1)
                                                            <a href="{{ route('pengajuan-subcont.edit', $pengajuan->id) }}"
                                                                class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="#" class="btn btn-sm btn-danger"
                                                                onclick="event.preventDefault(); deleteData('{{ route('pengajuan-subcont.destroy', $pengajuan->id) }}');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
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
                                                                data-bs-toggle="tooltip"
                                                                title="Click to view or download the quotation file">
                                                                <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                                                <p class="mb-0 fw-bold">View Quotation</p>
                                                            </a>
                                                        @else
                                                            <span class="text-muted fst-italic">Quotation belum tersedia</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="13" class="text-center text-muted">
                                                        Data pengajuan belum tersedia.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </form>

                            <div class="mt-3">
                                {{ $pengajuanSubconts->links('pagination::bootstrap-4') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            $(document).ready(function() {
                const namaCustomerTerakhir = @json($namaCustomerTerakhir);

                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });

                $('#select-all').on('change', function() {
                    $('.row-check').prop('checked', $(this).is(':checked'));
                });

                $(document).on('change', '.row-check', function() {
                    const total = $('.row-check').length;
                    const selected = $('.row-check:checked').length;
                    $('#select-all').prop('checked', total > 0 && total === selected);
                });

                $('#exportExcel').on('click', function() {
                    if ($('.row-check:checked').length === 0) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Pilih data terlebih dahulu',
                            text: 'Silakan pilih minimal satu data sebelum melakukan export.'
                        });
                        return;
                    }

                    $('#exportForm').trigger('submit');
                });

                if (namaCustomerTerakhir) {
                    Swal.fire({
                        title: 'File Quotation Diterima!',
                        text: `Customer ${namaCustomerTerakhir} telah memiliki File Quotation.`,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
            });

            function deleteData(url) {
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
                        fetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Content-Type': 'application/json'
                            }
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire(
                                    'Terhapus!',
                                    'Data berhasil dihapus.',
                                    'success'
                                ).then(() => {
                                    window.location.reload();
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
