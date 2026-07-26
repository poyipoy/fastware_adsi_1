# MISSION: KM Jangka Menengah — Safe Experience Improvements

## Goal

Meningkatkan pengalaman mencari, menyimpan, menyiapkan, dan membaca materi Knowledge Management (KM) tanpa mengubah keputusan bisnis terkait approval, completion, poin, atau dukungan dokumen Office. Fase ini membangun pengalaman dashboard yang terukur dan viewer PDF yang stabil di atas schema, policy, private storage, status, serta test baseline yang telah diselesaikan pada MISSION Jangka Pendek.

Mission ini adalah satu unit eksekusi agent. Agent harus menyelesaikan kelima fitur berikut beserta migration, test, build asset, dan verifikasinya dalam satu sesi; bila fondasi Jangka Pendek belum tersedia, agent harus berhenti sebagai blocked prerequisite dan tidak membuat substitusi yang melemahkan authorization atau integritas data.

## Scope

- **In scope:** server-side pagination/filter/search/sort pada dashboard; bookmark “Baca Nanti”; autosave metadata untuk draft yang sudah tersimpan; tag, co-author atribusi, dan estimasi waktu baca; PDF.js 2.14.305 lokal melalui Vite; viewer PDF responsif; thumbnail otomatis untuk PDF dengan capability check dan fallback aman.
- **In scope:** mempertahankan route legacy `dsKnowlege`, folder view legacy `knowlege_management`, policy/private streaming dari fase Jangka Pendek, serta upload PPT/PPTX existing dengan pesan bahwa preview dan thumbnail otomatis belum didukung.
- **Out of scope:** versioning dan aturan approval ulang; progress halaman/resume serta definisi completion baru; approval berjenjang/SLA/reminder; notifikasi; recommendation; threading/reaction/mention; ledger poin, badge, tier, atau leaderboard departemen; analytics KPI/compliance; OCR, ekstraksi isi, FULLTEXT, serta konversi/thumbnail PPT/PPTX; PWA/offline; integrasi HR; reward; upgrade Laravel.
- **Constraint:** gunakan Laravel 10, Blade, Bootstrap yang sudah ada, MySQL, PHPUnit 10, dan Vite. Jangan menambah framework UI, CDN baru, search service eksternal, atau binary Office.
- **Alasan pengelompokan revisi:** mission ini menampung Safe Quick Wins Phase 1 yang baru aman setelah Jangka Pendek, ditambah authoring metadata Phase 2 yang tidak bergantung pada keputusan versioning. Notifikasi, workflow, completion, dan engagement tidak dipaksakan masuk karena masih mempunyai keputusan stakeholder atau dependency yang belum siap.

## Fitur yang Dikerjakan

1. **Dashboard server-side** — pagination, pencarian metadata, filter kategori/status baca/tanggal/bookmark, dan sorting dijalankan oleh query database setelah pembatasan akses dokumen diterapkan.
2. **Bookmark “Baca Nanti”** — user dapat menambah/menghapus bookmark secara idempotent dan menampilkan hanya dokumen yang telah di-bookmark.
3. **Authoring draft** — autosave metadata draft dengan optimistic revision, tag, co-author sebagai atribusi, dan estimasi waktu baca; autosave tidak mengunggah binary dan tidak mengubah lifecycle/approval status.
4. **Viewer PDF lokal** — PDF.js versi tepat `2.14.305` dipaketkan khusus halaman KM melalui Vite, memakai private preview route, dan menyediakan viewer satu-modal yang responsif serta memiliki state loading/error.
5. **Thumbnail PDF-only** — thumbnail halaman pertama dibuat secara asynchronous bila `pdftoppm` tersedia; dokumen non-PDF, binary yang tidak tersedia, dan kegagalan job selalu memakai thumbnail default tanpa menggagalkan submit/publish dokumen.

## Acceptance Criteria

### 1. Dashboard server-side

