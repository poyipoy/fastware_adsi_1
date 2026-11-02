@extends('layout')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet">
<style>
/* ==========================================================================
   Archived Materials — Read-Only Enterprise Theme
   ========================================================================== */
:root {
    --om-primary: #2563eb;
    --om-primary-light: #eff6ff;
    --om-primary-muted: #93c5fd;
    --om-success: #059669;
    --om-success-light: #ecfdf5;
    --om-warning: #d97706;
    --om-warning-light: #fffbeb;
    --om-danger: #dc2626;
    --om-danger-light: #fef2f2;
    --om-info: #0891b2;
    --om-info-light: #ecfeff;
    --om-gray-50: #f9fafb;
    --om-gray-100: #f3f4f6;
    --om-gray-200: #e5e7eb;
    --om-gray-300: #d1d5db;
    --om-gray-400: #9ca3af;
    --om-gray-500: #6b7280;
    --om-gray-600: #4b5563;
    --om-gray-700: #374151;
    --om-gray-800: #1f2937;
    --om-gray-900: #111827;
    --om-radius: 8px;
    --om-radius-sm: 6px;
    --om-shadow-sm: 0 1px 2px rgba(0,0,0,.05);
    --om-shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
    --om-transition: 150ms cubic-bezier(.4,0,.2,1);
}

.om-summary-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 8px;
    margin-bottom: 16px;
}

.om-summary-card {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #fff;
    border: 1px solid var(--om-gray-200);
    border-left: 3px solid var(--om-gray-300);
    border-radius: var(--om-radius);
    padding: 10px 12px;
    transition: box-shadow var(--om-transition), transform var(--om-transition);
}

.om-summary-card:hover {
    box-shadow: var(--om-shadow-md);
    transform: translateY(-1px);
}

.om-summary-card--total { border-left-color: var(--om-primary); }
.om-summary-card--production { border-left-color: var(--om-warning); }
.om-summary-card--shipment { border-left-color: var(--om-info); }
.om-summary-card--received { border-left-color: var(--om-success); }

.om-summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: var(--om-radius);
    font-size: 16px;
    flex-shrink: 0;
}

.om-summary-card--total .om-summary-icon { background: var(--om-primary-light); color: var(--om-primary); }
.om-summary-card--production .om-summary-icon { background: var(--om-warning-light); color: var(--om-warning); }
.om-summary-card--shipment .om-summary-icon { background: var(--om-info-light); color: var(--om-info); }
.om-summary-card--received .om-summary-icon { background: var(--om-success-light); color: var(--om-success); }

.om-summary-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.om-summary-value {
    font-size: 1.05rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--om-gray-900);
    font-family: "Nunito", sans-serif;
}

.om-summary-label {
    font-size: .68rem;
    font-weight: 500;
    color: var(--om-gray-500);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 2px;
}

.om-summary-meta {
    color: var(--om-gray-500);
    font-size: .72rem;
    line-height: 1.25;
    margin-top: 2px;
}

.om-card {
    border: 1px solid var(--om-gray-200);
    border-radius: var(--om-radius);
    box-shadow: var(--om-shadow-sm);
}

.om-card .card-body {
    padding: 20px 24px 24px;
}

.om-card-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: center;
    gap: 12px;
    padding-bottom: 16px;
    margin-bottom: 16px;
    border-bottom: 1px solid var(--om-gray-200);
}

.om-card-title {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--om-gray-800);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: "Nunito", sans-serif;
}

.om-toolbar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
}

.om-table-wrap {
    max-height: 70vh;
    overflow: auto;
    overscroll-behavior-x: contain;
    overscroll-behavior-y: auto;
    position: relative;
    border: 1px solid var(--om-gray-200);
    border-radius: var(--om-radius);
}

.om-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin-bottom: 0;
}

.om-table thead th {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    font-size: .78rem;
    font-weight: 600;
    color: var(--om-gray-600);
    text-transform: uppercase;
    letter-spacing: .04em;
    background: var(--om-gray-50);
    border-bottom: 2px solid var(--om-gray-200);
    padding: 10px 12px;
}

.om-table thead > tr.om-column-header > th {
    position: sticky;
    top: var(--om-table-filter-height, 0px);
    z-index: 3;
    background: var(--om-gray-50);
}

/* Inline filter row */
.om-filter-row th {
    padding: 6px 6px !important;
    vertical-align: middle !important;
    background: #fff !important;
    border-bottom: 2px solid var(--om-gray-200) !important;
    text-transform: none !important;
    letter-spacing: 0 !important;
    position: sticky;
    top: 0;
    z-index: 4;
}

