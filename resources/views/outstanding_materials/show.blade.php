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
        <h1>Detail Outstanding Material</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item">Procurement</li>
                <li class="breadcrumb-item"><a href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
                <li class="breadcrumb-item active">Detail</li>
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

        <div class="card om-card">
            <div class="card-body">
                <div class="om-card-header">
                    <h5 class="om-card-title">Detail Outstanding Material</h5>
                    <div class="om-toolbar">
                        @if ($canManageOutstandingMaterials)
                            <a href="{{ route('outstanding-materials.edit', $material) }}" class="btn btn-warning">
                                <i class="bi bi-pencil-square me-1"></i>Edit
                            </a>
                        @endif
                        <a href="{{ route('outstanding-materials.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>

                <div class="om-detail-grid">
                    {{-- Material Info --}}
                    <div class="om-detail-section">
                        <div class="om-section-title">
                            <i class="bi bi-box-seam"></i> Material Info
                        </div>
                        <table class="om-detail-table">
                            <tr>
                                <th>Supplier</th>
                                <td>{{ $material->supplier }}</td>
                            </tr>
                            <tr>
                                <th>TYPE</th>
                                <td>{{ $material->type }}</td>
                            </tr>
                            <tr>
                                <th>Thickness</th>
                                <td>{{ $material->thickness ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Width</th>
                                <td>{{ $material->width ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Diameter</th>
                                <td>{{ $material->diameter ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Length</th>
                                <td>{{ $material->length ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    {{-- Quantity & Invoice --}}
                    <div class="om-detail-section">
                        <div class="om-section-title">
                            <i class="bi bi-clipboard-data"></i> Quantity & Invoice
                        </div>
                        <table class="om-detail-table">
                            <tr>
                                <th>QTY (PCS)</th>
                                <td>{{ $material->qty_pcs ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Est QTY (KG)</th>
                                <td>{{ $material->est_qty_kg ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Number Invoice</th>
                                <td>{{ $material->number_invoice ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    @php
                                        $badgeClass = match($material->status) {
                                            'Received' => 'om-badge--received',
                                            'On Production' => 'om-badge--production',
                                            'On Shipment' => 'om-badge--shipment',
                                            default => 'om-badge--default',
                                        };
                                    @endphp
                                    <span class="om-badge om-badge-lg {{ $badgeClass }}">{{ $material->status }}</span>
                                </td>
                            </tr>
                            <tr>
                                <th>Keterangan</th>
                                <td>
                                    @if ($material->keterangan)
                                        @php
                                            $ketBadge = match($material->keterangan) {
                                                'On Schedule' => 'om-badge--on-schedule',
                                                'Delay' => 'om-badge--delay',
                                                'Closed' => 'om-badge--closed',
                                                default => 'om-badge--default',
                                            };
                                        @endphp
                                        <span class="om-badge {{ $ketBadge }}">{{ $material->keterangan }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Estimasi Bulan ETA</th>
                                <td>{{ $material->estimasi_bulan_eta ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    {{-- ETA Schedule --}}
                    <div class="om-detail-section">
                        <div class="om-section-title">
                            <i class="bi bi-calendar-event"></i> ETA Schedule
                        </div>
                        <table class="om-detail-table">
                            <tr>
                                <th>Estimasi ETA Port</th>
                                <td>{{ $material->estimasi_eta_port ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Estimasi ETA Warehouse</th>
                                <td>{{ $material->estimasi_eta_warehouse ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Estimasi Delay ETA Port</th>
                                <td>{{ $material->estimasi_delay_eta_port ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Estimasi Delay ETA Warehouse</th>
                                <td>{{ $material->estimasi_delay_eta_warehouse ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>

                    {{-- Documents --}}
                    <div class="om-detail-section">
                        <div class="om-section-title">
                            <i class="bi bi-file-earmark-text"></i> Documents
                        </div>
                        <table class="om-detail-table">
                            <tr>
                                <th>Packing List</th>
                                <td>
                                    @if ($material->packing_list_path)
                                        @if (str_starts_with($material->packing_list_path, 'outstanding-materials/'))
                                            <a href="{{ route('outstanding-materials.attachment', ['outstandingMaterial' => $material, 'type' => 'packing-list']) }}" target="_blank" class="om-link">
                                                <i class="bi bi-file-earmark me-1"></i>Lihat File
                                            </a>
                                        @else
                                            {{ $material->packing_list_path }}
                                        @endif
                                    @else
                                        <span class="om-text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>MTC</th>
                                <td>
                                    @if ($material->mtc_path)
                                        @if (str_starts_with($material->mtc_path, 'outstanding-materials/'))
                                            <a href="{{ route('outstanding-materials.attachment', ['outstandingMaterial' => $material, 'type' => 'mtc']) }}" target="_blank" class="om-link">
                                                <i class="bi bi-file-earmark me-1"></i>Lihat File
                                            </a>
                                        @else
                                            {{ $material->mtc_path }}
                                        @endif
                                    @else
                                        <span class="om-text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>Dokumen PL & MTC</th>
                                <td>
                                    @if ($material->attachment_path)
                                        @if (str_starts_with($material->attachment_path, 'outstanding-materials/'))
                                            <a href="{{ route('outstanding-materials.attachment', $material) }}" target="_blank" class="om-link">
                                                <i class="bi bi-file-earmark me-1"></i>Lihat File
                                            </a>
                                        @else
                                            {{ $material->attachment_path }}
                                        @endif
                                    @else
                                        <span class="om-text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    {{-- Audit Trail --}}
                    <div class="om-detail-section om-detail-section--full">
                        <div class="om-section-title">
                            <i class="bi bi-person-badge"></i> Audit Trail
                        </div>
                        <table class="om-detail-table">
                            <tr>
                                <th style="width:20%">Created By</th>
                                <td>{{ optional($material->creator)->name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Updated By</th>
                                <td>{{ optional($material->updater)->name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