- GET route legacy `dsKnowlege` menerima query `q`, `category`, `read_status`, `date_from`, `date_to`, `bookmarked`, `sort`, dan `per_page`; nilai di luar allow-list menghasilkan validation error, bukan disisipkan langsung ke SQL.
- Default `per_page` adalah 12 dan pilihan yang diizinkan hanya 12, 24, atau 48. Sorting yang didukung adalah `latest`, `oldest`, `title_asc`, dan `popular`; default adalah `latest` dengan `id` sebagai tie-breaker deterministik.
- `q` mencari `km_pengajuans.judul` dan `km_pengajuans.keterangan` dengan escaped `LIKE`; filter kategori, rentang `km_pengajuans.created_at` sebagai tanggal dokumen baseline, status baca user aktif, dan bookmark dapat dikombinasikan.
- Query visibility/policy dari fase Jangka Pendek diterapkan sebelum filter dan pagination. User tidak dapat menemukan atau menghitung dokumen di luar aksesnya melalui search, filter, count, pagination, maupun parameter ID.
- Hasil menggunakan `LengthAwarePaginator`, link pagination mempertahankan seluruh query string, dan tampilan empty state membedakan “belum ada materi” dengan “filter tidak menemukan hasil”.
- Dashboard tidak lagi memuat seluruh dokumen melalui `get()` dan tidak lagi bergantung pada fungsi JavaScript client-side `searchData()`, `filterStatus()`, atau sorting DOM sebagai sumber hasil.

### 2. Bookmark “Baca Nanti”

- Kombinasi `user_id + km_pengajuan_id` unik di database; request store berulang tidak membuat duplikat dan request destroy untuk bookmark yang sudah tidak ada tetap berhasil secara idempotent.
- Route `km.bookmarks.store` dan `km.bookmarks.destroy` berada di dalam middleware `auth`, menggunakan route model binding, serta menjalankan authorization `view` untuk dokumen target sebelum mutasi.
- Setiap card dashboard menampilkan state bookmark user aktif dan tombol Bootstrap yang dapat dioperasikan dengan keyboard; sukses/gagal ditampilkan tanpa me-reload halaman penuh.
- Query `bookmarked=1` hanya menampilkan bookmark milik user aktif yang dokumennya masih visible. Bookmark tidak memberikan akses baru ke dokumen.

### 3. Autosave metadata draft, tag, co-author, dan estimasi baca

- Route `km.documents.autosave` hanya menerima dokumen milik user aktif yang masih berstatus draft dan lolos policy `update`; dokumen submitted/approved/rejected tidak dapat diubah melalui autosave.
- Payload autosave hanya berisi `judul`, `keterangan`, `reading_minutes`, `tags`, `co_author_ids`, dan `revision`. Binary file/thumbnail, owner, status, approval, poin, serta field lain ditolak atau diabaikan oleh request whitelist.
- Autosave menggunakan debounce 4 detik dan optimistic locking. Revision yang sesuai menaikkan `draft_revision` satu angka serta mengisi `autosaved_at`; revision stale mengembalikan HTTP 409 beserta revision server tanpa menimpa data terbaru.
- `reading_minutes` nullable atau integer 1–1440; maksimal 10 tag unik per dokumen, masing-masing maksimal 50 karakter; maksimal 10 co-author aktif, berbeda dari owner, dan tidak duplikat.
- Tag dinormalisasi dengan trim, whitespace tunggal, dan slug lowercase sebelum `firstOrCreate`. Co-author hanya atribusi/display dan tidak memperoleh hak edit, approve, preview, atau download tambahan.
- Sinkronisasi metadata, tag, dan co-author berjalan dalam satu transaction. Response sukses memuat `draft_revision` dan `autosaved_at`; UI menampilkan state `Saving`, `Saved`, `Conflict`, atau `Failed` dan tidak menyatakan sukses sebelum response server diterima.
- Normal submit/update memakai aturan validasi metadata yang sama. Autosave tidak mengubah lifecycle status, tidak mengirim approval, tidak membuat versi dokumen, dan tidak memberi poin.

