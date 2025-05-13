@extends('layout')

@section('content')



<main id="main" class="main">
    <style>
        body {
            font-family: 'Cambria', serif;
        }

        .container {
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        #message.hidden {
            display: none; /* Sembunyikan elemen ketika kelas 'hidden' ada */
        }

        #message {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 10px;
            text-align: center;
            font-weight: bold;
            z-index: 1000;
            display: none; /* Atur untuk tidak tampil secara default */
        }



        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .fotext {
            font-family: 'Cambria', serif;
            font-size: 10pt;
            font-weight: bold;
        }

        table th {
            background-color: #f2f2f2;
        }

        .form-section {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }

        .form-section .form-group {
            flex: 1 1 15%;
            /* Adjust this value to control the width of each item */
            margin-right: 2px;
            margin-bottom: 15px;
        }

        .form-section label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }

        .add-column-button {
            margin-top: 15px;
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .swal2-popup {
            width: 300px;
            /* Mengatur lebar pop-up */
            font-size: 0.7rem;
            /* Mengatur ukuran font */
        }

        .swal2-title {
            font-family: 'Cambria', serif;
        }
        .form-value {
            width: 100%;
            border-radius: 8px;
            border: 1px solid #e5e7eb; /* Gray-200 border */
            background-color: #f9fafb; /* Gray-50 background */
            padding: 8px 12px; /* Padding untuk menambah ruang di dalam */
            color: #000000; /* Text Gray-900 */
            font-size: 0.875rem; /* Ukuran font 14px */
            font-weight: normal;
        }

        /* Tabel */
        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.875rem; /* 14px */
        }

        .custom-table th, .custom-table td {
            padding: 12px 20px;
            border-bottom: 1px solid #e5e7eb; /* Border gray untuk baris lainnya */
        }

        .custom-table th {
            background-color: #f3f4f6;
            color: #1f2937;
            font-weight: 600;
        }

        

        /* Border bawah tabel terakhir */
        .custom-table tr:last-child td {
            border-bottom: 2px solid black; /* Setel border bottom baris terakhir menjadi hitam */
        }

        /* Status Pill */
        .status-pill {
            display: inline-block;
            background-color: #e0f7fa; /* Light Blue */
            color: #00796b;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 20px;
        }

        /* Tombol Styling */
        .btn-upload, .btn-download, .btn-approve, .btn-reject {
            display: inline-block;
            padding: 6px 14px;
            font-size: 0.875rem; /* 14px */
            border-radius: 4px;
            text-align: center;
            margin-top: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        /* Button Colors */
        .btn-upload {
            background-color: #4CAF50;
            color: white;
            border: none;
        }

        .btn-upload:hover {
            background-color: #45a049;
        }

        .btn-download {
            background-color: #1976d2;
            color: white;
            border: none;
        }

        .btn-download:hover {
            background-color: #1565c0;
        }

        .btn-approve {
            background-color: #4CAF50;
            color: white;
            border: none;
        }

        .btn-approve:hover {
            background-color: #388e3c;
        }

        .btn-reject {
            background-color: #f44336;
            color: white;
            border: none;
        }

        .btn-reject:hover {
            background-color: #e53935;
        }

        /* Hover Effects */
        .custom-table tbody tr:hover {
            background-color: #f9f9f9;
        }

        .download-links {
            margin-top: 10px;
        }

        .btn-upload, .btn-approve, .btn-reject {
            margin-top: 5px;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
        }

        .btn-upload {
            background-color: #007bff;
            color: white;
        }

        .btn-approve {
            background-color: #28a745;
            color: white;
        }

        .btn-reject {
            background-color: #dc3545;
            color: white;
        }

        .download-links ul {
            list-style-type: none;
            padding-left: 0;
        }

        .download-links li {
            margin-bottom: 5px;
        }

        /* Styling container to use flexbox for alignment */
        .btn-container {
            display: flex;
            justify-content: space-between; /* Distribute space evenly between the left and right side */
            align-items: center; /* Align items vertically in the center */
            width: 100%; /* Make sure the container takes full width */
        }

        /* Styling for the left button (Kembali) */
        .btn-warning {
            margin-right: auto; /* Push the 'Kembali' button to the far left */
        }

        /* Styling for the right-side navigation buttons */
        .btn-navigation-right {
            display: flex; /* Use flexbox to align the next/previous buttons in a row */
            gap: 10px; /* Add some space between the buttons */
        }

        /* Styling for the navigation buttons */
        .btn-navigation {
            padding: 10px 20px; /* Add some padding for better clickability */
            font-size: 16px; /* Set font size */
            display: flex;
            align-items: center;
        }

        /* Optional: Add some margin to the left and right buttons for better spacing */
        .btn-navigation i {
            margin-right: 5px; /* Space between icon and text */
        }


    </style>
    <head>
        <meta name="csrf-token" content="{{ csrf_token() }}">
    </head>
    
        
        
            <div class="pagetitle">
            <h1>Import Documentation</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item active"><a href="{{ route('createadministration') }}">Import Administration</a></li>
                    <li class="breadcrumb-item active">formulir Import Administration</li>
                </ol>
            </nav>
        </div>

        <section class="section">
            <div class="card p-4"> <!-- Tambahkan padding di sini -->
                <div class="card-body">
                    <button class="btn btn-warning float-end" data-bs-toggle="modal" data-bs-target="#editModal">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    
                    <div class="form-section mt-3">
                        <div class="form-group mb-4">
                            <label class="form-label">No Document :</label>
                            <div class="form-value">{{ $admin->no_document }}</div>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label">Nama Supplier :</label>
                            <div class="form-value">{{ $admin->supplier }}</div>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label">No Invoice :</label>
                            <div class="form-value">{{ $admin->no_inv }}</div>
                        </div>
                        <div class="form-group mb-4">
                            <label class="form-label">Dibuat :</label>
                            <div class="form-value">{{ $admin->created_at }}</div>
                        </div>
                    </div>
                </div>

                <div class="btn-container">
                    <button class="btn btn-warning float-start" onclick="window.location.href='{{ route('createadministration') }}'">
                        <i class="fas fa-arrow-left"></i>  <!-- Icon panah kiri -->
                        Kembali
                    </button>
                
                    <div class="btn-navigation-right">
                        <button
                            type="button"
                            aria-label="Previous Page"
                            class="btn-navigation"
                            onclick="navigateInquiry('previous')"
                        >
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <button
                            type="button"
                            aria-label="Next Page"
                            class="btn-navigation"
                            onclick="navigateInquiry('next')"
                        >
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                
            
                <div class="table-responsive mt-4">
                    <div class="row">
                        <!-- Kolom Kiri: Status 1, 2, dan 3 -->
                        <div class="col-md-6">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Status</th>
                                        <th>File</th>
                                        <th>Partner</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([1 => 'Ready To Ship', 2 => 'Proses Surveyor', 3 => 'Dokumen Final'] as $statusCode => $status)
                                        <tr style="background-color: {{ $admin->status == $statusCode ? '#ffffff' : '#d3d3d3' }};">
                                            <td>{{ $loop->iteration }}</td>
                                            <td><span>{{ $status }}</span></td>
                                            <td>
                                                @if ($statusCode != 7)
                                                    <button class="btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal{{ $statusCode }}">
                                                        <i class="fas fa-upload"></i> Upload Files
                                                    </button>
                                                @endif
                                                @if ($statusCode == 2)
                                                    <span>No. VO : {{ $admin->no_vo }}</span>
                                                @endif
                
                                                @if ($statusCode != 7)
                                                    <div id="download-links-{{ $statusCode }}" class="download-links" style="display:block;">
                                                        <h5>File tersedia:</h5>
                                                        <ul id="download-file-list-{{ $statusCode }}"></ul>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($statusCode == 1 && (!empty($admin->pl) || !empty($admin->inv)))
                                                    @if ($admin->purchase)
                                                        <div>{{ $admin->purchase->name }}</div>
                                                    @endif
                                                @elseif ($statusCode == 2 && (!empty($admin->novo_file) || !empty($admin->ls)))
                                                    @if ($admin->admin)
                                                        <div>{{ $admin->purchase->name }}</div>
                                                    @endif
                                                @elseif ($statusCode == 3 && (!empty($admin->bl) || !empty($admin->inv_final) || !empty($admin->pl_final) || !empty($admin->form_e)))
                                                    @if ($admin->purchase)
                                                        <span>{{ $admin->purchase->name }}</span>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($admin->status == $statusCode)
                                                    @if ($statusCode < 7)
                                                        <form method="POST" action="{{ route('approve', $admin->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn-approve">Submit</button>
                                                        </form>
                                                    @endif
                                                    @if ($statusCode == 1)
                                                    @else
                                                        <form method="POST" action="{{ route('reject', $admin->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn-reject">
                                                                Reject (Decrement)
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!-- Baris untuk Last Update -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div><strong>Last Update (Purchase):</strong> {{ $admin->purchase_updated_at }}</div>
                                </div>
                            </div>
                        </div>
                
                        <!-- Kolom Kanan: Status 4, 5, 6, 7 -->
                        <div class="col-md-6">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Status</th>
                                        <th>File</th>
                                        <th>Partner</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ([4 => 'Daftar Asuransi', 5 => 'Proses PPJK', 6 => 'E-Billing', 7 => 'Finish'] as $statusCode => $status)
                                        <tr style="background-color: {{ $admin->status == $statusCode ? '#ffffff' : '#d3d3d3' }};">
                                            <td>{{ $loop->iteration }}</td>
                                            <td><span>{{ $status }}</span></td>
                                            <td>
                                                @if ($statusCode != 7)
                                                    <button class="btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal{{ $statusCode }}">
                                                        <i class="fas fa-upload"></i> Upload Files
                                                    </button>
                                                @endif
                                                @if ($statusCode == 2)
                                                    <span>No. VO : {{ $admin->no_vo }}</span>
                                                @endif
                
                                                @if ($statusCode != 7)
                                                    <div id="download-links-{{ $statusCode }}" class="download-links" style="display:block;">
                                                        <h5>File tersedia:</h5>
                                                        <ul id="download-file-list-{{ $statusCode }}"></ul>
                                                    </div>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($statusCode == 4 && !empty($admin->asuransi))
                                                    @if ($admin->admin)
                                                        <div>{{ $admin->admin->name }}</div>
                                                    @endif
                                                @elseif ($statusCode == 5 && !empty($admin->pib_final))
                                                    @if ($admin->admin)
                                                        <div>{{ $admin->admin->name }}</div>
                                                    @endif
                                                @elseif ($statusCode == 6 && !empty($admin->e_bill))
                                                    @if ($admin->admin)
                                                        <div>{{ $admin->admin->name }}</div>
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($admin->status == $statusCode)
                                                    @if ($statusCode < 7)
                                                        <form method="POST" action="{{ route('approve', $admin->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn-approve">Submit</button>
                                                        </form>
                                                    @endif
                                                    @if ($statusCode == 1)
                                                    @else
                                                        <form method="POST" action="{{ route('reject', $admin->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn-reject">
                                                                Reject (Decrement)
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <!-- Baris untuk Last Update -->
                            <div class="row">
                                <div class="col-md-6">
                                    <div><strong>Last Update (Admin):</strong> {{ $admin->admin_updated_at }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                
            </div>
            

            @foreach ([
                1 => 'Ready To Ship',
                2 => 'Proses Surveyor',
                3 => 'Dokumen Final',
                4 => 'Daftar Asuransi',
                5 => 'Proses PPJK',
                6 => 'E-Billing',
                7 => 'Finish'
            ] as $statusCode => $status)
                <div class="modal fade" id="uploadModal{{ $statusCode }}" tabindex="-1" aria-labelledby="uploadModalLabel{{ $statusCode }}" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form id="uploadForm{{ $statusCode }}" method="POST" action="{{ route('uploadFiles', $admin->id) }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="status" value="{{ $statusCode }}">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="uploadModalLabel{{ $statusCode }}">Upload Files for {{ $status }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    @if ($statusCode == 1) <!-- Ready To Ship -->
                                        <div class="mb-3">
                                            <label for="pl_file" class="form-label">Packing List</label>
                                            <input type="file" name="pl_file[]" id="pl_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                        <div class="mb-3">
                                            <label for="inv_file" class="form-label">Invoice</label>
                                            <input type="file" name="inv_file[]" id="inv_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                    @elseif ($statusCode == 2) <!-- Proses Surveyor -->
                                        <div class="mb-3">
                                            <label for="no_vo_text" class="form-label">No VO</label>
                                            <input type="text" name="no_vo_text" id="no_vo_text" class="form-control" placeholder="Enter No VO Text">
                                        </div>    
                                        <div class="mb-3">
                                            <label for="no_vo_file" class="form-label">File VO</label>
                                            <input type="file" name="no_vo_file[]" id="no_vo_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                        <div class="mb-3">
                                            <label for="no_vr_text" class="form-label">No VR</label>
                                            <input type="text" name="no_vr_text" id="no_vr_text" class="form-control" placeholder="Enter No VR Text">
                                        </div>
                                        <div class="mb-3">
                                            <label for="ls_file" class="form-label">LS</label>
                                            <input type="file" name="ls_file[]" id="ls_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                    @elseif ($statusCode == 3) <!-- Dokumen Final -->
                                        <div class="mb-3">
                                            <label for="bl_file" class="form-label">BL</label>
                                            <input type="file" name="bl_file[]" id="bl_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                        <div class="mb-3">
                                            <label for="inv_final_file" class="form-label">Invoice Final</label>
                                            <input type="file" name="inv_final_file[]" id="inv_final_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                        <div class="mb-3">
                                            <label for="pl_final_file" class="form-label">Packing List Final</label>
                                            <input type="file" name="pl_final_file[]" id="pl_final_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                        <div class="mb-3">
                                            <label for="form_e_file" class="form-label">Form-E</label>
                                            <input type="file" name="form_e_file[]" id="form_e_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                    @elseif ($statusCode == 4) <!-- Daftar Asuransi -->
                                        <div class="mb-3">
                                            <label for="asuransi_file" class="form-label">Asuransi</label>
                                            <input type="file" name="asuransi_file[]" id="asuransi_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                        @elseif ($statusCode == 5) <!-- Proses PPJK -->
                                        <div class="mb-3">
                                            <label for="no_aju_text" class="form-label">No Aju</label>
                                            <input type="text" name="no_aju_text" id="no_aju_text" class="form-control" placeholder="Enter No Aju">
                                        </div>
                                        <div class="mb-3">
                                            <label for="pib_final_file" class="form-label">PIB Final</label>
                                            <input type="file" name="pib_final_file[]" id="pib_final_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>                                    
                                    @elseif ($statusCode == 6) <!-- E-Billing -->
                                        <div class="mb-3">
                                            <label for="e_bill_file" class="form-label">E-Bill</label>
                                            <input type="file" name="e_bill_file[]" id="e_bill_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
                                        </div>
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Upload</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Edit -->
                <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST" action="{{ route('updateAdmin', $admin->id) }}">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editModalLabel">Edit Supplier and Invoice</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="supplier" class="form-label">Nama Supplier</label>
                                        <input type="text" class="form-control" id="supplier" name="supplier" value="{{ $admin->supplier }}" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="no_inv" class="form-label">No Invoice</label>
                                        <input type="text" class="form-control" id="no_inv" name="no_inv" value="{{ $admin->no_inv }}" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            @endforeach

            
            <div id="alertContainer"></div>
            
        </section>
        
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        




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

function navigateInquiry(direction) {
    let currentAdminId = {{ $admin->id }};
    const maxId = {{ $maxAdminId }};

    let targetAdminId;
    if (direction === 'next') {
        targetAdminId = currentAdminId === maxId ? 1 : currentAdminId + 1;
    } else if (direction === 'previous') {
        targetAdminId = currentAdminId === 1 ? maxId : currentAdminId - 1;
    }

    // Mengarahkan ke URL baru dan me-reload halaman
    let newUrl = '{{ route("dokumenadministration", "") }}' + '/' + targetAdminId;
    window.location.href = newUrl; // Ini akan memuat ulang halaman dengan URL baru
}




        document.addEventListener('DOMContentLoaded', async () => {
            const adminId = '{{ $admin->id }}'; // Ambil adminId dari variabel yang ada di halaman atau dari sesi

            // Ambil data file berdasarkan adminId
            const files = await fetchFiles(adminId);

            // Render data file ke dalam UI
            renderFiles(files);
        });

        async function fetchFiles(adminId) {
            try {
                // Menggunakan route Blade untuk menghasilkan URL
                const url = `{{ route('downloadFiles', ['adminId' => '__ADMIN_ID__']) }}`.replace('__ADMIN_ID__', adminId);
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success && data.filesByStatus) {
                    return data.filesByStatus; // Mengembalikan file berdasarkan status
                } else {
                    return {};
                }
            } catch (error) {
                console.error('Error fetching files:', error);
                return {};
            }
        }

        // Fungsi untuk merender file berdasarkan status
function renderFiles(filesByStatus) {
    // Iterasi untuk setiap status dan tampilkan file sesuai status
    for (const statusCode in filesByStatus) {
        const files = filesByStatus[statusCode]; // Ambil file berdasarkan status

        // Cari elemen dengan ID yang sesuai untuk status
        const list = document.getElementById(`download-file-list-${statusCode}`);

        if (list) {
            list.innerHTML = ''; // Bersihkan daftar sebelumnya

            if (files.length === 0) {
                list.innerHTML = '<li>Tidak ada file tersedia</li>';
            } else {
                // Menampilkan setiap file yang diterima
                files.forEach(file => {
                    const listItem = document.createElement('li');
                    const link = document.createElement('a');
                    link.textContent = file.name;
                    link.href = file.url;
                    link.classList.add('file-link');
                    listItem.id = `file-${file.name}`; // Menambahkan ID pada setiap file

                    // Menambahkan tombol hapus
                    const deleteButton = document.createElement('button');
                    deleteButton.textContent = 'X';
                    deleteButton.classList.add('delete-file-button');
                    deleteButton.setAttribute('data-file-name', file.name);
                    deleteButton.setAttribute('data-status-code', statusCode);

                    // Menangani klik untuk download
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const tempLink = document.createElement('a');
                        tempLink.href = file.url;
                        tempLink.download = file.name;
                        document.body.appendChild(tempLink);
                        tempLink.click();
                        document.body.removeChild(tempLink);
                    });

                    // Menangani klik untuk hapus
                    deleteButton.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const fileName = e.target.getAttribute('data-file-name');
                        const statusCode = e.target.getAttribute('data-status-code');
                        const isConfirmed = confirm(`Apakah Anda yakin ingin menghapus file ${fileName}?`);

                        if (isConfirmed) {
                            // Menghapus file dari server dan halaman
                            await deleteFile(fileName, statusCode);
                            listItem.remove();  // Menghapus elemen file dari halaman setelah respons sukses
                        }
                    });

                    // Menambahkan link dan tombol hapus ke dalam list item
                    listItem.appendChild(link);
                    listItem.appendChild(deleteButton);
                    list.appendChild(listItem);
                });
            }
        }
    }
}

