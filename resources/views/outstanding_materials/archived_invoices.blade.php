@extends('layout')

@push('styles')
<style>
    .om-card { border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.05); } .om-card .card-body { padding:20px; }
    .om-card-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding-bottom:14px; margin-bottom:14px; border-bottom:1px solid #e5e7eb; } .om-card-title { margin:0; font-size:1.08rem; font-weight:600; }
    .om-table-wrap { max-height:70vh; overflow:auto; overscroll-behavior-x:contain; overscroll-behavior-y:auto; position:relative; border:1px solid #e5e7eb; border-radius:8px; } .om-table { width:100%; min-width:1150px; border-collapse:separate; border-spacing:0; margin:0; }
    .om-table thead th { background:#f9fafb; white-space:nowrap; text-align:center; vertical-align:middle; font-size:.76rem; padding:10px; border-bottom:2px solid #e5e7eb; } .om-table thead > tr.om-column-header > th { position:sticky; top:var(--om-table-filter-height,0px); z-index:3; background:#f9fafb; }
    table.om-table.dataTable thead > tr.om-column-header > th { position:sticky!important; top:var(--om-table-filter-height,0px)!important; z-index:3!important; background:#f9fafb!important; }
    .om-table tbody td { white-space:nowrap; vertical-align:middle; font-size:.83rem; padding:10px; border-bottom:1px solid #f3f4f6; } .om-table tbody tr:nth-child(even) { background:#f9fafb; } .om-table tbody tr:hover { background:#eff6ff; }
    .om-badge { display:inline-block; padding:.25em .55em; border-radius:999px; font-size:.72rem; background:#f3f4f6; color:#374151; margin:1px; } .om-badge--production { background:#fff7ed; color:#c2410c; } .om-badge--shipment { background:#ecfeff; color:#0e7490; } .om-badge--received { background:#ecfdf5; color:#047857; } .om-badge--on-schedule { background:#ecfdf5; color:#047857; } .om-badge--delay { background:#fff7ed; color:#c2410c; } .om-badge--closed { background:#f3f4f6; color:#4b5563; }
    .om-actions { display:inline-flex; align-items:center; justify-content:center; gap:5px; } .om-action-btn { width:31px; height:31px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#374151; text-decoration:none; } .om-action-btn:hover { background:#eff6ff; color:#1d4ed8; } .om-action-btn--restore { color:#059669; } .om-action-btn--restore:hover { background:#ecfdf5; border-color:#a7f3d0; color:#047857; } .om-document-warning { color:#d97706; margin-left:3px; }
    @media (max-width:768px) { .om-card .card-body { padding:12px; } }
</style>
@endpush

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Archived Invoices</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.invoice.index') }}">Invoice List</a></li>
                <li class="breadcrumb-item active">Archived Invoices</li>
            </ol>
        </nav>
    </div>
    <section class="section">
        @if (session('success')) <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif
        @if (session('warning')) <div class="alert alert-warning alert-dismissible fade show" role="alert">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif
        @if (!empty($errors) && $errors->any()) <div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif
        <div class="card om-card">
            <div class="card-body">
                <div class="om-card-header">
                    <h5 class="om-card-title"><i class="bi bi-archive me-2 text-warning"></i>Daftar Invoice Diarsipkan</h5>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group input-group-sm" style="max-width:300px">
                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                            <input type="search" id="archivedInvoiceSearch" class="form-control" placeholder="Cari invoice arsip, supplier...">
                        </div>
                        <a href="{{ route('outstanding-materials.materials.archived.index') }}" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-box-seam me-1"></i>Archived Materials
                        </a>
                        <a href="{{ route('outstanding-materials.invoice.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Active Invoices
                        </a>
                    </div>
                </div>
                <div class="om-table-wrap">
                    <table id="archivedInvoiceTable" class="om-table">
                        <thead>
                            <tr class="om-column-header">
                                <th>Number Invoice</th>
                                <th>Supplier</th>
                                <th>Total Row</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Latest ETA Warehouse</th>
                                <th>Archived Date</th>
                                <th>Packing List</th>
                                <th>MTC</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</main>

@if ($canManageOutstandingMaterials)
<div class="modal fade" id="archivedMaterialsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Material Diarsipkan · <span id="modalArchivedInvoiceTitle" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalArchivedLoading" class="text-center py-4">
                    <span class="spinner-border spinner-border-sm"></span> Loading...
                </div>
                <div id="modalArchivedContainer" class="d-none">
                    <div class="table-responsive" style="max-height:420px">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40px;" class="text-center">
                                        <input type="checkbox" id="selectAllArchivedMaterials" class="form-check-input" aria-label="Select all archived materials">
                                    </th>
                                    <th>Supplier</th>
                                    <th>Type</th>
                                    <th>Dimensions</th>
                                    <th>QTY</th>
                                    <th>Status</th>
                                    <th>Keterangan</th>
                                    <th>Archived At</th>
                                </tr>
                            </thead>
                            <tbody id="modalArchivedMaterialsBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
                <div>
                    <button type="button" class="btn btn-success" id="btnRestoreSelectedMaterials" disabled>
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Restore Terpilih ke Aktif
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('assets/js/outstanding-materials/sticky-table.js') }}?v={{ filemtime(public_path('assets/js/outstanding-materials/sticky-table.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !window.jQuery.fn.DataTable) return;
    const $ = window.jQuery;
    const csrf = '{{ csrf_token() }}';
    const archivedScopedBase = '{{ url('outstanding-materials/archived-invoices') }}';
    const archivedScopedUrl = (anchor, suffix) => archivedScopedBase + '/' + encodeURIComponent(anchor) + '/' + suffix;

    const table = $('#archivedInvoiceTable').DataTable({
        stateSave: true,
        stateDuration: 300,
        processing: true,
        serverSide: true,
        searching: true,
        searchDelay: 400,
        scrollX: false,
        ajax: '{{ route('outstanding-materials.invoice.archived.data') }}',
        columns: [
            { data: 'number_invoice' },
            { data: 'supplier' },
            { data: 'material_count', className: 'text-end' },
            { data: 'status', className: 'text-center' },
            { data: 'keterangan', className: 'text-center' },
            { data: 'latest_eta_warehouse' },
            { data: 'archived_at', className: 'text-center' },
            { data: 'packing_list', className: 'text-center', orderable: false, searchable: false },
            { data: 'mtc', className: 'text-center', orderable: false, searchable: false },
            { data: 'actions', className: 'text-center', orderable: false, searchable: false }
        ],
        order: [],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: 'Memuat data arsip...',
            lengthMenu: 'Tampilkan _MENU_ invoice per halaman',
            zeroRecords: 'Tidak ada invoice yang diarsipkan',
            info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ invoice arsip',
            infoEmpty: 'Tidak ada invoice arsip',
            paginate: { next: '›', previous: '‹' }
        },
        drawCallback: () => $('[data-bs-toggle="tooltip"]').tooltip()
    });

    window.OutstandingMaterialStickyTable && window.OutstandingMaterialStickyTable.install('#archivedInvoiceTable');

    if (table.search()) {
        $('#archivedInvoiceSearch').val(table.search());
    }
    $('#archivedInvoiceSearch').on('input', function () {
        table.search(this.value).draw();
    });

    // Handle restore confirmation
    $(document).on('submit', '.js-outstanding-invoice-restore-form', function (e) {
        e.preventDefault();
        const form = this;
        const invoice = $(this).data('invoice');
        const message = 'Kembalikan invoice "' + invoice + '" ke daftar aktif?';

        if (typeof window.Swal === 'undefined') {
            if (window.confirm(message)) {
                form.submit();
            }
            return;
        }

        window.Swal.fire({
            title: 'Restore Invoice?',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kembalikan',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });

    @if ($canManageOutstandingMaterials)
    const archivedModalEl = document.getElementById('archivedMaterialsModal');
    const archivedModal = archivedModalEl ? new bootstrap.Modal(archivedModalEl) : null;
    const escapeHtml = value => $('<div>').text(value ?? '').html();

    $(document).on('click', '.js-open-archived-material-modal', function () {
        const anchor = $(this).data('anchor-id');
        const invoice = $(this).data('invoice');

        $('#modalArchivedInvoiceTitle').text(invoice);
        $('#modalArchivedMaterialsBody').empty();
        $('#modalArchivedContainer').addClass('d-none');
        $('#modalArchivedLoading').removeClass('d-none');
        $('#selectAllArchivedMaterials').prop('checked', false).prop('disabled', true);
        $('#btnRestoreSelectedMaterials').prop('disabled', true);

        if (archivedModal) archivedModal.show();

        $.getJSON(archivedScopedUrl(anchor, 'materials')).done(function (rows) {
            if (!rows.length) {
                $('#modalArchivedMaterialsBody').html('<tr><td colspan="8" class="text-center text-muted">Tidak ada material arsip ditemukan.</td></tr>');
            } else {
                $('#selectAllArchivedMaterials').prop('disabled', false);
            }

            rows.forEach(function (row) {
                const dimensions = [row.thickness, row.width, row.diameter, row.length].filter(Boolean).map(escapeHtml).join(' × ');
                const quantities = [row.qty_pcs, row.est_qty_kg].filter(Boolean).map(escapeHtml).join(' / ');
                const archivedDate = row.archived_at ? new Date(row.archived_at).toLocaleDateString('id-ID', {day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit'}) : '-';

                $('#modalArchivedMaterialsBody').append(
                    '<tr>' +
                    '<td class="text-center"><input type="checkbox" class="archived-material-check form-check-input" value="' + Number(row.id) + '"></td>' +
                    '<td>' + escapeHtml(row.supplier) + '</td>' +
                    '<td>' + escapeHtml(row.type) + '</td>' +
                    '<td>' + (dimensions || '-') + '</td>' +
                    '<td>' + (quantities || '-') + '</td>' +
                    '<td><span class="om-badge om-badge--received">' + escapeHtml(row.status) + '</span></td>' +
                    '<td><span class="om-badge om-badge--closed">' + escapeHtml(row.keterangan || '-') + '</span></td>' +
                    '<td>' + escapeHtml(archivedDate) + '</td>' +
                    '</tr>'
                );
            });

            $('#modalArchivedLoading').addClass('d-none');
            $('#modalArchivedContainer').removeClass('d-none');
        }).fail(function () {
            $('#modalArchivedLoading').html('<span class="text-danger">Gagal memuat material arsip.</span>');
        });
    });

    $(document).on('change', '#selectAllArchivedMaterials', function () {
        const isChecked = this.checked;
        $('.archived-material-check').prop('checked', isChecked);
        $('#btnRestoreSelectedMaterials').prop('disabled', $('.archived-material-check:checked').length === 0);
    });

    $(document).on('change', '.archived-material-check', function () {
        const total = $('.archived-material-check').length;
        const checked = $('.archived-material-check:checked').length;
        $('#selectAllArchivedMaterials').prop('checked', total > 0 && checked === total);
        $('#selectAllArchivedMaterials').prop('indeterminate', checked > 0 && checked < total);
        $('#btnRestoreSelectedMaterials').prop('disabled', checked === 0);
    });

    $(document).on('click', '#btnRestoreSelectedMaterials', function () {
        const ids = $('.archived-material-check:checked').map(function () { return Number(this.value); }).get();
        if (!ids.length) return;
        const count = ids.length;
        const message = 'Kembalikan ' + count + ' material terpilih ke daftar aktif?';

        const proceedRestore = function () {
            const btn = $('#btnRestoreSelectedMaterials').prop('disabled', true);
            const originalHtml = btn.html();
            btn.html('<span class="spinner-border spinner-border-sm me-1"></span> Mengembalikan...');

            $.ajax({
                url: '{{ route('outstanding-materials.materials.restore') }}',
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf },
                data: { material_ids: ids }
            }).done(function (res) {
                if (archivedModal) archivedModal.hide();
                table.ajax.reload(null, false);
                if (typeof window.Swal !== 'undefined') {
                    window.Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: res.message || (count + ' material berhasil dikembalikan ke aktif.'),
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            }).fail(function (xhr) {
                const errMsg = xhr.responseJSON?.message || 'Gagal mengembalikan material.';
                if (typeof window.Swal !== 'undefined') {
                    window.Swal.fire({ icon: 'error', title: 'Gagal', text: errMsg });
                } else {
                    alert(errMsg);
                }
            }).always(function () {
                btn.prop('disabled', false).html(originalHtml);
            });
        };

        if (typeof window.Swal === 'undefined') {
            if (window.confirm(message)) proceedRestore();
            return;
        }

        window.Swal.fire({
            title: 'Restore Material?',
            text: message,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Kembalikan',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) proceedRestore();
        });
    });
    @endif
});
</script>
@endpush
