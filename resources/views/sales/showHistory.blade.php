@extends('layout')

@section('content')
    <main id="main" class="main">
        <style>
            /* Gaya untuk pesan catatan */
            .catatan-overdue {
                color: red;
                font-weight: bold;
            }
        </style>
        <div class="pagetitle">
            <h1>Halaman Riwayat Progres</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('index') }}">Menu Handling</a></li>
                    <li class="breadcrumb-item active">Halaman Riwayat Progres</li>
                </ol>
            </nav>
        </div><!-- End Page Title -->
        <section class="section">
            <div class="row">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Form Konfirmasi</h5>
                        <form action="{{ route('updateConfirm', $handlings->id) }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="modified_by" class="col-sm-6 col-form-label">User : <span
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
                                                maxlength="6" style="width: 100%;" value="{{ $handlings->no_wo }}" required
                                                disabled>
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
                                                style="width: 100%" onchange="updateCustomerInfo()" disabled>
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
                                            <input type="text" class="form-control" id="nama_barang"
                                                name="nama_barang" style="width: 100%; max-width: 100%;"
                                                value="{{ $handlings->nama_barang }}" disabled>
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
                                    <br>
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
                                    <br>
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
                                                name="pcs" style="max-width: 80%" value="{{ $handlings->pcs }}"
                                                required disabled>
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
                                                value="{{ $handlings->category }}" disabled>
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
                                                style="width: 100%" required disabled>
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
                                            <label for="hasil_tindak_lanjut" class="col-sm-6 col-form-label">Keterangan:
                                                (Jika ada)</label>
                                        </div>
                                        <div class="col-lg-6">
                                            <textarea class="form-control" rows="5" id="results" name="results" style="width: 100%" disabled required>{{ $handlings->results }}</textarea>
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
                                                style="width: 100%" disabled>
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
                                </div>
                                <div class="col-lg-6">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <label for="image_upload" class="col-sm-7 col-form-label">Unggah Gambar: (Jika
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
                            </div>
                        </form>
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
                                <div class="table-responsive">
                                    <table id="followUpTbl" class="datatable table">
                                        <thead>
                                            <tr>
                                                <th style="text-align: center;">NO</th>
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
                                                    <td class="text-center py-3">{{ $row->results }}</td>
                                                    <td class="text-center py-3">{{ $row->schedule }}</td>
                                                    <td class="text-center py-3">{{ $row->pic }}</td>
                                                    <td class="text-center py-3"
                                                        data-due-date="{{ $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') : '' }}">
                                                        {{ $row->due_date ? \Carbon\Carbon::parse($row->due_date)->format('Y-m-d') : '' }}
                                                    </td>
                                                    <td class="text-center py-3" style="color: red">{{ $row->notes }}</td>
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


        <script>
            //read data control
            document.addEventListener("DOMContentLoaded", function() {
                const requestDropdown = document.getElementById('notes');
                const categorySelectRow = document.getElementById('category')?.parentElement?.parentElement;
                const categoryInputRow = document.getElementById('category_input')?.parentElement?.parentElement;
                const typeMaterialRow = document.getElementById('type_id')?.parentElement?.parentElement;
                const materialProjectRow = document.getElementById('material_project')?.parentElement?.parentElement;
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
                updateDueDateNotes();
            });

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

            // Inisialisasi DataTables jika diperlukan
            $(document).ready(function() {
                // Fungsi untuk memperbarui catatan terkait due_date
                function updateDueDateNotes() {
                    const today = new Date(); // Tanggal hari ini
                    const rows = document.querySelectorAll("#followUpTbl tbody tr"); // Semua baris dalam tbody

                    if (rows.length === 0) {
                        console.warn("Tidak ada baris data dalam tabel.");
                        return; // Tidak ada data
                    }

                    // Identifikasi baris terakhir (terbaru)
                    const latestRow = rows[rows.length - 1];
                    const dueDateCell = latestRow.querySelector(
                        "td:nth-child(5)"); // Kolom "Tenggat waktu" adalah yang ke-5
                    const noteCell = latestRow.querySelector("td:nth-child(6)"); // Kolom "Catatan" adalah yang ke-6

                    if (!dueDateCell || !noteCell) {
                        console.error("Kolom Tenggat waktu atau Catatan tidak ditemukan pada baris terakhir.");
                        return;
                    }

                    // Ambil nilai due_date dari atribut data
                    const dueDateStr = dueDateCell.getAttribute('data-due-date') || dueDateCell.textContent.trim();
                    console.log(`Nilai due_date yang diambil: "${dueDateStr}"`); // Log nilai due_date yang diambil

                    // Cek jika dueDateStr kosong
                    if (!dueDateStr) {
                        console.warn("due_date kosong, tidak menampilkan catatan atau tanggal.");
                        noteCell.innerHTML = ''; // Pastikan catatan tetap kosong
                        dueDateCell.innerHTML = ''; // Kosongkan kolom due_date juga
                        return; // Jika kosong, hentikan fungsi
                    }

                    const dueDate = new Date(dueDateStr);
                    console.log(`Tanggal due_date yang diparse: ${dueDate}`); // Log tanggal yang diparse

                    if (isNaN(dueDate)) {
                        console.error(`Tanggal due_date tidak valid: ${dueDateStr}`);
                        return;
                    }

                    // Hitung selisih hari antara due_date dan hari ini
                    const utcToday = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
                    const utcDueDate = Date.UTC(dueDate.getFullYear(), dueDate.getMonth(), dueDate.getDate());
                    const diffInMillis = utcDueDate - utcToday;
                    const diffInDays = Math.floor(diffInMillis / (1000 * 60 * 60 * 24));

                    // Log detail selisih hari
                    console.log(`Tanggal Hari Ini: ${today}`);
                    console.log(`Due Date dalam UTC: ${utcDueDate}`);
                    console.log(`Tanggal Hari Ini dalam UTC: ${utcToday}`);
                    console.log(`Selisih dalam milidetik: ${diffInMillis}`);
                    console.log(`Selisih Hari: ${diffInDays}`);

                    // Bersihkan semua catatan sebelumnya
                    rows.forEach(row => {
                        const cell = row.querySelector("td:nth-child(6)");
                        if (cell) {
                            cell.innerHTML = ''; // Kosongkan isi catatan
                        }
                    });

                    // Tambahkan catatan pada baris terbaru jika due_date telah terlewati
                    if (diffInDays < 0) {
                        noteCell.innerHTML =
                            `<span class="catatan-overdue">Due date telah terlewati ${Math.abs(diffInDays)} hari</span>`;
                        console.log(
                            `Catatan ditambahkan: Due date telah terlewati ${Math.abs(diffInDays)} hari`
                        ); // Log catatan yang ditambahkan
                    } else {
                        console.log("Due date masih di masa depan, tidak ada catatan yang ditampilkan.");
                    }
                }

                // Panggil fungsi updateDueDateNotes setelah DataTables selesai menginisialisasi
                updateDueDateNotes();
            });
        </script>
    </main><!-- End #main -->
@endsection
