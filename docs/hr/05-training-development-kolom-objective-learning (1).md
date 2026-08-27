# Revisi HR #5 — Training Development: Kolom Objective Learning di Form Review & Detail

> **Catatan Revisi Dokumen**: Versi ini diperbarui setelah verifikasi langsung ke kode (clone repo, telusuri sampai ke jalur submit/save, bukan hanya cek keberadaan elemen di DOM). Ditemukan **bug kritis** yang tidak terdeteksi di investigasi awal — lihat bagian bertanda 🔴 di Section 2, 6, dan 7. Perubahan utama: klaim "field sudah ada juga di baris additional" pada versi awal dokumen ini **kurang tepat** dan sudah dikoreksi.

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL).
Modul: **Training Development / People Development — Persetujuan Development (HRGA)**.
Model utama: `app/Models/TcPeopleDevelopment.php` (tabel `mst_pd_pengajuans`).
Controller: `app/Http/Controllers/PdController.php`.

Pemetaan istilah user ke kode (sudah dikonfirmasi lewat routing):

| Istilah User | Route | Method Controller | View |
|---|---|---|---|
| Form Review | `editPdPengajuanHRGA` | `PdController::editPdPengajuanHRGA()` | `people_development/edit_develop_hrga.blade.php` |
| Detail | `viewPD2` | `PdController::viewPD2()` | `people_development/view_develop_hrga.blade.php` |

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

