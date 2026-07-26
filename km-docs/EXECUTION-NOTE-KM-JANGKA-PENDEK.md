# Execution Note: KM Jangka Pendek — Stabilization

Tanggal implementasi: 18 Juli 2026  
Verifikasi akhir: 19 Juli 2026  
Repository: `fastware_adsi_1`  
Status implementasi: enam fitur mission telah diimplementasikan; migration KM sudah dijalankan pada database lokal aplikasi `dms_adasi_rev1`.

Catatan acceptance: feature scope 6/6 sudah tersedia dan seluruh gate KM yang dapat dijalankan otomatis ditutup oleh test terarah. Tiga gate literal repository/deployment belum boleh diklaim lulus: fresh migration seluruh repository terblokir migration non-KM lama, Pint seluruh repository masih memiliki debt awal, dan UAT browser desktop/mobile serta dua-session harus dilakukan operator pada staging.

## Ruang Lingkup yang Diselesaikan

- Baseline dan hardening enam tabel KM legacy melalui migration `100001` sampai `100004`.
- Audit/repair schema dengan manifest dan checksum, termasuk validasi shape kolom, index, unique key, dan foreign key. Repair memakai sidecar journal atomik dengan state recovery `applying → committing → applied`; manifest audit asli tetap immutable.
- Enum status, transition service, reject reason wajib, dan approval event append-only.
- Completion membaca dan pemberian poin yang idempotent, termasuk race dua koneksi/proses.
- Policy per objek, upload private, migrasi file legacy replay-safe dengan manifest v2, serta preview/download berotorisasi.
- Factory, feature test MySQL, readiness command read-only, dan adapter kompatibilitas untuk route/view legacy.

Item pada `PENDING-DECISIONS-KM.md` tidak diimplementasikan.

## Keselamatan Data dan Environment

- Semua test yang memutasi database dijalankan dengan `APP_ENV=testing` dan database `fastware_adsi_1_testing`.
- `phpunit.xml` mengunci koneksi test ke MySQL dan nama database bersuffix `_testing`.
- Migration KM `100001` sampai `100004` sudah dijalankan pada database lokal aplikasi `dms_adasi_rev1` pada 19 Juli 2026, batch 37.
- Tidak ada repair, cleanup, atau pemindahan file legacy yang dijalankan pada database aplikasi `dms_adasi_rev1`.
- Source of truth yang diubah hanya `app/Http/Controllers/KmPengajuanController.php`; controller duplikat lama `1225_KmPengajuanController.php` tidak disentuh.
- Manifest audit terbaru berada di `storage/app/private/km/schema-audits/20260719_143339_067654.json`. Manifest berisi metadata schema, finding, dan checksum; tidak memuat credential atau isi dokumen.

## Bukti Verifikasi

| Pemeriksaan | Hasil |
| --- | --- |
| Baseline full suite sebelum perubahan | 15 lulus, 27 gagal |
| Targeted `tests/Feature/KnowledgeManagement` | 92 lulus, 853 assertion |
| Full suite setelah perubahan | 107 lulus, 27 gagal, 932 assertion |
| Delta failure | 0 failure baru |
| `php -l` seluruh PHP baru/berubah | 54 file lulus |
| Pint seluruh PHP baru/berubah selain `routes/web.php` | 53 file lulus |
| Route KM dan `knowledge-management` | route baru dan adapter legacy tersedia |
| `php artisan view:cache` | lulus |
| Migration KM fresh/legacy, rollback empat migration, dan migrate ulang | lulus di MySQL testing melalui `KmSchemaMigrationTest` |
| Audit schema aplikasi `km:audit-schema --write-manifest --strict` | PASS, 0 finding blocking |

Kegagalan full suite tetap sama dengan baseline:

- 18 test `HRMenuServiceTest`: Eloquent resolver tidak tersedia dan dua signature test lama tidak sesuai typed `User`.
- 4 test `HRRoleAccessServiceTest`: Eloquent resolver tidak tersedia.
- 5 test `JobPositionAccessServiceTest`: model `App\Models\TcJobPosition` tidak tersedia.

Full-repository Pint tetap merah sebagai debt awal repository (535 style issue dan 2 parse error pada file di luar scope). Pemeriksaan terukur terhadap seluruh PHP mission lulus. `routes/web.php` memiliki debt format lama; diff mission pada file tersebut hanya empat baris untuk dua route private dan lulus `php -l`.

