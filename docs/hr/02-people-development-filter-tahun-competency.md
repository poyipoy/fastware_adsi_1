# Revisi HR #2 — People Development: Tambah Filter Tahun pada dsCompetency & dsDetailCompetency

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL).
Menu "People Development" di navbar mengarah ke route `dsCompetency` (lihat `app/Enums/DashboardMenuItem.php`, case `PEOPLE_DEVELOPMENT`).

Dua halaman yang direvisi:
- **dsCompetency** — halaman pilih Job Position + radar chart kompetensi per karyawan.
- **dsDetailCompetency** — halaman detail kompetensi 1 karyawan (dibuka dari tombol "Detail" di dsCompetency).

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

### Routing & Controller

```
routes/web.php:
  GET /dashboard-competency         -> PenilaianTCController@dsCompetency       (name: dsCompetency)
  GET /dashboard-detail-competency  -> PenilaianTCController@dsDetailCompetency (name: dsDetailCompetency)
  GET /get-competency-data          -> PenilaianTCController@getCompetencyData   (dipanggil AJAX dari dsCompetency)
  GET /get-competency-filter        -> PenilaianTCController@getCompetencyFilter (dipanggil AJAX dari dsCompetency, per data_type)
  GET /get-detail-filter            -> PenilaianTCController@getDetailCompetency (dipanggil AJAX berkali-kali dari dsDetailCompetency)
```

Controller: `app/Http/Controllers/PenilaianTCController.php`

- `dsCompetency()` (baris ~308) memanggil `App\Services\Dashboard\CompetencyDashboardService::getDashboardData()` untuk mengambil daftar `$jobPositions` (job position mana saja yang punya penilaian dengan `status IN (3,4)`, berdasarkan role user). **Tidak ada filter tahun sama sekali** di sini.
- `getCompetencyData()` (baris ~1611) — query utama radar chart. Filter hanya `WHERE tpt.id_job_position = ?`. **Tidak ada filter `tahun_penilaian`.**
- `getCompetencyFilter()` (baris ~1646) — query breakdown per kategori (TC/SK/AD) saat user pilih salah satu titik radar. Filter hanya `job_position` + `data_type`. **Tidak ada filter tahun.**
- `dsDetailCompetency()` (baris ~316) — hanya return `dataTc1/2/3` (master kategori), tidak query data user sama sekali (data diambil via AJAX terpisah).
- `getDetailCompetency()` (baris ~1732) — query detail (tc_data, sk_data, ad_data, penilaians, working_experience_data, strength_data, mentor_badges) filter **hanya `WHERE tpt.id_user = ?`**. **Tidak ada filter tahun.**

### View

- `resources/views/dashboard/dsCompetency.blade.php` — dropdown `#options` untuk pilih Job Position, memanggil `updateChart()` yang AJAX ke `get-competency-data` hanya dengan param `job_position`. Tombol detail (`btnDsDetail(userId)`) redirect ke `dsDetailCompetency` hanya membawa `id_user` dan `id_job_position` di query string.
- `resources/views/dashboard/dsDetailCompetency.blade.php` — banyak pemanggilan `fetch`/`$.ajax` ke `get-detail-filter` hanya dengan param `id_user`.

### Yang PENTING: Kapasitas filter tahun SUDAH ADA di level Model, tinggal disambungkan

`app/Models/TrsPenilaianTc.php` (tabel `trs_penilaian_tcs`) sudah punya:
- Kolom `tahun_penilaian` (integer, fillable, casted `integer`).
- Scope `scopeForYear($query, int $year)` → `where('tahun_penilaian', $year)`.
- Static helper `TrsPenilaianTc::getAvailableYears(): array` → daftar tahun distinct, urut descending.
- Juga ada `scopeForCurrentYear()` dan `lockPreviousYears()` untuk konteks penguncian data tahun lama.

**Jadi pekerjaan utama revisi ini adalah menyambungkan filter tahun yang sudah tersedia di model ke seluruh alur query dan UI di dsCompetency + dsDetailCompetency, bukan membuat mekanisme baru dari nol.**

## 3. Perubahan yang Diminta

Tambahkan filter tahun (berdasarkan `tahun_penilaian` di `trs_penilaian_tcs`) pada halaman **dsCompetency** dan **dsDetailCompetency**, sehingga user bisa memilih data penilaian kompetensi untuk tahun tertentu.

## 4. Tujuan

Saat ini semua query mengambil data lintas-tahun tanpa filter, sehingga radar chart dan detail kompetensi bisa tercampur antara penilaian tahun berjalan dan tahun-tahun sebelumnya (yang sudah di-lock via `lockPreviousYears()`). User perlu bisa fokus melihat data satu tahun penilaian spesifik.

## 5. File & Komponen Terkait

