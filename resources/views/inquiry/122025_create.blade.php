@extends('layout')

@section('content')

    <main id="main" class="main">

        <style>
            .card-title1 {
                text-align: center;
                width: 100%;
            }

            .swal2-popup {
                font-size: 0.6rem;
                width: 300px;
            }

            .modal-content {
                font-family: 'Cambria', serif;
                width: 400px;
                max-width: 90%;
                color: #000000;
                background-color: rgb(114, 114, 114);
            }

            .modal-title {
                font-family: 'Cambria', serif;
                font-weight: bold;
                font-size: 20px;
                color: #ecf000;
            }

            .input-group {
                margin-bottom: 15px;
                /* Jarak antar input */
            }

            .input-group label {
                margin-bottom: 5px;
                display: block;
                /* Memisahkan label dari input */
                font-weight: bold;
                /* Mempertegas label */
            }

            .input-group input,
            .input-group select {
                width: 100%;
                /* Lebar penuh untuk semua input */
                padding: 10px;
                /* Padding seragam */
                border: 1px solid #ccc;
                /* Border seragam */
                border-radius: 4px;
                /* Sudut border seragam */
                box-sizing: border-box;
                /* Memastikan padding masuk ke dalam lebar */
                font-size: 14px;
                /* Ukuran font seragam */
            }

            .btn {
                padding: 8px;
                /* Sesuaikan ukuran tombol */
                margin-left: 5px;
                /* Jarak antara input dan tombol */
            }

            /* Dropdown Input Styling */
            .searchable-dropdown {
                position: relative;
                margin: 10px 0;
            }

            #search_customer {
                width: 100%;
                padding: 8px;
                /* Mengurangi padding */
                border: 1px solid #ccc;
                border-radius: 5px;
                outline: none;
                box-sizing: border-box;
                /* Pastikan padding dihitung dalam lebar */
                margin: 0;
                /* Pastikan margin adalah 0 */
            }

            /* Dropdown Items Styling */
            .dropdown-items {
                /* position: absolute; */
                top: 100%;
                left: 0;
                right: 0;
                z-index: 1000;
                background-color: white;
                border: 1px solid #ccc;
                border-radius: 5px;
                max-height: 200px;
                overflow-y: auto;
                display: none;
                padding: 10px;
            }

            /* Style for each item */
            .dropdown-item {
                padding: 10px;
                cursor: pointer;
                white-space: nowrap;
            }

            .dropdown-item:hover {
                background-color: #f0f0f0;
            }

            /* Style for selected customers */
            .selected-customer {
                display: inline-block;
                margin: 5px;
                padding: 5px 8px;
                background-color: #e0e0e0;
                border-radius: 5px;
            }

            .font-sii {
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .table-1 {
                margin: 5px auto;
                /* Pusatkan tabel */
                padding: 1rem;
                /* Padding di sekeliling tabel */
                background-color: #f7f7f7;
                /* Warna latar belakang */
                border-radius: 8px;
                /* Sudut membulat */
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
                /* Bayangan untuk efek kedalaman */
            }

            .table-1 th {
                background-color: rgb(97, 97, 97);
                /* Warna latar belakang */
                color: #ffffff;
                font-size: 10pt;
                /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); */
                /* Bayangan untuk efek kedalaman */
                text-align: center;
                font-family: 'Cambria', serif;
            }

            .table-1 td {
                font-size: 8pt;
                font-family: 'Cambria', serif;
            }

            .datatable-table>tbody>tr>td {
                text-align: center;
            }


            .dataTable-pagination {
                padding: 0.25rem;
                /* Padding lebih kecil untuk pagination */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
            }

            .dataTable-pagination .dataTable-info,
            .dataTable-pagination .dataTable-pagination-button {
                margin: 0;
                /* Hapus margin untuk elemen info dan tombol pagination */
            }

            .datatable-dropdown {
                font-family: 'Cambria', serif;
                font-size: 0.8rem;
            }

            .datatable-selector {
                padding: 0.2rem;
                /* Padding lebih kecil pada dropdown pagination */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
                border-radius: 4px;
                /* Sudut membulat */
                border: 1px solid #ddd;
                /* Border untuk dropdown */
                font-family: 'Cambria', serif;
            }

            input[type="search"] {
                width: 100%;
                /* Lebar input pencarian */
                padding: 0.5rem;
                /* Padding untuk input */
                border: 1px solid #ddd;
                /* Border untuk input */
                border-radius: 10px;
                /* Sudut membulat untuk input */
                margin-bottom: 0.5rem;
                /* Jarak antara input dan tabel */
                transition: border-color 0.3s;
                /* Transisi saat berinteraksi */
                font-family: 'Cambria', serif;
            }

            input[type="search"] {
                padding: 0.3rem;
                /* Padding lebih kecil untuk input pencarian */
                font-size: 0.8rem;
                /* Ukuran font lebih kecil */
                border-radius: 10px;
                /* Sudut membulat */
                border: 1px solid #ddd;
                /* Border untuk input */
            }

            .dataTable-search {
                margin-bottom: 0.5rem;
                /* Jarak antara input pencarian dan tabel */
                font-family: 'Cambria', serif;
            }

            .btn-custom-draft {
                background-color: #6c757d;
                /* atau warna lain yang Anda inginkan */
                color: white;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-open {
                background-color: #00db37;
                /* atau warna lain */
                color: rgb(0, 0, 0);
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-approve-dept {
                background-color: #00cfeb;
                /* Warna kuning bisa jadi untuk approve ka.dept */
                color: black;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-approve-dept:hover {
                background-color: #14b4c9;
                color: #ffffff;
            }

            .btn-custom-approve-sie {
                background-color: #00ffff;
                /* Warna biru bisa untuk approve ka.sie */
                color: rgb(0, 0, 0);
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-in-progress {
                background-color: #fbff07;
                /* Warna kuning tua untuk on progress */
                color: rgb(0, 0, 0);
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-finished {
                background-color: #00346b;
                /* Warna biru untuk finished */
                color: white;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-rejected {
                background-color: #dc3545;
                /* Merah untuk rejected */
                color: white;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-inventory {
                background-color: #00d39e;
                /* Merah untuk show form */
                color: #000000;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-inventory:hover {
                background-color: #00ffbf;
                /* Merah untuk show form */
            }

            .btn-custom-form {
                background-color: #4df300;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-show {
                background-color: #f300a2;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-edit {
                background-color: #3564ff;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-view {
                background-color: #fffb00;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-delete {
                background-color: #ff0000;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-form:hover {
                background-color: #34a500;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-show:hover {
                background-color: #b10076;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-edit:hover {
                background-color: #0026a3;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-view:hover {
                background-color: #ffd000;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-delete:hover {
                background-color: #be0000;
                /* Merah untuk show form */
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-stts {
                text-align: center;
            }

            .btn-add {
                font-size: 8pt;
                background-color: #0033da;
                color: #ffffff;
            }

            .btn-add:hover {
                background-color: #0026a3;
                color: #fbff00;
            }

            .eempty {
                font-family: 'Cambria', serif;
                border: 1px solid #220000;
                border-radius: 10px;
                color: #be0000;
                font-style: italic;
            }

            .disabledform {
                font-size: 8pt;
                color: red;
            }
        </style>
        {{-- <div class="pagetitle">
        <h1>Halaman Inquiry</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item active">Menu Inquiry Sales</li>
            </ol>
        </nav>
    </div> --}}
        <section class="">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-start mt-4 mb-3">
                        <button class="btn btn-add btn-sm me-2" data-bs-toggle="modal" data-bs-target="#inquiryModal">
                            <i class="bx bx-plus-medical fw-bold"> Add Local</i>
                        </button>
                        <a href="{{ route('export.inquiry') }}" class="bx bx-download btn btn-success btn-sm me-2"> Export Inquiry</a>
                        {{-- <h5 class="card-title1 font-sii text-center">Data Inquiry Sales View</h5> --}}
                    </div>

                    <!-- Table with stripped rows -->
                    @if ($inquiries->isEmpty())
                        <div class="eempty">
                            <p class="ps-3 mt-3">--- Not Found Inquiry Sales ---</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-1" id="inquiryTable">
                                <thead>
                                    <tr>
                                        <th scope="col">No</th>
                                        <th scope="col">Create By</th>
                                        <th scope="col">Reference</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Supplier</th>
                                        <th scope="col">Customer</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Ship to</th>
                                        <th scope="col">Last Update</th>
                                        <th scope="col">Est. Date</th>
                                        <th scope="col">Actions</th>
                                        {{-- <th scope="col">is Active</th> --}}
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($inquiries as $inquiry)
                                        <tr>
                                            <th scope="row">{{ $loop->iteration }}</th>
                                            <td>{{ $inquiry->create_by }}</td>
                                            <td>{{ $inquiry->kode_inquiry }}</td>
                                            <td>{{ $inquiry->loc_imp }}</td>
                                            <td>{{ $inquiry->supplier }}</td>
                                            <td>{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}</td>
                                            {{-- <td>{{ $inquiry->note }}</td> --}}
                                            @php
                                                $statusDescriptions = [
                                                    1 => 'Draft',
                                                    2 => 'Open',
                                                    3 => 'Approve Ka.Dept',
                                                    4 => 'Approve Ka.Sie',
                                                    5 => 'On Progress',
                                                    6 => 'Finished',
                                                    7 => 'Rejected',
                                                    8 => 'Approve Inventory',
                                                    9 => 'Confirm Purchasing',
                                                ];

                                                // Mendefinisikan kelas tombol berdasarkan status
                                                $buttonClasses = [
                                                    1 => 'btn-secondary', // Draft
                                                    2 => 'btn-success', // Open
                                                    3 => 'btn-danger', // Approve Ka.Dept
                                                    4 => 'btn-info', // Approve Ka.Sie
                                                    5 => 'btn-warning', // On Progress
                                                    6 => 'btn-primary', // Finished
                                                    7 => 'btn-danger', // Rejected
                                                    8 => 'btn-danger', // Approve Inventory
                                                    9 => 'btn-warning', // Confirm Purchasing
                                                ];
                                            @endphp

                                            <td class="btn-stts">
                                                <button
                                                    class="btn btn-sm 
                                                        {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }} 
                                                        {{ $inquiry->status == 1 ? 'btn-custom-draft' : '' }}
                                                        {{ $inquiry->status == 2 ? 'btn-custom-open' : '' }}
                                                        {{ $inquiry->status == 3 ? 'btn-custom-approve-dept' : '' }}
                                                        {{ $inquiry->status == 4 ? 'btn-custom-approve-sie' : '' }}
                                                        {{ $inquiry->status == 5 ? 'btn-custom-in-progress' : '' }}
                                                        {{ $inquiry->status == 6 ? 'btn-custom-finished' : '' }}
                                                        {{ $inquiry->status == 7 ? 'btn-custom-rejected' : '' }}
                                                        {{ $inquiry->status == 8 ? 'btn-custom-inventory' : '' }}
                                                        {{ $inquiry->status == 9 ? 'btn-custom-confirm-purchasing' : '' }}">
                                                    {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                </button>
                                            </td>
                                            <td>
                                                @if ($inquiry->details->isNotEmpty())
                                                    @php
                                                        // Ambil semua nilai ship dari detail
                                                        $ships = $inquiry->details->pluck('ship')->unique(); // Mengambil nilai unik
                                                    @endphp

                                                    @if ($ships->count() === 1)
                                                        <p>{{ $ships->first() }}</p>
                                                    @else
                                                        @foreach ($ships as $ship)
                                                            <p>{{ $ship }}</p>
                                                        @endforeach
                                                    @endif
                                                @else
                                                    --- No Shipping Options ---
                                                @endif
                                            </td>

                                            {{-- <td>
                                                @if ($inquiry->attach_file)
                                                    <a href="{{ asset('assets/files/' . $inquiry->attach_file) }}"
                                                        target="_blank">
                                                        <i class="fas fa-file-alt"></i>
                                                    </a>
                                                @else
                                                    <i class="fas fa-times"></i> No File
                                                @endif
                                            </td> --}}

                                            <td>
                                                @php
                                                    // Ambil update progress terbaru
                                                    $progress = App\Models\TrxDboProgPurchase::where(
                                                        'inquiry_id',
                                                        $inquiry->id,
                                                    )
                                                        ->latest()
                                                        ->first();

                                                    // Jika inquiry belum pernah memiliki progress, tampilkan "No updates yet"
                                                    $lastUpdateMessage =
                                                        $progress && $progress->description !== 'No updates yet'
                                                            ? $progress->description
                                                            : 'No updates yet';
                                                @endphp
                                                {{ $lastUpdateMessage }}
                                            </td>

                                            <td>{{ $inquiry->est_date }}</td>

                                            {{-- <td>
                                    @if ($inquiry->status == 0)
                                    <button type="button" class="btn btn-danger" title="Data tidak aktif">
                                        <i class="bi bi-exclamation-octagon"></i>
                                    </button>
                                    @endif
                                </td> --}}

                                            <td>
                                                @if ($inquiry->status == 1)
                                                    <a class="btn btn-custom-edit m-1 btn-sm" title="Edit">
                                                        <i class="bi bi-pencil-fill"
                                                            onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
                                                    </a>
                                                @endif
                                                @if ($inquiry->status == 1)
                                                    <a class="btn btn-custom-form m-1 btn-sm"
                                                        href="{{ route('formulirInquiry', ['id' => $inquiry->id]) }}"
                                                        title="Formulir Inquiry">
                                                        <i class="bi bi-file-earmark-arrow-up-fill"></i>
                                                    </a>
                                                @endif

                                                <a class="btn btn-custom-view m-1 btn-sm" title="View Form"
                                                    href="{{ route('showFormSS', $inquiry->id) }}">
                                                    <i class="bi bi-eye-fill"></i>
                                                </a>
                                                {{-- <a href="#" class="btn btn-info m-1 btn-sm"
                                                    onclick="showProgressModal1({{ $inquiry->id }}); return false;"
                                                    title="Show Detail Inquiry">
                                                    <i class="bi bi-info-square-fill"></i>
                                                </a> --}}
                                                @if ($inquiry->status == 1)
                                                    <a class="btn btn-custom-delete m-1 btn-sm" title="Delete">
                                                        <i class="bi bi-trash-fill"
                                                            onclick="deleteInquiry({{ $inquiry->id }})"></i>
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
                <!-- End Table with stripped rows -->

                <!-- Modal Add-->
                <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="inquiryModalLabel">[Local] - Form Inquiry</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form action="{{ route('storeinquiry') }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="mb-3">
                                        <label for="loc_imp" class="form-label fw-bold">Category <span
                                                class="disabledform">
                                                [*Form Disabled]</span></label>
                                        <input type="text" class="form-control" id="loc_imp" name="loc_imp"
                                            value="Local" disabled required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="jenis_inquiry" class="form-label fw-bold">Jenis Inquiry</label>
                                        <select class="form-select" id="jenis_inquiry" name="jenis_inquiry" required>
                                            <option value="RO">Order Local</option>
                                            {{-- <option value="SPOR">Spesial Order</option> --}}
                                        </select>
                                    </div>
                                    {{-- <div class="mb-3">
                                        <label for="id_customer" class="form-label">Order from</label>
                                        <div class="searchable-dropdown">
                                            <input type="text" id="search_customer" placeholder="Select Customer">
                                            <div class="dropdown-items" id="customer_list">
                                                @foreach ($customers as $customer)
                                                    <div data-value="{{ $customer->id }}">{{ $customer->name_customer }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input type="hidden" id="id_customer" name="id_customer" required>
                                    </div> --}}

                                    <div class="mb-3">
                                        <label for="id_customer" class="form-label fw-bold">Order from</label>
                                        <div class="searchable-dropdown">
                                            <input type="text" id="search_customer">
                                            <div class="dropdown-items" id="customer_list" style="display: none;">
                                                @foreach ($customers as $customer)
                                                    <div data-value="{{ $customer->id }}">{{ $customer->name_customer }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input type="hidden" id="id_customer" name="id_customer" required>
                                        <div id="selected_customers_list"></div>
                                    </div>
                                    {{-- <div class="mb-3">
                                        <label for="supplier" class="form-label">Supplier</label>
                                        <select class="form-select" id="supplier" name="supplier" required>
                                            <option value="Daido Steel">Daido Steel</option>
                                            <option value="Surya Metalindo">Surya Metalindo</option>
                                        </select>
                                    </div> --}}
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Modal -->

                <!-- Start Edit Inquiry Modal -->
                <div class="modal fade" id="editInquiryModal" tabindex="-1" aria-labelledby="editInquiryModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editInquiryModalLabel">[Local] - Edit Form Inquiry</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editInquiryForm"
                                    action="{{ route('updateinquiry', ['id' => $inquiries->first()->id ?? 0]) }}"
                                    method="POST" enctype="multipart/form-data">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" id="editInquiryId" name="inquiry_id">
                                    <div class="mb-3">
                                        <label for="editloc_imp" class="form-label">Category</label>
                                        <input type="text" class="form-control" id="editloc_imp" name="loc_imp"
                                            value="Order Local" disabled required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editjenis_inquiry" class="form-label">Jenis Inquiry</label>
                                        <select class="form-select" id="editjenis_inquiry" name="jenis_inquiry" required
                                            disabled>
                                            <option value="RO">Reguler Order</option>
                                            {{-- <option value="SPOR">Spesial Order</option> --}}
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="id_customer" class="form-label fw-bold">Order from</label>
                                        <div class="searchable-dropdown">
                                            <input type="text" id="search_edit_customer" class="form-control"
                                                placeholder="Search customer...">
                                            <div id="edit_customer_list" class="dropdown-items"
                                                style="display: none; max-height: 200px; overflow-y: auto;">
                                                @foreach ($customers as $customer)
                                                    <div data-value="{{ $customer->id }}">{{ $customer->name_customer }}
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        <input type="hidden" id="edit_id_customer" name="id_customer" required>
                                        <div id="selected_customers_list"></div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary"
                                            data-bs-dismiss="modal">Close</button>
                                        <button type="submit" class="btn btn-primary">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Edit Inquiry Modal -->
            </div>
            </div>
        </section>

        <!-- Modal for Viewing Progress History -->
        <div class="modal fade" id="progressHistoryModal1" tabindex="-1" aria-labelledby="progressHistoryModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="progressHistoryModalLabel">History Progress</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="table-responsive">
                            <table class="table table-1" id="historyTable">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Progress Description</th>
                                    </tr>
                                </thead>
                                <tbody id="historyBody">
                                    <!-- Data akan ditambahkan melalui AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        {{-- excel --}}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>

        <!-- SimpleDataTables JS -->
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
            document.addEventListener('DOMContentLoaded', function() {
                const locImpSelect = document.getElementById('loc_imp'); // Dropdown, tetapi hanya untuk Local
                const searchInput = document.getElementById('search_customer');
                const customerList = document.getElementById('customer_list');
                const hiddenInput = document.getElementById('id_customer');
                const selectedCustomersList = document.getElementById('selected_customers_list');

                // Tampilkan dropdown dan menangani perubahan kategori
                locImpSelect.value = 'Local'; // Set default ke Local
                customerList.style.display = 'block'; // Selalu tampilkan daftar customer

                // Mencari customer berdasarkan input
                searchInput.addEventListener('input', function() {
                    const filter = searchInput.value.toLowerCase();
                    const items = customerList.getElementsByTagName('div');

                    for (let i = 0; i < items.length; i++) {
                        const txtValue = items[i].textContent || items[i].innerText;
                        items[i].style.display = txtValue.toLowerCase().indexOf(filter) > -1 ? '' : 'none';
                    }
                });

                // Menangani pemilihan customer
                customerList.addEventListener('click', function(e) {
                    if (e.target && e.target.matches('div[data-value]')) {
                        const selectedValue = e.target.getAttribute('data-value');
                        const selectedText = e.target.textContent;

                        // Untuk Local, set satu customer dan tidak mengizinkan pemilihan lebih
                        searchInput.value = selectedText; // Tampilkan nama customer
                        hiddenInput.value = selectedValue; // Set ID customer
                        customerList.style.display = 'none'; // Sembunyikan daftar
                        selectedCustomersList.innerHTML = ''; // Kosongkan daftar, hanya satu yang terpilih
                        selectedCustomersList.innerHTML = '<span class="selected-customer">' + selectedText +
                            '</span>'; // Tampilkan customer terpilih
                    }
                });

                // Menangani fokus input
                searchInput.addEventListener('focus', function() {
                    customerList.style.display = 'block'; // Tampilkan dropdown saat mencari
                });

                // Menyembunyikan dropdown jika klik di luar
                document.addEventListener('click', function(e) {
                    if (!e.target.closest('.searchable-dropdown')) {
                        customerList.style.display = 'none'; // Sembunyikan dropdown jika klik di luar
                    }
                });
            });

            // function openEditInquiryModal(id) {
            //     console.log('Opening modal for inquiry ID: ' + id);
            //     $.ajax({
            //         url: '{{ route('editInquiry', ['id' => ':id']) }}'.replace(':id', id),
            //         type: 'GET',
            //         success: function(response) {
            //             console.log('Response:', response);

            //             // Populate the form with the received data
            //             $('#editjenis_inquiry').val(response.jenis_inquiry);
            //             $('#search_edit_customer').val(response.customer_name);
            //             $('#edit_id_customer').val(response.id_customer);
            //             $('#editloc_imp').val(response.loc_imp);
            //             // $('#editsupplier').val(response.supplier);
            //             $('#editInquiryId').val(response.id);

            //             // Populate the customer dropdown
            //             const editDropdown = $('#edit_customer_list');
            //             editDropdown.empty(); // Clear existing options
            //             response.customers.forEach(customer => {
            //                 const item = $('<div>').addClass('dropdown-item').attr('data-value',
            //                     customer
            //                     .id).text(customer.name_customer);
            //                 item.on('click', function() {
            //                     $('#search_edit_customer').val(customer.name_customer);
            //                     $('#edit_id_customer').val(customer.id);
            //                     editDropdown.hide();
            //                 });
            //                 editDropdown.append(item);
            //             });

            //             // Set the current customer name in the search input
            //             $('#search_edit_customer').val(response.customer_name);

            //             // Show the modal
            //             $('#editInquiryModal').modal('show');

            //             // Update inquiry on save
            //             $('#editInquiryForm').off('submit').on('submit', function(e) {
            //                 e.preventDefault(); // Mencegah form dari pengiriman default
            //                 const inquiryId = $('#editInquiryId').val();
            //                 const updateData = {
            //                     jenis_inquiry: $('#editjenis_inquiry').val(),
            //                     id_customer: $('#edit_id_customer').val(),
            //                     loc_imp: $('#editloc_imp').val(),
            //                     // supplier: $('#editsupplier').val(),
            //                     _token: '{{ csrf_token() }}', // Sertakan token CSRF untuk keamanan
            //                     _method: 'PUT', // Menggunakan metode PUT untuk update
            //                 };

            //                 $.ajax({
            //                     url: '{{ route('updateinquiry', ['id' => ':id']) }}'
            //                         .replace(':id',
            //                             inquiryId),
            //                     type: 'POST', // Gunakan POST karena kita menipu metode
            //                     data: updateData,
            //                     success: function(response) {
            //                         // SweetAlert notification
            //                         Swal.fire({
            //                             title: 'Success!',
            //                             text: 'Inquiry updated successfully.',
            //                             icon: 'success',
            //                             confirmButtonText: 'OK'
            //                         }).then(() => {
            //                             $('#editInquiryModal').modal(
            //                                 'hide'); // Tutup modal
            //                             location.reload(); // Reload halaman
            //                         });
            //                     },
            //                     error: function(xhr) {
            //                         console.log(xhr.responseText);
            //                     }
            //                 });
            //             });
            //         },
            //         error: function(xhr) {
            //             console.log(xhr.responseText);
            //         }
            //     });
            // }

            function openEditInquiryModal(id) {
                console.log('Opening modal for inquiry ID: ' + id);
                $.ajax({
                    url: '{{ route('editInquiry', ['id' => ':id']) }}'.replace(':id', id),
                    type: 'GET',
                    success: function(response) {
                        console.log('Response:', response);
                        // Populate the form with the received data
                        $('#editjenis_inquiry').val(response.jenis_inquiry);
                        $('#search_edit_customer').val(response.customer_name);
                        $('#edit_id_customer').val(response.id_customer);
                        $('#editloc_imp').val(response.loc_imp);
                        $('#editInquiryId').val(response.id);

                        // Tampilkan dropdown dengan customer yang diambil dari response
                        $('#edit_customer_list').empty(); // Clear existing options
                        response.customers.forEach(customer => {
                            const item = $('<div>').addClass('dropdown-item').attr('data-value', customer
                                .id).text(customer.name_customer);
                            item.on('click', function() {
                                $('#search_edit_customer').val(customer.name_customer);
                                $('#edit_id_customer').val(customer.id);
                                $('#edit_customer_list')
                                    .hide(); // Sembunyikan dropdown setelah memilih customer
                            });
                            $('#edit_customer_list').append(item);
                        });

                        // Event untuk mencari customer
                        $('#search_edit_customer').on('input', function() {
                            const filter = $(this).val().toLowerCase();
                            $('#edit_customer_list').children('div').each(function() {
                                const text = $(this).text().toLowerCase();
                                $(this).toggle(text.indexOf(filter) > -1);
                            });
                            $('#edit_customer_list').toggle($('#edit_customer_list').children('div:visible')
                                .length > 0);
                        });

                        $('#search_edit_customer').on('focus', function() {
                            $('#edit_customer_list').show(); // Tampilkan dropdown saat fokus
                        });

                        // Sembunyikan dropdown jika klik di luar
                        $(document).on('click', function(e) {
                            if (!$(e.target).closest('.searchable-dropdown').length) {
                                $('#edit_customer_list').hide(); // Sembunyikan dropdown jika klik di luar
                            }
                        });

                        // Show the modal
                        $('#editInquiryModal').modal('show');

                        // Update inquiry on save
                        $('#editInquiryForm').off('submit').on('submit', function(e) {
                            e.preventDefault(); // Mencegah form dari pengiriman default
                            const inquiryId = $('#editInquiryId').val();
                            const updateData = {
                                jenis_inquiry: $('#editjenis_inquiry').val(),
                                id_customer: $('#edit_id_customer').val(),
                                loc_imp: $('#editloc_imp').val(),
                                _token: '{{ csrf_token() }}', // Sertakan token CSRF untuk keamanan
                                _method: 'PUT', // Menggunakan metode PUT untuk update
                            };
                            $.ajax({
                                url: '{{ route('updateinquiry', ['id' => ':id']) }}'.replace(':id',
                                    inquiryId),
                                type: 'POST', // Gunakan POST karena kita menipu metode
                                data: updateData,
                                success: function(response) {
                                    // SweetAlert notification
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Inquiry updated successfully.',
                                        icon: 'success',
                                        confirmButtonText: 'OK'
                                    }).then(() => {
                                        $('#editInquiryModal').modal(
                                            'hide'); // Tutup modal
                                        location.reload(); // Reload halaman
                                    });
                                },
                                error: function(xhr) {
                                    console.log(xhr.responseText);
                                }
                            });
                        });
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            }

            $.noConflict();
            jQuery(document).ready(function($) {
                const dataTable = new simpleDatatables.DataTable("#inquiryTable", {
                    searchable: true, // Aktifkan fitur pencarian
                    perPage: 10, // Jumlah entri data per halaman
                    perPageSelect: [5, 10, 20, 150], // Opsi jumlah entri data per halaman
                    dataProps: {
                        // Fungsi untuk menghasilkan format yang diinginkan
                        "Urutan": (value, data) => {
                            // Mendapatkan indeks baris data saat ini
                            const index = data.tableData.id;

                            // Mendapatkan nilai dari kolom "RO" atau "SPOR"
                            const spoOrRo = data[index][0].startsWith("RO") ? "RO" : "SPOR";

                            // Mendapatkan nilai dari kolom "Bulan"
                            const month = data[index][1];

                            // Mendapatkan nilai dari kolom "Tahun"
                            const year = data[index][2];

                            // Menghasilkan urutan sesuai format yang diinginkan
                            const order = (index + 1).toString().padStart(3, '0');
                            return `${spoOrRo}/${month}/${year}/${order}`;
                        }
                    }
                });
            });

            //delete
            function deleteInquiry(id) {
                Swal.fire({
                    title: "Apakah Anda yakin?",
                    text: "Anda akan menghapus inquiry ini!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, hapus!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('deleteinquiry', '') }}/' + id,
                            type: 'DELETE',
                            data: {
                                '_token': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire(
                                    'Deleted!',
                                    'Inquiry telah berhasil dihapus!',
                                    'success'
                                ).then((result) => {
                                    // Jika pengguna menekan tombol 'OK', refresh halaman
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                })
                                // Anda bisa menambahkan kode untuk menghapus baris tabel atau memperbarui tampilan di sini
                            }
                        });
                    }
                })
            }

            function showProgressModal1(id) {
                // Tampilkan modal
                $('#progressHistoryModal1').modal('show');

                // Ambil data progress untuk inquiry tersebut
                $.ajax({
                    url: '{{ route('progressHistory', '') }}/' + id, // Pastikan route-nya sesuai
                    method: 'GET',
                    success: function(response) {
                        const historyBody = $('#historyBody');
                        historyBody.empty(); // Bersihkan data sebelumnya

                        // Menambahkan data response ke dalam tabel
                        response.progressUpdates.forEach((progress, index) => {
                            historyBody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td>${new Date(progress.created_at).toLocaleString()}</td>
                        <td>${progress.user.name}</td>
                        <td>${progress.description}</td>
                    </tr>
                `);
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while fetching progress history.',
                            'error');
                    }
                });
            }

            // report
            document.getElementById('exportReportBtn').addEventListener('click', function() {
                // Get the table element
                var table = document.getElementById('inquiryTable');

                // Create a workbook and add a worksheet
                var wb = XLSX.utils.table_to_book(table, {
                    sheet: "Inquiry Report"
                });
                var ws = wb.Sheets["Inquiry Report"];

                // Filter out unwanted columns (Note, File, is Active, Actions)
                // Define the columns we want to keep (1-based index: No, Kode Inq., Type Inq., Type, Size, Supplier, Qty, Order From, Create By, To Approve, To Validate)
                var columnsToKeep = [1, 2, 3, 4, 5, 6, 7];

                // Get the range of the worksheet
                var range = XLSX.utils.decode_range(ws['!ref']);

                // Create a new worksheet to store the filtered data
                var newWsData = [];

                for (var R = range.s.r; R <= range.e.r; ++R) {
                    var newRow = [];
                    for (var C = range.s.c; C <= range.e.c; ++C) {
                        if (columnsToKeep.includes(C + 1)) {
                            var cellAddress = {
                                c: C,
                                r: R
                            };
                            var cellRef = XLSX.utils.encode_cell(cellAddress);
                            newRow.push(ws[cellRef] ? ws[cellRef].v : null);
                        }
                    }
                    newWsData.push(newRow);
                }

                // Create a new worksheet with the filtered data
                var newWs = XLSX.utils.aoa_to_sheet(newWsData);

                // Apply auto filter to the header row
                newWs['!autofilter'] = {
                    ref: `A1:K${newWsData.length}`
                };

                // Adjust column widths
                var colWidths = [{
                        wpx: 40
                    }, // No
                    {
                        wpx: 100
                    }, // Kode Inq.

                    {
                        wpx: 120
                    }, // Supplier

                    {
                        wpx: 100
                    }, // Order From
                    {
                        wpx: 100
                    }, // Create By
                    {
                        wpx: 100
                    }, // To Approve
                    {
                        wpx: 100
                    } // To Validate
                ];
                newWs['!cols'] = colWidths;

                // Replace the old worksheet with the new one
                wb.Sheets["Inquiry Report"] = newWs;

                // Write the workbook to a file
                XLSX.writeFile(wb, 'Inquiry_Report.xlsx');
            });
        </script>

        <script>
            var inputCount = 1; // Untuk menghitung jumlah input yang ada

            function addInput() {
                inputCount++;
                var html = `<div class="mb-3">
                         <label for="supplier" class="form-label">Type</label>
                        <input type="text" class="form-control type-input" name="type[]" required>
                    </div>
                    <div class="mb-3">
                          <label for="supplier" class="form-label">Size</label>
                        <input type="text" class="form-control size-input" name="size[]" required>
                    </div>`;
                $('#inputContainer').append(html);
            }
        </script>

        <script>
            function handleCategoryChange() {
                const locImpSelect = document.getElementById('loc_imp');
                const otherContainer = document.getElementById('other-container');
                const customerInput = document.getElementById('search_customer');

                if (locImpSelect.value === 'Import') {
                    customerInput.disabled = true; // Disable customer input
                    otherContainer.style.display = 'block'; // Show other input
                } else {
                    customerInput.disabled = false; // Enable customer input
                    otherContainer.style.display = 'none'; // Hide other input
                }
            }

            function addOtherField() {
                const additionalFieldsContainer = document.getElementById('additional-fields');
                const newOtherField = document.createElement('input');
                newOtherField.type = 'text';
                newOtherField.name = 'other[]';
                newOtherField.placeholder = 'Enter other...';
                newOtherField.classList.add('form-control');
                additionalFieldsContainer.appendChild(newOtherField);
            }
        </script>


    </main><!-- End #main -->
@endsection
