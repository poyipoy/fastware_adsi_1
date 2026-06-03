@extends('layout')

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Outstanding Material</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item active">Outstanding Material</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <div class="row">
            <div class="col-lg-12">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        {{ session('warning') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <h5 class="card-title mb-0">List Outstanding Material</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('outstanding-materials.create') }}" class="btn btn-primary">
                                    Add Outstanding Material
                                </a>
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importOutstandingMaterialModal">
                                    Import
                                </button>
                                <a href="{{ route('outstanding-materials.export') }}" id="btnOutstandingExport" class="btn btn-success">
                                    Export
                                </a>
                                <a href="{{ route('outstanding-materials.template') }}" class="btn btn-outline-success">
                                    Download Template
                                </a>
                            </div>
                        </div>

                        <form id="outstandingFilterForm" class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label for="filter_q" class="form-label">Search Global</label>
                                <input type="text" id="filter_q" name="q" class="form-control" placeholder="Supplier, TYPE, invoice, status">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_supplier" class="form-label">Supplier</label>
                                <select id="filter_supplier" name="supplier" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($filterOptions['suppliers'] as $supplier)
                                        <option value="{{ $supplier }}">{{ $supplier }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_type" class="form-label">TYPE</label>
                                <select id="filter_type" name="type" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($filterOptions['types'] as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_status" class="form-label">Status</label>
                                <select id="filter_status" name="status" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($statusOptions as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_keterangan" class="form-label">Keterangan</label>
                                <select id="filter_keterangan" name="keterangan" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($keteranganOptions as $keterangan)
                                        <option value="{{ $keterangan }}">{{ $keterangan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_estimasi_bulan_eta" class="form-label">Estimasi Bulan ETA</label>
                                <select id="filter_estimasi_bulan_eta" name="estimasi_bulan_eta" class="form-select">
                                    <option value="">All</option>
                                    @foreach ($filterOptions['months'] as $month)
                                        <option value="{{ $month }}">{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filter_eta_port_from" class="form-label">ETA Port From</label>
                                <input type="date" id="filter_eta_port_from" name="eta_port_from" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_eta_port_to" class="form-label">ETA Port To</label>
                                <input type="date" id="filter_eta_port_to" name="eta_port_to" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_eta_warehouse_from" class="form-label">ETA Warehouse From</label>
                                <input type="date" id="filter_eta_warehouse_from" name="eta_warehouse_from" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="filter_eta_warehouse_to" class="form-label">ETA Warehouse To</label>
                                <input type="date" id="filter_eta_warehouse_to" name="eta_warehouse_to" class="form-control">
                            </div>
                            <div class="col-md-6 d-flex align-items-end gap-2">
                                <button type="submit" class="btn btn-secondary">Apply Filter</button>
                                <button type="button" id="btnOutstandingReset" class="btn btn-outline-secondary">Reset</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table id="outstandingMaterialTable" class="table table-striped table-bordered nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>NO</th>
                                        <th>Supplier</th>
                                        <th>TYPE</th>
                                        <th>Thickness</th>
                                        <th>Width</th>
                                        <th>Diameter</th>
                                        <th>Length</th>
                                        <th>QTY (PCS)</th>
                                        <th>Est QTY (KG)</th>
                                        <th>Number Invoice</th>
                                        <th>Status</th>
                                        <th>Estimasi ETA Port</th>
                                        <th>Estimasi ETA Warehouse</th>
                                        <th>Estimasi Bulan ETA</th>
                                        <th>Keterangan</th>
                                        <th>Estimasi Delay ETA Port</th>
                                        <th>Estimasi Delay ETA Warehouse</th>
                                        <th>DOKUMEN PACKING LIST DAN MTC</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="modal fade" id="importOutstandingMaterialModal" tabindex="-1" aria-labelledby="importOutstandingMaterialModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="outstandingImportForm" method="POST" action="{{ route('outstanding-materials.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="importOutstandingMaterialModalLabel">Import Outstanding Material</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="import_file" class="form-label">File Excel / CSV</label>
                        <input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls,.csv" required>
                    </div>
                    <p class="text-muted small mb-0">
                        Data diproses mulai dari baris setelah header. Kolom NO diabaikan.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            console.error('DataTables plugin is not loaded.');
            return;
        }

        const $ = window.jQuery;
        const filterForm = $('#outstandingFilterForm');
        const importForm = $('#outstandingImportForm');
        const exportButton = $('#btnOutstandingExport');
        let confirmedImportSubmit = false;

        function currentFilters() {
            return {
                q: $('#filter_q').val(),
                supplier: $('#filter_supplier').val(),
                type: $('#filter_type').val(),
                status: $('#filter_status').val(),
                keterangan: $('#filter_keterangan').val(),
                estimasi_bulan_eta: $('#filter_estimasi_bulan_eta').val(),
                eta_port_from: $('#filter_eta_port_from').val(),
                eta_port_to: $('#filter_eta_port_to').val(),
                eta_warehouse_from: $('#filter_eta_warehouse_from').val(),
                eta_warehouse_to: $('#filter_eta_warehouse_to').val(),
            };
        }

        function exportUrl() {
            const params = new URLSearchParams();
            Object.entries(currentFilters()).forEach(function ([key, value]) {
                if (value) {
                    params.set(key, value);
                }
            });

            const query = params.toString();
            return '{{ route('outstanding-materials.export') }}' + (query ? '?' + query : '');
        }

        const table = $('#outstandingMaterialTable').DataTable({
            processing: true,
            serverSide: true,
            searching: false,
            scrollX: true,
            ajax: {
                url: '{{ route('outstanding-materials.data') }}',
                type: 'GET',
                data: function (data) {
                    Object.assign(data, currentFilters());
                },
            },
            columns: [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center align-middle',
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    },
                },
                { data: 'supplier', name: 'supplier' },
                { data: 'type', name: 'type' },
                { data: 'thickness', name: 'thickness', className: 'text-end' },
                { data: 'width', name: 'width', className: 'text-end' },
                { data: 'diameter', name: 'diameter', className: 'text-end' },
                { data: 'length', name: 'length', className: 'text-end' },
                { data: 'qty_pcs', name: 'qty_pcs', className: 'text-end' },
                { data: 'est_qty_kg', name: 'est_qty_kg', className: 'text-end' },
                { data: 'number_invoice', name: 'number_invoice' },
                { data: 'status', name: 'status', orderable: true, className: 'text-center' },
                { data: 'estimasi_eta_port', name: 'estimasi_eta_port' },
                { data: 'estimasi_eta_warehouse', name: 'estimasi_eta_warehouse' },
                { data: 'estimasi_bulan_eta', name: 'estimasi_bulan_eta' },
                { data: 'keterangan', name: 'keterangan' },
                { data: 'estimasi_delay_eta_port', name: 'estimasi_delay_eta_port' },
                { data: 'estimasi_delay_eta_warehouse', name: 'estimasi_delay_eta_warehouse' },
                { data: 'attachment', name: 'attachment_path', orderable: true, searchable: false, className: 'text-center' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
            ],
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: 'Memuat data...',
                lengthMenu: 'Tampilkan _MENU_ data per halaman',
                zeroRecords: 'Data tidak ditemukan',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data yang tersedia',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: {
                    first: 'Pertama',
                    last: 'Terakhir',
                    next: 'Selanjutnya',
                    previous: 'Sebelumnya',
                },
            },
        });

        filterForm.on('submit', function (event) {
            event.preventDefault();
            table.ajax.reload();
            exportButton.attr('href', exportUrl());
        });

        $('#btnOutstandingReset').on('click', function () {
            filterForm.get(0).reset();
            table.ajax.reload();
            exportButton.attr('href', exportUrl());
        });

        exportButton.on('click', function () {
            exportButton.attr('href', exportUrl());
        });

        $(document).on('submit', '.js-outstanding-delete-form', function (event) {
            event.preventDefault();

            const form = this;
            const supplier = form.dataset.supplier || '-';
            const type = form.dataset.type || '-';
            const invoice = form.dataset.invoice || '-';

            Swal.fire({
                title: 'Delete Outstanding Material?',
                html: 'Data <strong>' + supplier + '</strong> - <strong>' + type + '</strong><br>Invoice: <strong>' + invoice + '</strong>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
            }).then(function (result) {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });

        importForm.on('submit', function (event) {
            if (confirmedImportSubmit) {
                return;
            }

            event.preventDefault();

            const fileInput = document.getElementById('import_file');
            if (!fileInput || fileInput.files.length === 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'File belum dipilih',
                    text: 'Pilih file Excel atau CSV terlebih dahulu sebelum import.',
                });
                return;
            }

            Swal.fire({
                title: 'Import Outstanding Material?',
                text: 'Data valid dari file akan ditambahkan sebagai record baru.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, import',
                cancelButtonText: 'Cancel',
            }).then(function (result) {
                if (result.isConfirmed) {
                    confirmedImportSubmit = true;
                    importForm.get(0).submit();
                }
            });
        });
    });
</script>
@endpush
