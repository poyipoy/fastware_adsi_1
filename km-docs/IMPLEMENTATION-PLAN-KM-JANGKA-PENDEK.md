# IMPLEMENTATION PLAN: KM Jangka Pendek — Stabilization

## Urutan Eksekusi

### 0. Bekukan baseline sebelum perubahan

1. Pastikan source of truth adalah `app/Http/Controllers/KmPengajuanController.php`; jangan mengedit `app/Http/Controllers/1225_KmPengajuanController.php`.
2. Catat output awal `php artisan test`, `php artisan route:list`, dan `php artisan view:cache`. Simpan jumlah pass/fail dan nama failure sebagai baseline pada deskripsi PR; suite yang memang sudah merah tidak boleh bertambah merah.
3. Verifikasi `APP_ENV=testing` dan nama `DB_DATABASE` berakhiran `_testing` sebelum menjalankan `migrate:fresh`, rollback, repair schema, atau pengujian migration. Hentikan command destruktif bila salah satu guard gagal.
4. Lakukan audit awal read-only dengan query `SELECT`/schema inspection yang setara dengan daftar pemeriksaan pada langkah 1 dan simpan ringkasannya pada catatan eksekusi. Setelah command `km:audit-schema` dibuat pada langkah 1, jalankan ulang `php artisan km:audit-schema --write-manifest`; manifest JSON disimpan di `storage/app/private/km/schema-audits/<timestamp>.json` dan tidak boleh memuat credential atau isi dokumen.

### 1. Baseline dan hardening schema KM legacy

**Migration -> service/command -> model -> test**

1. Buat `database/migrations/2026_07_18_100001_baseline_knowledge_management_schema.php`.
   - Gunakan `Schema::hasTable()` agar migration membuat tabel hanya pada database baru dan tidak mendefinisikan ulang tabel legacy.
   - Bentuk enam tabel dengan nama/kolom legacy: `km_kategoris`, `km_pengajuans`, `km_transaksis`, `km_lihat_bukus`, `km_sukas`, dan `km_insights`. Pada fresh schema, gunakan `BIGINT UNSIGNED` untuk seluruh reference ke `users.id`; pertahankan signed `INT` untuk `km_pengajuans.id` dan seluruh reference dokumen agar sama dengan schema legacy yang diaudit.
   - Pada fresh database, langsung berikan primary key auto-increment dan timestamps; constraint lintas tabel tetap dipasang oleh migration hardening berikutnya agar jalur fresh dan legacy identik.
2. Buat `app/Services/KnowledgeManagement/KmSchemaAuditService.php` dan command `app/Console/Commands/AuditKmSchemaCommand.php` dengan signature `km:audit-schema {--write-manifest} {--strict}`.
   - Audit keberadaan tabel/kolom, duplicate primary ID, duplicate `(id_user,id_km_pengajuan)` pada transaksi/like, duplicate counter per dokumen, nilai `jumlah_lihat` non-numerik/negatif, dan orphan ke user/dokumen/kategori/transaksi.
   - `--strict` mengembalikan exit code non-zero bila hardening belum aman; mode default tetap read-only dan selalu menampilkan ringkasan.
3. Buat `app/Console/Commands/RepairKmSchemaCommand.php` dengan signature `km:repair-schema {manifest} {--apply} {--restore}` dan guard wajib testing untuk test otomatis. Untuk deployment non-testing, command hanya boleh berjalan setelah operator memberikan `--apply` dan file manifest cocok dengan checksum database saat ini.
   - Duplicate transaksi: pertahankan ID terkecil, gunakan status tertinggi (`3` mengalahkan `2`), timestamp creation paling awal dan update paling akhir, lalu arsipkan row yang dilebur di manifest.
   - Duplicate like: pertahankan ID terkecil; duplicate counter: jumlahkan hanya nilai numerik non-negatif ke ID terkecil.
   - Orphan child yang tidak lagi memiliki user/dokumen tidak dipindahkan ke parent palsu; arsipkan lalu keluarkan row yang tidak dapat dipakai. Relasi kategori dokumen yang orphan dinormalisasi menjadi `NULL`.
   - `--restore` hanya tersedia sebelum constraint dipasang dan mengembalikan row dari manifest; checksum mismatch wajib menghentikan proses.
