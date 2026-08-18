[CmdletBinding()]
param(
    [string]$ReleaseName = 'OUTSTANDING-MATERIAL-20260818'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$deployRoot = (Resolve-Path $PSScriptRoot).Path
$artifactRoot = Join-Path $deployRoot 'artifacts'
$archivePath = Join-Path $artifactRoot ($ReleaseName + '.zip')
$deployPrefix = $deployRoot.TrimEnd('\') + '\'

function Get-PackagePath {
    param([Parameter(Mandatory)][string]$RelativePath)

    if ([System.IO.Path]::IsPathRooted($RelativePath)) {
        throw "Package path must be relative: $RelativePath"
    }

    $path = [System.IO.Path]::GetFullPath((Join-Path $deployRoot $RelativePath))
    if (-not $path.StartsWith($deployPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
        throw "Refusing to use a path outside the package directory: $path"
    }

    return $path
}

function Remove-PackagePath {
    param([Parameter(Mandatory)][string]$RelativePath)

    $path = Get-PackagePath $RelativePath
    if (Test-Path -LiteralPath $path) {
        Remove-Item -LiteralPath $path -Recurse -Force
    }
}

function Write-PackageTextFile {
    param(
        [Parameter(Mandatory)][string]$RelativePath,
        [Parameter(Mandatory)][string]$Content
    )

    $path = Get-PackagePath $RelativePath
    $directory = Split-Path -Parent $path
    if (-not (Test-Path -LiteralPath $directory)) {
        New-Item -ItemType Directory -Path $directory -Force | Out-Null
    }

    $utf8WithoutBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllText($path, $Content, $utf8WithoutBom)
}

function Remove-RequiredContentBlock {
    param(
        [Parameter(Mandatory)][string]$RelativePath,
        [Parameter(Mandatory)][string]$Pattern,
        [Parameter(Mandatory)][string]$Description
    )

    $path = Get-PackagePath $RelativePath
    $content = [System.IO.File]::ReadAllText($path)
    $expression = New-Object System.Text.RegularExpressions.Regex($Pattern)
    $matches = $expression.Matches($content)

    if ($matches.Count -ne 1) {
        throw "Expected exactly one $Description in $RelativePath, found $($matches.Count)."
    }

    Write-PackageTextFile $RelativePath ($expression.Replace($content, '', 1))
}

function Get-RelativePackagePath {
    param([Parameter(Mandatory)][string]$FullName)

    return $FullName.Substring($deployRoot.Length + 1).Replace('\', '/')
}

function Get-PackagePayloadFiles {
    $artifactPrefix = [System.IO.Path]::GetFullPath($artifactRoot).TrimEnd('\') + '\'

    return @(
        Get-ChildItem -LiteralPath $deployRoot -File -Recurse |
            Where-Object {
                -not $_.FullName.StartsWith($artifactPrefix, [System.StringComparison]::OrdinalIgnoreCase) -and
                $_.Name -notin @('MANIFEST.txt', 'SHA256SUMS.txt')
            } |
            Sort-Object { Get-RelativePackagePath $_.FullName }
    )
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

Write-Output "Building $ReleaseName from $repositoryRoot"

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
    'public/assets/js/outstanding-materials/delete-confirmation.js',
    'public/assets/js/outstanding-materials/invoice-update-selection.js',
    'public/assets/js/outstanding-materials/sticky-table.js',
    'resources/views/outstanding_materials/form-batch.blade.php',
    'resources/views/outstanding_materials/form.blade.php',
    'resources/views/outstanding_materials/import-preview.blade.php',
    'resources/views/outstanding_materials/index.blade.php',
    'resources/views/outstanding_materials/invoice.blade.php',
    'resources/views/outstanding_materials/show.blade.php'
)

$fullSharedFiles = [string[]]@(
    'routes/web.php',
    'app/Enums/ProcurementMenuAccessGroup.php',
    'resources/views/layout.blade.php'
)

$packageSourceFiles = [string[]]@($moduleFiles + $fullSharedFiles)

foreach ($relativePath in @('app', 'public', 'resources', 'routes', 'database', 'shared-integrations')) {
    Remove-PackagePath $relativePath
}
foreach ($relativePath in @('MANIFEST.txt', 'SHA256SUMS.txt')) {
    Remove-PackagePath $relativePath
}

if (-not (Test-Path -LiteralPath $artifactRoot)) {
    New-Item -ItemType Directory -Path $artifactRoot -Force | Out-Null
}

$normalizedArchivePath = [System.IO.Path]::GetFullPath($archivePath)
$artifactPrefix = [System.IO.Path]::GetFullPath($artifactRoot).TrimEnd('\') + '\'
if (-not $normalizedArchivePath.StartsWith($artifactPrefix, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw "Refusing to replace an archive outside artifacts: $normalizedArchivePath"
}
if (Test-Path -LiteralPath $normalizedArchivePath) {
    Remove-Item -LiteralPath $normalizedArchivePath -Force
}

$copiedCount = 0
foreach ($relativePath in $packageSourceFiles) {
    $sourcePath = Join-Path $repositoryRoot $relativePath
    if (-not (Test-Path -LiteralPath $sourcePath -PathType Leaf)) {
        throw "Required Outstanding Material package source file is missing: $relativePath"
    }

    $destinationPath = Get-PackagePath $relativePath
    $destinationDirectory = Split-Path -Parent $destinationPath
    if (-not (Test-Path -LiteralPath $destinationDirectory)) {
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
    }

    Copy-Item -LiteralPath $sourcePath -Destination $destinationPath -Force
    $copiedCount++
}

Remove-RequiredContentBlock `
    'routes/web.php' `
    '(?ms)^[ ]{4}// Warehouse Consumable routes\..*?^[ ]{4}\}\);\r?\n?' `
    'Warehouse route group'
Remove-RequiredContentBlock `
    'resources/views/layout.blade.php' `
    '(?ms)^[ ]{24}@php\r?\n[ ]{28}\$warehouseMenuVisible\s*=.*?^[ ]{24}@endif\r?\n?' `
    'Warehouse navigation block'

foreach ($relativePath in $fullSharedFiles) {
    $sharedContent = [System.IO.File]::ReadAllText((Get-PackagePath $relativePath))
    if ($sharedContent -match '(?i)warehouse') {
        throw "Warehouse content remains in packaged shared file: $relativePath"
    }
}

Write-PackageTextFile 'database/sql/00_preflight.sql' @'
-- Outstanding Material preflight (READ ONLY)
-- Run this file first. Continue only when both requirements show PASS.
-- This script does not create, alter, delete, or insert any database object/data.

SELECT
    DATABASE() AS active_database,
    CASE
        WHEN EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'users'
        ) THEN 'PASS'
        ELSE 'STOP: table users is required before installing Outstanding Material'
    END AS users_requirement,
    CASE
        WHEN NOT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'outstanding_materials'
        )
        AND NOT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = 'outstanding_material_invoices'
        ) THEN 'PASS'
        ELSE 'STOP: Outstanding Material tables already exist; do not run the fresh schema script'
    END AS outstanding_material_requirement;

SELECT
    table_name,
    table_type
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name IN ('users', 'outstanding_materials', 'outstanding_material_invoices')
ORDER BY table_name;
'@

Write-PackageTextFile 'database/sql/01_outstanding_material_schema.sql' @'
-- Outstanding Material schema-only installation (fresh tables only)
-- Prerequisite: database/sql/00_preflight.sql returned PASS for both requirements.
-- This file deliberately contains no business data, no migration ledger entries,
-- no INSERT, UPDATE, DELETE, or DROP statements.
-- Do not run this script when either Outstanding Material table already exists.

CREATE TABLE `outstanding_material_invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_invoice` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_identity_key` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `packing_list_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mtc_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_review_required` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `outstanding_material_invoices_invoice_identity_key_unique` (`invoice_identity_key`),
  KEY `outstanding_material_invoices_created_by_foreign` (`created_by`),
  KEY `outstanding_material_invoices_updated_by_foreign` (`updated_by`),
  KEY `outstanding_material_invoices_supplier_number_invoice_index` (`supplier`,`number_invoice`),
  CONSTRAINT `outstanding_material_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outstanding_material_invoices_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `outstanding_materials` (
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
  `estimasi_delay_eta_port` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estimasi_delay_eta_warehouse` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  CONSTRAINT `outstanding_materials_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outstanding_materials_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `outstanding_material_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `outstanding_materials_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
'@

$payloadFiles = Get-PackagePayloadFiles
$manifestLines = @($payloadFiles | ForEach-Object { Get-RelativePackagePath $_.FullName })
Write-PackageTextFile 'MANIFEST.txt' (($manifestLines -join [Environment]::NewLine) + [Environment]::NewLine)

$hashLines = @(
    $payloadFiles | ForEach-Object {
        $relativePath = Get-RelativePackagePath $_.FullName
        $hash = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        "$hash  $relativePath"
    }
)
Write-PackageTextFile 'SHA256SUMS.txt' (($hashLines -join [Environment]::NewLine) + [Environment]::NewLine)

$zipInputs = @(Get-ChildItem -LiteralPath $deployRoot -Force | Where-Object { $_.Name -ne 'artifacts' })
if ($zipInputs.Count -eq 0) {
    throw 'No package files are available to archive.'
}
Compress-Archive -Path $zipInputs.FullName -DestinationPath $normalizedArchivePath -Force

Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::OpenRead($normalizedArchivePath)
try {
    $zipEntryMap = @{}
    $mismatches = @()
    foreach ($zipEntry in $zip.Entries) {
        if ($zipEntry.FullName.EndsWith('/') -or $zipEntry.FullName.EndsWith('\')) {
            continue
        }

        $normalizedEntryPath = $zipEntry.FullName -replace '\\', '/'
        if ($zipEntryMap.ContainsKey($normalizedEntryPath)) {
            $mismatches += "Duplicate ZIP entry: $normalizedEntryPath"
            continue
        }

        $zipEntryMap[$normalizedEntryPath] = $zipEntry
    }

    $zipEntries = @($zipEntryMap.Keys)
    $expectedArchivePaths = @($manifestLines + @('MANIFEST.txt', 'SHA256SUMS.txt'))

    foreach ($relativePath in $expectedArchivePaths) {
        if ($zipEntries -notcontains $relativePath) {
            $mismatches += "Missing ZIP entry: $relativePath"
        }
    }
    foreach ($entryName in $zipEntries) {
        if ($expectedArchivePaths -notcontains $entryName) {
            $mismatches += "Unexpected ZIP entry: $entryName"
        }
    }

    foreach ($hashLine in $hashLines) {
        if ($hashLine -notmatch '^(?<hash>[0-9a-f]{64})  (?<path>.+)$') {
            $mismatches += "Invalid SHA256SUMS entry: $hashLine"
            continue
        }

        $entry = $zipEntryMap[$Matches.path]
        if ($null -eq $entry) {
            $mismatches += "Missing ZIP entry for checksum: $($Matches.path)"
            continue
        }

        $actualHash = Get-StreamSha256 -Stream ($entry.Open())
        if ($actualHash -ne $Matches.hash) {
            $mismatches += "Checksum mismatch: $($Matches.path)"
        }
    }

    foreach ($entryName in $zipEntries) {
        if ($entryName -match '(^|/)vendor/' -or
            $entryName -match '(^|/)tests/' -or
            $entryName -match '(^|/)storage/' -or
            $entryName -match '(^|/)database/migrations/' -or
            $entryName -match '(^|/)\.env($|\.)' -or
            $entryName -match 'warehouse') {
            $mismatches += "Forbidden ZIP entry: $entryName"
        }
    }

    foreach ($relativeSqlPath in @(
        'database/sql/00_preflight.sql',
        'database/sql/01_outstanding_material_schema.sql'
    )) {
        $sqlPath = Get-PackagePath $relativeSqlPath
        $dml = Select-String -LiteralPath $sqlPath -Pattern '(?im)^\s*(INSERT|UPDATE|DELETE|DROP)\b'
        if ($dml) {
            $mismatches += "Forbidden DML statement in $relativeSqlPath"
        }
    }

    Write-Output "MODULE_SOURCE_FILE_COUNT=$($moduleFiles.Count)"
    Write-Output "FULL_SHARED_FILE_COUNT=$($fullSharedFiles.Count)"
    Write-Output 'WAREHOUSE_SHARED_CONTENT=EXCLUDED'
    Write-Output "SOURCE_FILE_COUNT=$copiedCount"
    Write-Output "MANIFEST_ENTRY_COUNT=$($manifestLines.Count)"
    Write-Output "ZIP_ENTRY_COUNT=$($zipEntries.Count)"
    Write-Output "MANIFEST_MISMATCH_COUNT=$($mismatches.Count)"

    if ($mismatches.Count -gt 0) {
        throw ($mismatches -join [Environment]::NewLine)
    }
} finally {
    $zip.Dispose()
}

Write-Output "ARCHIVE=$normalizedArchivePath"
Write-Output 'PACKAGE_BUILD=SUCCESS'
