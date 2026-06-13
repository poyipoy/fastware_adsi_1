@extends('layout')

@push('styles')
<style>
/* ==========================================================================
   Outstanding Material Module — Minimalist Enterprise Theme
   ========================================================================== */

/* --------------------------------------------------------------------------
   0. CSS Custom Properties (Scoped)
   -------------------------------------------------------------------------- */
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
    --om-shadow: 0 1px 3px rgba(0,0,0,.1), 0 1px 2px rgba(0,0,0,.06);
    --om-shadow-md: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -2px rgba(0,0,0,.1);
    --om-transition: 150ms cubic-bezier(.4,0,.2,1);
}

/* --------------------------------------------------------------------------
   1. Summary Cards
   -------------------------------------------------------------------------- */
.om-summary-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.om-summary-card {
    display: flex;
    align-items: center;
    gap: 16px;
    background: #fff;
    border: 1px solid var(--om-gray-200);
    border-left: 4px solid var(--om-gray-300);
    border-radius: var(--om-radius);
    padding: 18px 20px;
    transition: box-shadow var(--om-transition), transform var(--om-transition);
}

.om-summary-card:hover {
    box-shadow: var(--om-shadow-md);
    transform: translateY(-2px);
}

.om-summary-card--total    { border-left-color: var(--om-primary); }
.om-summary-card--production { border-left-color: var(--om-warning); }
.om-summary-card--shipment { border-left-color: var(--om-info); }
.om-summary-card--received { border-left-color: var(--om-success); }

.om-summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: var(--om-radius);
    font-size: 22px;
    flex-shrink: 0;
}

.om-summary-card--total .om-summary-icon    { background: var(--om-primary-light); color: var(--om-primary); }
.om-summary-card--production .om-summary-icon { background: var(--om-warning-light); color: var(--om-warning); }
.om-summary-card--shipment .om-summary-icon { background: var(--om-info-light); color: var(--om-info); }
.om-summary-card--received .om-summary-icon { background: var(--om-success-light); color: var(--om-success); }

.om-summary-content {
    display: flex;
    flex-direction: column;
    min-width: 0;
}

.om-summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1.2;
    color: var(--om-gray-900);
    font-family: "Nunito", sans-serif;
}

.om-summary-label {
    font-size: .8rem;
    font-weight: 500;
    color: var(--om-gray-500);
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-top: 2px;
}

/* --------------------------------------------------------------------------
   2. Card Override (for Outstanding Material pages)
   -------------------------------------------------------------------------- */
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
    font-weight: 600;
    color: var(--om-gray-800);
    margin: 0;
    font-family: "Nunito", sans-serif;
}

/* Action toolbar */
.om-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.om-toolbar .btn {
    font-size: .82rem;
    padding: 6px 14px;
    border-radius: var(--om-radius-sm);
    font-weight: 500;
    transition: all var(--om-transition);
}

/* --------------------------------------------------------------------------
   3. Collapsible Filter Panel
   -------------------------------------------------------------------------- */
.om-filter-toggle {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: .82rem;
    font-weight: 500;
    color: var(--om-gray-600);
    background: var(--om-gray-50);
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    padding: 6px 14px;
    cursor: pointer;
    transition: all var(--om-transition);
}

.om-filter-toggle:hover {
    background: var(--om-gray-100);
    color: var(--om-gray-800);
}

.om-filter-toggle i {
    transition: transform var(--om-transition);
}

.om-filter-toggle[aria-expanded="true"] i {
    transform: rotate(180deg);
}

.om-filter-panel {
    background: var(--om-gray-50);
    border: 1px solid var(--om-gray-200);
    border-radius: var(--om-radius);
    padding: 16px 20px;
    margin-bottom: 16px;
}

.om-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 12px;
}

.om-filter-group label {
    display: block;
    font-size: .75rem;
    font-weight: 600;
    color: var(--om-gray-500);
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: 4px;
}

