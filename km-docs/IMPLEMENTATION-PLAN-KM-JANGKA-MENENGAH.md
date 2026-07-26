# IMPLEMENTATION PLAN: KM Jangka Menengah — Safe Experience Improvements

## Prasyarat Eksekusi

- Selesaikan dan verifikasi MISSION Jangka Pendek terlebih dahulu: schema KM reproducible, status enum/transition aktif, completion/poin idempotent, `KmPengajuanPolicy` terdaftar, file berada pada private disk, route `km.documents.preview`/`km.documents.download` berotorisasi, approval audit minimum tersedia, dan targeted test baseline hijau.
- Gunakan hanya controller aktif `app/Http/Controllers/KmPengajuanController.php`. Jangan mengubah atau menjadikan `app/Http/Controllers/1225_KmPengajuanController.php` sebagai source of truth.
- Catat baseline `php artisan test` sebelum perubahan. Semua perintah migration/rollback hanya boleh dijalankan ketika `APP_ENV=testing` dan nama database berakhiran `_testing`.
- Buat seluruh enam file migration fase ini sebelum menjalankan migration pertama. Artisan harus menerapkannya sesuai timestamp yang dikunci di bagian **Database Changes**, termasuk migration thumbnail `110002` yang secara dependency behavior baru diaktifkan pada fitur kelima.

## Urutan Eksekusi

### 1. Dashboard server-side: pagination, filter, search, dan sort

1. **Migration:** tidak membuat migration baru. Verifikasi index hasil hardening Jangka Pendek mencakup dokumen published/status-kategori-tanggal, transaksi user-dokumen-status, kategori, dan counter view. Jalankan `EXPLAIN` untuk query default, pencarian, status baca, kategori, bookmark, dan popular; bila index prasyarat tidak ada, perbaiki deliverable Jangka Pendek terlebih dahulu, bukan menambah raw index tanpa audit pada fase ini.
2. **Model/query:** buat `app/Services/KnowledgeManagement/KmDashboardQueryService.php`. Service menerima user dan DTO filter tervalidasi, menerapkan visibility scope lebih dahulu, lalu eager-load hanya `kmKategori`, tag/co-author yang diperlukan, transaksi user aktif, serta aggregate count/sum. Gunakan `paginate($perPage)->withQueryString()`; jangan panggil `get()` sebelum pagination.
3. **Request/controller:** buat `app/Http/Requests/KnowledgeManagement/KmDashboardFilterRequest.php` dengan allow-list:
   - `q`: nullable string, trim, maksimum 100;
   - `category`: nullable integer yang harus ada di `km_kategoris`;
   - `read_status`: `unread|reading|completed` dan dipetakan melalui `KmReadStatus`, bukan angka magic;
   - `date_from`/`date_to`: format tanggal, `date_to >= date_from`, difilter terhadap `km_pengajuans.created_at` sebagai baseline tanggal dokumen;
   - `bookmarked`: nullable boolean;
   - `sort`: `latest|oldest|title_asc|popular`;
   - `per_page`: `12|24|48`.
   Ubah `KmPengajuanController::dsKnowlege()` agar menerima request dan service tersebut. `q` memakai escaped `LIKE` pada `judul` dan `keterangan`; setiap sort menambahkan `id` sebagai tie-breaker.
4. **Route:** pertahankan GET `/dsKnowlege` bernama `dsKnowlege`. Jangan mengganti nama route/menu legacy dan jangan membuat endpoint pencarian terpisah.
5. **View:** ubah `resources/views/dashboard/dsKnowlege.blade.php` menjadi GET filter form Bootstrap. Render paginator, active-filter chips, tombol reset, dan dua empty state. Hapus `searchData()`, `filterStatus()`, `toggleSort()`, serta manipulasi DOM yang menjadi sumber filtering/sorting; JavaScript hanya boleh meningkatkan UX form, bukan menentukan hasil.
6. **Test:** buat `tests/Feature/KnowledgeManagement/KmDashboardFilterTest.php` untuk default pagination 12, query-string preservation, kombinasi filter, search title/description, semua sort, invalid allow-list, status baca per user, visibility sebelum filter, dan bukti page kedua tidak memuat row page pertama. Pastikan query count tidak bertumbuh linear terhadap jumlah card pada page.

