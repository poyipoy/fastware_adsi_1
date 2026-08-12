<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management — File Storage
    |--------------------------------------------------------------------------
    |
    | Konfigurasi disk dan path untuk dokumen KM privat.
    |
    */
    'disk' => env('KM_DISK', 'km_private'),

    'upload' => [
        'maximum_kilobytes' => (int) env('KM_MAX_UPLOAD_KB', 51_200),
        'office_submission_enabled' => (bool) env('KM_OFFICE_SUBMISSION_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management — PDF Thumbnail Pipeline
    |--------------------------------------------------------------------------
    |
    | Konfigurasi pipeline pembuatan thumbnail otomatis untuk dokumen PDF.
    | Pipeline kanonis dijalankan oleh scheduled Artisan command tanpa queue worker.
    |
    | KM_PDF_THUMBNAIL_ENABLED  : aktifkan/nonaktifkan pipeline thumbnail
    | KM_PDFTOPPM_BINARY        : path ke binary pdftoppm (Poppler)
    | KM_PDF_THUMBNAIL_TIMEOUT  : batas waktu proses pdftoppm (detik)
    | KM_PDF_THUMBNAIL_DISK     : disk storage untuk menyimpan PNG thumbnail
    |
    */
    'thumbnail' => [
        'enabled' => (bool) env('KM_PDF_THUMBNAIL_ENABLED', false),
        'binary' => env('KM_PDFTOPPM_BINARY', 'pdftoppm'),
        'timeout' => (int) env('KM_PDF_THUMBNAIL_TIMEOUT', 30),
        'disk' => env('KM_PDF_THUMBNAIL_DISK', 'km_private'),
        'format' => 'png',
        'scale' => 150, // DPI / scale resolusi untuk pdftoppm -r
        'quality' => 85,
        'max_file_bytes' => 52_428_800, // 50 MB — lewat ini tidak diproses
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management — Scheduled Document Processing
    |--------------------------------------------------------------------------
    */
    'processing' => [
        'enabled' => (bool) env('KM_DOCUMENT_PROCESSING_ENABLED', false),
        'maximum_attempts' => (int) env('KM_PROCESSING_MAX_ATTEMPTS', 3),
        'stale_after_minutes' => (int) env('KM_PROCESSING_STALE_MINUTES', 60),
        'retry_minutes' => [5, 30, 120],
        'temporary_directory' => env(
            'KM_PROCESSING_TEMP_DIR',
            storage_path('app/private/km/temporary'),
        ),
        'cleanup_after_minutes' => (int) env('KM_PROCESSING_CLEANUP_MINUTES', 60),
        'antivirus' => [
            'binary' => env('KM_CLAMSCAN_BINARY', 'clamscan'),
            'database' => env('KM_CLAMSCAN_DATABASE'),
            'timeout' => (int) env('KM_CLAMSCAN_TIMEOUT', 120),
        ],
        'libreoffice' => [
            'binary' => env('KM_LIBREOFFICE_BINARY', 'soffice'),
            'timeout' => (int) env('KM_LIBREOFFICE_TIMEOUT', 120),
        ],
        'poppler' => [
            'pdftotext' => env('KM_PDFTOTEXT_BINARY', 'pdftotext'),
            'pdfinfo' => env('KM_PDFINFO_BINARY', 'pdfinfo'),
            'pdftoppm' => env('KM_PDFTOPPM_BINARY', 'pdftoppm'),
            'timeout' => (int) env('KM_POPPLER_TIMEOUT', 120),
        ],
        'tesseract' => [
            'binary' => env('KM_TESSERACT_BINARY', 'tesseract'),
            'languages' => env('KM_TESSERACT_LANGUAGES', 'ind+eng'),
            'timeout' => (int) env('KM_TESSERACT_TIMEOUT', 300),
        ],
    ],

    'points' => [
        'completion' => 5,
        'published_document' => 25,
        'featured_insight' => 10,
        'department_minimum_cohort' => 5,
    ],

    'tiers' => [
        'bronze' => 50,
        'silver' => 150,
        'gold' => 300,
    ],

    'reading' => [
        'unique_page_ratio' => 0.90,
        'minimum_active_seconds' => 60,
        'seconds_per_page' => 20,
        'maximum_required_seconds' => 900,
        'maximum_active_delta_seconds' => 120,
        'inactive_timeout_seconds' => 60,
        'progress_flush_seconds' => 12,
    ],

    'insights' => [
        'edit_window_minutes' => 30,
        'maximum_mentions' => 10,
        'maximum_featured' => 3,
        'reactions' => ['helpful', 'insightful', 'agree'],
    ],

    'approval_sla' => [
        'due_working_days' => 3,
        'reminder_working_days' => 2,
        'lazy_sweep_cache_minutes' => 15,
    ],

    'abilities' => [
        'km.oversight' => 'Pengawasan materi',
        'km.insight.moderate' => 'Moderasi insight',
        'km.analytics.view' => 'Analytics seluruh KM',
        'km.assignment.manage' => 'Kelola assignment',
        'km.completion.override' => 'Completion manual aksesibilitas',
        'km.processing.recover_original' => 'Recovery file processing gagal',
        'km.export' => 'Export data untuk HR',
        'km.access.manage' => 'Kelola hak akses KM',
    ],

    'hris' => [
        'enabled' => (bool) env('KM_HRIS_ENABLED', false),
        'endpoint' => env('KM_HRIS_ENDPOINT'),
        'secret' => env('KM_HRIS_SECRET'),
        'timeout_seconds' => (int) env('KM_HRIS_TIMEOUT', 15),
        'maximum_attempts' => (int) env('KM_HRIS_MAX_ATTEMPTS', 5),
        'gates' => [
            'completion_two_releases' => (bool) env('KM_HRIS_GATE_TWO_RELEASES', false),
            'no_critical_defect' => (bool) env('KM_HRIS_GATE_NO_CRITICAL_DEFECT', false),
            'idempotency_verified' => (bool) env('KM_HRIS_GATE_IDEMPOTENCY', false),
            'reconciliation_995' => (bool) env('KM_HRIS_GATE_RECONCILIATION_995', false),
            'exceptions_tested' => (bool) env('KM_HRIS_GATE_EXCEPTIONS', false),
            'sandbox_available' => (bool) env('KM_HRIS_GATE_SANDBOX', false),
        ],
    ],

];
