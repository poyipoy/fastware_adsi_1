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

    /*
    |--------------------------------------------------------------------------
    | Knowledge Management — PDF Thumbnail Pipeline
    |--------------------------------------------------------------------------
    |
    | Konfigurasi pipeline pembuatan thumbnail otomatis untuk dokumen PDF.
    | Thumbnail dibuat secara asynchronous oleh job GenerateKmPdfThumbnail.
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
    | Knowledge Management — Queue
    |--------------------------------------------------------------------------
    */
    'queue' => [
        'thumbnail_job' => env('KM_THUMBNAIL_QUEUE', 'default'),
    ],

];
