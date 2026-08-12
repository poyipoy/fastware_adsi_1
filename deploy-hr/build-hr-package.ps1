# Skrip Build Modul HR untuk deploy ke Production
# Menyiapkan seluruh folder dan file langsung di bawah folder deploy-hr
# Tanpa folder tests, menyertakan seluruh 21 migration HR, TcController, mst_tc views, dan SQL database dengan master data

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$deployRoot = $PSScriptRoot

Write-Output "=== Memulai penyalinan modul HR ke $deployRoot ==="

# 1. Bersihkan subfolder versi lama jika ada
$legacyFolder = Join-Path $deployRoot 'HR-MODULE-COMPLETE-20260731'
if (Test-Path -LiteralPath $legacyFolder) {
    Write-Output "Menghapus folder lama: $legacyFolder"
    Remove-Item -LiteralPath $legacyFolder -Recurse -Force
}

# 2. Daftar file eksplisit modul HR
$explicitFiles = @(
    'app/Enums/HRMenuAccessGroup.php',
    'app/Enums/TcpdDepartment.php',
    'app/Enums/TrainingStatus.php',
    'app/Exports/AbstractTrainingQueryExport.php',
    'app/Exports/Concerns/StylesTrainingWorkbook.php',
    'app/Exports/PeopleDevelopmentHrgaExport.php',
    'app/Exports/TcpdCompanyExport.php',
    'app/Exports/TcpdCompetencyExport.php',
    'app/Exports/TcpdCriticalFocusExport.php',
    'app/Exports/TcpdEmployeesExport.php',
    'app/Exports/TcpdFullWorkbookExport.php',
    'app/Exports/TcpdTopJobsExport.php',
    'app/Exports/TrainingApprovalExport.php',
    'app/Exports/TrainingFollowUpExport.php',
    'app/Exports/TrainingHistoryExport.php',
    'app/Exports/TrainingSubmissionExport.php',
    'app/Exports/WorkingExperienceTemplateExport.php',
    'app/Http/Controllers/Api/DashboardController.php',
    'app/Http/Controllers/Api/ServerDrivenController.php',
    'app/Http/Controllers/DashboardController.php',
    'app/Http/Controllers/MstJobPositionController.php',
    'app/Http/Controllers/PdController.php',
    'app/Http/Controllers/PenilaianTCController.php',
    'app/Http/Controllers/TcController.php',
    'app/Http/Controllers/TrainingExportController.php',
    'app/Http/Controllers/UserJobPositionController.php',
    'app/Http/Requests/ImportWorkingExperienceRequest.php',
    'app/Http/Requests/StoreWorkingExperienceRequest.php',
    'app/Http/Requests/UpdateTrainingEvaluationRequest.php',
    'app/Http/Requests/UpdateTrainingFollowUpRequest.php',
    'app/Http/Requests/UpdateWorkingExperienceRequest.php',
    'app/Imports/WorkingExperienceImport.php',
    'app/Models/BtnStatus.php',
    'app/Models/DetailTcPenilaian.php',
    'app/Models/MstAdditionals.php',
    'app/Models/MstDepartment.php',
    'app/Models/MstJobPosition.php',
    'app/Models/MstPdActiveYear.php',
    'app/Models/MstPositionApproval.php',
    'app/Models/MstSection.php',
    'app/Models/MstSoftSkill.php',
    'app/Models/MstTc.php',
    'app/Models/PoinKategori.php',
    'app/Models/Role.php',
    'app/Models/TcPeopleDevelopment.php',
    'app/Models/TrsPenilaianTc.php',
    'app/Models/User.php',
    'app/Models/UserJobPosition.php',
    'app/Models/WorkingExperience.php',
    'app/Providers/AuthServiceProvider.php',
    'app/Providers/ViewServiceProvider.php',
    'app/Services/Competency/CompetencyAssessmentService.php',
    'app/Services/Dashboard/TcpdAccessResolver.php',
    'app/Services/Dashboard/TcpdDashboardService.php',
    'app/Services/DashboardMenuService.php',
    'app/Services/HRMenuService.php',
    'app/Services/KnowledgeManagement/KmOrganizationAssignmentService.php',
    'app/View/Composers/DashboardMenuComposer.php',
    'app/View/Composers/HRMenuComposer.php',
    'config/server_driven_navigation.php',
    'database/migrations/2026_06_29_000001_add_approval_fields_to_tc_job_positions.php',
    'database/migrations/2026_06_29_000002_add_penilaian_yearly_and_history_fields.php',
    'database/migrations/2026_06_29_000003_add_deskripsi_bertingkat_fields.php',
    'database/migrations/2026_06_29_000004_rename_tc_poin_kategoris.php',
    'database/migrations/2026_06_30_000001_create_mst_job_positions_table.php',
    'database/migrations/2026_06_30_000002_create_mst_position_approvals_table.php',
    'database/migrations/2026_06_30_000003_create_user_job_positions_table.php',
    'database/migrations/2026_06_30_000004_create_mst_departments_table.php',
    'database/migrations/2026_06_30_000005_create_mst_sections_table.php',
    'database/migrations/2026_06_30_000006_alter_mst_job_positions_dept_section_fk.php',
    'database/migrations/2026_06_30_000007_drop_deskripsi_bertingkat_fields.php',
    'database/migrations/2026_07_01_000001_migrate_pd_pengajuan_to_ids.php',
    'database/migrations/2026_07_01_065000_disable_legacy_job_position_tables.php',
    'database/migrations/2026_07_01_103000_drop_legacy_tc_job_tables.php',
    'database/migrations/2026_07_08_100001_create_working_experiences_table.php',
    'database/migrations/2026_07_08_100002_add_is_key_position_to_mst_job_positions.php',
    'database/migrations/2026_07_08_100003_create_mst_pd_active_years_table.php',
    'database/migrations/2026_07_08_100004_add_sharing_knowledge_and_objective_to_mst_pd_pengajuans.php',
    'database/migrations/2026_07_08_100005_add_sharing_knowledge_text_to_mst_pd_pengajuans.php',
    'database/migrations/2026_07_08_100006_update_key_positions_exact.php',
    'database/migrations/2026_07_29_150001_create_mst_pd_pengajuan_participants_table.php',
    'resources/views/4layout.blade.php',
    'resources/views/auth/dataDiri.blade.php',
    'resources/views/dashboard/dashboardTCPD.blade.php',
    'resources/views/dashboard/dsCompetency.blade.php',
    'resources/views/dashboard/dsDetailCompetency.blade.php',
    'resources/views/layout.blade.php',
    'routes/api.php',
    'routes/web.php',
    'vite.config.js'
)

