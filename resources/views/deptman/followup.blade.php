@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            .highlight-notes {
                background-color: red !important;
                color: white !important;
            }

            /* Gaya untuk pesan catatan */
            .catatan-overdue {
                color: red;
                font-weight: bold;
            }

            .hidden-column {
                display: none;
            }
        </style>
        <div class="pagetitle">
            <h1>Tindak Lanjut</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('submission') }}">Menu Tindak Lanjut</a></li>
                    <li class="breadcrumb-item active">Form Tindak Lanjut</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="card mb-2">
                    <h5 class="card-title">Form Follow-Up</h5>
                    <form id="formFollowUp" action="{{ route('updateFollowUp', $handlings->id) }}" method="post"
                        enctype="multipart/form-data" style="margin-top: 10px;">
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
                                            placeholder="{{ $handlings->user->name }}" disabled>
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
                                            maxlength="6" style="width: 100%; max-width: 100%;"
                                            value="{{ $handlings->no_wo }}" required disabled>
                                        <input type="hidden" id="handling_id" name="handling_id"
                                            value="{{ $handlings->id }}">
                                        <input type="hidden" id="users_id" name="users_id" value="{{ Auth::user()->id }}">
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="customer_code" class="col-sm-5 col-form-label">Kode Pelanggan:<span
                                                style="color: red;">*</span></label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select name="customer_id" id="customer_id_code" class="select2" style="width: 100%"
                                            onchange="updateCustomerInfo()" disabled>
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
                                        <select name="notes" class="form-control" id="notes" style="width: 100%;"
                                            disabled>
                                            <option value="">------------- Pilih Request ------------
                                            </option>
                                            <option value="Testing"
                                                {{ $handlings->notes == 'Testing' ? 'selected' : '' }}>Testing</option>
                                            <option value="Trial" {{ $handlings->notes == 'Trial' ? 'selected' : '' }}>
                                                Trial</option>
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
                                        <select name="type_id" id="type_id" class="select2" style="width: 100%"
                                            disabled>
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
                                        <input type="text" class="form-control" id="nama_barang" name="nama_barang"
                                            style="width: 100%; max-width: 100%;" value="{{ $handlings->nama_barang }}"
                                            disabled>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="t" class="form-label">T:</label>
                                        <input type="text" class="form-control input-sm" id="thickness"
                                            name="thickness" placeholder="Thickness" style="max-width: 80%;"
                                            value="{{ $handlings->thickness }}" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="w" class="form-label">W:</label>
                                        <input type="text" class="form-control input-sm" id="weight"
                                            name="weight" placeholder="Weight" style="max-width: 80%;"
                                            value="{{ $handlings->weight }}" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="w" class="form-label">L:</label>
                                        <input type="text" class="form-control input-sm" id="length"
                                            name="length" placeholder="Lenght" style="max-width: 80%;"
                                            value="{{ $handlings->length }}" disabled>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="w" class="form-label">OD:</label>
                                        <input type="text" class="form-control input-sm" id="outer_diameter"
                                            name="outer_diameter" placeholder="Outer Diameter" style="max-width: 80%"
                                            value="{{ $handlings->outer_diameter }}" disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="w" class="form-label">ID:</label>
                                        <input type="text" class="form-control input-sm" id="inner_diameter"
                                            name="inner_diameter" placeholder="Inner Diameter" style="max-width: 80%"
                                            value="{{ $handlings->inner_diameter }}" disabled>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="qty" class="form-label">QTY (Kg):</label>
                                        <input type="text" class="form-control input-sm" id="qty"
                                            name="qty" style="max-width: 80%;" value="{{ $handlings->qty }}"
                                            required disabled>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="pcs" class="form-label">Unit (Pcs):</label>
                                        <input type="text" class="form-control input-sm" id="pcs"
                                            name="pcs" style="max-width: 80%" value="{{ $handlings->pcs }}" required
                                            disabled>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="category" class="col-sm-5 col-form-label">Kategori (NG):<span
                                                style="color: red;">*</span></label>
                                    </div>
                                    <div class="col-lg-6">
                                        <select name="category" class="form-control" id="category" style="width: 100%"
                                            required disabled>
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
                                        <input type="text" class="form-control" id="category_input" name="category"
                                            style="width: 100%; max-width: 100%;" value="{{ $handlings->category }}"
                                            disabled>
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
                                            style="width: 100%;" disabled>
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
                                        <label for="hasil_tindak_lanjut" class="col-sm-5 col-form-label">Keterangan:
                                            (Jika ada)</label>
                                    </div>
                                    <div class="col-lg-6">
                                        <textarea class="form-control" rows="5" id="" name="" style="width: 100%" disabled required>{{ $handlings->results }}</textarea>
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
                                            style="width: 100%" required disabled>
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
                                                name="type_1" value="Komplain" disabled
                                                @if ($handlings->type_1 == 'Komplain') checked @endif>
                                            <label class="form-check-label" for="check2">Komplain</label>
                                        </div>
                                        <div class="form-check mr-2">
                                            <input type="checkbox" class="form-check-input" id="type_2"
                                                name="type_2" value="Klaim" disabled
                                                @if ($handlings->type_2 == 'Klaim') checked @endif>
                                            <label class="form-check-label" for="check1">Klaim</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="image_upload" class="col-sm-6 col-form-label">Unggah Gambar: (Jika
                                            ada)</span></label>
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
                            <div class="col-lg-6">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="schedule" class="form-label">Jadwal Kunjungan:</label>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="datetime-local" class="form-control input-sm" id="schedule"
                                            name="schedule" style="max-width: 100%;">
                                    </div>

                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="hasil_tindak_lanjut" class="form-label">Catatan Hasil:
                                            (optional)</label>
                                        <textarea class="form-control" rows="5" id="results" name="results"></textarea>
                                    </div>
                                    <div class="col-lg-6">
                                        <label for="duedate" class="form-label">Batas Akhir: (optional):</label>
                                        <input type="datetime-local" class="form-control input-sm" id="due_date"
                                            name="due_date" style="max-width: 250px;">
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="schedule_visit" class="form-label">PIC Tindak Lanjut:<span
                                                style="color: red;">*</span></label>
                                    </div>
                                    <div class="col-lg-6">
                                        <input type="text" class="form-control input-sm" id="pic"
                                            name="pic" style="max-width: 480px;" placeholder="PIC" required>
                                    </div>
                                </div>
                                <br>
                                <div class="row">
                                    <div class="col-lg-6">
                                        <label for="upload_file" class="form-label" style="margin-top: 10px;">Unggah File
                                            (optional):</label>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="input-group">
                                            <input class="form-control" type="file" id="upload_file"
                                                onchange="displayCancelBtn()" name="file" style="margin-top: 10px;"
                                                accept=".jpg,.jpeg,.png,.pdf,.xlsx,.xls,.ppt,.pptx" multiple>
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
                        </div>
                        <div class="ps-3 mb-3 mt-3 d-flex justify-content-end">
                            @if (!$handlings && !in_array($notes, ['Testing', 'Trial']))
                                <button type="button" class="btn btn-success mb-4 me-2"
                                    onclick="submitFollowUp('claim')">
                                    <i class="fas fa-save"></i> Klaim
                                </button>
                            @endif
                            <button type="button" class="btn btn-primary mb-4 me-2" onclick="submitFollowUp('save')">
                                <i class="fas fa-save"></i> Save
                            </button>
                            <button type="button" class="btn btn-success mb-4 me-4" onclick="submitFollowUp('finish')">
                                <i class="fas fa-save"></i> Finish
                            </button>
                            <button type="button" class="btn btn-primary mb-4 me-4" onclick="goToSubmission()">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card mb-2">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                History Progres
                            </button>

                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <a href="{{ route('showFollowUp', ['id' => $handlings]) }}"
                                    class="btn btn-primary mb-4 me-2">
                                    <i class="fa fa-refresh"></i> Refresh Tabel
                                </a>
                                <div class="table-responsive">
                                    <table id="followUpTbl" class="datatable table">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center;">NO</th>
                                                <th style="text-align: center;" hidden>ID</th>
                                                <th style="text-align: center;">Hasil dan Tindak Lanjut</th>
                                                <th style="text-align: center;">Jadwal Kunjungan</th>
                                                <th style="text-align: center;">PIC</th>
                                                <th style="text-align: center;">Tenggat waktu</th>
                                                <th style="text-align: center;">Catatan</th>
                                                <th style="text-align: center;">Jenis 1</th>
                                                <th style="text-align: center;">Jenis 2</th>
                                                <th style="text-align: center;">Unggahan (File)</th>
                                                <th style="text-align: center;">Pembaruan Terakhir</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($data as $row)
                                                <tr>
                                                    <td class="text-center py-3">{{ $loop->iteration }}</td>
                                                    <td class="text-center py-3" hidden>{{ $row->id }}</td>
                                                    <td class="text-center py-3">{{ $row->results }}</td>
                                                    <td class="text-center py-3">{{ $row->schedule }}</td>
                                                    <td class="text-center py-3">{{ $row->pic }}</td>

                                                    <td class="text-center py-3"
                                                        data-due-date="{{ $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') : '' }}">
                                                        {{ $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') : '' }}
                                                    </td>
                                                    <td class="text-center py-3" style="color: red">
                                                        {{ $row->notes }}
                                                    </td>
                                                    <!-- Kolom Lainnya -->
                                                    <td class="text-center py-3">
                                                        @if ($row->history_type == 1)
                                                            Komplain
                                                        @endif
                                                    </td>
                                                    <td class="text-center py-3">
                                                        @if ($row->history_type == 1)
                                                            Klaim
                                                        @endif
                                                    </td>
                                                    <td class="text-center pt-3">
                                                        @if (pathinfo($row->file, PATHINFO_EXTENSION) == 'pdf')
                                                            <a href="{{ asset('assets/image/' . $row->file) }}"
                                                                target="_blank">
                                                                <i class="fas fa-file-pdf fs-4"></i>
                                                            </a>
                                                        @elseif (in_array(pathinfo($row->file, PATHINFO_EXTENSION), ['ppt', 'pptx']))
                                                            <a href="{{ asset('assets/image/' . $row->file) }}"
                                                                download="{{ $row->file_name }}">
                                                                <i class="fas fa-file-powerpoint fs-4"></i>
                                                            </a>
                                                        @elseif (in_array(pathinfo($row->file, PATHINFO_EXTENSION), ['xlsx', 'xls']))
                                                            <a href="{{ asset('assets/image/' . $row->file) }}"
                                                                download="{{ $row->file_name }}">
                                                                <i class="fas fa-file-excel fs-4"></i>
                                                            </a>
                                                        @elseif (in_array(pathinfo($row->file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png']))
                                                            <a href="{{ asset('assets/image/' . $row->file) }}"
                                                                download="{{ $row->file_name }}">
                                                                <img src="{{ asset('assets/image/' . $row->file) }}"
                                                                    class="img-fluid rounded"
                                                                    style="max-width: 100%; height: auto;">
                                                            </a>
                                                        @else
                                                            <p>File tidak didukung</p>
                                                        @endif
                                                    </td>
                                                    <td class="text-center py-3">{{ $row->created_at }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img id="modalImage" class="img-fluid" src="" alt="Image Preview"
                                style="max-width: 100%; max-height: 80vh;">
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
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
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

            //read data control
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
                        if (prosesTypeSelect) prosesTypeSelect.disabled = true;
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
                        if (prosesTypeSelect) prosesTypeSelect.disabled = true;
                        if (prosesTypeCheckbox1) prosesTypeCheckbox1.disabled = true;
                        if (prosesTypeCheckbox2) prosesTypeCheckbox2.disabled = true;
                    }
                }

                updateUIBasedOnRequest(requestDropdown.value);
                requestDropdown.addEventListener('change', function() {
                    updateUIBasedOnRequest(this.value);
                });
            });

            // Initialize the customer info on page load
            document.addEventListener('DOMContentLoaded', function() {
                updateCustomerInfo();
                handleRequestChange();
                updateDueDateNotes();
            });

            function submitFollowUp(action) {
                var formFollowUp = document.getElementById('formFollowUp');
                var fileInput = document.getElementById('upload_file');
                var maxFileSize = 15 * 1024 * 1024; // 15 MB

                // Validasi berdasarkan tindakan
                if (action === 'save' || action === 'claim') {
                    if (!document.getElementById('pic').value.trim() || !document.getElementById('results').value.trim() ||
                        (action === 'save' && fileInput.files.length === 0)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Silakan isi PIC, Catatan Hasil, dan Unggah File sebelum menyimpan!',
                        });
                        return;
                    }

                    // Validasi ukuran file maksimal 15 MB untuk tindakan save dan claim
                    if (fileInput.files.length > 0) {
                        var file = fileInput.files[0];
                        if (file.size > maxFileSize) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: 'Ukuran file harus lebih kecil dari 15 MB!',
                            });
                            return;
                        }
                    }
                } else if (action === 'finish') {
                    if (!document.getElementById('pic').value.trim() || !document.getElementById('results').value.trim() ||
                        fileInput.files.length === 0) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Silakan isi PIC, Catatan, dan Unggah File sebelum menyelesaikan!',
                        });
                        return;
                    }

                    // Validasi ukuran file maksimal 15 MB untuk tindakan finish
                    var file = fileInput.files[0];
                    if (file.size > maxFileSize) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Ukuran file harus lebih kecil dari 15 MB!',
                        });
                        return;
                    }
                }

                // Tambahkan action ke form data
                var formData = new FormData(formFollowUp);
                formData.append('action', action);

                // Kirim data ke server menggunakan AJAX
                $.ajax({
                    url: formFollowUp.action,
                    method: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        // Tanggapan sukses dari server
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: response.message,
                        }).then((result) => {
                            if (response.redirect) {
                                window.location.href = response.redirect;
                            } else if (response.refresh) {
                                location.reload();
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        // Kesalahan dari server
                        var errorMessage = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message :
                            'Terjadi kesalahan saat menyimpan data!';
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: errorMessage,
                        });
                    }
                });
            }

            //back page
            var submissionUrl = '{{ route('submission') }}';

            function goToSubmission() {
                // Redirect ke URL route
                window.location.href = submissionUrl;
            }
            //end

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

            $(document).ready(function() {
                var latestDueDate = null; // Variabel untuk menyimpan due_date terbaru
                var latestRow = null; // Variabel untuk menyimpan baris dengan due_date terbaru

                // Fungsi untuk mendapatkan tanggal dalam WIB
                function getWIBDate(dateString) {
                    var date = new Date(dateString);
                    // WIB adalah UTC+7
                    var utc = date.getTime() + (date.getTimezoneOffset() * 60000);
                    var wib = new Date(utc + (7 * 3600000));
                    return wib;
                }

                // Cari ID yang memiliki due_date terbaru
                $('td[data-due-date]').each(function() {
                    var dueDate = $(this).data('due-date');
                    var dueDateObj = getWIBDate(dueDate);

                    // Jika due_date valid dan lebih baru dari yang sebelumnya ditemukan
                    if (dueDate && (latestDueDate === null || dueDateObj > latestDueDate)) {
                        latestDueDate = dueDateObj;
                        latestRow = $(this).closest('tr'); // Simpan baris dengan due_date terbaru
                    }
                });

                // Hanya lanjutkan jika ada due_date terbaru yang ditemukan
                if (latestRow && latestDueDate) {
                    var today = new Date();

                    // Konversi tanggal hari ini ke WIB
                    var utcToday = today.getTime() + (today.getTimezoneOffset() * 60000);
                    var wibToday = new Date(utcToday + (7 * 3600000));

                    // Hitung selisih waktu antara hari ini dan due_date terbaru
                    var diffTime = wibToday - latestDueDate;
                    var diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

                    console.log("Latest Due Date:", latestDueDate);
                    console.log("WIB Today:", wibToday);
                    console.log("Diff Time:", diffTime);
                    console.log("Diff Days:", diffDays);

                    // Tampilkan catatan 'Telat Melewati Tenggat Waktu dalam ... Hari'
                    var modalText = "Telat Melewati Tenggat Waktu dalam " + diffDays + " Hari";
                    $('#modalNotes').text(modalText);

                    // Ambil catatan dari baris dengan due_date terbaru
                    var notesColumn = latestRow.find('td').eq(5); // Asumsi kolom ke-6 adalah 'Catatan'
                    var currentNotes = notesColumn.text().trim();

                    // Jika due_date telah melewati hari ini dan belum ada catatan "Telat", tambahkan catatan baru
                    if (latestDueDate < wibToday && !currentNotes.includes('Telat')) {
                        // Menampilkan modal
                        $('#dueDateModal').modal('show');

                        // Kirim data ke server menggunakan Ajax untuk update catatan
                        var rowId = latestRow.find('td').eq(1).text(); // Ambil ID dari kolom kedua
                        $.ajax({
                            url: '{{ route('schedule.updateNotes') }}', // Pastikan route ini benar
                            type: 'POST',
                            data: {
                                id: rowId,
                                notes: modalText,
                                _token: '{{ csrf_token() }}' // Jangan lupa CSRF token jika diperlukan
                            },
                            success: function(response) {
                                console.log('Data berhasil dikirim:', response);
                            },
                            error: function(xhr) {
                                console.log('Terjadi kesalahan:', xhr.responseText);
                            }
                        });
                    } else {
                        // Jika catatan sudah ada, tampilkan catatan yang ada
                        $('#modalNotes').text(currentNotes);
                        $('#dueDateModal').modal('show');
                    }
                }
            });
        </script>
    </main><!-- End #main -->
@endsection
