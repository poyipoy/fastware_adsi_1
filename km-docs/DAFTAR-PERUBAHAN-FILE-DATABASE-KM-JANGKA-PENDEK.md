# Daftar Perubahan File, Folder, dan Database KM Jangka Pendek

Tanggal penyusunan: 19 Juli 2026
Repository: `fastware_adsi_1`
Database lokal aplikasi: `dms_adasi_rev1`

Dokumen ini mencatat file/folder dan database yang ditambahkan atau dimodifikasi untuk implementasi Knowledge Management jangka pendek. Sumber pemeriksaan: `git status --short`, `git diff --name-status`, daftar file workspace, `php artisan migrate:status`, `php artisan km:readiness`, dan manifest file migration KM.

## Ringkasan Status

- Migration KM `100001` sampai `100004` sudah dijalankan pada database lokal `dms_adasi_rev1`, batch 37.
- Command migrasi file legacy ke private storage sudah berjalan sebagian pada 19 Juli 2026 pukul 14:40:30 +07:00.
- Manifest file migration: `storage/app/private/km/file-migrations/20260719_144030_500162.json`.
- 19 dokumen legacy sudah dipindah dari `public/assets/image` ke private storage.
- 11 dokumen legacy masih tersisa menurut `php artisan km:readiness`.
- `km:readiness` PASS untuk schema, unique constraint, index, foreign key, private storage, metadata, public exposure, dan checksum; masih WARN untuk sisa legacy file, queue sync, dan scheduler deployment.

## File Existing yang Dimodifikasi

### Root dan konfigurasi

- `.gitignore`
- `config/filesystems.php`
  - Menambahkan disk private KM `km_private` dengan root `storage/app/private/km`.
- `phpunit.xml`
  - Mengunci environment test agar memakai database testing bersuffix `_testing`.

### Controller, provider, dan service existing

- `app/Http/Controllers/KmPengajuanController.php`
  - Menambahkan Form Request, authorization policy, workflow submit/update/approval, private preview/download, mark read, dan completion.
- `app/Providers/AuthServiceProvider.php`
  - Mendaftarkan `KmPengajuanPolicy`.
- `app/Services/Dashboard/KnowledgeManagementDashboardService.php`
  - Menyesuaikan query dashboard KM dengan schema dan workflow baru.

### Model existing

- `app/Models/Insight.php`
  - Menambahkan casts/relationship untuk relasi KM.
- `app/Models/KmKategori.php`
  - Menambahkan casts dan relationship ke dokumen KM.
- `app/Models/KmLihatBuku.php`
  - Menambahkan casts dan relationship transaksi/dokumen.
- `app/Models/KmPengajuan.php`
  - Menambahkan fillable/casts metadata private file, helper preview/download, relationship approval events, dan relationship KM lain.
- `app/Models/KmSuka.php`
  - Menambahkan casts dan relationship user/dokumen.
- `app/Models/KmTransaksi.php`
  - Menambahkan casts `completed_at` dan `points_awarded_at`, helper status baca, dan relationship.

### View existing

- `resources/views/dashboard/dsKnowlege.blade.php`
  - Menyesuaikan tampilan dashboard KM dengan data/workflow baru.
- `resources/views/knowlege_management/pengajuanKM.blade.php`
  - Menyesuaikan form/list dokumen, status, preview/download private, dan aksi baca/complete.
- `resources/views/knowlege_management/persetujuanKM.blade.php`
  - Menyesuaikan approval/reject, alasan reject, dan route private file.

### Route existing

- `routes/web.php`
  - Menambahkan route KM private preview/download dan endpoint interaksi baca/complete.
  - Menjaga compatibility route legacy seperti `pengajuanKM`, `persetujuanKM`, dan `dsKnowlege`.

## File Source Code Baru

### Console command

- `app/Console/Commands/AuditKmSchemaCommand.php`
  - Command audit schema KM dan manifest.
- `app/Console/Commands/KmReadinessCommand.php`
  - Command readiness deployment KM.
- `app/Console/Commands/MigrateKmFilesToPrivateStorageCommand.php`
  - Command migrasi file legacy ke private storage, manifest, checksum, backup, restore.