/* DataTables' sorting selectors are more specific than the base sticky rule. */
table.om-table.dataTable thead > tr.om-column-header > th {
    position: sticky !important;
    top: var(--om-table-filter-height, 0px) !important;
    z-index: 3 !important;
    background: var(--om-gray-50) !important;
}

table.om-table.dataTable thead > tr.om-filter-row > th {
    position: sticky !important;
    top: 0 !important;
    z-index: 4 !important;
    background: #fff !important;
}

.om-table tbody td {
    vertical-align: middle;
    white-space: nowrap;
    font-size: .83rem;
    color: var(--om-gray-700);
    padding: 8px 12px;
    border-bottom: 1px solid var(--om-gray-100);
}

.om-table tbody tr:nth-child(even) { background: var(--om-gray-50); }
.om-table tbody tr:hover { background: var(--om-primary-light); }

.om-inline-filter {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    font-size: .78rem;
    padding: 4px 6px;
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    background: var(--om-gray-50);
    color: var(--om-gray-700);
    outline: none;
    transition: border-color var(--om-transition), box-shadow var(--om-transition);
}

.om-inline-filter:focus {
    border-color: var(--om-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .15);
    background: #fff;
}

/* Select2 filter dropdown styling */
.om-filter-row .select2-container {
    width: 100% !important;
}
.om-filter-row .select2-container .select2-selection--single {
    height: 30px;
    font-size: .8rem;
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    background-color: var(--om-gray-50);
}
.om-filter-row .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 28px;
    padding-left: 8px;
    padding-right: 20px;
    color: var(--om-gray-700);
    font-size: .78rem;
}
.om-filter-row .select2-selection__arrow {
    height: 28px !important;
    right: 4px;
}
.om-filter-row .select2-container--default .select2-selection--single .select2-selection__clear {
    margin-right: 18px;
    line-height: 28px;
}
.om-filter-row .select2-container--default.select2-container--focus .select2-selection--single,
.om-filter-row .select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--om-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
    background: #fff;
}

.om-date-range {
    position: relative;
}

.om-date-range-panel {
    display: none;
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    width: 240px;
    padding: 14px;
    background: #fff;
    border: 1px solid var(--om-gray-200);
    border-radius: var(--om-radius);
    box-shadow: var(--om-shadow-md);
    z-index: 30;
    white-space: normal;
    text-align: left;
}

.om-date-range.is-open .om-date-range-panel {
    display: block;
}

.om-date-range-panel label {
    display: block;
    font-size: .75rem;
    font-weight: 600;
    color: var(--om-gray-500);
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: 4px;
}

.om-date-range-panel input[type="date"] {
    display: block;
    width: 100%;
    font-size: .82rem;
    padding: 6px 10px;
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    margin-bottom: 10px;
}

.om-date-range-panel input[type="date"]:focus {
    outline: none;
    border-color: var(--om-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
}

.om-inline-date-btn {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    font-size: .78rem;
    padding: 5px 8px;
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    background: var(--om-gray-50);
    color: var(--om-gray-700);
    cursor: pointer;
    text-align: left;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    transition: border-color var(--om-transition), box-shadow var(--om-transition);
}

.om-inline-date-btn:hover {
    border-color: var(--om-primary);
    background: #fff;
}

.om-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: .75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 999px;
    white-space: nowrap;
    line-height: 1.4;
}

