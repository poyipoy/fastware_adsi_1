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

            .searchable-dropdown {
                position: relative;
            }

            .searchable-dropdown input {
                width: 100%;
                box-sizing: border-box;
            }

            .dropdown-items {
                display: none;
                position: absolute;
                background-color: white;
                border: 1px solid #ddd;
                max-height: 200px;
                overflow-y: auto;
                z-index: 1000;
            }

            .dropdown-items div {
                padding: 8px;
                cursor: pointer;
            }

            .dropdown-items div:hover {
                background-color: #f1f1f1;
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
                /* background-color: #00d39e; */
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
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-show {
                background-color: #f300a2;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-edit {
                background-color: #3564ff;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-view {
                background-color: #fffb00;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-delete {
                background-color: #ff0000;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-form:hover {
                background-color: #34a500;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-show:hover {
                background-color: #b10076;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-edit:hover {
                background-color: #0026a3;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-view:hover {
                background-color: #ffd000;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-custom-delete:hover {
                background-color: #be0000;
                /* Merah untuk show form */
                font-size: 9pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
            }

            .btn-stts {
                text-align: center;
            }
        </style>

        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title font-sii text-center">Approval Inventory</h5>
                </div>

                <section class="section">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title fo fw-bold">Inquiry Region 1</h5>
                                    <div class="text-start mb-3">
                                        <button id="exportButton" class="btn btn-success">
                                            <i class="bi bi-file-earmark-excel-fill"></i> Export Selected
                                        </button>
                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                                            <i class="bi bi-file-earmark-excel"></i> Import Data
                                        </button>
                                    </div>
                                    
                                    <!-- Import Modal -->
                                    <div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form action="#" method="POST" enctype="multipart/form-data">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="importModalLabel">Import Inquiry Data</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        @if(session('import_success'))
                                                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                                {{ session('import_success') }}
                                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                            </div>
                                                        @endif
                                    
                                                        @if(session('import_error'))
                                                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                                {{ session('import_error') }}
                                                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                            </div>
                                                        @endif
                                    
                                                        <div class="mb-3">
                                                            <label for="file" class="form-label">Excel File</label>
                                                            <input type="file" name="file" id="file" 
                                                                   class="form-control @error('file') is-invalid @enderror" 
                                                                   accept=".xlsx,.xls" required>
                                                            @error('file')
                                                                <div class="invalid-feedback">{{ $message }}</div>
                                                            @enderror
                                                            <div class="form-text">Upload the Excel file that was previously exported from the system.</div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="button" class="btn btn-primary" onclick="uploadexcel()">Import</button> <!-- Ubah type menjadi button -->
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    @php
                                        // Filter inquiry berdasarkan region 1
                                        $filteredInquiries = $inquiries->where('region', 1);
                                    @endphp
                                        @if ($filteredInquiries->isEmpty())
                                            <div class="eempty">
                                                <p class="ps-3 mt-3">--- Not Found Inquiry Sales ---</p>
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="datatable table table-hover" id="inquiryTable1">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="40px">
                                                                {{-- <input type="checkbox" class="form-check-input" id="headerCheckbox1" onchange="toggleCheckboxes('inquiryTable1', this.checked)"> --}}
                                                            </th>
                                                            <th scope="col">No</th>
                                                            <th scope="col">Bulan</th>
                                                            <th scope="col">Create By</th>
                                                            <th scope="col">Reference</th>
                                                            <th scope="col">Submit</th>
                                                            <th scope="col">Category</th>
                                                            <th scope="col">Status</th>
                                                            <th scope="col">Last Update</th>
                                                            <th scope="col">Update Time</th>
                                                            {{-- <th scope="col">Est. Date</th> --}}
                                                            <th scope="col">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($filteredInquiries as $inquiry)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input inquiry-checkbox" name="selected_inquiries[]" value="{{ $inquiry->id }}" data-id="{{ $inquiry->id }}" data-table="inquiryTable1">
                                                                </td>
                                                                <th scope="row">{{ $loop->iteration }}</th>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->oldest() // Mengambil data pertama berdasarkan created_at paling lama
                                                                            ->first();
                                                                
                                                                        // Format bulan dan tahun jika data ada, jika tidak tampilkan pesan default
                                                                        $lastUpdateMessage = $progress ? $progress->created_at->format('F Y') : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->create_by }}</td>
                                                                <td>{{ $inquiry->kode_inquiry }}</td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->where('description', 'Approved by Ka. Dept.')
                                                                            ->orderBy('created_at', 'asc') // Mengurutkan dari yang paling lama
                                                                            ->first(); // Ambil data pertama (paling lama)
                                                                
                                                                        $lastUpdateMessage = $progress ? $progress->created_at : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                
                                                                <td>{{ $inquiry->loc_imp }}</td>
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

                                                                    $buttonClasses = [
                                                                        1 => 'btn-secondary',
                                                                        2 => 'btn-success',
                                                                        3 => 'btn-danger',
                                                                        4 => 'btn-info',
                                                                        5 => 'btn-warning',
                                                                        6 => 'btn-primary',
                                                                        7 => 'btn-danger',
                                                                        8 => 'btn-danger',
                                                                        9 => 'btn-warning',
                                                                    ];
                                                                @endphp

                                                                <td class="btn-stts">
                                                                    <button
                                                                        class="btn btn-sm {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }}">
                                                                        {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                                    </button>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where(
                                                                            'inquiry_id',
                                                                            $inquiry->id,
                                                                        )
                                                                            ->latest()
                                                                            ->first();
                                                                        $lastUpdateMessage =
                                                                            $progress &&
                                                                            $progress->description !== 'No updates yet'
                                                                                ? $progress->description
                                                                                : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->updated_at }}</td>
                                                                {{-- <td>{{ $inquiry->est_date }}</td> --}}
                                                                <td>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-edit m-1 btn-sm"
                                                                            title="Edit">
                                                                            <i class="bi bi-pencil-fill"
                                                                                onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
                                                                        </a>
                                                                    @endif
                                                                    <a class="btn btn-custom-view m-1 btn-sm"
                                                                        title="View Form"
                                                                        href="{{ route('showFormSSimport', $inquiry->id) }}">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-primary btn-sm"
                                                                        onclick="approveInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-check-square-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-danger btn-sm"
                                                                        onclick="rejectInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-file-x-fill"></i>
                                                                    </a>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-delete m-1 btn-sm"
                                                                            title="Delete">
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
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title fo fw-bold">Inquiry Region 2</h5>
                                    @php
                                        // Filter inquiry berdasarkan region 1
                                        $filteredInquiries = $inquiries->where('region', 2);
                                    @endphp
                                        @if ($filteredInquiries->isEmpty())
                                            <div class="eempty">
                                                <p class="ps-3 mt-3">--- Not Found Inquiry Sales ---</p>
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="datatable table table-hover" id="inquiryTable2">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="40px">
                                                                {{-- <input type="checkbox" class="form-check-input" id="headerCheckbox1" onchange="toggleCheckboxes('inquiryTable1', this.checked)"> --}}
                                                            </th>
                                                            <th scope="col">No</th>
                                                            <th scope="col">Bulan</th>
                                                            <th scope="col">Create By</th>
                                                            <th scope="col">Reference</th>
                                                            <th scope="col">Submit</th>
                                                            <th scope="col">Category</th>
                                                            <th scope="col">Status</th>
                                                            <th scope="col">Last Update</th>
                                                            <th scope="col">Update Time</th>
                                                            {{-- <th scope="col">Est. Date</th> --}}
                                                            <th scope="col">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($filteredInquiries as $inquiry)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input inquiry-checkbox" name="selected_inquiries[]" value="{{ $inquiry->id }}" data-id="{{ $inquiry->id }}" data-table="inquiryTable2">
                                                                </td>
                                                                <th scope="row">{{ $loop->iteration }}</th>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->oldest() // Mengambil data pertama berdasarkan created_at paling lama
                                                                            ->first();
                                                                
                                                                        // Format bulan dan tahun jika data ada, jika tidak tampilkan pesan default
                                                                        $lastUpdateMessage = $progress ? $progress->created_at->format('F Y') : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->create_by }}</td>
                                                                <td>{{ $inquiry->kode_inquiry }}</td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->where('description', 'Approved by Ka. Dept.')
                                                                            ->orderBy('created_at', 'asc') // Mengurutkan dari yang paling lama
                                                                            ->first(); // Ambil data pertama (paling lama)
                                                                
                                                                        $lastUpdateMessage = $progress ? $progress->created_at : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                
                                                                <td>{{ $inquiry->loc_imp }}</td>
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

                                                                    $buttonClasses = [
                                                                        1 => 'btn-secondary',
                                                                        2 => 'btn-success',
                                                                        3 => 'btn-danger',
                                                                        4 => 'btn-info',
                                                                        5 => 'btn-warning',
                                                                        6 => 'btn-primary',
                                                                        7 => 'btn-danger',
                                                                        8 => 'btn-danger',
                                                                        9 => 'btn-warning',
                                                                    ];
                                                                @endphp

                                                                <td class="btn-stts">
                                                                    <button
                                                                        class="btn btn-sm {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }}">
                                                                        {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                                    </button>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where(
                                                                            'inquiry_id',
                                                                            $inquiry->id,
                                                                        )
                                                                            ->latest()
                                                                            ->first();
                                                                        $lastUpdateMessage =
                                                                            $progress &&
                                                                            $progress->description !== 'No updates yet'
                                                                                ? $progress->description
                                                                                : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->updated_at }}</td>
                                                                {{-- <td>{{ $inquiry->est_date }}</td> --}}
                                                                <td>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-edit m-1 btn-sm"
                                                                            title="Edit">
                                                                            <i class="bi bi-pencil-fill"
                                                                                onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
                                                                        </a>
                                                                    @endif
                                                                    <a class="btn btn-custom-view m-1 btn-sm"
                                                                        title="View Form"
                                                                        href="{{ route('showFormSSimport', $inquiry->id) }}">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-primary btn-sm"
                                                                        onclick="approveInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-check-square-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-danger btn-sm"
                                                                        onclick="rejectInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-file-x-fill"></i>
                                                                    </a>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-delete m-1 btn-sm"
                                                                            title="Delete">
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
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title fo fw-bold">Inquiry Region 3</h5>
                                    @php
                                        // Filter inquiry berdasarkan region 1
                                        $filteredInquiries = $inquiries->where('region', 3);
                                    @endphp
                                        @if ($filteredInquiries->isEmpty())
                                            <div class="eempty">
                                                <p class="ps-3 mt-3">--- Not Found Inquiry Sales ---</p>
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="datatable table table-hover" id="inquiryTable3">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="40px">
                                                                {{-- <input type="checkbox" class="form-check-input" id="headerCheckbox3" onchange="toggleCheckboxes('inquiryTable3', this.checked)"> --}}
                                                            </th>
                                                            <th scope="col">No</th>
                                                            <th scope="col">Bulan</th>
                                                            <th scope="col">Create By</th>
                                                            <th scope="col">Reference</th>
                                                            <th scope="col">Submit</th>
                                                            <th scope="col">Category</th>
                                                            <th scope="col">Status</th>
                                                            <th scope="col">Last Update</th>
                                                            <th scope="col">Update Time</th>
                                                            {{-- <th scope="col">Est. Date</th> --}}
                                                            <th scope="col">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($filteredInquiries as $inquiry)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input inquiry-checkbox" name="selected_inquiries[]" value="{{ $inquiry->id }}" data-id="{{ $inquiry->id }}" data-table="inquiryTable3">
                                                                </td>
                                                                <th scope="row">{{ $loop->iteration }}</th>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->oldest() // Mengambil data pertama berdasarkan created_at paling lama
                                                                            ->first();
                                                                
                                                                        // Format bulan dan tahun jika data ada, jika tidak tampilkan pesan default
                                                                        $lastUpdateMessage = $progress ? $progress->created_at->format('F Y') : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->create_by }}</td>
                                                                <td>{{ $inquiry->kode_inquiry }}</td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->where('description', 'Approved by Ka. Dept.')
                                                                            ->orderBy('created_at', 'asc') // Mengurutkan dari yang paling lama
                                                                            ->first(); // Ambil data pertama (paling lama)
                                                                
                                                                        $lastUpdateMessage = $progress ? $progress->created_at : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                
                                                                <td>{{ $inquiry->loc_imp }}</td>
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

                                                                    $buttonClasses = [
                                                                        1 => 'btn-secondary',
                                                                        2 => 'btn-success',
                                                                        3 => 'btn-danger',
                                                                        4 => 'btn-info',
                                                                        5 => 'btn-warning',
                                                                        6 => 'btn-primary',
                                                                        7 => 'btn-danger',
                                                                        8 => 'btn-danger',
                                                                        9 => 'btn-warning',
                                                                    ];
                                                                @endphp

                                                                <td class="btn-stts">
                                                                    <button
                                                                        class="btn btn-sm {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }}">
                                                                        {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                                    </button>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where(
                                                                            'inquiry_id',
                                                                            $inquiry->id,
                                                                        )
                                                                            ->latest()
                                                                            ->first();
                                                                        $lastUpdateMessage =
                                                                            $progress &&
                                                                            $progress->description !== 'No updates yet'
                                                                                ? $progress->description
                                                                                : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->updated_at }}</td>
                                                                {{-- <td>{{ $inquiry->est_date }}</td> --}}
                                                                <td>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-edit m-1 btn-sm"
                                                                            title="Edit">
                                                                            <i class="bi bi-pencil-fill"
                                                                                onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
                                                                        </a>
                                                                    @endif
                                                                    <a class="btn btn-custom-view m-1 btn-sm"
                                                                        title="View Form"
                                                                        href="{{ route('showFormSSimport', $inquiry->id) }}">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-primary btn-sm"
                                                                        onclick="approveInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-check-square-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-danger btn-sm"
                                                                        onclick="rejectInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-file-x-fill"></i>
                                                                    </a>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-delete m-1 btn-sm"
                                                                            title="Delete">
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
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title fo fw-bold">Inquiry Region 4</h5>
                                    @php
                                        // Filter inquiry berdasarkan region 1
                                        $filteredInquiries = $inquiries->where('region', 4);
                                    @endphp
                                        @if ($filteredInquiries->isEmpty())
                                            <div class="eempty">
                                                <p class="ps-3 mt-3">--- Not Found Inquiry Sales ---</p>
                                            </div>
                                        @else
                                            <div class="table-responsive">
                                                <table class="datatable table table-hover" id="inquiryTable4">
                                                    <thead>
                                                        <tr>
                                                            <th scope="col" width="40px">
                                                                {{-- <input type="checkbox" class="form-check-input" id="headerCheckbox4" onchange="toggleCheckboxes('inquiryTable4', this.checked)"> --}}
                                                            </th>
                                                            <th scope="col">No</th>
                                                            <th scope="col">Bulan</th>
                                                            <th scope="col">Create By</th>
                                                            <th scope="col">Reference</th>
                                                            <th scope="col">Submit</th>
                                                            <th scope="col">Category</th>
                                                            <th scope="col">Status</th>
                                                            <th scope="col">Last Update</th>
                                                            <th scope="col">Update Time</th>
                                                            {{-- <th scope="col">Est. Date</th> --}}
                                                            <th scope="col">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($filteredInquiries as $inquiry)
                                                            <tr>
                                                                <td>
                                                                    <input type="checkbox" class="form-check-input inquiry-checkbox" name="selected_inquiries[]" value="{{ $inquiry->id }}" data-id="{{ $inquiry->id }}" data-table="inquiryTable4">
                                                                </td>
                                                                <th scope="row">{{ $loop->iteration }}</th>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->oldest() // Mengambil data pertama berdasarkan created_at paling lama
                                                                            ->first();
                                                                
                                                                        // Format bulan dan tahun jika data ada, jika tidak tampilkan pesan default
                                                                        $lastUpdateMessage = $progress ? $progress->created_at->format('F Y') : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->create_by }}</td>
                                                                <td>{{ $inquiry->kode_inquiry }}</td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where('inquiry_id', $inquiry->id)
                                                                            ->where('description', 'Approved by Ka. Dept.')
                                                                            ->orderBy('created_at', 'asc') // Mengurutkan dari yang paling lama
                                                                            ->first(); // Ambil data pertama (paling lama)
                                                                
                                                                        $lastUpdateMessage = $progress ? $progress->created_at : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                
                                                                <td>{{ $inquiry->loc_imp }}</td>
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

                                                                    $buttonClasses = [
                                                                        1 => 'btn-secondary',
                                                                        2 => 'btn-success',
                                                                        3 => 'btn-danger',
                                                                        4 => 'btn-info',
                                                                        5 => 'btn-warning',
                                                                        6 => 'btn-primary',
                                                                        7 => 'btn-danger',
                                                                        8 => 'btn-danger',
                                                                        9 => 'btn-warning',
                                                                    ];
                                                                @endphp

                                                                <td class="btn-stts">
                                                                    <button
                                                                        class="btn btn-sm {{ $buttonClasses[$inquiry->status] ?? 'btn-light' }}">
                                                                        {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                                    </button>
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $progress = App\Models\TrxDboProgPurchase::where(
                                                                            'inquiry_id',
                                                                            $inquiry->id,
                                                                        )
                                                                            ->latest()
                                                                            ->first();
                                                                        $lastUpdateMessage =
                                                                            $progress &&
                                                                            $progress->description !== 'No updates yet'
                                                                                ? $progress->description
                                                                                : 'No updates yet';
                                                                    @endphp
                                                                    {{ $lastUpdateMessage }}
                                                                </td>
                                                                <td>{{ $inquiry->updated_at }}</td>
                                                                {{-- <td>{{ $inquiry->est_date }}</td> --}}
                                                                <td>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-edit m-1 btn-sm"
                                                                            title="Edit">
                                                                            <i class="bi bi-pencil-fill"
                                                                                onclick="openEditInquiryModal({{ $inquiry->id }})"></i>
                                                                        </a>
                                                                    @endif
                                                                    <a class="btn btn-custom-view m-1 btn-sm"
                                                                        title="View Form"
                                                                        href="{{ route('showFormSSimport', $inquiry->id) }}">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-primary btn-sm"
                                                                        onclick="approveInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-check-square-fill"></i>
                                                                    </a>
                                                                    <a href="#" class="btn btn-danger btn-sm"
                                                                        onclick="rejectInventory({{ $inquiry->id }}); return false;">
                                                                        <i class="bi bi-file-x-fill"></i>
                                                                    </a>
                                                                    @if ($inquiry->status == 1)
                                                                        <a class="btn btn-custom-delete m-1 btn-sm"
                                                                            title="Delete">
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
                            </div>
                        </div>
                    </div>
                </section>
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

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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

            $.noConflict();
            jQuery(document).ready(function($) {
                const dataTable = new simpleDatatables.DataTable("#overviewTableInventory", {
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
        </script>

<script>
    function uploadexcel() {
        let fileInput = document.getElementById('file'); // Perbaiki ID di sini
        
        if (fileInput.files.length === 0) {
            alert("Pilih file terlebih dahulu!");
            return;
        }
        
        let formData = new FormData();
        formData.append("file", fileInput.files[0]);
        
        $.ajax({
            url: "{{ route('import.inquiry') }}", // Pastikan rute ini benar
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            success: function(response) {
                alert(response.message);
                window.location.href = response.redirect; // Redirect ke halaman tujuan
            },
            error: function(xhr) {
                alert("Terjadi kesalahan: " + xhr.responseText);
            }
        });
    }
</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Initialize the checkboxes
    updateHeaderCheckboxes();
    
    // Add event listeners to all inquiry checkboxes
    document.querySelectorAll('.inquiry-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateHeaderCheckboxes();
        });
    });
    
    // Tambahkan event listener untuk tombol ekspor
    const exportButton = document.getElementById('exportButton');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            let selectedIds = [];
            
            // Kumpulkan semua ID dari checkbox yang tercentang
            document.querySelectorAll('.inquiry-checkbox:checked').forEach((checkbox) => {
                const id = checkbox.getAttribute('data-id');
                if (id) {
                    selectedIds.push(id);
                }
            });
            
            console.log("Selected IDs for export:", selectedIds);
            
            if (selectedIds.length === 0) {
                alert("Silakan pilih minimal satu inquiry untuk diekspor.");
                return;
            }
            
            // Buat URL untuk request ekspor
            const exportUrl = '{{ route("exportInquiries") }}?id=' + encodeURIComponent(selectedIds.join(','));
            
            // Redirect untuk download file
            window.location.href = exportUrl;
        });
    } else {
        console.error('Export button not found');
    }
});

