@extends('layout')

@section('content')
    <main id="main" class="main">

        <div class="pagetitle">
            <h1>Halaman Ubah Data</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Menu Handling</a></li>
                    <li class="breadcrumb-item active">Halaman Ubah Data</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Form Ubah Data</h5>
                        <form id="updateForm" action="{{ route('update', $handlings->id) }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="modified_by" class="col-sm-2 col-form-label">User : <span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" class="form-control" id="modified_by" name="modified_by"
                                                maxlength="6" style="width: 100%; max-width: 100%;"
                                                placeholder="{{ Auth::user()->name }}" disabled>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="no_wo" class="col-sm-5 col-form-label">No. WO:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" class="form-control" id="no_wo" name="no_wo"
                                                maxlength="6" style="width: 100%;" value="{{ $handlings->no_wo }}">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="customer_code" class="col-sm-5 col-form-label">Kode Pelanggan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="customer_id" id="customer_id_code" class="select2"
                                                style="width: 100%" onchange="updateCustomerInfo()">
                                                <option value="">Pilih Kode Pelanggan</option>
                                                @foreach ($customers as $customer)
                                                    <option value="{{ $customer->id }}"
                                                        @if ($customer->id == $handlings->customer_id) selected @endif
                                                        data-name_customer="{{ $customer->name_customer }}"
                                                        data-area="{{ $customer->area }}">
                                                        {{ $customer->customer_code }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="customer_name" class="col-sm-5 col-form-label">Nama Pelanggan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" id="customer_name" class="form-control"
                                                value="{{ $handlings->customers->name_customer }}" disabled>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="area" class="col-sm-5 col-form-label">Area Pelanggan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" id="customer_area" class="form-control"
                                                value="{{ $handlings->customers->area }}" disabled>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="notes" class="col-sm-5 col-form-label">Request:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="notes" class="form-control" id="notes" style="width: 100%;">
                                                <option value="">-------------Pilih Request ------------
                                                </option>
                                                <option value="Testing"
                                                    {{ $handlings->notes == 'Testing' ? 'selected' : '' }}>Testing</option>
                                                <option value="Trial"
                                                    {{ $handlings->notes == 'Trial' ? 'selected' : '' }}>Trial</option>
                                                <option value="Klaim / Komplain"
                                                    {{ $handlings->notes == 'Klaim / Komplain' ? 'selected' : '' }}>Klaim /
                                                    Komplain</option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="area" class="col-sm-5 col-form-label">Tipe Bahan:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="type_id" id="type_id" class="select2" style="width: 100%">
                                                @foreach ($type_materials as $typeMaterial)
                                                    <option value="{{ $typeMaterial->id }}"
                                                        @if ($typeMaterial->id == $handlings->type_id) selected @endif>
                                                        {{ $typeMaterial->type_name }}
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
                                                name="nama_barang" style="width: 100%; max-width: 100%;"
                                                value="{{ $handlings->nama_barang }}">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="t" class="form-label">T:</label>
                                            <input type="text" class="form-control input-sm" id="thickness"
                                                name="thickness" placeholder="Thickness" style="max-width: 100%;"
                                                value="{{ $handlings->thickness }}" onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">W:</label>
                                            <input type="text" class="form-control input-sm" id="weight"
                                                name="weight" placeholder="Weight" style="max-width: 100%;"
                                                value="{{ $handlings->weight }}" onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">L:</label>
                                            <input type="text" class="form-control input-sm" id="length"
                                                name="length" placeholder="length" style="max-width: 100%;"
                                                value="{{ $handlings->length }}" onkeypress="hanyaAngka(event)">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">OD:</label>
                                            <input type="text" class="form-control input-sm" id="outer_diameter"
                                                name="outer_diameter" placeholder="Outer Diameter"
                                                style="max-width: 100%" value="{{ $handlings->outer_diameter }}"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="w" class="form-label">ID:</label>
                                            <input type="text" class="form-control input-sm" id="inner_diameter"
                                                name="inner_diameter" placeholder="Inner Diameter"
                                                style="max-width: 100%" value="{{ $handlings->inner_diameter }}"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="qty" class="form-label">QTY (Kg):</label>
                                            <input type="text" class="form-control input-sm" id="qty"
                                                name="qty" style="max-width: 100%;" value="{{ $handlings->qty }}"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="pcs" class="form-label">Unit (Pcs):<span
                                                    style="color: red;">*</span></label>
                                            <input type="text" class="form-control input-sm" id="pcs"
                                                name="pcs" style="max-width: 100%" value="{{ $handlings->pcs }}"
                                                onkeypress="hanyaAngka(event)">
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="category" class="col-sm-5 col-form-label">Kategori (NG):<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="category" class="form-control" id="category"
                                                style="width: 100%">
                                                <option value="" class="text-center">-------- Silahkan Pilih
                                                    Kategori --------</option>
                                                <option value="CT - Ukuran Minus"
                                                    {{ $handlings->category == 'CT - Ukuran Minus' ? 'selected' : '' }}>CT
                                                    - Ukuran Minus</option>
                                                <option value="CT - Potongan Miring"
                                                    {{ $handlings->category == 'CT - Potongan Miring' ? 'selected' : '' }}>
                                                    CT - Potongan Miring</option>
                                                <option value="CT - NG Dimensi"
                                                    {{ $handlings->category == 'CT - NG Dimensi' ? 'selected' : '' }}>CT -
                                                    NG Dimensi</option>
                                                <option value="MCH - Dimensi NG"
                                                    {{ $handlings->category == 'MCH - Dimensi NG' ? 'selected' : '' }}>MCH
                                                    - Dimensi NG</option>
                                                <option value="MCH - Ada Step"
                                                    {{ $handlings->category == 'MCH - Ada Step' ? 'selected' : '' }}>MCH -
                                                    Ada Step</option>
                                                <option value="MCH - NG Paralelism"
                                                    {{ $handlings->category == 'MCH - NG Paralelism' ? 'selected' : '' }}>
                                                    MCH - NG Paralelism</option>
                                                <option value="MCH - NG Siku"
                                                    {{ $handlings->category == 'MCH - NG Siku' ? 'selected' : '' }}>MCH -
                                                    NG Siku</option>
                                                <option value="HT - NG Siku"
                                                    {{ $handlings->category == 'HT - NG Siku' ? 'selected' : '' }}>HT - NG
                                                    Siku</option>
                                                <option value="HT - Retak/Patah"
                                                    {{ $handlings->category == 'HT - Retak/Patah' ? 'selected' : '' }}>HT -
                                                    Retak/Patah</option>
                                                <option value="HT - Bending"
                                                    {{ $handlings->category == 'HT - Bending' ? 'selected' : '' }}>HT -
                                                    Bending</option>
                                                <option value="HT - Kekerasan Minus"
                                                    {{ $handlings->category == 'HT - Kekerasan Minus' ? 'selected' : '' }}>
                                                    HT - Kekerasan Minus</option>
                                                <option value="HT - Kekerasan Lebih"
                                                    {{ $handlings->category == 'HT - Kekerasan Lebih' ? 'selected' : '' }}>
                                                    HT - Kekerasan Lebih</option>
                                                <option value="HT - Scratch/Gores"
                                                    {{ $handlings->category == 'HT - Scratch/Gores' ? 'selected' : '' }}>HT
                                                    - Scratch/Gores</option>
                                                <option value="HT - Aus"
                                                    {{ $handlings->category == 'HT - Aus' ? 'selected' : '' }}>HT - Aus
                                                </option>
                                                <option value="HT - Appearance"
                                                    {{ $handlings->category == 'HT - Appearance' ? 'selected' : '' }}>HT -
                                                    Appearance</option>
                                                <option value="MKT - Jumlah Tidak Sesuai"
                                                    {{ $handlings->category == 'MKT - Jumlah Tidak Sesuai' ? 'selected' : '' }}>
                                                    MKT - Jumlah Tidak Sesuai</option>
                                                <option value="MKT - Dimensi Tidak Sesuai"
                                                    {{ $handlings->category == 'MKT - Dimensi Tidak Sesuai' ? 'selected' : '' }}>
                                                    MKT - Dimensi Tidak Sesuai</option>
                                                <option value="MKT - Type Material Tidak Sesuai"
                                                    {{ $handlings->category == 'MKT - Type Material Tidak Sesuai' ? 'selected' : '' }}>
                                                    MKT - Type Material Tidak Sesuai</option>
                                                <option value="MTRL - Pin Hole"
                                                    {{ $handlings->category == 'MTRL - Pin Hole' ? 'selected' : '' }}>MTRL
                                                    - Pin Hole</option>
                                                <option value="MTRL - Inklusi"
                                                    {{ $handlings->category == 'MTRL - Inklusi' ? 'selected' : '' }}>MTRL -
                                                    Inklusi</option>
                                                <option value="MTRL - Sulit Machining"
                                                    {{ $handlings->category == 'MTRL - Sulit Machining' ? 'selected' : '' }}>
                                                    MTRL - Sulit Machining</option>
                                                <option value="MTRL - Karat"
                                                    {{ $handlings->category == 'MTRL - Karat' ? 'selected' : '' }}>MTRL -
                                                    Karat</option>
                                                <option value="Others"
                                                    {{ $handlings->category == 'Others' ? 'selected' : '' }}>Others
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="category" class="col-sm-6 col-form-label">Nama Project:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input type="text" class="form-control" id="category_input"
                                                name="category" style="width: 100%; max-width: 100%;"
                                                value="{{ $handlings->category }}">
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
                                                <option value="Spectro"
                                                    {{ $handlings->jenis_test == 'Spectro' ? 'selected' : '' }}>
                                                    Spectro</option>
                                                <option value="Kekerasan"
                                                    {{ $handlings->jenis_test == 'Kekerasan' ? 'selected' : '' }}>
                                                    Kekerasan
                                                </option>
                                                <option value="Micro Structure"
                                                    {{ $handlings->jenis_test == 'Micro Structure' ? 'selected' : '' }}>
                                                    Micro Structure</option>
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
                                            <textarea class="form-control" rows="5" id="results" name="results" style="width: 100%">{{ $handlings->results }}</textarea>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="process_type" class="col-sm-5 col-form-label">Jenis Proses:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <select name="process_type" class="form-control" id="process_type"
                                                style="width: 100%">
                                                <option value="">------------------- Jenis Proses -----------------
                                                </option>
                                                <option value="Heat Treatment"
                                                    {{ $handlings->process_type == 'Heat Treatment' ? 'selected' : '' }}>
                                                    Heat
                                                    treatment</option>
                                                <option value="Cutting"
                                                    {{ $handlings->process_type == 'Cutting' ? 'selected' : '' }}>
                                                    Cutting
                                                </option>
                                                <option value="Machining"
                                                    {{ $handlings->process_type == 'Machining' ? 'selected' : '' }}>
                                                    Machining
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <br>
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="type_1" class="col-sm-5 col-form-label">Jenis:<span
                                                    style="color: red;">*</span></label>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-check">
                                                <input type="checkbox" class="form-check-input" id="type_1"
                                                    name="type_1" value="Komplain"
                                                    @if ($handlings->type_1 == 'Komplain') checked @endif>
                                                <label class="form-check-label" for="check2">Komplain</label>
                                            </div>
                                            <div class="form-check mr-2">
                                                <input type="checkbox" class="form-check-input" id="type_2"
                                                    name="type_2" value="Klaim"
                                                    @if ($handlings->type_2 == 'Klaim') checked @endif>
                                                <label class="form-check-label" for="check1">Klaim</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="image_upload" class="col-sm-8 col-form-label">Unggah Gambar: (Jika
                                                Ada)</label>
                                        </div>
                                        <div class="col-lg-6">
                                            <input class="form-control @error('image') is-invalid @enderror"
                                                type="file" id="formFile" name="images[]" accept="image/*"
                                                style="width: 100%" multiple onchange="viewImages(event);">
                                            <small id="fileError" class="text-danger" style="display:none;">Format berkas
                                                tidak sesuai. Silakan unggah gambar.</small>
                                            <!-- error message untuk title -->
                                            @error('image')
                                                <div class="alert alert-danger mt-2">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                            <div class="row mt-3">
                                                <div id="imagePreviewContainer" class="row"
                                                    style="display: flex; flex-wrap: wrap;"></div>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="row mt-3">
                                                <div class="col-lg-12">
                                                    <div id="existingImages" class="row">
                                                        @php $count = 0; @endphp
                                                        @foreach (json_decode($handlings->image) as $image)
                                                            @if ($count < 4)
                                                                <div class="col-lg-6 mb-2 d-flex justify-content-start">
                                                                    <img src="{{ asset('assets/image/' . $image) }}"
                                                                        class="img-fluid rounded mx-1" alt="image"
                                                                        style="max-width: 100%; object-fit: cover;"
                                                                        onclick="showModal('{{ asset('assets/image/' . $image) }}')">
                                                                </div>
                                                                @php $count++; @endphp
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mb-3" style="margin-top: 2%">
                                    <div class="col-sm-12 d-flex justify-content-end">
                                        <button type="submit" class="btn btn-primary mb-4 me-3">
                                            <i class="fas fa-save"></i> Simpan
                                        </button>
                                        <a href="{{ route('index') }}" class="btn btn-primary mb-4 me-2">
                                            <i class="fas fa-arrow-left"></i> Kembali
                                        </a>
                                    </div>
                                </div>
                        </form>
                        <div id="imageModal" class="modal"
                            style="display: none; position: fixed; z-index: 1; padding-top: 50px; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.9);">
                            <div
                                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80%; max-width: 500px; background-color: #fefefe; border-radius: 5px;">
                                <span class="close"
                                    style="position: absolute; top: 10px; right: 10px; color: #000; font-size: 30px; font-weight: bold; cursor: pointer;"
                                    onclick="closeModal()">&times;</span>
                                <img class="modal-content" id="modalImage"
                                    style="display: block; margin: auto; width: 70%; max-width: 70%;">
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
        <script>
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

            function updateCustomerInfo() {
                var customerIdCodeSelect = document.getElementById('customer_id_code');
                var customerNameInput = document.getElementById('customer_name');
                var customerAreaInput = document.getElementById('customer_area');
                var customerIdNameSelect = document.getElementById('customer_id_name');
                var customerIdAreaSelect = document.getElementById('customer_id_area');

                var selectedOption = customerIdCodeSelect.options[customerIdCodeSelect.selectedIndex];
                var customerName = selectedOption.getAttribute('data-name_customer');
                var customerArea = selectedOption.getAttribute('data-area');

                // Update input fields
                customerNameInput.value = customerName;
                customerAreaInput.value = customerArea;

                // Update select fields
                customerIdNameSelect.value = customerName;
                customerIdAreaSelect.value = customerArea;
            }

            // Initialize the customer info on page load
            document.addEventListener('DOMContentLoaded', function() {
                updateCustomerInfo();
                handleRequestChange();
            });

            document.getElementById('updateForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent form submission

                // Check for required fields
                let isValid = true;
                const requiredFields = ['no_wo', 'customer_id_code', 'type_id', 'qty', 'pcs'];

                requiredFields.forEach(function(fieldId) {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                if (!isValid) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Harap lengkapi semua isian yang diperlukan!',
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Data Berhasil Diubah!',
                    }).then(() => {
                        document.getElementById('updateForm').submit(); // Submit the form
                    });
                }
            });

            var imageError = document.getElementById('imageError');
            imageError.style.display = 'none';

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

            function viewImage(event) {
                var imageContainer = document.getElementById('imagePreviewContainer');
                imageContainer.innerHTML = ''; // Kosongkan kontainer gambar sebelum menambahkan gambar baru

                var files = event.target.files; // Ambil file-file yang diunggah

                // Hapus gambar yang lama
                var existingImagesContainer = document.getElementById('existingImages');
                existingImagesContainer.innerHTML = '';

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

            function hanyaAngka(evt) {
                // Mendapatkan karakter yang ditekan
                var charCode = (evt.which) ? evt.which : event.keyCode;
                // Mengizinkan hanya angka (0-9), tombol backspace, dan tombol delete
                if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 8 && charCode !== 46) {
                    // Mencegah aksi default jika karakter tidak valid
                    evt.preventDefault();
                }
            }
        </script>

    </main><!-- End #main -->
@endsection
