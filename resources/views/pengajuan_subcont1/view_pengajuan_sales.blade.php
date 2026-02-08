@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Halaman View Data</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('indexSales') }}">Menu Pengajuan Penawaran Subcont</a></li>
                    <li class="breadcrumb-item active">Halaman View Data</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5>Form View Data</h5>
                    </div>
                    <div class="card-body">
                        <form id="pengajuanEditForm" action="{{ route('pengajuan-subcont.update', $pengajuan->id) }}"
                            method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!-- Nama Customer -->
                            <div class="mb-3">
                                <label for="nama_customer" class="form-label">Nama Customer</label>
                                <input type="text" class="form-control" id="nama_customer" name="nama_customer"
                                    value="{{ $pengajuan->nama_customer }}" placeholder="Masukkan nama customer" disabled
                                    required>
                            </div>

                            <!-- Nama Project -->
                            <div class="mb-3">
                                <label for="nama_project" class="form-label">Nama Project</label>
                                <input type="text" class="form-control" id="nama_project" name="nama_project"
                                    value="{{ $pengajuan->nama_project }}" placeholder="Masukkan nama project" disabled
                                    required>
                            </div>

                            <div class="mb-3">
                                <label for="qty" class="form-label">QTY</label>
                                <input type="text" class="form-control" id="qty" name="qty"
                                    value="{{ $pengajuan->qty }}" placeholder="Masukkan qty" disabled required>
                            </div>

                            <!-- Keterangan (Textarea) -->
                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="4" disabled
                                    placeholder="Masukkan keterangan">{{ $pengajuan->keterangan }}</textarea>
                            </div>

                            <!-- Jenis Project Subcont -->
                            <div class="mb-3">
                                <label for="jenis_proses_subcont" class="form-label">Jenis Project Subcont</label>
                                <input type="text" class="form-control" id="jenis_proses_subcont"
                                    name="jenis_proses_subcont" value="{{ $pengajuan->jenis_proses_subcont }}"
                                    placeholder="Masukkan jenis project subcont" disabled required>
                            </div>

                            <!-- File -->
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                @if ($pengajuan->file)
                                    <p class="fw-bold text-secondary mb-1">Unduh File</p>
                                    <a href="{{ asset($pengajuan->file) }}" target="_blank"
                                        class="btn btn-outline-secondary" data-bs-toggle="tooltip"
                                        title="Click to download the file">
                                        <i class="fas fa-file-alt fa-lg me-2"></i> View File
                                    </a>
                                @else
                                    <span class="text-muted fst-italic">File belum tersedia</span>
                                @endif
                            </div>

                            <!-- Tombol Submit -->
                            <div class="d-flex justify-content-end">
                                <!-- Div untuk tombol di sebelah kiri (Lihat Histori) -->
                                <div class="d-flex justify-content-start">
                                    <button type="button" class="btn btn-warning mb-4 me-3"
                                        onclick="showHistory({{ $pengajuan->id }})">
                                        <i class="fas fa-eye"></i> Lihat Histori
                                    </button>
                                </div>
                                <a href="{{ route('indexSales') }}" class="btn btn-primary mb-4 me-2">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal fade" id="historyModal" tabindex="-1" role="dialog" aria-labelledby="historyModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-xl" role="document">
                        <!-- Ganti 'modal-lg' dengan 'modal-xl' untuk ekstra lebar -->
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="historyModalLabel">Histori Progres Penawaran Subcont</h5>
                            </div>
                            <div class="modal-body">
                                <!-- Tabel untuk menampilkan histori -->
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Nama Customer</th>
                                            <th>Nama Project</th>
                                            <th>Keterangan</th>
                                            <th>Status</th>
                                            <th>Dibuat Pada</th>
                                            <th>PIC</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historyTableBody">
                                        <!-- Data histori akan dimuat di sini melalui AJAX -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="modal-footer">
                                <a href="{{ route('pengajuan-subcont.view', $pengajuan->id) }}"
                                    class="btn btn-sm btn-secondary">
                                    <i class="fas fa-close"></i> Tutup
                                </a>
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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
        <script>
            document.getElementById('pengajuanEditForm').addEventListener('submit', function(event) {
                // Cek ukuran file
                var fileInput = document.getElementById('file');
                var file = fileInput.files[0];

                if (file && file.size > 10 * 1024 * 1024) { // 10 MB dalam byte
                    event.preventDefault(); // Mencegah form submit
                    Swal.fire({
                        icon: 'error',
                        title: 'Ukuran File Terlalu Besar',
                        text: 'Ukuran file tidak boleh lebih dari 10MB!',
                        showConfirmButton: false, // Menghilangkan tombol OK
                        timer: 2000 // Pesan otomatis hilang setelah 2 detik
                    });
                    return;
                }

                // Tampilkan alert berhasil sebelum submit
                Swal.fire({
                    icon: 'success',
                    title: 'Data Berhasil Disimpan',
                    showConfirmButton: false, // Menghilangkan tombol OK
                    timer: 1500 // Pesan otomatis hilang setelah 1,5 detik
                });

                // Beri sedikit delay agar alert bisa terlihat sebelum form di-submit
                setTimeout(() => {
                    this.submit(); // Submit form setelah delay
                }, 1600);
            });

            function showHistory(id) {
                const url = `{{ route('get.history', ':id') }}`.replace(':id', id);

                $.ajax({
                    url: url,
                    type: 'GET',
                    success: function(data) {
                        console.log(data); // Tambahkan log untuk memeriksa data yang diterima
                        const historyTableBody = document.getElementById('historyTableBody');
                        historyTableBody.innerHTML = '';

                        let fileUploadedShown =
                            false; // Flag untuk melacak apakah "File quotation telah diunggah" sudah ditampilkan

                        // Urutkan data secara descending
                        data.reverse(); // Ini membalik urutan data menjadi descending

                        data.forEach(item => {
                            console.log(item.status); // Periksa nilai status yang diterima
                            let statusText = '';

                            // Tentukan status berdasarkan value status
                            if (item.status == '2') {
                                statusText =
                                    '<span class="badge bg-info align-items-center" style="font-size: 12px;">Telah diajukan sales</span>';
                            } else if (item.status == '3') {
                                statusText =
                                    '<span class="badge bg-primary align-items-center" style="font-size: 12px;">Telah dikonfirmasi procurement</span>';
                            } else if (item.status == '4') {
                                statusText =
                                    '<span class="badge bg-warning align-items-center" style="font-size: 12px;">Progress telah berjalan</span>';
                            } else if (item.status == '5') {
                                statusText =
                                    '<span class="badge bg-info align-items-center" style="font-size: 12px;">Finish</span>';
                            }

                            // Tambahkan baris ke dalam tabel
                            const row = `
                    <tr>
                        <td>${item.nama_customer}</td>
                        <td>${item.nama_project}</td>
                        <td>${item.keterangan ? item.keterangan : '-'}</td>
                        <td>${statusText}</td>
                        <td>${item.created_at}</td>
                        <td>${item.modified_at}</td>
                    </tr>`;
                            historyTableBody.innerHTML += row;
                        });

                        $('#historyModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error('Terjadi kesalahan:', error);
                    }
                });
            }
        </script>

    </main><!-- End #main -->
@endsection
