# Revisi HR #6 — Training Development: Dropdown Kategori (Training / Sharing Knowledge) di Baris Additional Form Review

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL).
Modul: **Training Development / People Development — Persetujuan Development (HRGA)**.
Model utama: `app/Models/TcPeopleDevelopment.php` (tabel `mst_pd_pengajuans`).
Controller: `app/Http/Controllers/PdController.php`.
Halaman: **Form Review** (route `editPdPengajuanHRGA`, method `PdController::editPdPengajuanHRGA()`, view `people_development/edit_develop_hrga.blade.php`).

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

- Form Review punya fungsi JS `addAdditionalRow()` (`edit_develop_hrga.blade.php`, baris ~966–1197), dipicu tombol `#add-additional-btn` (baris ~190), yang menyisipkan card baru "Usulan Tambahan Baru (Additional)" ke dalam `#table-body2`.
- Di dalam card tersebut ada heading `<h6>... 1. Data Usulan</h6>` (baris 990), diikuti field: **Section** (`section_id[]`, `required`, baris 994), **Job Position** (`id_job_position[]`, `required`, baris 1002, di-populate dinamis dari pilihan Section), **Nama Karyawan** (`id_user[]`, `required`, baris 1010, di-populate dinamis dari pilihan Job Position), Program Training, **Kategori Competency** (dropdown Technical/Soft Skill/Additional/Others — ini **beda konsep** dari "kategori" yang diminta revisi ini), Competency, Due Date, Budget, Lembaga, Keterangan Tujuan.
- **Belum ada dropdown "Kategori" (Training / Sharing Knowledge) sama sekali** di card additional ini atau di mana pun pada Form Review.
- **Kolom database untuk keperluan ini SUDAH ADA namun BELUM DIPAKAI**: migration `2026_07_08_100004_add_sharing_knowledge_and_objective_to_mst_pd_pengajuans.php` menambahkan kolom `is_sharing_knowledge` (boolean, default `false`), dengan komentar migration persis: *"1 = entry dari tombol Sharing Knowledge di section Additional"*. Kolom ini juga sudah ada di `$fillable` model `TcPeopleDevelopment` (dengan docblock: *"1 = entry Sharing Knowledge (tanpa Section & Departemen)"*).
- **Namun, `is_sharing_knowledge` TIDAK DIREFERENSIKAN sama sekali** di `PdController.php` (dicek dengan grep, nihil hasil) — kolom ini murni sudah disiapkan skema-nya tapi belum pernah diisi/dibaca oleh kode manapun. Ini indikasi kuat kolom ini memang disiapkan untuk fitur persis seperti yang diminta di revisi ini.
- Kolom `section_id` dan `id_job_position` di tabel `mst_pd_pengajuans` **sudah `nullable()` di level database** (lihat migration `2026_07_01_000001_migrate_pd_pengajuan_to_ids.php`), dan method `updateData()` (yang memproses submit Form Review, `PdController.php` baris ~688) **tidak memanggil `$request->validate()`** — data diterima sebagai JSON string lalu diproses manual per-item, tanpa rule validasi wajib di level server untuk `section_id`/`id_job_position`. Artinya backend sudah longgar/toleran terhadap nilai `null` pada dua field ini; constraint `required` yang ada saat ini murni di level HTML/frontend (atribut `required` pada `<select>`).
- **Temuan arsitektur penting (berpotensi jadi blocker)**: dropdown **Nama Karyawan** (`id_user[]`) di card additional **hanya di-populate lewat cascading dari Job Position** — lihat event listener `jobPositionDropdown.addEventListener('change', ...)` (baris ~1173–1196) yang mengisi opsi karyawan dari `jp.active_users` (data karyawan aktif per job position, bersumber dari variabel `availableJobPositions`). **Tidak ada sumber data karyawan lain** (tidak ada flat list "semua karyawan aktif" yang dikirim terpisah dari `editPdPengajuanHRGA()` ke view — dicek di controller, hanya `$sections`, `$jobPositions` (nested per posisi), `$penilaians`, dan `$data` yang dikirim). **Jika Job Position disembunyikan/ditiadakan saat kategori "Sharing Knowledge" dipilih, mekanisme pengisian dropdown Nama Karyawan ikut hilang** — ini harus diputuskan solusinya sebelum implementasi (lihat Catatan Teknis).

## 3. Perubahan yang Diminta

Pada halaman **persetujuan development**, di **Form Review**, saat user menambahkan bagian **Additional** (lewat tombol "Tambah Data Baru" / `addAdditionalRow()`), tambahkan **field dropdown Kategori** di samping/dekat teks "Data Usulan", dengan pilihan:
- **Training**
- **Sharing Knowledge**

