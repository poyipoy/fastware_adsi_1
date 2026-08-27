# IMPLEMENTATION-PLAN-06: Training Development - Dropdown Kategori (Training / Sharing Knowledge)

## Hasil Verifikasi Kode
- Kolom `is_sharing_knowledge` di database dan model `$fillable` `mst_pd_pengajuans` sudah ada namun tidak pernah difungsikan.
- Di `edit_develop_hrga.blade.php` tidak ada `<select>` untuk tipe/kategori antara Training vs Sharing Knowledge.
- Cascading dropdown Nama Karyawan saat ini bergantung pada perubahan dari Job Position. Jika Job Position di-hide, maka Nama Karyawan berpotensi gagal ter-populate.

## Daftar File & Perubahan
1. **`resources/views/people_development/edit_develop_hrga.blade.php`**
   - **HTML JS Tambah Baris**: Di fungsi `addAdditionalRow()`, tambahkan elemen `<select name="kategori_usulan[]">` dengan opsi 'Training' (default) dan 'Sharing Knowledge' tepat di bawah label "1. Data Usulan".
   - **JS Event Dropdown**: Buat fungsi listener `onchange` pada dropdown tersebut. Jika = "Sharing Knowledge", maka:
     - Container `.col-md-6` untuk "Section" dan "Job Position" di-hide (`style.display = 'none'`).
     - Remove atribut `required` dan kosongkan `.val('')` dari kedua field tersebut.
     - (Kembalikan jika memilih 'Training').
   - **Update JS Nama Karyawan (Opsi A)**: Memodifikasi sumber dropdown "Nama Karyawan" ketika Kategori = Sharing Knowledge. Kita akan menggunakan daftar karyawan global di client-side dengan me-loop variabel `availableJobPositions` yang dirender PHP (`@json`), menggabungkan semua `active_users`, me-remove duplicates ID, lalu merendernya di `<select>` Karyawan secara instan (tanpa tunggu Job Position diisi).
   - **Fix `collectFormData()`**: Tambahkan pembacaan elemen `kategori_usulan[]` ke dalam payload untuk dikirim sebagai JSON flag boolean `is_sharing_knowledge`.

2. **`app/Http/Controllers/PdController.php`**
   - Pada `updateData()`, saat mapping baris `$isNew`, tambahkan assign: `$tcPeopleDevelopment->is_sharing_knowledge = filter_var($item['is_sharing_knowledge'] ?? false, FILTER_VALIDATE_BOOLEAN);`.
   - Pastikan fallback `modified_at` aman ketika `id_job_position` null.

## Keputusan Desain & UX
- **Sumber Data Nama Karyawan**: Menggunakan Opsi A (Client-side flat-mapping dari `availableJobPositions`) untuk performa tanpa AJAX. Hal ini sah asalkan semua karyawan ter-cover dalam hierarchy job position aktif yang diexpose. 
- **Tidak mempengaruhi existing data**: Fitur dropdown "Kategori Usulan" hanya ditambahkan di `addAdditionalRow()` untuk entry baru, bukan memodifikasi UI dari table 1 dan table 2 data lama.
