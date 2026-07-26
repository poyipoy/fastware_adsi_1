# MISSION: KM Jangka Pendek — Stabilization

## Goal

Menjadikan modul Knowledge Management (KM) pada Laravel 10 aman untuk dikembangkan lebih lanjut dengan skema yang dapat direproduksi, transisi status yang terkendali, completion dan poin yang idempotent, serta akses dokumen yang selalu diotorisasi. Fase ini mempertahankan perilaku dan nama route/view lama agar alur pengajuan, persetujuan, dan membaca yang sedang dipakai tidak terputus.

Mission ini adalah satu sesi eksekusi agent. Agent harus menyelesaikan keenam fitur di bawah secara berurutan, menjalankan seluruh verifikasi, dan tidak memperluas scope ke fitur yang memerlukan keputusan stakeholder.

## Scope

- **In scope:** baseline dan hardening enam tabel KM legacy; enum dan aturan transisi status; completion/poin idempotent untuk transaksi baru; policy per objek; pemindahan file dokumen ke private storage; preview/download berotorisasi; alasan penolakan wajib; audit approval append-only; factory, feature test, dan command readiness.
- **In scope:** mempertahankan controller aktif `app/Http/Controllers/KmPengajuanController.php`, folder view legacy `resources/views/knowlege_management`, view `resources/views/dashboard/dsKnowlege.blade.php`, serta seluruh route name lama selama minimal satu release kompatibilitas.
- **Out of scope:** perubahan saldo `users.km_total_poin` historis, rekonsiliasi atau migrasi point ledger historis, definisi completion berbasis halaman/waktu/quiz, versioning dokumen, approval berjenjang/SLA/reminder, master organisasi baru, notifikasi, thumbnail/conversion/OCR, full-text search, analytics KPI, compliance, PWA, integrasi HR, badge/tier/leaderboard baru, dan reward nyata.
- **Out of scope:** upgrade Laravel, perubahan framework UI, pemindahan/rename massal path salah eja, penghapusan route lama, serta penghapusan permanen file public tanpa checksum dan backup pemulihan.
- **Alasan pengelompokan revisi:** mission ini mengambil Sprint 0/Stabilization dari roadmap audit sebagai satu sesi utuh karena seluruh fitur berikutnya bergantung pada schema, status, idempotensi, policy, private storage, dan test baseline. Quick win UI Phase 1 dipindahkan ke Jangka Menengah agar satu mission tidak memuat estimasi gabungan 12–18 hari kerja dan urutan dependency tetap aman.

## Fitur yang Dikerjakan

1. **Baseline dan hardening schema KM legacy.** Representasikan `km_kategoris`, `km_pengajuans`, `km_transaksis`, `km_lihat_bukus`, `km_sukas`, dan `km_insights` dengan migration Laravel yang aman untuk database kosong maupun database legacy. Audit duplicate/orphan harus dijalankan sebelum constraint, dan migration wajib additive serta reversible.
2. **Enum dan transisi status yang kompatibel.** Gunakan `KmDocumentStatus`, `KmReadStatus`, dan `KmApprovalAction` sebagai vocabulary tunggal tanpa mengubah angka yang sudah tersimpan. Semua mutasi status harus melewati `KmDocumentWorkflowService`; field legacy `persetujuan` tetap disinkronkan selama masa kompatibilitas.
3. **Completion dan pemberian poin idempotent.** Pindahkan `markAsRead()` dan `saveTransaction()` ke `KmReadingService`, proses completion di dalam database transaction dengan row lock, dan pastikan hanya transisi pertama menuju selesai yang boleh menambah poin. Saldo poin dan transaksi selesai historis tidak dihitung ulang atau dikurangi.
4. **Policy authorization dan private document streaming.** Terapkan `KmPengajuanPolicy` pada seluruh endpoint KM, pindahkan dokumen dari `public/assets/image` ke disk private `km_private` dengan driver local, kemudian layani preview/download hanya melalui route `km.documents.preview` dan `km.documents.download` setelah policy lulus.
5. **Alasan penolakan dan audit approval append-only.** Penolakan wajib memiliki alasan, approve/reject harus atomik, dan setiap submit/approve/reject/deactivate menghasilkan `km_approval_events` yang tidak dapat diedit atau dihapus dari alur aplikasi.
6. **Factory, test baseline, dan readiness check.** Sediakan factory data KM, targeted feature tests, serta command read-only yang memeriksa schema, storage, queue, scheduler, dan failed-job readiness. Baseline full suite dicatat agar fase ini tidak memperkenalkan kegagalan baru di luar kondisi awal repository.

