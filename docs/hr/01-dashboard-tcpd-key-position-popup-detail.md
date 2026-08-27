# Revisi HR #1 — Dashboard TCPD: Key Position List Bisa Diklik (Popup Detail)

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL).
Halaman: **Dashboard TCPD** (`/dashboard-tcpd`), route name `dashboardTCPD`.
Section spesifik yang direvisi: card **"Key Position Status"** (komentar kode: `Modul 2.1`).

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

- View: `resources/views/dashboard/dashboardTCPD.blade.php`
  - Container card "Key Position Status" ada di sekitar baris ~565–581, dengan id:
    - `#key-position-row` (wrapper row, awalnya `display:none`)
    - `#kp-total-badge` (badge total jumlah key position)
    - `#key-position-stats` (grid card per job position, di-render oleh JS)
  - JS yang merender `#key-position-stats` ada di blok `Modul 2.1: Key Position Stats` (dalam `<script>` di bagian bawah file). Saat ini setiap card **hanya menampilkan angka statis** (nama job position, jumlah karyawan, jumlah "terpenuhi", jumlah "defisit", progress bar %). **Tidak ada `onclick`/event listener apa pun** — card tidak bisa diklik dan tidak ada popup.
- Backend: `app/Services/Dashboard/TcpdDashboardService.php`
  - Method `getKeyPositionStats()` (komentar `Modul 2.1`) mengambil semua `mst_job_positions` dengan `is_key_position = true`, lalu untuk tiap key position menghitung `employee_count`, `strength_count`, `deficit_count` (via `CompetencyAssessmentService::getStrengthCompetencies()` dan `getAreaDevelopmentCompetencies()`).
  - **Data yang dikembalikan saat ini TIDAK termasuk**: `job_position_id` dan **daftar karyawan per key position** (nama + skor). Ini yang perlu ditambahkan agar popup bisa menampilkan detail.
  - Hasil `getKeyPositionStats()` disisipkan ke payload sebagai `key_position_stats` di dalam `insights` (lihat `buildCompanyInsights()`), dan dikonsumsi frontend lewat endpoint `GET /dashboard-tcpd/company-data` (route `dashboardTCPD.companyData`, controller `DashboardController::getTcpdCompanyData`) sebagai `meta.insights.key_position_stats`.

### Pola Referensi yang SUDAH ADA dan Berfungsi (WAJIB DITIRU)

Di halaman yang sama, section **"Top 5 Job Positions"** (id `#insight-top-jobs`) sudah mengimplementasikan **persis** pola yang diminta di revisi ini:

- Backend (`buildCompanyInsights()` di `TcpdDashboardService.php`) menghasilkan `top_jobs`, setiap item berisi `job_position`, `percentage`, dan array `employees` (`{id, name, tc, sk, ad}` — masing-masing persentase Technical/Soft Skill/Additional per karyawan). Data employee ini diambil dari `userSummaries` pada snapshot job position (`$jobSnapshotData['aggregate'][$jobName]`).
- Frontend: setiap card di `#insight-top-jobs` di-render dengan atribut `data-job-name`, `data-percentage`, `data-employees` (JSON employees di-escape ke atribut HTML), `role="button"`, `style="cursor:pointer"`.
- Ada modal Bootstrap `#topJobsModal` (HTML-nya statis di blade, sekitar baris ~491–520) dengan title `#tjm-title`, badge row `#tjm-badge-row`, dan `<tbody id="tjm-tbody">`.
- Click listener di-attach ke semua `.top-job-item`: saat diklik, JS mengambil `data-employees`, mem-parse JSON, mengisi `#tjm-tbody` dengan baris per karyawan (No, Nama, Technical %, Soft Skill %, Additional %), lalu memanggil `new bootstrap.Modal(document.getElementById('topJobsModal')).show()`.

Pola yang sama juga dipakai untuk `#insight-critical-focus` + `#criticalFocusModal`.

## 3. Perubahan yang Diminta

Setiap item job position pada card **"Key Position Status"** harus bisa **diklik**, dan saat diklik akan muncul **popup (modal)** yang menampilkan detail karyawan pada key position tersebut (bukan hanya angka agregat).

## 4. Tujuan

Saat ini card Key Position hanya menampilkan angka ringkasan (jumlah karyawan, terpenuhi, defisit) tanpa bisa drill-down. HR/user ingin langsung melihat **siapa saja karyawannya dan bagaimana skor kompetensinya per individu**, tanpa harus pindah halaman — konsisten dengan pengalaman yang sudah ada di "Top 5 Job Positions" dan "Critical Focus Area".

## 5. File & Komponen Terkait

- `resources/views/dashboard/dashboardTCPD.blade.php`
  - Section "Key Position Status" (~baris 565–581)
  - Blok JS `Modul 2.1: Key Position Stats` (rendering `#key-position-stats`)
  - Referensi pola: blok JS `insight-top-jobs` + modal `#topJobsModal` (~baris 432–530)