# 3. File dari direktori yang dipindai otomatis
$directoryFiles = @(
    'app/Services/HR',
    'public/css/hr',
    'public/js/hr',
    'resources/views/mst_job_position',
    'resources/views/mst_tc',
    'resources/views/tc_penilaian',
    'resources/views/user_job_position'
) | ForEach-Object {
    $directory = Join-Path $repositoryRoot $_
    if (Test-Path -LiteralPath $directory) {
        Get-ChildItem -LiteralPath $directory -File -Recurse |
            ForEach-Object { $_.FullName.Substring($repositoryRoot.Length + 1).Replace('\', '/') }
    }
}

# 4. View People Development (tanpa file cadangan 1225_*)
$peopleDevelopmentFiles = Get-ChildItem -LiteralPath (Join-Path $repositoryRoot 'resources/views/people_development') -File |
    Where-Object { $_.Name -notlike '1225_*' } |
    ForEach-Object { $_.FullName.Substring($repositoryRoot.Length + 1).Replace('\', '/') }

# Gabungkan seluruh file (TANPA folder tests)
$files = @($explicitFiles + $directoryFiles + $peopleDevelopmentFiles) |
    Sort-Object -Unique

$missing = @()
$copiedCount = 0

foreach ($relativePath in $files) {
    $source = Join-Path $repositoryRoot $relativePath
    if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
        $missing += $relativePath
        continue
    }

    $destination = Join-Path $deployRoot $relativePath
    $destinationDirectory = Split-Path -Parent $destination
    if (-not (Test-Path -LiteralPath $destinationDirectory)) {
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
    }
    Copy-Item -LiteralPath $source -Destination $destination -Force
    $copiedCount++
}

if ($missing.Count -gt 0) {
    throw "Missing files:`n$($missing -join "`n")"
}

# 5. Generate SQL dumps
Write-Output "Menghasilkan database SQL (versi schema dan master data beserta isinya)..."
$phpScript = Join-Path $deployRoot 'generate_sql_dumps.php'
if (Test-Path -LiteralPath $phpScript) {
    php $phpScript
} else {
    Write-Warning "File generate_sql_dumps.php tidak ditemukan!"
}

# 6. Buat manifest-files.txt dan SHA256SUMS.txt di root deploy-hr
$files | Set-Content -LiteralPath (Join-Path $deployRoot 'manifest-files.txt') -Encoding UTF8

$hashes = Get-ChildItem -LiteralPath $deployRoot -File -Recurse |
    Where-Object { 
        $_.FullName -notlike "*\artifacts\*" -and 
        $_.Name -ne "SHA256SUMS.txt" -and 
        $_.Name -ne "manifest-files.txt" 
    } |
    Sort-Object FullName |
    ForEach-Object {
        $relative = $_.FullName.Substring($deployRoot.Length + 1).Replace('\', '/')
        $hash = (Get-FileHash -LiteralPath $_.FullName -Algorithm SHA256).Hash.ToLowerInvariant()
        "$hash  $relative"
    }
$hashes | Set-Content -LiteralPath (Join-Path $deployRoot 'SHA256SUMS.txt') -Encoding UTF8

Write-Output "=== BUILD SELESAI ==="
Write-Output "TOTAL FILE DISALIN: $copiedCount"
Write-Output "LOKASI DEPLOY HR: $deployRoot"