### 4. PDF.js lokal dan viewer responsif

- `package.json` dan lockfile mengunci `pdfjs-dist` tepat `2.14.305`; halaman KM memuat entry Vite khusus KM dan tidak lagi memuat PDF.js dari `cdnjs`, `mozilla.github.io`, atau CDN lain.
- Worker PDF.js di-resolve sebagai asset Vite lokal. Viewer mengambil file hanya dari route berotorisasi `km.documents.preview`, tidak dari `public/assets/image` atau path file database.
- Satu modal viewer reusable memiliki loading indicator, pesan error, nomor halaman, previous/next, zoom, close, dan canvas responsif; ukuran canvas menyesuaikan modal tanpa horizontal overflow pada viewport 360 px.
- PDF dirender satu halaman aktif pada satu waktu sehingga dokumen besar tidak langsung merender seluruh halaman. Request/race lama dibatalkan ketika user berpindah dokumen atau menutup modal.
- PPT/PPTX tidak dikirim ke PDF.js. UI menampilkan “Preview belum tersedia untuk format ini” dan hanya menawarkan route download berotorisasi dari fase Jangka Pendek.
- Perubahan viewer tidak mengubah endpoint/semantik `markAsRead` dan completion yang sudah diamankan pada fase Jangka Pendek.

### 5. Thumbnail otomatis PDF-only

- Saat PDF baru disimpan atau PDF existing diganti, status thumbnail menjadi `pending` dan job `GenerateKmPdfThumbnail` didispatch setelah transaction commit. Job idempotent terhadap document ID dan checksum sumber.
- Service memeriksa flag konfigurasi dan executable `pdftoppm` sebelum menjalankan proses dengan argument array, timeout, serta temporary directory terisolasi. Binary tidak tersedia menghasilkan status `unavailable`, bukan exception pada alur submit.
- Output PNG halaman pertama disimpan pada private KM disk. Thumbnail hanya disajikan melalui route `km.documents.thumbnail` setelah policy `view`; route tidak membocorkan path storage.
- Thumbnail `ready` hanya dipakai bila `thumbnail_source_checksum` sama dengan checksum file aktif. Pending, failed, unavailable, checksum mismatch, file non-PDF, dan dokumen tanpa thumbnail memakai asset default yang sama.
- MIME ditentukan dari metadata/file server-side, bukan ekstensi atau nama upload. PPT/PPTX ditandai `unsupported` dan tidak pernah dikirim ke Poppler, LibreOffice, OCR, atau converter eksternal.
- Job memiliki maksimum tiga percobaan untuk kegagalan transient dan menyimpan alasan gagal yang disanitasi. Kegagalan thumbnail tidak mengubah status dokumen, approval, atau akses file.

## Success Metrics

- Tepat lima fitur di atas selesai; tidak ada fitur `Out of scope` yang ikut diimplementasikan.
- `KmDashboardFilterTest`, `KmBookmarkTest`, `KmDocumentAuthoringTest`, `KmPdfViewerTest`, dan `KmPdfThumbnailTest` lulus pada database testing yang terverifikasi.
- Keenam migration fase ini berhasil melalui urutan migrate → rollback batch → migrate ulang pada database dengan `APP_ENV=testing` dan nama database berakhiran `_testing`.
- `npm.cmd run build` berhasil dan manifest Vite memuat entry KM serta PDF.js 2.14.305 tanpa request runtime ke CDN PDF.js.
- Uji manual pada desktop dan viewport 360 px membuktikan filter/pagination/bookmark/autosave/viewer dapat digunakan, focus modal kembali ke pemicu, dan dokumen private tetap tidak dapat diakses user unauthorized.
- Targeted KM test hijau dan full suite tidak menambah kegagalan di luar baseline yang telah dicatat pada fase Jangka Pendek.
