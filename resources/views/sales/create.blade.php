@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Halaman Tambah Data</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Menu Handling</a></li>
                    <li class="breadcrumb-item active">Halaman Tambah Data</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Form Tambah Data</h5>
                        <form id="formInputHandling" action="{{ route('store') }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row">
                                <div class="col lg-6">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="modified_by" class="col-sm-4 col-form-label">User : <span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="hidden" id="user_id" name="user_id"
                                                value="{{ Auth::user()->id }}">
                                            <!-- Tampilkan nama pengguna yang sedang masuk (opsional) -->
                                            <input type="text" class="form-control" id="modified_by" name="modified_by"
                                                maxlength="6" style="width: 100%; max-width: 100%;"
                                                placeholder="{{ Auth::user()->name }}" disabled>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="no_wo" class="col-sm-6 col-form-label">No. WO:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" class="form-control" id="no_wo" name="no_wo"
                                                maxlength="6" style="width: 100%; max-width: 100%;" required>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="customer_code" class="col-sm-5 col-form-label">Kode Pelanggan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="searchable-dropdown">
                                                <input type="text" id="search_customer"
                                                    placeholder="🔍 Search or select customer code"
                                                    style="width: 100%; margin-bottom: 10px;">
                                                <select id="customer_id" name="customer_id" class="form-control"
                                                    style="width: 100%;" required>
                                                    <option value="" disabled selected>🔍 Search or select customer
                                                        code</option>
                                                    @foreach ($customers as $customer)
                                                        <option value="{{ $customer->id }}"
                                                            data-name_customer="{{ $customer->name_customer }}"
                                                            data-area="{{ $customer->area }}">
                                                            {{ $customer->customer_code }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="name_customer" class="col-sm-6 col-form-label">Nama Pelanggan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="name_customer" class="form-control" id="name_customer"
                                                style="width: 100%;" required disabled>
                                                <option>🔍 Search or select customer</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->name_customer }}">
                                                        {{ $customer->name_customer }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="area" class="col-sm-5 col-form-label">Area Pelanggan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="area" class="form-control" id="area" style="width: 100%"
                                                required disabled>
                                                <option>🔍 Search or select area</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->area }}">
                                                        {{ $customer->area }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="notes" class="col-sm-5 col-form-label">Request:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="notes" class="form-control" id="notes" style="width: 100%;"
                                                required>
                                                <option value="">------------- Pilih Request ------------
                                                </option>
                                                <option value="Testing">Testing</option>
                                                <option value="Trial">Trial</option>
                                                <option value="Klaim / Komplain">Klaim / Komplain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="type_material" class="col-sm-5 col-form-label">Tipe Bahan:</label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select id="type_id" name="type_id" class="form-select"
                                                style="width: 100%">
                                                <option value="">------------- Type Material ------------</option>
                                                @foreach ($type_materials as $typematerial)
                                                    <option value="{{ $typematerial->id }}">
                                                        {{ $typematerial->type_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="category" class="col-sm-6 col-form-label">Nama Barang:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" class="form-control" id="nama_barang"
                                                name="nama_barang" style="width: 100%; max-width: 100%;">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="t" class="form-label">T:</label>
                                            <input type="text" class="form-control input-sm" id="thickness"
                                                name="thickness" placeholder="Thickness" style="max-width: 80%"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">W:</label>
                                            <input type="text" class="form-control input-sm" id="weight"
                                                name="weight" placeholder="Weight" style="max-width: 80%;"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">L:</label>
                                            <input type="text" class="form-control input-sm" id="length"
                                                name="length" placeholder="Lenght" style="max-width: 80%;"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">OD:</label>
                                            <input type="text" class="form-control input-sm" id="outer_diameter"
                                                name="outer_diameter" placeholder="Outer Diameter" style="max-width: 80%"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">ID:</label>
                                            <input type="text" class="form-control input-sm" id="inner_diameter"
                                                name="inner_diameter" placeholder="Inner Diameter" style="max-width: 80%"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="qty" class="form-label">QTY (Kg):<span
                                                    style="color: red;">*</span></label>
                                            <input type="text" class="form-control input-sm" id="qty"
                                                name="qty" style="max-width: 80%;" required pattern="[0-9]+"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="pcs" class="form-label">Unit (Pcs):<span
                                                    style="color: red;">*</span></label>
                                            <input type="text" class="form-control input-sm" id="pcs"
                                                name="pcs" style="max-width: 80%" required
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                    </div>
                                    <br>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="cataegory" class="col-sm-5 col-form-label">Kategori (NG):<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="category" class="form-control" id="category" name="category"
                                                style="width: 100%;">
                                                <option value="" class="text-center">-------- Silahkan Pilih
                                                    Kategori --------
                                                </option>
                                                <option value="CT - Ukuran Minus">CT - Ukuran Minus</option>
                                                <option value="CT - Potongan Miring">CT - Potongan Miring</option>
                                                <option value="CT - NG Dimensi">CT - NG Dimensi</option>
                                                <option value="MCH - Dimensi NG">MCH - Dimensi NG</option>
                                                <option value="MCH - Ada Step">MCH - Ada Step</option>
                                                <option value="MCH - NG Paralelism">MCH - NG Paralelism</option>
                                                <option value="MCH - NG Siku">MCH - NG Siku</option>
                                                <option value="HT - NG Siku">HT - NG Siku</option>
                                                <option value="HT - Retak/Patah">HT - Retak/Patah</option>
                                                <option value="HT - Bending">HT - Bending</option>
                                                <option value="HT - Kekerasan Minus">HT - Kekerasan Minus</option>
                                                <option value="HT - Kekerasan Lebih">HT - Kekerasan Lebih</option>
                                                <option value="HT - Scratch/Gores">HT - Scratch/Gores</option>
                                                <option value="HT - Aus">HT - Aus</option>
                                                <option value="HT - Appearance">HT - Appearance</option>
                                                <option value="MKT - Jumlah Tidak Sesuai">MKT - Jumlah Tidak Sesuai
                                                </option>
                                                <option value="MKT - Dimensi Tidak Sesuai">MKT - Dimensi Tidak Sesuai
                                                </option>
                                                <option value="MKT - Type Material Tidak Sesuai">MKT - Type Material Tidak
                                                    Sesuai</option>
                                                <option value="MTRL - Pin Hole">MTRL - Pin Hole</option>
                                                <option value="MTRL - Inklusi">MTRL - Inklusi</option>
                                                <option value="MTRL - Sulit Machining">MTRL - Sulit Machining</option>
                                                <option value="MTRL - Karat">MTRL - Karat</option>
                                                <option value="Others">Others</option>
                                                <!-- Tambahkan opsi statis lainnya jika diperlukan -->
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="category_input" class="col-sm-6 col-form-label">Nama Project:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" class="form-control" id="category_input"
                                                name="category" style="width: 100%; max-width: 100%;">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row" id="jenisTestRow" style="display: none;">
                                        <div class="col-lg-6">
                                            <label for="jenis_test" class="col-sm-5 col-form-label">Jenis test
                                                <span style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="jenis_test" class="form-control" id="jenis_test"
                                                style="width: 100%;">
                                                <option value="">------------- Pilih jenis Test ------------
                                                </option>
                                                <option value="Spectro">Spectro</option>
                                                <option value="Kekerasan">Kekerasan</option>
                                                <option value="Micro Structure">Micro Structure</option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="hasil_tindak_lanjut" class="col-sm-6 col-form-label">Keterangan:
                                                (Jika ada)</label>
                                        </div>
                                        <div class="col-lg-6">
                                            <textarea class="form-control" rows="5" id="results" name="results" style="width: 100%"></textarea>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="process_type" class="col-sm-5 col-form-label">Jenis
                                                Proses:</label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="process_type" class="form-control" id="process_type"
                                                style="width: 100%;">
                                                <option value="">------------------- Tipe Proses -----------------
                                                </option>
                                                <option value="Heat Treatment">Heat treatment</option>
                                                <option value="Cutting">Cutting</option>
                                                <option value="Machining">Machining</option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="proses_type" class="col-sm-5 col-form-label">Jenis:</label>
                                        </div>
                                        <div class="col-lg-6" @required(true)>
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="type_1"
                                                    name="type_1" value="Komplain">
                                                <label class="form-check-label" for="check2">Komplain</label>
                                            </div>
                                            <div class="form-check mr-2">
                                                <input type="checkbox" class="form-check-input" id="type_2"
                                                    name="type_2" value="Klaim">
                                                <label class="form-check-label" for="check1">Klaim</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <br>
                                <div class="col lg-6">
                                    <div class="col lg-6">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <label for="image_upload">Unggah Gambar: (Jika Ada)</span></label>
                                            </div>
                                            <div class="col-lg-6">
                                                <input class="form-control" type="file" id="formFile" name="image[]"
                                                    accept="image/*" style="width: 100%" onchange="viewImage(event);"
                                                    multiple>
                                            </div>
                                            <small id="fileError" class="text-danger" style="display:none;">Format berkas
                                                tidak sesuai. Silakan unggah gambar.</small>
                                            <!-- error message untuk title -->
                                            <div id="imageError" class="alert alert-danger mt-2" style="display:none;">
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div id="imagePreviewContainer" class="row"
                                                style="display: flex; flex-wrap: wrap;">
                                                <!-- Gambar akan ditampilkan di sini -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="imageModal" class="modal"
                                style="display: none; position: fixed; z-index: 1; padding-top: 50px; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.9);">
                                <div
                                    style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; max-width: 700px; background-color: #fefefe; border-radius: 5px;">
                                    <span class="close"
                                        style="position: absolute; top: 10px; right: 10px; color: #000; font-size: 30px; font-weight: bold; cursor: pointer;"
                                        onclick="closeModal()">&times;</span>
                                    <img class="modal-content" id="modalImage"
                                        style="display: block; margin: auto; width: 50%; max-width: 70%;">
                                </div>
                            </div>
                            <div class="row mb-3" style="margin-top: 2%">
                                <div class="col-sm-12 d-flex justify-content-end">
                                    <button id="saveButton" type="submit" class="btn btn-primary mb-4 me-3"
                                        onclick="validateCreate()">
                                        <i class="fas fa-save"></i> Simpan
                                    </button>
                                    <a href="{{ route('index') }}" class="btn btn-primary mb-4 me-2">
                                        <i class="fas fa-arrow-left"></i> Kembali
                                    </a>
                                </div>
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
            document.getElementById('process_type').addEventListener('change', function() {
                var dropdownValue = this.value;
                var checkBox1 = document.getElementById('type_2');
                var requestDropdown = document.getElementById('notes').value; // Ambil nilai dari elemen notes

                // Cek jika notes bernilai 'Klaim / Komplain'
                if (requestDropdown === 'Klaim / Komplain') {
                    checkBox1.disabled = false; // Aktifkan checkbox jika notes bernilai 'Klaim / Komplain'

                    // Cek jika nilai dari process_type adalah 'Cutting', maka centang checkbox
                    if (dropdownValue === 'Cutting') {
                        checkBox1.checked = true;
                    } else {
                        checkBox1.checked = false;
                    }
                } else {
                    checkBox1.checked = false; // Hapus centang checkbox jika tidak memenuhi syarat
                    checkBox1.disabled = true; // Nonaktifkan checkbox
                }
            });


            var imageError = document.getElementById('imageError');
            imageError.style.display = 'none';

            // Fungsi untuk menampilkan modal saat gambar di klik
            function showModal(imageSrc) {
                var modal = document.getElementById("imageModal");
                var modalImg = document.getElementById("modalImage");
                modal.style.display = "block";
                modalImg.src = imageSrc;
            }

            // Fungsi untuk menutup modal saat tombol "x" di klik
            function closeModal() {
                var modal = document.getElementById("imageModal");
                modal.style.display = "none";
            }

            // Fungsi untuk menampilkan gambar saat diunggah
            function viewImage(event) {
                var imageContainer = document.getElementById('imagePreviewContainer');
                imageContainer.innerHTML = ''; // Kosongkan kontainer gambar sebelum menambahkan gambar baru

                var files = event.target.files; // Ambil file-file yang diunggah

                // Iterasi melalui setiap file
                for (var i = 0; i < files.length; i++) {
                    var file = files[i];
                    var reader = new FileReader(); // Buat pembaca file

                    reader.onload = function(e) {
                        // Buat elemen gambar baru
                        var imgElement = document.createElement('img');
                        imgElement.src = e.target.result;

                        // Tentukan ukuran gambar berdasarkan jumlah file yang diunggah
                        if (files.length === 1 || files.length === 2) {
                            imgElement.style.width = '50%'; // Set lebar gambar menjadi 50% untuk 1 atau 2 gambar
                        } else {
                            imgElement.style.width = '33.33%'; // Set lebar gambar menjadi 33.33% untuk lebih dari 2 gambar
                        }

                        // Tambahkan gambar ke dalam kontainer
                        imageContainer.appendChild(imgElement);

                        // Tambahkan fungsi untuk menampilkan modal saat gambar di klik
                        imgElement.onclick = function() {
                            showModal(this.src);
                        };
                    };

                    reader.readAsDataURL(file); // Baca file sebagai URL data
                }

                // Setelah semua gambar ditambahkan, kita akan mengatur ulang lebar kontainer gambar
                setTimeout(function() {
                    var images = imageContainer.querySelectorAll('img');
                    var containerWidth = (files.length > 2) ? '100%' : '50%'; // Tentukan lebar kontainer
                    imageContainer.style.width = containerWidth;
                }, 100); // Beri sedikit waktu agar gambar ditampilkan sebelum menyesuaikan lebar kontainer
            }
            //ddlselect
            document.addEventListener('DOMContentLoaded', function() {
                var searchCustomerInput = document.getElementById('search_customer');
                var customerSelect = document.getElementById('customer_id');
                var customerOptions = customerSelect.options;

                var nameCustomerSelect = document.getElementById('name_customer');
                var areaCustomerSelect = document.getElementById('area');

                // Show the dropdown items when input is focused
                searchCustomerInput.addEventListener('focus', function() {
                    customerSelect.style.display = 'block';
                });

                // Hide the dropdown items when clicking outside
                document.addEventListener('click', function(e) {
                    if (!searchCustomerInput.contains(e.target) && !customerSelect.contains(e.target)) {
                        customerSelect.style.display = 'none';
                    }
                });

                // Filter the dropdown items based on input
                searchCustomerInput.addEventListener('input', function() {
                    var filter = searchCustomerInput.value.toLowerCase();
                    for (var i = 1; i < customerOptions.length; i++) {
                        var option = customerOptions[i];
                        var text = option.text.toLowerCase();
                        option.style.display = text.includes(filter) ? '' : 'none';
                    }
                });

                // Set the input and update other selects when an item is selected
                customerSelect.addEventListener('change', function() {
                    var selectedOption = customerSelect.options[customerSelect.selectedIndex];

                    searchCustomerInput.value = selectedOption.text;

                    var selectedNameCustomer = selectedOption.getAttribute('data-name_customer');
                    var selectedArea = selectedOption.getAttribute('data-area');

                    nameCustomerSelect.value = selectedNameCustomer;
                    areaCustomerSelect.value = selectedArea;
                });
            });

            //button validasi
            function validateCreate() {
                event.preventDefault();

                var no_wo = document.getElementById('no_wo').value.trim();
                var customerCode = document.getElementById('customer_id').value.trim();
                var customerName = document.getElementById('name_customer').value.trim();
                var notes = document.getElementById('notes').value.trim();
                var area = document.getElementById('area').value.trim();
                var qty = document.getElementById('qty').value.trim();
                var pcs = document.getElementById('pcs').value.trim();

                // Memeriksa apakah ada input yang kosong
                if (!no_wo || !customerName || !customerCode || !area || !pcs || !notes) {
                    // Menampilkan sweet alert error jika ada input yang kosong
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Harap lengkapi semua isian yang diperlukan!',
                    });
                } else {
                    // Simulasi validasi
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data telah tersimpan',
                        showConfirmButton: false
                    });
                    document.getElementById('formInputHandling').submit();
                }
            }

            // Event listener untuk submit form
            document.getElementById('formInputHandling').addEventListener('submit', function(event) {
                event.preventDefault(); // Mencegah pengiriman formulir secara default

                // Memanggil fungsi untuk menangani pengiriman formulir dan menampilkan SweetAlert
                validateCreate();
            });


            function hanyaAngka(evt) {
                // Mendapatkan karakter yang ditekan
                var charCode = (evt.which) ? evt.which : event.keyCode;
                // Mengizinkan hanya angka (0-9), tombol backspace, dan tombol delete
                if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 8 && charCode !== 46) {
                    // Mencegah aksi default jika karakter tidak valid
                    evt.preventDefault();
                }
            }

            document.addEventListener("DOMContentLoaded", function() {
                const requestDropdown = document.getElementById('notes');
                const categorySelectRow = document.getElementById('category')?.parentElement?.parentElement;
                const categoryInputRow = document.getElementById('category_input')?.parentElement?.parentElement;
                const typeMaterialRow = document.getElementById('type_id')?.parentElement?.parentElement;
                const jenisTestRow = document.getElementById('jenis_test')?.parentElement?.parentElement;
                const namaBarangRow = document.getElementById('nama_barang')?.parentElement?.parentElement;
                const prosesTypeSelect = document.getElementById('process_type');
                const prosesTypeCheckbox1 = document.getElementById('type_1');
                const prosesTypeCheckbox2 = document.getElementById('type_2');

                function updateUIBasedOnRequest(value) {
                    if (value === "Trial") {
                        categoryInputRow.style.display = "flex";
                        categorySelectRow.style.display = "none";
                        typeMaterialRow.style.display = "flex";
                        jenisTestRow.style.display = "none";
                        namaBarangRow.style.display = "none";
                        if (prosesTypeSelect) prosesTypeSelect.disabled = false;
                        if (prosesTypeCheckbox1) prosesTypeCheckbox1.disabled = true;
                        if (prosesTypeCheckbox2) prosesTypeCheckbox2.disabled = true;
                    } else if (value === "Testing") {
                        categoryInputRow.style.display = "none";
                        categorySelectRow.style.display = "none";
                        typeMaterialRow.style.display = "none";
                        jenisTestRow.style.display = "flex";
                        namaBarangRow.style.display = "flex";
                        if (prosesTypeSelect) prosesTypeSelect.disabled = true;
                        if (prosesTypeCheckbox1) prosesTypeCheckbox1.disabled = true;
                        if (prosesTypeCheckbox2) prosesTypeCheckbox2.disabled = true;
                    } else {
                        categoryInputRow.style.display = "none";
                        categorySelectRow.style.display = "flex";
                        typeMaterialRow.style.display = "flex";
                        jenisTestRow.style.display = "none";
                        namaBarangRow.style.display = "none";
                        if (prosesTypeSelect) prosesTypeSelect.disabled = false;
                        if (prosesTypeCheckbox1) prosesTypeCheckbox1.disabled = false;
                        if (prosesTypeCheckbox2) prosesTypeCheckbox2.disabled = false;
                    }
                }

                updateUIBasedOnRequest(requestDropdown.value);
                requestDropdown.addEventListener('change', function() {
                    updateUIBasedOnRequest(this.value);
                });
            });
        </script>
    </main><!-- End #main -->
@endsection
