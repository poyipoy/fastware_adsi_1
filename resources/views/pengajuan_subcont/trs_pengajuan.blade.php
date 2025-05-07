@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Halaman Pengajuan Data</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('indexProc') }}">Menu Pengajuan Penawaran Subcont</a></li>
                    <li class="breadcrumb-item active">Halaman Pengajuan Data</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5>Form pengajuan Data</h5>
                    </div>
                    <div class="card-body">
                        <form id="pengajuanEditForm" action="{{ route('submit.data', $pengajuan->id) }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf
                            @method('POST')

                            <!-- Nama Customer -->
                            <div class="mb-3">
                                <label for="nama_customer" class="form-label">Nama Customer</label>
                                <input type="text" class="form-control" id="nama_customer" name="nama_customer"
                                    value="{{ $pengajuan->nama_customer }}" placeholder="Masukkan nama customer" disabled>
                            </div>

                            <!-- Nama Project -->
                            <div class="mb-3">
                                <label for="nama_project" class="form-label">Nama Project</label>
                                <input type="text" class="form-control" id="nama_project" name="nama_project"
                                    value="{{ $pengajuan->nama_project }}" placeholder="Masukkan nama project" disabled>
                            </div>

                            <!-- Nama Project -->
                            <div class="mb-3">
                                <label for="nama_project" class="form-label">QTY</label>
                                <input type="text" class="form-control" id="qty" name="qty"
                                    value="{{ $pengajuan->qty }}" placeholder="Masukkan QTY" disabled>
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
                                    placeholder="Masukkan jenis project subcont" disabled>
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

                            <br>
                            <!-- File -->
                            @if (in_array($pengajuan->status_1, [3, 4, 5]))
                                <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                    <label for="quotation_file" class="form-label fw-bold text-primary">
                                        <i class="fas fa-upload"></i> Upload Quotation
                                    </label>

                                    @if (!$pengajuan->quotation_file)
                                        <input type="file" class="form-control" id="quotation_file"
                                            name="quotation_file">
                                    @endif

                                    @if ($pengajuan->quotation_file)
                                        <div class="mt-3">
                                            <p class="mb-1 fw-bold text-secondary">Download Quotation</p>
                                            <a href="{{ asset($pengajuan->quotation_file) }}" target="_blank"
                                                class="btn btn-outline-secondary" data-bs-toggle="tooltip"
                                                title="Click to download the quotation file">
                                                <i class="fas fa-file-pdf fa-lg me-2"></i> View Quotation
                                            </a>
                                        </div>
                                    @else
                                        <div class="mt-3">
                                            <span class="text-muted fst-italic">Quotation belum tersedia</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- Tombol Submit -->
                            <div class="d-flex justify-content-between" style="margin-top: 3%">
                                <!-- Div untuk tombol di sebelah kiri (Lihat Histori) -->
                                <div class="d-flex justify-content-start">
                                    <button type="button" class="btn btn-warning mb-4 me-3"
                                        onclick="showHistory({{ $pengajuan->id }})">
                                        <i class="fas fa-eye"></i> Lihat Histori
                                    </button>
                                </div>

                                <!-- Div untuk tombol di sebelah kanan -->
                                <div class="d-flex justify-content-end">
                                    <!-- Cek jika status_1 memiliki nilai 2 -->
                                    @if ($pengajuan->status_1 == 2)
                                        <button id="confirmButton" type="button" class="btn btn-warning mb-4 me-3"
                                            onclick="kirimData('{{ route('pengajuan-subcont.kirim2', $pengajuan->id) }}')">
                                            <i class="fas fa-check-circle"></i> Konfirmasi
                                        </button>
                                    @endif

                                    <!-- Cek jika status_1 memiliki nilai 3 -->
                                    @if ($pengajuan->status_1 == 3 || $pengajuan->status_1 == 4)
                                        <button id="saveButton" type="button" class="btn btn-primary mb-4 me-3"
                                            onclick="submitData('{{ route('submit.data', $pengajuan->id) }}', {{ $pengajuan->id }})">
                                            <i class="fas fa-save"></i> Submit
                                        </button>

                                        <button id="finishButton" type="button" class="btn btn-success mb-4 me-3"
                                            onclick="kirimData2('{{ route('FinishProc', $pengajuan->id) }}')">
                                            <i class="fas fa-flag-checkered"></i> Finish
                                        </button>
                                    @endif

                                    <a href="{{ route('indexProc') }}" class="btn btn-primary mb-4 me-2">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <!-- Modal untuk menampilkan histori -->
                <div class="modal fade" id="historyModal" tabindex="-1" role="dialog"
                    aria-labelledby="historyModalLabel" aria-hidden="true">
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
                                <a href="{{ route('pengajuan-subcont.editProc', $pengajuan->id) }}"
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

            // Fungsi untuk Kondirmasi
            function kirimData(url) {
                Swal.fire({
                    title: 'Apakah Anda yakin ingin Konfirmasi data ini?',
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
                                status_1: 2, // status_1 diubah menjadi 2
                                status_2: 2 // status_2 juga diubah menjadi 2
                            })
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire(
                                    'Terkirim!',
                                    'Status berhasil diubah menjadi "Dikirim".',
                                    'success'
                                ).then(() => {
                                    window.location.reload(); // Reload halaman setelah sukses
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

            // Fungsi untuk Kondirmasi
            function kirimData2(url) {
                Swal.fire({
                    title: 'Apakah Anda yakin ingin Finish data ini?',
                    text: "Status akan diubah menjadi 'Finish'.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Finsih!',
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
                                status_1: 2, // status_1 diubah menjadi 2
                                status_2: 2 // status_2 juga diubah menjadi 2
                            })
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire(
                                    'Terkirim!',
                                    'Status berhasil diubah menjadi "Dikirim".',
                                    'success'
                                ).then(() => {
                                    window.location.reload(); // Reload halaman setelah sukses
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

            function submitData(url) {
                Swal.fire({
                    title: 'Keterangan',
                    input: 'textarea',
                    inputPlaceholder: 'Tulis keterangan di sini...',
                    inputAttributes: {
                        'aria-label': 'Tulis keterangan'
                    },
                    showCancelButton: true,
                    confirmButtonText: 'Submit',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    preConfirm: (keterangan) => {
                        if (!keterangan) {
                            Swal.showValidationMessage('Keterangan tidak boleh kosong!')
                        }
                        return keterangan;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        let formData = new FormData(document.getElementById('pengajuanEditForm')); // Ambil seluruh form
                        formData.append('keterangan', result.value); // Tambahkan keterangan ke form data

                        fetch(url, { // Tidak perlu menambah /id di sini
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: formData
                        }).then(response => {
                            if (response.ok) {
                                Swal.fire(
                                    'Berhasil!',
                                    'Data telah berhasil disubmit.',
                                    'success'
                                ).then(() => {
                                    window.location.reload(); // Reload halaman setelah sukses
                                });
                            } else {
                                Swal.fire(
                                    'Gagal!',
                                    'Gagal mengirim data.',
                                    'error'
                                );
                            }
                        }).catch(error => {
                            Swal.fire(
                                'Kesalahan!',
                                'Terjadi kesalahan saat mengirim data!',
                                'error'
                            );
                        });
                    }
                });
            }

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