- `app/Http/Controllers/PenilaianTCController.php`
  - `dsCompetency()`, `dsDetailCompetency()`, `getCompetencyData()`, `getCompetencyFilter()`, `getDetailCompetency()`
- `app/Services/Dashboard/CompetencyDashboardService.php` (`getDashboardData()`, `getJobPositions()`)
- `app/Models/TrsPenilaianTc.php` (scope `forYear`, helper `getAvailableYears()`)
- `resources/views/dashboard/dsCompetency.blade.php`
- `resources/views/dashboard/dsDetailCompetency.blade.php`
- `routes/web.php` (baris ~506–507, 592–594 — route terkait)

## 6. Catatan Teknis & Temuan Investigasi

- Gunakan `TrsPenilaianTc::getAvailableYears()` untuk mengisi dropdown tahun di kedua halaman — tidak perlu query manual baru.
- Di **dsCompetency**: tambahkan dropdown tahun di samping dropdown Job Position (`#options`). Saat tahun berubah, kirim ulang AJAX ke `get-competency-data` dengan param tambahan `tahun` (atau `tahun_penilaian`), lalu tambahkan `->where('tpt.tahun_penilaian', $tahun)` di `getCompetencyData()` dan `getCompetencyFilter()`. Pertimbangkan juga apakah daftar Job Position (`$jobPositions` dari `CompetencyDashboardService`) perlu ikut difilter tahun (misal: job position yang tidak punya penilaian di tahun terpilih tidak usah muncul di dropdown) — putuskan berdasarkan UX yang paling masuk akal, dan dokumentasikan keputusan ini di implementation plan.
- Saat redirect ke **dsDetailCompetency** dari `btnDsDetail()`, sertakan juga param tahun terpilih di query string (`?id_user=...&id_job_position=...&tahun=...`), lalu propagate ke semua pemanggilan `get-detail-filter` di `dsDetailCompetency.blade.php` (ada banyak titik pemanggilan — pastikan semua konsisten membawa param tahun yang sama, bisa disimpan di 1 variabel JS global agar tidak duplikatif).
- Di `getDetailCompetency()`, tambahkan filter tahun ke semua query yang relevan (`$tcData`, `$skData`, `$adData`, `$penilaians`). **Perhatikan**: `$dataTcPeopleDevelopment` (dari `TcPeopleDevelopment`) dan `$workingExperienceData` (dari `WorkingExperience`) **tidak** punya kolom tahun penilaian yang sama — jangan paksakan filter tahun pada dua data ini kecuali memang relevan secara bisnis (working experience adalah riwayat lintas tahun, bukan data penilaian tahunan). Putuskan dan dokumentasikan mana yang ikut difilter tahun dan mana yang tetap tampil semua.
- Sediakan default tahun yang masuk akal (misal: tahun terbaru dari `getAvailableYears()`, atau tahun berjalan `now()->year`) agar halaman tidak kosong saat pertama kali dibuka.

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Ada dropdown/selector tahun di halaman dsCompetency, terisi dari `TrsPenilaianTc::getAvailableYears()`.
- [ ] Mengubah tahun di dsCompetency memuat ulang radar chart sesuai data tahun tersebut saja.
- [ ] Ada dropdown/selector tahun di halaman dsDetailCompetency (atau tahun terbawa otomatis dari dsCompetency lewat query string — pilih pendekatan yang paling konsisten dengan pola redirect yang sudah ada).
- [ ] Semua data di dsDetailCompetency (tc_data, sk_data, ad_data, penilaians) konsisten menampilkan hanya data pada tahun terpilih.
- [ ] Working experience tetap tampil apa adanya (riwayat jabatan lintas tahun), tidak ikut terpotong oleh filter tahun penilaian — kecuali diputuskan lain di implementation plan.
- [ ] Tidak ada error saat tahun terpilih tidak memiliki data sama sekali (tampilkan state kosong yang wajar, bukan crash).

## 8. Di Luar Cakupan

- Tidak mengubah mekanisme `lockPreviousYears()` atau `is_locked`.
- Tidak mengubah struktur tabel `trs_penilaian_tcs` (kolom `tahun_penilaian` sudah ada).

## 9. Instruksi untuk AI Agent

1. Baca seluruh method terkait di `PenilaianTCController.php` dan kedua file blade sebelum mulai, untuk memastikan semua titik AJAX yang perlu diberi param tahun sudah terpetakan lengkap (jangan sampai ada satu endpoint yang terlewat).
2. Buat `IMPLEMENTATION-PLAN-02.md`: daftar semua titik kode yang diubah, keputusan desain (apakah job position list ikut difilter tahun, apakah working experience ikut difilter), dan urutan implementasi (backend dulu lalu frontend, atau sebaliknya).
3. Tunggu review implementation plan sebelum eksekusi kode.
