@extends('layout')

@section('content')
    <main id="main" class="main">

        <style>
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
                margin-top: 1.5rem;
                /* Pusatkan tabel */
                padding: 1rem;
                /* Padding di sekeliling tabel */
                background-color: #00346b;
                /* Warna latar belakang */
                border-radius: 8px;
                /* Sudut membulat */
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
                /* Bayangan untuk efek kedalaman */
            }

            .table-1 th {
                background-color: #002144;
                /* Warna latar belakang */
                border-radius: 8px;
                /* Sudut membulat */
                color: #f1f1f1;
                font-size: 13pt;
                /* box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); */
                /* Bayangan untuk efek kedalaman */
            }

            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.9rem;
                /* Ukuran font lebih kecil */
            }

            th {
                background-color: #007bff;
                /* Warna header tabel */
                color: white;
                /* Warna teks di header */
                padding: 0.75rem;
                /* Padding untuk header */
                /* border: 1px solid #858585; */
                font-family: 'Cambria', serif;
            }

            td {
                padding: 0.75rem;
                /* Padding untuk sel */
                /* border: 1px solid #858585; */
                /* Border antara sel */
                transition: background-color 0.3s;
                /* Transisi halus saat hover */
                font-family: 'Cambria', serif;
            }

            td:hover {
                background-color: #f1f1f1;
                /* Efek hover pada sel */
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

            <style>.btn-custom-draft {
                background-color: #6c757d;
                /* atau warna lain yang Anda inginkan */
                color: white;
            }

            .btn-custom-open {
                background-color: #008f64;
                /* atau warna lain */
                color: white;
            }

            .btn-custom-approve-dept {
                background-color: #ff7707;
                /* Warna kuning bisa jadi untuk approve ka.dept */
                color: black;
            }

            .btn-custom-approve-sie {
                background-color: #17a2b8;
                /* Warna biru bisa untuk approve ka.sie */
                color: white;
            }

            .btn-custom-in-progress {
                background-color: #fbff07;
                /* Warna kuning tua untuk on progress */
                color: rgb(0, 0, 0);
            }

            .btn-custom-finished {
                background-color: #00346b;
                /* Warna biru untuk finished */
                color: white;
            }

            .btn-custom-rejected {
                background-color: #dc3545;
                /* Merah untuk rejected */
                color: white;
            }

            .btn-custom-show {
                background-color: #f300a2;
                /* Merah untuk show form */
            }

            .btn-custom-show:hover {
                background-color: #5e0051;
                /* Merah untuk show form */
                color: #f7f7f7;
            }

            .btn-custom-inventory {
                background-color: #00d9ffbb;
                /* Merah untuk show form */
                color: #000000;
            }

            .btn-custom-inventory:hover {
                background-color: #00a4f0;
                /* Merah untuk show form */
                color: #000000;
            }
        </style>

        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title font-sii text-center">Overview Purchase</h5>
                </div>

                {{-- Overview All 1,2,3,4 --}}

                <section class="section">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-1" id="overviewTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th class="text-center">Create By</th>
                                            <th class="text-center">Reference</th>
                                            <th class="text-center">Category</th>
                                            <th class="text-center">Supplier</th>
                                            <th class="text-center">Customer</th>
                                            <th>Status</th>
                                            <th>Last Update</th>
                                            <th>Est. Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($draftInquiries as $index => $inquiry)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td class="text-center">{{ $inquiry->create_by }}</td>
                                                <td class="text-center">{{ $inquiry->kode_inquiry }}</td>
                                                <td class="text-center">{{ $inquiry->loc_imp }}</td>
                                                <td class="text-center">{{ $inquiry->supplier }}</td>
                                                <td class="text-center">
                                                    {{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
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
                                                        {{ $inquiry->status == 8 ? 'btn-custom-inventory' : '' }}
                                                        {{ $inquiry->status == 9 ? 'btn-custom-confirm-purchasing' : '' }}">
                                                        {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                    </button>
                                                </td>
                                                <td>
                                                    {{ $inquiry->last_update ?? 'No updates yet' }}
                                                </td>
                                                <td>{{ $inquiry->est_date }}</td>
                                                {{-- <td class="text-center"> --}}
                                                {{-- <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressHistory({{ $inquiry->id }}); return false;">
                                                        History Progress
                                                    </a> --}}
                                                {{-- </td> --}}
                                                <td>
                                                    {{-- <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressModal1({{ $inquiry->id }}); return false;"
                                                        title="Show Detail Inquiry">
                                                        <i class="bi bi-info-square-fill"></i>
                                                    </a> --}}
                                                    <a href="#" class="btn btn-custom-show btn-sm"
                                                        onclick="showInquiry({{ $inquiry->id }}); return false;"
                                                        title="Show Form">
                                                        <i class="bi bi-eye-fill"></i>
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
                {{-- Overview All 5,6,7 --}}
                <section class="section">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-1" id="overviewTable">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th class="text-center">Create By</th>
                                            <th class="text-center">Reference</th>
                                            <th class="text-center">Category</th>
                                            <th class="text-center">Supplier</th>
                                            <th class="text-center">Customer</th>
                                            <th>Status</th>
                                            <th>Last Update</th>
                                            <th>Est. Date</th>
                                            <th style="width: 250px">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($inquiries as $index => $inquiry)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td class="text-center">{{ $inquiry->create_by }}</td>
                                                <td class="text-center">{{ $inquiry->kode_inquiry }}</td>
                                                <td class="text-center">{{ $inquiry->loc_imp }}</td>
                                                <td class="text-center">{{ $inquiry->supplier }}</td>
                                                <td class="text-center">
                                                    {{ $inquiry->customer ? $inquiry->customer->name_customer : 'N/A' }}
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
                                                        {{ $inquiry->status == 8 ? 'btn-custom-inventory' : '' }}
                                                        {{ $inquiry->status == 9 ? 'btn-custom-confirm-purchasing' : '' }}">
                                                        {{ $statusDescriptions[$inquiry->status] ?? 'Unknown' }}
                                                    </button>
                                                </td>
                                                <td>
                                                    {{ $inquiry->last_update ?? 'No updates yet' }}
                                                </td>
                                                {{-- <td class="text-center"> --}}
                                                {{-- <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressHistory({{ $inquiry->id }}); return false;">
                                                        History Progress
                                                    </a> --}}
                                                {{-- </td> --}}
                                                <td>{{ $inquiry->est_date }}</td>
                                                <td>
                                                    {{-- Jika status belum selesai --}}
                                                    <a href="#" class="btn btn-custom-in-progress btn-sm"
                                                        onclick="confirmPurchasing({{ $inquiry->id }}); return false;"
                                                        title="Confirm Form">
                                                        <i class="bi bi-hand-index-thumb-fill"></i>
                                                    </a>
                                                    {{-- Jika status belum selesai --}}
                                                    <a href="#" class="btn btn-primary btn-sm"
                                                        onclick="finishInquiry({{ $inquiry->id }}); return false;"
                                                        title="Finish Inquiry">
                                                        <i class="bi bi-emoji-sunglasses-fill"></i>
                                                    </a>
                                                    {{-- <a href="#" class="btn btn-custom-in-progress btn-sm"
                                                        onclick="showProgressModal({{ $inquiry->id }}); return false;"
                                                        title="Update Progress">
                                                        <i class="bi bi-chat-square-text-fill"></i>
                                                    </a> --}}
                                                    <a href="#" class="btn btn-info btn-sm"
                                                        onclick="showProgressModal1({{ $inquiry->id }}); return false;"
                                                        title="Show Detail Inquiry">
                                                        <i class="bi bi-info-square-fill"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-custom-show btn-sm"
                                                        onclick="showInquiry({{ $inquiry->id }}); return false;"
                                                        title="Show Form">
                                                        <i class="bi bi-eye-fill"></i>
                                                    </a>
                                                    <a class="btn btn-primary btn-sm"
                                                        onclick="showCombinedModal({{ $inquiry->id }})">
                                                        <i class="bi bi-pencil"></i>
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

                <!-- Modal for Viewing Progress History -->
                <div class="modal fade" id="progressHistoryModal1" tabindex="-1"
                    aria-labelledby="progressHistoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="progressHistoryModalLabel">History Progress</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
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

                <!-- Modal for Update Progress, Supplier, and Est. Date -->
                <div class="modal fade" id="combinedModal" tabindex="-1" aria-labelledby="combinedModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="combinedModalLabel">Update Progress / Edit Supplier</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                    aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="combinedForm">
                                    @csrf
                                    <input type="hidden" id="inquiryId" name="inquiry_id">

                                    <!-- Update Progress Section -->
                                    <div class="mb-3">
                                        <label for="progressDescription" class="form-label">Progress Description</label>
                                        <textarea class="form-control" id="progressDescription" name="progress_description" rows="3" required></textarea>
                                    </div>

                                    <!-- Edit Supplier Section -->
                                    <div class="mb-3">
                                        <label for="editSupplier" class="form-label">Supplier</label>
                                        <select class="form-select" id="editSupplier" name="supplier" required>
                                            <option value="">Select Supplier</option>
                                            <option value="PT. SINAR PUTRA METALINDO">PT. SINAR PUTRA METALINDO</option>
                                            <option value="PT. TRUST STEEL INDO">PT. TRUST STEEL INDO</option>
                                            <!-- Tambahkan opsi lain jika diperlukan -->
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="estDate" class="form-label">Est. Date</label>
                                        <input type="date" class="form-control" id="estDate" name="est_date">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary"
                                    onclick="submitCombinedForm()">Submit</button>
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

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
            function showInquiry(id) {
                window.location.href = '{{ route('formulirInquiry', '') }}/' + id;
            }

            function showInquirySS(id) {
                // Tampilkan detail inquiry dan tambahkan parameter query
                window.location.href = '{{ route('showFormSS', '') }}/' + id + '?source=approval';
            }
        </script>

        <script>
            function showProgressHistory(inquiryId) {
                // Aksi yang dilakukan saat mengklik History Progress
                window.location.href = '{{ route('progressHistory') }}/' + inquiryId; // Route yang perlu dibuat
            }

            function showProgressModal(id) {
                $('#progressInquiryId').val(id); // Simpan ID ke dalam modal
                $('#progressModal').modal('show'); // Tampilkan modal
            }

            function showProgressModal1(id) {
                // Tampilkan modal
                $('#progressHistoryModal1').modal('show');

                // Ambil data progress untuk inquiry tersebut
                $.ajax({
                    url: '{{ route('progressHistory') }}/' + id, // Pastikan route-nya sesuai
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
                        Swal.fire('Error!', 'An error occurred while fetching progress history.', 'error');
                    }
                });
            }

            function submitProgress() {
                var inquiryId = $('#progressInquiryId').val();
                var progressDescription = $('#progressDescription').val();

                $.ajax({
                    url: '{{ route('storeProgressPurchase') }}', // Route untuk menyimpan progress
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        inquiry_id: inquiryId,
                        progress_description: progressDescription
                    },
                    success: function(response) {
                        Swal.fire('Success!', 'Progress updated successfully.', 'success');
                        $('#progressModal').modal('hide'); // Tutup modal
                        location.reload(); // Reload halaman
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while updating progress.', 'error');
                    }
                });
            }

            // Function to update the overview table dynamically
            function updateOverviewTable(inquiry) {
                // Logic to update the relevant row in the overview table
                const tableBody = document.getElementById('overviewTable').getElementsByTagName('tbody')[0];

                // Find the row based on inquiry ID (assumed to be already existing in the table)
                let rowToUpdate = Array.from(tableBody.rows).find(row => {
                    return row.cells[1].textContent.includes(inquiry.kode_inquiry);
                });

                if (rowToUpdate) {
                    // Update the specific cells as necessary
                    rowToUpdate.cells[5].textContent = 'On Progress'; // Assuming the 6th cell for the status
                    // Other cells can be updated as needed
                } else {
                    // Optional: If row is not found, you might handle this to log or notify that update failed
                    console.warn('Row not found for inquiry update.');
                }
            }

            function confirmPurchasing(id) {
                // Tampilkan konfirmasi sebelum mengirim permintaan
                if (confirm("Are you sure you want to confirm the purchase?")) {
                    $.ajax({
                        url: '{{ route('confirmPurchase') }}/' + id,
                        method: 'POST',
                        data: {
                            '_token': '{{ csrf_token() }}' // CSRF token
                        },
                        success: function(response) {
                            // Tampilkan alert dengan SweetAlert saat berhasil
                            Swal.fire('Success!', response.success, 'success').then(() => {
                                location.reload(); // Reload halaman
                            });
                        },
                        error: function(xhr) {
                            console.error(xhr.responseText);
                            Swal.fire('Error!', xhr.responseJSON.error, 'error'); // Tampilkan pesan error
                        }
                    });
                }
            }

            function finishInquiry(id) {
                // Menampilkan konfirmasi sebelum melanjutkan
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will mark the inquiry as finished.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, finish it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika pengguna mengkonfirmasi, lanjutkan dengan AJAX
                        $.ajax({
                            url: '{{ route('finishInquiry') }}/' + id, // Route untuk finishing inquiry
                            method: 'POST',
                            data: {
                                '_token': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Success!', 'Inquiry marked as finished.', 'success').then(() => {
                                    location.reload(); // Reload halaman untuk melihat update
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                                Swal.fire('Error!', 'An error occurred while finishing the inquiry.',
                                    'error');
                            }
                        });
                    }
                });
            }
        </script>

    </main>
@endsection
