# Implementation Plan — KM Design Foundation

## Referensi dan Guardrail

Plan ini menjalankan `MISSION-KM-DESIGN-FOUNDATION.md` dengan referensi visual `UX-REDESIGN-KM-ENTERPRISE.md`. `PENDING-DECISIONS-KM.md` tetap merupakan decision gate dan tidak menjadi scope implementasi.

Mission ini tidak memiliki migration/model/controller/service/route work. Urutan AGENTS tetap diikuti dengan menandai lapisan tersebut sebagai **no change required**, bukan melewatinya secara diam-diam.

## Tahap 1 — Preflight

1. Rekam working tree dan jangan mengubah file yang tidak terkait mission.
2. Verifikasi empat view aktif, layout hook, Vite inputs, aset Bootstrap Icons lokal, serta ID/field/route yang menjadi compatibility surface.
3. Verifikasi enum empat status dan policy ability yang akan dipakai navigasi.
4. Rekam baseline `km:health` dan `km:readiness` secara serial.
5. Pastikan tidak ada kebutuhan schema/data; migration, rollback DB, dan data cleanup ditandai tidak berlaku.

## Tahap 2 — Fondasi Visual dan Asset

1. Tambahkan `resources/css/km/foundation.css` sebagai Vite entry.
2. Definisikan token primitive → semantic → component untuk warna, type, spacing, radius, shadow, focus, motion, dan z-index.
3. Scope style komponen di bawah `.km-app`, gunakan system font serta aset lokal.
4. Tambahkan responsive workspace, sidebar/offcanvas, table overflow, minimum target 44 px, visible focus, skip link, loading/empty/error, dan reduced motion.
5. Tambahkan input CSS ke `vite.config.js`; tidak ada dependency baru.

## Tahap 3 — Komponen Blade dan Feedback

1. Tambahkan hook `@yield('documentLanguage', 'en')` pada elemen `<html>` layout global.
2. Buat komponen anonymous Blade:
   - `km.shell`
   - `km.page-header`
   - `km.status-badge`
   - `km.feedback`
   - `km.empty-state`
   - `km.loading-state`
3. Shell menyediakan skip link, policy-aware navigation, active state, offcanvas responsif, main landmark, confirmation modal, dan toast live region.
4. Tambahkan `resources/js/km/ui-feedback.js` dengan export `confirmAction()` dan `notify()`.
5. Integrasikan modul feedback ke JS dashboard/authoring/approval sambil mempertahankan fallback form server-side.

## Tahap 4 — Migrasi Empat Surface

### Library/Dashboard

- Bungkus konten dalam shell dan page header.
- Jadikan search kontrol utama; pindahkan filter lainnya ke `<details>` disclosure tanpa mengganti nama query/value.
- Pertahankan pagination, private preview/download, bookmark, like, insight, completion, leaderboard, dan ID hook JS.
- Gunakan komponen status/empty/error dan semantics tabel/card yang aksesibel.

### Pengajuan Saya

- Bungkus konten dalam shell dan perjelas hierarchy daftar/status/action.
- Pertahankan modal create/edit, field names, DOM IDs, upload privat, tag, co-author, autosave, dan fallback submit/deactivate.
- Gunakan komponen status/feedback/empty dan busy state.

### Persetujuan

- Bungkus konten dalam shell dan redesign bulk toolbar/table/history/decision modal.
- Pertahankan satu tahap approval, kategori per dokumen, reject reason wajib, selection payload, single action, dan all-or-nothing backend flow.
- Ganti blocking browser dialog dengan module feedback dan double-submit guard.

### Materi Populer

- Bungkus konten dalam shell dan terapkan page/table/filter/pagination baru.
- Pertahankan label data operasional bukan KPI, query filter, pagination, serta export XLSX/PDF.

## Tahap 5 — Test dan Static Contract

