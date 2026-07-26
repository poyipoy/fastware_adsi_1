# MISSION: KM Jangka Panjang — Data Readiness

## Goal

Menutup roadmap KM dengan kemampuan operasional yang aman untuk approval massal, pencarian metadata, dan pelaporan popularitas tanpa mengklaim data tersebut sebagai KPI resmi. Fase ini juga merapikan batas controller/service dan frontend KM agar perubahan berikutnya lebih mudah diuji serta menyediakan pemeriksaan kesehatan yang hanya membaca konfigurasi dan schema. Mission ini dikerjakan dalam satu sesi setelah seluruh acceptance criteria Jangka Pendek dan Jangka Menengah lulus.

## Scope

- **In scope:** bulk approval atomik untuk workflow satu tahap yang berlaku; MySQL `FULLTEXT` untuk metadata `judul` dan `keterangan` dengan filter tag; dashboard dan export materi populer agregat non-KPI; refactor controller/service KM, perbaikan responsive view pengajuan/persetujuan, konsolidasi dependency frontend, dan command read-only `km:health`.
- **Out of scope:** perubahan menjadi approval berjenjang; full-text isi PDF/PPT/PPTX; OCR; event analytics/KPI resmi; data pembaca individual; assignment/compliance; document versioning; notifikasi; perubahan aturan poin/gamifikasi; PWA/offline; integrasi HR; reward nyata; dan kebijakan retensi. Seluruh item tersebut tetap berada di `km-docs/PENDING-DECISIONS-KM.md`.
- **Entry criteria:** migration dan targeted test dua fase sebelumnya sudah lulus; status dokumen sudah memakai enum kompatibel; `KmApprovalService` sudah menulis `km_approval_events`; tag sudah tersedia; counter baca/completion sudah idempotent; policy KM serta private document route sudah aktif; dan database eksekusi adalah MySQL.
- **Batas kompatibilitas:** URI, nama route, dan folder view legacy seperti `dsKnowlege`, `persetujuanKM`, `pengajuanKM`, dan `knowlege_management` tetap berfungsi minimal satu release. File `app/Http/Controllers/1225_KmPengajuanController.php` bukan source of truth dan tidak dijadikan target perubahan.
- **Alasan pengelompokan revisi:** mission ini hanya mengambil slice Phase 2–3 yang sudah dependency-safe—bulk workflow satu tahap, metadata search, laporan operasional, dan hardening teknis. Scope strategis Phase 3–4 tidak dipadatkan secara artifisial ke satu sesi; seluruh bagian yang masih membutuhkan definisi bisnis, governance, security, atau provisioning dipindahkan ke pending decisions.

## Fitur yang Dikerjakan

1. **Bulk approval atomik untuk workflow satu tahap** — dibangun lebih dulu karena memakai enum, policy, constraint, dan append-only approval event dari Jangka Pendek. Aksi approve/reject terhadap sekumpulan dokumen harus bersifat all-or-nothing dan tidak mengubah workflow menjadi berjenjang.
2. **Pencarian metadata MySQL `FULLTEXT` dan filter tag** — dibangun setelah schema/tag stabil. Pencarian hanya mencakup `km_pengajuans.judul` dan `km_pengajuans.keterangan`; isi file tidak diekstrak atau diindeks.
3. **Dashboard dan export materi populer non-KPI** — dibangun setelah counter baca dan completion idempotent. Urutan popularitas bersifat operasional: jumlah lihat menurun, lalu jumlah pembaca selesai menurun, jumlah like menurun, dan ID dokumen menaik sebagai tie-breaker.
4. **Hardening maintainability dan operasional KM** — business logic pada controller aktif didelegasikan ke service, form pengajuan/persetujuan dibuat responsive, dependency JavaScript/CSS ganda dihapus dari view KM, dan command read-only `km:health` ditambahkan. Route/view legacy tetap menjadi kontrak kompatibilitas.

## Acceptance Criteria

### 1. Bulk approval atomik untuk workflow satu tahap

- Route `POST /km/approvals/bulk` bernama `km.approvals.bulk` hanya dapat dipanggil user yang lolos policy approval KM.
- Request menerima `items` unik berisi `document_id` dan kategori masing-masing dokumen saat approve, `action` berupa approve atau reject, serta satu alasan non-kosong saat reject. Satu kategori global tidak boleh ditimpakan diam-diam ke seluruh dokumen.
- Service mengurutkan ID, mengambil seluruh row dengan `lockForUpdate()` di dalam satu `DB::transaction()`, lalu memvalidasi jumlah row, otorisasi, dan status eligible sebelum row pertama diubah.
- Jika satu ID hilang, tidak berhak diakses, sudah diproses, atau transisinya tidak valid, seluruh batch gagal dan tidak ada status maupun approval event yang berubah.
- Batch yang berhasil menghasilkan tepat satu `km_approval_events` append-only per dokumen dengan actor, action, from/to status, reason/notes, dan `acted_at`; tidak ada event gabungan yang menggantikan audit per dokumen.
- Endpoint `approveKM` lama tetap bekerja dan memakai method service/transisi yang sama dengan bulk approval.
- `KmBulkApprovalTest` membuktikan success path, reject reason wajib, unauthorized, stale/mixed status, missing ID, concurrent/stale request, serta rollback all-or-nothing.

### 2. Pencarian metadata MySQL `FULLTEXT` dan filter tag

