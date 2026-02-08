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
                padding: 0.6rem;
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
                padding: 0.45rem 0.5rem;
            }

            .table-1 td {
                font-size: 8pt;
                font-family: 'Cambria', serif;
                padding: 0.4rem 0.45rem;
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

            .detail-container {
                background-color: #fdfdfd;
                border-left: 3px solid #0d6efd;
                padding: 0.75rem;
                border-radius: 4px;
            }

            .detail-container .mb-3 {
                margin-bottom: 0.6rem !important;
            }

            .detail-stack-table th,
            .detail-stack-table td {
                font-size: 8pt;
                font-family: 'Cambria', serif;
                vertical-align: middle;
                padding: 0.4rem 0.45rem;
            }

            .detail-stack-table td.note-cell {
                white-space: normal;
                min-width: 160px;
            }

            .detail-row {
                display: none;
            }

            .detail-row.show {
                display: table-row;
            }

            .toggle-details i {
                transition: transform 0.2s ease;
            }

            .toggle-details {
                cursor: pointer;
            }

            .toggle-details[aria-expanded="true"] i {
                transform: rotate(90deg);
            }
        </style>

        <section class="section">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title font-sii text-center">Overview Purchasing</h5>
                    <form method="GET" action="{{ route('overviewPurchase.exportByDate') }}" class="row g-2 align-items-end justify-content-end mt-3">
                        <div class="col-sm-6 col-md-3">
                            <label for="start_date" class="form-label small fw-bold">From Date</label>
                            <input type="date"
                                   name="start_date"
                                   id="start_date"
                                   class="form-control form-control-sm @error('start_date') is-invalid @enderror"
                                   value="{{ old('start_date') }}">
                            @error('start_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <label for="end_date" class="form-label small fw-bold">To Date</label>
                            <input type="date"
                                   name="end_date"
                                   id="end_date"
                                   class="form-control form-control-sm @error('end_date') is-invalid @enderror"
                                   value="{{ old('end_date') }}">
                            @error('end_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-sm-12 col-md-auto">
                            <button type="submit" class="btn btn-outline-success btn-sm w-100 mt-3 mt-md-0">
                                <i class="bi bi-calendar3"></i> Export Date Range
                            </button>
                        </div>
                    </form>
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
                                                <th class="text-center" style="width: 40px;"></th>
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

                                        <!-- Field PO Number -->
                                        <div class="mb-3">
                                            <label for="refnopo" class="form-label">PO Number</label>
                                            <input type="text" class="form-control" id="refnopo" name="refnopo">
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

                    <!-- Modal for Detail Status Update -->
                    <div class="modal fade" id="detailStatusModal" tabindex="-1" aria-labelledby="detailStatusModalLabel"
                        aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title testfont" id="detailStatusModalLabel">Update Detail Status</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form id="detailStatusForm">
                                        @csrf
                                        <input type="hidden" name="inquiry_id" id="detailStatusInquiryId">
                                        <div id="detailStatusSelected"></div>
                                        <div class="mb-3">
                                            <label for="detailStatusSelect" class="form-label">Status</label>
                                            <select class="form-select" id="detailStatusSelect" name="status" required>
                                                <option value="" disabled selected>Select status</option>
                                                <option value="8">Approve Inventory</option>
                                                <option value="9">Confirm</option>
                                                <option value="6">Finish</option>
                                            </select>
                                        </div>
                                    </form>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm"
                                        data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-primary btn-sm"
                                        id="detailStatusSubmit">Update</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Editing Detail PO -->
        <div class="modal fade" id="editDetailPoModal" tabindex="-1" aria-labelledby="editDetailPoModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title testfont" id="editDetailPoModalLabel">Edit PO Number</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editDetailPoForm">
                            @csrf
                            <input type="hidden" name="detail_id" id="detailPoId">
                            <div class="mb-3">
                                <label for="detailPoInput" class="form-label">PO Number</label>
                                <input type="text" class="form-control" name="po_number" id="detailPoInput"
                                    placeholder="Enter PO number">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary btn-sm" id="detailPoSubmit">Save</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

                <script>
            $(document).ready(function() {
                $(".nav-item.dropdown").hover(function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideDown(150);
                }, function() {
                    $(this).find('.dropdown-menu').first().stop(true, true).slideUp(150);
                });
            });

            jQuery(function ($) {
                const initialSelected = (@json($preselected ?? [])) || [];
                const selectedInquiries = new Set(initialSelected.map(Number));
                const selectedDetails = new Map();
                const hiddenContainer = $('#selectedHiddenInputs');
                const selectAll = document.getElementById('select-all');
                const detailStatusModalEl = document.getElementById('detailStatusModal');
                const detailStatusModal = detailStatusModalEl ? new bootstrap.Modal(detailStatusModalEl) : null;
                const detailStatusForm = $('#detailStatusForm');
                const detailStatusSelected = $('#detailStatusSelected');
                const detailStatusSelect = $('#detailStatusSelect');
                const detailStatusInquiryId = $('#detailStatusInquiryId');
                const detailStatusSubmit = $('#detailStatusSubmit');
                const detailStatusSubmitText = detailStatusSubmit.text();
                const editDetailPoModalEl = document.getElementById('editDetailPoModal');
                const editDetailPoModal = editDetailPoModalEl ? new bootstrap.Modal(editDetailPoModalEl) : null;
                const editDetailPoForm = $('#editDetailPoForm');
                const detailPoId = $('#detailPoId');
                const detailPoInput = $('#detailPoInput');
                const detailPoSubmit = $('#detailPoSubmit');
                const detailPoSubmitText = detailPoSubmit.length ? detailPoSubmit.text() : 'Save';
                let activePoDetailRow = null;
                let activePoButton = null;
                let activePoRowData = null;
                let activePoDetailData = null;

                const ensureArray = (value) => Array.isArray(value) ? value : [];
                const escapeHtml = (value) => {
                    if (value === null || value === undefined) {
                        return '';
                    }
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                };
                const escapeAttr = (value) => {
                    if (value === null || value === undefined) {
                        return '';
                    }
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;');
                };

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
                    let checkedCount = 0;
                    checkboxes.each(function () {
                        if (this.checked) {
                            checkedCount += 1;
                        }
                    });
                    selectAll.checked = checkedCount === checkboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                };

                const formatDetailRows = (rowData) => {
                    const details = ensureArray(rowData.detail_rows);
                    if (!details.length) {
                        return `
                            <div class="detail-container">
                                <em class="text-muted">Belum ada detail untuk inquiry ini.</em>
                            </div>
                        `;
                    }

                    const rowsHtml = details.map((detail) => {
                        const statusClass = detail.status_class || 'badge bg-secondary';
                        const statusLabel = detail.status_label || 'Pending';
                        const poDisplay = detail.no_po ?? '-';
                        const poValue = detail.po_value ?? '';
                        return `
                            <tr data-detail-id="${detail.id}">
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input detail-checkbox" value="${detail.id}">
                                </td>
                                <td>
                                    <span class="detail-po-value">${escapeHtml(poDisplay)}</span>
                                </td>
                                <td>${escapeHtml(detail.material ?? '-')}</td>
                                <td>${escapeHtml(detail.jenis ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.thickness ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.weight ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.inner_diameter ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.outer_diameter ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.length ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.qty ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.m1 ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.m2 ?? '-')}</td>
                                <td class="text-center">${escapeHtml(detail.m3 ?? '-')}</td>
                                <td>${escapeHtml(detail.so ?? '-')}</td>
                                <td class="note-cell">${escapeHtml(detail.note ?? '-')}</td>
                                <td>${escapeHtml(detail.ship ?? '-')}</td>
                                <td class="text-center">
                                    <span class="detail-status ${statusClass}">${escapeHtml(statusLabel)}</span>
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-outline-primary btn-sm edit-po-btn"
                                        data-detail-id="${detail.id}"
                                        data-po="${escapeAttr(poValue)}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');

                    return `
                        <div class="detail-container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Detail Material</h6>
                                <small class="text-muted">Pilih item untuk update status melalui tombol aksi di baris master.</small>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered detail-stack-table mb-0">
                                    <thead>
                                        <tr class="table-light">
                                            <th class="text-center" style="width: 55px;">Pilih</th>
                                            <th>PO Number</th>
                                            <th>Material</th>
                                            <th>Jenis</th>
                                            <th class="text-center" style="width: 70px;">Thickness</th>
                                            <th class="text-center" style="width: 70px;">Weight</th>
                                            <th class="text-center" style="width: 80px;">Inner Ø</th>
                                            <th class="text-center" style="width: 80px;">Outer Ø</th>
                                            <th class="text-center" style="width: 70px;">Length</th>
                                            <th class="text-center" style="width: 60px;">Qty</th>
                                            <th class="text-center" style="width: 60px;">M1</th>
                                            <th class="text-center" style="width: 60px;">M2</th>
                                            <th class="text-center" style="width: 60px;">M3</th>
                                            <th class="text-center" style="width: 90px;">SO</th>
                                            <th>Note</th>
                                            <th>Ship-to</th>
                                            <th class="text-center" style="width: 140px;">Status</th>
                                            <th class="text-center" style="width: 90px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${rowsHtml}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `;
                };

                const bindDetailEvents = (childRow, rowData) => {
                    const inquiryId = parseInt(rowData.id, 10);
                    const currentSelection = selectedDetails.get(inquiryId) || new Set();

                    childRow.find('.detail-checkbox').each(function () {
                        const detailId = parseInt(this.value, 10);
                        this.checked = currentSelection.has(detailId);
                    });

                    childRow.find('.detail-checkbox').on('change', function () {
                        const detailId = parseInt(this.value, 10);
                        const selection = selectedDetails.get(inquiryId) || new Set();

                        if (this.checked) {
                            selection.add(detailId);
                            selectedDetails.set(inquiryId, selection);
                        } else {
                            selection.delete(detailId);
                            if (selection.size === 0) {
                                selectedDetails.delete(inquiryId);
                            } else {
                                selectedDetails.set(inquiryId, selection);
                            }
                        }
                    });

                    childRow.find('.edit-po-btn').on('click', function () {
                        if (!editDetailPoModal) {
                            return;
                        }

                        const button = $(this);
                        const detailId = parseInt(button.data('detail-id'), 10);
                        if (!detailId) {
                            Swal.fire('Error', 'Detail tidak valid.', 'error');
                            return;
                        }

                        const currentPo = button.data('po') ?? '';
                        activePoDetailRow = button.closest('tr');
                        activePoButton = button;
                        activePoRowData = rowData;
                        const detailList = ensureArray(rowData.detail_rows);
                        activePoDetailData = detailList.find((item) => parseInt(item.id, 10) === detailId) || null;

                        detailPoId.val(detailId);
                        detailPoInput.val(currentPo);
                        detailPoSubmit.prop('disabled', false).text(detailPoSubmitText);

                        editDetailPoModal.show();
                        setTimeout(() => detailPoInput.trigger('focus'), 150);
                    });
                };

                const findRowByInquiryId = (inquiryId) => {
                    let targetRow = null;
                    overviewTable.rows().every(function () {
                        const data = this.data();
                        if (data && parseInt(data.id, 10) === inquiryId) {
                            targetRow = this;
                        }
                    });
                    return targetRow;
                };

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

                const overviewTable = $('#overviewTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: '{{ route('overviewPurchase2') }}',
                        data: function (params) {
                            params.format = 'json';
                        }
                    },
                    columns: [
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center align-middle',
                            defaultContent: '<button type="button" class="btn btn-outline-secondary btn-sm toggle-details" aria-label="Toggle detail" aria-expanded="false"><i class="bi bi-chevron-down"></i></button>'
                        },
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
                    order: [[4, 'desc']],
                    lengthMenu: [[10, 15, 25, 50], [10, 15, 25, 50]]
                });

                overviewTable.on('preXhr.dt', function () {
                    selectedDetails.clear();
                });

                overviewTable.on('draw', function () {
                    $('#overviewTable').find('.inquiry-checkbox').each(function () {
                        const id = parseInt(this.value, 10);
                        this.checked = selectedInquiries.has(id);
                    });
                    updateSelectAllState();
                });

                $('#overviewTable tbody').on('change', '.inquiry-checkbox', function () {
                    const id = parseInt(this.value, 10);
                    if (this.checked) {
                        selectedInquiries.add(id);
                    } else {
                        selectedInquiries.delete(id);
                    }
                    syncSelectedInputs();
                    updateSelectAllState();
                });

                $('#overviewTable tbody').on('click', '.toggle-details', function () {
                    const button = $(this);
                    const tr = button.closest('tr');
                    const row = overviewTable.row(tr);

                    if (row.child.isShown()) {
                        row.child.hide();
                        tr.removeClass('shown');
                        button.attr('aria-expanded', 'false');
                        button.find('i').removeClass('bi-chevron-up').addClass('bi-chevron-down');
                    } else {
                        row.child(formatDetailRows(row.data())).show();
                        tr.addClass('shown');
                        button.attr('aria-expanded', 'true');
                        button.find('i').removeClass('bi-chevron-down').addClass('bi-chevron-up');
                        const childRow = tr.next('tr');
                        childRow.addClass('detail-child-row');
                        childRow.find('td').addClass('p-0');
                        bindDetailEvents(childRow, row.data());
                    }
                });

                syncSelectedInputs();
                updateSelectAllState();

                const resetEditPoModal = () => {
                    if (!editDetailPoModalEl) {
                        return;
                    }
                    if (editDetailPoForm.length) {
                        const formElement = editDetailPoForm.get(0);
                        if (formElement) {
                            formElement.reset();
                        }
                    }
                    detailPoId.val('');
                    detailPoInput.val('');
                    detailPoSubmit.prop('disabled', false).text(detailPoSubmitText);
                    activePoDetailRow = null;
                    activePoButton = null;
                    activePoRowData = null;
                    activePoDetailData = null;
                };

                const resetDetailModal = () => {
                    if (!detailStatusModalEl) {
                        return;
                    }
                    if (detailStatusForm.length) {
                        const formElement = detailStatusForm.get(0);
                        if (formElement) {
                            formElement.reset();
                        }
                    }
                    detailStatusSelected.empty();
                };

                if (editDetailPoModalEl) {
                    editDetailPoModalEl.addEventListener('hidden.bs.modal', resetEditPoModal);
                }

                if (detailStatusModalEl) {
                    detailStatusModalEl.addEventListener('hidden.bs.modal', resetDetailModal);
                }

                if (editDetailPoForm.length) {
                    editDetailPoForm.on('submit', function (event) {
                        event.preventDefault();
                        if (detailPoSubmit.length) {
                            detailPoSubmit.trigger('click');
                        }
                    });
                }

                if (detailPoSubmit.length) {
                    detailPoSubmit.on('click', function () {
                        if (!editDetailPoModal) {
                            return;
                        }

                        const detailId = parseInt(detailPoId.val(), 10);
                        if (!detailId) {
                            Swal.fire('Error', 'Detail tidak valid.', 'error');
                            return;
                        }

                        const payload = editDetailPoForm.serialize();
                        detailPoSubmit.prop('disabled', true).text('Memproses...');

                        $.ajax({
                            url: '{{ route('inquiry.detail-po') }}',
                            method: 'POST',
                            data: payload,
                            success: function (response) {
                                detailPoSubmit.prop('disabled', false).text(detailPoSubmitText);
                                editDetailPoModal.hide();

                                const displayPo = response && response.no_po !== undefined && response.no_po !== null && response.no_po !== ''
                                    ? response.no_po
                                    : '-';
                                const rawValue = response && response.po_value !== undefined && response.po_value !== null
                                    ? response.po_value
                                    : '';

                                if (activePoDetailRow) {
                                    activePoDetailRow.find('.detail-po-value').text(displayPo);
                                }

                                if (activePoButton) {
                                    activePoButton.attr('data-po', rawValue);
                                    activePoButton.data('po', rawValue);
                                }

                                if (activePoDetailData) {
                                    activePoDetailData.no_po = displayPo;
                                    activePoDetailData.po_value = rawValue;
                                }

                                if (activePoRowData) {
                                    const detailList = ensureArray(activePoRowData.detail_rows);
                                    const targetId = detailId;
                                    activePoRowData.detail_rows = detailList.map((item) => {
                                        if (parseInt(item.id, 10) === targetId) {
                                            item.no_po = displayPo;
                                            item.po_value = rawValue;
                                        }
                                        return item;
                                    });

                                    const rowApi = findRowByInquiryId(parseInt(activePoRowData.id, 10));
                                    if (rowApi) {
                                        rowApi.data(activePoRowData);
                                    }
                                }

                                const message = response && response.message
                                    ? response.message
                                    : 'PO detail berhasil diperbarui.';
                                Swal.fire('Success!', message, 'success');
                            },
                            error: function (xhr) {
                                detailPoSubmit.prop('disabled', false).text(detailPoSubmitText);
                                let message = 'Terjadi kesalahan saat memperbarui PO.';
                                if (xhr.responseJSON) {
                                    if (xhr.responseJSON.message) {
                                        message = xhr.responseJSON.message;
                                    } else if (xhr.responseJSON.errors) {
                                        const firstError = Object.values(xhr.responseJSON.errors)[0];
                                        if (Array.isArray(firstError) && firstError.length) {
                                            message = firstError[0];
                                        }
                                    }
                                }
                                Swal.fire('Error', message, 'error');
                            }
                        });
                    });
                }

                window.openDetailStatusModal = function (inquiryId) {
                    if (!detailStatusModal) {
                        return;
                    }
                    const selection = selectedDetails.get(inquiryId);
                    if (!selection || selection.size === 0) {
                        Swal.fire('Informasi', 'Silakan pilih detail yang ingin diperbarui.', 'info');
                        return;
                    }

                    detailStatusSelected.empty();
                    Array.from(selection).forEach(function (detailId) {
                        detailStatusSelected.append(
                            $('<input>', { type: 'hidden', name: 'detail_ids[]', value: detailId })
                        );
                    });

                    detailStatusInquiryId.val(inquiryId);
                    if (detailStatusSelect.length) {
                        detailStatusSelect.get(0).selectedIndex = 0;
                    }
                    detailStatusModal.show();
                };

                if (detailStatusSubmit.length) {
                    detailStatusSubmit.on('click', function () {
                        if (!detailStatusModal) {
                            return;
                        }

                        const statusValue = detailStatusSelect.val();
                        if (!statusValue) {
                            Swal.fire('Informasi', 'Silakan pilih status yang ingin diterapkan.', 'info');
                            return;
                        }

                        const inquiryId = parseInt(detailStatusInquiryId.val(), 10);
                        if (!inquiryId) {
                            Swal.fire('Error', 'Inquiry tidak valid.', 'error');
                            return;
                        }

                        const payload = detailStatusForm.serialize();
                        detailStatusSubmit.prop('disabled', true).text('Memproses...');

                        $.ajax({
                            url: '{{ route('inquiry.detail-status') }}',
                            method: 'POST',
                            data: payload,
                            success: function (response) {
                                detailStatusSubmit.prop('disabled', false).text(detailStatusSubmitText);
                                detailStatusModal.hide();
                                resetDetailModal();
                                selectedDetails.delete(inquiryId);

                                const updates = Array.isArray(response.details) ? response.details : [];
                                if (updates.length) {
                                    const rowApi = findRowByInquiryId(inquiryId);
                                    if (rowApi) {
                                        const rowData = rowApi.data();
                                        const updateMap = new Map(updates.map((detail) => [parseInt(detail.id, 10), detail]));
                                        rowData.detail_rows = ensureArray(rowData.detail_rows).map((detail) => {
                                            const update = updateMap.get(parseInt(detail.id, 10));
                                            if (update) {
                                                detail.status = update.status;
                                                detail.status_label = update.status_label;
                                                detail.status_class = update.status_class;
                                            }
                                            return detail;
                                        });
                                        rowApi.data(rowData);
                                        if (rowApi.child && rowApi.child.isShown()) {
                                            rowApi.child(formatDetailRows(rowData)).show();
                                            const tr = $(rowApi.node());
                                            const childRow = tr.next('tr');
                                            childRow.addClass('detail-child-row');
                                            childRow.find('td').addClass('p-0');
                                            bindDetailEvents(childRow, rowData);
                                        }
                                    }
                                }

                                const message = response.message || 'Status detail berhasil diperbarui.';
                                Swal.fire('Success!', message, 'success');
                                overviewTable.ajax.reload(null, false);
                            },
                            error: function (xhr) {
                                detailStatusSubmit.prop('disabled', false).text(detailStatusSubmitText);
                                let message = 'Terjadi kesalahan saat memperbarui status.';
                                if (xhr.responseJSON) {
                                    if (xhr.responseJSON.message) {
                                        message = xhr.responseJSON.message;
                                    } else if (xhr.responseJSON.errors) {
                                        const firstError = Object.values(xhr.responseJSON.errors)[0];
                                        if (Array.isArray(firstError) && firstError.length) {
                                            message = firstError[0];
                                        }
                                    }
                                }
                                Swal.fire('Error', message, 'error');
                            }
                        });
                    });
                }
            });
        </script>

        <script>
            function confirmPurchasing(id) {
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
                                '_token': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Sukses!', response.success, 'success').then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                                Swal.fire('Error!', xhr.responseJSON.error, 'error');
                            }
                        });
                    } else {
                        Swal.fire('Canceled', 'Confirmation Canceled', 'info');
                    }
                });
            }

            function showEditDataModal(id, supplier, progress, refnopo, estDate) {
                document.getElementById('inquiryId').value = id;
                document.getElementById('supplier').value = supplier;
                document.getElementById('progress').value = progress;
                const refnopoInput = document.getElementById('refnopo');
                if (refnopoInput) {
                    refnopoInput.value = refnopo || '';
                }
                document.getElementById('estDate').value = estDate;

                const modal = new bootstrap.Modal(document.getElementById('editDataModal'), {});
                modal.show();
            }

            function submitEditDataForm() {
                const form = document.getElementById('editDataForm');
                const formData = new FormData(form);

                $.ajax({
                    url: '{{ route('updateInquiry') }}',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        Swal.fire('Success!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        Swal.fire('Error!', 'An error occurred while updating.', 'error');
                    }
                });
            }

            function finishInquiry(id) {
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
                        $.ajax({
                            url: '{{ route('finishInquiry', '') }}/' + id,
                            method: 'POST',
                            data: {
                                '_token': '{{ csrf_token() }}'
                            },
                            success: function(response) {
                                Swal.fire('Success!', 'Inquiry marked as finished.', 'success').then(() => {
                                    location.reload();
                                });
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                                Swal.fire('Error!', 'An error occurred while finishing the inquiry.', 'error');
                            }
                        });
                    }
                });
            }

            function showInquiry(id) {
                window.location.href = '{{ route('showFormSS', '') }}/' + id + '?source=approval';
            }
        </script>

    </main>
@endsection