4. Buat `database/migrations/2026_07_18_100002_harden_knowledge_management_constraints.php`.
   - Tambahkan primary key/auto-increment yang hilang, normalisasi `jumlah_lihat` menjadi unsigned integer, ubah reference user (`id_user` dan `modified_by` yang memang berisi user ID) menjadi `BIGINT UNSIGNED`, tambah kolom transaksi `completed_at` dan `points_awarded_at`, serta buat index/unique/foreign key sesuai bagian Database Changes.
   - Preflight di awal migration harus melempar `RuntimeException` berisi command audit/repair yang perlu dijalankan bila duplicate/orphan masih ada; migration tidak boleh menghapus data secara implisit.
   - Backfill row `km_transaksis.status=3` dengan `completed_at=COALESCE(updated_at,created_at)` dan `points_awarded_at=COALESCE(updated_at,created_at)` untuk memblokir award berikutnya. Jangan menyentuh `users.km_total_poin` dan jangan mengisi `poin` historis yang tidak diketahui.
5. Perbarui model `KmPengajuan`, `KmKategori`, `KmTransaksi`, `KmLihatBuku`, `KmSuka`, dan `Insight` hanya untuk relasi, fillable/cast baru, dan tipe counter. Jangan rename tabel/kolom legacy pada fase ini.
6. Tambahkan `tests/Feature/KnowledgeManagement/KmSchemaMigrationTest.php`: fresh migrate, legacy-shape migrate, dirty preflight gagal tanpa data loss, audit/repair/restore, constraint uniqueness, rollback empat migration, dan migrate ulang pada MySQL testing.

### 2. Enum dan transition service kompatibel

**Enum -> service -> model/controller -> view -> test**

1. Buat enum backed berikut di `app/Enums/KnowledgeManagement`:
   - `KmDocumentStatus.php`: `INACTIVE=0`, `DRAFT=1`, `PENDING_APPROVAL=2`, `PUBLISHED=3`.
   - `KmReadStatus.php`: `READING=2`, `COMPLETED=3`.
   - `KmApprovalAction.php`: `SUBMITTED='submitted'`, `APPROVED='approved'`, `REJECTED='rejected'`, `DEACTIVATED='deactivated'`.
2. Buat `app/Services/KnowledgeManagement/KmDocumentWorkflowService.php` dengan method `submit()`, `approve()`, `reject()`, dan `deactivate()`.
   - Kunci row `km_pengajuans` sebelum transisi.
   - Izinkan hanya `DRAFT -> PENDING_APPROVAL`, `PENDING_APPROVAL -> PUBLISHED`, `PENDING_APPROVAL -> DRAFT`, serta `DRAFT|PUBLISHED -> INACTIVE`.
   - Set `persetujuan` bersamaan dengan `status`: inactive `0`, draft/pending `1`, published `2`.
   - Lempar domain exception untuk transisi invalid; controller mengubahnya menjadi 422, sedangkan authorization failure tetap 403.
3. Jangan pasang enum cast langsung pada kolom `KmPengajuan.status` atau `KmTransaksi.status` dalam fase ini karena Blade legacy masih membandingkan integer. Tambahkan helper `documentStatus()`/`readStatus()` yang menggunakan `tryFrom((int) $this->status)`; seluruh code baru memakai enum/service, sedangkan view lama tetap menerima integer.
4. Ubah `KmPengajuanController::kirimKM()` dan `updateStatus()` agar hanya memanggil workflow service. Pertahankan URI dan route name `kirimKM` serta `updateStatusKM` sebagai adapter kompatibilitas.
5. Ganti magic number pada Blade KM dengan value enum yang diekspor dari controller/view data atau helper model, tanpa rename `dsKnowlege` dan `knowlege_management`.
6. Tambahkan kasus transisi valid/invalid pada `KmApprovalWorkflowTest` dan pastikan invalid transition tidak mengubah `status`, `persetujuan`, atau audit event.

### 3. Idempotensi completion dan poin

**Migration constraint -> request -> service -> controller/route -> view -> test**

1. Buat Form Request:
   - `app/Http/Requests/KnowledgeManagement/MarkKmReadingRequest.php` untuk `id_km_pengajuan` required/existing dan authorization `view`.
   - `app/Http/Requests/KnowledgeManagement/CompleteKmReadingRequest.php` dengan kontrak yang sama.
