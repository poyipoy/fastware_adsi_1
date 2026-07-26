# Catatan Eksekusi KM Jangka Panjang

Tanggal eksekusi: 24 Juli 2026  
Mission: `MISSION-KM-JANGKA-PANJANG.md`  
Implementation plan: `IMPLEMENTATION-PLAN-KM-JANGKA-PANJANG.md`  
Branch saat preflight: `main`

## Batas Eksekusi

- Implementasi dibatasi pada bulk approval, metadata/tag search, analytics materi populer non-KPI, hardening controller/UI, dan `km:health`.
- `PENDING-DECISIONS-KM.md`, controller legacy `1225_KmPengajuanController.php`, dan perubahan non-KM tidak dimasukkan ke scope.
- Working tree sudah dirty sebelum sesi ini. Perubahan milik pengguna yang tidak terkait KM dipertahankan dan tidak dibersihkan.
- Database lokal `dms_adasi_rev1` hanya diperiksa secara read-only. Tidak ada migration atau test mutatif yang dijalankan terhadap database tersebut.
- Migration lifecycle dan seluruh test mutatif dijalankan hanya dengan `APP_ENV=testing` dan database `fastware_adsi_1_testing` yang memiliki suffix `_testing`.
- Tidak ada commit, push, branch baru, atau pull request yang dibuat.

## Preflight dan Drift Awal

Preflight menemukan drift repository terhadap schema lokal:

- Source migration `2026_07_18_120001_add_km_metadata_fulltext_index_to_km_pengajuans.php` tidak ada di working tree pada awal sesi.
- Database lokal sudah memiliki migration row `2026_07_18_120001_add_km_metadata_fulltext_index_to_km_pengajuans` pada batch 44.
- Database lokal sudah memiliki FULLTEXT index `km_pengajuans_judul_keterangan_fulltext` dengan urutan kolom `judul, keterangan`.
- Source migration direkonstruksi agar repository kembali merepresentasikan schema tersebut. Migration tidak dijalankan ulang pada `dms_adasi_rev1`.
- Preflight `km:readiness --json` pada database lokal menghasilkan 10 PASS, 2 WARN, dan 0 FAIL.

Baseline sebelum implementasi:

- Targeted Knowledge Management: 179 test lulus, 1.271 assertion.
- Full suite: 205 test lulus dan 27 test gagal, 1.480 assertion.
- Seluruh 27 kegagalan baseline berada di `HRMenuServiceTest`, `HRRoleAccessServiceTest`, dan `JobPositionAccessServiceTest`; ketiganya berada di luar scope mission ini.

## Implementasi

### Migration FULLTEXT

- Migration yang dipulihkan hanya mendukung MySQL dan memeriksa tabel, kolom, engine InnoDB, serta collision/drift index melalui `information_schema`.
- Index bernama `km_pengajuans_judul_keterangan_fulltext` dibuat hanya untuk `judul` dan `keterangan`.
- Schema yang sudah memiliki index tanpa migration row dihentikan dengan pesan drift eksplisit.
- `down()` hanya diizinkan pada environment `testing` dan database dengan suffix `_testing`, serta hanya menghapus named FULLTEXT index dengan definisi yang sesuai.
- Migration dimasukkan ke pembentukan schema `KmTestCase` dan diuji dengan siklus `up -> down -> up`.

### Bulk Approval Atomik

- Ditambahkan policy class-level `bulkApprove`, Form Request bulk, route `km.approvals.bulk`, controller orchestration, dan UI bulk pada halaman persetujuan.
- Maksimum 100 ID unik; action publik `approve|reject` menerima alias kompatibilitas `approved|rejected`.
- Approve mewajibkan kategori per item; reject mewajibkan reason non-whitespace maksimal 2.000 karakter dan mengabaikan kategori.
- Single dan bulk memakai primitive transition terkunci yang sama. Service mengurutkan ID, mengambil seluruh row melalui satu locked query, memvalidasi seluruh batch sebelum mutation pertama, dan membungkus row serta event dalam satu transaction.
- Setiap dokumen menghasilkan tepat satu approval event dengan request ID yang sama untuk satu batch. Kegagalan row/event mana pun me-rollback seluruh batch.
- Endpoint single approval, field legacy, redirect, flash, dan audit semantics tetap dipertahankan.

### Metadata dan Tag Search

- Pencarian dashboard memakai FULLTEXT hanya pada `judul` dan `keterangan`, setelah scope dokumen published diterapkan.
- `tag_ids[]` memakai semantics OR/any-of dan dapat digabung dengan filter serta pagination lama.
- `sort=relevance` hanya diterima bila query `q` terisi; urutan relevance adalah skor menurun lalu ID menaik.
- Default dan sort legacy tetap kompatibel; query string filter dipertahankan oleh paginator.

### Analytics Materi Populer Non-KPI

