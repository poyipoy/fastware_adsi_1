# Revisi HR #4 — Mapping Karyawan: Tampilkan Detail Working Experience di Form Edit Mapping

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL).
Halaman: **Mapping Karyawan → Job Position** (`/hr/user-job-position`).
View: `resources/views/user_job_position/index.blade.php`.
Controller: `app/Http/Controllers/UserJobPositionController.php`.

Revisi ini adalah kelanjutan/berkaitan langsung dengan Revisi #3 (fitur Working Experience yang sudah ada), fokus pada **penempatan tampilan** working experience di form edit mapping.

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

Saat ini ada **dua modal terpisah** di `index.blade.php` untuk satu baris mapping karyawan:

1. **`#modalEditMapping`** (~baris 181–219) — dibuka lewat tombol `.btn-edit-mapping`. Isinya **hanya 2 field**: dropdown "Karyawan" (`#editUserId`) dan dropdown "Job Position" (`#editPositionId`). Tidak ada informasi lain di sini sama sekali.
2. **`#modalWorkingExp`** (~baris 221–295) — dibuka lewat tombol terpisah `.btn-working-exp` ("Riwayat Jabatan", ikon `bi-briefcase-fill`, warna ungu). Isinya form tambah riwayat + tabel riwayat jabatan (`#we-tbody`), sudah lengkap dengan CRUD (fungsi JS `weLoadData()`, `we-btn-add`, `we-btn-edit`, `we-btn-delete` — lihat blok script `Modul 3.1: Working Experience CRUD`).

Kedua modal ini **independen** — membuka Edit Mapping tidak menampilkan apa pun dari Working Experience, dan sebaliknya. User harus membuka dua modal berbeda untuk melihat kedua informasi tersebut.

Data yang dibutuhkan untuk menampilkan working experience saat edit mapping **sudah tersedia** lewat endpoint yang sudah ada:
`GET /hr/user-job-position/api/working-experience?user_id=X` (route `user-job-position.api.working-experience.index`, method `UserJobPositionController::getWorkingExperiences()`), dan fungsi JS `weLoadData(userId)` yang sudah mem-fetch dan merender tabel riwayat — tinggal dipakai ulang.

## 3. Perubahan yang Diminta

Saat user membuka **form Edit Mapping**, tampilkan juga **detail working experience** dari karyawan yang sedang diedit di dalam form/modal tersebut (tidak perlu buka modal terpisah lagi untuk sekadar melihat riwayat jabatannya).

## 4. Tujuan

Saat mengedit mapping (misal memindahkan karyawan ke job position lain), HR perlu konteks riwayat jabatan karyawan tersebut untuk memastikan perpindahan job position masuk akal / konsisten dengan riwayat kariernya. Saat ini konteks itu tidak terlihat tanpa membuka modal lain secara terpisah.

## 5. File & Komponen Terkait

- `resources/views/user_job_position/index.blade.php`
  - `#modalEditMapping` (perlu diperluas)
  - `#modalWorkingExp` dan fungsi `weLoadData(userId)` (sumber logic yang akan dipakai ulang)
  - Handler klik `.btn-edit-mapping` (perlu ditambah pemanggilan load working experience)
- `app/Http/Controllers/UserJobPositionController.php` — `getWorkingExperiences()` (endpoint yang sudah ada, kemungkinan cukup dipakai ulang tanpa perubahan)

## 6. Catatan Teknis & Temuan Investigasi

- Karena `weLoadData(userId)` sudah ada dan sudah bekerja untuk mengisi `#we-tbody` di dalam `#modalWorkingExp`, opsi implementasi termudah adalah:
  - Tambahkan section baru di dalam `#modalEditMapping` (misal tabel ringkas riwayat jabatan, read-only, di bawah dua dropdown yang sudah ada), dengan `id` tabel yang **berbeda** dari `#we-tbody` (misal `#edit-mapping-we-tbody`) supaya tidak bentrok dengan modal Working Experience yang sudah ada.
  - Modifikasi `weLoadData()` agar bisa menerima parameter target elemen tbody (atau buat fungsi kecil baru yang reuse struktur render row-nya) sehingga logic fetch tidak terduplikasi.
  - Saat handler `.btn-edit-mapping` di-klik (yang sudah ada, mengisi `#editUserId` dan `#editPositionId`), tambahkan pemanggilan load working experience untuk `userId` yang sama ke tabel baru tersebut.
