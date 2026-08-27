# IMPLEMENTATION-PLAN-05: Training Development - Kolom Objective Learning di Form Review & Detail

## Hasil Verifikasi Kode
- **Bug Kritis di Form Review**: Kolom `objective_learning` sudah ada dan muncul di satu bagian form (data existing table 1), namun perubahannya tidak tersimpan saat form di-submit. 
- Analisis menemukan fungsi `collectFormData()` tidak memproses pembacaan value untuk elemen textarea `objective_learning[]` dan `sharing_knowledge[]`. Akibatnya, data ini hilang saat controller menerima POST data.
- Kolom ini juga belum ditambahkan di card untuk Additional Table 2 dan `addAdditionalRow()`.
- Di Halaman **Detail** (`view_develop_hrga.blade.php`), kolom `objective_learning` sama sekali belum dirender di tabel.

## Daftar File & Perubahan
1. **`resources/views/people_development/edit_develop_hrga.blade.php`** (Prioritas Utama)
   - **Fix `collectFormData()`**: Tambahkan ekstraksi value dari `textarea[name="objective_learning[]"]` dan `sharing_knowledge[]` ke dalam pembentukan payload JSON di dalam blok loop `.dynamic-card`.
   - **Tampilan form**: Lengkapi elemen `<textarea name="objective_learning[]">` (dan `sharing_knowledge[]` jika belum ada) pada blok JS `existingEmployeeData.forEach()` dan `addAdditionalRow()` persis seperti pola di `existingData`.

2. **`resources/views/people_development/view_develop_hrga.blade.php`**
   - **Header Tabel**: Tambahkan `<th scope="col" rowspan="2">Objective Learning</th>` pada blok `<thead>` (setelah kolom "Keterangan Tujuan" atau sesuai kesepakatan susunan kolom).
   - **Row Data**: Tambahkan `<td>{{ $item->objective_learning ?? '-' }}</td>` di kedua blok `@foreach` (data biasa dan "ADDITIONAL").
   - **Update Colspan**: Ubah `colspan` pada "Sub Total" dan divider row "ADDITIONAL" yang semula 17 menjadi 18.

## Keputusan Desain & UX
- **Layout Kolom di Detail**: Mengingat tabel di halaman detail sudah punya sangat banyak kolom (sekarang 18), styling `white-space: normal; min-width: 150px;` akan diaplikasikan pada kolom `objective_learning` agar teks panjang memuat ruang dan membungkus dengan baik tanpa melebar tak terbatas.
- **Validasi End-to-End**: Harus di-tes edit -> save -> reload, agar dapat diverifikasi perbaikan `collectFormData` berhasil menyimpan update terbaru dari textarea ke database.
