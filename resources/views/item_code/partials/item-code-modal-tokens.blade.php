@once
    <style>
        .itemcode-modal {
            --itemcode-border-soft: #c4d2e4;
            --itemcode-border-strong: #b8c6da;
            --itemcode-surface-soft: #fcfdff;
            --itemcode-surface-section: #eef4fb;
            --itemcode-surface-header: #f4f8fd;
            --itemcode-text-strong: #3b5674;
            --itemcode-text-muted: #516273;
            --itemcode-focus: #6f95c6;
        }

        .itemcode-modal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
            padding-right: 0.4rem;
        }

        .itemcode-modal .itemcode-section-title {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            margin-top: 1rem;
            margin-bottom: 0.2rem;
        }

        .itemcode-modal .itemcode-section-title:first-of-type {
            margin-top: 0.25rem;
        }

        .itemcode-modal.itemcode-modal-compact-headings .itemcode-section-title {
            margin-top: 0.6rem;
            margin-bottom: 0.55rem;
        }

        .itemcode-modal.itemcode-modal-compact-headings .itemcode-section-title:first-of-type {
            margin-top: 0.1rem;
        }

        .itemcode-modal .itemcode-section-title::after {
            content: '';
            flex: 1;
            border-top: 1px solid #d6e1ee;
        }

        .itemcode-modal .itemcode-section-heading {
            font-size: 0.84rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            color: var(--itemcode-text-strong);
            background: var(--itemcode-surface-section);
            border: 1px solid #c7d6e8;
            border-radius: 999px;
            padding: 0.24rem 0.7rem;
        }

        .itemcode-modal .itemcode-field-wrap {
            border: 1px solid var(--itemcode-border-soft);
            border-radius: 0.55rem;
            background: var(--itemcode-surface-soft);
            padding: 0.65rem 0.75rem;
        }

        .itemcode-modal .itemcode-field-wrap .form-label {
            font-weight: 600;
            margin-bottom: 0.35rem;
            color: #3f5268;
        }

        .itemcode-modal .itemcode-field-wrap .form-control,
        .itemcode-modal .itemcode-field-wrap .form-select {
            border: 1px solid #aebfd5;
        }

        .itemcode-modal .itemcode-field-wrap .form-control:focus,
        .itemcode-modal .itemcode-field-wrap .form-select:focus {
            border-color: var(--itemcode-focus);
            box-shadow: 0 0 0 0.16rem rgba(31, 111, 209, 0.12);
        }

        .itemcode-modal .itemcode-detail-table {
            border: 1px solid var(--itemcode-border-strong);
            border-collapse: separate;
            border-spacing: 0;
        }

        .itemcode-modal .itemcode-detail-table th,
        .itemcode-modal .itemcode-detail-table td {
            border-color: var(--itemcode-border-strong);
            border-width: 1px;
            vertical-align: middle;
        }

        .itemcode-modal .itemcode-detail-table th {
            background: var(--itemcode-surface-header) !important;
            color: var(--itemcode-text-muted) !important;
            font-weight: 600;
        }

        .itemcode-modal .itemcode-detail-table td {
            background: #ffffff;
        }

        .itemcode-modal .itemcode-history-table {
            table-layout: fixed;
            width: 100%;
            border: 1px solid var(--itemcode-border-strong);
            border-collapse: separate;
            border-spacing: 0;
        }

        .itemcode-modal .itemcode-history-table th,
        .itemcode-modal .itemcode-history-table td {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: top;
            border-color: var(--itemcode-border-strong);
            border-width: 1px;
        }

        .itemcode-modal .itemcode-history-table thead th {
            background: var(--itemcode-surface-header);
            color: var(--itemcode-text-muted);
            font-weight: 600;
        }

        .itemcode-modal .history-col-no {
            width: 56px;
            text-align: center;
        }

        .itemcode-modal .history-col-keterangan {
            width: 55%;
        }

        .itemcode-modal .history-keterangan-cell {
            max-width: 0;
        }
    </style>
@endonce
