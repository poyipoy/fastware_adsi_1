# Catatan Eksekusi KM Engagement Foundation

Tanggal eksekusi: 27 Juli 2026  
Mission: `MISSION-KM-ENGAGEMENT-FOUNDATION.md`  
Implementation plan: `IMPLEMENTATION-PLAN-KM-ENGAGEMENT-FOUNDATION.md`  
Spesifikasi fase: `plan-refactor-km.md`  
Branch saat preflight: `main`

## Batas Eksekusi

- Fase 0 sudah tersedia pada commit lokal `cadb510` dan diperlakukan sebagai baseline pemulihan.
- Sesi ini mengerjakan fase 1 sampai fase 9: stabilisasi, konsistensi shell, notification center, progress membaca, insight sosial, point ledger/leaderboard, SLA approval, verifikasi, dan paket deploy.
- Instruksi pengguna mengalahkan instruksi commit/migration pada plan: tidak ada commit, push, branch baru, pull request, atau migration pada database aplikasi lokal.
- Working tree sudah memuat perubahan non-KM milik pengguna sebelum sesi. Perubahan tersebut dipertahankan dan tidak dibersihkan.
- Test database mutatif hanya dijalankan secara serial dengan `APP_ENV=testing`, driver MySQL, dan database `fastware_adsi_1_testing` yang bersuffix `_testing`.
- Empat migration baru diuji pada schema testing, tetapi statusnya pada database aplikasi lokal tetap `Pending`.

## Preflight

- Baseline `npm.cmd run build`: lulus.
- Baseline route KM: aplikasi berhasil boot dan route legacy tersedia.
- Baseline `php artisan test --filter=Km`: 205 test lulus, 2 gagal, 1.577 assertion. Dua kegagalan adalah kontrak statis lama pada `KmBladeCompatibilityTest`, bukan kegagalan runtime.
- Baseline full suite tidak dijalankan sebelum edit. Baseline proyek yang sudah terdokumentasi adalah 27 kegagalan pada `HRMenuServiceTest`, `HRRoleAccessServiceTest`, dan `JobPositionAccessServiceTest`.
- Repository berada satu commit di depan `origin/main` pada awal sesi; tidak ada sinkronisasi remote atau mutation Git dilakukan.

## Implementasi Fase 1-2

- Menghapus controller duplikat `app/Http/Controllers/1225_KmPengajuanController.php` dan dead service `app/Services/Dashboard/KnowledgeManagementDashboardService.php` setelah reference audit.
- Menghapus partial yatim `document-form.blade.php` dan `approval-actions.blade.php`.
- Menghapus `sub_kategori` yang tidak memiliki kolom schema dari fillable `KmPengajuan` dan trait `HasFactory` duplikat pada `User`.
- Menghapus titik koma yang ter-render setelah `@yield('content')` pada layout.
- Memindahkan analytics materi populer ke shell/foundation/feedback/panel KM tanpa mengubah kontrak report atau menambah runtime/CDN baru.

## Implementasi Fase 3-7

### Notification Center

- Menambahkan notification user-scoped dengan event key unik, payload allowlist, insert idempotent, persistence setelah transaction commit, unread count, mark-read scoped, dan mark-all-read.
- Event submit hanya mengirim ke allowlist approver yang eligible dan full-access; approve/reject, mention, Insight Pilihan, reminder, dan overdue memiliki event key terpisah.
- Shell KM memiliki utility bar, badge non-color-only, loading/empty/error state, refresh terkendali, dan navigasi deep-link ke dokumen/insight yang tetap melalui visibility scope server.

### Progress Membaca

- Menambahkan bitmap halaman unik, last page, active seconds, progress percent, dan continue-reading.
- Progress bersifat monoton, row-locked, replay-safe, dan delta aktif dibatasi server. Viewer hanya menghitung ketika visible dan aktif, memakai lease `localStorage` agar tab dokumen yang sama tidak menghitung bersamaan, serta tetap memiliki server-side delta cap sebagai fallback.
- Completion PDF memerlukan minimal 90 persen halaman unik dan waktu aktif `max(60, min(20 x halaman, 900))`; file non-previewable tetap memakai konfirmasi manual.
- Award completion baru menjadi flat 5 poin sesuai keputusan bisnis.

### Insight Sosial

- Menambahkan thread dua level, reaction tunggal per user, mention picker berbasis akses dokumen, edit author 30 menit, soft delete/moderation, dan maksimal tiga Insight Pilihan per dokumen.
- Reply tingkat tiga direanchor ke root. Edit mention bersifat add-only dan tidak dapat melepas mention lama secara diam-diam.
- Semua output UI dibuat melalui DOM API/escaping; notification focus membuka modal tunggal dan memprioritaskan root insight target pada pagination.
- Query listing, notification, mention, dan antrean approval memakai Form Request serta menolak query key yang tidak diizinkan.

### Point Ledger dan Leaderboard

- Menambahkan ledger append-only dengan unique event key, opening balance dari `users.km_total_poin`, snapshot departemen dari assignment aktif terbaru, dan fallback `users.section`.
- Award publish 25, completion 5, dan Insight Pilihan 10 berjalan di dalam transaction serta memperbarui cache poin atomik.
- Menambahkan `km:reconcile-points`, leaderboard global top 10, leaderboard departemen, dan masking bila cohort kurang dari lima user.

### SLA Approval

