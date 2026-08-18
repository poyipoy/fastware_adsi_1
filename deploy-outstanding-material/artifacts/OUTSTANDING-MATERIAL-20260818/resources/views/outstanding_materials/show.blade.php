@extends('layout')

@push('styles')
<style>
    .om-workspace-header { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:flex-start; gap:1rem; margin-bottom:1.25rem; }
    .om-workspace-header h1 { margin:0; font-size:1.55rem; }
    .om-invoice-identity { color:#6b7280; font-size:.9rem; margin-top:.35rem; }
    .om-summary-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:8px; margin-bottom:1rem; }
    .om-summary-card { display:flex; align-items:center; gap:8px; background:#fff; border:1px solid #e5e7eb; border-left:3px solid #2563eb; border-radius:8px; padding:10px 12px; }
    .om-summary-card--production { border-left-color:#d97706; } .om-summary-card--shipment { border-left-color:#0891b2; } .om-summary-card--received { border-left-color:#059669; }
    .om-summary-icon { width:34px; height:34px; display:flex; align-items:center; justify-content:center; border-radius:7px; background:#eff6ff; color:#2563eb; font-size:.9rem; }
    .om-summary-content { display:flex; flex-direction:column; min-width:0; } .om-summary-value { font-size:1.05rem; font-weight:700; line-height:1.2; } .om-summary-label { color:#6b7280; font-size:.68rem; text-transform:uppercase; letter-spacing:.04em; } .om-summary-meta { color:#6b7280; font-size:.72rem; line-height:1.25; margin-top:2px; }
    .om-card { border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 1px 2px rgba(0,0,0,.05); } .om-card .card-body { padding:20px; }
    .om-card-header { display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:10px; padding-bottom:14px; margin-bottom:14px; border-bottom:1px solid #e5e7eb; }
    .om-card-title { margin:0; font-size:1.05rem; font-weight:600; } .om-toolbar { display:flex; flex-wrap:wrap; gap:7px; }
    .om-table-wrap { max-height:70vh; overflow:auto; overscroll-behavior-x:contain; overscroll-behavior-y:auto; position:relative; border:1px solid #e5e7eb; border-radius:8px; }
    .om-table { width:100%; min-width:1500px; border-collapse:separate; border-spacing:0; margin:0; }
    .om-table thead th { background:#f9fafb; white-space:nowrap; text-align:center; vertical-align:middle; font-size:.74rem; padding:9px 10px; border-bottom:2px solid #e5e7eb; }
    .om-table thead > tr.om-column-header > th { position:sticky; top:var(--om-table-filter-height,0px); z-index:3; background:#f9fafb; } .om-table tbody td { white-space:nowrap; vertical-align:middle; font-size:.82rem; padding:9px 10px; border-bottom:1px solid #f3f4f6; }
    .om-table tbody tr:nth-child(even) { background:#f9fafb; } .om-table tbody tr:hover { background:#eff6ff; }
    .om-filter-row th { position:sticky; top:0; z-index:4; background:#fff!important; padding:5px!important; }
    table.om-table.dataTable thead > tr.om-column-header > th { position:sticky!important; top:var(--om-table-filter-height,0px)!important; z-index:3!important; background:#f9fafb!important; }
    table.om-table.dataTable thead > tr.om-filter-row > th { position:sticky!important; top:0!important; z-index:4!important; background:#fff!important; }
    .om-inline-filter,.om-inline-date-btn { width:100%; min-width:0; box-sizing:border-box; font-size:.75rem; padding:5px 7px; border:1px solid #d1d5db; border-radius:6px; background:#fff; }
    .om-inline-date-btn { text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .om-date-range { position:relative; } .om-date-range-panel { display:none; position:absolute; left:0; top:calc(100% + 3px); width:220px; padding:10px; background:#fff; border:1px solid #e5e7eb; border-radius:7px; box-shadow:0 4px 12px rgba(0,0,0,.12); z-index:30; }
    .om-date-range.is-open .om-date-range-panel { display:block; } .om-date-range-panel input[type=date] { width:100%; font-size:.75rem; margin:3px 0 7px; }
    .om-badge { display:inline-block; padding:.25em .55em; border-radius:999px; font-size:.72rem; background:#f3f4f6; color:#374151; } .om-badge--production { background:#fff7ed; color:#c2410c; } .om-badge--shipment { background:#ecfeff; color:#0e7490; } .om-badge--received { background:#ecfdf5; color:#047857; }
    .om-actions { display:inline-flex; align-items:center; justify-content:center; gap:5px; } .om-action-btn { width:30px; height:30px; display:inline-flex; align-items:center; justify-content:center; border:1px solid #d1d5db; border-radius:6px; background:#fff; color:#374151; text-decoration:none; } .om-action-btn:hover { background:#eff6ff; color:#1d4ed8; }
    .om-empty-note { color:#6b7280; font-size:.84rem; }
    @media (max-width:768px) { .om-card .card-body { padding:12px; } .om-workspace-header h1 { font-size:1.25rem; } }
</style>
@endpush

@section('content')
<main id="main" class="main">
    <div class="om-workspace-header pagetitle">
        <div>
            <h1>Outstanding Material</h1>
            <nav><ol class="breadcrumb mb-0"><li class="breadcrumb-item">Procurement</li><li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li><li class="breadcrumb-item active">Invoice Workspace</li></ol></nav>
            <div class="om-invoice-identity">Invoice: <strong>{{ $invoiceNumber ?: 'Unassigned / single material' }}</strong> · Anchor #{{ $anchorMaterial->id }}</div>
        </div>
    </div>

    <section class="section">
        @if (session('success')) <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif
        @if (session('warning')) <div class="alert alert-warning alert-dismissible fade show" role="alert">{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif
        @if (!empty($errors) && $errors->any()) <div class="alert alert-danger alert-dismissible fade show" role="alert"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif

        <div class="om-summary-row">
            <div class="om-summary-card"><div class="om-summary-icon"><i class="bi bi-boxes"></i></div><div class="om-summary-content"><span class="om-summary-value">{{ number_format($summary['total']) }}</span><span class="om-summary-label">Total Material</span><span class="om-summary-meta">Total KG: {{ number_format($summary['est_qty_kg'], 2) }}</span></div></div>
            <div class="om-summary-card"><div class="om-summary-icon"><i class="bi bi-calculator"></i></div><div class="om-summary-content"><span class="om-summary-value">{{ number_format($summary['qty_pcs'], 2) }}</span><span class="om-summary-label">Total QTY PCS</span></div></div>
            <div class="om-summary-card om-summary-card--production"><div class="om-summary-icon"><i class="bi bi-gear-wide-connected"></i></div><div class="om-summary-content"><span class="om-summary-value">{{ number_format($summary['on_production']) }}</span><span class="om-summary-label">On Production</span></div></div>
            <div class="om-summary-card om-summary-card--shipment"><div class="om-summary-icon"><i class="bi bi-truck"></i></div><div class="om-summary-content"><span class="om-summary-value">{{ number_format($summary['on_shipment']) }}</span><span class="om-summary-label">On Shipment</span></div></div>
            <div class="om-summary-card om-summary-card--received"><div class="om-summary-icon"><i class="bi bi-check2-circle"></i></div><div class="om-summary-content"><span class="om-summary-value">{{ number_format($summary['received']) }}</span><span class="om-summary-label">Received</span></div></div>
        </div>

        <div class="card om-card"><div class="card-body">
            <div class="om-card-header">
                <h5 class="om-card-title">Invoice Material Workspace</h5>
                <div class="om-toolbar">
                    <a href="{{ route('outstanding-materials.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    <a href="{{ route('outstanding-materials.invoice.index') }}" class="btn btn-sm btn-outline-dark"><i class="bi bi-receipt me-1"></i>Invoice List</a>
                    @if ($canExportInvoice)
                        <a id="btnInvoiceExport" href="{{ route('outstanding-materials.invoice-detail.export', $anchorMaterial) }}" class="btn btn-sm btn-outline-success"><i class="bi bi-file-earmark-excel me-1"></i>Export Invoice</a>
                    @endif
                    @if ($canManageOutstandingMaterials)
                        <a href="{{ route('outstanding-materials.create', ['invoice_context' => $anchorMaterial->id]) }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Material</a>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importOutstandingMaterialModal"><i class="bi bi-upload me-1"></i>Import</button>
                        <a href="{{ route('outstanding-materials.template') }}" class="btn btn-sm btn-outline-success"><i class="bi bi-download me-1"></i>Template</a>
                    @endif
                    <button type="button" id="btnInvoiceReset" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter</button>
                </div>
            </div>
            @if ($canManageOutstandingMaterials)
                <p class="om-empty-note mb-3"><i class="bi bi-info-circle me-1"></i>Import memproses seluruh baris pada file dan tidak dibatasi pada invoice yang sedang dibuka.</p>
            @endif
            <div class="om-table-wrap"><table id="outstandingInvoiceDetailTable" class="om-table">
                <thead>
                    <tr class="om-filter-row">
                        <th></th><th><select id="filter_supplier" class="om-inline-filter"><option value="">All</option>@foreach ($filterOptions['suppliers'] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></th><th><select id="filter_type" class="om-inline-filter"><option value="">All</option>@foreach ($filterOptions['types'] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></th>
                        <th><input id="filter_thickness" class="om-inline-filter"></th><th><input id="filter_width" class="om-inline-filter"></th><th><input id="filter_diameter" class="om-inline-filter"></th><th><input id="filter_material_length" class="om-inline-filter"></th><th><input id="filter_qty_pcs" class="om-inline-filter"></th><th><input id="filter_est_qty_kg" class="om-inline-filter"></th><th><span class="text-muted small">Locked</span></th><th><select id="filter_status" class="om-inline-filter"><option value="">All</option>@foreach ($statusOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></th>
                        @foreach (['eta_port' => 'ETA Port','eta_warehouse' => 'ETA Warehouse'] as $prefix => $label)<th><div class="om-date-range" data-range-filter><button type="button" id="filter_{{ $prefix }}_display" class="om-inline-date-btn">Pilih tanggal</button><input type="hidden" id="filter_{{ $prefix }}_from"><input type="hidden" id="filter_{{ $prefix }}_to"><div class="om-date-range-panel"><label>From</label><input type="date" id="filter_{{ $prefix }}_from_picker"><label>To</label><input type="date" id="filter_{{ $prefix }}_to_picker"><div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-primary flex-fill" data-range-apply data-prefix="filter_{{ $prefix }}">Apply</button><button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-range-clear data-prefix="filter_{{ $prefix }}">Clear</button></div></div></div></th>@endforeach
                        <th><select id="filter_estimasi_bulan_eta" class="om-inline-filter"><option value="">All</option>@foreach ($filterOptions['months'] as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></th><th><select id="filter_keterangan" class="om-inline-filter"><option value="">All</option>@foreach ($keteranganOptions as $value)<option value="{{ $value }}">{{ $value }}</option>@endforeach</select></th>
                        @foreach (['delay_eta_port' => 'Delay Port','delay_eta_warehouse' => 'Delay Warehouse'] as $prefix => $label)<th><div class="om-date-range" data-range-filter><button type="button" id="filter_{{ $prefix }}_display" class="om-inline-date-btn">Pilih tanggal</button><input type="hidden" id="filter_{{ $prefix }}_from"><input type="hidden" id="filter_{{ $prefix }}_to"><div class="om-date-range-panel"><label>From</label><input type="date" id="filter_{{ $prefix }}_from_picker"><label>To</label><input type="date" id="filter_{{ $prefix }}_to_picker"><div class="d-flex gap-1"><button type="button" class="btn btn-sm btn-primary flex-fill" data-range-apply data-prefix="filter_{{ $prefix }}">Apply</button><button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-range-clear data-prefix="filter_{{ $prefix }}">Clear</button></div></div></div></th>@endforeach
                        <th></th><th></th><th></th>
                    </tr>
                    <tr class="om-column-header">
                        <th>NO</th><th>Supplier</th><th>TYPE</th><th>Thickness</th><th>Width</th><th>Diameter</th><th>Length</th><th>QTY<br>(PCS)</th><th>Est QTY<br>(KG)</th><th>Number<br>Invoice</th><th>Status</th><th>ETA Port</th><th>ETA Warehouse</th><th>Bulan ETA</th><th>Keterangan</th><th>Delay Port</th><th>Delay Warehouse</th><th>Packing List</th><th>MTC</th><th>Action</th>
                    </tr>
                </thead><tbody></tbody>
            </table></div>
        </div></div>
    </section>
</main>

@if ($canManageOutstandingMaterials)
<div class="modal fade" id="importOutstandingMaterialModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><form method="POST" action="{{ route('outstanding-materials.import') }}" enctype="multipart/form-data">@csrf<div class="modal-header"><h5 class="modal-title">Import Multi-Invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label for="import_file" class="form-label">File Excel / CSV</label><input type="file" class="form-control" id="import_file" name="import_file" accept=".xlsx,.xls,.csv" required><div class="form-text">Satu file dapat berisi banyak supplier, invoice, dan material. Setiap baris valid akan ditambahkan, termasuk material yang datanya sama persis. Preview akan muncul sebelum data disimpan.</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Preview Import</button></div></form></div></div></div>
@endif
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script><script src="{{ asset('assets/js/outstanding-materials/sticky-table.js') }}?v={{ filemtime(public_path('assets/js/outstanding-materials/sticky-table.js')) }}"></script><script src="{{ asset('assets/js/outstanding-materials/delete-confirmation.js') }}?v={{ filemtime(public_path('assets/js/outstanding-materials/delete-confirmation.js')) }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.jQuery || !window.jQuery.fn.DataTable) return;
    const $ = window.jQuery;
    const prefixes = ['eta_port','eta_warehouse','delay_eta_port','delay_eta_warehouse'];
    const displayDate = value => value ? value.split('-').reverse().join('/') : 'Pilih tanggal';
    const syncDate = prefix => { const from = $('#' + prefix + '_from').val(), to = $('#' + prefix + '_to').val(); $('#' + prefix + '_display').text(from && to ? displayDate(from) + ' — ' + displayDate(to) : (from ? 'Mulai ' + displayDate(from) : (to ? 'Sampai ' + displayDate(to) : 'Pilih tanggal'))); };
    const filters = () => ({ q: table ? table.search() : '', supplier: $('#filter_supplier').val(), type: $('#filter_type').val(), thickness: $('#filter_thickness').val(), width: $('#filter_width').val(), diameter: $('#filter_diameter').val(), material_length: $('#filter_material_length').val(), qty_pcs: $('#filter_qty_pcs').val(), est_qty_kg: $('#filter_est_qty_kg').val(), status: $('#filter_status').val(), keterangan: $('#filter_keterangan').val(), estimasi_bulan_eta: $('#filter_estimasi_bulan_eta').val(), eta_port_from: $('#filter_eta_port_from').val(), eta_port_to: $('#filter_eta_port_to').val(), eta_warehouse_from: $('#filter_eta_warehouse_from').val(), eta_warehouse_to: $('#filter_eta_warehouse_to').val(), delay_eta_port_from: $('#filter_delay_eta_port_from').val(), delay_eta_port_to: $('#filter_delay_eta_port_to').val(), delay_eta_warehouse_from: $('#filter_delay_eta_warehouse_from').val(), delay_eta_warehouse_to: $('#filter_delay_eta_warehouse_to').val() });
    const exportUrl = () => { const params = new URLSearchParams(); Object.entries(filters()).forEach(([key,value]) => value && params.set(key,value)); const query = params.toString(); return '{{ route('outstanding-materials.invoice-detail.export', $anchorMaterial) }}' + (query ? '?' + query : ''); };
    let table = null;
    table = $('#outstandingInvoiceDetailTable').DataTable({ processing:true, serverSide:true, searching:true, searchDelay:400, orderCellsTop:false, scrollX:false, ajax:{ url:'{{ route('outstanding-materials.invoice-detail.data', $anchorMaterial) }}', data:data => Object.assign(data, filters()) }, columns:[{data:null,orderable:false,searchable:false,className:'text-center',render:(d,t,r,m)=>m.row+m.settings._iDisplayStart+1},{data:'supplier'},{data:'type'},{data:'thickness',className:'text-end'},{data:'width',className:'text-end'},{data:'diameter',className:'text-end'},{data:'length',className:'text-end'},{data:'qty_pcs',className:'text-end'},{data:'est_qty_kg',className:'text-end'},{data:'number_invoice'},{data:'status',className:'text-center'},{data:'estimasi_eta_port'},{data:'estimasi_eta_warehouse'},{data:'estimasi_bulan_eta'},{data:'keterangan'},{data:'estimasi_delay_eta_port'},{data:'estimasi_delay_eta_warehouse'},{data:'packing_list',className:'text-center'},{data:'mtc',className:'text-center'},{data:'actions',orderable:false,searchable:false,className:'text-center'}], order:[[1,'asc']], pageLength:10, lengthMenu:[[10,25,50,100],[10,25,50,100]], language:{processing:'Memuat data...',lengthMenu:'Tampilkan _MENU_ data per halaman',zeroRecords:'Data tidak ditemukan',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Tidak ada data yang tersedia',paginate:{next:'›',previous:'‹'}}, drawCallback:()=> $('[data-bs-toggle="tooltip"]').tooltip() });
    window.OutstandingMaterialStickyTable && window.OutstandingMaterialStickyTable.install('#outstandingInvoiceDetailTable');
    const reload = () => { table.ajax.reload(); $('#btnInvoiceExport').attr('href', exportUrl()); };
    $('.om-inline-filter').on('change input', reload); $('#outstandingInvoiceDetailTable thead').on('click keydown','input,select,button',e=>e.stopPropagation());
    $('[data-range-filter]').on('click','.om-inline-date-btn',function(e){ e.stopPropagation(); const wrapper=$(this).closest('[data-range-filter]'); $('[data-range-filter]').not(wrapper).removeClass('is-open'); wrapper.toggleClass('is-open'); });
    $('[data-range-apply]').on('click',function(){ const prefix=$(this).data('prefix'); let from=$('#'+prefix+'_from_picker').val(),to=$('#'+prefix+'_to_picker').val(); if(from&&to&&from>to){[from,to]=[to,from];} $('#'+prefix+'_from').val(from); $('#'+prefix+'_to').val(to); syncDate(prefix); $(this).closest('[data-range-filter]').removeClass('is-open'); reload(); });
    $('[data-range-clear]').on('click',function(){ const prefix=$(this).data('prefix'); $('#'+prefix+'_from,#'+prefix+'_to,#'+prefix+'_from_picker,#'+prefix+'_to_picker').val(''); syncDate(prefix); $(this).closest('[data-range-filter]').removeClass('is-open'); reload(); });
    $(document).on('click',()=> $('[data-range-filter]').removeClass('is-open')); $('#btnInvoiceReset').on('click',function(){ $('.om-inline-filter').val(''); prefixes.forEach(prefix=>{ $('#'+prefix+'_from,#'+prefix+'_to,#'+prefix+'_from_picker,#'+prefix+'_to_picker').val(''); syncDate(prefix); }); table.search(''); reload(); }); table.on('search.dt draw.dt',()=>$('#btnInvoiceExport').attr('href',exportUrl()));
});
</script>
@endpush