// Fungsi untuk menghapus file
async function deleteFile(fileName, statusCode) {
    try {
        // Mengambil CSRF token dari meta tag
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Menggunakan route Blade untuk menghasilkan URL
        const url = "{{ route('deleteFile') }}";

        // Mengirimkan request ke server untuk menghapus file
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token // Menambahkan CSRF token ke header
            },
            body: JSON.stringify({
                fileName: fileName,
                statusCode: statusCode
            })
        });

        const data = await response.json();

        // Menangani respons dari server
        if (data.success) {
            alert('File berhasil dihapus!');
        } else {
            alert('Gagal menghapus file!');
        }
    } catch (error) {
        console.error('Error deleting file:', error);
        alert('Terjadi kesalahan saat menghapus file!');
    }
}



        function uploadfile() {
            let formData = new FormData(document.getElementById('uploadForm'));

            // Validasi minimal ada file
            if (!formData.getAll('invoice_file[]').length && !formData.getAll('packing_file[]').length) {
                alert("Pilih file Invoice dan Packing List terlebih dahulu.");
                return;
            }

            $.ajax({
                url: "{{ route('import.purchaseimport') }}", // Pastikan rutenya benar
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                success: function(response) {
                    $('#alertContainer').html(`
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            ${response.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                    setTimeout(function() {
                        location.reload(); // Reload setelah sukses
                    }, 1500);
                },
                error: function(xhr) {
                    $('#alertContainer').html(`
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            Terjadi kesalahan: ${xhr.responseText}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `);
                }
            });
        }
        function displayMessage(message, type) {
            // Menampilkan pesan menggunakan alert() sebagai pengganti div notifikasi
            if (type === 'error') {
                alert('Error: ' + message); // Menampilkan pesan kesalahan
            } else if (type === 'success') {
                alert('Success: ' + message); // Menampilkan pesan keberhasilan
            }
        }

    </script>
</main>



@endsection