Fresh `php artisan migrate` untuk seluruh repository pada database kosong masih terhenti sebelum migration KM oleh migration lama `2024_03_25_100325_create_time_lines_table.php`, yang membuat foreign key ke tabel `mesin` sebelum tabel itu tersedia. Karena masalah ini berada di luar mission KM, tidak dilakukan perubahan schema non-KM. Jalur migration KM sendiri telah diuji fresh, pada shape legacy, rollback, dan migrate ulang.

## Status Readiness Database Lokal Setelah Migration

`php artisan km:readiness` pada database lokal aplikasi setelah migration KM memberikan hasil:

- `PASS schema.columns`: kolom baseline, hardening, approval, dan metadata file tersedia.
- `PASS schema.column_shapes`: tipe, nullability, primary key, dan auto-increment penting sesuai.
- `PASS schema.unique`: constraint idempotensi tersedia.
- `PASS schema.indexes`: 14 index non-unique KM wajib tersedia.
- `PASS schema.foreign_keys`: 13 foreign key KM wajib sesuai target dan delete rule.
- `PASS storage.private`: disk private berada di luar public root dan dapat diakses.
- `WARN files.legacy`: 30 dokumen legacy belum dimigrasikan ke private storage.
- `PASS files.metadata`: 0 dokumen memiliki metadata file parsial atau disk/path tidak valid.
- `PASS files.public_exposure`: 0 dokumen private masih memiliki binary pada public/assets/image.
- `PASS files.checksum`: 0 private file hilang atau checksum mismatch.
- `WARN queue.connection`: aplikasi masih memakai queue `sync`.
- `WARN scheduler.deployment`: cron/worker harus diverifikasi operator pada environment deployment.

Command readiness tidak mengubah row KM atau file private, dan perilaku read-only dikunci oleh test. Migration file legacy ke private storage belum dijalankan.

## Urutan Rollout

1. Backup database dan file KM di `public/assets/image`; simpan bukti bahwa backup dapat dipulihkan.
2. Jalankan `php artisan km:audit-schema --write-manifest --strict` dan arsipkan manifest.
3. Bila audit menemukan duplicate/orphan, tinjau manifest lalu jalankan repair hanya dengan approval operator dan guard command yang tersedia.
4. Jalankan migration secara berurutan: `100001` → `100002` → `100003` → `100004`.
5. Jalankan `php artisan km:migrate-private-files`; simpan manifest file migration dan verifikasi backup/checksum.
6. Jalankan `php artisan km:readiness --strict`; selesaikan WARN queue/scheduler sesuai topology deployment.
7. Lakukan UAT owner, approver, employee, PDF/Office download, replay completion, dan viewport desktop/mobile.
8. Setelah verifikasi, jalankan `php artisan optimize:clear` dan pantau error authorization, streaming, serta queue/failed jobs.

## Rollback

1. Pulihkan file public dengan `php artisan km:migrate-private-files --restore-manifest=<path>`; command menolak overwrite bila checksum berbeda.
2. Verifikasi seluruh metadata file kembali `NULL` sebelum rollback migration `100004`; migration akan berhenti bila file belum dipulihkan.
3. Keempat method `down()` sengaja memiliki guard `APP_ENV=testing` dan suffix database `_testing`; karena itu rollback schema production tidak dapat dijalankan memakai migration ini. Untuk production, rollback release aplikasi sambil mempertahankan schema additive dan prioritaskan forward-fix. Reversal schema memerlukan migration maintenance baru yang ditinjau serta disetujui operator, bukan bypass guard yang ada.
4. Untuk repair duplicate/orphan yang belum di-hardening, gunakan `php artisan km:repair-schema <manifest> --restore`; checksum mismatch menghentikan restore.
5. Jangan mengurangi atau menghitung ulang `users.km_total_poin`; award completion yang sah tetap dipertahankan.

## Verifikasi Manual yang Masih Memerlukan Operator

Runtime browser interaktif tidak tersedia pada sesi implementasi dan repository tidak memiliki Playwright/Cypress/E2E fallback. Karena itu screenshot serta smoke test visual desktop/mobile belum diklaim selesai. Perilaku owner/approver/employee, reject reason, file private, Office fallback, replay completion, dan compatibility route telah ditutup oleh feature test; layout visual dan dua-session browser tetap harus diverifikasi pada staging sebelum rollout produksi. Layout global legacy masih memuat bundle Bootstrap ganda di luar scope mission; verifikasi staging harus mencakup interaksi modal KM untuk mendeteksi konflik runtime tersebut.
