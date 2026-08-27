# Revisi HR #3 — Mapping Karyawan: Notifikasi Setelah Simpan + Fitur Import Working Experience

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL, package import Excel: `maatwebsite/excel`).
Halaman: **Mapping Karyawan → Job Position** (`/hr/user-job-position`, route group `user-job-position.*`).
Controller: `app/Http/Controllers/UserJobPositionController.php`.
View: `resources/views/user_job_position/index.blade.php`.

Ada dua sub-revisi di poin ini:
- **(A)** Popup notifikasi pengingat setelah tambah/edit mapping berhasil.
- **(B)** Fitur import data Working Experience dari file (Excel).

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

### (A) Alur Simpan Mapping — Saat Ini Tanpa Popup Reminder

- `store()` (assign karyawan baru ke posisi) dan `update()` (edit mapping) di `UserJobPositionController.php` sama-sama melakukan `return back()->with('success', '...')` / `with('error', '...')`. Ini form POST/PUT biasa (bukan AJAX) — browser reload halaman, lalu pesan flash `session('success')`/`session('error')` ditampilkan sebagai Bootstrap alert statis di atas halaman (lihat `index.blade.php` baris ~23–34). **Belum ada popup/modal apa pun setelah simpan.**
- **Pola SweetAlert yang sudah baku di modul People Development (satu domain dengan HR)**, ada di `resources/views/people_development/create_develop.blade.php` dan `edit_develop.blade.php`:

```blade
@if (session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: "{{ session('success') }}",
                timer: 3000,
                showConfirmButton: false
            });
        });
    </script>
@endif
```

Pola ini WAJIB dijadikan referensi gaya (SweetAlert `Swal.fire`, bukan modal Bootstrap manual) agar konsisten dengan modul HR lainnya.

### (B) Working Experience — Sudah Ada CRUD Manual, Belum Ada Import

- Model: `app/Models/WorkingExperience.php`, tabel `working_experiences`. Kolom fillable: `user_id, year_start, year_end, job_position, section, departemen, keterangan`.
- API CRUD sudah lengkap di `UserJobPositionController.php` (blok komentar `Modul 3.1 — Working Experience CRUD`): `getWorkingExperiences()`, `storeWorkingExperience()`, `updateWorkingExperience()`, `destroyWorkingExperience()`.
- Di `index.blade.php`, tiap baris mapping punya tombol "Riwayat Jabatan" (`.btn-working-exp`) yang membuka modal `#modalWorkingExp` — form tambah manual satu-per-satu + tabel riwayat (`#we-tbody`). **Belum ada tombol/fitur import file sama sekali.**
- **Belum ada `app/Imports/WorkingExperienceImport.php`** — perlu dibuat baru.
- Package `maatwebsite/excel` sudah terpasang dan sudah dipakai luas di project ini. Contoh pola Import class yang bisa ditiru:
  - `app/Imports/EventsImport.php` — pakai `ToModel`, `WithHeadingRow`, validasi per baris dengan `Validator::make()`, skip baris invalid tanpa menghentikan seluruh proses.
  - `app/Imports/ItemCodeImport.php` — pakai `ToCollection`, `WithHeadingRow`, `SkipsEmptyRows`.

## 3. Perubahan yang Diminta

**(A)** Saat user selesai **tambah** atau **edit** mapping karyawan (berhasil), tampilkan popup notifikasi dengan pesan:

> "Jangan lupa untuk tambahkan penilaian competency untuk **{nama karyawan}** di job position **{nama job position}**"

dengan `{nama karyawan}` dan `{nama job position}` diisi data aktual dari mapping yang baru saja disimpan/diedit.

**(B)** Tambahkan fitur **import data Working Experience** dari file (Excel), dengan kolom sesuai kolom yang sudah ada pada tabel `working_experiences` (user, tahun mulai, tahun selesai, jabatan, section, departemen, keterangan).

## 4. Tujuan

**(A)** Assign/edit mapping karyawan ke job position sering menjadi langkah pertama sebelum HR melakukan penilaian kompetensi (`trs_penilaian_tcs`). Tanpa reminder, HR bisa lupa menindaklanjuti dengan input penilaian, sehingga job position tersebut tidak muncul di dashboard TCPD/People Development.

**(B)** Input working experience satu-per-satu lewat modal manual tidak praktis untuk migrasi data riwayat jabatan karyawan dalam jumlah besar (misal saat onboarding data awal HR). Import file mempercepat proses ini.

## 5. File & Komponen Terkait

- `app/Http/Controllers/UserJobPositionController.php` (`store()`, `update()`, dan bagian baru untuk import)
- `resources/views/user_job_position/index.blade.php` (form Assign, modal Edit Mapping, modal Working Experience, script JS)
- `app/Models/WorkingExperience.php`
- `app/Models/UserJobPosition.php`, `app/Models/MstJobPosition.php`, `app/Models/User.php` (relasi nama karyawan & job position)
- **Baru**: `app/Imports/WorkingExperienceImport.php`
- Referensi pola SweetAlert: `resources/views/people_development/create_develop.blade.php`
- Referensi pola Import: `app/Imports/EventsImport.php`, `app/Imports/ItemCodeImport.php`
- `routes/web.php` (group `user-job-position.*`, baris ~900–912 — perlu route baru untuk endpoint import)

## 6. Catatan Teknis & Temuan Investigasi

### Untuk (A) — Notifikasi