.om-badge--production { background: var(--om-warning-light); color: #92400e; }
.om-badge--shipment { background: var(--om-info-light); color: #155e75; }
.om-badge--received { background: var(--om-success-light); color: #065f46; }
.om-badge--on-schedule { background: var(--om-success-light); color: #065f46; }
.om-badge--delay { background: var(--om-danger-light); color: #991b1b; }
.om-badge--closed { background: var(--om-gray-100); color: var(--om-gray-600); }

.om-actions {
    display: flex;
    gap: 4px;
    justify-content: center;
    align-items: center;
}

.om-action-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: var(--om-radius-sm);
    border: 1px solid var(--om-info);
    background: transparent;
    color: var(--om-info);
    font-size: 15px;
    cursor: pointer;
    transition: all var(--om-transition);
    text-decoration: none;
    padding: 0;
}

.om-action-btn:hover {
    background: var(--om-info);
    color: #fff;
}

.om-link {
    color: var(--om-primary);
    font-weight: 500;
    text-decoration: none;
    transition: color var(--om-transition);
}

.om-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.om-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--om-gray-400);
}

.om-empty-state i {
    font-size: 48px;
    margin-bottom: 12px;
    display: block;
}

.om-datatable-wrapper .dataTables_filter {
    display: none !important;
}

.om-datatable-wrapper .dataTables_info {
    font-size: .82rem;
    color: var(--om-gray-500);
    padding-top: 12px;
}

.om-datatable-wrapper .dataTables_length {
    font-size: .85rem;
    color: var(--om-gray-600);
    margin-bottom: 16px;
}

.om-datatable-wrapper .dataTables_length select {
    width: auto;
    display: inline-block;
    font-size: .85rem;
    padding: 4px 28px 4px 10px;
    border-color: var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    background-color: #fff;
    cursor: pointer;
    outline: none;
}

.om-datatable-wrapper .dataTables_paginate .paginate_button {
    border-radius: var(--om-radius-sm) !important;
    font-size: .82rem !important;
    padding: 4px 10px !important;
    margin: 0 2px !important;
}

.om-datatable-wrapper .dataTables_paginate .paginate_button.current {
    background: var(--om-primary) !important;
    color: #fff !important;
    border-color: var(--om-primary) !important;
}

@media (max-width: 768px) {
    .om-summary-row { grid-template-columns: 1fr 1fr; gap: 8px; }
    .om-card .card-body { padding: 14px 16px 16px; }
}
@media (max-width: 480px) {
    .om-summary-row { grid-template-columns: 1fr; }
}
</style>
@endpush

@section('content')
<main id="main" class="main">
    <div class="pagetitle">
        <h1>Archived Materials</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item active">Archived Materials</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>{{ session('warning') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- ── Summary Cards ── --}}
        <div class="om-summary-row">
            <div class="om-summary-card om-summary-card--total">
                <div class="om-summary-icon"><i class="bi bi-boxes"></i></div>
                <div class="om-summary-content">
                    <span class="om-summary-value">{{ number_format($summary['total']) }}</span>
                    <span class="om-summary-label">Total Material</span>
                    <span class="om-summary-meta">Total KG: {{ number_format($summary['est_qty_kg'], 2) }}</span>
                </div>
            </div>
            <div class="om-summary-card om-summary-card--production">
                <div class="om-summary-icon"><i class="bi bi-gear-wide-connected"></i></div>
                <div class="om-summary-content">
                    <span class="om-summary-value">{{ number_format($summary['on_production']) }}</span>
                    <span class="om-summary-label">On Production</span>
                </div>
            </div>
            <div class="om-summary-card om-summary-card--shipment">
                <div class="om-summary-icon"><i class="bi bi-truck"></i></div>
                <div class="om-summary-content">
                    <span class="om-summary-value">{{ number_format($summary['on_shipment']) }}</span>
                    <span class="om-summary-label">On Shipment</span>
                </div>
            </div>
            <div class="om-summary-card om-summary-card--received">
                <div class="om-summary-icon"><i class="bi bi-check2-circle"></i></div>
                <div class="om-summary-content">
                    <span class="om-summary-value">{{ number_format($summary['received']) }}</span>
                    <span class="om-summary-label">Received</span>
                </div>
            </div>
        </div>

        {{-- ── Main Card ── --}}
        <div class="card om-card">
            <div class="card-body">
                <div class="om-card-header">
                    <h5 class="om-card-title"><i class="bi bi-archive me-2 text-warning"></i>Daftar Material Diarsipkan</h5>
                    <div class="om-toolbar">
                        <a href="{{ route('outstanding-materials.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-box-seam me-1"></i>Active Materials
                        </a>
                        <a href="{{ route('outstanding-materials.invoice.archived.index') }}" class="btn btn-outline-warning">
                            <i class="bi bi-archive me-1"></i>Archived Invoices
                        </a>
                        <a href="{{ route('outstanding-materials.invoice.index') }}" class="btn btn-outline-dark">
                            <i class="bi bi-receipt me-1"></i>Active Invoices
                        </a>
                        <button type="button" id="btnArchivedReset" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filter
                        </button>
                    </div>
                </div>

                {{-- Table Wrap --}}
                <div class="om-table-wrap om-datatable-wrapper">
                    <table id="archivedMaterialTable" class="om-table" style="width:100%">
                        <thead>
                            {{-- Inline Filter Row --}}
                            <tr class="om-filter-row">
                                <th></th>
                                <th>
                                    <select id="filter_supplier" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($filterOptions['suppliers'] as $supplier)
                                            <option value="{{ $supplier }}">{{ $supplier }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <select id="filter_type" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($filterOptions['types'] as $type)
                                            <option value="{{ $type }}">{{ $type }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th><input type="text" id="filter_thickness" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th><input type="text" id="filter_width" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th><input type="text" id="filter_diameter" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th><input type="text" id="filter_material_length" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th><input type="text" id="filter_qty_pcs" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th><input type="text" id="filter_est_qty_kg" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th>
                                    <select id="filter_number_invoice" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($filterOptions['invoices'] as $invoice)
                                            <option value="{{ $invoice }}">{{ $invoice }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <select id="filter_status" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($statusOptions as $status)
                                            <option value="{{ $status }}">{{ $status }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <div class="om-date-range" data-range-filter>
                                        <button type="button" id="filter_eta_port_display" class="om-inline-date-btn">Pilih tanggal</button>
                                        <input type="hidden" id="filter_eta_port_from">
                                        <input type="hidden" id="filter_eta_port_to">
                                        <div class="om-date-range-panel">
                                            <label for="filter_eta_port_from_picker">From</label>
                                            <input type="date" id="filter_eta_port_from_picker">
                                            <label for="filter_eta_port_to_picker">To</label>
                                            <input type="date" id="filter_eta_port_to_picker">
                                            <div class="d-flex gap-1 mt-2">
                                                <button type="button" class="btn btn-sm btn-primary flex-fill" data-range-apply data-prefix="filter_eta_port">Apply</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-range-clear data-prefix="filter_eta_port">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="om-date-range" data-range-filter>
                                        <button type="button" id="filter_eta_warehouse_display" class="om-inline-date-btn">Pilih tanggal</button>
                                        <input type="hidden" id="filter_eta_warehouse_from">
                                        <input type="hidden" id="filter_eta_warehouse_to">
                                        <div class="om-date-range-panel">
                                            <label for="filter_eta_warehouse_from_picker">From</label>
                                            <input type="date" id="filter_eta_warehouse_from_picker">
                                            <label for="filter_eta_warehouse_to_picker">To</label>
                                            <input type="date" id="filter_eta_warehouse_to_picker">
                                            <div class="d-flex gap-1 mt-2">
                                                <button type="button" class="btn btn-sm btn-primary flex-fill" data-range-apply data-prefix="filter_eta_warehouse">Apply</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-range-clear data-prefix="filter_eta_warehouse">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <select id="filter_estimasi_bulan_eta" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($filterOptions['months'] as $month)
                                            <option value="{{ $month }}">{{ $month }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <select id="filter_keterangan" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($keteranganOptions as $keterangan)
                                            <option value="{{ $keterangan }}">{{ $keterangan }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th>
                                    <div class="om-date-range" data-range-filter>
                                        <button type="button" id="filter_delay_eta_port_display" class="om-inline-date-btn">Pilih tanggal</button>
                                        <input type="hidden" id="filter_delay_eta_port_from">
                                        <input type="hidden" id="filter_delay_eta_port_to">
                                        <div class="om-date-range-panel">
                                            <label for="filter_delay_eta_port_from_picker">From</label>
                                            <input type="date" id="filter_delay_eta_port_from_picker">
                                            <label for="filter_delay_eta_port_to_picker">To</label>
                                            <input type="date" id="filter_delay_eta_port_to_picker">
                                            <div class="d-flex gap-1 mt-2">
                                                <button type="button" class="btn btn-sm btn-primary flex-fill" data-range-apply data-prefix="filter_delay_eta_port">Apply</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-range-clear data-prefix="filter_delay_eta_port">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <div class="om-date-range" data-range-filter>
                                        <button type="button" id="filter_delay_eta_warehouse_display" class="om-inline-date-btn">Pilih tanggal</button>
                                        <input type="hidden" id="filter_delay_eta_warehouse_from">
                                        <input type="hidden" id="filter_delay_eta_warehouse_to">
                                        <div class="om-date-range-panel">
                                            <label for="filter_delay_eta_warehouse_from_picker">From</label>
                                            <input type="date" id="filter_delay_eta_warehouse_from_picker">
                                            <label for="filter_delay_eta_warehouse_to_picker">To</label>
                                            <input type="date" id="filter_delay_eta_warehouse_to_picker">
                                            <div class="d-flex gap-1 mt-2">
                                                <button type="button" class="btn btn-sm btn-primary flex-fill" data-range-apply data-prefix="filter_delay_eta_warehouse">Apply</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary flex-fill" data-range-clear data-prefix="filter_delay_eta_warehouse">Clear</button>
                                            </div>
                                        </div>
                                    </div>
                                </th>
                                <th>
                                    <select id="filter_port" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($portOptions as $port)
                                            <option value="{{ $port }}">{{ $port }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th><input type="text" id="filter_number_po" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th>
                                    <select id="filter_month_order" class="om-inline-filter archived-column-filter">
                                        <option value="">All</option>
                                        @foreach ($filterOptions['month_orders'] as $monthOrder)
                                            <option value="{{ $monthOrder }}">{{ $monthOrder }}</option>
                                        @endforeach
                                    </select>
                                </th>
                                <th><input type="text" id="filter_remarks" class="om-inline-filter archived-column-filter" placeholder="..."></th>
                                <th></th>
                                <th></th>
                                <th></th>
                            </tr>
                            <tr class="om-column-header">
                                <th>NO</th>
                                <th>Supplier</th>
                                <th>TYPE</th>
                                <th>Thickness</th>
                                <th>Width</th>
                                <th>Diameter</th>
                                <th>Length</th>
                                <th>QTY<br>(PCS)</th>
                                <th>Est QTY<br>(KG)</th>
                                <th>Number<br>Invoice</th>
                                <th>Status</th>
                                <th>Estimasi ETA<br>Port</th>
                                <th>Estimasi ETA<br>Warehouse</th>
                                <th>Estimasi Bulan<br>ETA</th>
                                <th>Keterangan</th>
                                <th>Estimasi Delay<br>ETA Port</th>
                                <th>Estimasi Delay<br>ETA Warehouse</th>
                                <th>Port</th>
                                <th>Nomor PO</th>
                                <th>Month Order</th>
                                <th>Remarks</th>
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
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="{{ asset('assets/js/outstanding-materials/sticky-table.js') }}?v={{ filemtime(public_path('assets/js/outstanding-materials/sticky-table.js')) }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.DataTable === 'undefined') {
            console.error('DataTables plugin is not loaded.');
            return;
        }

        const $ = window.jQuery;
        let table = null;

        function formatDisplayDate(value) {
            if (!value) {
                return '';
            }

            const parts = value.split('-');

            if (parts.length !== 3) {
                return value;
            }

            return parts[2] + '/' + parts[1] + '/' + parts[0];
        }

        function dateRangeLabel(prefix) {
            const from = $('#' + prefix + '_from').val();
            const to = $('#' + prefix + '_to').val();

            if (from && to) {
                return formatDisplayDate(from) + ' — ' + formatDisplayDate(to);
            }

            if (from) {
                return 'Mulai ' + formatDisplayDate(from);
            }

            if (to) {
                return 'Sampai ' + formatDisplayDate(to);
            }

            return 'Pilih tanggal';
        }

        function syncDateRangeDisplay(prefix) {
            $('#' + prefix + '_display').text(dateRangeLabel(prefix));
        }

        function reloadWithFilters() {
            if (table) {
                table.ajax.reload();
            }
        }

        function currentFilters() {
            return {
                q: table ? table.search() : '',
                supplier: $('#filter_supplier').val(),
                type: $('#filter_type').val(),
                thickness: $('#filter_thickness').val(),
                width: $('#filter_width').val(),
                diameter: $('#filter_diameter').val(),
                material_length: $('#filter_material_length').val(),
                qty_pcs: $('#filter_qty_pcs').val(),
                est_qty_kg: $('#filter_est_qty_kg').val(),
                number_invoice: $('#filter_number_invoice').val(),
                status: $('#filter_status').val(),
                keterangan: $('#filter_keterangan').val(),
                estimasi_bulan_eta: $('#filter_estimasi_bulan_eta').val(),
                eta_port_from: $('#filter_eta_port_from').val(),
                eta_port_to: $('#filter_eta_port_to').val(),
                eta_warehouse_from: $('#filter_eta_warehouse_from').val(),
                eta_warehouse_to: $('#filter_eta_warehouse_to').val(),
                delay_eta_port_from: $('#filter_delay_eta_port_from').val(),
                delay_eta_port_to: $('#filter_delay_eta_port_to').val(),
                delay_eta_warehouse_from: $('#filter_delay_eta_warehouse_from').val(),
                delay_eta_warehouse_to: $('#filter_delay_eta_warehouse_to').val(),
                port: $('#filter_port').val(),
                number_po: $('#filter_number_po').val(),
                month_order: $('#filter_month_order').val(),
                remarks: $('#filter_remarks').val(),
            };
        }

        table = $('#archivedMaterialTable').DataTable({
            processing: true,
            serverSide: true,
            searching: true,
            searchDelay: 500,
            orderCellsTop: false,
            scrollX: false,
            autoWidth: true,
            ajax: {
                url: '{{ route('outstanding-materials.materials.archived.data') }}',
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
                { data: 'port', name: 'port' },
                { data: 'number_po', name: 'number_po' },
                { data: 'month_order', name: 'month_order' },
                { data: 'remarks', name: 'remarks' },
                { data: 'packing_list', name: 'packing_list_path', orderable: true, searchable: false, className: 'text-center' },
                { data: 'mtc', name: 'mtc_path', orderable: true, searchable: false, className: 'text-center' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
            ],
            order: [],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<div class="d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>',
                lengthMenu: 'Tampilkan _MENU_ data per halaman',
                zeroRecords: '<div class="om-empty-state"><i class="bi bi-inbox"></i>Data tidak ditemukan</div>',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Tidak ada data yang tersedia',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                },
            },
            drawCallback: function () {
                $('[data-bs-toggle="tooltip"]').tooltip();
            },
        });

        if (window.OutstandingMaterialStickyTable) {
            window.OutstandingMaterialStickyTable.install('#archivedMaterialTable');
            window.OutstandingMaterialStickyTable.sync('#archivedMaterialTable');
        }

        // Initialize Select2 on dropdown filters
        $('select.archived-column-filter').select2({
            width: '100%',
            placeholder: 'All',
            allowClear: true,
            minimumResultsForSearch: 0,
        });

        // Prevent sort when clicking on filter controls
        $('#archivedMaterialTable thead').on('click keydown', 'input, select, button, .select2-container, .select2-container *', function (event) {
            event.stopPropagation();
        });

        // Filter change handlers
        $('.archived-column-filter').on('change input', function () {
            reloadWithFilters();
        });

        // Date range toggle
        $('[data-range-filter]').on('click', '.om-inline-date-btn', function (e) {
            e.stopPropagation();
            const wrapper = $(this).closest('[data-range-filter]');
            $('[data-range-filter]').not(wrapper).removeClass('is-open');
            wrapper.toggleClass('is-open');
        });

        $(document).on('click', function () {
            $('[data-range-filter]').removeClass('is-open');
        });

        $('[data-range-filter]').on('click', function (event) {
            event.stopPropagation();
        });

        $('[data-range-apply]').on('click', function () {
            const prefix = $(this).data('prefix');
            let from = $('#' + prefix + '_from_picker').val();
            let to = $('#' + prefix + '_to_picker').val();

            if (from && to && from > to) {
                const originalFrom = from;
                from = to;
                to = originalFrom;
                $('#' + prefix + '_from_picker').val(from);
                $('#' + prefix + '_to_picker').val(to);
            }

            $('#' + prefix + '_from').val(from);
            $('#' + prefix + '_to').val(to);
            syncDateRangeDisplay(prefix);
            $(this).closest('[data-range-filter]').removeClass('is-open');
            reloadWithFilters();
        });

        $('[data-range-clear]').on('click', function () {
            const prefix = $(this).data('prefix');
            $('#' + prefix + '_from').val('');
            $('#' + prefix + '_to').val('');
            $('#' + prefix + '_from_picker').val('');
            $('#' + prefix + '_to_picker').val('');
            syncDateRangeDisplay(prefix);
            $(this).closest('[data-range-filter]').removeClass('is-open');
            reloadWithFilters();
        });

        // Reset button
        $('#btnArchivedReset').on('click', function () {
            $('.archived-column-filter').val('');
            $('select.archived-column-filter').val('').trigger('change.select2');
            $('#filter_thickness, #filter_width, #filter_diameter, #filter_material_length, #filter_qty_pcs, #filter_est_qty_kg, #filter_number_po, #filter_remarks').val('');
            $('#filter_port').val('');
            ['filter_eta_port', 'filter_eta_warehouse', 'filter_delay_eta_port', 'filter_delay_eta_warehouse'].forEach(function (prefix) {
                $('#' + prefix + '_from').val('');
                $('#' + prefix + '_to').val('');
                $('#' + prefix + '_from_picker').val('');
                $('#' + prefix + '_to_picker').val('');
                syncDateRangeDisplay(prefix);
            });
            table.search('');
            reloadWithFilters();
        });
    });
</script>
@endpush