2. Buat `app/Services/KnowledgeManagement/KmReadingService.php`:
   - `markStarted(User $user, KmPengajuan $document)` memakai `firstOrCreate` berdasarkan unique `(id_user,id_km_pengajuan)`, tidak pernah menurunkan `COMPLETED`, dan melakukan atomic upsert/increment pada satu `km_lihat_bukus` per dokumen.
   - `complete(User $user, KmPengajuan $document)` berjalan dalam `DB::transaction()`, mengunci user dan transaksi, lalu membuat row bila belum ada. Bila status sudah `COMPLETED` atau `points_awarded_at` terisi, return hasil idempotent dengan `points_awarded=0`.
   - Saat dua request sama-sama belum menemukan row, tangkap duplicate-key dari unique constraint, ambil ulang row canonical dengan `lockForUpdate()`, lalu lanjutkan jalur idempotent; jangan mengembalikan 500 akibat race.
   - Hanya transisi pertama yang mengisi `status=3`, `poin` sebagai snapshot poin kategori, `completed_at`, `points_awarded_at`, `modified_by`, lalu melakukan `users.km_total_poin = km_total_poin + poin` secara atomik.
   - Bila kategori/poin tidak valid, batalkan transaction dengan validation/domain error; jangan memberi nilai default yang mengubah aturan bisnis.
3. Refactor `KmPengajuanController::markAsRead()` dan `saveTransaction()` menjadi adapter tipis ke service. Pertahankan route name `kmTransaksi.markAsRead` dan `kmTransaksi.saveTransaction`, serta response JSON lama; tambahkan field `already_completed` dan `points_awarded` tanpa menghapus field `success`.
4. Ubah JavaScript tombol selesai di `resources/views/dashboard/dsKnowlege.blade.php` untuk men-disable tombol setelah submit pertama dan memperlakukan response idempotent sebagai sukses. Server-side idempotency tetap menjadi pengaman utama.
5. Buat `tests/Feature/KnowledgeManagement/KmReadingPointIdempotencyTest.php` untuk first completion, replay, transaksi historis status 3, start-after-complete, missing category, unauthorized document, unique constraint, dan dua koneksi MySQL yang mencoba complete pasangan yang sama.

### 4. Policy authorization dan private document streaming

**Policy/access service -> storage config/migration -> file service/command -> request/controller -> route/view -> test**

1. Buat `app/Services/KnowledgeManagement/KmAccessService.php` dan pindahkan mapping posisi legacy dari `KnowledgeManagementDashboardService` ke service ini supaya query dashboard dan policy memakai aturan yang sama.
   - Owner boleh melihat draft miliknya.
   - `HRMenuAccessGroup::KNOWLEDGE_APPROVAL` boleh melihat dan memproses pending approval.
   - User biasa hanya boleh melihat dokumen published yang cocok dengan posisi legacy; role full-access yang sekarang melihat semua published tetap dipertahankan.
2. Buat `app/Policies/KmPengajuanPolicy.php` dengan ability `viewAny`, `view`, `create`, `update`, `submit`, `approve`, `reject`, `deactivate`, dan `completeReading`. Daftarkan mapping di `app/Providers/AuthServiceProvider.php`.
   - `update`, `submit`, dan deactivate draft memerlukan owner; full-access existing boleh menjadi bypass terkontrol.
   - `approve`/`reject` memerlukan `HRMenuAccessGroup::KNOWLEDGE_APPROVAL` dan status pending.
   - Semua endpoint berbasis ID (`edit`, `update`, `showPersetujuan`, `updateStatus`, `kirimKM`, mark/complete, like/unlike, insight, preview/download) harus memanggil `authorize()`; menyembunyikan tombol Blade bukan authorization.
3. Tambahkan disk `km_private` di `config/filesystems.php` dengan driver `local`, root `storage_path('app/private/km')`, `visibility='private'`, dan `throw=true`.
4. Buat `database/migrations/2026_07_18_100004_add_private_file_metadata_to_km_pengajuans.php` sebelum refactor upload dijalankan. Kolom dan index tercantum pada Database Changes.
5. Buat `app/Services/KnowledgeManagement/KmFileService.php`:
   - Upload baru ke `km_private/documents/{document_id}/{uuid}.{extension}`; nama path tidak boleh berasal dari input user.
   - MIME ditentukan dari server (`UploadedFile::getMimeType()`/Fileinfo), extension divalidasi terhadap allow-list yang sudah berlaku, size dicatat, dan checksum SHA-256 dihitung dari stream.
   - `streamPreview()` hanya inline untuk MIME PDF; PPT/PPTX mengembalikan 415 dengan instruksi download. `streamDownload()` memakai nama asli yang disanitasi.
   - Selalu cek keberadaan file dan kecocokan checksum metadata; jangan menerima path dari query/body.