### 2. Bookmark “Baca Nanti”

1. **Migration:** buat `database/migrations/2026_07_18_110001_create_km_bookmarks_table.php` sesuai definisi database di bawah. Unique `(user_id, km_pengajuan_id)` adalah enforcement utama idempotensi.
2. **Model:** buat `app/Models/KmBookmark.php` dengan relasi `user()` dan `document()`. Tambahkan `bookmarks()` pada `User`, serta `bookmarks()`/`bookmarkedBy(User $user)` pada `KmPengajuan`; hindari query state bookmark per-card dengan eager-load/`withExists` untuk user aktif.
3. **Controller:** buat `app/Http/Controllers/KnowledgeManagement/KmBookmarkController.php` dengan `store(KmPengajuan $kmPengajuan)` dan `destroy(KmPengajuan $kmPengajuan)`; ambil user hanya dari `auth()`, bukan parameter/payload client. Panggil `$this->authorize('view', $kmPengajuan)` sebelum `firstOrCreate`/delete. Store mengembalikan JSON 201 saat dibuat dan 200 saat sudah ada; destroy selalu 204 setelah authorization, termasuk ketika row tidak ditemukan.
4. **Route:** dalam group `auth`, tambahkan:
   - `POST /km/documents/{kmPengajuan}/bookmarks` → `km.bookmarks.store`;
   - `DELETE /km/documents/{kmPengajuan}/bookmarks` → `km.bookmarks.destroy`.
   Gunakan implicit route model binding dan rate limit web default; route tidak menerima `user_id` dari client.
5. **View/asset:** tambahkan tombol bookmark pada card di `dsKnowlege.blade.php` dan handler di `resources/js/km/bookmarks.js`, diimpor oleh entry `resources/js/km/dashboard.js`. Kirim CSRF token, disable tombol selama request, update `aria-pressed`, teks, dan icon hanya setelah response sukses, serta tampilkan alert inline pada error. Tambahkan filter `bookmarked=1` ke form server-side.
6. **Test:** buat `tests/Feature/KnowledgeManagement/KmBookmarkTest.php` untuk guest redirect, authorized store/destroy, request store berulang, destroy berulang, constraint unique pada race/duplikat, isolation antar-user, forbidden invisible document, serta filter bookmark yang tetap menerapkan visibility.

### 3. Autosave metadata draft, tag, co-author, dan estimasi waktu baca

1. **Migration:** buat, sebelum migration dijalankan:
   - `2026_07_18_110003_create_km_tags_table.php`;
   - `2026_07_18_110004_create_km_document_tag_table.php`;
   - `2026_07_18_110005_create_km_document_authors_table.php`;
   - `2026_07_18_110006_add_km_authoring_metadata_to_km_pengajuans.php`.
   Gunakan kolom, unique constraint, dan FK pada bagian **Database Changes**. Jangan menambah version table/current version, progress, approval stage, atau organization snapshot.
2. **Model:** buat `app/Models/KmTag.php` dan `app/Models/KmDocumentAuthor.php`. Tambahkan `tags()` dan `coAuthors()` ke `KmPengajuan`; `coAuthors()` adalah relasi atribusi ke `User` melalui `km_document_authors` dan tidak digunakan sebagai grant authorization. Tambahkan cast integer/datetime untuk `reading_minutes`, `draft_revision`, dan `autosaved_at`.
3. **Request/service/controller:**
   - buat `app/Http/Requests/KnowledgeManagement/KmDocumentAutosaveRequest.php` dengan whitelist payload dan batas dari mission;
   - buat `app/Services/KnowledgeManagement/KmDocumentAuthoringService.php` untuk normalisasi tag, validasi user aktif, dan sinkronisasi metadata/tag/co-author dalam `DB::transaction`;
   - buat invokable `app/Http/Controllers/KnowledgeManagement/KmDocumentAutosaveController.php`.
   Controller menjalankan policy `update`, memastikan status `KmDocumentStatus::Draft`, lalu membandingkan `revision` menggunakan conditional update (`WHERE id = ? AND draft_revision = ?`). Jika affected row nol, kembalikan HTTP 409 dengan `draft_revision` dan `autosaved_at` server; jangan melakukan last-write-wins. Service menaikkan revision tepat sekali dan tidak menyentuh file/status/approval/poin.
