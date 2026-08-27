# Rencana: Pemulihan + Refactor KM — Paket Jangka Menengah

## Konteks

Laporan "Usulan Pengembangan Menu KM" (before/after, 8 area) sebagian besar **sudah diimplementasikan** (fase jangka pendek + panjang + design foundation, terdokumentasi di `km-docs/`), dan seluruh 14 keputusan bisnis sudah **Approved 20 Jul 2026** (`km-docs/APPROVED-DECISIONS-KM.md`) — termasuk amendemen **tanpa queue worker** (sinkron / scheduler / manual).

**Insiden**: hari ini 26 Jul 19:02–19:21, ±25 file inti KM terhapus ke Recycle Bin (11 service, 12 form request, policy, config, 5 command, factories, tests, sumber CSS/JS/komponen blade, prototypes). File tersebut belum pernah di-commit → modul KM sekarang 500 total, `php artisan migrate` fatal, `npm run build` gagal. **Recycle Bin adalah satu-satunya sumber pemulihan — jangan dikosongkan.**

**Keputusan user**: (1) pulihkan dari Recycle Bin, arsitektur tetap; (2) scope = paket jangka menengah penuh (notifikasi in-app + progress granular + threading/reaksi insight + leaderboard departemen + reminder SLA); (3) desain: konsistensi KM saja, tanpa redesign; (4) deploy prod = copy file manual **tanpa artisan** → schema dikirim sebagai SQL manual (konvensi sudah ada: `database/migrations/2026_07_24_000001_add_cancellation_fields_to_item_codes.sql`).

Fitur yang belum dibangun tetap ikut gate approved: yang di luar scope ini (versioning, compliance/assignment, full-text isi dokumen, HR export, PWA) → misi terpisah.

---

## Fase 0 — Pemulihan dari Recycle Bin (COPY, bukan move — bin tetap jadi backup)

