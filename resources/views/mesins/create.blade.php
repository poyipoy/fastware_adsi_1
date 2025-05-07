@extends('layout')

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Dept.Head Maintenance</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">Mesin & Approved FPP</li>
                    <li class="breadcrumb-item active">Tambah Data Mesin</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Form Tambah Mesin</h5>

                                <form id="mesinForm" action="{{ route('mesins.store') }}" method="POST"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="section" class="form-label">
                                            Section<span style="color: red;">*</span>
                                        </label>
                                        <select class="form-control" id="section" name="section">
                                            <option value="">Pilih Section</option>
                                            @php
                                                // Filter mesins berdasarkan status 0
                                                $mesinsWithStatusZero = $mesins->where('status', 0);
                                                // Ambil nilai section yang unik dari mesins dengan status 0
                                                $uniqueSections = $mesinsWithStatusZero
                                                    ->unique('section')
                                                    ->pluck('section');
                                            @endphp
                                            @foreach ($uniqueSections as $section)
                                                <option value="{{ $section }}">{{ $section }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="tipe" class="form-label">
                                            Tipe<span style="color: red;">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="tipe" name="tipe">
                                    </div>

                                    <div class="mb-3">
                                        <label for="no_mesin" class="form-label">
                                            Nomor Mesin<span style="color: red;">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="no_mesin" name="no_mesin">
                                    </div>

                                    <div class="mb-3">
                                        <label for="tanggal_dibuat" class="form-label">
                                            Manufacturing Year<span style="color: red;">*</span>
                                        </label>
                                        <input type="number" class="form-control" id="tanggal_dibuat" name="tanggal_dibuat"
                                            min="1900" max="2099" onchange="hitungUmur()">
                                    </div>

                                    <div class="mb-3">
                                        <label for="umur" class="form-label">Umur<span
                                                style="color: red;">*</span></label>
                                        <input type="text" class="form-control" id="umur" name="umur" readonly>
                                        <!-- Jika Anda ingin melakukan perhitungan Age secara otomatis, Anda perlu menambahkan JavaScript untuk menghitungnya. -->
                                    </div>

                                    <div class="mb-3">
                                        <label for="spesifikasi" class="form-label">
                                            Spesifikasi<span style="color: red;">*</span>
                                        </label>
                                        <textarea class="form-control" id="spesifikasi" name="spesifikasi"></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="lokasi" class="form-label">
                                            Lokasi Mesin<span style="color: red;">*</span>
                                        </label>
                                        <select class="form-select" id="lokasi" name="lokasi">
                                            <option value="" disabled selected>Pilih Lokasi Mesin</option>
                                            <option value="Deltamas">Deltamas</option>
                                            <option value="DS8">DS8</option>
                                            <option value="Surabaya">Surabaya</option>
                                        </select>
                                    </div>


                                    <div class="mb-3">
                                        <label for="tanggal_preventif" class="form-label">Schedule Preventive Date</label>
                                        <input type="date" class="form-control" id="tanggal_preventif"
                                            name="tanggal_preventif">
                                    </div>

                                    <img id="fotoPreview" src="" alt=""
                                        style="max-width: 300px; max-height: 200px; display: none; cursor: pointer;">

                                    <div class="mb-3">
                                        <label for="foto" class="form-label">Upload Foto (Jika ada)</label>
                                        <input type="file" class="form-control" id="foto" name="foto">
                                    </div>

                                    <img id="sparepartPreview" src="" alt="Preview Sparepart"
                                        style="max-width: 300px; max-height: 200px; display: none; cursor: pointer;">

                                    <div class="mb-3">
                                        <label for="sparepart" class="form-label">Upload Data Sparepart (Jika ada)</label>
                                        <input type="file" class="form-control" id="sparepart" name="sparepart">
                                    </div>

                                    <!-- Modal untuk foto -->
                                    <div id="fotoModal" class="modal">
                                        <span class="close" onclick="closeFotoModal()">&times;</span>
                                        <div class="modal-content">
                                            <img id="fotoModalImage" src="" alt="">
                                        </div>
                                    </div>

                                    <!-- Modal untuk sparepart -->
                                    <div id="sparepartModal" class="modal">
                                        <span class="close" onclick="closeSparepartModal()">&times;</span>
                                        <div class="modal-content">
                                            <img id="sparepartModalImage" src="" alt="">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                        <a href="{{ route('mesins.index') }}" class="btn btn-danger">Cancel</a>
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
                document.getElementById('mesinForm').reset();
            }
        </script>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


        <script>
            // Fungsi untuk menghitung umur berdasarkan tahun pembuatan
            function hitungUmur() {
                // Ambil tahun pembuatan dari input tahun_dibuat
                var tahunDibuat = document.getElementById('tanggal_dibuat').value;

                // Ambil tahun saat ini
                var tahunSaatIni = new Date().getFullYear();

                // Hitung umur dengan mengurangi tahun saat ini dengan tahun pembuatan
                var umur = tahunSaatIni - tahunDibuat;

                // Masukkan hasil perhitungan umur ke input umur
                document.getElementById('umur').value = umur;
            }

            // Perbarui umur setiap kali tahun berubah
            setInterval(function() {
                hitungUmur();
            }, 1000 * 60 * 60 * 24); // Perbarui setiap hari
        </script>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var fotoInput = document.getElementById('foto');
                var sparepartInput = document.getElementById('sparepart');

                var fotoPreview = document.getElementById('fotoPreview');
                var sparepartPreview = document.getElementById('sparepartPreview');

                fotoInput.addEventListener('change', function() {
                    previewImage(this, fotoPreview);
                });

                sparepartInput.addEventListener('change', function() {
                    previewImage(this, sparepartPreview);
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

                function toggleFotoModal() {
                    var modal = document.getElementById("fotoModal");
                    var modalImg = document.getElementById("fotoModalImage");
                    if (modal.style.display === "block") {
                        modal.style.display = "none";
                    } else {
                        modal.style.display = "block";
                        modalImg.src = fotoPreview.src;
                    }
                }

                function closeFotoModal() {
                    var modal = document.getElementById("fotoModal");
                    modal.style.display = "none";
                }

                function toggleSparepartModal() {
                    var modal = document.getElementById("sparepartModal");
                    var modalImg = document.getElementById("sparepartModalImage");
                    if (modal.style.display === "block") {
                        modal.style.display = "none";
                    } else {
                        modal.style.display = "block";
                        modalImg.src = sparepartPreview.src;
                    }
                }

                function closeSparepartModal() {
                    var modal = document.getElementById("sparepartModal");
                    modal.style.display = "none";
                }

                var fotoModal = document.getElementById("fotoModal");
                var sparepartModal = document.getElementById("sparepartModal");

                // Menambahkan event listener untuk menutup modal saat tombol "X" diklik
                fotoModal.querySelector(".close").addEventListener('click', function() {
                    closeFotoModal();
                });

                sparepartModal.querySelector(".close").addEventListener('click', function() {
                    closeSparepartModal();
                });

                // Menambahkan event listener untuk menutup modal saat gambar pratinjau diklik kembali
                fotoPreview.addEventListener('click', function() {
                    toggleFotoModal();
                });

                sparepartPreview.addEventListener('click', function() {
                    toggleSparepartModal();
                });
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

    </main><!-- End #main -->
@endsection
