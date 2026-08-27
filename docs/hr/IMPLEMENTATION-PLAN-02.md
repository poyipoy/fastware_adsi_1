# IMPLEMENTATION-PLAN-02: People Development Tambah Filter Tahun pada dsCompetency & dsDetailCompetency

## Hasil Verifikasi Kode
- Parameter tahun belum diteruskan ke query `getCompetencyData()`, `getCompetencyFilter()`, dan `getDetailCompetency()` di `PenilaianTCController`.
- Kolom `tahun_penilaian` dan scope sudah ada di model `TrsPenilaianTc` dan fungsi static `getAvailableYears()` tersedia.
- Halaman `dsCompetency.blade.php` dan `dsDetailCompetency.blade.php` menggunakan pemanggilan AJAX ke route yang perlu di-filter.

## Daftar File & Perubahan
1. **`app/Http/Controllers/PenilaianTCController.php`**
   - `getCompetencyData()`: Tambahkan `$tahun = $request->tahun;` dan filter `$query->where('tpt.tahun_penilaian', $tahun)`.
   - `getCompetencyFilter()`: Tambahkan hal yang sama.
   - `getDetailCompetency()`: Tambahkan filter `$tahun` untuk query `$tcData`, `$skData`, `$adData`, dan `$penilaians`. Pengecualian pada Working Experience.

2. **`resources/views/dashboard/dsCompetency.blade.php`**
   - Tambahkan elemen `<select id="filter-tahun">` di header / di samping dropdown Job Position, populasi dengan `TrsPenilaianTc::getAvailableYears()`.
   - Update AJAX di `updateChart()` untuk mengambil `$('#filter-tahun').val()` dan mengirimkan parameter `tahun`.
   - Update URL pada tombol Detail `btnDsDetail(userId)` untuk menyertakan `&tahun=` di querystring.

3. **`resources/views/dashboard/dsDetailCompetency.blade.php`**
   - Tambahkan `<select id="filter-tahun">` untuk tahun di header filter.
   - Tangkap parameter `tahun` dari URL, set sebagai nilai default `<select>`.
   - Update semua panggilan AJAX (`fetch` / `$.ajax`) ke endpoint dengan menyisipkan `&tahun=`.
   - Tambahkan event listener agar bila mengubah tahun di dropdown, semua data memuat ulang.

## Keputusan Desain & UX
- **Data Working Experience**: Data riwayat jabatan (working experience) TIDAK akan difilter tahun. Alasan: riwayat pekerjaan adalah data akumulatif historis per karyawan, bukan data tahunan spesifik terkait penilaian kompetensi.
- **Default Tahun**: Default tahun dipilih dari tahun paling baru yang ada (index 0 dari list `getAvailableYears()`).
- **Job Position List**: List Job Position pada dropdown tidak ikut difilter tahun, untuk mempermudah navigasi UI agar pilihan tidak muncul-hilang saat mengganti tahun.
