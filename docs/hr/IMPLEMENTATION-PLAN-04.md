# IMPLEMENTATION-PLAN-04: Mapping Karyawan - Detail Working Experience di Edit Mapping

## Hasil Verifikasi Kode
- Saat ini `#modalEditMapping` dan `#modalWorkingExp` dipanggil terpisah.
- Terdapat fungsi global JS `weLoadData(userId)` di `index.blade.php` yang mengisi `#we-tbody` dalam `#modalWorkingExp`.

## Daftar File & Perubahan
1. **`resources/views/user_job_position/index.blade.php`**
   - Modifikasi struktur HTML `#modalEditMapping` agar lebih lebar (menggunakan class `modal-lg` atau `modal-xl`).
   - Di bawah input dropdown form edit, tambahkan tabel *read-only* dengan `<tbody id="edit-mapping-we-tbody">`.
   - Modifikasi click handler pada tombol Edit (`.btn-edit-mapping`):
     Panggil fungsi `weLoadDataForEdit(userId)` setiap kali modal edit dibuka atau nilai karyawan di dropdown berubah.
   - Buat fungsi pembantu `weLoadDataForEdit(userId)` (atau refactor `weLoadData` agar menerima parameter ID tbody dan actions boolean). Fungsi baru ini akan me-render daftar riwayat kerja ke `edit-mapping-we-tbody` *tanpa tombol Action (CRUD)*.

## Keputusan Desain & UX
- **Mode Tabel (Read-only)**: Tabel Working Experience pada form edit ini murni bersifat read-only. Tidak akan ada tombol "Hapus" atau "Edit" agar modal Edit Mapping tetap bersih fokus pada mapping posisi, namun informatif soal masa lalu si karyawan.
- **Tampilan saat tidak ada riwayat**: Jika data kosong, akan menampilkan satu baris `colspan` dengan text "Belum ada riwayat jabatan" untuk menghindari UI kosong tanpa kejelasan.
- **On Change Employee**: Jika dalam modal edit, HR mengganti dropdown "Karyawan", maka tabel Working Experience di bawahnya akan secara otomatis ter-refresh ke data karyawan yang baru (dengan listener `onchange` pada `#editUserId`).
