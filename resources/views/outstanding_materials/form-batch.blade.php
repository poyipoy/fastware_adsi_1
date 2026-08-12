@extends('layout')

@push('styles')
<style>
    .om-batch-card { border: 1px solid #e5e7eb; border-radius: 10px; }
    .om-batch-card .card-header { background: #f9fafb; border-bottom: 1px solid #e5e7eb; }
    .om-batch-table { min-width: 1120px; }
    .om-batch-table th { white-space: nowrap; font-size: .78rem; vertical-align: middle; }
    .om-batch-table td { vertical-align: middle; }
    .om-batch-table .form-control { min-width: 92px; }
    .om-batch-table .type-input { min-width: 150px; }
    .om-batch-table .row-number { width: 42px; text-align: center; font-weight: 600; }
</style>
@endpush

@section('content')
@php
    $isContext = (bool) $invoiceContextAnchor;
    $backUrl = $detailReturnAnchor
        ? route('outstanding-materials.show', $detailReturnAnchor)
        : route('outstanding-materials.index');
    $defaultRows = old('materials', [['type' => '', 'thickness' => '', 'width' => '', 'diameter' => '', 'length' => '', 'qty_pcs' => '', 'est_qty_kg' => '']]);
@endphp

<main id="main" class="main">
    <div class="pagetitle">
        <h1>Add Material ke Invoice</h1>
        <nav><ol class="breadcrumb">
            <li class="breadcrumb-item">Procurement</li>
            <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
            <li class="breadcrumb-item active">Add Material</li>
        </ol></nav>
    </div>

    <section class="section">
        @if ($errors->any())
            <div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ route('outstanding-materials.bulk-store') }}" id="outstandingMaterialBatchForm">
            @csrf
            @if ($invoiceContextAnchor)
                <input type="hidden" name="invoice_context_id" value="{{ $invoiceContextAnchor->id }}">
            @endif

            <div class="card om-batch-card mb-3">
                <div class="card-header"><strong><i class="bi bi-receipt me-1"></i>Invoice & Default Values</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="batch_supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                            <input id="batch_supplier" name="supplier" class="form-control" value="{{ old('supplier', $invoiceContextAnchor?->supplier) }}" @readonly($isContext) required>
                            @if ($isContext)<div class="form-text text-primary"><i class="bi bi-lock-fill me-1"></i>Supplier dikunci dari invoice context.</div>@endif
                        </div>
                        <div class="col-md-4">
                            <label for="batch_invoice" class="form-label">Number Invoice <span class="text-danger">*</span></label>
                            <input id="batch_invoice" name="number_invoice" class="form-control" value="{{ old('number_invoice', $invoiceContextAnchor?->number_invoice) }}" @readonly($isContext) required>
                            @if ($isContext)<div class="form-text text-primary"><i class="bi bi-lock-fill me-1"></i>Invoice context dikunci dari workspace.</div>@endif
                        </div>
                        <div class="col-md-4">
                            <label for="batch_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select id="batch_status" name="status" class="form-select" required>
                                <option value="">Pilih Status</option>
                                @foreach ($statusOptions as $status)<option value="{{ $status }}" @selected(old('status', $invoiceDefaults['status'] ?? '') === $status)>{{ $status }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-3"><label for="batch_eta_port" class="form-label">Estimasi ETA Port</label><input type="date" id="batch_eta_port" name="estimasi_eta_port" class="form-control" value="{{ old('estimasi_eta_port', $invoiceDefaults['estimasi_eta_port'] ?? '') }}"></div>
                        <div class="col-md-3"><label for="batch_eta_warehouse" class="form-label">Estimasi ETA Warehouse</label><input type="date" id="batch_eta_warehouse" name="estimasi_eta_warehouse" class="form-control" value="{{ old('estimasi_eta_warehouse', $invoiceDefaults['estimasi_eta_warehouse'] ?? '') }}"></div>
                        <div class="col-md-3"><label for="batch_month" class="form-label">Estimasi Bulan ETA</label><input id="batch_month" name="estimasi_bulan_eta" class="form-control" value="{{ old('estimasi_bulan_eta', $invoiceDefaults['estimasi_bulan_eta'] ?? '') }}" placeholder="Contoh: May 2026"></div>
                        <div class="col-md-3"><label for="batch_keterangan" class="form-label">Keterangan</label><select id="batch_keterangan" name="keterangan" class="form-select"><option value="">Pilih Keterangan</option>@foreach ($keteranganOptions as $option)<option value="{{ $option }}" @selected(old('keterangan', $invoiceDefaults['keterangan'] ?? '') === $option)>{{ $option }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label for="batch_delay_port" class="form-label">Estimasi Delay ETA Port</label><input type="date" id="batch_delay_port" name="estimasi_delay_eta_port" class="form-control" value="{{ old('estimasi_delay_eta_port', $invoiceDefaults['estimasi_delay_eta_port'] ?? '') }}"></div>
                        <div class="col-md-3"><label for="batch_delay_warehouse" class="form-label">Estimasi Delay ETA Warehouse</label><input type="date" id="batch_delay_warehouse" name="estimasi_delay_eta_warehouse" class="form-control" value="{{ old('estimasi_delay_eta_warehouse', $invoiceDefaults['estimasi_delay_eta_warehouse'] ?? '') }}"></div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 py-2 small"><i class="bi bi-info-circle me-1"></i>Nilai di atas menjadi default untuk seluruh baris baru. Setelah tersimpan, setiap material tetap dapat diedit secara independen.</div>
                </div>
            </div>

            <div class="card om-batch-card">
                <div class="card-header d-flex align-items-center justify-content-between gap-2"><strong><i class="bi bi-box-seam me-1"></i>Material Lines</strong><button type="button" class="btn btn-sm btn-outline-primary" id="addMaterialRow"><i class="bi bi-plus-lg me-1"></i>Add Row</button></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm om-batch-table mb-0">
                            <thead class="table-light"><tr><th>#</th><th>TYPE *</th><th>Thickness</th><th>Width</th><th>Diameter</th><th>Length</th><th>QTY PCS</th><th>Est QTY KG</th><th>Action</th></tr></thead>
                            <tbody id="materialRows">
                                @foreach ($defaultRows as $index => $row)
                                    <tr data-row-index="{{ $index }}">
                                        <td class="row-number"></td>
                                        <td><input name="materials[{{ $index }}][type]" value="{{ $row['type'] ?? '' }}" class="form-control type-input" required maxlength="255"></td>
                                        <td><input type="number" step="0.01" name="materials[{{ $index }}][thickness]" value="{{ $row['thickness'] ?? '' }}" class="form-control"></td>
                                        <td><input type="number" step="0.01" name="materials[{{ $index }}][width]" value="{{ $row['width'] ?? '' }}" class="form-control"></td>
                                        <td><input type="number" step="0.01" name="materials[{{ $index }}][diameter]" value="{{ $row['diameter'] ?? '' }}" class="form-control"></td>
                                        <td><input name="materials[{{ $index }}][length]" value="{{ $row['length'] ?? '' }}" class="form-control"></td>
                                        <td><input type="number" step="0.01" name="materials[{{ $index }}][qty_pcs]" value="{{ $row['qty_pcs'] ?? '' }}" class="form-control"></td>
                                        <td><input type="number" step="0.01" name="materials[{{ $index }}][est_qty_kg]" value="{{ $row['est_qty_kg'] ?? '' }}" class="form-control"></td>
                                        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-secondary duplicate-material-row" title="Duplicate"><i class="bi bi-copy"></i></button> <button type="button" class="btn btn-sm btn-outline-danger remove-material-row" title="Remove"><i class="bi bi-trash"></i></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="small text-muted mt-2">Maksimal {{ \App\Services\OutstandingMaterialBatchService::MAX_ROWS }} material per submit. Baris material identik tetap diperbolehkan.</div>
                    <div class="d-flex justify-content-between align-items-center mt-3"><a href="{{ $backUrl }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Materials</button></div>
                </div>
            </div>
        </form>
    </section>
</main>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.getElementById('materialRows');
    const addButton = document.getElementById('addMaterialRow');
    const maxRows = {{ \App\Services\OutstandingMaterialBatchService::MAX_ROWS }};
    let nextIndex = body.querySelectorAll('tr').length;

    const renumber = () => {
        body.querySelectorAll('tr').forEach((row, index) => {
            row.querySelector('.row-number').textContent = String(index + 1);
            row.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/materials\[\d+\]/, 'materials[' + index + ']');
            });
        });
        addButton.disabled = body.querySelectorAll('tr').length >= maxRows;
    };

    const rowTemplate = index => '<tr data-row-index="' + index + '">' +
        '<td class="row-number"></td>' +
        '<td><input name="materials[' + index + '][type]" class="form-control type-input" required maxlength="255"></td>' +
        '<td><input type="number" step="0.01" name="materials[' + index + '][thickness]" class="form-control"></td>' +
        '<td><input type="number" step="0.01" name="materials[' + index + '][width]" class="form-control"></td>' +
        '<td><input type="number" step="0.01" name="materials[' + index + '][diameter]" class="form-control"></td>' +
        '<td><input name="materials[' + index + '][length]" class="form-control"></td>' +
        '<td><input type="number" step="0.01" name="materials[' + index + '][qty_pcs]" class="form-control"></td>' +
        '<td><input type="number" step="0.01" name="materials[' + index + '][est_qty_kg]" class="form-control"></td>' +
        '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-secondary duplicate-material-row" title="Duplicate"><i class="bi bi-copy"></i></button> <button type="button" class="btn btn-sm btn-outline-danger remove-material-row" title="Remove"><i class="bi bi-trash"></i></button></td>' +
        '</tr>';

    addButton.addEventListener('click', () => {
        if (body.querySelectorAll('tr').length >= maxRows) return;
        body.insertAdjacentHTML('beforeend', rowTemplate(nextIndex++));
        renumber();
        body.lastElementChild.querySelector('input').focus();
    });

    body.addEventListener('click', event => {
        const remove = event.target.closest('.remove-material-row');
        const duplicate = event.target.closest('.duplicate-material-row');
        if (remove) {
            if (body.querySelectorAll('tr').length > 1) remove.closest('tr').remove();
            renumber();
            return;
        }
        if (duplicate && body.querySelectorAll('tr').length < maxRows) {
            const source = duplicate.closest('tr');
            const clone = source.cloneNode(true);
            body.appendChild(clone);
            renumber();
        }
    });

    renumber();
});
</script>
@endpush
@endsection
