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

            .btn-blue {
            background-color: #0ea5e9;
            color: white;
            }
            .btn-blue:hover,
            .btn-blue:focus {
            background-color: #0284c7;
            color: white;
            }
            .btn-green {
            background-color: #15803d;
            color: white;
            }
            .btn-green:hover,
            .btn-green:focus {
            background-color: #166534;
            color: white;
            }
            .btn-yellow {
            background-color: #d97706;
            color: white;
            }
            .btn-yellow:hover,
            .btn-yellow:focus {
            background-color: #b45309;
            color: white;
            }
            .btn .icon {
            margin-right: 6px;
            }
            @media (max-width: 576px) {
            h2 {
                font-size: 1rem;
            }
            table {
                font-size: 0.85rem;
            }
            .btn {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
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

            .datatable tbody td  {
                background-color: transparent; /* Memungkinkan penggunaan latar belakang yang ditetapkan dengan inline style */
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
            .btn-hover:hover {
                    opacity: 0.9; /* Mengurangi opasitas saat hover */
                    transition: opacity 0.3s ease-in-out; /* Transisi halus */
                }
        </style>

        <div class="pagetitle">
            <h1>Menu Pengajuan</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('dashboardHandling') }}">Dashboard</a></li>
                    <li class="breadcrumb-item">Menu Pengajuan Custom</li>

                </ol>
            </nav>
        </div><!-- End Page Title -->

        <section class="section">
            <div class="row">
                <div class="col-lg-12">

                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Tampilan Data Pengajuan Custom</h5>
                            <div class="d-flex align-item-center justify-content-start mt-4 mb-3">
                                <button class="btn btn-add btn-sm me-2" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
                                    <i class="bi bi-plus-circle"> Add Request Custom</i>
                                </button>
                            </div>
                            <!-- Table with stripped rows -->
                            <div class="table-responsive" style="height: 100%; overflow-y: auto;">
                                <div class="mb-3 d-flex gap-2">
                                    <button class="btn btn-secondary" onclick="filterTable('all')">All Data</button>
                                    <button class="btn btn-danger" onclick="filterTable('lebih3')">Outstanding</button>
                                    <button class="btn btn-success" onclick="filterTable('maks3')">On-Track</button>
                                </div>

                                
                                <table class="datatable table">
                                    <thead>
                                        <tr>
                                            <th class="text-center" width="50px"></th>
                                            <th class="text-center" width="50px">NO</th>
                                            <th class="text-center" width="100px">No Ref</th>
                                            <th class="text-center" width="100px">PIC</th>
                                            <th class="text-center" width="100px">Nama Customer</th>
                                            <th class="text-center" width="100px">Nama Project</th>
                                            <th class="text-center" width="100px">No SO</th>
                                            <th class="text-center" width="100px">Keterangan</th>
                                            <th class="text-center" width="100px">Part Name</th>
                                            <th class="text-center" width="100px">Note Sales</th>
                                            <th class="text-center" width="100px">Jenis Proses</th>
                                            <th class="text-center" width="100px">Tgl Pengajuan</th>
                                            <th class="text-center" width="100px">LeadTime</th>
                                            <th class="text-center" width="100px">Status</th>
                                            <th class="text-center" width="100px">Cost Production</th>
                                            <th class="text-center" width="100px">Selling Price</th>
                                            <th class="text-center" width="100px">Profit (%)</th>
                                            <th class="text-center" width="100px">Custom</th>
                                            <th class="text-center" width="100px">Custom Aprroval</th>
                                            <th class="text-center" width="100px">Marketing Dept Head</th>
                                            <th class="text-center" width="100px">Marketing Approval</th>
                                            <th class="text-center" width="100px">Finance Dept Head</th>
                                            <th class="text-center" width="100px">Finance Approval</th>
                                            <th class="text-center" width="100px">Aksi</th>
                                            @if (auth()->user()->name == 'RAGIL ISHA RAHMANTO')
                                            <th class="text-center" width="100px">Quotation Subcont</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($materials as $key => $pengajuan)
                                            <tr data-id="{{ $pengajuan->id }}">
                                                
                                                <td>
                                                    @php
                                                        $warna = '';
                                                        if ($date[$pengajuan->id] > 3) {
                                                            $warna = 'lebih3';
                                                        } elseif (!is_null($date[$pengajuan->id]) && $date[$pengajuan->id] >= 0 && $date[$pengajuan->id] <= 3) {
                                                            $warna = 'maks3';
                                                        }
                                                    @endphp
                                                    <div class="indikator-warna {{ $warna }}" style="width: 20px; height: 20px;
                                                        @if($date[$pengajuan->id] > 3)
                                                            background-color: #ed2434;
                                                        @elseif($date[$pengajuan->id] !== null && $date[$pengajuan->id] >= 0 && $date[$pengajuan->id] <= 3)
                                                            background-color: #44ff4d;
                                                        @else
                                                            background-color: #ffffff;
                                                        @endif
                                                    ">
                                                    </div>
                                                </td>

                                                <td class="text-center">{{ $key + 1 }}</td>
                                                <td class="text-center">{{ $pengajuan->no_ref}}</td>
                                                {{-- Menampilkan nama user yang terakhir mengubah data --}}
                                                <td class="text-center">{{ $pengajuan->modified_at ? $pengajuan->modified_at : '' }}</td>
                                                <td class="text-center">{{ $pengajuan->nama_customer }}</td>
                                                <td class="text-center">{{ $pengajuan->nama_project }}</td>
                                                <td class="text-center">{{ $pengajuan->so }}</td>
                                                <td class="text-center">{{ $pengajuan->keterangan }}</td>
                                                <td class="text-center">{{ $pengajuan->part_name }}</td>
                                                <td class="text-center">{{ $pengajuan->note_sales }}</td>
                                                <td class="text-center">
                                                    {{ $pengajuan->jenis_proses_subcont !== 'Null' ? $pengajuan->jenis_proses_subcont : '' }}
                                                </td>
                                                <td class="text-center">{{ $pengajuan->created_at->format('d-m-Y') }}</td>
                                                <td class="text-center">
                                                    {{ $sincedays[$pengajuan->id] ?? '-' }} Hari
                                                </td>
                                                <td class="text-center">
                                                    @php
                                                        $statusClasses = [
                                                            1 => ['bg' => 'bg-secondary', 'label' => 'Draft'], // Abu-abu
                                                            2 => ['bg' => 'bg-primary', 'label' => 'Open'], // Hijau
                                                            3 => ['bg' => 'bg-warning', 'label' => 'On Progress'], // Kuning
                                                            4 => ['bg' => 'bg-warning', 'label' => 'On Progress'], // Kuning (sama dengan status 3)
                                                            5 => ['bg' => 'bg-info', 'label' => 'Finish'], // Biru Muda
                                                        ];

                                                        // Menentukan status saat ini berdasarkan sec_line
                                                        if ($pengajuan->sec_line == 1) {
                                                            // Jika sec_line == 1
                                                            switch ($pengajuan->status_1) {
                                                                case 1:
                                                                    $currentStatus = $statusClasses[1]; // Draf
                                                                    break;
                                                                case 2:
                                                                    $currentStatus = $statusClasses[2]; // Open
                                                                    break;
                                                                case 3:
                                                                    $currentStatus = $statusClasses[3]; // On Progress
                                                                    break;
                                                                case 4:
                                                                    $currentStatus = $statusClasses[4]; // On Progress
                                                                    break;
                                                                case 5:
                                                                    $currentStatus = $statusClasses[5]; // Finish
                                                                    break;
                                                                default:
                                                                    $currentStatus = ['bg' => 'bg-danger', 'label' => 'Status Tidak Tersedia'];
                                                            }
                                                        } else {
                                                            // Jika sec_line == 2
                                                            switch ($pengajuan->status_1) {
                                                                case 1:
                                                                case 2:
                                                                    $currentStatus = $statusClasses[2]; // Open
                                                                    break;
                                                                case 3:
                                                                    $currentStatus = $statusClasses[3]; // On Progress
                                                                    break;
                                                                case 4:
                                                                    $currentStatus = $statusClasses[4]; // On Progress
                                                                    break;
                                                                case 5:
                                                                    $currentStatus = $statusClasses[5]; // Finish
                                                                    break;
                                                                default:
                                                                    $currentStatus = ['bg' => 'bg-danger', 'label' => 'Status Tidak Tersedia'];
                                                            }
                                                        }
                                                    @endphp
                                                    <span class="badge {{ $currentStatus['bg'] }}" style="font-size: 14px;">
                                                        {{ $currentStatus['label'] }}
                                                    </span>
                                                </td>
                                                <td class="text-center">Rp{{ number_format($pengajuan->harga_awal, 0, ',', '.') }}</td>
                                                <td class="text-center">Rp{{ number_format($pengajuan->harga_akhir, 0, ',', '.') }}</td>         
                                                <td class="text-center profit-cell" data-harga-awal="{{ $pengajuan->harga_awal }}" data-harga-akhir="{{ $pengajuan->harga_akhir }}"></td>                                       
                                                <td class="text-center">{{ $pengajuan->confirm_prod ? $pengajuan->production->name : '' }}</td>
                                                <td class="text-center">{{ $pengajuan->date_confirm_prod ? $pengajuan->date_confirm_prod : '' }}</td>
                                                <td class="text-center">{{ $pengajuan->marketing ? $pengajuan->marketing->name : '' }}</td>
                                                <td class="text-center">{{ $pengajuan->date_app_1 ? $pengajuan->date_app_1 : '' }}</td>
                                                <td class="text-center">{{ $pengajuan->finance ? $pengajuan->finance->name : '' }}</td>
                                                <td class="text-center">{{ $pengajuan->date_app_2 ? $pengajuan->date_app_2 : '' }}</td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center align-items-center">
                                                        {{-- Tombol Lihat --}}

                                                        @if (auth()->user()->name == 'RAGIL ISHA RAHMANTO')
                                                        {{-- Jika user adalah sales dan statusnya draft --}}
                                                            <a href="{{ route('CustomRequest.form', $pengajuan->id) }}" class="btn btn-warning btn-sm d-inline-flex align-items-center me-2">
                                                                <i class="fas fa-eye"></i> Lihat
                                                            </a>
                                                        @else
                                                            
                                                            <a href="{{ route('CustomRequest.formSales', $pengajuan->id) }}" class="btn btn-warning btn-sm d-inline-flex align-items-center me-2">
                                                            <i class="fas fa-eye"></i> Lihat
                                                        </a>
                                                        @endif
                                                        {{-- @if ($pengajuan->status_1 == 1)
                                                            <button type="button" class="btn btn-green btn-sm d-inline-flex align-items-center me-2" 
                                                                onclick="konfirmasiKirim('{{ route('kirimproduction', $pengajuan->id) }}', 'Production')">
                                                                <i class="fas fa-paper-plane"></i> Submit
                                                            </button>
                                                        @endif --}}
                                                        @if ($pengajuan->status_1 == 2 && auth()->user()->name == 'RAGIL ISHA RAHMANTO' && $pengajuan->sec_line == 1)
                                                            <button type="button" class="btn btn-yellow btn-sm d-inline-flex align-items-center" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#modalKeterangan" 
                                                                    data-id="{{ $pengajuan->id }}"> <!-- Tambahkan data-id -->
                                                                <i class="fas fa-paper-plane"></i> Subcont
                                                            </button>
                                                        @endif
                                                    </div>
                                                </td>
                                                @if (auth()->user()->name == 'RAGIL ISHA RAHMANTO')
                                                <td class="text-center align-middle">
                                                    @if ($pengajuan->quotation_file)
                                                        <a href="{{ asset($pengajuan->quotation_file) }}" target="_blank" 
                                                           class="d-inline-block p-3 bg-white rounded border shadow-sm text-decoration-none" 
                                                           style="color: inherit; transition: transform 0.2s ease;" 
                                                           onmouseover="this.style.transform='scale(1.05)'" 
                                                           onmouseout="this.style.transform='scale(1)'"
                                                           data-bs-toggle="tooltip" title="Click to view or download the quotation file">
                                                            <i class="fas fa-file-pdf fa-2x text-danger mb-1"></i>
                                                            <p class="mb-0 fw-bold">View Quotation</p>
                                                        </a>
                                                    @else
                                                        <span class="text-muted fst-italic">Quotation belum tersedia</span>
                                                    @endif
                                                </td>
                                                
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="addMaterialModal" tabindex="-1" aria-labelledby="addMaterialModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form action="{{ route('CustomRequest.store') }}" method="POST" id="requestForm">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Form Request Quotation</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Customer</label>
                                    <input type="text" id="customer" name="customer" class="form-control" placeholder="Masukkan Nama Customer..." required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Project</label>
                                    <input type="text" id="nama_project" name="nama_project" class="form-control" placeholder="Masukkan Nama Project..." required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">No SO</label>
                                    <input type="text" id="no_so" name="no_so" class="form-control" placeholder="SO/Tahun/.....(masukan 6 angka terakhir saja)" required 
                                        pattern="\d{6}" maxlength="6" title="Masukkan 6 digit angka saja">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nama Part</label>
                                    <input type="text" id="part_name" name="part_name" class="form-control" placeholder="Masukkan Nama Part..." required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Note Sales</label>
                                    <textarea id="note_sales" name="note_sales" class="form-control" rows="3" placeholder="Masukkan Catatan Sales..."></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>Simpan</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="modalKeterangan" tabindex="-1" role="dialog" aria-labelledby="modalKeteranganLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalKeteranganLabel">Input Keterangan dan Jenis Proses</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="jenisProcessInput">Jenis Proses Subcont</label>
                                <input type="text" class="form-control" id="jenisProcessInput" required>
                            </div>
                            <div class="form-group mt-2">
                                <label for="keteranganInput">Keterangan</label>
                                <input type="text" class="form-control" id="keteranganInput" required>
                            </div>
                            <div class="form-group mt-2">
                                <label for="fileInput">Upload File</label>
                                <input type="file" class="form-control" id="fileInput">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                            <button type="button" class="btn btn-primary" id="submitKirim">Kirim</button>
                        </div>
                    </div>
                </div>
            </div>


            <form id="formKirim" method="POST" style="display:none;">
                @csrf
            </form>
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
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
            
            function filterTable(type) {
                const rows = document.querySelectorAll("table tbody tr");

                rows.forEach(row => {
                    const indicator = row.querySelector(".indikator-warna");

                    if (!indicator) return;

                    const classList = indicator.classList;

                    if (type === "all") {
                        row.style.display = "";
                    } else if (type === "lebih3") {
                        row.style.display = classList.contains("lebih3") ? "" : "none";
                    } else if (type === "maks3") {
                        row.style.display = classList.contains("maks3") ? "" : "none";
                    }
                });
            }

            // $(document).ready(function() {
            //     // Menginisialisasi DataTables
            //     var table = $('.datatable').DataTable({
            //         // Opsi lain jika perlu
            //     });

            //     // Callback untuk mengubah warna latar belakang
            //     table.on('draw', function() {
            //         $('.datatable tbody tr').each(function() {
            //             var id = $(this).data('id'); // Mengambil ID dari atribut data-id
            //             var currentDateValue = {{ json_encode($date) }}[id]; // Mendapatkan data dari PHP untuk penyertaan JavaScript
                        
            //             // Tentukan warna berdasarkan nilai
            //             if (currentDateValue > 3) {
            //                 $(this).css('background-color', '#ed2434'); // Merah jika lebih dari 3
            //             } else if (currentDateValue < 3) {
            //                 $(this).css('background-color', '#44ff4d'); // Hijau jika kurang dari 3
            //             } else {
            //                 $(this).css('background-color', '#ffffff'); // Putih jika sama dengan 3
            //             }
            //         });
            //     });
            // });
            function konfirmasiKirim(url, tujuan) {
                if (confirm(`Anda yakin ingin mengirim data ke bagian ${tujuan}?`)) {
                    const form = document.getElementById('formKirim');
                    form.action = url;
                    form.submit();
                }
            }
            //datatabelSales
            $(document).ready(function() {
                new DataTable('#viewPoSecHead');
            });

            document.addEventListener('DOMContentLoaded', (event) => {
            const customerInput = document.getElementById('customer');
            const namaProjectInput = document.getElementById('nama_project');
            const noSoInput = document.getElementById('no_so');
            const noteSalesInput = document.getElementById('note_sales');
            const submitButton = document.getElementById('submitBtn');

            // Function to check the validity of the input fields
            const validateInput = () => {
                const isCustomerValid = customerInput.value.trim() !== ''; // Memastikan Field Customer diisi
                const isNamaProjectValid = namaProjectInput.value.trim() !== ''; // Memastikan Field Nama Project diisi
                const isNoSoValid = noSoInput.value.length === 6; // Memastikan panjang No SO adalah 6 digit
                const isNoteSalesValid = noteSalesInput.value.trim() !== ''; // Memastikan Field Note Sales diisi (optional)

                // Mengaktifkan tombol jika semua input valid
                submitButton.disabled = !(isCustomerValid && isNamaProjectValid && isNoSoValid);
            };

            // Event listener untuk semua input
            customerInput.addEventListener('input', validateInput);
            namaProjectInput.addEventListener('input', validateInput);
            noSoInput.addEventListener('input', validateInput);
            noteSalesInput.addEventListener('input', validateInput);
        });

            function calcProfit(hargaAwal, hargaAkhir) {
                if (hargaAkhir > 0) {
                    let profit = ((hargaAkhir - hargaAwal) / hargaAkhir) * 100;
                    return {
                        value: profit.toFixed(2) + '%', // Mengembalikan hasil dengan format 2 decimal
                        isLow: profit < 25 // Menyimpan flag jika profit kurang dari 25%
                    };
                } else {
                    return {
                        value: 'N/A', // Tidak bisa dihitung jika harga akhir 0
                        isLow: false
                    };
                }
            }

            document.addEventListener('DOMContentLoaded', function() {
                $('#modalKeterangan').on('show.bs.modal', function(event) {
                    const button = $(event.relatedTarget);
                    const idPengajuan = button.data('id');
                    $(this).data('pengajuanId', idPengajuan);
                });

                document.getElementById('submitKirim').addEventListener('click', function() {
                    const keterangan = document.getElementById('keteranganInput').value.trim();
                    const jenisProcess = document.getElementById('jenisProcessInput').value.trim();
                    const fileInput = document.getElementById('fileInput');
                    const file = fileInput.files[0]; // Ambil file

                    const idPengajuan = $('#modalKeterangan').data('pengajuanId');
                    const route = '{{ route('kirimsubcont', '') }}' + '/' + idPengajuan;

                    if (!keterangan || !jenisProcess) {
                        alert('Silakan lengkapi keterangan dan jenis proses.');
                        return;
                    }

                    const formData = new FormData();
                    formData.append('keterangan', keterangan);
                    formData.append('jenis_process_subcont', jenisProcess);
                    if (file) {
                        formData.append('file', file);
                    }

                    fetch(route, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        $('#modalKeterangan').modal('hide');
                        alert(data.message);
                        location.reload();
                    })
                    .catch((error) => {
                        console.error('Error:', error);
                    });
                });
            });


            // Menjalankan perhitungan profit saat halaman dimuat
            document.addEventListener('DOMContentLoaded', function() {
                const rows = document.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const hargaAwalCell = row.cells[14]; // Kolom Harga Awal
                    const hargaAkhirCell = row.cells[15]; // Kolom Harga Akhir
                    const profitCell = row.cells[16]; // Kolom Profit

                    // Memastikan kolom ada sebelum digunakan
                    if (hargaAwalCell && hargaAkhirCell && profitCell) {
                        const hargaAwal = parseFloat(hargaAwalCell.innerText.replace(/[^0-9.-]+/g,"")) || 0; // Menghapus simbol
                        const hargaAkhir = parseFloat(hargaAkhirCell.innerText.replace(/[^0-9.-]+/g,"")) || 0; // Menghapus simbol
                        
                        // Menghitung profit dan menampilkannya
                        const profitResult = calcProfit(hargaAwal, hargaAkhir);
                        
                        // Mengatur teks dan warna berdasarkan profit
                        if (profitResult.isLow) {
                            profitCell.innerHTML = `<span style="color: red;">${profitResult.value}</span>`; // Tampilkan dalam warna merah
                        } else {
                            profitCell.innerText = profitResult.value; // Tampilkan hasil biasa
                        }
                    } else {
                        console.warn('Kolom tidak ditemukan pada baris ini:', row);
                    }
                });
            });
        </script>

    </main><!-- End #main -->
@endsection