.om-filter-group select,
.om-filter-group input,
.om-filter-group button.om-filter-date-btn {
    width: 100%;
    font-size: .82rem;
    padding: 6px 10px;
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    background: #fff;
    color: var(--om-gray-700);
    transition: border-color var(--om-transition), box-shadow var(--om-transition);
}

.om-filter-group select:focus,
.om-filter-group input:focus {
    outline: none;
    border-color: var(--om-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
}

.om-filter-date-btn {
    text-align: left !important;
    cursor: pointer;
}

/* Date range popover */
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
}

.om-date-range.is-open .om-date-range-panel {
    display: block;
}

.om-date-range-panel label {
    font-size: .75rem;
    font-weight: 600;
    color: var(--om-gray-500);
    text-transform: uppercase;
    letter-spacing: .03em;
    margin-bottom: 4px;
}

.om-date-range-panel input[type="date"] {
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

.om-filter-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--om-gray-200);
}

/* --------------------------------------------------------------------------
   4. Table Styles
   -------------------------------------------------------------------------- */
.om-table-wrap {
    max-height: 70vh;
    overflow: auto;
    overscroll-behavior: contain;
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
    position: sticky;
    top: 0;
    z-index: 3;
}

.om-table tbody td {
    vertical-align: middle;
    font-size: .84rem;
    color: var(--om-gray-700);
    padding: 10px 12px;
    border-bottom: 1px solid var(--om-gray-100);
    white-space: nowrap;
}

.om-table tbody tr {
    transition: background-color var(--om-transition);
}

.om-table tbody tr:hover {
    background-color: var(--om-primary-light) !important;
}

#modalMaterialsBody tr {
    cursor: pointer;
}

#modalMaterialsBody tr.is-selected {
    background-color: var(--om-primary-light) !important;
}

/* Zebra striping */
.om-table tbody tr:nth-child(even) {
    background-color: var(--om-gray-50);
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
    top: 40px;
    z-index: 2;
}

.om-inline-filter {
    width: 100%;
    min-width: 0;
    box-sizing: border-box;
    font-size: .78rem;
    padding: 5px 8px;
    border: 1px solid var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    background: var(--om-gray-50);
    color: var(--om-gray-700);
    transition: border-color var(--om-transition), box-shadow var(--om-transition);
    appearance: auto;
}

.om-inline-filter:focus {
    outline: none;
    border-color: var(--om-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
    background: #fff;
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

/* Hide DataTables default search */
.om-datatable-wrapper .dataTables_filter {
    display: none !important;
}

/* DataTables info & pagination refinement */
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

.om-datatable-wrapper .dataTables_length label {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-weight: 500;
    margin: 0;
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
    transition: border-color var(--om-transition), box-shadow var(--om-transition);
}

.om-datatable-wrapper .dataTables_length select:focus {
    border-color: var(--om-primary);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, .12);
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

.om-datatable-wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
    background: var(--om-gray-100) !important;
    color: var(--om-gray-700) !important;
    border-color: var(--om-gray-300) !important;
}

/* --------------------------------------------------------------------------
   5. Status Badges
   -------------------------------------------------------------------------- */
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

.om-badge--production {
    background: var(--om-warning-light);
    color: #92400e;
}

.om-badge--shipment {
    background: var(--om-info-light);
    color: #155e75;
}

.om-badge--received {
    background: var(--om-success-light);
    color: #065f46;
}

.om-badge--default {
    background: var(--om-gray-100);
    color: var(--om-gray-600);
}

/* Large badge for detail page */
.om-badge-lg {
    font-size: .85rem;
    padding: 6px 16px;
}

/* Keterangan badges */
.om-badge--on-schedule {
    background: var(--om-success-light);
    color: #065f46;
}

.om-badge--delay {
    background: var(--om-danger-light);
    color: #991b1b;
}

.om-badge--closed {
    background: var(--om-gray-100);
    color: var(--om-gray-600);
}

/* --------------------------------------------------------------------------
   6. Icon Action Buttons
   -------------------------------------------------------------------------- */
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
    border: 1px solid transparent;
    background: transparent;
    color: var(--om-gray-500);
    font-size: 15px;
    cursor: pointer;
    transition: all var(--om-transition);
    text-decoration: none;
    padding: 0;
}