Urutan di atas wajib: schema mendahului enum/service; enum mendahului completion dan approval; policy mendahului file streaming; seluruh perilaku kemudian dikunci oleh test dan readiness check.

## Acceptance Criteria

### 1. Baseline dan hardening schema KM legacy

- `php artisan migrate` pada database MySQL testing kosong membentuk keenam tabel KM beserta primary key, auto-increment, index, foreign key yang kompatibel, dan timestamps.
- Migration yang sama dapat dijalankan pada snapshot schema legacy tanpa mencoba membuat ulang tabel atau kolom yang sudah ada.
- Audit schema melaporkan duplicate transaksi/like/view-counter dan orphan reference sebelum constraint diterapkan; tidak ada data yang dihapus diam-diam tanpa manifest cleanup.
- Setelah hardening, hanya ada satu `km_transaksis` per `(id_user, id_km_pengajuan)`, satu `km_sukas` per pasangan yang sama, dan satu aggregate `km_lihat_bukus` per dokumen.
- Kolom `jumlah_lihat` menjadi integer non-negatif; relasi nullable tetap nullable dan menggunakan `nullOnDelete`, sedangkan child yang tidak bermakna tanpa parent menggunakan `cascadeOnDelete` sesuai implementation plan.
- Batch migration dapat di-rollback lalu di-migrate ulang pada database yang memenuhi `APP_ENV=testing` dan nama database berakhiran `_testing`.

### 2. Enum dan transisi status yang kompatibel

- `KmDocumentStatus` memetakan tepat `INACTIVE=0`, `DRAFT=1`, `PENDING_APPROVAL=2`, dan `PUBLISHED=3`.
- `KmReadStatus` memetakan `READING=2` dan `COMPLETED=3`; tidak adanya row transaksi tetap berarti belum membaca.
- Transisi yang diterima adalah `DRAFT -> PENDING_APPROVAL`, `PENDING_APPROVAL -> PUBLISHED`, `PENDING_APPROVAL -> DRAFT` untuk reject, serta `DRAFT|PUBLISHED -> INACTIVE`. Transisi lain menghasilkan HTTP 422/403 sesuai penyebab dan tidak mengubah database.
- `persetujuan` tetap mengikuti mapping legacy: inactive `0`, draft/pending `1`, published `2`; Blade yang masih membandingkan integer tetap berfungsi.
- Endpoint lama `kirimKM`, `approveKM`, dan `updateStatusKM` tetap tersedia, tetapi mendelegasikan perubahan status ke service yang sama.

### 3. Completion dan poin idempotent

- Dua request completion berurutan maupun paralel untuk user dan dokumen yang sama menghasilkan tepat satu row `km_transaksis`, satu transisi ke `COMPLETED`, dan satu penambahan `users.km_total_poin` sebesar snapshot `km_kategoris.poin_kategori`.
- Request completion ulang pada transaksi berstatus `COMPLETED` mengembalikan sukses idempotent dengan `points_awarded=0` dan tidak mengubah saldo.
- Row historis yang sudah berstatus `3` ditandai sudah selesai untuk mencegah award berikutnya, tetapi migration tidak menghitung ulang, menambah, atau mengurangi `km_total_poin`.
- `markAsRead` tidak menurunkan transaksi selesai ke `READING`; increment `jumlah_lihat` dilakukan atomik pada satu aggregate row per dokumen.
- Dokumen tidak ada, kategori tidak ada, user tidak terautentikasi, atau dokumen tidak berhak dilihat ditolak tanpa perubahan transaksi maupun poin.

