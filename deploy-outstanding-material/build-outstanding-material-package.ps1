[CmdletBinding()]
param(
    [string]$ReleaseName = 'OUTSTANDING-MATERIAL-20261030'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$deployRoot = (Resolve-Path $PSScriptRoot).Path
$artifactRoot = Join-Path $deployRoot 'artifacts'
$stagingDir = Join-Path $artifactRoot $ReleaseName
$archivePath = Join-Path $artifactRoot ($ReleaseName + '.zip')
$topLevelArchivePath = Join-Path $deployRoot ($ReleaseName + '.zip')

function Write-PackageTextFile {
    param(
        [Parameter(Mandatory)][string]$RelativePath,
        [Parameter(Mandatory)][string]$Content
    )

    $path = Join-Path $stagingDir $RelativePath
    $directory = Split-Path -Parent $path
    if (-not (Test-Path -LiteralPath $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }

    $utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($path, $Content.Replace("`r`n", "`n"), $utf8WithoutBom)
}

function Get-RelativePath {
    param(
        [Parameter(Mandatory)][string]$BasePath,
        [Parameter(Mandatory)][string]$FullPath
    )

    $base = $BasePath.TrimEnd('\') + '\'
    if ($FullPath.StartsWith($base, [System.StringComparison]::OrdinalIgnoreCase)) {
        return $FullPath.Substring($base.Length).Replace('\', '/')
    }
    return $FullPath.Replace('\', '/')
}

function Get-StreamSha256 {
    param([Parameter(Mandatory)][System.IO.Stream]$Stream)

    $algorithm = [System.Security.Cryptography.SHA256]::Create()
    try {
        return ([System.BitConverter]::ToString($algorithm.ComputeHash($Stream))).Replace('-', '').ToLowerInvariant()
    } finally {
        $algorithm.Dispose()
        $Stream.Dispose()
    }
}

Write-Output "=== BUILDING $ReleaseName ==="
Write-Output "Source Repository: $repositoryRoot"
Write-Output "Staging Directory: $stagingDir"

# Clean previous staging and archives
if (Test-Path -LiteralPath $stagingDir) {
    Remove-Item -LiteralPath $stagingDir -Recurse -Force
}
New-Item -ItemType Directory -Path $stagingDir -Force | Out-Null

if (Test-Path -LiteralPath $archivePath) {
    Remove-Item -LiteralPath $archivePath -Force
}
if (Test-Path -LiteralPath $topLevelArchivePath) {
    Remove-Item -LiteralPath $topLevelArchivePath -Force
}

# 1. Module Source Files
$moduleFiles = [string[]]@(
    'app/Exports/OutstandingMaterialExport.php',
    'app/Exports/OutstandingMaterialTemplateExport.php',
    'app/Http/Controllers/OutstandingMaterialController.php',
    'app/Imports/OutstandingMaterialImport.php',
    'app/Models/OutstandingMaterial.php',
    'app/Models/OutstandingMaterialInvoice.php',
    'app/Services/OutstandingMaterialAccessService.php',
    'app/Services/OutstandingMaterialBatchService.php',
    'app/Services/OutstandingMaterialDocumentService.php',
    'app/Services/OutstandingMaterialIdentityService.php',
    'app/Services/OutstandingMaterialImportPreviewService.php',
    'app/Services/OutstandingMaterialInvoiceService.php',
    'app/Enums/ProcurementMenuAccessGroup.php',
    'public/assets/js/outstanding-materials/delete-confirmation.js',
    'public/assets/js/outstanding-materials/invoice-update-selection.js',
    'public/assets/js/outstanding-materials/sticky-table.js',
    'resources/views/outstanding_materials/form-batch.blade.php',
    'resources/views/outstanding_materials/form.blade.php',
    'resources/views/outstanding_materials/import-preview.blade.php',
    'resources/views/outstanding_materials/index.blade.php',
    'resources/views/outstanding_materials/invoice.blade.php',
    'resources/views/outstanding_materials/show.blade.php',
    'resources/views/outstanding_materials/archived_invoices.blade.php',
    'resources/views/outstanding_materials/archived_materials.blade.php'
)

$copiedCount = 0
foreach ($relPath in $moduleFiles) {
    $src = Join-Path $repositoryRoot $relPath
    if (-not (Test-Path -LiteralPath $src -PathType Leaf)) {
        throw "Missing required source file: $relPath"
    }
    $dest = Join-Path $stagingDir $relPath
    $destDir = Split-Path -Parent $dest
    if (-not (Test-Path -LiteralPath $destDir)) {
        New-Item -ItemType Directory -Path $destDir -Force | Out-Null
    }
    Copy-Item -LiteralPath $src -Destination $dest -Force
    $copiedCount++
}
Write-Output "Copied $copiedCount module files to staging."

# 2. Shared Files with Sanitization
# routes/web.php
$webPhpContent = [System.IO.File]::ReadAllText((Join-Path $repositoryRoot 'routes/web.php'))
# Remove uncommitted master-department routes if present
$webPhpSanitized = [System.Text.RegularExpressions.Regex]::Replace(
    $webPhpContent,
    '(?ms)^[ ]{4}// ===== HR Base Competency — Master Job Position =====\r?\n[ ]{4}Route::prefix\(''hr/master-department''\).*?^[ ]{4}\}\);\r?\n?',
    "    // ===== HR Base Competency — Master Job Position =====`n"
)
Write-PackageTextFile 'routes/web.php' $webPhpSanitized

# resources/views/layout.blade.php
$layoutContent = [System.IO.File]::ReadAllText((Join-Path $repositoryRoot 'resources/views/layout.blade.php'))
# Remove uncommitted mst-department link if present
$layoutSanitized = [System.Text.RegularExpressions.Regex]::Replace(
    $layoutContent,
    '(?ms)[ ]{44}@if \(app\(\\App\\Services\\HR\\JobPositionAccessService::class\)->canManageMapping\(\$hrUser\)\)\r?\n[ ]{48}<li><a class="dropdown-item" href="{{ route\(''mst-department\.index''\) }}">Master Department</a></li>\r?\n[ ]{44}@endif\r?\n',
    ''
)
Write-PackageTextFile 'resources/views/layout.blade.php' $layoutSanitized
Write-Output 'Shared files routes/web.php and layout.blade.php sanitized and packaged.'

# 3. Snippets
$routesSnippet = @'
// =========================================================================
// Outstanding Material Module Routes
// Add this block inside the authenticated middleware group in routes/web.php
// =========================================================================

    // Outstanding Materials
    Route::prefix('outstanding-materials')->name('outstanding-materials.')->middleware([
        'auth',
        'role:outstanding_material',
    ])->group(function () {
        Route::get('/', [OutstandingMaterialController::class, 'index'])->name('index');
        Route::get('/data', [OutstandingMaterialController::class, 'data'])->name('data');
        Route::get('/create', [OutstandingMaterialController::class, 'create'])->name('create');
        Route::post('/bulk', [OutstandingMaterialController::class, 'storeBatch'])->name('bulk-store');
        Route::post('/', [OutstandingMaterialController::class, 'store'])->name('store');
        Route::get('/export', [OutstandingMaterialController::class, 'export'])->name('export');
        Route::post('/import', [OutstandingMaterialController::class, 'import'])->name('import');
        Route::get('/import/preview/{token}', [OutstandingMaterialController::class, 'importPreview'])->whereUuid('token')->name('import.preview');
        Route::post('/import/preview/{token}/execute', [OutstandingMaterialController::class, 'importExecute'])->whereUuid('token')->name('import.preview.execute');
        Route::get('/template', [OutstandingMaterialController::class, 'template'])->name('template');
        Route::get('/show-based-on-invoice', [OutstandingMaterialController::class, 'invoiceIndex'])->name('invoice.index');
        Route::get('/show-based-on-invoice/data', [OutstandingMaterialController::class, 'invoiceData'])->name('invoice.data');
        Route::get('/show-based-on-invoice/materials', [OutstandingMaterialController::class, 'invoiceMaterials'])->name('invoice.materials');
        Route::post('/show-based-on-invoice/update', [OutstandingMaterialController::class, 'updateInvoiceFields'])->name('invoice.update');
        Route::post('/show-based-on-invoice/documents', [OutstandingMaterialController::class, 'uploadInvoiceDocuments'])->name('invoice.documents.upload');
        Route::delete('/show-based-on-invoice/{outstandingMaterial}', [OutstandingMaterialController::class, 'destroyInvoice'])->name('invoice.destroy');
        Route::get('/show-based-on-invoice/{outstandingMaterial}/materials', [OutstandingMaterialController::class, 'invoiceMaterialsForAnchor'])->name('invoice.materials.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/update', [OutstandingMaterialController::class, 'updateInvoiceFieldsForAnchor'])->name('invoice.update.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/documents', [OutstandingMaterialController::class, 'uploadInvoiceDocumentsForAnchor'])->name('invoice.documents.upload.scoped');
        Route::post('/show-based-on-invoice/{outstandingMaterial}/archive', [OutstandingMaterialController::class, 'archiveInvoice'])->name('invoice.archive');
        Route::post('/materials/archive', [OutstandingMaterialController::class, 'archiveMaterials'])->name('materials.archive');
        Route::post('/materials/restore', [OutstandingMaterialController::class, 'restoreMaterials'])->name('materials.restore');
        Route::get('/archived-invoices', [OutstandingMaterialController::class, 'archivedInvoiceIndex'])->name('invoice.archived.index');
        Route::get('/archived-invoices/data', [OutstandingMaterialController::class, 'archivedInvoiceData'])->name('invoice.archived.data');
        Route::get('/archived-invoices/{outstandingMaterial}/materials', [OutstandingMaterialController::class, 'archivedInvoiceMaterialsForAnchor'])->name('invoice.archived.materials.scoped');
        Route::post('/archived-invoices/{outstandingMaterial}/restore', [OutstandingMaterialController::class, 'restoreInvoice'])->name('invoice.restore');
        Route::get('/archived-materials', [OutstandingMaterialController::class, 'archivedMaterialIndex'])->name('materials.archived.index');
        Route::get('/archived-materials/data', [OutstandingMaterialController::class, 'archivedMaterialData'])->name('materials.archived.data');
        Route::get('/{outstandingMaterial}/invoice-data', [OutstandingMaterialController::class, 'invoiceDetailData'])->name('invoice-detail.data');
        Route::get('/{outstandingMaterial}/export', [OutstandingMaterialController::class, 'invoiceDetailExport'])->name('invoice-detail.export');
        Route::get('/{outstandingMaterial}/attachment/{type?}', [OutstandingMaterialController::class, 'attachment'])
            ->whereIn('type', ['attachment', 'packing-list', 'mtc'])
            ->name('attachment');
        Route::get('/{outstandingMaterial}', [OutstandingMaterialController::class, 'show'])->name('show');
        Route::get('/{outstandingMaterial}/edit', [OutstandingMaterialController::class, 'edit'])->name('edit');
        Route::put('/{outstandingMaterial}', [OutstandingMaterialController::class, 'update'])->name('update');
        Route::delete('/{outstandingMaterial}', [OutstandingMaterialController::class, 'destroy'])->name('destroy');
    });
'@
Write-PackageTextFile 'snippets/routes_web_snippet.php' $routesSnippet

$layoutSnippet = @'
{{-- =========================================================================
     Outstanding Material Navigation Menu Item
     Add inside Procurement dropdown menu in resources/views/layout.blade.php
     ========================================================================= --}}
@if (app(\App\Services\OutstandingMaterialAccessService::class)->canView(Auth::user()))
    <li><a class="dropdown-item" href="{{ route('outstanding-materials.index') }}">Outstanding Material</a></li>
@endif
'@
Write-PackageTextFile 'snippets/layout_menu_snippet.blade.php' $layoutSnippet

# 4. Database SQL Scripts
$sqlPreflight = @'
-- =============================================================================
-- Outstanding Material Preflight Check (READ-ONLY)
-- Run this script first in MySQL GUI / phpMyAdmin.
-- =============================================================================

SELECT
    DATABASE() AS active_database,
    CASE
        WHEN EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'users'
        ) THEN 'PASS: table users exists'
        ELSE 'STOP: table users is required before installing Outstanding Material'
    END AS users_requirement,
    CASE
        WHEN EXISTS (
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'outstanding_materials'
        ) THEN 'TABLES_EXIST: use 02_outstanding_material_patch_archive.sql'
        ELSE 'FRESH_INSTALL: use 01_outstanding_material_fresh_schema.sql'
    END AS installation_mode;

SELECT
    table_name,
    table_rows,
    create_time
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('users', 'outstanding_materials', 'outstanding_material_invoices')
ORDER BY table_name;
'@
Write-PackageTextFile 'database/sql/00_preflight.sql' $sqlPreflight

$sqlFresh = @'
-- =============================================================================
-- Outstanding Material Schema Installation (FRESH TABLES ONLY)
-- Use this script ONLY if 00_preflight.sql shows FRESH_INSTALL.
-- =============================================================================

CREATE TABLE IF NOT EXISTS `outstanding_material_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_invoice` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_identity_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `packing_list_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mtc_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_review_required` tinyint(1) NOT NULL DEFAULT '0',
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outstanding_material_invoices_invoice_identity_key_unique` (`invoice_identity_key`),
  KEY `outstanding_material_invoices_created_by_foreign` (`created_by`),
  KEY `outstanding_material_invoices_updated_by_foreign` (`updated_by`),
  KEY `outstanding_material_invoices_supplier_number_invoice_index` (`supplier`,`number_invoice`),
  KEY `om_invoices_archived_at_idx` (`archived_at`),
  CONSTRAINT `outstanding_material_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outstanding_material_invoices_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `outstanding_materials` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned DEFAULT NULL,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `thickness` decimal(15,2) DEFAULT NULL,
  `width` decimal(15,2) DEFAULT NULL,
  `diameter` decimal(15,2) DEFAULT NULL,
  `length` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `qty_pcs` decimal(15,2) DEFAULT NULL,
  `est_qty_kg` decimal(15,2) DEFAULT NULL,
  `number_invoice` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_identity_key` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estimasi_eta_port` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimasi_eta_warehouse` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimasi_bulan_eta` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `estimasi_delay_eta_port` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimasi_delay_eta_warehouse` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `port` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `number_po` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `month_order` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `packing_list_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mtc_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outstanding_materials_created_by_foreign` (`created_by`),
  KEY `outstanding_materials_updated_by_foreign` (`updated_by`),
  KEY `outstanding_materials_supplier_index` (`supplier`),
  KEY `outstanding_materials_type_index` (`type`),
  KEY `outstanding_materials_status_index` (`status`),
  KEY `outstanding_materials_keterangan_index` (`keterangan`),
  KEY `outstanding_materials_estimasi_bulan_eta_index` (`estimasi_bulan_eta`),
  KEY `outstanding_materials_estimasi_eta_port_index` (`estimasi_eta_port`),
  KEY `outstanding_materials_estimasi_eta_warehouse_index` (`estimasi_eta_warehouse`),
  KEY `outstanding_materials_invoice_identity_idx` (`invoice_identity_key`),
  KEY `outstanding_materials_invoice_id_index` (`invoice_id`),
  KEY `om_materials_archived_at_idx` (`archived_at`),
  KEY `om_delay_eta_wh_idx` (`estimasi_delay_eta_warehouse`),
  CONSTRAINT `outstanding_materials_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outstanding_materials_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `outstanding_material_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outstanding_materials_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
'@
Write-PackageTextFile 'database/sql/01_outstanding_material_fresh_schema.sql' $sqlFresh

$sqlPatch = @'
-- =============================================================================
-- Outstanding Material Idempotent Archive Patch (FOR EXISTING DATABASES)
-- Safe to execute repeatedly. Does not drop or delete existing data.
-- =============================================================================

DELIMITER $$

DROP PROCEDURE IF EXISTS `patch_outstanding_material_archive`$$
CREATE PROCEDURE `patch_outstanding_material_archive`()
BEGIN
    -- 1. Add archived_at to outstanding_material_invoices if missing
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
          AND table_name = 'outstanding_material_invoices' 
          AND column_name = 'archived_at'
    ) THEN
        ALTER TABLE `outstanding_material_invoices` 
        ADD COLUMN `archived_at` timestamp NULL DEFAULT NULL AFTER `document_review_required`;
    END IF;

    -- 2. Add om_invoices_archived_at_idx index if missing
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics 
        WHERE table_schema = DATABASE() 
          AND table_name = 'outstanding_material_invoices' 
          AND index_name = 'om_invoices_archived_at_idx'
    ) THEN
        ALTER TABLE `outstanding_material_invoices` 
        ADD INDEX `om_invoices_archived_at_idx` (`archived_at`);
    END IF;

    -- 3. Add om_delay_eta_wh_idx index to outstanding_materials if missing
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics 
        WHERE table_schema = DATABASE() 
          AND table_name = 'outstanding_materials' 
          AND index_name = 'om_delay_eta_wh_idx'
    ) THEN
        ALTER TABLE `outstanding_materials` 
        ADD INDEX `om_delay_eta_wh_idx` (`estimasi_delay_eta_warehouse`);
    END IF;

    -- 4. Add archived_at to outstanding_materials if missing
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = DATABASE() 
          AND table_name = 'outstanding_materials' 
          AND column_name = 'archived_at'
    ) THEN
        ALTER TABLE `outstanding_materials` 
        ADD COLUMN `archived_at` timestamp NULL DEFAULT NULL AFTER `keterangan`;
    END IF;

    -- 5. Add om_materials_archived_at_idx index to outstanding_materials if missing
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.statistics 
        WHERE table_schema = DATABASE() 
          AND table_name = 'outstanding_materials' 
          AND index_name = 'om_materials_archived_at_idx'
    ) THEN
        ALTER TABLE `outstanding_materials` 
        ADD INDEX `om_materials_archived_at_idx` (`archived_at`);
    END IF;

    -- 6. Synchronize materials whose invoice header is already archived
    UPDATE `outstanding_materials` m
    INNER JOIN `outstanding_material_invoices` i ON i.id = m.invoice_id
    SET m.archived_at = i.archived_at
    WHERE i.archived_at IS NOT NULL AND m.archived_at IS NULL;

END$$

DELIMITER ;

CALL `patch_outstanding_material_archive`();
DROP PROCEDURE IF EXISTS `patch_outstanding_material_archive`;
'@
Write-PackageTextFile 'database/sql/02_outstanding_material_patch_archive.sql' $sqlPatch

# 5. Documentation
$deployMd = @'
# Panduan Deployment Modul Outstanding Material (Production)
**Release:** OUTSTANDING-MATERIAL-20261030  
**Target:** Root Direktori Laravel Server Produksi

---

## Ringkasan Perubahan pada Rilis Ini
1. **Perbaikan Format Nilai 0 pada Export Excel:**
   - Kolom numerik (`thickness`, `width`, `diameter`, `qty_pcs`, `est_qty_kg`) yang bernilai NULL/kosong diekspor sebagai float numerik `0.0` (tipe sel numerik Excel, bukan teks string `"0"`).
   - Nilai eksisting `0` dan nilai non-zero tetap terjaga presisinya.
2. **Pembaruan Bulk Update Material (5 Kolom ETA Tambahan):**
   - Modal Bulk Update Materials kini mendukung pembaruan 5 field: *Estimasi ETA Port, Estimasi ETA Warehouse, Estimasi Bulan ETA, Estimasi Delay ETA Port, dan Estimasi Delay ETA Warehouse*.
   - Aturan *Keep Current*: field yang dikosongkan tidak mengubah nilai material berjalan.
   - Dilengkapi validasi tanggal kalender `YYYY-MM-DD` dan proteksi kepemilikan invoice.
3. **Halaman Khusus Material Diarsipkan (Archived Materials):**
   - Rute baru: `/outstanding-materials/archived-materials`.
   - Menampilkan seluruh material yang invoice induknya telah berstatus diarsipkan secara read-only dengan DataTables, server-side pagination, dan filter Select2.
   - Restoring invoice dari halaman *Archived Invoices* secara otomatis mengembalikan seluruh material terkait ke daftar monitoring aktif.

---

## Langkah 1: Backup Sebelum Deployment
Selalu lakukan backup sebelum melakukan update di server production:
1. Backup database MySQL target (misal: via `mysqldump` atau phpMyAdmin export).
2. Backup berkas shared target:
   - `routes/web.php`
   - `resources/views/layout.blade.php`

---

## Langkah 2: Eksekusi Skrip Database
Buka database target via phpMyAdmin, Adminer, atau MySQL CLI:

- **Kasus A: Tabel Outstanding Material sudah ada dari versi sebelumnya:**
  Jalankan berkas:
  ```text
  database/sql/02_outstanding_material_patch_archive.sql
  ```
  *(Skrip ini aman, idempotent, dan tidak menghapus data berjalan. Skrip akan menambahkan kolom `archived_at` dan indeks pendukung pada tabel `outstanding_material_invoices` dan `outstanding_materials`).*

- **Kasus B: Instalasi baru dari nol (tabel belum pernah ada):**
  1. Jalankan `database/sql/00_preflight.sql` untuk memastikan tabel `users` siap.
  2. Jalankan `database/sql/01_outstanding_material_fresh_schema.sql`.

---

## Langkah 3: Salin Berkas ke Root Laravel Production
Ekstrak isi ZIP langsung ke root direktori aplikasi Laravel Anda di server:

1. **Berkas Modul Otomatis Terganti / Bertambah Sesuai Jalur:**
   - `app/` → `app/`
   - `public/` → `public/`
   - `resources/` → `resources/`
2. **Berkas Bersama (Shared Files):**
   - **Opsi Cepat (Timpa File):** Salin file `routes/web.php` dan `resources/views/layout.blade.php` dari paket.
   - **Opsi Manual (Merge Snippet):** Jika server produksi memiliki penyesuaian route/layout lokal lain, Anda dapat menggunakan potongan kode siap pakai dari:
     - `snippets/routes_web_snippet.php` (paste ke grup middleware auth di `routes/web.php`).
     - `snippets/layout_menu_snippet.blade.php` (paste ke menu navigasi di `resources/views/layout.blade.php`).

---

## Langkah 4: Optimasi & Pembersihan Cache Laravel
Jalankan perintah berikut di terminal server production:
```bash
composer dump-autoload --optimize
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan cache:clear
```

Jika aplikasi target menggunakan route caching di production, jalankan:
```bash
php artisan route:cache
```

---

## Langkah 5: Verifikasi Pasca-Deployment
1. Jalankan perintah pengecekan route:
   ```bash
   php artisan route:list --path=outstanding-materials
   ```
   Pastikan route `archived-materials` dan `archived-invoices` terdaftar.
2. Buka browser dan login sebagai user yang berhak (Procurement/Sales).
3. Buka menu **Outstanding Material**:
   - Uji tombol **Export Excel**: pastikan sel kosong pada tebal/lebar muncul sebagai angka `0`.
   - Buka **Detail Invoice** → **Update Materials**: pastikan 5 field ETA baru muncul di modal dan berfungsi dengan mode *Keep Current*.
   - Buka menu/halaman **Archived Materials** (`/outstanding-materials/archived-materials`): pastikan tabel DataTables memuat daftar material arsip lengkap dengan filter Select2.
'@
Write-PackageTextFile 'DEPLOY.md' $deployMd

$integrationMd = @'
# Panduan Integrasi Shared Files

Paket ini menyediakan berkas shared utuh yang sudah diselaraskan, sekaligus file snippet terpisah:

## 1. File Shared Utuh
- `routes/web.php`
- `resources/views/layout.blade.php`
- `app/Enums/ProcurementMenuAccessGroup.php`

Berkas ini telah disanitasi sehingga hanya memuat rute dan navigasi produksi yang valid tanpa membawa draf pekerjaan modul lain yang belum dirilis.

## 2. File Snippet Terpisah
Bila Anda lebih memilih melakukan merge kode manual pada server target:
- `snippets/routes_web_snippet.php`: Potongan route group `Route::prefix('outstanding-materials')`.
- `snippets/layout_menu_snippet.blade.php`: Potongan tag `<li>` menu navigasi Outstanding Material.
'@
Write-PackageTextFile 'INTEGRATION.md' $integrationMd

# 6. Generate MANIFEST.txt & SHA256SUMS.txt
$payloadFiles = @(
    Get-ChildItem -LiteralPath $stagingDir -File -Recurse |
        Where-Object { $_.Name -notin @('MANIFEST.txt', 'SHA256SUMS.txt') } |
        Sort-Object { Get-RelativePath $stagingDir $_.FullName }
)

$manifestLines = @($payloadFiles | ForEach-Object { Get-RelativePath $stagingDir $_.FullName })
Write-PackageTextFile 'MANIFEST.txt' (($manifestLines -join "`n") + "`n")

$hashLines = @(
    $payloadFiles | ForEach-Object {
        $rel = Get-RelativePath $stagingDir $_.FullName
        $hash = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        "$hash  $rel"
    }
)
Write-PackageTextFile 'SHA256SUMS.txt' (($hashLines -join "`n") + "`n")

# 7. Create ZIP Archive
Write-Output "Compressing package into $archivePath..."
$zipInputs = @(Get-ChildItem -LiteralPath $stagingDir -Force)
Compress-Archive -Path $zipInputs.FullName -DestinationPath $archivePath -Force

# Also copy top-level archive for convenience
Copy-Item -LiteralPath $archivePath -Destination $topLevelArchivePath -Force
Write-Output "Copied top-level archive to: $topLevelArchivePath"

# 8. Archive Integrity Validation
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($archivePath)
try {
    $zipEntryMap = @{}
    $mismatches = @()
    foreach ($entry in $zip.Entries) {
        if ($entry.FullName.EndsWith('/') -or $entry.FullName.EndsWith('\')) {
            continue
        }
        $norm = $entry.FullName -replace '\\', '/'
        $zipEntryMap[$norm] = $entry
    }

    $allExpected = @($manifestLines + @('MANIFEST.txt', 'SHA256SUMS.txt'))
    foreach ($path in $allExpected) {
        if (-not $zipEntryMap.ContainsKey($path)) {
            $mismatches += "Missing ZIP entry: $path"
        }
    }
    foreach ($entryKey in $zipEntryMap.Keys) {
        if ($allExpected -notcontains $entryKey) {
            $mismatches += "Unexpected ZIP entry: $entryKey"
        }
    }

    foreach ($hashLine in $hashLines) {
        if ($hashLine -match '^(?<hash>[0-9a-f]{64})\s+(?<path>.+)$') {
            $p = $Matches.path
            $expectedH = $Matches.hash
            $entry = $zipEntryMap[$p]
            if ($null -ne $entry) {
                $actualH = Get-StreamSha256 -Stream ($entry.Open())
                if ($actualH -ne $expectedH) {
                    $mismatches += "Hash mismatch for: $p"
                }
            }
        }
    }

    # Forbidden files verification
    foreach ($entryKey in $zipEntryMap.Keys) {
        if ($entryKey -match '(^|/)vendor/' -or
            $entryKey -match '(^|/)tests/' -or
            $entryKey -match '(^|/)storage/' -or
            $entryKey -match '(^|/)database/migrations/' -or
            $entryKey -match '(^|/)\.env($|\.)') {
            $mismatches += "Forbidden ZIP entry detected: $entryKey"
        }
    }

    Write-Output "TOTAL_PAYLOAD_FILES=$($manifestLines.Count)"
    Write-Output "ZIP_ENTRIES_COUNT=$($zipEntryMap.Count)"
    Write-Output "MISMATCHES_COUNT=$($mismatches.Count)"

    if ($mismatches.Count -gt 0) {
        throw ($mismatches -join "`n")
    }
} finally {
    $zip.Dispose()
}

Write-Output "=== PACKAGE BUILD SUCCESS ==="
Write-Output "Archive: $archivePath"
Write-Output "Top-level: $topLevelArchivePath"