4. **Route:** tambahkan `PATCH /km/documents/{kmPengajuan}/autosave` bernama `km.documents.autosave` di group `auth`. Untuk draft baru, route legacy `storeKM` tetap menjadi tindakan pertama “Simpan Draft”; autosave baru aktif setelah dokumen memiliki ID. Jangan membuat autosave binary upload.
5. **View/asset:** perluas `resources/views/knowlege_management/pengajuanKM.blade.php` dengan input tag, co-author, reading minutes, hidden `draft_revision`, status autosave, dan tombol “Simpan Draft” awal. Buat `resources/js/km/draft-autosave.js`, impor dari entry khusus `resources/js/km/authoring.js`, daftarkan entry itu di `vite.config.js`, lalu muat melalui `@vite` hanya pada view pengajuan. Autosave memakai debounce 4 detik, satu request aktif per dokumen, `AbortController` untuk request superseded, dan berhenti setelah HTTP 409 sampai user reload/reconcile. Update normal `storeKM`/`updateKM` agar memanggil service/validator yang sama untuk metadata; file tetap diproses oleh service file Jangka Pendek.
6. **Test:** buat `tests/Feature/KnowledgeManagement/KmDocumentAuthoringTest.php` untuk owner draft sukses, unauthorized/non-owner, non-draft ditolak, revision increment, stale revision 409 tanpa overwrite, rollback semua relasi bila satu input gagal, batas tag/co-author, tag normalization/uniqueness, user inactive/owner sebagai co-author ditolak, co-author tidak memperoleh akses baru, serta pembuktian binary/status/approval/poin tidak berubah.

### 4. PDF.js 2.14.305 lokal dan viewer responsif

1. **Migration/model:** tidak ada perubahan database atau model. Viewer wajib memakai metadata MIME/private path yang sudah tersedia dari Jangka Pendek.
2. **Dependency/asset:** jalankan `npm.cmd install --save-exact pdfjs-dist@2.14.305` sehingga `package.json` dan `package-lock.json` mengunci versi tepat. Tambahkan `resources/js/km/dashboard.js` dan `resources/css/km/dashboard.css` sebagai input di `vite.config.js`. Buat `resources/js/km/pdf-viewer.js` yang mengimpor library dari `pdfjs-dist/build/pdf` dan worker melalui `pdfjs-dist/build/pdf.worker.min.js?url`; set `GlobalWorkerOptions.workerSrc` ke URL hasil Vite, bukan URL eksternal.
3. **Controller:** tidak membuat controller file baru. Gunakan response dari route `km.documents.preview` dan `km.documents.download` Jangka Pendek. Controller preview harus tetap melakukan policy `view`, mendukung byte/range response yang diperlukan browser bila implementasi Jangka Pendek sudah menyediakannya, dan mengirim `Content-Type: application/pdf` hanya untuk MIME PDF terverifikasi.
4. **Route:** tidak mengubah route legacy. JavaScript menerima URL dari `route('km.documents.preview', $pengajuan)` pada `data-preview-url`; jangan menyusun URL dari `file`/`file_name` di client.
5. **View:** ganti modal-per-card dengan satu modal viewer reusable di `resources/views/dashboard/dsKnowlege.blade.php`. Buat controls previous/next, page counter, zoom, retry, loading, error, dan close; gunakan Bootstrap existing serta CSS scoped `.km-pdf-viewer`. Render satu page aktif, cancel `loadingTask`/`renderTask` ketika dokumen berganti atau modal tutup, dan revoke resource yang dibuat. Untuk non-PDF, tampilkan fallback dengan link `km.documents.download`; hapus ketiga `<script>` PDF.js CDN dan seluruh referensi `public/assets/image/{file}` dari viewer KM.
6. **Test/build:** buat `tests/Feature/KnowledgeManagement/KmPdfViewerTest.php` untuk authorized/unauthorized preview, MIME PDF vs PPT/PPTX fallback, data URL berasal dari named route, dan HTML tidak memuat domain CDN/direct public file. Jalankan `npm.cmd run build` dan verifikasi manifest memuat entry KM, worker lokal, serta tidak ada request network PDF.js saat smoke test desktop/360 px.

