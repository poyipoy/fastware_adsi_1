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

        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title font-sii text-center">Overview Purchasing</h5>
                </div>

                <section class="section">
                    <div class="card">
                        <div class="card-body">
                            <form method="POST" action="{{ route('overviewPurchase.export') }}" id="overviewPurchaseExportForm">
                                @csrf
                                <div class="d-flex justify-content-end mb-3">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="bi bi-file-earmark-excel"></i> Export Excel
                                    </button>
                                </div>
                                @if ($errors->has('selected_inquiries'))
                                    <div class="alert alert-danger py-2 px-3 mb-3">
                                        {{ $errors->first('selected_inquiries') }}
                                    </div>
                                @endif
                                <div class="table-responsive">
                                    <table class="table table-1" id="overviewTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th class="text-center">PO Number</th>
                                                <th class="text-center">Create By</th>
                                                <th class="text-center">Reference</th>
                                                <th class="text-center">Category</th>
                                                <th class="text-center">Supplier</th>
                                                <th class="text-center">Customer</th>
                                                <th>Status</th>
                                                <th>Ship-to</th>
                                                <th>Last Update</th>
                                                <th>Est. Date</th>
                                                <th>Actions</th>
                                                <th class="text-center"><input type="checkbox" id="select-all" class="form-check-input" title="Select all"></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                                <div id="selectedHiddenInputs"></div>
                            </form>
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
                                                <option value=''>Select Supplier</option>
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
                                                <option value="CV. DIMA RAMA SAKTI">
                                                    CV. DIMA RAMA SAKTI</option>
                                                <option value="CV. DWI PUTRA TEKNINDO">
                                                    CV. DWI PUTRA TEKNINDO</option>
                                                <option value="CV. DWI PUTRA TEKNINDO">
                                                    PT INTI ATLAS INDONESIA</option>
                                                <option value="CV. DWI PUTRA TEKNINDO">
                                                    PT GAYA STEEL</option>
                                                <option value="CV. GLOBAL METAL INDONESIA">
                                                    CV. GLOBAL METAL INDONESIA</option>
                                                <option value="PT. KREASI INTI SUKSES">
                                                    PT. KREASI INTI SUKSES</option>
                                                <option value="METAL JAYA UTAMA">
                                                    METAL JAYA UTAMA</option>
                                                {{-- <option value="DAVID MURDIYANTO">
                                                    DAVID MURDIYANTO</option> --}}
                                                <!-- Tambahkan opsi lain jika diperlukan -->
                                            </select>
                                        </div>

                                        <!-- Field Last Update (Free Text) -->
                                        <div class="mb-3">
                                            <label for="progress" class="form-label">Last Update</label>
                                            <input type="text" class="form-control" id="progress" name="progress"
                                                required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="refnopo" class="form-label">Ref PO Number</label>
                                            <input type="text" class="form-control" id="refnopo" name="refnopo"
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
                </section>
            </div>
        </section>


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

            jQuery(function ($) {
                const initialSelected = (@json($preselected ?? [])) || [];
                const selectedInquiries = new Set(initialSelected.map(Number));
                const hiddenContainer = $('#selectedHiddenInputs');
                const selectAll = document.getElementById('select-all');

                const syncSelectedInputs = () => {
                    hiddenContainer.empty();
                    selectedInquiries.forEach((id) => {
                        hiddenContainer.append(
                            $('<input>', { type: 'hidden', name: 'selected_inquiries[]', value: id })
                        );
                    });
                };

                const updateSelectAllState = () => {
                    if (!selectAll) {
                        return;
                    }
                    const checkboxes = $('#overviewTable').find('.inquiry-checkbox');
                    if (!checkboxes.length) {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                        return;
                    }

                    const allChecked = checkboxes.toArray().every((checkbox) => checkbox.checked);
                    const anyChecked = checkboxes.toArray().some((checkbox) => checkbox.checked);

                    selectAll.checked = allChecked;
                    selectAll.indeterminate = !allChecked && anyChecked;
                };

                const overviewTable = $('#overviewTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('overviewPurchase') }}',
                        data: function (params) {
                            params.format = 'json';
                        }
                    },
                    columns: [
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        { data: 'refnopo', name: 'refnopo', defaultContent: '-' },
                        { data: 'create_by', name: 'create_by' },
                        { data: 'kode_inquiry', name: 'kode_inquiry' },
                        { data: 'loc_imp', name: 'loc_imp' },
                        { data: 'supplier', name: 'supplier', defaultContent: '-' },
                        { data: 'customer_name', name: 'customer.name_customer', defaultContent: 'N/A' },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            render: function (data, type, row) {
                                const css = row.status_class || 'btn-light';
                                const label = row.status_label || 'Unknown';
                                return '<span class="btn btn-sm ' + css + '">' + label + '</span>';
                            }
                        },
                        { data: 'ship_to', name: 'ship_to', orderable: false, defaultContent: '--- No Shipping Options ---' },
                        { data: 'last_update', name: 'last_update', defaultContent: 'No updates yet' },
                        { data: 'est_date', name: 'est_date', defaultContent: '-' },
                        { data: 'actions', orderable: false, searchable: false, defaultContent: '' },
                        { data: 'checkbox', orderable: false, searchable: false, defaultContent: '' }
                    ],
                    order: [[3, 'desc']],
                    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]]
                });

                overviewTable.on('draw', function () {
                    $('#overviewTable').find('.inquiry-checkbox').each(function () {
                        const id = parseInt(this.value, 10);
                        this.checked = selectedInquiries.has(id);
                    });
                    updateSelectAllState();
                });

                $(document).on('change', '.inquiry-checkbox', function () {
                    const id = parseInt(this.value, 10);
                    if (this.checked) {
                        selectedInquiries.add(id);
                    } else {
                        selectedInquiries.delete(id);
                    }
                    syncSelectedInputs();
                    updateSelectAllState();
                });

                if (selectAll) {
                    selectAll.addEventListener('change', function () {
                        const checkboxes = $('#overviewTable').find('.inquiry-checkbox');
                        checkboxes.each(function () {
                            const id = parseInt(this.value, 10);
                            this.checked = selectAll.checked;
                            if (selectAll.checked) {
                                selectedInquiries.add(id);
                            } else {
                                selectedInquiries.delete(id);
                            }
                        });
                        syncSelectedInputs();
                        updateSelectAllState();
                    });
                }

                syncSelectedInputs();
            });
        </script>


        <script>
            function confirmPurchasing(id) {
                // Tampilkan pertanyaan konfirmasi dengan SweetAlert
                Swal.fire({
                    title: 'Confirm',
                    text: "Are you sure?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes!',
                    cancelButtonText: 'No!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: '{{ route('confirmPurchase', '') }}/' + id,
                            method: 'POST',
                            data: {
                                '_token': '{{ csrf_token() }}' // CSRF token
                            },
                            success: function(response) {
                                Swal.fire('Sukses!', response.success, 'success').then(() => {
                                    location.reload(); // Reload halaman
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                                Swal.fire('Error!', xhr.responseJSON.error,
                                    'error'); // Tampilkan pesan error
                            }
                        });
                    } else {
                        Swal.fire('Canceled', 'Confirmation Canceled', 'info');
                    }
                });
            }

            function showEditDataModal(id, supplier, progress, refnopo, estDate) {
                // Set inquiry_id
                document.getElementById('inquiryId').value = id;
                document.getElementById('supplier').value = supplier; // Set supplier
                document.getElementById('progress').value = progress; // Set last update
                document.getElementById('refnopo').value = refnopo; // Set nopo
                document.getElementById('estDate').value = estDate; // Set est. date

                // Tampilkan modal
                var myModal = new bootstrap.Modal(document.getElementById('editDataModal'), {});
                myModal.show();
            }

            function submitEditDataForm() {
                const form = document.getElementById('editDataForm');
                const formData = new FormData(form);

                $.ajax({
                    url: '{{ route('updateInquiry') }}', // Route untuk update inquiry
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload(); // Reload halaman
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while updating.', 'error');
                    }
                });
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
                            url: '{{ route('finishInquiry', '') }}/' + id, // Route untuk finishing inquiry
                            method: 'POST',
                            data: {
                                '_token': '{{ csrf_token() }}' // CSRF token
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

            function showInquiry(id) {
                // Tampilkan detail inquiry dan tambahkan parameter query
                window.location.href = '{{ route('showFormSS', '') }}/' + id + '?source=approval';
            }
        </script>

    </main>
@endsection