6. Buat command idempotent `app/Console/Commands/MigrateKmFilesToPrivateStorageCommand.php` dengan signature `km:migrate-private-files {--dry-run} {--limit=100} {--restore-manifest=}`.
   - Sumber legacy adalah `public/assets/image/{km_pengajuans.file}` setelah basename/path traversal check.
   - Copy ke private, verifikasi checksum sumber/tujuan, pindahkan sumber ke `km_private/legacy-backup/{document_id}/`, lalu update metadata dalam transaction. Simpan manifest source/destination/checksum dan lakukan compensating move untuk mengembalikan sumber bila update database gagal; pada error akhir source dan row harus tetap seperti semula.
   - `--restore-manifest` mengembalikan metadata dan file public dari private backup; tidak boleh overwrite file public berbeda checksum.
7. Refactor `storeKM()`/`update()` memakai Form Request baru (`StoreKmDocumentRequest`, `UpdateKmDocumentRequest`) dan `KmFileService`. Binary dokumen baru tidak boleh masuk public; thumbnail legacy tetap tidak diubah dalam mission ini.
8. Tambahkan route dalam group `auth`:
   - `GET /km/documents/{kmPengajuan}/preview` -> `KmPengajuanController::preview`, name `km.documents.preview`.
   - `GET /km/documents/{kmPengajuan}/download` -> `KmPengajuanController::download`, name `km.documents.download`.
   - Gunakan implicit model binding, policy, `X-Content-Type-Options: nosniff`, cache private/no-store, dan Content-Disposition yang tepat.
9. Ubah seluruh link/file source pada tiga Blade KM ke route baru. Jangan menghapus route name lama atau rename path Blade.
10. Buat `tests/Feature/KnowledgeManagement/KmAuthorizationTest.php` dan `KmPrivateFileAccessTest.php`: guest/owner/approver/employee, object ID milik user lain, published-position visibility, path traversal, MIME spoof, missing/checksum mismatch, inline PDF, forced download Office, upload private, migrasi ulang, dan restore manifest.

### 5. Reject reason wajib dan approval audit append-only

**Migration -> enum/model -> request/service -> controller/route -> view -> test**

1. Buat `database/migrations/2026_07_18_100003_create_km_approval_events_table.php` sebelum migration metadata file; urutan timestamp migration tetap `100003` lalu `100004`.
2. Buat `app/Models/KmApprovalEvent.php` dengan cast `action` ke `KmApprovalAction`, `acted_at` datetime, dan `metadata` array. Model tidak mempunyai `updated_at`; event listener `updating` dan `deleting` melempar `LogicException` agar event append-only dari aplikasi.
3. Tambahkan relasi `approvalEvents()` pada `KmPengajuan` dan `actor()` pada `KmApprovalEvent`.
4. Buat `app/Http/Requests/KnowledgeManagement/ApproveKmDocumentRequest.php`.
   - Normalisasi button legacy `approve`/`reject` menjadi `action` pada `prepareForValidation()`.
   - Validasi action hanya approved/rejected, kategori/posisi/judul seperti form sekarang, dan `reason` required/string/max:2000 ketika rejected.
5. Buat `app/Services/KnowledgeManagement/KmApprovalService.php`. Method `submit`, `approve`, `reject`, dan `deactivate` membuka satu transaction, `lockForUpdate()` dokumen, memanggil workflow service, lalu membuat event dengan `km_pengajuan_id`, actor snapshot, action, from/to status, reason, metadata request correlation, dan `acted_at`.
6. Refactor `approveKM()`, `kirimKM()`, dan `updateStatus()` ke approval service. Jangan tulis request payload penuh ke application log; audit resmi hanya berada di tabel event.
7. Perbarui `resources/views/knowlege_management/persetujuanKM.blade.php`: tambahkan textarea `reason`, tampilkan hanya ketika reject dipilih, beri client validation untuk UX, dan selalu pertahankan server validation. Tampilkan riwayat event read-only pada modal/detail bila data sudah tersedia.
8. Pertahankan route `approveKM`, `kirimKM`, dan `updateStatusKM`; tidak ada route update/delete untuk event.
9. Buat/lengkapi `tests/Feature/KnowledgeManagement/KmApprovalWorkflowTest.php`: reason kosong/whitespace, approve tanpa reason, actor/status snapshot, append-only guard, unauthorized approver, invalid state, rollback event failure, dan concurrent approver hanya menghasilkan satu transisi valid.