Perilaku:
- Jika kategori **Training** dipilih → tidak ada perubahan, semua field yang sudah ada tetap tampil seperti sekarang.
- Jika kategori **Sharing Knowledge** dipilih → field **Section** dan **Job Position** ditiadakan (disembunyikan/tidak wajib diisi).

## 4. Tujuan

Tidak semua entri "Additional" di Form Review merupakan training formal yang terikat pada Section/Job Position tertentu — beberapa merupakan aktivitas sharing knowledge internal yang sifatnya lintas-posisi atau tidak relevan dikaitkan ke satu job position spesifik. Memaksa pengisian Section & Job Position untuk kasus ini membuat data tidak akurat atau memaksa HR mengisi nilai yang tidak benar-benar relevan. Dropdown kategori memungkinkan form menyesuaikan field yang wajib diisi sesuai jenis entri.

## 5. File & Komponen Terkait

- `resources/views/people_development/edit_develop_hrga.blade.php`
  - Fungsi JS `addAdditionalRow()` (baris ~966–1197) — tempat dropdown Kategori baru ditambahkan, dan tempat logic show/hide Section & Job Position diimplementasikan.
  - Fungsi JS `collectFormData()` (baris ~1211–1257) — **harus** disertakan mengirim nilai kategori/`is_sharing_knowledge` untuk baris additional baru (lihat Catatan Teknis, ini bagian yang sering terlewat).
- `app/Http/Controllers/PdController.php` — method `updateData()` (baris ~688), khususnya blok `$isNew` (baris ~703–753) yang membuat record baru dari baris additional — perlu menambahkan pemrosesan `is_sharing_knowledge`.
- `app/Models/TcPeopleDevelopment.php` — kolom `is_sharing_knowledge` sudah ada di `$fillable`, tidak perlu perubahan skema.
- Migration referensi (tidak perlu diubah, hanya referensi): `database/migrations/2026_07_08_100004_add_sharing_knowledge_and_objective_to_mst_pd_pengajuans.php`.

## 6. Catatan Teknis & Temuan Investigasi

- **Penempatan dropdown**: tambahkan `<select>` baru (misal `name="is_sharing_knowledge[]"` atau `name="kategori_usulan[]"`, putuskan penamaan di implementation plan) tepat di sebelah/bawah heading `<h6>1. Data Usulan</h6>` (baris 990 di `addAdditionalRow()`), dengan opsi `Training` (default/selected) dan `Sharing Knowledge`.
- **Show/hide Section & Job Position**: gunakan event listener `change` pada dropdown Kategori baru ini. Saat "Sharing Knowledge" dipilih:
  - Sembunyikan (`display:none` pada wrapper `.col-md-6` masing-masing) dropdown Section (`section_id_${tempId}`) dan Job Position (`id_job_position_${tempId}`).
  - Hapus atribut `required` dari kedua dropdown tersebut saat disembunyikan (dan kembalikan `required` saat kategori diubah balik ke "Training"), supaya form tidak gagal submit karena field tersembunyi dianggap kosong oleh validasi HTML5.
  - Kosongkan (`.val('')`) nilai kedua dropdown saat disembunyikan, supaya tidak ada data section/job position "nyangkut" yang tidak sengaja ikut ter-submit.
- **Wajib diputuskan — sumber data dropdown Nama Karyawan saat kategori Sharing Knowledge**: karena dropdown Nama Karyawan saat ini hanya bisa diisi lewat cascading Section → Job Position → `active_users`, opsi yang perlu dipertimbangkan dan didokumentasikan keputusannya di implementation plan:
  - **Opsi A** — Bangun daftar karyawan flat (deduplicate) di sisi client dari `availableJobPositions[].active_users` begitu halaman dimuat, dan gunakan daftar ini untuk mengisi dropdown Nama Karyawan langsung (tanpa menunggu Section/Job Position) saat kategori = Sharing Knowledge. Tidak perlu endpoint baru, murni JS.
  - **Opsi B** — Tambahkan endpoint/route baru yang mengembalikan seluruh user aktif (mirip pola `getWorkingExperiences()` di modul Mapping Karyawan), lalu fetch AJAX saat kategori diganti ke Sharing Knowledge.
  - **Rekomendasi awal**: Opsi A lebih sederhana dan tidak menambah request AJAX baru, tapi perlu dipastikan `active_users` di `availableJobPositions` sudah cukup representatif untuk seluruh karyawan (bukan hanya karyawan yang punya job position ter-assign) — verifikasi ini sebelum memutuskan.