### 5. Thumbnail otomatis PDF-only dengan fallback

1. **Migration:** implementasikan file yang sudah dibuat pada batch awal: `database/migrations/2026_07_18_110002_add_km_thumbnail_pipeline_fields_to_km_pengajuans.php`. Migration hanya menambah metadata pipeline, tidak memindahkan/menghapus kolom legacy `image`.
2. **Model/enum/config:** buat string-backed enum `app/Enums/KnowledgeManagement/KmThumbnailStatus.php` dengan mapping `Missing='missing'`, `Pending='pending'`, `Processing='processing'`, `Ready='ready'`, `Unsupported='unsupported'`, `Unavailable='unavailable'`, dan `Failed='failed'`. Tambahkan fillable/cast yang diperlukan pada `KmPengajuan`. Tambahkan konfigurasi `thumbnail` pada `config/knowledge_management.php` dan dokumentasikan env `KM_PDF_THUMBNAIL_ENABLED`, `KM_PDFTOPPM_BINARY`, `KM_PDF_THUMBNAIL_TIMEOUT`, serta `KM_PDF_THUMBNAIL_DISK` pada deployment note; jangan mengubah `.env` yang ter-commit atau hard-code path binary production.
3. **Service/job/controller:**
   - buat `app/Services/KnowledgeManagement/KmPdfThumbnailService.php` untuk MIME/checksum validation, capability probe `pdftoppm -v`, command argument array, isolated temp directory, first-page PNG, dan atomic private-storage write;
   - buat `app/Jobs/KnowledgeManagement/GenerateKmPdfThumbnail.php` dengan `$tries = 3`, backoff 60/300 detik, idempotency document+checksum, status transitions, sanitized failure reason, dan cleanup temp pada `finally`;
   - dispatch job `afterCommit` dari flow file-create/file-replacement Jangka Pendek hanya untuk MIME PDF. Non-PDF langsung berstatus `unsupported`; binary/flag unavailable berstatus `unavailable` tanpa retry storm;
   - buat invokable `app/Http/Controllers/KnowledgeManagement/KmDocumentThumbnailController.php` yang authorize `view`, memastikan checksum thumbnail sama dengan checksum sumber aktif, lalu stream PNG private atau mengembalikan asset default `public/assets/img/km/default-thumbnail.svg`.
4. **Route:** tambah `GET /km/documents/{kmPengajuan}/thumbnail` bernama `km.documents.thumbnail` dalam group `auth`. View hanya menerima URL route ini dan tidak membaca `thumbnail_path` atau kolom legacy `image`.
5. **View/asset:** buat SVG generik tanpa data dokumen pada `public/assets/img/km/default-thumbnail.svg`. Ubah card dashboard dan list pengajuan agar memakai route thumbnail; gunakan `loading="lazy"`, dimensi tetap untuk mencegah layout shift, alt text judul, serta fallback default yang sama untuk `missing|pending|processing|unsupported|unavailable|failed`.
6. **Test/operasional:** buat `tests/Feature/KnowledgeManagement/KmPdfThumbnailTest.php` memakai `Storage::fake`, `Queue::fake`, dan process fake/wrapper injectable untuk skenario dispatch after commit, PDF sukses, checksum idempotent/mismatch, binary unavailable, timeout/failure/retry, non-PDF tidak diproses, private route authorization, dan fallback. Uji capability nyata di environment deployment; ketiadaan Poppler adalah mode degraded yang valid dan harus menghasilkan fallback, bukan memblokir release.