Copy isi path bin → lokasi asli (semua di `C:\$Recycle.Bin\S-1-5-21-2638376934-1289364086-3566825602-1001\`):

| Bin | Tujuan |
|---|---|
| `$RCDGF6D\*` | `app/Services/KnowledgeManagement/` (11 service) |
| `$RVP0KCR\*` | `app/Http/Requests/KnowledgeManagement/` (12 request) |
| `$RS8U04Q\*` | `app/Policies/` (KmPengajuanPolicy) |
| `$RXJCR62.php` | `config/knowledge_management.php` |
| `$R0PXE7A.php` | `app/Console/Commands/MigrateKmFilesToPrivateStorageCommand.php` |
| `$R8RZFV6.php` | `app/Console/Commands/KmReadinessCommand.php` |
| `$RDRHYSQ.php` | `app/Console/Commands/KmHealthCommand.php` |
| `$RMCJWNX.php` | `app/Console/Commands/RepairKmSchemaCommand.php` |
| `$RRWFMVF.php` | `app/Console/Commands/AuditKmSchemaCommand.php` |
| `$RCIN40T.php`/`$RL3YSLI.php`/`$RQJNDKJ.php`/`$RUNR038.php`/`$RY95PDQ.php` | `database/factories/` (KmTag/KmKategori/KmTransaksi/KmPengajuan/KmApprovalEvent Factory) |
| `$RR77ZX8\*` | `resources/js/km/` (7 file: dashboard, pdf-viewer, bookmarks, ui-feedback, authoring, approval, draft-autosave) |
| `$RBAD8NL\*` | `resources/css/km/` (foundation, dashboard, authoring) |
| `$R1X5EWO\*` | `resources/views/components/km/` (6 komponen) |
| `$R9Y4V3E\*` | `tests/Feature/KnowledgeManagement/` (20 test + KmTestCase) |
| `$RDPY5AI\*` | `tests/Support/` (RunsKmWorkers) |
| `$R30SZA1\*` | `km-docs/prototypes/` |

Verifikasi boot: `php artisan route:list` OK → `php artisan test --filter=Km` hijau (butuh DB `*_testing` sesuai phpunit.xml) → `npm run build` hijau → smoke 4 halaman KM.
**Langsung `git add` + commit semua file KM** (pelajaran insiden: file untracked = tidak terlindungi). Commit kedua setelah refactor selesai.

## Fase 1 — Stabilisasi & pembersihan (KM saja)

- Hapus `app/Http/Controllers/1225_KmPengajuanController.php` (duplikat nama kelas PSR-4; referensi historis ada di git/docs).
- Hapus `app/Services/Dashboard/KnowledgeManagementDashboardService.php` (dead code; digantikan KmDashboardQueryService).
- Hapus partial yatim `resources/views/knowlege_management/partials/{document-form,approval-actions}.blade.php` (+ folder partials kosong) — nol referensi.
- `app/Models/KmPengajuan.php`: hapus `sub_kategori` dari `$fillable` (kolomnya tidak pernah ada).
- `app/Models/User.php`: hapus duplikat `use HasFactory;`.

## Fase 2 — Konsistensi desain (tanpa perubahan look)

- `resources/views/layout.blade.php:1876`: `@yield('content');` → `@yield('content')` (hapus `;` yang ter-render di semua halaman).
- Migrasi `resources/views/knowlege_management/analytics/popular.blade.php` ke shell KM: bungkus `<x-km.shell active="popular">` + `<x-km.page-header>`, `@section('documentLanguage','id')`, ganti `@vite dashboard.css` (tanpa foundation) → push `foundation.css`, buang `role="note"` (pakai `.km-operational-note` yang sudah ada), error via `<x-km.feedback>`, kartu → `.km-panel`. Halaman ini otomatis dapat lonceng notifikasi dari shell.

## Fase 3 — Notifikasi in-app (KM-DEC-006)

**Schema** (migrasi `2026_07_27_130001_create_km_notifications_table.php` + SQL kembar):
`km_notifications`: id BIGINT PK, user_id FK users CASCADE, type VARCHAR(48), event_key VARCHAR(191) **UNIQUE**, data JSON (hanya id + judul singkat), read_at NULL, created_at; index `(user_id, read_at, id)`; tanpa updated_at.

**Backend**:
- BARU `app/Services/KnowledgeManagement/KmNotificationService.php`: `record()` (insertOrIgnore; jika dalam transaksi → `DB::afterCommit`; gagal → `report()`), `recordMany()`, `paginateFor()`, `unreadCount()`, `markRead()` (scoped user), `markAllRead()`.
- BARU `app/Models/KmNotification.php` (UPDATED_AT null, casts data/read_at).
- BARU `app/Http/Controllers/KnowledgeManagement/KmNotificationController.php`: `index` (JSON paginated + unread_count), `markRead`, `markAllRead` (ownership via WHERE user_id, tanpa policy).
- EXTEND `KmApprovalService::applyLockedAction()`: SUBMITTED → notif ke approver eligible (key `submitted:{doc}:{eventId}:u{uid}`); APPROVED/REJECTED → notif owner (`approved|rejected:{doc}:{eventId}:u{owner}`, reject bawa reason).
- EXTEND `KmAccessService`: `eligibleApproverIds()` = allowlist `HRMenuAccessGroup::KNOWLEDGE_APPROVAL` + full-access (id 1, 91), **bukan** semua head (lihat Flag #3).
- Routes (`routes/web.php`, dalam grup auth, gaya `km.*`): GET `/km/notifications` (`km.notifications.index`), POST `/km/notifications/{id}/read`, POST `/km/notifications/read-all`.

**Frontend**:
- BARU `resources/js/km/shell.js` (**satu-satunya entry Vite baru**; daftarkan di `vite.config.js`): fetch sekali saat load + refetch saat dropdown dibuka >60 dtk, render list programatik, klik item → mark-read (keepalive) → navigate, "Tandai semua dibaca", empty state; pola fetch/CSRF/AbortController sama dgn ui-feedback.
- EXTEND `resources/views/components/km/shell.blade.php`: bar `.km-shell-utility` berisi tombol lonceng (badge unread + sr-text) + dropdown `.km-notification-menu`; blok `window.kmShellConfig` (template URL `__KM_ID__`); `@push('scripts') @vite('resources/js/km/shell.js')`.
- EXTEND `resources/css/km/foundation.css`: kelas `.km-shell-utility`, `.km-notification-*`, `.km-status--overdue`, `.km-sort-link` (semua pakai token `--km-*`, target sentuh 44px, unread ≠ warna-saja).

## Fase 4 — Progress granular + lanjutkan membaca (KM-DEC-004, tier progress)

**Schema** (`..._130002_add_km_reading_progress_to_km_transaksis.php` + SQL): ALTER `km_transaksis` ADD `last_page`, `pages_total`, `unique_pages` TEXT (bitmap base64, LSB=hal 1), `unique_pages_count`, `active_seconds`, `progress_percent`, `last_progress_at`; index `(id_user, status, last_progress_at)`.

**Backend**:
- EXTEND `KmReadingService`: `updateProgress()` — koreografi lock sama `markStarted()`; union bitmap idempotent/monoton, `last_page=max`, delta `active_seconds` di-cap (config 120 dtk), no-op bila sudah COMPLETED; balikan state + `completion_eligible`. **Ubah `complete()`**: untuk file previewable (PDF) wajib `unique_pages_count ≥ ceil(0.9×pages_total)` DAN `active_seconds ≥ max(60, min(20×pages, 900))` → selain itu `DomainException` (controller sudah memetakan ke 422); non-previewable (PPT) tetap konfirmasi manual. Poin completion → flat 5 via ledger (Fase 6, Flag #1).
- BARU request `UpdateKmReadingProgressRequest` (extends KmDocumentInteractionRequest; rules last_page/pages_total/pages[]≤200/active_delta 0..600, pesan Indonesia).
- EXTEND `KmPengajuanController`: action `updateProgress`. EXTEND `KmDashboardQueryService::dashboardReferences()`: + `continueReading` (3 transaksi READING terbaru). Route: PATCH `/km/documents/{kmPengajuan}/progress` (`km.reading.progress`).
- EXTEND `config/knowledge_management.php`: blok `points`, `reading`, `insights`, `approval_sla` (default hardcoded, tanpa env baru).

**Frontend**:
- EXTEND `resources/js/km/pdf-viewer.js`: sesi progres (Set halaman, timer aktif 1 dtk hanya saat `visibilityState==='visible'` && interaksi <60 dtk — aman multi-tab), flush PATCH tiap ~12 dtk / ganti halaman / tutup (`keepalive`), resume otomatis ke `data-resume-page` + toast "Melanjutkan dari halaman X", label toolbar `74% · Halaman 18 dari 24`, tombol `#km-viewer-complete` disabled + `aria-describedby` hint sisa syarat, enable saat server bilang eligible; sinkronkan kartu tanpa reload.
- EXTEND `resources/js/km/dashboard.js`: wording konfirmasi selesai per keputusan: "Saya telah membaca dan memahami dokumen ini."
- EXTEND `resources/views/dashboard/dsKnowlege.blade.php`: kartu ganti teks status → `.km-progress` (role="progressbar", label persen + halaman), tombol Buka/Lanjutkan bawa `data-resume-page` dst; `window.kmConfig` + template URL baru.
- EXTEND `resources/css/km/dashboard.css`: `.km-progress*`, `.km-viewer-progress` (spec UX dari `ANALISIS-UX-DASHBOARD-PDF-VIEWER-KM.md` §1.3).

## Fase 5 — Insight: threading, reaksi, mention, Insight Pilihan (KM-DEC-007 subset)

**Schema** (`..._130003_extend_km_insights_social.php` + SQL):
- ALTER `km_insights` ADD `parent_id` INT NULL self-FK CASCADE, `edited_at`, `deleted_at`, `deleted_by` FK SET NULL, `delete_reason` VARCHAR(500), `featured_at`, `featured_by` FK SET NULL; index `(id_km_pengajuan,parent_id,id)` dan `(id_km_pengajuan,featured_at)`.
- BARU `km_insight_reactions` (insight_id INT FK, user_id FK, reaction VARCHAR(16) CHECK in helpful|insightful|agree, **UNIQUE(insight_id,user_id)**).
- BARU `km_insight_mentions` (insight_id, mentioned_user_id, **UNIQUE pair**).

**Backend**:
- EXTEND `Insight` model: SoftDeletes, relasi parent/replies/reactions/mentionedUsers/featuredBy, `isEditableBy()` (jendela 30 mnt). BARU `KmInsightReaction` model.
- EXTEND `KmInteractionService`: `addInsight()` (+parent re-anchor ke root agar depth ≤2, mention ≤10 tervalidasi akses dokumen, notif `mention:{insight}:u{uid}`), `editInsight()` (author ≤30 mnt, mention add-only), `deleteInsight()` (author ≤30 mnt ATAU ability moderator + reason wajib; soft delete, clear featured), `react()/unreact()` (upsert unique, tanpa notif sesuai matrix), `feature()/unfeature()` (mutex = lock baris dokumen; max 3; +10 poin ledger `selected_insight:{insight}:{author}` + notif; unfeature tidak menarik poin — Flag #4), `listInsights()` (root+reply, count reaksi + reaksi caller, yang terhapus dimask kecuali moderator, flag izin per baris), `mentionOptions()` (pola LIKE-escape KmCoAuthorOptionsController).
- EXTEND `KmPengajuanPolicy`: ability `moderateInsights` (canApprove || full access), `featureInsight` (owner || moderator), `comment`.
- EXTEND `AddKmInsightRequest`: `parent_id`, `mention_ids[]≤10`. BARU `KmInsightActionRequest` (react/feature/delete/edit).
- Rate limiter (RouteServiceProvider): `km-comments` 10/10mnt, `km-reactions` 30/mnt. Routes: GET insights + mention-options (doc-scoped), PATCH/DELETE `/km/insights/{insight}`, PUT/DELETE `.../reaction`, POST/DELETE `.../feature`; `insights.add` lama tetap + throttle.

**Frontend**:
- **Hapus 48 modal insight per kartu** di dsKnowlege (blok `#insightModal{{id}}`) → SATU modal global `#km-insight-modal` (loading/empty state, list, "Muat lebih banyak", composer + tombol `@` mention picker meniru pola co-author picker di authoring.js).
- BARU `resources/js/km/insights.js` (modul non-entry, diimpor dashboard.js — tidak menambah entry Vite): render thread 2 level, chip reaksi label Indonesia (Membantu/Insightful/Setuju) dgn `aria-pressed`, balas/edit/hapus/feature sesuai flag izin, badge "Insight Pilihan · aktor · waktu", placeholder "Insight telah dihapus.", fokus kembali ke trigger saat tutup.
- EXTEND `dashboard.css`: `.km-insight-*`, `.km-reaction`, `.km-featured-badge`.

## Fase 6 — Ledger poin + leaderboard departemen (KM-DEC-008 + 005 fallback)

**Schema** (`..._130004_create_km_point_ledger_table.php` + SQL): `km_point_ledger` (user_id FK RESTRICT, event_type, event_key **UNIQUE**, points SIGNED, department_snapshot VARCHAR(255) NULL, km_pengajuan_id/km_insight_id FK SET NULL, notes, created_by, created_at; append-only). + INSERT opening balance idempotent per user `km_total_poin>0` (`opening_balance:{user}`, snapshot dept = posisi aktif `user_job_positions→mst_job_positions→mst_departments.name`, fallback `users.section` — fallback ini approved KM-DEC-005).

**Backend**:
- BARU `KmPointLedgerService`: `award()` (wajib dalam transaksi; catch duplicate key → false; update `users.km_total_poin` atomik — pola persis `KmReadingService`), `departmentSnapshotFor()`, `reconcile()`, `departmentLeaderboard(topN, minCohort=5)` (mask `insufficient cohort` per KM-DEC-009). BARU model `KmPointLedgerEntry` (append-only guard ala KmApprovalEvent).
- Hook award (semua dalam transaksi pemanggil): completion flat **5** (`completion:{user}:{doc}`), publish **+25** ke owner saat approve (`published:{doc}:{owner}` — idempotent walau reject→resubmit→approve), Insight Pilihan **+10**.
- BARU command `km:reconcile-points`. EXTEND `User` model relasi.
- Leaderboard: server-render dua daftar di dsKnowlege — Global (top 10, seperti sekarang) + "Departemen saya" (dari ledger by snapshot) — **tanpa endpoint baru**; toggle client-side.

**Frontend**: strip leaderboard dsKnowlege dibungkus fieldset radio `btn-check` "Global | Departemen saya" + live region; tanpa fetch.

## Fase 7 — Reminder SLA approval (KM-DEC-003)

- EXTEND `KmApprovalService`: `workingDaysSince()` (Sen–Jum, tanpa kalender libur — limitasi dicatat), `generateDueReminders()` idempotent: pending ≥2 hari kerja → notif `approval_reminder:{doc}:{submitEventId}:u{approver}`; ≥3 → `approval_overdue:...` (dedup by event_key, aman diulang).
- Pemicu ganda: (a) **lazy sweep** di `KmPengajuanController::persetujuanKM()` — pre-check EXISTS murah + gate `Cache::add` 15 mnt (jalan tanpa cron, sesuai prod tanpa artisan); (b) BARU command `km:send-approval-reminders` + jadwal di `Kernel.php` (`weekdays 08:00, withoutOverlapping`) untuk lingkungan ber-cron.
- EXTEND `KmDocumentQueryService::paginateApprovals()`: subquery `pending_since` (MAX acted_at event submitted) → kolom "Menunggu" + badge `Terlambat` (`.km-status--overdue`, teks bukan warna-saja) di persetujuanKM; sort default oldest-first + toggle via link header (`aria-sort`), murni server-side.

## Fase 8 — Build, test, commit

- `npm run build` → manifest baru (entry `shell.js` muncul; copy seluruh `public/build/` ke prod nanti).
- Update test yang terdampak: `KmTestCase::rebuildKmTestSchema()` + 4 migrasi baru & drop-list tabel baru; `KmReadingPointIdempotencyTest` asersi poin → flat 5.
- Test baru (±10 file di `tests/Feature/KnowledgeManagement/`): KmNotificationTest, KmApprovalNotificationTest, KmReadingProgressTest, KmGatedCompletionTest, KmPointLedgerTest, KmInsightThreadingTest, KmInsightReactionAndMentionTest, KmInsightFeatureTest, KmApprovalSlaReminderTest, KmDepartmentLeaderboardTest (detail asersi ada di catatan perancang; pakai RunsKmWorkers utk race).
- `php artisan test --filter=Km` hijau penuh → commit final.

## Fase 9 — Paket deploy prod (tanpa artisan)

- 4 file SQL kembar di `database/migrations/` meniru template item_codes: header backup-warning → pre-check `information_schema` (MySQL tak punya ADD COLUMN IF NOT EXISTS) → DDL → `INSERT INTO migrations` guarded (agar deploy artisan di masa depan konsisten) → SELECT verifikasi (mis. `SUM(ledger)=km_total_poin` harus 0 selisih) → rollback ter-comment. Urutan 130001→130004.
- **Cek dulu `DAFTAR-PERUBAHAN-FILE-DATABASE-KM-JANGKA-PENDEK.md`**: bila prod belum pernah menerima 11 migrasi KM existing (100001..120001), siapkan juga SQL manualnya (satu file konsolidasi) — konfirmasi status prod ke user saat eksekusi.
- BARU `deploy-km/DEPLOY.md` (Bahasa Indonesia): daftar manifest file lengkap (app/, config/, resources/views (blade ter-compile tidak perlu), `public/build/**` utuh, routes, dll), urutan langkah: backup DB → jalankan SQL → copy file → hapus `bootstrap/cache/{config.php,routes-v7.php}` bila ada → set `.env` prod (`KM_PDF_THUMBNAIL_ENABLED`, `KM_PDFTOPPM_BINARY` path prod, `KM_DISK`) → copy `storage/bin/poppler/` bila belum ada → smoke test.
- BARU `deploy-km/make-deploy-zip.ps1`: zip otomatis dari daftar manifest (baseline git arg).
- Tulis `km-docs/EXECUTION-NOTE-KM-JANGKA-MENENGAH.md` (konvensi governance proyek): apa yang dibangun, deviasi, limitasi.

## Verifikasi end-to-end

1. Suite: `php artisan test --filter=Km` (lokal, DB testing).
2. Jalankan app (Laragon/`php artisan serve`), smoke per fitur: submit→lonceng approver→approve→notif owner + poin +25 di ledger; buka PDF→progress naik→tombol selesai enable di ≥90%+waktu→selesai = +5 & satu baris ledger; reply 2 level + reaksi ganti-ganti (1 aktif) + mention → notif; feature 3 max, ke-4 ditolak 422; leaderboard toggle Global/Departemen; antrean approval menua >3 hari kerja → badge Terlambat + notif reminder sekali saja (ulang buka halaman tidak dobel); halaman Materi Populer tampil dengan shell + lonceng.
3. `SELECT` reconcile: `km:reconcile-points` 0 drift.
4. Regression non-KM ringan: halaman ItemCode approval & dashboard lain masih normal (perubahan global hanya `;` di layout).

## Flag — perubahan perilaku yang perlu disadari (sudah sesuai keputusan approved, tapi terlihat oleh user)

1. **Poin completion jadi flat 5** (katalog KM-DEC-008) menggantikan `poin_kategori` (mis. 25). Kolom `poin_kategori` tetap ada tapi tidak dipakai award baru.
2. **Completion PDF di-gate** (≥90% halaman + waktu aktif minimum). Pembaca yang sedang "READING" tanpa progress harus mengakumulasi dulu; tanpa masa tenggang.
3. **Penerima notif pengajuan dipersempit** ke allowlist KNOWLEDGE_APPROVAL + full-access (bukan semua Dept/Sec Head yang secara teknis bisa approve) — interpretasi matrix "HRGA Staff approver"; para head tetap bisa membuka antrean.
4. **Unfeature tidak menarik poin +10** (ledger append-only, recognition-only; re-feature tidak dobel karena event key). Audit unfeature hanya last-state; tabel audit penuh opsional bila diminta.
5. SLA hari kerja = Sen–Jum tanpa kalender libur nasional.
6. `user_job_positions` tidak effective-dated → snapshot departemen diambil saat award (fallback `users.section`), sesuai fallback yang diizinkan KM-DEC-005.

## Di luar scope (misi berikutnya, gate approved)

Versioning dokumen (KM-DEC-001), assignment/compliance (010), full-text isi dokumen + pipeline PPT/OCR (002), export HR Fase A (012), badge/tier (008 lanjutan), abuse report & moderation queue penuh (007 lanjutan), master organisasi effective-dated (005), pembersihan ±45 controller backup non-KM.

> Setelah implementasi: jalankan `/code-review ultra` (alias lama `/ultrareview`) untuk review cloud multi-agent — hanya bisa dipicu oleh Anda, bukan saya — dan `/security-review` untuk perubahan yang menyentuh file privat/akses.