- **Wiring `is_sharing_knowledge` ke backend**: di `updateData()` blok `$isNew` (baris ~703–753), tambahkan baris serupa pola field lain: `$tcPeopleDevelopment->is_sharing_knowledge = filter_var($item['is_sharing_knowledge'] ?? false, FILTER_VALIDATE_BOOLEAN);`. Karena kolom ini punya `default(false)` di migration, baris existing yang sudah ada tidak perlu disentuh — cukup pastikan baris additional baru mengisinya dengan benar.
- **`collectFormData()` harus ikut diperbarui** (baris ~1211–1257, khususnya blok `#table-body2 .dynamic-card` baris ~1233–1254) untuk menyertakan nilai dropdown Kategori/`is_sharing_knowledge` baru ke payload JSON yang dikirim ke `updateData()` — **field baru yang ditambahkan ke DOM tapi tidak dibaca di `collectFormData()` tidak akan pernah tersimpan** (lihat temuan serupa yang ditemukan pada field `objective_learning`/`sharing_knowledge` di Revisi #5, gejala persis sama).
- Pertimbangkan apakah field lain yang bergantung pada Job Position (misal validasi `modified_at` yang di-set dari `MstJobPosition::find($item['id_job_position'])->getApproverPosition(2)` di controller, baris ~740–748) perlu fallback yang wajar saat `id_job_position` kosong (kategori Sharing Knowledge) — saat ini kode sudah punya `else { $tcPeopleDevelopment->modified_at = $userName; }` sebagai fallback, jadi kemungkinan besar aman, tapi verifikasi ulang perilaku ini di implementation plan.
- Scope revisi ini **khusus baris Additional baru** (`addAdditionalRow()`), sesuai permintaan eksplisit di teks revisi ("saat menambahkan bagian additional"). Baris data existing (Table 1 & Table 2 yang sudah ada dari pengajuan awal) **tidak** perlu diberi dropdown Kategori ini kecuali diarahkan lain.

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Saat user klik "Tambah Data Baru", card additional baru menampilkan dropdown Kategori (Training / Sharing Knowledge) di dekat heading "Data Usulan", default terpilih "Training".
- [ ] Memilih kategori "Training" tidak mengubah tampilan/perilaku field apa pun (perilaku sama seperti sebelum revisi ini).
- [ ] Memilih kategori "Sharing Knowledge" menyembunyikan field Section dan Job Position, dan keduanya tidak lagi wajib diisi untuk submit berhasil.
- [ ] Dropdown Nama Karyawan tetap bisa diisi dengan benar meskipun kategori "Sharing Knowledge" dipilih (Section & Job Position tersembunyi) — sesuai opsi yang diputuskan di implementation plan.
- [ ] Data `is_sharing_knowledge` tersimpan dengan benar ke database sesuai kategori yang dipilih user saat submit.
- [ ] Baris additional dengan kategori "Sharing Knowledge" berhasil disimpan tanpa error meskipun `section_id`/`id_job_position` kosong.
- [ ] Tidak ada regresi pada baris additional dengan kategori "Training" maupun pada baris data existing di kedua tabel.

## 8. Di Luar Cakupan

- Tidak menambahkan dropdown Kategori ini ke baris data existing (Table 1 / Table 2) — hanya baris Additional baru.
- Tidak mengubah struktur tabel database (`is_sharing_knowledge` sudah ada).
- Tidak mengubah tampilan Detail (`view_develop_hrga.blade.php`) — jika kolom Kategori ini juga perlu tampil di Detail, itu perlu revisi terpisah.
- Tidak membuat endpoint baru untuk daftar karyawan kecuali Opsi B (lihat Catatan Teknis) yang dipilih sebagai pendekatan implementasi.

## 9. Instruksi untuk AI Agent

1. Baca ulang fungsi `addAdditionalRow()` dan `collectFormData()` secara utuh di `edit_develop_hrga.blade.php` sebelum implementasi, untuk memastikan integrasi dropdown baru tidak merusak alur cascading Section → Job Position → Nama Karyawan yang sudah ada.
2. Putuskan dan dokumentasikan pendekatan pengisian dropdown Nama Karyawan saat kategori Sharing Knowledge (Opsi A vs Opsi B, lihat Catatan Teknis) — ini keputusan arsitektur yang mempengaruhi cakupan perubahan secara signifikan.
3. Buat `IMPLEMENTATION-PLAN-06.md` berisi keputusan di atas, daftar perubahan pasti di blade & controller, dan penamaan field/parameter baru yang akan dipakai.
4. Tunggu review implementation plan sebelum eksekusi kode.
