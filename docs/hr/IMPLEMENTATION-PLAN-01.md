# IMPLEMENTATION-PLAN-01: Dashboard TCPD Key Position List Bisa Diklik (Popup Detail)

## Hasil Verifikasi Kode
- Tampilan HTML dan JS untuk `Key Position Status` terkonfirmasi ada di `resources/views/dashboard/dashboardTCPD.blade.php` baris ~565.
- Logic backend ada di `app/Services/Dashboard/TcpdDashboardService.php` pada method `getKeyPositionStats()`.
- Saat ini `getKeyPositionStats()` hanya memanggil count dan tidak memuat relasi/data karyawan. 
- Pola dari section `Top 5 Job Positions` (`#topJobsModal`) valid dan dapat diduplikasi.

## Daftar File & Perubahan
1. **`app/Services/Dashboard/TcpdDashboardService.php`**
   - Pada method `buildCompanyInsights` (baris ~566), lemparkan data `$jobSnapshotData` ke dalam `getKeyPositionStats`.
   - Pada method `getKeyPositionStats($jobSnapshotData = [])`, modifikasi logic untuk mencocokkan setiap `$kp->position_name` ke `$jobSnapshotData['aggregate']`. Jika ditemukan, gunakan data `userSummaries` untuk mendapatkan daftar karyawan beserta nilai persentase TC, SK, AD mereka. Jika tidak ada di snapshot, query fallback.

2. **`resources/views/dashboard/dashboardTCPD.blade.php`**
   - **HTML**: Tambahkan modal `#keyPositionModal` di bagian bawah dengan tabel: No, Nama Karyawan, Technical %, Soft Skill %, Additional %. Header tabel diberi warna biru primary (`bg-primary`).
   - **JS Render**: Pada script `Modul 2.1: Key Position Stats`, berikan `role="button"`, class `key-position-item`, dan `cursor: pointer;` untuk setiap kotak. 
   - Tambahkan `data-job-name`, `data-percentage`, dan `data-employees` (JSON stringify).
   - **JS Event**: Pasang onClick listener (menggunakan event delegation atau forEach attach) untuk membaca `data-employees`, merender tabel `<tbody>`, lalu memanggil `modal.show()`.

## Keputusan Desain / UX
- **Sumber Data Karyawan**: Menggunakan struktur data yang sama dari `$jobSnapshotData` seperti pada Top 5 Job Positions agar hasil konsisten. Persentase TC, SK, dan AD akan ditampilkan di modal.
- **Tampilan saat kosong**: Jika `employee_count = 0`, ketika diklik modal tetap terbuka namun hanya menampilkan pesan "Belum ada karyawan pada posisi ini".
- **Warna Modal**: Sesuai permintaan, modal ini akan menggunakan warna header Biru (`bg-primary`, text-white) agar berbeda dengan modal hijau/merah lainnya.
