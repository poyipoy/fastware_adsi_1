# IMPLEMENTATION-PLAN-03: Mapping Karyawan - Notifikasi Setelah Simpan & Import Working Experience

## Hasil Verifikasi Kode
- **(A)** Pada `UserJobPositionController.php`, `store()` dan `update()` menggunakan session flash standar (tanpa SweetAlert).
- **(B)** Form CRUD manual untuk Working Experience sudah ada. Route / controller untuk Excel Import belum ada. Pustaka `maatwebsite/excel` telah terinstal di proyek.

## Daftar File & Perubahan
1. **`app/Http/Controllers/UserJobPositionController.php`**
   - **(A)** Di dalam `store()` (Assign baru), setelah mapping sukses, simpan `session()->flash('reminder_mapping', ...)` berisi teks spesifik. Ambil nama posisi & array/jumlah nama karyawan (tergantung multi-assign).
   - **(A)** Di dalam `update()` (Edit Mapping), gunakan flash session serupa dengan nama 1 karyawan dan posisi.
   - **(B)** Tambahkan method `importWorkingExperience(Request $request)` yang akan memanggil class Excel import, menangkap error, dan mengembalikan status lewat session flash.

2. **`resources/views/user_job_position/index.blade.php`**
   - **(A)** Tambahkan blok `@if(session('reminder_mapping'))` yang merender script JS SweetAlert `Swal.fire` (icon info / warning halus).
   - **(B)** Tambahkan UI untuk Import Working Experience: sebuah tombol `Import Riwayat Jabatan (Excel)` di atas tabel utama (halaman Index) atau dekat tombol "Assign". Tombol ini memunculkan modal `#modalImportWe` berisi input file `.xlsx`.
   
3. **`app/Imports/WorkingExperienceImport.php` (FILE BARU)**
   - Buat class import implementasi `ToModel`, `WithHeadingRow`, `SkipsEmptyRows`.
   - Format kolom Excel yang diharapkan: `email_karyawan` (untuk dicari ID user-nya secara unik, menghindari nama kembar), `tahun_mulai`, `tahun_selesai`, `jabatan`, `section`, `departemen`, `keterangan`.
   - Gunakan `Validator` (implement `WithValidation`) sesuai rules di controller. 
   - Skip / kumpulkan baris error (implement `SkipsOnFailure`).

4. **`routes/web.php`**
   - Tambahkan route `POST /hr/user-job-position/api/working-experience/import` mengarah ke method baru di Controller.

## Keputusan Desain & UX
- **(A) Reminder untuk Multi-Assign**: Jika HR melakukan bulk assign ke > 1 karyawan, pop-up akan menyebutkan: "Jangan lupa untuk tambahkan penilaian competency untuk {Jumlah} karyawan terpilih di job position {Nama Posisi}". Ini agar teks pop-up tidak meledak panjang.
- **(B) Pengikatan ID User dari Excel**: Excel akan menggunakan kolom `email_karyawan` atau `nik` untuk mengamankan pencocokan data karena pencarian lewat string `nama_karyawan` rentan error/duplicate.
- **(B) Penempatan Import**: Import diletakkan di halaman luar (bulk untuk seluruh karyawan) sebagai fungsi bulk-migration, bukan per karyawan di dalam modal `#modalWorkingExp`.