## Database Changes

Migration harus dibuat dan diterapkan dalam urutan berikut:

1. **`2026_07_18_110001_create_km_bookmarks_table.php`**
   - `id` bigint unsigned auto increment;
   - `user_id` bigint unsigned FK → `users.id` cascade on delete;
   - `km_pengajuan_id` signed int FK → `km_pengajuans.id` cascade on delete, mengikuti tipe legacy yang dipertahankan Jangka Pendek;
   - timestamps;
   - unique `km_bookmarks_user_document_unique (user_id, km_pengajuan_id)` dan index balik `(km_pengajuan_id, user_id)`.

2. **`2026_07_18_110002_add_km_thumbnail_pipeline_fields_to_km_pengajuans.php`**
   - nullable `thumbnail_path` varchar(255);
   - `thumbnail_status` varchar(20) default `missing`, indexed;
   - nullable `thumbnail_source_checksum` char(64);
   - nullable `thumbnail_generated_at` timestamp;
   - nullable `thumbnail_failure_reason` varchar(500);
   - composite index `(thumbnail_status, updated_at)` untuk worker/backfill. Tidak drop/rename `image`.

3. **`2026_07_18_110003_create_km_tags_table.php`**
   - `id` bigint unsigned auto increment, `name` varchar(50), `slug` varchar(60), timestamps;
   - unique `slug`; simpan nama yang sudah dinormalisasi, bukan HTML.

4. **`2026_07_18_110004_create_km_document_tag_table.php`**
   - `km_pengajuan_id` signed int FK → `km_pengajuans.id` cascade;
   - `km_tag_id` bigint unsigned FK → `km_tags.id` cascade;
   - timestamps, unique `(km_pengajuan_id, km_tag_id)`, dan index balik `(km_tag_id, km_pengajuan_id)`.

5. **`2026_07_18_110005_create_km_document_authors_table.php`**
   - `id` bigint unsigned auto increment;
   - `km_pengajuan_id` signed int FK → `km_pengajuans.id` cascade;
   - `user_id` bigint unsigned FK → `users.id` restrict on delete agar atribusi tidak hilang diam-diam;
   - timestamps, unique `(km_pengajuan_id, user_id)`, dan index `(user_id, km_pengajuan_id)`.

6. **`2026_07_18_110006_add_km_authoring_metadata_to_km_pengajuans.php`**
   - nullable `reading_minutes` smallint unsigned;
   - `draft_revision` bigint unsigned default 0;
   - nullable `autosaved_at` timestamp;
   - index `(id_user, status, updated_at)` untuk daftar draft owner; gunakan nama/status lifecycle dari hasil Jangka Pendek, jangan membuat kolom status kedua.

Setiap `up()` melakukan preflight tipe/keberadaan tabel hasil baseline. Setiap `down()` melepas FK/index sebelum kolom/tabel dan tidak menyentuh tabel/kolom legacy lain. Jangan melakukan cleanup duplikat/orphan diam-diam; fase ini mengasumsikan laporan cleanup Jangka Pendek sudah bersih.

## Testing Checklist