- `app/Console/Commands/RepairKmSchemaCommand.php`
  - Command repair schema berbasis manifest dan journal.

### Enum

- `app/Enums/KnowledgeManagement/KmApprovalAction.php`
- `app/Enums/KnowledgeManagement/KmDocumentStatus.php`
- `app/Enums/KnowledgeManagement/KmReadStatus.php`

### Exception

- `app/Exceptions/KnowledgeManagement/InvalidKmTransitionException.php`

### Form Request

- `app/Http/Requests/KnowledgeManagement/ApproveKmDocumentRequest.php`
- `app/Http/Requests/KnowledgeManagement/CompleteKmReadingRequest.php`
- `app/Http/Requests/KnowledgeManagement/MarkKmReadingRequest.php`
- `app/Http/Requests/KnowledgeManagement/StoreKmDocumentRequest.php`
- `app/Http/Requests/KnowledgeManagement/UpdateKmDocumentRequest.php`

### Model baru

- `app/Models/KmApprovalEvent.php`
  - Model event approval append-only; update/delete ditolak.

### Policy

- `app/Policies/KmPengajuanPolicy.php`
  - Policy object-level untuk view, create, update, delete, approve, reject, preview/download, dan complete reading.

### Service KM

- `app/Services/KnowledgeManagement/KmAccessService.php`
- `app/Services/KnowledgeManagement/KmApprovalService.php`
- `app/Services/KnowledgeManagement/KmDocumentWorkflowService.php`
- `app/Services/KnowledgeManagement/KmFileService.php`
- `app/Services/KnowledgeManagement/KmReadingService.php`
- `app/Services/KnowledgeManagement/KmSchemaAuditService.php`

## Migration Baru

- `database/migrations/2026_07_18_100001_baseline_knowledge_management_schema.php`
  - Baseline legacy-aware untuk tabel KM bila belum tersedia.
- `database/migrations/2026_07_18_100002_harden_knowledge_management_constraints.php`
  - Hardening index, unique constraint, foreign key, tipe kolom, dan kolom transaksi baca.
- `database/migrations/2026_07_18_100003_create_km_approval_events_table.php`
  - Membuat tabel `km_approval_events`.
- `database/migrations/2026_07_18_100004_add_private_file_metadata_to_km_pengajuans.php`
  - Menambahkan metadata private file di `km_pengajuans`.

Status migration lokal:

| Migration | Batch | Status |
| --- | ---: | --- |
| `2026_07_18_100001_baseline_knowledge_management_schema` | 37 | Ran |
| `2026_07_18_100002_harden_knowledge_management_constraints` | 37 | Ran |
| `2026_07_18_100003_create_km_approval_events_table` | 37 | Ran |
| `2026_07_18_100004_add_private_file_metadata_to_km_pengajuans` | 37 | Ran |

## Factory Baru

- `database/factories/KmApprovalEventFactory.php`
- `database/factories/KmKategoriFactory.php`
- `database/factories/KmPengajuanFactory.php`
- `database/factories/KmTransaksiFactory.php`

## Test Baru

### Feature test

- `tests/Feature/KnowledgeManagement/KmApprovalWorkflowTest.php`
- `tests/Feature/KnowledgeManagement/KmAuthorizationTest.php`
- `tests/Feature/KnowledgeManagement/KmBladeCompatibilityTest.php`
- `tests/Feature/KnowledgeManagement/KmDocumentUpdateValidationTest.php`
- `tests/Feature/KnowledgeManagement/KmPrivateFileAccessTest.php`
- `tests/Feature/KnowledgeManagement/KmReadinessCommandTest.php`
- `tests/Feature/KnowledgeManagement/KmReadingPointIdempotencyTest.php`
- `tests/Feature/KnowledgeManagement/KmSchemaMigrationTest.php`
- `tests/Feature/KnowledgeManagement/KmTestCase.php`

### Test support

- `tests/Support/KnowledgeManagement/RunsKmWorkers.php`
- `tests/Support/KnowledgeManagement/km_parallel_worker.php`

## Dokumen Baru atau Dimodifikasi

- `AGENTS.md`
  - Instruksi kerja coding agent repository, termasuk aturan KM.