.om-action-btn:hover {
    color: #fff;
}

.om-action-btn--detail {
    border-color: var(--om-info);
    color: var(--om-info);
}
.om-action-btn--detail:hover {
    background: var(--om-info);
    color: #fff;
}

.om-action-btn--edit {
    border-color: var(--om-warning);
    color: var(--om-warning);
}
.om-action-btn--edit:hover {
    background: var(--om-warning);
    color: #fff;
}

.om-action-btn--delete {
    border-color: var(--om-danger);
    color: var(--om-danger);
}
.om-action-btn--delete:hover {
    background: var(--om-danger);
    color: #fff;
}

/* --------------------------------------------------------------------------
   7. Detail Page — Sections
   -------------------------------------------------------------------------- */
.om-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

@media (max-width: 768px) {
    .om-detail-grid {
        grid-template-columns: 1fr;
    }
}

.om-detail-section {
    background: #fff;
    border: 1px solid var(--om-gray-200);
    border-radius: var(--om-radius);
    padding: 20px;
}

.om-detail-section--full {
    grid-column: 1 / -1;
}

.om-section-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .9rem;
    font-weight: 700;
    color: var(--om-gray-800);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--om-gray-100);
    font-family: "Nunito", sans-serif;
}

.om-section-title i {
    font-size: 18px;
    color: var(--om-primary);
}

.om-detail-table {
    width: 100%;
    border-collapse: collapse;
}

.om-detail-table th,
.om-detail-table td {
    padding: 8px 0;
    font-size: .85rem;
    vertical-align: top;
}

.om-detail-table th {
    color: var(--om-gray-500);
    font-weight: 500;
    width: 40%;
    padding-right: 12px;
}

.om-detail-table td {
    color: var(--om-gray-800);
    font-weight: 500;
}

.om-detail-table tr + tr {
    border-top: 1px solid var(--om-gray-100);
}

/* --------------------------------------------------------------------------
   8. Form Page — Fieldsets
   -------------------------------------------------------------------------- */
.om-fieldset {
    border: 1px solid var(--om-gray-200);
    border-radius: var(--om-radius);
    padding: 20px;
    margin-bottom: 20px;
    background: #fff;
}

.om-fieldset-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .9rem;
    font-weight: 700;
    color: var(--om-gray-800);
    margin-bottom: 16px;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--om-gray-100);
    font-family: "Nunito", sans-serif;
}

.om-fieldset-title i {
    font-size: 18px;
    color: var(--om-primary);
}

/* Form controls refinement */
.om-form .form-label {
    font-size: .82rem;
    font-weight: 600;
    color: var(--om-gray-600);
    margin-bottom: 4px;
}

.om-form .form-control,
.om-form .form-select {
    font-size: .85rem;
    border-color: var(--om-gray-300);
    border-radius: var(--om-radius-sm);
    padding: 8px 12px;
    transition: border-color var(--om-transition), box-shadow var(--om-transition);
}

.om-form .form-control:focus,
.om-form .form-select:focus {
    border-color: var(--om-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
}

.om-form .form-text {
    font-size: .78rem;
    color: var(--om-gray-400);
}

.om-form .text-danger {
    color: var(--om-danger) !important;
}

/* Form sticky footer on mobile */
.om-form-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid var(--om-gray-200);
    margin-top: 4px;
}

@media (max-width: 576px) {
    .om-form-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        padding: 12px 16px;
        margin: 0 -24px -24px;
        border-top: 1px solid var(--om-gray-200);
        box-shadow: 0 -2px 8px rgba(0,0,0,.06);
        z-index: 10;
    }

    .om-form-footer .btn {
        flex: 1;
    }
}

