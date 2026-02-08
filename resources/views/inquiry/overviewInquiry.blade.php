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
                    <h5 class="card-title font-sii text-center">Overview Inquiry Order</h5>
                </div>

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
                                            <th>Ship-to</th>
                                            <th>Last Update</th>
                                            <th>Est. Date</th>
                                            <th>Source PR</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Modal for Edit Supplier, Last Update, and Est. Date -->
                    <div class="modal fade" id="editDataModal" tabindex="-1" aria-labelledby="editDataModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title testfont" id="editDataModalLabel">Edit Inquiry Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="editDataForm">
                                        @csrf
                                        <input type="hidden" id="inquiryId" name="inquiry_id">

                                        <!-- Field Supplier (Dropdown List) -->
                                        <div class="mb-3">
                                            <label for="supplier" class="form-label">Supplier</label>
                                            <select class="form-select" id="supplier" name="supplier" required>
                                                <option value="">Select Supplier</option>
                                                <option value="PT. SINAR PUTRA METALINDO">
                                                    PT. SINAR PUTRA METALINDO</option>
                                                <option value="PT. TRUST STEEL INDO">
                                                    PT. TRUST STEEL INDO</option>
                                                <option value="PT. LUKWINDO NUSA DWIPA">
                                                    PT. LUKWINDO NUSA DWIPA</option>
                                                <option value="CV. BAJA MAKMUR">
                                                    CV. BAJA MAKMUR</option>
                                                <option value="CV. REIHAI ABADI METAL INDONESIA">
                                                    CV. REIHAI ABADI METAL INDONESIA</option>
                                                <option value="PT. SAMUDRA BAJA NUSANTARA">
                                                    PT. SAMUDRA BAJA NUSANTARA</option>
                                                <option value="PT. SURYA SEJAHTERA METALINDO LESTARI">
                                                    PT. SURYA SEJAHTERA METALINDO LESTARI</option>
                                                <option value="CV. DIMA RAMA SAKTI">
                                                    CV. DIMA RAMA SAKTI</option>
                                                <option value="METAL JAYA UTAMA">
                                                    METAL JAYA UTAMA</option>
                                                <!-- Tambahkan opsi lain jika diperlukan -->
                                            </select>
                                        </div>

                                        <!-- Field Last Update (Free Text) -->
                                        <div class="mb-3">
                                            <label for="progress" class="form-label">Last Update</label>
                                            <input type="text" class="form-control" id="progress" name="progress"
                                                required>
                                        </div>

                                        <!-- Field Est. Date (Date Picker) -->
                                        <div class="mb-3">
                                            <label for="estDate" class="form-label">Est. Date <span>
                                                    (Incoming Shipment)</span></label>
                                            <input type="date" class="form-control" id="estDate" name="est_date"
                                                required>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary btn-sm"
                                        onclick="submitEditDataForm()">Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>

                                        <!-- Modal Edit Data -->
                    <div class="modal fade" id="editDataModal1" tabindex="-1" aria-labelledby="editDataModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editDataModalLabel">Edit Inquiry</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="editInquiryForm">
                                        @csrf
                                        <input type="hidden" id="inquiryId" name="inquiryId"> <!-- ID untuk inquiry yang akan diedit -->
                                        <div class="mb-3">
                                            <label for="source_pr" class="form-label">Source PR</label>
                                            <textarea class="form-control" id="source_pr" name="source_pr" placeholder="Masukkan source pr" required></textarea>
                                            <div class="form-text"></div>
                                        </div>
                                    </form>                                                                    
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="saveData()">Submit</button>
                                </div>
                            </div>
                        </div>
                    </div>



                </section>
            </div>
        </section>


        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

        <script>
            $(document).ready(function() {
                // Hover function for dropdowns
                $('.nav-item.dropdown').hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });

            jQuery(function($) {
                const escapeHtml = (value) => {
                    if (value === null || value === undefined) {
                        return '';
                    }
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                };

                const table = $('#overviewTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('overviewInquiry') }}',
                        data: function(params) {
                            params.format = 'json';
                        }
                    },
                    columns: [
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { data: 'create_by', name: 'create_by', defaultContent: '-' },
                        { data: 'kode_inquiry', name: 'kode_inquiry', defaultContent: '-' },
                        { data: 'loc_imp', name: 'loc_imp', defaultContent: '-' },
                        { data: 'supplier', name: 'supplier', defaultContent: '-' },
                        { data: 'customer_name', name: 'customer.name_customer', defaultContent: 'N/A' },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function(data, type, row) {
                                const css = row.status_class || 'btn-light';
                                const label = row.status_label || 'Unknown';
                                return '<span class="btn btn-sm ' + css + '">' + label + '</span>';
                            }
                        },
                        {
                            data: 'ship_to',
                            name: 'ship_to',
                            orderable: false,
                            searchable: false,
                            render: function(data, type) {
                                if (type === 'display') {
                                    return data || '--- No Shipping Options ---';
                                }
                                return data;
                            }
                        },
                        { data: 'last_update', name: 'last_update', defaultContent: 'No updates yet' },
                        { data: 'est_date', name: 'est_date', defaultContent: '-' },
                        {
                            data: 'source_pr',
                            name: 'source_pr',
                            render: function(data, type) {
                                if (!data) {
                                    return type === 'display' ? '-' : '';
                                }
                                if (type === 'display') {
                                    return escapeHtml(data).replace(/\n/g, '<br>');
                                }
                                return data;
                            }
                        },
                        { data: 'actions', orderable: false, searchable: false, defaultContent: '' }
                    ],
                    order: [[2, 'desc']],
                    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]]
                });

                window.overviewTable = table;
            });
        </script>



        <script>
            
            document.getElementById('source_pr').addEventListener('keydown', function(event) {
                if (event.ctrlKey && event.key === 'Enter') {
                    // Mencegah aksi default jika Ctrl + Enter ditekan
                    // Agar baris baru tetap ditambahkan
                    event.preventDefault();
                    
                    // Menambahkan baris baru dalam textarea
                    var cursorPos = this.selectionStart; // Menyimpan posisi kursor saat ini
                    var textBefore = this.value.substring(0, cursorPos); // Bagian sebelum kursor
                    var textAfter = this.value.substring(cursorPos); // Bagian setelah kursor
                    
                    // Menambahkan line break di posisi kursor
                    this.value = textBefore + '\n' + textAfter;

                    // Mengatur posisi kursor setelah baris baru
                    this.selectionStart = this.selectionEnd = cursorPos + 1;
                }
            });


            function showInquiry(id) {
                // Tampilkan detail inquiry dan tambahkan parameter query
                window.location.href = '{{ route('showFormSS', '') }}/' + id + '?source=approval';
            }

            function showEditDataModal1(id, source_pr) {
                // Pastikan ID yang benar dimasukkan ke input hidden
                document.getElementById('inquiryId').value = id;  // Ini akan mengisi input hidden dengan id

                // Masukkan 4 digit angka ke field input
                document.getElementById('source_pr').value = source_pr;

                new bootstrap.Modal(document.getElementById('editDataModal1')).show();
            }

            function saveData() {
                const form = document.getElementById('editInquiryForm');
                const formData = new FormData(form);

                // Pastikan 'inquiryId' diganti menjadi 'id' saat dikirim
                formData.set('id', document.getElementById('inquiryId').value); // Menggunakan 'id' sesuai backend

                $.ajax({
                    url: '{{ route('updateOverviewPurchase') }}', // Route untuk update inquiry
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const modalEl = document.getElementById('editDataModal1');
                        if (modalEl) {
                            const modalInstance = bootstrap.Modal.getInstance(modalEl);
                            if (modalInstance) {
                                modalInstance.hide();
                            }
                        }

                        Swal.fire('Success!', response.message, 'success').then(() => {
                            if (window.overviewTable) {
                                window.overviewTable.ajax.reload(null, false);
                            } else {
                                location.reload();
                            }
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while updating.', 'error');
                    }
                });
            }



        </script>

    </main>
@endsection