### 4. Authorization dan private file

- Guest menerima redirect/401, user tanpa hak menerima 403, owner hanya dapat mengubah dokumennya sendiri, dan approver ditentukan melalui `HRMenuAccessGroup::KNOWLEDGE_APPROVAL` yang sudah dipakai menu HR.
- Employee hanya dapat preview/download dokumen `PUBLISHED` yang lolos aturan posisi legacy; owner dan approver dapat melihat dokumen non-published sesuai tugasnya.
- Upload baru tidak pernah menulis binary dokumen ke `public/assets/image`; metadata disk, path, nama asli, MIME, size, checksum SHA-256, dan waktu migrasi tersimpan pada `km_pengajuans`.
- Migrasi file legacy bersifat idempotent: copy ke private disk, verifikasi checksum sumber/tujuan, update metadata, lalu pindahkan sumber ke private backup. Kegagalan checksum tidak mengubah row dan tidak menghapus sumber.
- Semua link dokumen pada view menggunakan route berotorisasi. Route tidak menerima path dari request, mengirim `X-Content-Type-Options: nosniff`, memakai disposition `inline` untuk preview PDF dan `attachment` untuk download.
- Route name lama dan view legacy tetap dapat dirender; file backup legacy dapat dikembalikan saat rollback.

### 5. Reject reason dan audit approval

- Form reject menolak alasan kosong/whitespace dan menampilkan pesan validasi; approve tidak mewajibkan alasan.
- Submit, approve, reject, dan deactivate berjalan dalam `DB::transaction()` dengan `lockForUpdate()` serta menulis event berisi dokumen, actor, action, status asal/tujuan, reason, dan `acted_at`.
- Reject mengembalikan dokumen dari `PENDING_APPROVAL` ke `DRAFT`; approve mengubahnya ke `PUBLISHED`; aksi pada status yang tidak valid tidak membuat event.
- Event approval tidak memiliki endpoint update/delete dan model menolak operasi update/delete, sehingga histori hanya dapat ditambah.
- Kegagalan menyimpan event membatalkan perubahan status dokumen.

### 6. Factory, test, dan readiness

- Tersedia factory untuk kategori, pengajuan, transaksi, dan approval event; test tidak bergantung pada data developer lokal.
- `KmReadingPointIdempotencyTest`, `KmAuthorizationTest`, `KmPrivateFileAccessTest`, dan `KmApprovalWorkflowTest` lulus pada MySQL testing.
- Test mencakup request paralel/race secara layak, invalid transition, reject reason, object-level access, path traversal, upload private, migrasi file idempotent, serta rollback/migrate ulang.
- `php artisan km:readiness` hanya membaca state dan mencetak PASS/WARN/FAIL untuk schema/constraint, disk private, legacy file tersisa, queue connection, tabel `jobs`/`failed_jobs`, dan scheduler deployment. Exit code non-zero hanya untuk kegagalan wajib schema/storage; opsi `--strict` juga menggagalkan WARN infra.
- Syntax check, Pint, targeted KM tests, `php artisan route:list`, dan `php artisan view:cache` lulus. Full test suite tidak menambah failure dibanding baseline yang direkam sebelum perubahan.

## Success Metrics

- **6 dari 6 fitur** selesai dalam sesi yang sama dan seluruh acceptance criteria mempunyai bukti command/test.
- **0 duplicate point award** pada replay maupun concurrent completion test.
- **100% endpoint KM read/mutation** berada di balik `auth` dan object-level policy; **0 direct document URL** baru di public web root.
- **100% perubahan status approval** memiliki event append-only yang konsisten dengan status akhir.
- **100% targeted KM tests hijau** dan **0 regression baru** terhadap baseline full suite.
- Fresh migrate, rollback satu batch, dan re-migrate berhasil pada MySQL database testing yang terverifikasi; tidak ada operasi destruktif dijalankan pada database non-testing.
