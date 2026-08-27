# Task Tracker — Implementasi Revision TCPD

## FASE 1 — Database & Models
- `[x]` 1A. Migration: create_working_experiences_table ✅
- `[x]` 1B. Migration: add_is_key_position_to_mst_job_positions_table ✅
- `[x]` 1C. Migration: create_mst_pd_active_years_table ✅
- `[x]` 1D. Migration: add_is_sharing_knowledge_and_objective_learning_to_pd_tables ✅
- `[x]` 1D2. Migration: add_sharing_knowledge_text_to_mst_pd_pengajuans ✅ (Modul 4.1)
- `[x]` 1E. Model: WorkingExperience.php ✅
- `[x]` 1F. Model: MstPdActiveYear.php ✅
- `[x]` 1G. Model: MstJobPosition.php (add is_key_position) ✅
- `[x]` 1H. Model: TcPeopleDevelopment.php (add sharing_knowledge, is_sharing_knowledge, objective_learning) ✅

## FASE 2 — Services & Logic
- `[x]` 2A. Service: CompetencyAssessmentService.php (reusable Strength / Badge logic) ✅
- `[x]` 2B. TcpdDashboardService.php — getKeyPositionStats() method (Modul 2.1) ✅
- `[x]` 2C. TcpdDashboardService.php — Critical Focus Area threshold >= 5 (Modul 2.2) ✅

## FASE 3 — Controllers & Routes
- `[x]` 3A. PenilaianTCController.php — getDetailCompetency returns strength_data, working_experience_data, mentor_badges ✅
- `[x]` 3B. UserJobPositionController.php — CRUD Working Experience AJAX (Modul 3.1) ✅
- `[x]` 3C. DashboardController.php — teruskan key_position_stats ke view (Modul 2.1) ✅
- `[x]` 3D. PdController.php — Year Management setActiveYear/getActiveYear + sharing_knowledge/objective_learning di updateData + savePdPengajuan ✅
- `[x]` 3E. Routes: route Working Experience CRUD, pd.active-year.set/.get ✅

## FASE 4 — Views & UI
- `[x]` 4A. dsCompetency.blade.php — label button "Individual Profile" sudah benar ✅
- `[x]` 4B. dsDetailCompetency.blade.php — Strength table + Working Experience table + layout order ✅
- `[x]` 4C. dsDetailCompetency.blade.php — Mentor badges di Area Development ✅
- `[x]` 4D. dashboardTCPD.blade.php — Key Position Stats card (Modul 2.1) ✅
- `[x]` 4E. dashboardTCPD.blade.php — threshold >= 5 + empty state Critical Focus (Modul 2.2) ✅
- `[x]` 4F. user_job_position/index.blade.php — modal CRUD Working Experience (Modul 3.1) ✅
- `[x]` 4G. dept_develop_index.blade.php + hrga_develop_index.blade.php — tampilkan status_2 deskriptif (Modul 4.1) ✅
- `[x]` 4H. edit_develop_hrga.blade.php — Year Management bar + Sharing Knowledge + Objective Learning fields ✅
- `[x]` 4I. create_develop.blade.php (buat-training) — field Objective Learning (Modul 4.4) ✅

## FASE 5 — Run Migrations
- `[x]` 5A. Jalankan perintah migrasi berikut satu per satu sesuai path file migrasinya: ✅
  ```bash
  php artisan migrate --path=database/migrations/2026_07_08_100001_create_working_experiences_table.php
  php artisan migrate --path=database/migrations/2026_07_08_100002_add_is_key_position_to_mst_job_positions.php
  php artisan migrate --path=database/migrations/2026_07_08_100003_create_mst_pd_active_years_table.php
  php artisan migrate --path=database/migrations/2026_07_08_100004_add_sharing_knowledge_and_objective_to_mst_pd_pengajuans.php
  php artisan migrate --path=database/migrations/2026_07_08_100005_add_sharing_knowledge_text_to_mst_pd_pengajuans.php
  ```

## FASE 6 — Audit & Verifikasi
- `[x]` 6A. Audit seluruh perubahan konsistensi coding standard, relasi, validasi ✅
- `[x]` 6B. Pastikan tidak ada regression fitur existing ✅