1. Tambahkan `KmDesignFoundationTest` untuk:
   - Vite entry dan scoped token CSS;
   - hook bahasa dan empat shell surface;
   - policy-aware navigation;
   - pemetaan empat status;
   - local asset/no CDN/no inline handler;
   - semantics aksesibilitas dan reduced motion;
   - import feedback module serta larangan `window.alert`/`window.confirm`.
2. Perbarui `KmBladeCompatibilityTest` tanpa melemahkan kontrak route, field, ID, private file, dan no-JS fallback.
3. Jalankan regresi feature terkait filter/pagination, file privat, engagement, authoring/autosave, single/bulk approval, validation, analytics, dan export.

## Tahap 6 — Verifikasi Serial

1. PHP lint untuk setiap file PHP/Blade yang berubah secara proporsional.
2. `vendor/bin/pint --test` untuk scope PHP mission.
3. Sebelum test database, buktikan `APP_ENV=testing` dan database target berakhiran `_testing`.
4. Jalankan `KmDesignFoundationTest`, `KmBladeCompatibilityTest`, lalu seluruh KM suite secara serial.
5. Jalankan route list KM dan knowledge-management.
6. Jalankan `php artisan view:cache`.
7. Jalankan Vite production build.
8. Jalankan `km:health --json` dan `km:readiness --json` secara serial, lalu bandingkan dengan baseline 10 PASS/2 WARN/0 FAIL.
9. Jalankan `php artisan optimize:clear` setelah verifikasi.

## Tahap 7 — QA dan Handoff

1. Uji atau siapkan matriks sign-off pada 360, 768, 1024, 1280, dan 1440 px.
2. Cakup keyboard-only, focus restoration, zoom 200%, reduced motion, dan tiga persona: employee, contributor, approver.
3. Catat screenshot staging sebagai artefak UAT; bila browser otomatis tidak tersedia, jangan mengklaim QA visual lulus.
4. Buat execution note berisi file changes, evidence test/build/readiness, warning operasional, QA gap, rollout, dan rollback.

## Risiko dan Mitigasi

- **Dirty working tree:** edit hanya file yang terinventarisasi; review diff per file dan hindari reset.
- **Legacy DOM/route contract:** pertahankan field, ID, route, query, dan fallback; lindungi dengan source/feature tests.
- **Bootstrap global legacy:** scope CSS di `.km-app`; cleanup global di luar mission.
- **Responsive table/modal:** gunakan overflow container dan Bootstrap behavior yang sudah ada.
- **Dialog async:** cegah listener ganda, restore focus, dan submit native hanya setelah konfirmasi.
- **Artisan cache race di Windows:** semua Artisan/test dijalankan serial.

## Daftar File yang Direncanakan

### Baru

- `resources/css/km/foundation.css`
- `resources/js/km/ui-feedback.js`
- `resources/views/components/km/shell.blade.php`
- `resources/views/components/km/page-header.blade.php`
- `resources/views/components/km/status-badge.blade.php`
- `resources/views/components/km/feedback.blade.php`
- `resources/views/components/km/empty-state.blade.php`
- `resources/views/components/km/loading-state.blade.php`
- `tests/Feature/KnowledgeManagement/KmDesignFoundationTest.php`
- `km-docs/EXECUTION-NOTE-KM-DESIGN-FOUNDATION.md`

### Diubah

- `resources/views/layout.blade.php`
- `resources/views/dashboard/dsKnowlege.blade.php`
- `resources/views/knowlege_management/pengajuanKM.blade.php`
- `resources/views/knowlege_management/persetujuanKM.blade.php`
- `resources/views/knowlege_management/analytics/popular.blade.php`
- `resources/css/km/dashboard.css`
- `resources/css/km/authoring.css`
- `resources/js/km/dashboard.js`
- `resources/js/km/authoring.js`
- `resources/js/km/approval.js`
- `vite.config.js`
- `tests/Feature/KnowledgeManagement/KmBladeCompatibilityTest.php`