### 6. Factory, test baseline, dan readiness

**Factory -> command -> tests -> verification**

1. Buat factory:
   - `database/factories/KmKategoriFactory.php`.
   - `database/factories/KmPengajuanFactory.php` dengan state `draft`, `pending`, `published`, dan `inactive`.
   - `database/factories/KmTransaksiFactory.php` dengan state `reading` dan `completed`.
   - `database/factories/KmApprovalEventFactory.php`.
2. Jangan menambahkan data KM dummy ke `DatabaseSeeder`; factory hanya digunakan test kecuali seeder eksplisit diminta kemudian.
3. Buat `app/Console/Commands/KmReadinessCommand.php`, signature `km:readiness {--strict} {--json}`. Check bersifat read-only:
   - tabel/kolom/index/foreign key/unique KM;
   - disk `km_private` terkonfigurasi, root berada di luar public web root, direktori tersedia, dan permission dapat diperiksa tanpa membuat/menghapus file probe;
   - jumlah dokumen legacy yang belum dimigrasikan serta checksum mismatch;
   - `QUEUE_CONNECTION`, keberadaan tabel `jobs`/`failed_jobs` bila driver database, dan akses failed jobs;
   - scheduler code tersedia dan menampilkan bahwa cron/worker eksternal perlu diverifikasi operator.
   - Schema/storage failure = FAIL dan exit 1; queue/scheduler yang belum diperlukan fase ini = WARN dan exit 0, kecuali `--strict` maka exit 1.
4. Buat `tests/Feature/KnowledgeManagement/KmReadinessCommandTest.php` untuk output PASS/WARN/FAIL, mode JSON, strict mode, dan memastikan command tidak memutasi data bisnis.
5. Jalankan checklist verifikasi di bawah. Semua failure baru harus diperbaiki dalam mission ini; jangan menandainya sebagai backlog.

## Database Changes

### `2026_07_18_100001_baseline_knowledge_management_schema.php`

- Fresh schema mempertahankan nama/relasi legacy, tetapi tipe reference harus cocok dengan parent aktual: `users.id` adalah `BIGINT UNSIGNED`, sedangkan `km_pengajuans.id` dan reference dokumennya tetap signed `INT`.
  - `km_kategoris`: `id BIGINT`, `nama_kategori VARCHAR(255)`, `poin_kategori INT`, timestamps.
  - `km_pengajuans`: `id INT`, `id_user BIGINT UNSIGNED NULL`, `id_km_kategori BIGINT NULL`, `judul VARCHAR(255) NULL`, `keterangan VARCHAR(3000) NULL`, `posisi VARCHAR(255) NULL`, `image`, `file`, `file_name`, `persetujuan VARCHAR(255) NULL`, `status INT`, timestamps nullable, `modified_by VARCHAR(255) NULL`.
  - `km_transaksis`: `id INT`, `id_km_pengajuan INT NULL`, `id_user BIGINT UNSIGNED NULL`, `poin INT NULL`, `level INT NULL`, `status INT`, timestamps nullable, `modified_by BIGINT UNSIGNED NULL`.
  - `km_lihat_bukus`: `id BIGINT`, `id_km_transaksi INT NULL`, `id_km_pengajuan INT NULL`, `jumlah_lihat` unsigned BIGINT default `0`, timestamps.
  - `km_sukas`: `id BIGINT`, `id_user BIGINT UNSIGNED NULL`, `id_km_pengajuan INT NULL`, `jumlah_like BIGINT NULL`, timestamps.
  - `km_insights`: `id INT`, `id_user BIGINT UNSIGNED NULL`, `id_km_pengajuan INT NULL`, `content VARCHAR(1200) NULL`, timestamps.