- `km-docs/MISSION-KM-JANGKA-PENDEK.md`
- `km-docs/IMPLEMENTATION-PLAN-KM-JANGKA-PENDEK.md`
- `km-docs/PENDING-DECISIONS-KM.md`
- `km-docs/EXECUTION-NOTE-KM-JANGKA-PENDEK.md`
- `km-docs/MISSION-KM-JANGKA-MENENGAH.md`
- `km-docs/IMPLEMENTATION-PLAN-KM-JANGKA-MENENGAH.md`
- `km-docs/MISSION-KM-JANGKA-PANJANG.md`
- `km-docs/IMPLEMENTATION-PLAN-KM-JANGKA-PANJANG.md`
- `km-docs/DAFTAR-PERUBAHAN-FILE-DATABASE-KM-JANGKA-PENDEK.md`
  - Dokumen inventaris ini.

## Folder Baru atau Terisi Baru

### Source code dan test

- `app/Enums/KnowledgeManagement/`
- `app/Exceptions/KnowledgeManagement/`
- `app/Http/Requests/KnowledgeManagement/`
- `app/Policies/`
- `app/Services/KnowledgeManagement/`
- `tests/Feature/KnowledgeManagement/`
- `tests/Support/KnowledgeManagement/`
- `km-docs/`

### Private storage KM

- `storage/app/private/km/documents/`
  - Tujuan file dokumen private.
- `storage/app/private/km/legacy-backup/`
  - Backup private dari file public legacy yang sudah dipindahkan.
- `storage/app/private/km/file-migrations/`
  - Manifest migrasi file legacy.
- `storage/app/private/km/schema-audits/`
  - Manifest audit schema KM.

## File Runtime Private Storage yang Ditambahkan

### Manifest file migration

- `storage/app/private/km/file-migrations/20260719_144030_500162.json`

### Manifest audit schema

- `storage/app/private/km/schema-audits/20260718_131746_828587.json`
- `storage/app/private/km/schema-audits/20260718_141530_973161.json`
- `storage/app/private/km/schema-audits/20260718_152303_577291.json`
- `storage/app/private/km/schema-audits/20260719_143312_712646.json`
- `storage/app/private/km/schema-audits/20260719_143339_067654.json`

### Dokumen private hasil migrasi

- `storage/app/private/km/documents/1/4d31deda-5470-4026-a7d8-78dc0105896e.pdf`
- `storage/app/private/km/documents/2/36f3a4cd-c639-42b8-997b-7152712955bc.pdf`
- `storage/app/private/km/documents/3/6949d1da-bbfd-46a4-bd09-b205b5e1cf5e.pdf`
- `storage/app/private/km/documents/4/44075c58-a9b5-4ad1-902f-1bffe8ee77d6.pdf`
- `storage/app/private/km/documents/5/05cf64f4-4ac0-4dc2-9619-6f719ae877e5.pdf`
- `storage/app/private/km/documents/6/11c6a2c5-051d-4ae0-9b11-376c80415c31.pdf`
- `storage/app/private/km/documents/7/c98db1a6-37cf-449c-bfe5-a64506f252f4.pdf`
- `storage/app/private/km/documents/8/4cdeb509-3e49-4336-90c1-c827aa372f08.pdf`
- `storage/app/private/km/documents/9/28180de2-d878-460a-abe2-5ebd73a9d4ab.pdf`
- `storage/app/private/km/documents/10/f352a4ec-d0c9-4110-8fb1-8f122f3cf87a.pdf`
- `storage/app/private/km/documents/11/d44c72f4-0a8d-4162-ac71-9d7c31983fa5.pdf`
- `storage/app/private/km/documents/12/40e19646-978f-47f4-b2b2-53d726ed9194.pdf`
- `storage/app/private/km/documents/13/bb949634-678b-4f03-ade3-ba6c950f18ec.pdf`
- `storage/app/private/km/documents/14/978306c5-332e-4805-86e4-130354a54933.pdf`
- `storage/app/private/km/documents/15/b3e58b9a-e777-427a-bf20-9301f52eff67.pdf`
- `storage/app/private/km/documents/16/b70ce156-7190-4aa9-b73d-abcdfbc17868.pdf`
- `storage/app/private/km/documents/17/31e355fb-13fa-4d17-9b0f-e2efb12fed6a.pdf`
- `storage/app/private/km/documents/18/21532008-a41b-4b10-85e3-41bb47035b5e.pdf`
- `storage/app/private/km/documents/20/e4a6ad0e-1ed3-4bc3-994d-73a4405b9611.pdf`