// Function to toggle all checkboxes in a specific table
function toggleCheckboxes(tableId, checked) {
    const checkboxes = document.querySelectorAll(`#${tableId} .inquiry-checkbox`);
    checkboxes.forEach(function(checkbox) {
        checkbox.checked = checked;
    });
    updateHeaderCheckboxes();
}

// Function to update header checkboxes based on individual checkboxes
function updateHeaderCheckboxes() {
    ['inquiryTable1', 'inquiryTable2', 'inquiryTable3', 'inquiryTable4'].forEach(tableId => {
        const checkboxes = document.querySelectorAll(`#${tableId} .inquiry-checkbox`);
        const headerCheckbox = document.getElementById(`headerCheckbox${tableId.replace('inquiryTable', '')}`);
        
        if (checkboxes.length > 0 && headerCheckbox) {
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const someChecked = Array.from(checkboxes).some(cb => cb.checked);
            
            headerCheckbox.checked = allChecked;
            headerCheckbox.indeterminate = someChecked && !allChecked;
        }
    });
}
</script>

        <script>
            function showInquiry(id) {
                window.location.href = '{{ route('formulirInquiryimport', '') }}/' + id;
            }

            function showInquiry(id) {
                // Tampilkan detail inquiry dan tambahkan parameter query
                window.location.href = '{{ route('showFormSSimport', '') }}/' + id + '?source=approval';
            }
        </script>

        <script>
            function approveInventory(id) {
                $.ajax({
                    url: '{{ route('approveInventoryImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan CSRF token
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry approved successfully.', 'success').then(() => {
                            location.reload(); // Reload halaman untuk melihat update
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while approving the inquiry.', 'error');
                    }
                });
            }

            function rejectInventory(id) {
                $.ajax({
                    url: '{{ route('rejectInventoryImport', '') }}/' + id,
                    method: 'POST',
                    data: {
                        '_token': '{{ csrf_token() }}' // Sertakan CSRF token
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Inquiry rejected successfully.', 'success').then(() => {
                            location.reload(); // Reload halaman untuk melihat update
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while rejecting the inquiry.', 'error');
                    }
                });
            }
        </script>
    </main>
@endsection
