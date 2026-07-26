# Mission KM Design Foundation

## Status Mission

- **Nama:** KM Design Foundation — Penerapan Redesign Enterprise
- **Tipe rilis:** code-only, tanpa perubahan schema atau data
- **Mission pendahulu:** KM Jangka Panjang (selesai)
- **Baseline kesiapan:** 10 PASS, 2 WARN, 0 FAIL
- **Bahasa antarmuka:** Bahasa Indonesia

## Tujuan

Menerapkan fondasi desain enterprise yang konsisten, aksesibel, dan responsif pada empat surface Knowledge Management yang aktif tanpa mengubah kontrak backend, alur bisnis, otorisasi, atau kompatibilitas route/view legacy.

Empat surface dalam mission ini adalah:

1. Knowledge Library/Dashboard (`dsKnowlege`)
2. Pengajuan Saya (`pengajuanKM`)
3. Persetujuan (`persetujuanKM`)
4. Materi Populer

## Prinsip Desain yang Dikunci

- Gunakan gaya Swiss enterprise/flat functional dengan navy-slate dan aksen ADASI secara terbatas.
- Gunakan system font lokal, Bootstrap 5.3.2, dan Bootstrap Icons lokal yang sudah tersedia.
- Semua style baru harus berada di bawah scope `.km-app`.
- Search menjadi kontrol utama Library; filter lanjutan menggunakan progressive disclosure.
- Kepadatan informasi tetap sesuai kebutuhan aplikasi internal, bukan landing page pemasaran.
- Feedback tindakan memakai Bootstrap Modal/Toast, bukan `window.alert` atau `window.confirm`.
- Aksesibilitas bukan tambahan kosmetik: keyboard, focus, ukuran target, struktur heading, error association, reduced motion, dan fallback tanpa JavaScript wajib dipertahankan.

## Dalam Scope

### Fondasi visual

- Vite entry CSS khusus fondasi KM dengan primitive, semantic, dan component tokens.
- Hook bahasa pada layout agar view KM dapat menetapkan `lang="id"` tanpa mengubah modul lain.
- Shell kontekstual KM dengan sidebar desktop dan Bootstrap offcanvas responsif.
- Komponen Blade reusable untuk page header, status badge, feedback/error summary, empty state, dan loading state.
- Empat status aktif saja: Tidak Aktif, Draf, Menunggu Persetujuan, dan Terbit.

### Migrasi UI

- Library: search utama, disclosure filter, dokumen, pagination, preview/download privat, bookmark, like, insight, dan completion.
- Pengajuan: hierarchy daftar/tindakan, modal create/edit, tag, co-author, autosave, upload privat, dan compatibility field/DOM.
- Persetujuan: bulk toolbar, tabel, history, decision modal, kategori per dokumen, reject reason, dan transaksi all-or-nothing.
- Materi Populer: shell/tabel baru, penanda data operasional bukan KPI, filter, pagination, dan export XLSX/PDF.

### Feedback dan aksesibilitas

- Modul ES `confirmAction()` dan `notify()` berbasis Bootstrap Modal/Toast.
- Focus restoration, `aria-live`, `aria-busy`, dan double-submit protection.
- Skip link, visible focus ring, target sentuh minimal 44 px, heading berurutan, error terhubung ke field, reduced motion, serta state loading/empty/error nonwarna.
- Progressive enhancement: alur form server-side tetap dapat dipakai tanpa JavaScript.

## Di Luar Scope

- Login, header global, shell modul lain, HR menu, dan server-driven navigation.
- Route canonical baru atau penghapusan alias/route legacy.
- Migration, perubahan schema, cleanup data, status baru, role baru, atau policy ability baru.
- Perubahan controller, service, request field, JSON response, flash key, atau business rule.
- Versioning, multi-reviewer, SLA, notification center, archive/retention, administration, dan decision-gated feature lainnya.
- Dark mode, font eksternal, CDN baru, framework UI baru, atau cleanup dependency global yang sudah ada.
- Split approval workspace atau keseluruhan layar prototipe yang tidak termasuk empat surface aktif.

## Kontrak Kompatibilitas

- URI dan nama route `dsKnowlege`, `pengajuanKM`, `persetujuanKM`, mutation route, private document route, analytics, dan export tidak berubah.
- Controller/service aktif tetap mengirim data view yang sama; desain tidak menambah query untuk dummy content.
- Request field, ID DOM yang digunakan script/test, JSON response, flash key, policy ability, dan database schema tetap.
- View legacy aktif tetap berada pada path saat ini.
- Visibilitas navigasi shell menggunakan policy `@can`; menu tidak menggantikan authorization endpoint.
- Working tree yang sudah kotor adalah baseline dan tidak boleh di-reset atau ditimpa di luar file mission ini.

## Acceptance Criteria

1. Keempat surface aktif memakai `<x-km.shell>` dan menetapkan bahasa dokumen `id`, sementara halaman non-KM tetap memakai default layout.
2. CSS fondasi termuat melalui Vite, seluruh selector komponen baru scoped di `.km-app`, memakai aset lokal, dan tidak menambah CDN/font eksternal/framework.
3. Sidebar menampilkan Library, Pengajuan Saya, Persetujuan, dan Materi Populer sesuai policy yang sudah ada serta memiliki active state yang dapat dibaca assistive technology.
4. `x-km.status-badge` memetakan tepat empat status aktif melalui enum yang sudah ada.
5. Semua nama route, parameter query, field form, pagination, private preview/download, interaksi bookmark/like/completion, autosave/tag/co-author, approval, analytics, dan export tetap bekerja.
6. Tidak ada `window.alert`, `window.confirm`, atau inline event handler pada empat view aktif dan modul JS KM aktif.
7. Modal konfirmasi mengembalikan focus ke trigger; toast memiliki live region; submit memiliki busy state dan perlindungan double-submit.
8. Keempat surface memiliki skip link, heading hierarchy, visible focus, target minimum 44 px, error/empty/loading semantics, responsive table handling, dan reduced-motion support.
9. Elemen versioning, reviewer tambahan, SLA, notification, archive, atau administration tidak dirender.
10. `KmDesignFoundationTest`, `KmBladeCompatibilityTest`, dan regresi KM terkait lulus secara serial.
11. PHP lint/Pint, route list, `view:cache`, Vite build, `km:health`, dan `km:readiness` selesai tanpa kegagalan mandatory baru.
12. QA visual dicatat untuk 360, 768, 1024, 1280, dan 1440 px, termasuk keyboard, zoom 200%, reduced motion, serta peran employee/contributor/approver; bila otomatisasi browser tidak tersedia, keterbatasan dan kebutuhan sign-off staging dicatat eksplisit.

## Rollout dan Rollback

- Rilis dilakukan sebagai satu deployment code-only setelah UAT.
- Rollout mencakup Blade components/views, CSS/JS KM, Vite configuration/manifest, dan test.
- Rollback mengembalikan file Blade/CSS/JS dan manifest/build asset ke versi sebelum mission.
- Tidak ada rollback database atau data karena mission ini tidak mengubah schema maupun record.