- Migration `2026_07_18_120001_add_km_metadata_fulltext_index_to_km_pengajuans.php` menambah index bernama `km_pengajuans_judul_keterangan_fulltext` pada `judul` dan `keterangan`, serta `down()` menghapus index yang sama.
- Query dashboard menggunakan `whereFullText(['judul', 'keterangan'], $term)` ketika parameter `q` tidak kosong; pencarian tidak membaca binary file, hasil OCR, nama file, komentar, atau field lain.
- Filter `tag_ids[]` divalidasi sebagai ID tag yang ada dan diterapkan di query database sebelum pagination. Filter visibilitas/policy selalu diterapkan sebelum hasil dikirim ke view.
- Search, filter tag, pagination, dan sorting server-side dari fase sebelumnya dapat dipakai bersamaan tanpa menghilangkan query string pada link halaman.
- Sort default tetap deterministik. Saat sort relevansi dipilih dan `q` tersedia, relevance menjadi urutan pertama dengan ID sebagai tie-breaker; tanpa `q`, pilihan relevance ditolak atau dinormalisasi ke sort default.
- `KmMetadataSearchTest` berjalan pada MySQL testing dan membuktikan match judul, match keterangan, non-match, kombinasi tag, pembatasan akses, pagination query string, dan tidak adanya pencarian isi file.

### 3. Dashboard dan export materi populer non-KPI

- Route `km.analytics.popular` menampilkan data agregat per dokumen yang diizinkan: total lihat, jumlah user unik berstatus selesai, jumlah like, kategori, tag, dan timestamp generasi laporan.
- Ranking tidak memakai bobot bisnis tersembunyi: `total_views DESC`, `completed_readers DESC`, `likes_count DESC`, lalu `km_pengajuans.id ASC`.
- Halaman dan file export selalu berlabel **“Materi Populer — data operasional, bukan KPI”** serta menjelaskan bahwa counter historis sebelum hardening mungkin memiliki keterbatasan.
- Tidak ada nama, email, NIK, atau daftar aktivitas pembaca individual pada halaman maupun export.
- Route `km.analytics.popular.export.xlsx` memakai `maatwebsite/excel`; route `km.analytics.popular.export.pdf` memakai `barryvdh/laravel-dompdf`. Keduanya memakai query/filter/order yang sama dengan halaman HTML.
- HTML memakai pagination 25 row sebagai slice dari ordered query yang sama; XLSX/PDF memakai seluruh filtered query sampai batas 10.000 row dan menampilkan peringatan bila batas tercapai. Nama file memuat tanggal/waktu generasi.
- `KmPopularMaterialAnalyticsTest` membuktikan authorization, agregasi, tie-breaker, perlindungan data individual, filter, dan parity dataset export.

### 4. Hardening maintainability dan operasional KM

- `app/Http/Controllers/KmPengajuanController.php` tetap menjadi controller kompatibilitas aktif, tetapi tidak lagi menjalankan query mutasi/status/file/poin secara langsung; method publik mendelegasikan aturan bisnis ke service KM dan hanya mengatur request/response.
- Seluruh route legacy KM masih terdaftar dan mengembalikan response ekuivalen. Tidak ada perubahan nama field form atau JSON contract tanpa alias kompatibilitas.
- `pengajuanKM.blade.php` dan `persetujuanKM.blade.php` dapat dipakai pada viewport 360 px, 768 px, dan desktop: tabel dapat di-scroll horizontal, action tetap dapat dijangkau, modal tidak melebar keluar viewport, dan label terkait dengan input.
- Ketiga view KM tidak lagi memuat jQuery, Bootstrap, PDF.js, XLSX, atau library yang sama lebih dari sekali. PDF.js 2.14.305 berasal dari bundle lokal fase Jangka Menengah; tidak ada CDN baru atau framework UI baru.
- `php artisan km:health` hanya melakukan operasi baca dan memeriksa koneksi/driver MySQL, tabel/kolom/index kritis KM, named routes KM, private storage path di luar `public`, serta konfigurasi queue. Command tidak membuat/mengubah row, file, job, cache, atau konfigurasi.
- `km:health` mengembalikan exit code `0` bila pemeriksaan wajib lulus, exit code non-zero bila ada kegagalan wajib, dan mencetak warning terpisah untuk queue `sync` atau scheduler/worker yang tidak dapat dibuktikan dari proses aplikasi. Output tidak menampilkan credential atau token.
- `KmLegacyRouteCompatibilityTest` dan `KmHealthCommandTest` lulus; `npm run build` serta `php artisan view:cache` selesai tanpa error.

## Success Metrics

- Tepat empat fitur di atas selesai dalam satu sesi agent dan tidak ada item dari `PENDING-DECISIONS-KM.md` yang ikut diimplementasikan.
- Seluruh targeted test Jangka Panjang lulus secara berurutan: `KmBulkApprovalTest`, `KmMetadataSearchTest`, `KmPopularMaterialAnalyticsTest`, `KmLegacyRouteCompatibilityTest`, dan `KmHealthCommandTest`.
- Tidak ada partial update pada seluruh skenario gagal bulk approval dan jumlah approval event sama dengan jumlah dokumen yang berhasil diproses.
- HTML, XLSX, dan PDF materi populer memakai filter serta ordering yang identik; page HTML cocok dengan slice terkait pada export dan tidak ada output yang mengekspos data pembaca individual.
- `php artisan route:list`, `php artisan view:cache`, `npm run build`, dan `php artisan km:health` lulus pada environment yang sesuai.
- Full test suite tidak menambah kegagalan di luar baseline yang dicatat sebelum sesi; acceptance fase ditentukan oleh targeted KM tests yang hijau dan baseline delta nol.
