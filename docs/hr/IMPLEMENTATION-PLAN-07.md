# IMPLEMENTATION-PLAN-07: Training Development - Tabel Rapi Form Review

## Hasil Verifikasi Kode
- `edit_develop_hrga.blade.php` (Form Review) sepenuhnya bergantung pada Card Layout yang memakan tempat sangat panjang.
- CSS styling tabel (`.styled-table`) sudah ada di dalam file blade (baris 38), namun tidak dipakai sama sekali di file ini.
- Pola dari halaman `view_develop_hrga.blade.php` memiliki two-line header thead yang sangat rapi dan komprehensif.

## Daftar File & Perubahan
1. **`resources/views/people_development/edit_develop_hrga.blade.php`**
   - Sisipkan satu section `<div id="table-summary-container">` yang diletakkan di atas card form usulan (sebelum loop `#table-body`).
   - Buat button "Toggle View: Tabel / Card" untuk beralih mode.
   - Buat HTML `<table class="styled-table" id="summary-table">` dengan thead 2 baris persis seperti di View Detail. Pastikan termasuk tambahan kolom `Objective Learning` (jika Revisi #5 sudah jalan).
   - Tambahkan fungsi JS `renderSummaryTable()` yang mengolah array JSON dari `existingData` dan `existingEmployeeData` menjadi string HTML `<tr>...</tr>` dan menaruhnya ke dalam `<tbody>` tabel baru ini. 
   - Row tambahan: Render baris divider `ADDITIONAL` memisahkan data pertama dan kedua agar visual tabel sesuai halaman detail.
   
## Keputusan Desain & UX
- **Toggle View (Tabel / Card)**: Kita akan menggunakan pendekatan Toggle. Secara default, hal pertama yang dilihat user adalah Tabel Rapi agar user dapat dengan cepat men-scan pengajuan. Ketika user ingin mengedit baris tertentu, mereka bisa mengklik tombol "Edit Detail (Card Mode)" dan beralih.
- **Read-Only List**: Tabel yang dirender dari JS akan bersifat Read-Only untuk memastikan data tidak redundant dengan state form Card.
- **Library DataTables**: Menggunakan instance *SimpleDataTables* (yang sudah terpanggil di code existing untuk view Detail) pada tabel ringkasan di form review ini, sehingga user bisa dengan cepat mencari nama karyawan atau status.
- **Urutan Pengerjaan**: Sangat disarankan implementasi plan ini dilakukan *terakhir* setelah plan 05 dan 06 selesai.
