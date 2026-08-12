# Daftar Migration KM Roadmap Prioritas 1, 3, dan 4

Status dokumen: implementasi code selesai disiapkan. Empat migration schema telah dijalankan secara serial pada database lokal `dms_adasi_rev1` tanggal 29 Juli 2026. Satu migration data-repair tambahan memperbaiki assignment posisi lama tanpa mengubah schema; instruksi ini tetap menjadi runbook untuk environment lain.

## Catatan Eksekusi Lokal

- Environment: `APP_ENV=local`.
- Database: `dms_adasi_rev1`.
- Backup sebelum migration: `storage/app/backups/km-roadmap/dms_adasi_rev1-before-km-140-20260729-163946.sql`.
- SHA-256 backup: `2E8833B43F323171128E8DA15A2C278861224A8323E7489450B4A5B69C888218`.
- Hasil migration: `140001` batch 51, `140002` batch 52, `140003` batch 53, dan `140004` batch 54.
- Data-repair `160001` dijalankan 2 Agustus 2026 pada batch 57; setelah eksekusi tidak ada assignment aktif dengan `effective_from` kosong.
- Hasil backfill: 23 dokumen memiliki 23 versi; tidak ada dokumen tanpa current version, published version, atau progress tanpa version.
- Seed recognition: lima badge tersedia.

## Prasyarat

- Target database harus dikonfirmasi bukan production yang sedang aktif tanpa maintenance window.
- Catat nama database, `APP_ENV`, commit/release, dan hasil `php artisan migrate:status`.
- Buat backup/snapshot yang telah diverifikasi dapat dibaca.
- Migration KM sampai `2026_07_27_130004_create_km_point_ledger_table` harus sudah berstatus `Ran`.
- Jangan memakai `php artisan migrate` tanpa `--path`, karena repository masih mempunyai migration legacy lain yang tidak termasuk release KM ini.
- Jalankan serial. Jangan menjalankan test/migration KM paralel pada schema yang sama.

## Urutan Wajib

1. `database/migrations/2026_07_29_140001_create_km_document_versions.php`
   - Membuat versi kanonis, snapshot tag/co-author, audit recovery, pointer versi, dan backfill versi `1.0`.
   - Mengubah identitas progress dari user-dokumen menjadi user-versi.
2. `database/migrations/2026_07_29_140002_create_km_access_targeting_and_publication.php`
   - Menambah histori efektif assignment organisasi, RBAC, target organisasi per versi, serta batch/recipient publikasi.
3. `database/migrations/2026_07_29_140003_create_km_compliance_tracking.php`
   - Membuat reading session, assignment, recipient snapshot, dan completion event.
4. `database/migrations/2026_07_29_140004_create_km_gamification_exports_and_hris.php`
   - Membuat badge, badge pengguna, audit export, outbound HRIS, serta seed lima badge awal.
5. `database/migrations/2026_08_02_160001_backfill_km_job_position_effective_from.php`
   - Mengisi `effective_from` yang kosong dari `created_at`, atau tanggal eksekusi bila `created_at` juga kosong.
   - Bersifat data-only dan tidak mengubah schema.

Jalankan satu per satu hanya setelah preflight disetujui:

```powershell
php artisan migrate --path=database/migrations/2026_07_29_140001_create_km_document_versions.php --force
php artisan migrate --path=database/migrations/2026_07_29_140002_create_km_access_targeting_and_publication.php --force
php artisan migrate --path=database/migrations/2026_07_29_140003_create_km_compliance_tracking.php --force
php artisan migrate --path=database/migrations/2026_07_29_140004_create_km_gamification_exports_and_hris.php --force
php artisan migrate --path=database/migrations/2026_08_02_160001_backfill_km_job_position_effective_from.php --force
```

Tidak dibuat jalur SQL kedua untuk migration `140001`-`140004` maupun data-repair `160001`. Hal ini sengaja mencegah migration yang sama dijalankan melalui dua mekanisme.

## Gate Setelah Setiap Migration

- Periksa exit code dan row migration yang baru.
- Jalankan `php artisan km:health --json` dan `php artisan km:readiness --json` setelah seluruh group selesai.
- Jalankan `php artisan km:document-capabilities --json` sebelum mengaktifkan pipeline.
- Pastikan `php artisan schedule:list` memuat command KM dan cron `schedule:run` tersedia.
- Jangan aktifkan `KM_DOCUMENT_PROCESSING_ENABLED=true` bila antivirus, LibreOffice, Poppler, atau Tesseract belum tersedia.
- Jangan aktifkan `KM_OFFICE_SUBMISSION_ENABLED=true` sebelum satu file PPT dan satu PPTX lolos antivirus, konversi, ekstraksi/OCR, thumbnail, preview inline, dan submit pada staging.
- Biarkan `KM_HRIS_ENABLED=false` sampai seluruh enam gate HRIS bernilai benar dan endpoint sandbox tersedia.

## Rollback

Method `down()` migration schema dibatasi ke `APP_ENV=testing` dan nama database berakhiran `_testing`. Migration data-repair `160001` mempunyai `down()` no-op karena tanggal awal yang kosong tidak dapat dipulihkan dengan aman. Untuk kegagalan rollout, kembalikan application code terlebih dahulu, pertahankan schema additive, lalu gunakan restore backup atau corrective migration yang disetujui.
