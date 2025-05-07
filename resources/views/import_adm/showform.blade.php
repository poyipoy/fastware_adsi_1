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

        .custom-table td {
            background-color: #fff;
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

    </style>
        
        
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
            
                <div class="table-responsive mt-4">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Status</th>
                                <th>File</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                1 => 'Ready To Ship',
                                2 => 'Proses Surveyor',
                                3 => 'Dokumen Final',
                                4 => 'Daftar Asuransi',
                                5 => 'Proses PPJK',
                                6 => 'E-Billing',
                                7 => 'Finish'
                            ] as $statusCode => $status)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><span>{{ $status }}</span></td>
                                    <td>
                                        @if ($admin->status == $statusCode && $statusCode != 7)
                                            <button class="btn-upload" data-bs-toggle="modal" data-bs-target="#uploadModal{{ $statusCode }}">
                                                <i class="fas fa-upload"></i> Upload Files
                                            </button>
                                        @endif
                                        <a href="#" class="btn-download" data-url="{{ route('downloadFiles', ['status' => $statusCode, 'adminId' => $admin->id]) }}">
                                            <i class="fas fa-download"></i> Download Files
                                        </a>
                                        
                                                                               
                                    </td>
                                    <td>
                                        @if ($admin->status == $statusCode)
                                            @if ($statusCode < 7)
                                                <form method="POST" action="{{ route('approve', $admin->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn-approve">Approve</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('reject', $admin->id) }}">
                                                @csrf
                                                <button type="submit" class="btn-reject">
                                                    @if ($statusCode == 1)
                                                        Reject (Delete)
                                                    @else
                                                        Reject (Decrement)
                                                    @endif
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
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
                                            <label for="no_vo_file" class="form-label">No VO</label>
                                            <input type="file" name="no_vo_file[]" id="no_vo_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
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
                                            <label for="no_aju_file" class="form-label">No Aju</label>
                                            <input type="file" name="no_aju_file[]" id="no_aju_file" class="form-control" accept=".pdf,.xlsx,.xls" multiple>
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

        $(document).on('click', '.btn-download', function (e) {
            e.preventDefault(); // Mencegah perilaku default (misalnya, mereload halaman)
            var downloadUrl = $(this).data('url'); // Mengambil URL route dari atribut data-url

            // Kirim permintaan AJAX untuk memeriksa apakah file ada
            $.ajax({
                url: downloadUrl, // Gunakan URL dari data-url
                type: 'GET',
                success: function (response) {
                    // Jika berhasil, tampilkan pesan keberhasilan
                    displayMessage(response.message, 'success');
                    
                    // Menyediakan URL untuk pengunduhan file satu per satu
                    if (response.download_urls && response.download_urls.length > 0) {
                        response.download_urls.forEach(function(url) {
                            window.location.href = url;  // Men-download file satu per satu
                        });
                    } else {
                        displayMessage('File tidak ditemukan.', 'error');
                    }
                },
                error: function (xhr) {
                    // Jika ada error, tampilkan pesan kesalahan
                    var response = xhr.responseJSON;
                    if (response && response.message) {
                        displayMessage(response.message, 'error');
                    } else {
                        displayMessage('Terjadi kesalahan saat mengunduh file.', 'error');
                    }
                    
                    // Jika ada file yang hilang
                    if (response && response.missing_files) {
                        displayMessage('File yang hilang: ' + response.missing_files.join(', '), 'error');
                    }
                }
            });
        });

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