### Backup private file legacy

- `storage/app/private/km/legacy-backup/1/LTASvLeSeQcGl9q4RXBGuefmKHYO9Fz1clOFQBa1.pdf`
- `storage/app/private/km/legacy-backup/2/m7vQjK7h2r7Uz6Ac1BelLXC3fyZ3NwkDq9gQE3zW.pdf`
- `storage/app/private/km/legacy-backup/3/M00YZXsJuAKchMSl1tAHkypfHJQ4uwJ327JsTjJS.pdf`
- `storage/app/private/km/legacy-backup/4/01XFLikskNnZ4t92VsRUsHCH1erbqshniaQFQZBh.pdf`
- `storage/app/private/km/legacy-backup/5/EQLSTFc0wwCE8IFSspWkceLWlBQB0iBKIZffrK7P.pdf`
- `storage/app/private/km/legacy-backup/6/pHK87Rn8pSVhMyoRt81pOxRERRPSnr6D3OrJCawF.pdf`
- `storage/app/private/km/legacy-backup/7/LgB6WcxDla9RfgNJfqAxHS6nNMrPirMjl40HbZB7.pdf`
- `storage/app/private/km/legacy-backup/8/KKaim1zek27G5isDM09qvEzLyGOjpoMXuHxqVQVO.pdf`
- `storage/app/private/km/legacy-backup/9/7fiOckPX2O79cv7UuMiWKV16MM4Asm0ta9WfpzUi.pdf`
- `storage/app/private/km/legacy-backup/10/S8teUOJJZkzFcMzPALu1j97awyPgp58HCnhyarco.pdf`
- `storage/app/private/km/legacy-backup/11/5PiMRxQqxvgSKvk8N5HbDyCs4PXvemfaFQmBKDbd.pdf`
- `storage/app/private/km/legacy-backup/12/4NiiHUxDmnYgDEgpGXUdVN9WGdxdqBpV1GtFLlEr.pdf`
- `storage/app/private/km/legacy-backup/13/EAF3eEJ016qwgaUHxRaDCAUSNOXwYqPGsWSUL5e1.pdf`
- `storage/app/private/km/legacy-backup/14/xMQnZXNtk8s3eE5OQSr4JiG39ckXqMelOEl4jpNG.pdf`
- `storage/app/private/km/legacy-backup/15/DBcC2yV2aw4bFIjYGPboQEOQwX4KOhMBtzHOmESh.pdf`
- `storage/app/private/km/legacy-backup/16/5Swc8Hn3h632PjN8NlubdNf1mFhGJARNFgHOcT4V.pdf`
- `storage/app/private/km/legacy-backup/17/Zxtkl78hkAwdt1qcpRKiP3uUHuhmeajOulxtoKfq.pdf`
- `storage/app/private/km/legacy-backup/18/1YTnxkIMwZSwfS69mkOXYakwz8qWOMeGTMXUptU8.pdf`
- `storage/app/private/km/legacy-backup/20/IdPddjAPCn44V7Vw7yHP7u1VYkvVaEus6WUo5Lrg.pdf`

## File Public Legacy yang Dipindahkan dari `public/assets/image`

File berikut tercatat deleted di git working tree karena sudah dipindahkan ke private storage dan backup private oleh command file migration:

- `public/assets/image/01XFLikskNnZ4t92VsRUsHCH1erbqshniaQFQZBh.pdf`
- `public/assets/image/1YTnxkIMwZSwfS69mkOXYakwz8qWOMeGTMXUptU8.pdf`
- `public/assets/image/4NiiHUxDmnYgDEgpGXUdVN9WGdxdqBpV1GtFLlEr.pdf`
- `public/assets/image/5PiMRxQqxvgSKvk8N5HbDyCs4PXvemfaFQmBKDbd.pdf`
- `public/assets/image/5Swc8Hn3h632PjN8NlubdNf1mFhGJARNFgHOcT4V.pdf`
- `public/assets/image/7fiOckPX2O79cv7UuMiWKV16MM4Asm0ta9WfpzUi.pdf`
- `public/assets/image/DBcC2yV2aw4bFIjYGPboQEOQwX4KOhMBtzHOmESh.pdf`
- `public/assets/image/EAF3eEJ016qwgaUHxRaDCAUSNOXwYqPGsWSUL5e1.pdf`
- `public/assets/image/EQLSTFc0wwCE8IFSspWkceLWlBQB0iBKIZffrK7P.pdf`
- `public/assets/image/IdPddjAPCn44V7Vw7yHP7u1VYkvVaEus6WUo5Lrg.pdf`
- `public/assets/image/KKaim1zek27G5isDM09qvEzLyGOjpoMXuHxqVQVO.pdf`
- `public/assets/image/LTASvLeSeQcGl9q4RXBGuefmKHYO9Fz1clOFQBa1.pdf`
- `public/assets/image/LgB6WcxDla9RfgNJfqAxHS6nNMrPirMjl40HbZB7.pdf`
- `public/assets/image/M00YZXsJuAKchMSl1tAHkypfHJQ4uwJ327JsTjJS.pdf`
- `public/assets/image/S8teUOJJZkzFcMzPALu1j97awyPgp58HCnhyarco.pdf`
- `public/assets/image/Zxtkl78hkAwdt1qcpRKiP3uUHuhmeajOulxtoKfq.pdf`
- `public/assets/image/m7vQjK7h2r7Uz6Ac1BelLXC3fyZ3NwkDq9gQE3zW.pdf`
- `public/assets/image/pHK87Rn8pSVhMyoRt81pOxRERRPSnr6D3OrJCawF.pdf`
- `public/assets/image/xMQnZXNtk8s3eE5OQSr4JiG39ckXqMelOEl4jpNG.pdf`

## Perubahan Database

### Tabel yang dibaseline atau diaudit oleh migration `100001`

Migration baseline bersifat legacy-aware: membuat tabel hanya bila belum ada, dan tidak menghapus tabel legacy yang sudah tersedia.

- `km_kategoris`
- `km_pengajuans`
- `km_transaksis`
- `km_lihat_bukus`
- `km_sukas`
- `km_insights`

### Perubahan tabel `km_transaksis`

- Kolom baru `completed_at`.
- Kolom baru `points_awarded_at`.
- Unique constraint `km_transaksis_user_document_unique` pada `id_user`, `id_km_pengajuan`.
- Index `km_transaksis_status_completed_at_index` pada `status`, `completed_at`.
- Index `km_transaksis_document_index` pada `id_km_pengajuan`.
- Index `km_transaksis_modified_by_index` pada `modified_by`.
- Foreign key `km_transaksis_user_foreign` ke `users.id`, delete cascade.
- Foreign key `km_transaksis_document_foreign` ke `km_pengajuans.id`, delete cascade.
- Foreign key `km_transaksis_modified_by_foreign` ke `users.id`, delete set null.
- Backfill `completed_at` dan `points_awarded_at` untuk transaksi status selesai sesuai migration hardening.

### Perubahan tabel `km_pengajuans`

- Kolom baru `file_disk`.
- Kolom baru `file_path`.
- Kolom baru `file_original_name`.
- Kolom baru `file_mime_type`.
- Kolom baru `file_size_bytes`.
- Kolom baru `file_checksum_sha256`.
- Kolom baru `file_migrated_at`.
- Index `km_pengajuans_status_posisi_index` pada `status`, `posisi`.
- Index `km_pengajuans_user_status_index` pada `id_user`, `status`.
- Index `km_pengajuans_category_index` pada `id_km_kategori`.
- Index `km_pengajuans_file_checksum_index` pada `file_checksum_sha256`.
- Index `km_pengajuans_file_migrated_at_index` pada `file_migrated_at`.
- Foreign key `km_pengajuans_user_foreign` ke `users.id`, delete set null.
- Foreign key `km_pengajuans_category_foreign` ke `km_kategoris.id`, delete set null.
- Metadata private file diisi untuk 19 dokumen hasil migration file: document ID `1` sampai `18`, dan `20`.

