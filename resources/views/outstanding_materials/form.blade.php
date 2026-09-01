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
    overscroll-behavior-x: contain;
    overscroll-behavior-y: auto;
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

.om-table thead tr:first-child th {
    z-index: 4;
    background: var(--om-gray-50);
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
    top: var(--om-table-header-height, 0px);
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
@php
    $title = $isEdit ? 'Edit Outstanding Material' : 'Add Outstanding Material';
    $action = $isEdit ? route('outstanding-materials.update', $material) : route('outstanding-materials.store');
    $backUrl = $detailReturnAnchor
        ? route('outstanding-materials.show', $detailReturnAnchor)
        : route('outstanding-materials.index');
@endphp

<main id="main" class="main">
    <div class="om-page-header pagetitle">
        <h1>{{ $title }}</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item active">{{ $isEdit ? 'Edit' : 'Create' }}</li>
            </ol>
        </nav>
    </div>

    <section class="section">
        @if (!empty($errors) && $errors->any())
            <div class="alert alert-danger om-alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card om-card">
            <div class="card-body">
                <div class="om-card-header">
                    <h5 class="om-card-title">{{ $title }}</h5>
                </div>

                <form method="POST" action="{{ $action }}" class="om-form">
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif
                    @if ($invoiceContextAnchor)
                        <input type="hidden" name="invoice_context_id" value="{{ $invoiceContextAnchor->id }}">
                    @endif

                    {{-- Section: Supplier & Material --}}
                    <div class="om-fieldset">
                        <div class="om-fieldset-title">
                            <i class="bi bi-building"></i> Supplier & Material
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="supplier" class="form-label">Supplier <span class="text-danger">*</span></label>
                                <input type="text" id="supplier" name="supplier" class="form-control" value="{{ old('supplier', $material->supplier) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="type" class="form-label">TYPE <span class="text-danger">*</span></label>
                                <input type="text" id="type" name="type" class="form-control" value="{{ old('type', $material->type) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                <select id="status" name="status" class="form-select" required>
                                    <option value="">Pilih Status</option>
                                    @foreach ($statusOptions as $status)
                                        <option value="{{ $status }}" @selected(old('status', $material->status) === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Section: Dimensi --}}
                    <div class="om-fieldset">
                        <div class="om-fieldset-title">
                            <i class="bi bi-rulers"></i> Dimensi
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="thickness" class="form-label">Thickness</label>
                                <input type="number" step="0.01" id="thickness" name="thickness" class="form-control" value="{{ old('thickness', $material->thickness) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="width" class="form-label">Width</label>
                                <input type="number" step="0.01" id="width" name="width" class="form-control" value="{{ old('width', $material->width) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="diameter" class="form-label">Diameter</label>
                                <input type="number" step="0.01" id="diameter" name="diameter" class="form-control" value="{{ old('diameter', $material->diameter) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="length" class="form-label">Length</label>
                                <input type="text" id="length" name="length" class="form-control" value="{{ old('length', $material->length) }}" placeholder="Contoh: 1000-2000 atau 1000~2000">
                            </div>
                        </div>
                    </div>

                    {{-- Section: Kuantitas & Invoice --}}
                    <div class="om-fieldset">
                        <div class="om-fieldset-title">
                            <i class="bi bi-box-seam"></i> Kuantitas & Invoice
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="qty_pcs" class="form-label">QTY (PCS)</label>
                                <input type="number" step="0.01" id="qty_pcs" name="qty_pcs" class="form-control" value="{{ old('qty_pcs', $material->qty_pcs) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="est_qty_kg" class="form-label">Est QTY (KG)</label>
                                <input type="number" step="0.01" id="est_qty_kg" name="est_qty_kg" class="form-control" value="{{ old('est_qty_kg', $material->est_qty_kg) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="number_invoice" class="form-label">Number Invoice <span class="text-danger">*</span></label>
                                <input type="text" id="number_invoice" name="number_invoice" class="form-control" value="{{ old('number_invoice', $invoiceContext ?? $material->number_invoice) }}" @readonly($invoiceContextAnchor) required>
                                @if ($invoiceContextAnchor)
                                    <div class="form-text text-primary"><i class="bi bi-lock-fill me-1"></i>Invoice context terkunci dari workspace invoice.</div>
                                @endif
                            </div>
                            <div class="col-md-3">
                                <label for="number_po" class="form-label">Nomor PO</label>
                                <input type="text" id="number_po" name="number_po" class="form-control" value="{{ old('number_po', $material->number_po) }}">
                            </div>
                        </div>
                    </div>

                    {{-- Section: Schedule ETA & Port --}}
                    <div class="om-fieldset">
                        <div class="om-fieldset-title">
                            <i class="bi bi-calendar-event"></i> Schedule ETA & Port
                        </div>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="port" class="form-label">Port</label>
                                <select id="port" name="port" class="form-select">
                                    <option value="">Pilih Port</option>
                                    @foreach (\App\Models\OutstandingMaterial::portOptions() as $port)
                                        <option value="{{ $port }}" @selected(old('port', $material->port) === $port)>{{ $port }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="estimasi_eta_port" class="form-label">Estimasi ETA Port</label>
                                <input type="date" id="estimasi_eta_port" name="estimasi_eta_port" class="form-control" value="{{ old('estimasi_eta_port', $material->estimasi_eta_port) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="estimasi_eta_warehouse" class="form-label">Estimasi ETA Warehouse</label>
                                <input type="date" id="estimasi_eta_warehouse" name="estimasi_eta_warehouse" class="form-control" value="{{ old('estimasi_eta_warehouse', $material->estimasi_eta_warehouse) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="estimasi_bulan_eta" class="form-label">Estimasi Bulan ETA</label>
                                <input type="text" id="estimasi_bulan_eta" name="estimasi_bulan_eta" class="form-control" value="{{ old('estimasi_bulan_eta', $material->estimasi_bulan_eta) }}" placeholder="Contoh: May 2026">
                            </div>
                            <div class="col-md-3">
                                <label for="estimasi_delay_eta_port" class="form-label">Estimasi Delay ETA Port</label>
                                <input type="date" id="estimasi_delay_eta_port" name="estimasi_delay_eta_port" class="form-control" value="{{ old('estimasi_delay_eta_port', $material->estimasi_delay_eta_port) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="estimasi_delay_eta_warehouse" class="form-label">Estimasi Delay ETA Warehouse</label>
                                <input type="date" id="estimasi_delay_eta_warehouse" name="estimasi_delay_eta_warehouse" class="form-control" value="{{ old('estimasi_delay_eta_warehouse', $material->estimasi_delay_eta_warehouse) }}">
                            </div>
                        </div>
                    </div>

                    {{-- Section: Keterangan & Remarks --}}
                    <div class="om-fieldset">
                        <div class="om-fieldset-title">
                            <i class="bi bi-chat-left-text"></i> Keterangan & Remarks
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="keterangan" class="form-label">Keterangan</label>
                                <select id="keterangan" name="keterangan" class="form-select">
                                    <option value="">Pilih Keterangan</option>
                                    @foreach ($keteranganOptions as $keterangan)
                                        <option value="{{ $keterangan }}" @selected(old('keterangan', $material->keterangan) === $keterangan)>{{ $keterangan }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Catatan tambahan...">{{ old('remarks', $material->remarks) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Form Footer --}}
                    <div class="om-form-footer">
                        <a href="{{ $backUrl }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</main>
@endsection