/* --------------------------------------------------------------------------
   9. Import Modal Refinement
   -------------------------------------------------------------------------- */
.om-modal .modal-content {
    border: none;
    border-radius: var(--om-radius);
    box-shadow: var(--om-shadow-md);
}

.om-modal .modal-header {
    border-bottom-color: var(--om-gray-200);
    padding: 16px 20px;
}

.om-modal .modal-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--om-gray-800);
}

.om-modal .modal-body {
    padding: 20px;
}

.om-modal .modal-footer {
    border-top-color: var(--om-gray-200);
    padding: 12px 20px;
}

/* --------------------------------------------------------------------------
   10. Alerts
   -------------------------------------------------------------------------- */
.om-alert {
    border-radius: var(--om-radius);
    font-size: .85rem;
    border-left-width: 4px;
}

/* --------------------------------------------------------------------------
   11. Page Header (Breadcrumb area)
   -------------------------------------------------------------------------- */
.om-page-header {
    margin-bottom: 20px;
}

.om-page-header h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--om-gray-800);
    margin-bottom: 4px;
}

/* --------------------------------------------------------------------------
   12. Responsive
   -------------------------------------------------------------------------- */
@media (max-width: 768px) {
    .om-summary-row {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .om-summary-card {
        padding: 12px 14px;
        gap: 10px;
    }

    .om-summary-icon {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }

    .om-summary-value {
        font-size: 1.2rem;
    }

    .om-filter-grid {
        grid-template-columns: 1fr 1fr;
    }

    .om-card .card-body {
        padding: 14px 16px 16px;
    }
}

@media (max-width: 480px) {
    .om-summary-row {
        grid-template-columns: 1fr;
    }

    .om-filter-grid {
        grid-template-columns: 1fr;
    }
}

/* --------------------------------------------------------------------------
   13. Miscellaneous Utilities
   -------------------------------------------------------------------------- */
.om-text-muted {
    color: var(--om-gray-400) !important;
    font-size: .82rem;
}

.om-text-sub {
    display: block;
    font-size: .75rem;
    color: var(--om-gray-400);
    margin-top: 1px;
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

/* Link style for attachments */
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

</style>

@endpush

@section('content')
<main id="main" class="main">
    <div class="om-page-header pagetitle">
        <h1>Outstanding Material By Invoice</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item active">Show Based on Invoice</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show om-alert" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show om-alert" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card om-card">
            <div class="card-body">
                <div class="om-card-header">
                    <h5 class="om-card-title">
                        <i class="bi bi-receipt me-2"></i>Show Based on Invoice
                    </h5>
                    <div class="om-toolbar">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="invoiceSearch" class="form-control border-start-0 ps-0" placeholder="Search invoice..." style="box-shadow: none;">
                        </div>
                        <a href="{{ route('outstanding-materials.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <div class="om-table-wrap om-datatable-wrapper">
                    <table id="outstandingInvoiceTable" class="om-table" style="width:100%">
                        <thead>
                            <tr>
                                <th>Number Invoice</th>
                                <th>Supplier Sample</th>
                                <th>Total Row</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th>Latest ETA Warehouse</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Invoice Update Modal -->
        <div class="modal fade om-modal" id="invoiceUpdateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <form id="invoiceUpdateForm" class="modal-content">
                    @csrf
                    <input type="hidden" id="modalInvoiceNumber" name="invoice" value="">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Update Materials untuk Invoice: <span id="modalInvoiceTitle" class="text-primary"></span></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <!-- Loading State -->
                        <div id="modalLoading" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="text-muted mt-2 small">Memuat material...</p>
                        </div>

                        <!-- Materials Table -->
                        <div id="modalMaterialsContainer" style="display: none;">
                            <div class="table-responsive mb-3" style="max-height: 400px;">
                                <table class="table table-sm table-bordered om-table">
                                    <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
                                        <tr>
                                            <th style="width: 40px; text-align: center;">
                                                <input class="form-check-input" type="checkbox" id="checkAllMaterials">
                                            </th>
                                            <th>Supplier</th>
                                            <th>Type</th>
                                            <th>Dimensi</th>
                                            <th>QTY</th>
                                            <th>Status Saat Ini</th>
                                        </tr>
                                    </thead>
                                    <tbody id="modalMaterialsBody">
                                        <!-- Rows will be populated via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-3 bg-light p-3 rounded border">
                                <div class="col-md-6">
                                    <label for="modalStatus" class="form-label small fw-bold">Update Status Ke</label>
                                    <select class="form-select form-select-sm" id="modalStatus" name="status">
                                        <option value="">-- Biarkan (Tidak Diubah) --</option>
                                        @foreach ($statusOptions ?? [] as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="modalKeterangan" class="form-label small fw-bold">Update Keterangan Ke</label>
                                    <select class="form-select form-select-sm" id="modalKeterangan" name="keterangan">
                                        <option value="">-- Biarkan (Tidak Diubah) --</option>
                                        @foreach ($keteranganOptions ?? [] as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="btnSaveInvoiceUpdate" disabled>
                            <i class="bi bi-save me-1"></i> Update Selected Materials
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </section>
</main>
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

        const table = $('#outstandingInvoiceTable').DataTable({
            processing: true,
            serverSide: true,
            searchDelay: 500,
            scrollX: true,
            ajax: {
                url: '{{ route('outstanding-materials.invoice.data') }}',
                type: 'GET',
            },
            columns: [
                { data: 'number_invoice', name: 'number_invoice' },
                { data: 'supplier', name: 'supplier_sample' },
                { data: 'material_count', name: 'material_count', className: 'text-end' },
                { data: 'status', name: 'status' },
                { data: 'keterangan', name: 'keterangan' },
                { data: 'latest_eta_warehouse', name: 'latest_eta_warehouse' },
                { data: 'actions', orderable: false, searchable: false, className: 'text-center' },
            ],
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            language: {
                processing: '<div class="d-flex align-items-center gap-2"><span class="spinner-border spinner-border-sm"></span> Memuat data...</div>',
                lengthMenu: 'Tampilkan _MENU_ data per halaman',
                zeroRecords: '<div class="om-empty-state"><i class="bi bi-inbox"></i>Data tidak ditemukan</div>',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ invoice',
                infoEmpty: 'Tidak ada invoice yang tersedia',
                infoFiltered: '(difilter dari _MAX_ total invoice)',
                paginate: {
                    first: '<i class="bi bi-chevron-double-left"></i>',
                    last: '<i class="bi bi-chevron-double-right"></i>',
                    next: '<i class="bi bi-chevron-right"></i>',
                    previous: '<i class="bi bi-chevron-left"></i>',
                },
            },
        });

        $('#invoiceSearch').on('keyup', function () {
            table.search(this.value).draw();
        });

        // Open Modal and Fetch Materials
        const invoiceModal = new bootstrap.Modal(document.getElementById('invoiceUpdateModal'));

        $(document).on('click', '.js-open-invoice-modal', function () {
            const invoice = $(this).data('invoice');
            
            $('#modalInvoiceTitle').text(invoice);
            $('#modalInvoiceNumber').val(invoice);
            $('#modalStatus').val('');
            $('#modalKeterangan').val('');
            $('#checkAllMaterials').prop('checked', false);
            $('#btnSaveInvoiceUpdate').prop('disabled', true);
            
            $('#modalLoading').show();
            $('#modalMaterialsContainer').hide();
            $('#modalMaterialsBody').empty();
            
            invoiceModal.show();

            $.ajax({
                url: '{{ route('outstanding-materials.invoice.materials') }}',
                type: 'GET',
                data: { invoice: invoice },
                success: function(response) {
                    let html = '';
                    if (response.length === 0) {
                        html = '<tr><td colspan="6" class="text-center text-muted">Tidak ada material ditemukan untuk invoice ini.</td></tr>';
                    } else {
                        response.forEach(function(mat) {
                            const dim = [mat.thickness, mat.width, mat.length].filter(Boolean).join(' x ');
                            const qty = mat.qty_pcs ? mat.qty_pcs + ' pcs' : (mat.est_qty_kg ? mat.est_qty_kg + ' kg' : '-');
                            
                            html += `
                                <tr class="js-material-row">
                                    <td class="text-center">
                                        <input class="form-check-input mat-checkbox" type="checkbox" name="material_ids[]" value="${mat.id}">
                                    </td>
                                    <td>${mat.supplier || '-'}</td>
                                    <td>${mat.type || '-'}</td>
                                    <td>${dim || '-'}</td>
                                    <td>${qty}</td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge bg-secondary">${mat.status || '-'}</span>
                                            ${mat.keterangan ? `<span class="badge bg-light text-dark border">${mat.keterangan}</span>` : ''}
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    
                    $('#modalMaterialsBody').html(html);
                    $('#modalLoading').hide();
                    $('#modalMaterialsContainer').show();
                },
                error: function() {
                    $('#modalLoading').html('<div class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat data material.</div>');
                }
            });
        });

        // Handle Check All
        $('#checkAllMaterials').on('change', function() {
            const checked = $(this).prop('checked');
            $('.mat-checkbox')
                .prop('checked', checked)
                .closest('tr')
                .toggleClass('is-selected', checked);
            toggleSaveButton();
        });

        // Toggle checkbox when clicking the material row.
        $(document).on('click', '#modalMaterialsBody tr.js-material-row', function(event) {
            if ($(event.target).closest('input, button, a, label, select, textarea').length) {
                return;
            }

            const checkbox = $(this).find('.mat-checkbox');
            checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
        });

        // Handle Individual Checkbox
        $(document).on('change', '.mat-checkbox', function() {
            $(this).closest('tr').toggleClass('is-selected', $(this).prop('checked'));

            const totalCheckboxes = $('.mat-checkbox').length;
            const allChecked = totalCheckboxes > 0 && totalCheckboxes === $('.mat-checkbox:checked').length;
            $('#checkAllMaterials').prop('checked', allChecked);
            toggleSaveButton();
        });

        function toggleSaveButton() {
            const checkedCount = $('.mat-checkbox:checked').length;
            $('#btnSaveInvoiceUpdate').prop('disabled', checkedCount === 0);
            $('#btnSaveInvoiceUpdate').html(`<i class="bi bi-save me-1"></i> Update Selected (${checkedCount})`);
        }

        // Handle Form Submission
        $('#invoiceUpdateForm').on('submit', function (e) {
            e.preventDefault();
            
            const form = $(this);
            const submitBtn = $('#btnSaveInvoiceUpdate');
            
            if ($('.mat-checkbox:checked').length === 0) {
                Swal.fire('Peringatan', 'Pilih minimal 1 material yang akan diupdate.', 'warning');
                return;
            }

            const status = $('#modalStatus').val();
            const ket = $('#modalKeterangan').val();
            
            if (!status && !ket) {
                Swal.fire('Peringatan', 'Pilih minimal salah satu (Status atau Keterangan) untuk diupdate.', 'warning');
                return;
            }

            submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');

            $.ajax({
                url: '{{ route('outstanding-materials.invoice.update') }}',
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    invoiceModal.hide();
                    table.ajax.reload(null, false); // reload datatable without resetting pagination
                    Swal.fire('Berhasil!', response.message, 'success');
                },
                error: function(xhr) {
                    submitBtn.prop('disabled', false).html('<i class="bi bi-save me-1"></i> Update Selected');
                    let msg = 'Terjadi kesalahan sistem.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    Swal.fire('Gagal!', msg, 'error');
                }
            });
        });
    });
</script>
@endpush