### Tabel baru `km_approval_events`

Tabel ini dibuat oleh migration `100003` sebagai audit trail append-only untuk approval KM.

Kolom:

- `id`
- `km_pengajuan_id`
- `actor_id`
- `action`
- `reason`
- `from_status`
- `to_status`
- `acted_at`
- `created_at`

Index dan foreign key:

- Index `km_approval_events_document_acted_at_index` pada `km_pengajuan_id`, `acted_at`.
- Index `km_approval_events_actor_acted_at_index` pada `actor_id`, `acted_at`.
- Foreign key `km_approval_events_document_foreign` ke `km_pengajuans.id`, delete restrict.
- Foreign key `km_approval_events_actor_foreign` ke `users.id`, delete set null.

Catatan: tabel ini tidak memakai `updated_at` karena event approval bersifat append-only.

### Perubahan tabel `km_sukas`

- Unique constraint `km_sukas_user_document_unique` pada `id_user`, `id_km_pengajuan`.
- Index `km_sukas_document_index` pada `id_km_pengajuan`.
- Foreign key `km_sukas_user_foreign` ke `users.id`, delete cascade.
- Foreign key `km_sukas_document_foreign` ke `km_pengajuans.id`, delete cascade.

### Perubahan tabel `km_lihat_bukus`

- Kolom `jumlah_lihat` dinormalisasi menjadi `BIGINT UNSIGNED NOT NULL DEFAULT 0`.
- Null `jumlah_lihat` dibackfill ke `0`.
- Unique constraint `km_lihat_bukus_document_unique` pada `id_km_pengajuan`.
- Index `km_lihat_bukus_transaction_index` pada `id_km_transaksi`.
- Foreign key `km_lihat_bukus_document_foreign` ke `km_pengajuans.id`, delete cascade.
- Foreign key `km_lihat_bukus_transaction_foreign` ke `km_transaksis.id`, delete set null.

### Perubahan tabel `km_insights`

- Index `km_insights_user_index` pada `id_user`.
- Index `km_insights_document_index` pada `id_km_pengajuan`.
- Foreign key `km_insights_user_foreign` ke `users.id`, delete cascade.
- Foreign key `km_insights_document_foreign` ke `km_pengajuans.id`, delete cascade.

### Perubahan metadata migration Laravel

Tabel `migrations` bertambah empat record migration KM batch 37:

- `2026_07_18_100001_baseline_knowledge_management_schema`
- `2026_07_18_100002_harden_knowledge_management_constraints`
- `2026_07_18_100003_create_km_approval_events_table`
- `2026_07_18_100004_add_private_file_metadata_to_km_pengajuans`

### Perubahan row data karena migrasi file

Command `km:migrate-private-files` memperbarui metadata private file pada 19 row `km_pengajuans`:

| Document ID | Private path | Backup legacy |
| ---: | --- | --- |
| 1 | `documents/1/4d31deda-5470-4026-a7d8-78dc0105896e.pdf` | `legacy-backup/1/LTASvLeSeQcGl9q4RXBGuefmKHYO9Fz1clOFQBa1.pdf` |
| 2 | `documents/2/36f3a4cd-c639-42b8-997b-7152712955bc.pdf` | `legacy-backup/2/m7vQjK7h2r7Uz6Ac1BelLXC3fyZ3NwkDq9gQE3zW.pdf` |
| 3 | `documents/3/6949d1da-bbfd-46a4-bd09-b205b5e1cf5e.pdf` | `legacy-backup/3/M00YZXsJuAKchMSl1tAHkypfHJQ4uwJ327JsTjJS.pdf` |
| 4 | `documents/4/44075c58-a9b5-4ad1-902f-1bffe8ee77d6.pdf` | `legacy-backup/4/01XFLikskNnZ4t92VsRUsHCH1erbqshniaQFQZBh.pdf` |
| 5 | `documents/5/05cf64f4-4ac0-4dc2-9619-6f719ae877e5.pdf` | `legacy-backup/5/EQLSTFc0wwCE8IFSspWkceLWlBQB0iBKIZffrK7P.pdf` |
| 6 | `documents/6/11c6a2c5-051d-4ae0-9b11-376c80415c31.pdf` | `legacy-backup/6/pHK87Rn8pSVhMyoRt81pOxRERRPSnr6D3OrJCawF.pdf` |
| 7 | `documents/7/c98db1a6-37cf-449c-bfe5-a64506f252f4.pdf` | `legacy-backup/7/LgB6WcxDla9RfgNJfqAxHS6nNMrPirMjl40HbZB7.pdf` |
| 8 | `documents/8/4cdeb509-3e49-4336-90c1-c827aa372f08.pdf` | `legacy-backup/8/KKaim1zek27G5isDM09qvEzLyGOjpoMXuHxqVQVO.pdf` |
| 9 | `documents/9/28180de2-d878-460a-abe2-5ebd73a9d4ab.pdf` | `legacy-backup/9/7fiOckPX2O79cv7UuMiWKV16MM4Asm0ta9WfpzUi.pdf` |
| 10 | `documents/10/f352a4ec-d0c9-4110-8fb1-8f122f3cf87a.pdf` | `legacy-backup/10/S8teUOJJZkzFcMzPALu1j97awyPgp58HCnhyarco.pdf` |
| 11 | `documents/11/d44c72f4-0a8d-4162-ac71-9d7c31983fa5.pdf` | `legacy-backup/11/5PiMRxQqxvgSKvk8N5HbDyCs4PXvemfaFQmBKDbd.pdf` |
| 12 | `documents/12/40e19646-978f-47f4-b2b2-53d726ed9194.pdf` | `legacy-backup/12/4NiiHUxDmnYgDEgpGXUdVN9WGdxdqBpV1GtFLlEr.pdf` |
| 13 | `documents/13/bb949634-678b-4f03-ade3-ba6c950f18ec.pdf` | `legacy-backup/13/EAF3eEJ016qwgaUHxRaDCAUSNOXwYqPGsWSUL5e1.pdf` |
| 14 | `documents/14/978306c5-332e-4805-86e4-130354a54933.pdf` | `legacy-backup/14/xMQnZXNtk8s3eE5OQSr4JiG39ckXqMelOEl4jpNG.pdf` |
| 15 | `documents/15/b3e58b9a-e777-427a-bf20-9301f52eff67.pdf` | `legacy-backup/15/DBcC2yV2aw4bFIjYGPboQEOQwX4KOhMBtzHOmESh.pdf` |
| 16 | `documents/16/b70ce156-7190-4aa9-b73d-abcdfbc17868.pdf` | `legacy-backup/16/5Swc8Hn3h632PjN8NlubdNf1mFhGJARNFgHOcT4V.pdf` |
| 17 | `documents/17/31e355fb-13fa-4d17-9b0f-e2efb12fed6a.pdf` | `legacy-backup/17/Zxtkl78hkAwdt1qcpRKiP3uUHuhmeajOulxtoKfq.pdf` |
| 18 | `documents/18/21532008-a41b-4b10-85e3-41bb47035b5e.pdf` | `legacy-backup/18/1YTnxkIMwZSwfS69mkOXYakwz8qWOMeGTMXUptU8.pdf` |
| 20 | `documents/20/e4a6ad0e-1ed3-4bc3-994d-73a4405b9611.pdf` | `legacy-backup/20/IdPddjAPCn44V7Vw7yHP7u1VYkvVaEus6WUo5Lrg.pdf` |

Metadata yang diisi per row:

- `file_disk = km_private`
- `file_path`
- `file_original_name`
- `file_mime_type`
- `file_size_bytes`
- `file_checksum_sha256`
- `file_migrated_at`

## Status yang Belum Selesai

- `php artisan km:readiness` masih mencatat `WARN files.legacy`: 11 dokumen legacy belum dimigrasikan.
- Queue masih memakai driver `sync`.
- Scheduler/cron/worker eksternal masih harus diverifikasi oleh operator saat deployment.
- Full repository masih memiliki failure baseline non-KM dan Pint debt di luar scope KM.