- Menambahkan umur antrean hari kerja Senin-Jumat, reminder hari kerja ke-2, overdue hari kerja ke-3, event key idempotent, sort oldest/newest, dan badge teks `Terlambat`.
- Lazy sweep memakai cache gate 15 menit agar tetap bekerja tanpa cron. Command scheduler tersedia pada hari kerja pukul 08:00; reconciliation dijadwalkan pukul 08:15.
- Sweep memakai `lazyById(200)` agar tidak memuat seluruh antrean ke memory.

## Schema yang Belum Dijalankan

Empat migration berikut adalah satu release group dan status lokalnya terkonfirmasi `Pending`:

1. `2026_07_27_130001_create_km_notifications_table.php`
2. `2026_07_27_130002_add_km_reading_progress_to_km_transaksis.php`
3. `2026_07_27_130003_extend_km_insights_social.php`
4. `2026_07_27_130004_create_km_point_ledger_table.php`

Setiap migration memiliki SQL manual pasangan. Pilih tepat satu jalur pada deployment: Laravel migration atau empat SQL manual, tidak keduanya. Urutan, preflight, verifikasi, dan rollback dijelaskan di `DAFTAR-MIGRASI-KM-ENGAGEMENT-FOUNDATION.md` dan `deploy-km/DEPLOY.md`.

## Hasil Verifikasi Final

- Targeted KM final: 230 test lulus, 1.867 assertion, 0 gagal, durasi 726,89 detik.
- Full suite final: 256 test lulus, 27 gagal, 2.076 assertion, durasi 812,53 detik.
- Seluruh 27 kegagalan identik dengan baseline proyek: 18 pada `HRMenuServiceTest`, 4 pada `HRRoleAccessServiceTest`, dan 5 pada `JobPositionAccessServiceTest`. Delta kegagalan baru adalah 0.
- Migration testing: siklus migrate, rollback batch, dan migrate ulang lulus hanya pada database `_testing`; schema parsial ditolak dan opening balance diuji idempotent.
- PHP syntax lint: 72 file change set/test lulus.
- Laravel Pint test-only: 72 file lulus.
- `npm.cmd run build`: lulus, 76 module ditransform; entry `resources/js/km/shell.js` dan worker PDF.js lokal tersedia pada manifest build. Warning `eval` berasal dari dependency PDF.js legacy yang sudah dipin, bukan source aplikasi baru.
- `php artisan route:list --path=km --json`: lulus, 31 route KM terdaftar dengan middleware auth.
- `php artisan view:cache`: lulus.
- `git diff --check`: lulus; warning line-ending hanya muncul pada file ItemCode non-KM milik pengguna.
- Manifest deploy: 63 entry, 0 missing, 0 forbidden; syntax PowerShell lulus.
- Validasi UI/UX Pro Max diterapkan pada loading feedback, focus return, target sentuh, state non-color-only, reduced-motion, dan stacking context.

`km:health`, `km:readiness`, dan `km:reconcile-points` tidak dijalankan terhadap database aplikasi lokal karena schema Engagement Foundation sengaja belum dimigrasikan. Ketiga command dan shape schema baru diuji pada database `_testing`; command tersebut wajib dijalankan pada target setelah release group schema selesai.

## Deviasi dan Limitasi

- Instruksi commit pada fase 0/8 dan instruksi migration dari plan tidak dijalankan karena pengguna secara eksplisit melarangnya.
- Skenario test baru dikelompokkan dalam enam file domain utama dan beberapa test existing, bukan sepuluh file persis seperti estimasi plan; coverage positive, validation, forbidden, invalid state, duplicate, rollback, dan race tetap tersedia.
- SLA tidak memperhitungkan hari libur nasional; hanya Senin-Jumat.
- Master organisasi belum effective-dated; snapshot memakai assignment aktif dengan ID terbaru dan fallback `users.section`.
- Unfeature tidak menarik kembali poin dan re-feature tidak memberi poin kedua, sesuai ledger recognition-only.
- Manual visual/browser smoke belum dilakukan pada sesi ini. Sebelum production sign-off tetap wajib menguji 360, 768, 1024, 1280, dan 1440 px, keyboard/focus, zoom 200 persen, reduced motion, serta persona owner/reader/approver/moderator.
- Status sebelas migration KM existing `100001` sampai `120001` di production belum diketahui. Deployment harus berhenti bila satu saja belum tersedia.

## Rollout

1. Konfirmasi target non-production/staging terlebih dahulu, backup database, dan bukti restore.
2. Audit sebelas migration existing, schema/type/orphan/duplicate, serta coverage organisasi.
3. Jalankan satu jalur schema `130001` sampai `130004` sesuai urutan dan verifikasi setiap langkah.
4. Copy file package path-preserving, termasuk seluruh `public/build`, lalu hapus dua source legacy sesuai runbook.
5. Jalankan `optimize:clear`, route check, `view:cache`, `km:health --json`, `km:readiness --json`, dan `km:reconcile-points --json`.
6. Jalankan smoke matrix fitur dan visual; hentikan aktivasi pada mandatory FAIL atau point drift.

## Rollback

1. Kembalikan application code dan seluruh bundle `public/build` ke release sebelumnya.
2. Refresh cache dan verifikasi route/smoke release lama.
3. Pertahankan schema/data additive serta ledger pada rollback code normal.
4. Rollback SQL yang menghapus data hanya boleh dilakukan setelah backup, dependency audit, dan persetujuan stakeholder terpisah.