- `down()` wajib memverifikasi `APP_ENV=testing` dan suffix database `_testing`, lalu menjatuhkan keenam tabel dalam urutan child-ke-parent. Pada environment lain, lempar `RuntimeException` dan gunakan forward-fix; jangan pernah menebak apakah tabel production merupakan tabel legacy.

### `2026_07_18_100002_harden_knowledge_management_constraints.php`

- Tambahkan `completed_at TIMESTAMP NULL` dan `points_awarded_at TIMESTAMP NULL` ke `km_transaksis`.
- Normalisasi `km_lihat_bukus.jumlah_lihat` ke unsigned BIGINT default `0` setelah audit nilai non-numerik.
- Pada database legacy, ubah `km_pengajuans.id_user`, `km_transaksis.id_user`, `km_transaksis.modified_by`, `km_sukas.id_user`, dan `km_insights.id_user` menjadi `BIGINT UNSIGNED NULL` sebelum menambah FK ke `users.id`; audit nilai non-numerik/orphan wajib bersih lebih dahulu.
- Primary/auto-increment: seluruh enam tabel memiliki PK `id`; tipe ID tidak diubah melintasi signed/unsigned bila target FK belum sama.
- Unique:
  - `km_transaksis(id_user,id_km_pengajuan)` bernama `km_transaksis_user_document_unique`.
  - `km_sukas(id_user,id_km_pengajuan)` bernama `km_sukas_user_document_unique`.
  - `km_lihat_bukus(id_km_pengajuan)` bernama `km_lihat_bukus_document_unique`.
- Index: `km_pengajuans(status,posisi)`, `km_pengajuans(id_user,status)`, `km_pengajuans(id_km_kategori)`, `km_transaksis(status,completed_at)`, serta foreign-key columns pada insight/like/view.
- Foreign key behavior:
  - Pengajuan -> user dan kategori: nullable + `nullOnDelete` agar dokumen legacy tidak hilang.
  - Transaksi/like/insight -> user dan pengajuan: `cascadeOnDelete` setelah orphan audit.
  - Lihat buku -> pengajuan: `cascadeOnDelete`; -> transaksi: nullable + `nullOnDelete`.
- `down()` menghapus foreign key/index/unique dengan nama eksplisit, menghapus dua timestamp baru, dan mengubah counter kembali hanya pada testing rollback. Data merge dari repair command dipulihkan lewat manifest, bukan ditebak oleh `down()`.

### `2026_07_18_100003_create_km_approval_events_table.php`

- `id BIGINT` primary auto-increment.
- `km_pengajuan_id INT` FK ke pengajuan dengan `restrictOnDelete` agar audit tidak hilang.
- `actor_id BIGINT UNSIGNED NULL` FK ke `users.id` dengan `nullOnDelete`.
- `actor_name VARCHAR(255) NULL` dan `actor_role_snapshot VARCHAR(255) NULL` untuk snapshot audit.
- `action VARCHAR(32)`, `from_status TINYINT UNSIGNED NULL`, `to_status TINYINT UNSIGNED`, `reason TEXT NULL`, `metadata JSON NULL`, `acted_at TIMESTAMP`, `created_at TIMESTAMP`; tidak ada `updated_at`.
- Index `(km_pengajuan_id,acted_at)` dan `(actor_id,acted_at)`.
- `down()` drop table; sebelum production rollback, export event sebagai bagian backup deployment.

### `2026_07_18_100004_add_private_file_metadata_to_km_pengajuans.php`

- Tambahkan nullable: `file_disk VARCHAR(32)`, `file_path VARCHAR(1024)`, `file_original_name VARCHAR(255)`, `file_mime_type VARCHAR(127)`, `file_size_bytes BIGINT UNSIGNED`, `file_checksum_sha256 CHAR(64)`, dan `file_migrated_at TIMESTAMP`.
- Tambahkan index non-unique pada `file_checksum_sha256` karena dua dokumen boleh memiliki binary identik; tambahkan index `file_migrated_at` untuk command migrasi batch.
- Kolom legacy `file` dan `file_name` tidak dihapus selama minimal satu release.
- `down()` hanya menghapus kolom metadata setelah manifest file dikembalikan; binary private tidak dihapus otomatis oleh rollback database.

## Testing Checklist

### Automated