- Kolom `objective_learning` **sudah ada** di database (migration `2026_07_08_100004_add_sharing_knowledge_and_objective_to_mst_pd_pengajuans.php`, kolom `text nullable`, komentar: *"Hasil yang diharapkan dari training (field di form /buat-training)"*) dan **sudah ada** di `$fillable` model `TcPeopleDevelopment`.
- **Form Create** (`people_development/create_develop.blade.php`, route `createPD`) — field `objective_learning` **sudah ada** (`<textarea name="objective_learning[]" id="objective_learning">`).
- **Form Review** (`people_development/edit_develop_hrga.blade.php`) — **koreksi atas investigasi awal**: field `objective_learning` (textarea `id="objective_learning_${item.id}" name="objective_learning[]"`, baris ~574) **hanya ada di SATU dari TIGA tempat** row-rendering di file ini:
  1. ✅ Ada — blok `existingData.forEach(...)` (baris ~356–679, disebut komentar kode "view tabel 1"), untuk baris data existing tanpa `tahun_usulan`.
  2. ❌ **Tidak ada** — blok `existingEmployeeData.forEach(...)` (baris ~679–966, "view tabel 2"), untuk baris data existing dengan `tahun_usulan`/additional lama.
  3. ❌ **Tidak ada** — fungsi `addAdditionalRow()` (baris ~966–1197), untuk baris additional **baru** yang ditambahkan lewat tombol "Tambah Data Baru". (Dokumen versi awal salah menyebut field ini ada di "baris additional yang di-generate JS" — yang dimaksud sebenarnya adalah blok #1 di atas, bukan `addAdditionalRow()`.)

  Controller `PdController.php` memang sudah memproses key `objective_learning` saat submit (baris ~467, ~737, ~781, ~793–794) — **tapi ini tidak berguna jika value-nya tidak pernah sampai ke controller sama sekali**, lihat temuan 🔴 di bawah.

  🔴 **Temuan kritis (bug, bukan sekadar gap tampilan)**: fungsi `collectFormData()` (baris ~1211–1257) — satu-satunya fungsi yang membangun payload JSON sebelum dikirim AJAX ke `updateData()` lewat `submitFormData()` — **tidak pernah membaca nilai `objective_learning[]` maupun `sharing_knowledge[]` dari DOM**, baik untuk loop `#table-body .dynamic-card` maupun `#table-body2 .dynamic-card`. Halaman ini tidak submit lewat native HTML form (`<form id="trainingForm">` ada di baris 151 tapi tidak pernah di-submit langsung — semua tombol save memanggil `submitFormData()` via JS yang membangun `FormData` sendiri dari hasil `collectFormData()`). **Akibatnya: meskipun textarea Objective Learning tampil dan bisa diedit di blok #1 (Table 1), setiap perubahan yang diketik user di field ini HILANG begitu saja saat klik Simpan** — nilai lama di database tidak ter-overwrite dengan nilai baru, karena key `objective_learning` tidak pernah ada di JSON yang dikirim ke server. Ini bug end-to-end, bukan cuma soal field belum tampil di sebagian tempat.
- **Detail** (`people_development/view_develop_hrga.blade.php`) — **BELUM ADA** kolom `objective_learning` sama sekali. Halaman ini punya **satu** elemen `<table class="styled-table">` (baris 96–327, verified — bukan dua `<table>` terpisah, koreksi atas poin di Section 6) dengan **satu** `<thead>`, menampilkan kolom: No, Section, Job Position, Nama Karyawan, Program Training, Kategori Competency, Competency, Due Date, Biaya, Lembaga, Keterangan Tujuan, Program Training Plan, Due Date Plan, Biaya Plan, Lembaga Plan, Keterangan Plan, Status — **tidak ada kolom untuk `objective_learning`**. Tabel ini merender datanya lewat **dua blok `@foreach`** berbeda (baris ~122–176 untuk data tanpa `tahun_usulan`, baris ~215–267ish untuk data dengan `tahun_usulan`/"ADDITIONAL") yang menulis ke `<tbody id="table-body">` yang sama.

**Kesimpulan investigasi (diperbarui)**: field `objective_learning` **tidak** lengkap end-to-end di form Review seperti dugaan awal — field ini hanya tampil di satu dari tiga blok render, dan bahkan di blok yang sudah tampil pun, **perubahannya tidak pernah tersimpan** karena bug di `collectFormData()`. Gap di halaman **Detail** tetap seperti temuan awal (belum ditampilkan sama sekali). Revisi ini punya **dua prioritas**, bukan satu: (a) perbaiki jalur simpan yang rusak di Form Review, (b) tambahkan kolom di Detail.

## 3. Perubahan yang Diminta

Pada halaman **persetujuan development**, di **Form Review** dan **Detail**, tambahkan kolom **Objective Learning**, sesuai dengan field yang sudah ada pada **form Create**.

## 4. Tujuan

HR yang mereview/melihat detail pengajuan training perlu tahu **hasil yang diharapkan dari training tersebut** (yang sudah diisi atasan/pengaju di form Create), agar bisa menilai relevansi pengajuan sebelum menyetujui — bukan hanya melihat nama program & lembaga training.

## 5. File & Komponen Terkait

- `resources/views/people_development/edit_develop_hrga.blade.php` (Form Review — 🔴 perbaiki fungsi `collectFormData()` (baris ~1211–1257) yang belum membaca `objective_learning[]`/`sharing_knowledge[]`; lengkapi field ini juga di blok `existingEmployeeData.forEach()` (~679–966) dan `addAdditionalRow()` (~966–1197) yang saat ini belum punya field ini sama sekali)
- `resources/views/people_development/view_develop_hrga.blade.php` (Detail — **field ini belum ada, perlu ditambahkan**)
- `app/Http/Controllers/PdController.php` — method `editPdPengajuanHRGA()`, `viewPD2()`, `update()`/`updateData()` (pastikan query yang mengisi data ke view Detail juga mengambil kolom `objective_learning`)
- `app/Models/TcPeopleDevelopment.php` (referensi field, sudah lengkap)
- `resources/views/people_development/create_develop.blade.php` (referensi tampilan field yang sudah benar di form Create)

## 6. Catatan Teknis & Temuan Investigasi

### Untuk halaman Detail (`view_develop_hrga.blade.php`)

- Tambahkan `<th scope="col" rowspan="2">Objective Learning</th>` **satu kali saja** di `<thead>` (file ini hanya punya **satu** elemen `<table>`, bukan dua — koreksi atas versi awal dokumen ini). Lalu tambahkan `<td>{{ $item->objective_learning ?? '-' }}</td>` di **kedua** blok `@foreach` (baris ~122–176 dan ~215–267ish) yang menulis ke `<tbody>` yang sama, mengikuti pola kolom lain yang sudah ada.
- 🔴 **Jangan lupa update `colspan`**: baris "Sub Total" (colspan `8` dan `5`, sekitar baris 201–204) dan baris divider "ADDITIONAL" (`colspan="17"`, baris ~209) dihitung berdasarkan jumlah total kolom saat ini (17). Menambah satu kolom baru berarti `colspan="17"` pada baris "ADDITIONAL" perlu naik jadi `colspan="18"`, atau layout baris tersebut akan bergeser/pecah. Ini detail yang mudah terlewat karena tidak langsung terlihat error di Blade, hanya salah secara visual.
- Query yang menyuplai data ke `viewPD2()` (`TcPeopleDevelopment::with('role', 'user', 'jobPosition', 'section')->...->get()`, baris ~398–407) **sudah dikonfirmasi** menggunakan `with()` tanpa `select()` eksplisit — artinya seluruh kolom (termasuk `objective_learning`) **sudah otomatis ikut ter-load**. Tidak perlu perubahan query di `viewPD2()`.
- Pertimbangkan lebar kolom — tabel ini sudah sangat lebar (17 kolom, akan jadi 18). Karena `objective_learning` adalah teks panjang (textarea), gunakan styling yang wajar (misal `max-width` + `white-space: normal` atau tooltip on hover) agar tidak merusak layout tabel yang sudah padat.

### Untuk Form Review (`edit_develop_hrga.blade.php`) — **prioritas lebih tinggi dari halaman Detail**

- 🔴 **Perbaiki dulu `collectFormData()` (baris ~1211–1257) sebelum menambah field baru di tempat lain** — ini akar masalah yang membuat field yang sudah tampil pun tidak tersimpan. Tambahkan baris berikut ke kedua object literal di dalam fungsi ini (loop `#table-body .dynamic-card`, baris ~1215–1230, dan loop `#table-body2 .dynamic-card`, baris ~1233–1254):
  ```js
  sharing_knowledge: row.find('textarea[name="sharing_knowledge[]"]').val() || '',
  objective_learning: row.find('textarea[name="objective_learning[]"]').val() || '',
  ```
  Tanpa perbaikan ini, menambah textarea di tempat lain (lihat poin berikutnya) tidak akan menyelesaikan masalah — datanya akan tetap hilang saat submit.
- Lengkapi textarea `objective_learning` (dan idealnya `sharing_knowledge`, karena mengalami bug yang identik) di **dua tempat yang saat ini belum memilikinya**:
  1. Blok `existingEmployeeData.forEach()` (baris ~679–966) — baris data existing dengan `tahun_usulan` (yang selama ini dirender di "Table 2"). Tiru persis pola textarea yang sudah ada di blok `existingData.forEach()` (baris ~566–579).
  2. Fungsi `addAdditionalRow()` (baris ~966–1197) — untuk baris additional yang baru dibuat. Tambahkan di sub-card "Tindak Lanjut Pasca Training" mengikuti pola yang sama.
- Setelah `collectFormData()` diperbaiki dan field dilengkapi di tiga tempat, controller `updateData()` (baris ~688) **tidak perlu diubah** — logic pemrosesan `item['objective_learning']`/`item['sharing_knowledge']` di baris ~737, ~781, ~793–794 sudah benar, ia hanya belum pernah menerima key ini dari payload.
- **Cara verifikasi fix**: edit nilai Objective Learning di salah satu baris existing, klik Simpan, reload halaman (`location.reload()` sudah dipanggil otomatis setelah submit sukses, lihat `submitFormData()` baris ~1298), lalu pastikan nilai baru benar-benar muncul kembali (bukan nilai lama) — ini test paling langsung untuk memastikan bug sudah tertutup, bukan sekadar cek elemen textarea ada di DOM.

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Kolom "Objective Learning" tampil di tabel halaman Detail (`view_develop_hrga.blade.php`), untuk kedua blok `@foreach` (data reguler & "ADDITIONAL") yang menulis ke tabel tersebut.
- [ ] `colspan` pada baris "Sub Total" dan baris divider "ADDITIONAL" sudah disesuaikan (17 → 18) sehingga layout tabel tidak pecah/bergeser.
- [ ] Isi kolom sesuai dengan data yang diisi user di form Create (`objective_learning`), termasuk untuk data lama/existing (bukan hanya data baru).
- [ ] 🔴 **Test end-to-end wajib**: di Form Review, edit nilai Objective Learning pada baris data existing, klik Simpan, reload halaman — nilai baru **benar-benar tersimpan** (bukan kembali ke nilai lama). Ini kriteria yang memvalidasi perbaikan `collectFormData()`, bukan sekadar cek elemen tampil di DOM.
- [ ] Textarea Objective Learning (dan `sharing_knowledge`) tersedia dan berfungsi (tampil **dan** tersimpan) di ketiga konteks baris: (a) data existing tanpa `tahun_usulan`, (b) data existing dengan `tahun_usulan`, (c) baris additional baru via `addAdditionalRow()`.
- [ ] Data kosong (`null`) ditampilkan dengan placeholder wajar (misal `-`), tidak menyebabkan error blade.
- [ ] Tidak ada regresi pada kolom-kolom lain yang sudah ada di kedua halaman ini — termasuk field lain yang juga dibaca lewat `collectFormData()` (pastikan penambahan 2 key baru ke object literal tidak mengubah urutan/format field lain yang sudah dikirim).

## 8. Di Luar Cakupan

- Tidak mengubah struktur tabel database (`objective_learning` sudah ada).
- Perbaikan `collectFormData()` di dokumen ini **secara khusus untuk key `objective_learning`/`sharing_knowledge`** — jika ditemukan field lain yang mengalami bug serupa (tampil di DOM tapi tidak terbaca `collectFormData()`), catat sebagai temuan terpisah di implementation plan, jangan diperbaiki diam-diam di luar scope revisi ini tanpa didokumentasikan.
- Tidak menambah field `sharing_knowledge` yang **baru** — kolom ini sudah ada dan sudah diproses controller (lihat migration `2026_07_08_100005`); yang diperbaiki di revisi ini hanya jalur simpannya (`collectFormData()`) dan kelengkapan tampilannya di dua blok yang belum punya field ini, konsisten dengan cakupan `objective_learning` karena keduanya mengalami bug arsitektur yang sama.

## 9. Instruksi untuk AI Agent

1. Sebelum implementasi, **jalankan sendiri verifikasi jalur save lengkap** (bukan hanya cek elemen ada di DOM): telusuri dari textarea → `collectFormData()` → `submitFormData()` → AJAX → `PdController::updateData()`. Dokumen ini sudah menemukan bahwa `collectFormData()` tidak membaca `objective_learning`/`sharing_knowledge` sama sekali — konfirmasi ulang temuan ini di kode sebelum mulai perbaikan, jangan berasumsi dokumen ini 100% masih akurat saat implementasi (kode bisa saja sudah berubah sejak dokumen ini ditulis).
2. Prioritaskan perbaikan `collectFormData()` **sebelum** menambah textarea baru di dua blok yang belum memilikinya — menambah field tanpa memperbaiki fungsi pengumpul data ini hanya memindahkan bug yang sama ke tempat baru.
3. Buat `IMPLEMENTATION-PLAN-05.md` berisi hasil verifikasi tersebut + daftar perubahan pasti yang akan dilakukan di kedua file blade dan (jika perlu) controller, termasuk cara pengujian end-to-end (edit → simpan → reload → cek nilai tersimpan) yang akan dipakai untuk memvalidasi perbaikan.
4. Tunggu review implementation plan sebelum eksekusi kode.
