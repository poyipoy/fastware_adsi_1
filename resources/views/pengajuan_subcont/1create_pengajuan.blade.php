@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Halaman Tambah Data</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('indexSales') }}">Menu Pengajuan Penawaran Subcont</a></li>
                    <li class="breadcrumb-item active">Halaman Tambah Data</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5>Form Tambah Data</h5>
                    </div>

                    <div class="card-body" style="margin-top: 3%">
                        <form id="pengajuanForm" action="{{ route('pengajuan-subcont.store') }}" method="POST"
                            enctype="multipart/form-data">
                            @csrf

                            <!-- Nama Customer -->
                            <div class="mb-3">
                                <label for="nama_customer" class="form-label">Nama Customer<span
                                        style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="nama_customer" name="nama_customer"
                                    placeholder="Masukkan nama customer" required>
                            </div>

                            <!-- Nama Project -->
                            <div class="mb-3">
                                <label for="nama_project" class="form-label">Nama Project<span
                                        style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="nama_project" name="nama_project"
                                    placeholder="Masukkan nama project" required>
                            </div>

                            <div class="mb-3">
                                <label for="qty" class="form-label">QTY<span style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="qty" name="qty"
                                    placeholder="Masukkan QTY" required>
                            </div>

                            <!-- Keterangan (Textarea) -->
                            <div class="mb-3">
                                <label for="keterangan" class="form-label">Keterangan(Jika Ada)</label>
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="4" placeholder="Masukkan keterangan"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="jenis_proses_subcont" class="form-label">Jenis Proses Subcont<span
                                        style="color: red;">*</span></label>
                                <input type="text" class="form-control" id="jenis_proses_subcont"
                                    name="jenis_proses_subcont" placeholder="Masukkan jenis proses subcont" required>
                            </div>

                            <!-- File -->
                            <div class="mb-4 p-3 border rounded shadow-sm bg-light">
                                <label for="file" class="form-label fw-bold text-primary">
                                    <i class="fas fa-upload"></i> Upload Drawing <span style="color: red;">*</span>
                                </label>
                                <input type="file" class="form-control" id="file" name="file" multiple required>
                                <ol id="file-list" class="mt-2"></ol> <!-- List nama file yang diunggah -->
                            </div>

                            <!-- Tombol Submit -->
                            <div class="d-flex justify-content-end">
                                <button id="saveButton" type="submit" class="btn btn-primary mb-4 me-3">
                                    <i class="fas fa-save"></i> Simpan
                                </button>
                                <a href="{{ route('indexSales') }}" class="btn btn-primary mb-4 me-2">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
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
            document.getElementById('pengajuanForm').addEventListener('submit', function(event) {
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

                document.getElementById('file').addEventListener('change', function(event) {
                let fileList = event.target.files;
                let fileListContainer = document.getElementById('file-list');
                fileListContainer.innerHTML = ''; // Kosongkan daftar sebelum diisi ulang

                if (fileList.length > 0) {
                    for (let i = 0; i < fileList.length; i++) {
                        let listItem = document.createElement('li');
                        
                        // Buat link untuk membuka file jika sudah terupload
                        let link = document.createElement('a');
                        link.href = URL.createObjectURL(fileList[i]);  // Menggunakan URL.createObjectURL untuk akses lokal file
                        link.target = '_blank';  // Membuka di tab baru
                        link.textContent = fileList[i].name;  // Nama file yang akan ditampilkan
                        listItem.appendChild(link);
                        
                        fileListConta   iner.appendChild(listItem);
                    }
 x               }
            });

                document.getElementById('file').addEventListener('change', function(event) {
                let fileList = document.getElementById('file-list');
                fileList.innerHTML = ""; // Reset daftar file seb  
                //  p   elumnya

                Array.from(event.target.files).forEach((file, index) => {
                    let listItem = document.createElement("li");
                    listItem.textContent = (index + 1) + ". " + file.name;
                    fileList.appendChild(listItem);
                    });
                });

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
        </script>

    </main><!-- End #main -->
@endsection
