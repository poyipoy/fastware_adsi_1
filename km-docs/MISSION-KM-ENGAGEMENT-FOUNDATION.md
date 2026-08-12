# MISSION: KM Engagement Foundation

## Goal

Menyelesaikan paket lanjutan Knowledge Management setelah pemulihan fase 0: stabilisasi source, konsistensi shell KM, notifikasi in-app, progress membaca dan completion resmi, insight sosial, ledger poin dan leaderboard departemen, serta reminder SLA approval. Mission ini membuka keputusan KM-DEC-003 sampai KM-DEC-009 yang telah disetujui pada 20 Juli 2026 tanpa memasukkan fitur keputusan lain yang masih memiliki execution gate terpisah.

Detail teknis fase, nama file, kontrak route, schema, dan verifikasi mengikuti `plan-refactor-km.md` serta `km-docs/IMPLEMENTATION-PLAN-KM-ENGAGEMENT-FOUNDATION.md`.

## Prasyarat

- Pemulihan fase 0 tersedia pada commit lokal `cadb510`; file hasil pemulihan tetap menjadi baseline dan tidak dipindahkan kembali dari Recycle Bin.
- Fondasi mission Jangka Pendek, Jangka Menengah, Jangka Panjang, dan Design Foundation tetap dipertahankan sebagai compatibility surface.
- `km-docs/APPROVED-DECISIONS-KM.md` adalah sumber keputusan bisnis; `km-docs/PENDING-DECISIONS-KM.md` tetap berfungsi sebagai catatan gate historis, bukan backlog.
- Implementasi tidak memakai queue worker, Horizon, Redis queue, Supervisor worker, atau daemon persisten.
- Production deployment tetap blocked sampai preflight schema, backup, infrastructure/security gate, dan smoke test yang relevan diselesaikan.

## Scope

1. Menghapus source KM yang terbukti yatim/duplikat dan membersihkan anomali model/layout yang tercantum pada fase 1-2.
2. Menambahkan notification center in-app yang private, user-scoped, idempotent, dan terhubung ke event approval/mention/featured insight/reminder.
3. Menambahkan progress membaca PDF yang monoton dan completion resmi yang memerlukan 90% halaman unik, waktu aktif minimum, serta konfirmasi eksplisit.
4. Menambahkan insight maksimal dua level, satu reaction aktif per user, mention melalui user picker, soft delete/moderation, dan maksimal tiga Insight Pilihan per dokumen.
5. Menambahkan append-only point ledger, opening balance, award idempotent, reconciliation, serta leaderboard global/departemen dengan minimum cohort lima user.
6. Menambahkan reminder SLA approval idempotent, lazy sweep tanpa cron, command scheduler opsional, umur antrean, status terlambat, dan sort server-side.
7. Menyediakan migration Laravel additive, SQL manual pasangan, daftar urutan migration tertunda, deployment manifest, rollout note, dan rollback note tanpa mengeksekusinya pada database aplikasi lokal dalam mission ini.

## Out of Scope

- Versioning dokumen dan approval ulang KM-DEC-001.
- Pipeline konversi PPT/PPTX, OCR, antivirus, atau full-text isi dokumen KM-DEC-002.
- Assignment/compliance KM-DEC-010.
- PWA/offline KM-DEC-011.
- Export atau integrasi HR KM-DEC-012.
- Badge/tier, reward ekonomi, abuse-report queue penuh, master organisasi effective-dated, atau retention cleanup otomatis.
- Perubahan non-KM, commit, push, branch, atau pull request.

## Acceptance Criteria

### Stabilization dan compatibility

- Hanya file fase 1 yang telah dibuktikan tidak memiliki runtime reference yang dihapus.
- Route/view legacy `dsKnowlege`, `pengajuanKM`, `persetujuanKM`, `km.documents.preview`, dan `km.documents.download` tetap tersedia.
- Halaman analytics popular memakai shell dan foundation KM tanpa mengubah makna laporan.

### Security dan data integrity

- Semua read/mutation endpoint baru berada di group `auth`, melakukan policy/ownership check yang sesuai, memvalidasi payload, dan tidak mengandalkan visibilitas menu.
- Business event replay-sensitive memakai transaction, row lock bila diperlukan, dan unique event key database.
- Notification dan point ledger tidak menduplikasi event ketika request/command diulang.
- Private document path dan telemetry detail tidak terekspos melalui payload notification atau HTML.

### Feature behavior

- Approval menghasilkan notifikasi recipient yang benar; read/unread hanya dapat dimutasi oleh pemilik notifikasi.
- Progress membaca bersifat monoton/idempotent, berhenti menghitung saat inactive, dan tidak dapat melewati completion gate server-side.
- Thread, reaction, mention, edit/delete window, moderation, featured limit, dan rate limit mengikuti KM-DEC-007.
- Award completion 5 poin, publish 25 poin, dan Insight Pilihan 10 poin tersimpan pada append-only ledger dan sinkron dengan cache `users.km_total_poin`.
- Reminder 2/3 hari kerja, badge overdue, dan pengurutan antrean approval bekerja tanpa notification duplicate.

### Delivery dan verification

- Empat migration group `130001` sampai `130004` memiliki migration Laravel reversible dan SQL manual yang menyertakan preflight, verification, migration-table guard, serta rollback terkomentar.
- Tidak ada migration yang dijalankan pada database aplikasi lokal selama sesi ini; daftar eksekusi migration diserahkan untuk dijalankan kemudian.
- Targeted positive, validation, unauthenticated/forbidden, invalid state, duplicate/idempotency, dan race-sensitive test tersedia sesuai risiko.
- PHP lint, Pint pada change set, JS syntax/build, route inspection, Blade cache, dan targeted KM tests lulus pada database bernama `*_testing`; full-suite delta dilaporkan apa adanya.
- `deploy-km/DEPLOY.md` dan execution note menjelaskan preflight, urutan SQL/copy, prerequisite scheduler, smoke test, rollout, dan rollback.