- Ditambahkan policy `viewPopularAnalytics`, halaman HTML, export XLSX/PDF, filter kategori/tag, dan link yang dibungkus policy.
- Dataset hanya dokumen published dan memakai correlated subquery untuk total views, completed readers distinct, dan likes tanpa aggregate multiplication.
- Ordering tunggal: views menurun, completed readers menurun, likes menurun, lalu document ID menaik.
- HTML dipaginasi 25; export membaca satu row tambahan untuk mendeteksi truncation dan membatasi output pada 10.000 row.
- Ketiga format memakai report service/dataset yang sama dan hanya memuat field dokumen/aggregate yang diizinkan, tanpa nama, email, NIK, relasi user, atau aktivitas individual.
- Label non-KPI, generated-at Asia/Jakarta, filter aktif, dan keterbatasan counter historis ditampilkan pada output.

### Hardening Controller, UI, dan Health

- Authoring, query/read model, interaction, approval, dan analytics dipindahkan ke service/Form Request/controller terpisah. `KmPengajuanController` tidak lagi melakukan query/mutation/validation inline.
- URI, route name, field form, JSON key, HTTP status, serta flash contract legacy dipertahankan.
- JavaScript persetujuan dipindahkan ke entry Vite KM; UI menyediakan select-all halaman aktif, kategori per row, jumlah pilihan, konfirmasi, error restoration, double-submit guard, responsive table/action, modal mobile, label accessible, dan fallback no-JavaScript untuk create/single approval.
- Asset PDF.js tetap lokal dan tidak ada CDN atau framework UI baru.
- `km:health` sekarang hanya menjalankan pemeriksaan read-only untuk config, schema, route, dan private-storage root. Warning runtime terpisah tidak membuat exit code gagal; FAIL mandatory menghasilkan exit non-zero. Pemeriksaan distribusi data tetap menjadi tanggung jawab `km:readiness`.

## Hasil Verifikasi

- Lima targeted test Jangka Panjang: 40 lulus, 313 assertion.
- Seluruh test `tests/Feature/KnowledgeManagement`: 207 lulus, 1.583 assertion.
- Full suite final: 233 test lulus dan 27 test gagal, 1.792 assertion, durasi 747,12 detik. Seluruh 27 kegagalan tetap identik dengan baseline pada tiga kelas unit test HR di luar scope; delta kegagalan baru adalah 0.
- PHP syntax lint untuk seluruh file PHP terkait: lulus.
- Laravel Pint test-only untuk file terkait: lulus.
- Pemeriksaan syntax entry JavaScript KM: lulus.
- `npm.cmd run build`: lulus; entry approval KM dan worker PDF.js lokal tersedia di manifest.
- `php artisan route:list --path=km`: lulus.
- Migration status lokal: FULLTEXT migration berstatus `Ran` pada batch 44; tidak dijalankan ulang.
- `php artisan view:cache`: lulus; `php artisan optimize:clear` juga lulus sebagai langkah penutup verifikasi.
- `php artisan km:health --json`: exit 0 dengan overall WARN; seluruh pemeriksaan mandatory PASS, sedangkan queue `sync` serta worker/scheduler yang tidak dapat dibuktikan dilaporkan sebagai WARN.
- Postflight `php artisan km:readiness --json`: 10 PASS, 2 WARN, dan 0 FAIL.
- Smoke otomatis mencakup bulk success/stale/unauthorized, kombinasi search/tag/pagination, parity dataset HTML/XLSX/PDF, forbidden path, dan kontrak fallback no-JavaScript. Smoke visual interaktif pada viewport 360/768/desktop sudah dicoba, tetapi runtime browser lokal gagal terhubung pada tahap inisialisasi sebelum navigasi/autentikasi. Karena itu, pemeriksaan visual tersebut tetap menjadi langkah deployment/manual QA.

## Rollout

1. Deploy source migration FULLTEXT.
2. Jalankan migration pada environment yang migration row-nya belum ada, sebelum mengaktifkan kode query FULLTEXT.
3. Deploy application code, route, policy, service, Form Request, controller, dan view.
4. Deploy hasil build Vite termasuk entry approval KM dan PDF.js lokal.
5. Refresh cache aplikasi.
6. Jalankan `km:health` dan `km:readiness`; hentikan rollout bila ada mandatory FAIL.

Untuk database lokal saat ini, cukup verifikasi migration status dan named index; jangan menjalankan ulang migration yang row dan index-nya sudah ada.

## Rollback

1. Kembalikan query pencarian ke implementasi non-FULLTEXT sebelum melepas FULLTEXT index.
2. Nonaktifkan bulk/analytics melalui rollback code dan route. Approval event yang sudah sah tidak dihapus.
3. Rollback bundle Vite bersamaan dengan view/controller yang bergantung padanya.
4. Penghapusan FULLTEXT index hanya dilakukan melalui migration `down()` pada environment testing/rollback terkontrol yang memenuhi guard database `_testing`.
5. Jalankan kembali cache refresh, `km:health`, dan `km:readiness` setelah rollback.

Warning queue `sync` serta worker/scheduler yang tidak dapat diverifikasi bukan mandatory failure, tetapi tetap menjadi prerequisite operasional sebelum asynchronous processing diaktifkan di deployment target.