- [ ] Pastikan database test: `APP_ENV=testing` dan `DB_DATABASE` berakhiran `_testing`.
- [ ] Jalankan syntax check pada seluruh PHP baru/berubah dengan `php -l`.
- [ ] Jalankan `vendor/bin/pint --test` (atau `vendor\\bin\\pint --test` di PowerShell).
- [ ] Jalankan `php artisan test --filter=KmSchemaMigrationTest`.
- [ ] Jalankan `php artisan test --filter=KmReadingPointIdempotencyTest`.
- [ ] Jalankan `php artisan test --filter=KmAuthorizationTest`.
- [ ] Jalankan `php artisan test --filter=KmPrivateFileAccessTest`.
- [ ] Jalankan `php artisan test --filter=KmApprovalWorkflowTest`.
- [ ] Jalankan `php artisan test --filter=KmReadinessCommandTest`.
- [ ] Jalankan `php artisan test`; bandingkan hasil dengan baseline dan pastikan tidak ada failure baru.
- [ ] Pada database MySQL testing: migrate -> rollback empat migration KM -> migrate ulang; verifikasi schema dan data fixture setelah setiap tahap.
- [ ] Jalankan `php artisan route:list --name=km` dan verifikasi route baru serta adapter route lama.
- [ ] Jalankan `php artisan view:cache`, lalu `php artisan optimize:clear` setelah verifikasi.

### Manual/UAT

- [ ] Owner membuat draft, edit, submit; user lain tidak dapat edit/submit dengan mengganti ID.
- [ ] Approver melihat pending, approve, dan reject; reject tanpa alasan gagal, reject dengan alasan kembali ke draft.
- [ ] Employee hanya melihat published sesuai posisi; URL preview/download dokumen yang tidak eligible menghasilkan 403.
- [ ] PDF tampil inline dari route private; PPT/PPTX tidak dicoba sebagai PDF dan tersedia sebagai authorized download.
- [ ] Double click/replay tombol selesai tidak menggandakan poin; dua browser/session bersamaan juga hanya memberi satu award.
- [ ] Upload baru tidak muncul di `public/assets/image`; migrasi file legacy sukses hanya setelah checksum cocok dan backup private tersedia.
- [ ] Layout submit/approval/dashboard tetap usable pada desktop dan viewport mobile Bootstrap; route/view legacy tidak menghasilkan 404.
- [ ] `php artisan km:readiness` menampilkan status aktual dengan pesan tindakan yang jelas dan tidak mengubah row KM.

## Rollback Plan

1. **Sebelum deploy:** backup database dan `public/assets/image` terkait KM, simpan manifest audit/file migration, serta catat batch migration. Jangan menjalankan cleanup/migration pada production tanpa backup yang dapat dipulihkan.
2. **Feature rollback:** nonaktifkan pemanggilan service baru melalui rollback release aplikasi, tetapi pertahankan route name lama. Karena adapter lama tetap ada, UI dapat kembali tanpa perubahan URL.
3. **File rollback:** jalankan `php artisan km:migrate-private-files --restore-manifest=<path>` untuk mengembalikan file dari `km_private/legacy-backup`; verifikasi checksum sebelum metadata dikembalikan. Jangan menghapus private copy sampai aplikasi lama berhasil membaca file public.
4. **Database rollback:** hanya pada database testing atau maintenance window yang disetujui, rollback migration dalam urutan `100004 -> 100003 -> 100002 -> 100001`. Export approval events sebelum drop `100003`; `100002` hanya melepas constraint/kolom dan tidak menebak restorasi duplicate.
5. **Cleanup rollback:** bila repair duplicate/orphan perlu dibatalkan sebelum hardening, jalankan `km:repair-schema <manifest> --restore`. Setelah constraint dipasang, lepaskan constraint pada maintenance window terlebih dahulu; checksum mismatch menghentikan restore.
6. **Point safety:** rollback tidak pernah mengurangi atau menghitung ulang `users.km_total_poin`. Bila release gagal setelah suatu completion sah ter-commit, pertahankan award tersebut; event historis tidak dibalik tanpa keputusan bisnis terpisah.
7. **Forward-fix default:** setelah data production melewati hardening, prioritaskan forward fix untuk bug aplikasi. Drop kolom, penghapusan permanent file backup, dan penghapusan route/field legacy ditunda minimal satu release stabil.
