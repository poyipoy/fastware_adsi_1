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

            .modal {
                font-family: 'Cambria', serif;
                font-size: 0.9rem;
                font-weight: bold;
            }

            .modal-header {
                font-family: 'Cambria', serif;
                font-size: 0.7rem;
            }

            .testfont {
                font-family: 'Cambria', serif;
                font-size: 1rem;
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

            .btn-custom-confirm-purchasing {
                background-color: #ffb300;
                color: #000000;
                border: none;
                font-size: 8pt;
                font-family: 'Cambria', serif;
                font-weight: bold;
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
                    <h5 class="card-title font-sii text-center">Overview Purchasing Import</h5>
                </div>

                <section class="section">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title fo fw-bold">DAIDO</h5>
                                        @if ($Daido->isEmpty())
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
                                                        @foreach ($Daido as $inquiry)
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
                                                                    @php
                                                                        $monthKey = $inquiry->created_at->format('Y-m');

                                                                        $inquiriesForMonth = $inquiries->filter(function ($item) use ($monthKey) {
                                                                            return $item->created_at->format('Y-m') === $monthKey;
                                                                        });

                                                                        $idsToConfirm = $inquiriesForMonth->whereIn('status', [8])->pluck('id')->values();
                                                                        $idsToFinish = $inquiriesForMonth->whereIn('status', [9])->pluck('id')->values();
                                                                        $idsToDescription = $inquiriesForMonth->pluck('id')->values();
                                                                    @endphp

                                                                    <!-- Tombol View -->
                                                                    <a class="btn btn-custom-view m-1 btn-sm"
                                                                        title="View Form"
                                                                        href="{{ route('showFormSSimportpurchase', ['month' => $monthKey, 'klasifikasi' => 'Daido']) }}">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>

                                                                    <!-- Tombol Confirm -->
                                                                    @if ($idsToConfirm->isNotEmpty())
                                                                        <a href="#" class="btn btn-primary btn-sm"
                                                                            title="Confirm"
                                                                            onclick="confirmPurchasing({!! json_encode($idsToConfirm) !!}, 'Daido'); return false;">
                                                                            <i class="bi bi-check-square-fill"></i>
                                                                        </a>
                                                                    @endif

                                                                    <!-- Tombol Finish hanya muncul jika tidak ada yang perlu dikonfirmasi -->
                                                                    @if ($idsToFinish->isNotEmpty() && $idsToConfirm->isEmpty())
                                                                        <a href="#" class="btn btn-success btn-sm"
                                                                            title="Finish"
                                                                            onclick='finishInquiry({!! json_encode($idsToFinish) !!}, "Daido"); return false;'>
                                                                            <i class="bi bi-check-square-fill"></i>
                                                                        </a>
                                                                        <a href="#" class="btn btn-primary btn-sm" 
                                                                            onclick="showEditDataModal({!! json_encode($idsToDescription, JSON_HEX_TAG) !!}, 'Daido'); return false;" 
                                                                            title="Edit Description">
                                                                            <i class="bi bi-pencil"></i>
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

                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title fo fw-bold">NON-DAIDO</h5>
                                                @if ($NonDaido->isEmpty())
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
                                                                @foreach ($NonDaido as $inquiry)
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
                                                                            @php
                                                                                $monthKey = $inquiry->created_at->format('Y-m');

                                                                                $inquiriesForMonth = $inquiries->filter(function ($item) use ($monthKey) {
                                                                                    return $item->created_at->format('Y-m') === $monthKey;
                                                                                });

                                                                                $idsToConfirm = $inquiriesForMonth->whereIn('status', 8)->pluck('id')->values();
                                                                                $idsToFinish = $inquiriesForMonth->whereIn('status', 9)->pluck('id')->values();
                                                                                $idsToDescription = $inquiriesForMonth->pluck('id')->values(); // Ambil semua ID
                                                                            @endphp

                                                                    <!-- Tombol View -->
                                                                    <a class="btn btn-custom-view m-1 btn-sm"
                                                                    title="View Form"
                                                                    href="{{ route('showFormSSimportpurchase', ['month' => $monthKey, 'klasifikasi' => 'NonDaido']) }}">
                                                                        <i class="bi bi-eye-fill"></i>
                                                                    </a>

                                                                    <!-- Tombol Confirm -->
                                                                    @if ($idsToConfirm->isNotEmpty())
                                                                        <a href="#" class="btn btn-primary btn-sm"
                                                                        title="Confirm"
                                                                        onclick="confirmPurchasing({{ $idsToConfirm->toJson() }}, 'NonDaido'); return false;">
                                                                            <i class="bi bi-check-square-fill"></i>
                                                                        </a>
                                                                    @endif

                                                                    @if ($idsToFinish->isNotEmpty() && $idsToConfirm->isEmpty())
                                                                        <a href="#" class="btn btn-success btn-sm"
                                                                        title="Finish"
                                                                        onclick="finishInquiry({{ $idsToFinish->toJson() }}, 'NonDaido'); return false;">
                                                                            <i class="bi bi-check-square-fill"></i>
                                                                        </a>
                                                                        <a href="#" class="btn btn-primary btn-sm" 
                                                                        onclick="showEditDataModal({!! json_encode($idsToDescription, JSON_HEX_TAG) !!}, 'NonDaido'); return false;" 
                                                                        title="Edit Description">
                                                                        <i class="bi bi-pencil"></i>
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
                        </div>
                    </div>
                    <!-- Modal for Edit Supplier, Last Update, and Est. Date -->
                    <!-- Modal -->
                    <div class="modal fade" id="editDataModal">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <form id="editDataForm">
                                    @csrf
                                    <input type="hidden" id="inquiryIds" name="ids">
                                    <input type="hidden" id="inquiryKlasifikasi" name="klasifikasi">
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label>Description untuk Semua Inquiry Bulan Ini</label>
                                            <textarea name="description" class="form-control" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" onclick="submitEditDataForm()" class="btn btn-primary">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </section>
            </div>
        </section>

        

        <!-- excel -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
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
                const dataTable = new simpleDatatables.DataTable("#overviewTable", {
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
    let fileInput = document.getElementById('excelFile');
    
    if (fileInput.files.length === 0) {
        alert("Pilih file terlebih dahulu!");
        return;
    }
    
    let formData = new FormData();
    formData.append("file", fileInput.files[0]);
    
    $.ajax({
        url: "{{ route('importExcelimportpurchase') }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            "X-CSRF-TOKEN": "{{ csrf_token() }}"
        },
        success: function(response) {
            alert(response.message);
        },
        error: function(xhr) {
            alert("Terjadi kesalahan: " + xhr.responseText);
        }
    });
}

    </script>
    

        <script>
            function confirmPurchasing(ids, klasifikasi) {
                if (!Array.isArray(ids)) {
                    console.error('Invalid IDs format:', ids);
                    Swal.fire('Error!', 'Invalid inquiry IDs format.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Confirm',
                    text: `Are you sure you want to confirm inquiries with klasifikasi: ${klasifikasi}?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes!',
                    cancelButtonText: 'No!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route("confirmPurchaseimport") }}',
                            method: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                ids: ids,
                                klasifikasi: klasifikasi
                            },
                            success: function(response) {
                                Swal.fire('Success!', response.success || 'Inquiries confirmed successfully!', 'success').then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                console.error('AJAX Error:', xhr.responseText);
                                const errorMsg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'An unknown error occurred.';
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    } else {
                        Swal.fire('Canceled', 'Confirmation canceled', 'info');
                    }
                });
            }


            function showEditDataModal(ids, klasifikasi) {
                document.getElementById('inquiryIds').value = JSON.stringify(ids);
                document.getElementById('inquiryKlasifikasi').value = klasifikasi;
                new bootstrap.Modal(document.getElementById('editDataModal')).show();
            }


            function submitEditDataForm() {
                const form = document.getElementById('editDataForm');
                const formData = new FormData(form);
                
                const ids = JSON.parse(formData.get('ids'));
                const klasifikasi = formData.get('klasifikasi');

                if (!Array.isArray(ids) || ids.length === 0) {
                    Swal.fire('Error!', 'No inquiries selected.', 'error');
                    return;
                }

                $.ajax({
                    url: '{{ route('updateInquiryImport') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        ids: ids,
                        klasifikasi: klasifikasi,
                        description: formData.get('description')
                    },
                    success: function(response) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        const errorMsg = xhr.responseJSON?.error || 'Update failed.';
                        Swal.fire('Error!', errorMsg, 'error');
                    }
                });
            }


            function finishInquiry(ids, klasifikasi) {
                if (!Array.isArray(ids)) {
                    console.error('Invalid IDs format:', ids);
                    Swal.fire('Error!', 'Invalid inquiry IDs format.', 'error');
                    return;
                }

                Swal.fire({
                    title: 'Finish',
                    text: 'Are you sure you want to finish inquiries with klasifikasi: ' + klasifikasi + '?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, finish them!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('finishInquiryimport') }}', // Route tanpa parameter ID
                            method: 'POST',
                            data: {
                                '_token': '{{ csrf_token() }}',
                                ids: ids, // Kirim array of IDs
                                klasifikasi: klasifikasi
                            },
                            success: function(response) {
                                Swal.fire('Success!', response.success || 'Selected inquiries marked as finished.', 'success').then(() => {
                                        location.reload();
                                    });
                            },
                            error: function(xhr) {
                                console.error('AJAX Error:', xhr.responseText);
                                const errorMsg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : 'An unknown error occurred.';
                                Swal.fire('Error!', errorMsg, 'error');
                            }
                        });
                    } else {
                        Swal.fire('Canceled', 'Finish canceled', 'info');
                    }
                });
            }


            function showInquiry(id) {
                // Tampilkan detail inquiry dan tambahkan parameter query
                window.location.href = '{{ route('showFormSSimport', '') }}/' + id + '?source=approval';
            }
        </script>

    </main>
@endsection