- [ ] Pastikan `.env.testing` memakai `APP_ENV=testing` dan nama database berakhiran `_testing`; catat `php artisan about` dan koneksi database sebelum migration.
- [ ] Jalankan migrate seluruh batch → rollback batch → migrate ulang. Verifikasi FK, unique constraint, default, index, dan urutan timestamp keenam migration.
- [ ] Jalankan `php artisan test tests/Feature/KnowledgeManagement/KmDashboardFilterTest.php`.
- [ ] Jalankan `php artisan test tests/Feature/KnowledgeManagement/KmBookmarkTest.php`.
- [ ] Jalankan `php artisan test tests/Feature/KnowledgeManagement/KmDocumentAuthoringTest.php`.
- [ ] Jalankan `php artisan test tests/Feature/KnowledgeManagement/KmPdfViewerTest.php`.
- [ ] Jalankan `php artisan test tests/Feature/KnowledgeManagement/KmPdfThumbnailTest.php`.
- [ ] Jalankan seluruh targeted test Jangka Pendek untuk idempotensi poin, authorization, private file access, dan approval workflow; perubahan fase ini tidak boleh meregresikan test tersebut.
- [ ] Jalankan `php artisan route:list --name=km` dan pastikan route bookmark/autosave/thumbnail terlindungi `auth`; konfirmasi route `dsKnowlege` dan route legacy lain tetap ada.
- [ ] Jalankan PHP syntax check pada file baru/diubah, `vendor/bin/pint --test` pada change set, `php artisan view:cache`, lalu `php artisan optimize:clear` setelah verifikasi.
- [ ] Jalankan `npm.cmd install` dari lockfile dan `npm.cmd run build`; periksa manifest Vite, versi `pdfjs-dist`, worker lokal, dan tidak adanya URL CDN PDF.js di source/rendered page.
- [ ] Uji dashboard dengan data lebih dari 48 dokumen: kombinasi search/filter/sort/page, query string, empty state, dan query count.
- [ ] Uji race autosave dua tab: request revision lama harus 409 dan tidak boleh menimpa tag/co-author/metadata terbaru.
- [ ] Uji PDF kecil/besar/corrupt, PPT/PPTX, Poppler tersedia/tidak tersedia, timeout, retry, checksum berubah, serta fallback default.
- [ ] Uji owner, co-author, employee eligible, employee tidak eligible, dan approver: bookmark/preview/thumbnail tidak boleh memperluas hak policy.
- [ ] Uji manual keyboard, focus modal, loading/error feedback, dan viewport 360 px/desktop pada Chrome target perusahaan.
- [ ] Jalankan full suite dan bandingkan dengan baseline. Fase diterima bila seluruh targeted test hijau dan tidak ada kegagalan baru di luar baseline existing.

## Rollback Plan

1. **Strategi deployment:** deploy secara additive. Jalankan migration lebih dahulu, lalu code/model/routes, lalu build asset. Jangan drop `image`, route legacy, atau field/status lama minimal satu release; jangan menghapus file/thumbnail lama saat rollout.
2. **Rollback aplikasi:** bila error produksi muncul, rollback code dan asset ke release sebelumnya tetapi biarkan tabel/kolom additive tetap ada. Route lama tetap kompatibel; document flow Jangka Pendek tetap bekerja tanpa bookmark/autosave/viewer baru.
3. **Viewer:** bila PDF.js lokal bermasalah, nonaktifkan viewer KM melalui release forward-fix dan tampilkan hanya `km.documents.download`; jangan mengembalikan CDN atau direct public file URL.
4. **Thumbnail:** set `KM_PDF_THUMBNAIL_ENABLED=false`, hentikan dispatch job baru, dan biarkan UI memakai SVG default. Jangan menghapus output private sampai job aktif berhenti dan checksum/referensi selesai diaudit.
5. **Autosave:** cabut pemanggilan JavaScript/route melalui code rollback; data metadata terakhir tetap tersimpan. Jangan menurunkan `draft_revision` atau melakukan overwrite massal. Bookmark/tag/co-author tetap aman sebagai data additive.
6. **Rollback database:** `migrate:rollback --step=6` hanya pada database testing atau sebelum fitur dipakai user. Untuk production yang sudah memiliki bookmark/tag/co-author, ambil backup/export dahulu dan gunakan forward-fix; menjalankan `down()` akan menghapus data fitur.
7. **File/process failure:** temporary file wajib dibersihkan di blok `finally`. DB tidak boleh menunjuk thumbnail `ready` sebelum write atomik selesai; pada mismatch atau rollback, ubah status menjadi `failed/missing` dan gunakan fallback, bukan menghapus dokumen sumber.
