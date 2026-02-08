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
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-1" id="overviewTableInventory">
                                    <thead>
                                        <tr>
                                            <th scope="col">No</th>
                                            <th scope="col">Create By</th>
                                            <th scope="col">Reference</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Supplier</th>
                                            <th scope="col">Customer</th>
                                            <th scope="col">State</th>
                                            <th scope="col">Ship-to</th>
                                            <th scope="col">Last Update</th>
                                            <th scope="col">Est.Date</th>
                                            <th scope="col">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($inquiries as $index => $inquiry)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $inquiry->create_by }}</td>
                                                <td>{{ $inquiry->kode_inquiry }}</td>
                                                <td>{{ $inquiry->loc_imp }}</td>
                                                <td>{{ $inquiry->supplier }}</td>
                                                <td>{{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
                                                </td>
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
                                                    ];
                                                @endphp
                                                <td>
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
                                                        {{ $inquiry->status == 8 ? 'btn-custom-rejected' : '' }}">
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
                                                <td>
                                                    @php
                                                        // Ambil update progress jika ada
                                                        $progress = App\Models\TrxDboProgPurchase::where(
                                                            'inquiry_id',
                                                            $inquiry->id,
                                                        )
                                                            ->latest()
                                                            ->first();
                                                    @endphp
                                                    {{ $progress ? $progress->description : 'No updates yet' }}
                                                </td>

                                                <td>{{ $inquiry->est_date }}</td>
                                                <td>
                                                    <a href="#" class="btn btn-warning btn-sm"
                                                        onclick="showInquiry({{ $inquiry->id }}); return false;">
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
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
            function showInquiry(id) {
                window.location.href = '{{ route('formulirInquiry', '') }}/' + id;
            }

            function showInquiry(id) {
                // Tampilkan detail inquiry dan tambahkan parameter query
                window.location.href = '{{ route('showFormSS', '') }}/' + id + '?source=approval';
            }
        </script>

        <script>
            function approveInventory(id) {
                $.ajax({
                    url: '{{ route('approveInventory', '') }}/' + id,
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
                    url: '{{ route('rejectInventory', '') }}/' + id,
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