- Karena `store()`/`update()` adalah full-page POST/PUT (bukan AJAX), pendekatan paling konsisten dengan codebase adalah: flash session key khusus (misal `session('reminder')`) berisi pesan yang sudah diisi nama karyawan + nama job position, lalu di `index.blade.php` tambahkan blok `@if (session('reminder')) ... Swal.fire(...) @endif` mengikuti pola persis seperti di `create_develop.blade.php`.
- Ambil nama karyawan (`User::find($userId)->name`) dan nama job position (`MstJobPosition::find($positionId)->position_name`) **sebelum** redirect, supaya bisa disisipkan ke pesan flash.
- Untuk `store()`, karena bisa assign banyak karyawan sekaligus (`user_ids[]` array) ke satu job position — putuskan apakah reminder ditampilkan untuk **setiap karyawan** (misal daftar dalam satu popup) atau disederhanakan (misal hanya sebutkan job position + jumlah karyawan). Dokumentasikan keputusan ini di implementation plan karena mempengaruhi UX popup secara signifikan.
- Untuk `update()`, jelas hanya 1 karyawan + 1 job position → pesan sesuai persis format yang diminta.
- Pastikan popup **tidak muncul** saat ada error validasi/gagal simpan (gunakan flash `error` seperti biasa untuk kasus gagal, jangan campur dengan `reminder`).

### Untuk (B) — Import Working Experience

- Buat `app/Imports/WorkingExperienceImport.php` mengikuti pola `EventsImport.php` (implement `ToModel`, `WithHeadingRow`, validasi per baris via `Validator::make()`).
- Kolom Excel yang diharapkan harus sesuai kolom `working_experiences`: kemungkinan header seperti `nama_karyawan` (atau `email`/`nik` sebagai identifier — putuskan cara mencocokkan baris Excel ke `user_id`, karena tabel `working_experiences` butuh `user_id` numerik, bukan nama), `tahun_mulai`, `tahun_selesai`, `jabatan`, `section`, `departemen`, `keterangan`.
- **Penting**: karena `year_end` nullable berarti "Present" (lihat `WorkingExperience::getYearEndLabelAttribute()`), pastikan baris Excel dengan tahun selesai kosong tetap ditangani sebagai `null`, bukan `0` atau string kosong yang menyebabkan error validasi (lihat aturan validasi `updateWorkingExperience()`/`storeWorkingExperience()`: `year_end` harus `gte:year_start` jika diisi).
- Validasi per baris minimal harus meniru rule yang sudah ada di `storeWorkingExperience()` (`year_start` wajib & 4 digit, `job_position` wajib, dsb) supaya konsisten dengan input manual.
- Tangani baris gagal validasi dengan mengumpulkan error (bukan menghentikan seluruh import), lalu tampilkan ringkasan hasil import (berapa baris berhasil, berapa gagal beserta alasan) ke user — bisa lewat SweetAlert atau halaman ringkasan, sesuaikan dengan pola termudah untuk diimplementasi.
- Tambahkan route baru (mis. `POST /hr/user-job-position/api/working-experience/import`, nama route `user-job-position.api.working-experience.import`) dan tombol/form upload file baru di UI — pertimbangkan menambahkannya di area modal `#modalWorkingExp` (per-karyawan) ATAU sebagai fitur import massal terpisah di halaman index (lintas karyawan sekaligus, karena Excel bisa berisi banyak karyawan berbeda). **Pilih pendekatan "bulk import lintas karyawan"** karena lebih sesuai dengan kebutuhan migrasi data awal (bukan per satu karyawan).
- Sediakan juga link/tombol download template Excel kosong (opsional tapi direkomendasikan) agar format kolom jelas bagi user yang mengisi data.

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Setelah `store()` (assign) berhasil, muncul popup SweetAlert berisi pesan reminder sesuai format yang diminta, dengan nama karyawan & job position terisi benar.
- [ ] Setelah `update()` (edit mapping) berhasil, muncul popup SweetAlert dengan pesan reminder yang sama formatnya.
- [ ] Popup tidak muncul saat operasi gagal/validasi error.
- [ ] Ada tombol/form untuk upload file Excel import working experience di halaman Mapping Karyawan.
- [ ] Import berhasil membuat record `working_experiences` baru sesuai isi file, ter-mapping ke `user_id` yang benar.
- [ ] Baris Excel yang tidak valid (data kosong/salah format) tidak membuat seluruh proses import gagal — hanya baris tersebut yang di-skip, dan user diberi tahu baris mana saja yang gagal beserta alasannya.
- [ ] Data hasil import langsung terlihat saat modal "Riwayat Jabatan" (`#modalWorkingExp`) karyawan terkait dibuka.

## 8. Di Luar Cakupan

- Tidak mengubah struktur tabel `working_experiences` atau `user_job_positions`.
- Tidak membuat fitur export working experience (kecuali template kosong untuk import, itu opsional).
- Tidak mengubah alur penilaian kompetensi (`trs_penilaian_tcs`) itu sendiri — poin ini hanya mengingatkan, tidak mengarahkan otomatis ke form penilaian (kecuali AI Agent menilai itu peningkatan UX yang wajar untuk diusulkan di implementation plan).

## 9. Instruksi untuk AI Agent

1. Baca `UserJobPositionController.php` dan `index.blade.php` secara utuh, termasuk pola Working Experience CRUD (`Modul 3.1`) yang sudah ada, sebelum merancang perubahan.
2. Baca minimal 2 contoh `app/Imports/*.php` yang disebutkan di atas untuk memahami konvensi import di project ini.
3. Buat `IMPLEMENTATION-PLAN-03.md` yang memisahkan jelas rencana untuk sub-revisi (A) Notifikasi dan (B) Import — termasuk keputusan desain yang disebutkan di "Catatan Teknis" (terutama: cara mencocokkan baris Excel ke `user_id`, dan bentuk reminder untuk multi-assign).
4. Tunggu review implementation plan sebelum eksekusi kode.
