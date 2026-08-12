# Implementation Note KM Roadmap Prioritas 1, 3, dan 4

Tanggal implementasi: 29 Juli 2026.

## Hasil per Release

### Release 0 — Guardrail

- Upload private menerima PDF/PPT/PPTX sampai 50 MB.
- Draf Office dapat disimpan, menampilkan status pemrosesan, dan tidak dapat diajukan sebelum versi siap serta gate Office diaktifkan.
- Route download kompatibilitas tetap ada tetapi policy selalu menolak pengguna umum.
- Approval tetap terkunci untuk assignment aktif `HRGA & Legal Staff` atau user ID 91.

### Release 1 — Versioning dan Processing

- `km_pengajuans` menjadi identitas dokumen logis; `km_document_versions` menyimpan snapshot kanonis dan file per versi.
- Major revision memerlukan approval ulang; minor revision untuk metadata administratif dipublikasikan tanpa approval ulang; change note wajib.
- Backfill versi `1.0`, pointer current/published, serta relasi version pada approval event, progress, insight, dan ledger disediakan secara idempotent.
- Pipeline scheduled mencakup antivirus, normalisasi PDF, validasi, ekstraksi teks, OCR fallback, thumbnail, retry maksimal tiga kali, quarantine, dan cleanup temporary file.
- Preview pengguna hanya mengirim PDF normalisasi secara inline. Recovery original hanya untuk ability khusus, file berstatus clean + processing failed, alasan wajib, checksum, actor, waktu, dan audit.
- FULLTEXT published version mencakup judul, sinopsis, dan teks hasil ekstraksi.

### Release 2 — Interaksi dan Rekomendasi

- Reply, mention, dan reaction baru menghasilkan notifikasi idempotent sesuai aturan deduplikasi.
- Reaction diri sendiri, perubahan jenis, dan penghapusan tidak menghasilkan notifikasi; hapus lalu membuat reaction baru menghasilkan event baru.
- Dashboard memuat maksimal enam rekomendasi setelah Lanjutkan Membaca. Kandidat melewati policy/processing, exclusion pengguna, affinity kategori/tag, fallback engagement 30 hari, lalu terbaru.

### Release 3 — Organisasi, RBAC, dan Targeting

- Assignment posisi memiliki periode efektif, sumber, validasi overlap, reconciliation command, dan audit pada perubahan melalui UI HR.
- RBAC database menyediakan ability oversight, moderasi, analytics, assignment, completion override, processing recovery, export, dan access management.
- Rule approval tidak tersedia pada RBAC sehingga tidak dapat memperluas approver yang dikunci sistem.
- Audience dan target department/job position diterapkan bersama. Pengguna tanpa mapping tidak cocok dengan versi bertarget organisasi.
- Recipient materi baru disnapshot ketika publication batch dibuat; scheduler mengirim notifikasi secara idempotent tanpa queue worker.

### Release 4 — Completion, Assignment, dan Analytics

- Progress diisolasi per user-version dan reading session; active time ganda dari tab/perangkat bersamaan tidak dikreditkan.
- Completion resmi membutuhkan 90% halaman unik, active time formula yang dikunci, serta acknowledgment eksplisit.
- Assignment terikat ke satu versi, due date default 14 hari, recipient/organisasi disnapshot, dan reminder H-3/H-1/overdue H+1 idempotent.
- Manual completion hanya melalui ability khusus, alasan wajib, notification, dan completion event auditable.
- Dashboard compliance hanya menampilkan agregat; cohort department kurang dari lima tidak dirender.
- Detail XLSX/CSV hanya untuk ability export, sedangkan PDF hanya agregat. Seluruh export mencatat actor, filter, record count, nama file, dan checksum.

### Release 5 — Gamification dan HR Export

- Tier dihitung pada 50/150/300 poin untuk Bronze/Silver/Gold.
- Lima badge awal disediakan dan diberikan idempotent dari event completion, publication, dan featured insight.
- Leaderboard memakai posisi unik deterministik: poin turun, nama naik, lalu user ID.
- Recognition bersifat internal dan tidak memiliki mekanisme penukaran atau nilai ekonomi.
- Export HR tidak memuat page telemetry, active time, komentar, reaction, atau aktivitas sosial.

### Release 6 — HRIS Bersyarat

- Outbound hanya KM ke HRIS, menggunakan NPK/stable employee ID, schema JSON v1, HMAC signature, idempotency key, retry, dan reconciliation report.
- Sinkronisasi tetap disabled secara default dan scheduler hanya aktif bila konfigurasi utama serta seluruh enam gate bernilai benar.
- Payload tidak mengandung performance score dan tidak mengubah modul penilaian HR.

## Command Operasional

```text
km:document-capabilities --json
km:process-pending-documents --limit=1
km:cleanup-temporary-files
km:dispatch-publication-notifications --limit=5
km:send-assignment-reminders
km:reconcile-organization
km:sync-hris --limit=50
km:hris-reconciliation --json
km:health --json
km:readiness --json
```

Semua proses berat/batch memakai Artisan command berumur pendek melalui Laravel Scheduler. Tidak ada queue worker, Horizon, Redis queue, service worker, atau cache dokumen offline.

## Check Deployment Tambahan

- Pastikan satu cron Laravel Scheduler aktif di production dan verifikasi command pemrosesan, notifikasi publikasi, serta reminder benar-benar dieksekusi. Tanpa scheduler, proses tersebut hanya berjalan ketika administrator menjalankan command secara manual.
- Viewer masih menggunakan build PDF.js yang ada. Build frontend dapat melaporkan penggunaan `eval`; periksa Content Security Policy production agar preview PDF tidak terblokir. Upgrade PDF.js tidak termasuk dalam perbaikan assignment versi ini.
- Setelah deploy, jalankan `km:health --json` dan `km:readiness --json`; assignment posisi aktif dengan `effective_from` kosong merupakan readiness failure.

## Aktivasi Bertahap

1. Deploy code dengan processing, Office submission, dan HRIS tetap disabled.
2. Jalankan empat migration sesuai daftar terpisah setelah backup dan persetujuan.
3. Jalankan health/readiness dan reconciliation organisasi.
4. Pasang/validasi binary pada staging, lalu aktifkan document processing.
5. Buktikan end-to-end PPT/PPTX pada staging sebelum membuka Office submission.
6. Aktifkan assignment/compliance setelah target organisasi direkonsiliasi.
7. HRIS tetap ditutup sampai gate dua-release, defect, idempotency, reconciliation 99,5%, exception, dan sandbox dipenuhi.

## Status Sesi

- Migration lokal `dms_adasi_rev1`: `140001`–`140004` dijalankan serial melalui `--path` dan tercatat pada batch 51–54.
- Data-repair assignment posisi `160001` dijalankan melalui `--path` pada 2 Agustus 2026 dan tercatat pada batch 57; sisa assignment aktif tanpa `effective_from`: 0.
- Backup sebelum migration: `storage/app/backups/km-roadmap/dms_adasi_rev1-before-km-140-20260729-163946.sql` dengan SHA-256 `2E8833B43F323171128E8DA15A2C278861224A8323E7489450B4A5B69C888218`.
- Commit Git: tidak dibuat.
- Push Git: tidak dilakukan.
- Capability binary staging dan cron production: memerlukan verifikasi operator.
- QA visual lintas perangkat: tetap menjadi gate manual bila browser runtime tidak tersedia.