- Pertimbangkan apakah tampilan working experience di form edit ini perlu **read-only** (cukup untuk konteks) atau **full-editable** (bisa tambah/edit/hapus langsung dari situ juga, seperti di `#modalWorkingExp`). Mengingat modal Working Experience terpisah (`.btn-working-exp`) sudah menyediakan CRUD lengkap, kemungkinan besar cukup **read-only ringkas** di form edit ini untuk menghindari duplikasi UI/fungsi — namun tetap bisa beri link/tombol kecil "Kelola Riwayat Jabatan" yang membuka `#modalWorkingExp` untuk user yang ingin CRUD lengkap. Dokumentasikan keputusan ini di implementation plan.
- Perhatikan ukuran modal: `#modalEditMapping` saat ini `modal-dialog-centered` (ukuran default/kecil), sedangkan tabel working experience butuh lebih banyak ruang horizontal (6 kolom: Tahun Mulai, Tahun Selesai, Jabatan, Section, Departemen, Keterangan). Kemungkinan perlu mengubah ukuran modal (misal ke `modal-lg` atau `modal-xl`) agar tabel tidak terlalu sempit.
- Reset tabel working experience saat modal ditutup atau saat dibuka untuk karyawan berbeda, supaya tidak menampilkan data karyawan sebelumnya (data lama tertinggal).

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Saat tombol Edit (`.btn-edit-mapping`) diklik, modal Edit Mapping menampilkan juga tabel/daftar riwayat jabatan karyawan yang sedang diedit.
- [ ] Data working experience yang tampil sesuai dengan karyawan yang dipilih (bukan karyawan lain / data sisa sebelumnya).
- [ ] Jika karyawan belum punya riwayat jabatan sama sekali, tampilkan pesan kosong yang wajar ("Belum ada riwayat jabatan"), bukan error.
- [ ] Mengubah dropdown "Karyawan" di dalam modal edit (jika user mengganti karyawan sebelum submit) — putuskan dan implementasikan perilaku yang wajar: apakah tabel working experience ikut ter-refresh sesuai karyawan baru yang dipilih, atau tetap menampilkan karyawan awal (dokumentasikan pilihan ini).
- [ ] Tidak ada regresi pada modal `#modalWorkingExp` yang sudah ada (tombol "Riwayat Jabatan" tetap berfungsi seperti semula).
- [ ] Submit form Edit Mapping tetap berjalan normal seperti sebelumnya (hanya update `user_id` + `mst_job_position_id`), tidak terpengaruh oleh penambahan tampilan working experience.

## 8. Di Luar Cakupan

- Tidak menghapus atau mengganti modal `#modalWorkingExp` yang sudah ada — modal ini tetap dipertahankan sebagai akses cepat "Riwayat Jabatan" dari tabel utama.
- Tidak mengubah endpoint `getWorkingExperiences()` kecuali benar-benar diperlukan (misal jika perlu parameter tambahan).

## 9. Instruksi untuk AI Agent

1. Baca ulang blok script `Modul 3.1: Working Experience CRUD` di `index.blade.php` secara utuh sebelum membuat perubahan, agar reuse logic-nya benar dan tidak menduplikasi kode secara serampangan.
2. Buat `IMPLEMENTATION-PLAN-04.md`: rencana perubahan struktur `#modalEditMapping`, keputusan read-only vs editable, dan cara reuse `weLoadData()` (refactor fungsi vs duplikasi ringan).
3. Tunggu review implementation plan sebelum eksekusi kode. Revisi ini idealnya dikerjakan **setelah** atau **bersamaan** dengan Revisi #3, karena keduanya menyentuh file `index.blade.php` yang sama.
