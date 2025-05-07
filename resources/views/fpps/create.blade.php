@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Production</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Form Permintaan Perbaikan</li>
                    <li class="breadcrumb-item active">Data Form Perbaikan</li>
                    <li class="breadcrumb-item active">Buat Form</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Buat Form</h5>

                                <form id="FPPForm" action="{{ route('formperbaikans.store') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf

                                    <div class="mb-3">
                                        <label for="pemohon" class="form-label">
                                            Pemohon<span style="color: red;">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="pemohon" name="pemohon"
                                            value="{{ $loggedInUser->name }}" readonly>
                                    </div>


                                    <div class="mb-3">
                                        <label for="tanggal" class="form-label">
                                            Tanggal<span style="color: red;">*</span>
                                        </label>
                                        <input type="date" class="form-control" id="tanggal" name="tanggal">
                                    </div>

                                    <!-- Select section -->
                                    <div class="mb-3">
                                        <label for="section" class="form-label">Pilih Bagian<span
                                                style="color: red;">*</span></label>
                                        <select class="form-control" id="section" name="section">
                                            <option value="">Pilih Bagian</option>
                                            @php
                                                $uniqueSections = $mesins
                                                    ->where('status', 0)
                                                    ->unique('section')
                                                    ->pluck('section');
                                            @endphp
                                            @foreach ($uniqueSections as $section)
                                                <option value="{{ $section }}">{{ $section }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <!-- Select mesin -->
                                    <div class="mb-3">
                                        <label for="mesin" class="form-label">Pilih Mesin<span
                                                style="color: red;">*</span></label>
                                        <select class="form-control" id="mesin" name="mesin"
                                            onchange="checkSelectedOption()">
                                            <option value="">Pilih Mesin</option>
                                            @foreach ($mesins as $mesin)
                                                <option value="{{ $mesin->no_mesin }}" data-lokasi="{{ $mesin->lokasi }}">
                                                    {{ $mesin->no_mesin }} | {{ $mesin->tipe }}</option>
                                            @endforeach
                                            <option value="Others">Lainnya</option> <!-- Tambahkan opsi "Others" di sini -->
                                        </select>
                                    </div>

                                    <div class="mb-3" id="othersFields" style="display: none;">
                                        <label for="namaAlatBantu" class="form-label">Nama Alat Bantu<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="namaAlatBantu" name="namaAlatBantu">
                                    </div>


                                    <div class="mb-3">
                                        <label for="lokasi" class="form-label">Lokasi<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="lokasi" name="lokasi" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label for="kendala" class="form-label">
                                            Kendala<span style="color: red;">*</span>
                                        </label>
                                        <textarea class="form-control" id="kendala" name="kendala"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <img id="gambarPreview" src="" alt="" width="300" height="200"
                                            style="display: none; cursor: pointer;" onclick="toggleImageModal()">
                                    </div>
                                    <div class="mb-3">
                                        <label for="gambar" class="form-label">
                                            Unggah Gambar (Jika Ada)<span style="color: red;"></span>
                                        </label>
                                        <input type="file" class="form-control" id="gambar" name="gambar"
                                            onchange="previewImage()" accept=".jpg,.jpeg,.png">
                                    </div>

                                    <!-- Modal -->
                                    <div id="imageModal" class="modal">
                                        <span class="close" onclick="closeImageModal()">&times;</span>
                                        <img class="modal-content" id="img01">
                                    </div>

                                    <input type="hidden" name="note" id="note" value="">

                                    <div class="mb-3">
                                        <button type="button" class="btn btn-primary"
                                            onclick="handleSaveButtonClick()">Simpan</button>
                                        <button type="button" class="btn btn-secondary"
                                            onclick="resetForm()">Reset</button>
                                    </div>
                                </form>
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
            function resetForm() {
                document.getElementById('FPPForm').reset();
            }
        </script>
        <script>
            function handleFormSubmission() {
                // Memeriksa apakah semua isian telah diisi
                var pemohon = document.getElementById('pemohon').value;
                var tanggal = document.getElementById('tanggal').value;
                var mesin = document.getElementById('mesin').value;
                var lokasi = document.getElementById('lokasi').value;
                var section = document.getElementById('section').value;
                var kendala = document.getElementById('kendala').value;

                if (pemohon === '' || tanggal === '' || mesin === '' || lokasi === '' || kendala === '' || section === '') {
                    // Menampilkan SweetAlert jika ada isian yang kosong kecuali upload gambar
                    Swal.fire({
                        title: 'Gagal!',
                        text: 'Mohon lengkapi semua isian, kecuali unggah gambar.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                } else {
                    // Jika formulir valid, tampilkan SweetAlert untuk konfirmasi
                    Swal.fire({
                        title: 'Berhasil!',
                        text: 'Form Permintaan Perbaikan berhasil disimpan.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect or perform any other action after clicking OK
                            document.getElementById('FPPForm').submit();
                        }
                    });
                }
            }

            // Event listener for form submission
            document.getElementById('FPPForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission

                // Call the function to handle form submission and show SweetAlert
                handleFormSubmission();
            });

            function resetForm() {
                document.getElementById('FPPForm').reset();
                document.getElementById('gambarPreview').style.display = 'none';
            }
        </script>

        <script>
            function handleSaveButtonClick() {
                // Validate file size
                const fileInput = document.getElementById('gambar');
                const file = fileInput.files[0];
                if (file && file.size > 15 * 1024 * 1024) { // 15 MB in bytes
                    Swal.fire({
                        icon: 'error',
                        title: 'File Terlalu Besar',
                        text: 'Ukuran file tidak boleh melebihi 15MB!',
                    });
                    fileInput.value = ''; // Clear the file input
                    return; // Stop further execution
                }

                // Show SweetAlert confirmation
                Swal.fire({
                    title: 'Konfirmasi',
                    text: 'Apakah Anda yakin ingin menyimpan perubahan?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Tidak'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Show success notification
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Perubahan berhasil disimpan!',
                            showConfirmButton: false,
                            timer: 2000,
                            didClose: () => {
                                // Submit the form after the success notification is closed
                                document.getElementById('FPPForm').submit();
                            }
                        });
                    }
                });
            }
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var gambarInput = document.getElementById('gambar');
                var gambarPreview = document.getElementById('gambarPreview');
                var modal = document.getElementById("imageModal");
                var modalImg = document.getElementById("img01");

                gambarInput.addEventListener('change', function() {
                    previewImage(this, gambarPreview);
                });

                function previewImage(input, previewElement) {
                    var file = input.files[0];
                    var reader = new FileReader();

                    reader.onload = function(e) {
                        previewElement.src = e.target.result;
                        previewElement.style.display = 'block';
                    };

                    reader.readAsDataURL(file);
                }

                function toggleImageModal() {
                    if (modal.style.display === "block") {
                        closeImageModal();
                    } else {
                        modal.style.display = "block";
                        modalImg.src = gambarPreview.src;
                    }
                }

                function closeImageModal() {
                    modal.style.display = "none";
                }

                // Menambahkan event listener untuk menutup modal saat tombol "X" diklik
                var closeButton = document.querySelector(".close");
                closeButton.addEventListener('click', function() {
                    closeImageModal();
                });

                // Menambahkan event listener untuk menutup modal saat gambar pratinjau diklik kembali
                gambarPreview.addEventListener('click', function() {
                    toggleImageModal();
                });
            });
        </script>
        <script>
            document.getElementById('section').addEventListener('change', function() {
                var selectedSection = this.value;
                var mesinSelect = document.getElementById('mesin');

                // Kosongkan opsi nomor mesin
                mesinSelect.innerHTML = '';

                // Tambahkan opsi default
                var defaultOption = document.createElement('option');
                defaultOption.value = "";
                defaultOption.textContent = "Pilih Mesin";
                mesinSelect.appendChild(defaultOption);

                // Filter dan tambahkan opsi nomor mesin sesuai dengan section yang dipilih
                @foreach ($mesins as $mesin)
                    if ("{{ $mesin->section }}" === selectedSection) {
                        var option = document.createElement('option');
                        option.value = "{{ $mesin->no_mesin }}";
                        option.textContent = "{{ $mesin->no_mesin }} | {{ $mesin->tipe }}";
                        option.setAttribute('data-lokasi', "{{ $mesin->lokasi }}"); // Tambahkan atribut data-lokasi
                        mesinSelect.appendChild(option);
                    }
                @endforeach

                // Tambahkan opsi "Others" di bawah
                var othersOption = document.createElement('option');
                othersOption.value = "Others";
                othersOption.textContent = "Others";
                mesinSelect.appendChild(othersOption);
            });
        </script>

        <script>
            document.getElementById('mesin').addEventListener('change', function() {
                var selectedMesin = this.value;
                var lokasiInput = document.getElementById('lokasi');
                var namaAlatBantuInput = document.getElementById('othersFields');
                var othersFields = document.getElementById('othersFields');

                if (selectedMesin === 'Others') {
                    // Jika opsi "Others" dipilih, tampilkan input "Nama Alat Bantu" dan hapus readonly pada input lokasi
                    namaAlatBantuInput.style.display = 'block';
                    lokasiInput.readOnly = false;
                    lokasiInput.value = ''; // Kosongkan nilai lokasi
                    othersFields.style.display = 'block'; // Tampilkan div "Others" fields
                } else {
                    // Jika opsi bukan "Others" dipilih, sembunyikan input "Nama Alat Bantu" dan tetapkan readonly pada input lokasi
                    namaAlatBantuInput.style.display = 'none';
                    lokasiInput.readOnly = true;
                    // Temukan lokasi dari mesin yang dipilih
                    var selectedOption = this.options[this.selectedIndex];
                    var lokasi = selectedOption.getAttribute('data-lokasi');
                    // Perbarui nilai input teks lokasi sesuai dengan lokasi mesin yang dipilih
                    lokasiInput.value = lokasi;
                    othersFields.style.display = 'none'; // Sembunyikan div "Others" fields
                }
            });
        </script>
        <style>
            /* CSS untuk modal */
            .modal {
                display: none;
                position: fixed;
                z-index: 1;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
                width: 600px;
                /* Ubah ukuran lebar */
                height: 400px;
                /* Ubah ukuran tinggi */
                overflow: auto;
                background-color: white;
                border: 1px solid #ccc;
                border-radius: 4px;
                box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
                padding: 20px;
            }

            /* CSS untuk tombol close */
            .close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .close:hover,
            .close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }

            /* CSS untuk gambar di dalam modal */
            .modal-content {
                width: 100%;
                height: auto;
            }
        </style>

    </main>
@endsection