- `app/Services/Dashboard/TcpdDashboardService.php`
  - Method `getKeyPositionStats()`
  - Method `buildCompanyInsights()` (tempat `top_jobs` dengan `employees` dibangun — jadi contoh nyata cara mengambil `userSummaries` per job position)
- `app/Services/Competency/CompetencyAssessmentService.php` (method `getStrengthCompetencies()`, `getAreaDevelopmentCompetencies()`, dan kemungkinan helper lain untuk skor TC/SK/AD per user)
- `app/Http/Controllers/DashboardController.php` (method `getTcpdCompanyData`, endpoint yang mengirim payload ke frontend)
- `app/Models/MstJobPosition.php` (field `is_key_position`, scope terkait)

## 6. Catatan Teknis & Temuan Investigasi

- Backend perlu **memperluas** `getKeyPositionStats()` agar setiap item juga membawa:
  - `job_position_id` (dari `$kp->id`, sudah tersedia di query, tinggal disertakan ke hasil)
  - `employees`: array `{id, name, tc, sk, ad}` per karyawan aktif pada job position tersebut — idealnya menggunakan sumber data yang **sama** dengan yang dipakai `buildCompanyInsights()` untuk `top_jobs` (yaitu `userSummaries` dari snapshot job position), supaya angka konsisten dan tidak perlu query ulang / logic ganda. Cek apakah `getKeyPositionStats()` bisa diberi akses ke `$jobSnapshotData` yang sama, atau apakah perlu memanggil helper snapshot per job position secara terpisah (karena `is_key_position` bisa jadi tidak overlap 100% dengan `allJobNames` yang dipakai `buildCompanyInsights()`).
  - Perhatikan potensi N+1 query — `getKeyPositionStats()` saat ini sudah loop per key position lalu loop per user di dalamnya untuk hitung strength/deficit; pastikan penambahan data employee tidak menambah query per-user yang berlebihan.
- Frontend: ikuti **persis** pola `top-job-item` / `topJobsModal` — gunakan `data-*` attribute untuk membawa employee JSON, tambahkan `role="button"` + `cursor:pointer` pada card key position, buat modal baru (misal `#keyPositionModal`) dengan struktur tabel serupa (`tjm-tbody` → analognya, bisa cukup 4 kolom: No, Nama Karyawan, Strength (kompetensi terpenuhi), Deficit (kompetensi kurang) — atau tampilkan juga TC/SK/AD % jika tersedia, samakan dengan `topJobsModal` agar konsisten).
- Beri warna tema **biru** (primary) pada modal baru ini agar konsisten dengan warna card "Key Position Status" yang sudah biru (`linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%)`), berbeda dari hijau (`topJobsModal`) dan merah (`criticalFocusModal`).
- Jangan lupa: fungsi render `#key-position-stats` dipanggil di beberapa tempat (ada 2 titik pemanggilan terlihat di file — kondisi ada data & kondisi tidak ada data / error). Pastikan click handler tetap ter-attach ulang setiap kali `#key-position-stats` di-render ulang (event delegation atau re-attach setelah render, sama seperti pola `top-job-item`).

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Setiap card job position di dalam "Key Position Status" memiliki cursor pointer dan bisa diklik.
- [ ] Klik pada satu card memunculkan modal Bootstrap berisi: nama job position, ringkasan (jumlah karyawan / strength / deficit), dan **tabel per-karyawan** (nama + data kompetensi relevan).
- [ ] Jika key position tidak punya karyawan aktif (`employee_count = 0`), modal tetap bisa dibuka dan menampilkan pesan "Belum ada karyawan pada posisi ini" (bukan error).
- [ ] Tidak ada perubahan pada perilaku "Top 5 Job Positions" dan "Critical Focus Area" yang sudah berjalan (regresi nol).
- [ ] Gaya visual modal baru konsisten dengan modal lain (Bootstrap modal, `modal-dialog-scrollable`, header berwarna, tabel `table-hover table-sm`).
- [ ] Data pada modal akurat — dicocokkan manual dengan data yang tampil di card ringkasan (jumlah karyawan di modal = `employee_count` di card).

## 8. Di Luar Cakupan

- Tidak perlu menambah/mengubah cara menandai suatu job position sebagai "Key Position" (field `is_key_position` sudah ada, pengelolaannya di luar scope revisi ini).
- Tidak perlu mengubah logic perhitungan `strength_count` / `deficit_count` itu sendiri, kecuali diperlukan untuk konsistensi data yang ditampilkan di modal.

## 9. Instruksi untuk AI Agent

1. Baca dan pahami dulu kode di file-file pada bagian "File & Komponen Terkait" di atas, khususnya pola `top_jobs` + `topJobsModal` sebagai referensi wajib.
2. Buat `IMPLEMENTATION-PLAN-01.md` yang berisi: analisis final struktur data `employees` yang akan dikirim backend, daftar perubahan per file (backend & frontend), potensi risiko performa (query tambahan), dan urutan langkah implementasi.
3. Jangan langsung eksekusi kode — tunggu review implementation plan terlebih dahulu, kecuali diarahkan lain oleh user.
