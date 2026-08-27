# Revisi HR #7 — Training Development: Versi Tabel Rapi (Ringkasan) di Form Review

## 1. Konteks Modul

Repo: `poyipoy/fastware_adsi_1` (Laravel 10 + Bootstrap 5 + MySQL).
Modul: **Training Development / People Development — Persetujuan Development (HRGA)**.
Halaman: **Form Review** (route `editPdPengajuanHRGA`, method `PdController::editPdPengajuanHRGA()`, view `people_development/edit_develop_hrga.blade.php`).

## 2. Kondisi Saat Ini (Sudah Diverifikasi di Kode)

- Form Review saat ini **100% berbasis card**, bukan tabel. Setiap baris data (baik "Table 1" — data tanpa `tahun_usulan`, maupun "Table 2" — data dengan `tahun_usulan`/additional) dirender sebagai `<div class="card mb-4 ...">` besar berisi **dua panel**: "1. Data Usulan" (kiri, field disabled/read-only untuk baris existing: Section, Job Position, Nama Karyawan, Program Training, Kategori Competency, Competency, Due Date, Budget, Lembaga, Keterangan Tujuan) dan "2. Tindak Lanjut / Aktual" (kanan, field editable: Nama Program Aktual, Date Aktual, Biaya Aktual, Lembaga Aktual, Keterangan Aktual, Status, Upload File, plus **Sharing Knowledge & Objective Learning** di sub-card "Tindak Lanjut Pasca Training").
- Dengan banyak baris data (kondisi normal untuk satu periode training aktif), tampilan card-per-row ini **memakan banyak scroll vertikal** dan menyulitkan HR untuk membandingkan/menyisir banyak baris sekaligus secara sepintas — setiap card butuh dibuka/di-scroll satu-per-satu untuk melihat ringkasan datanya.
- **Temuan penting**: class CSS `.styled-table` (styling tabel bergaris rapi — header biru, zebra-stripe, hover) **sudah didefinisikan** di blok `<style>` file ini sendiri (`edit_develop_hrga.blade.php`, baris ~38–68), **tapi tidak dipakai di file ini sama sekali** — tidak ada elemen `<table>` apa pun di file ini saat ini (dicek dengan grep, nihil). Class ini sama persis dipakai secara aktif di halaman Detail (`view_develop_hrga.blade.php`) untuk tabel ringkasannya. Artinya styling sudah siap pakai, tinggal dipasang ke elemen `<table>` baru.
- **Data sudah tersedia di sisi client tanpa perlu request baru**: variabel JS `existingData` (baris ~347, dari `@json($data)`) dan `existingEmployeeData` (baris ~671, sumber data sama `@json($data)`) sudah berisi seluruh data yang dibutuhkan untuk merender ringkasan tabel — tidak perlu endpoint/AJAX baru.
- **Referensi pola tabel rapi yang paling relevan sudah ada di codebase**: `view_develop_hrga.blade.php` (halaman Detail) sudah punya implementasi tabel dengan **header dua baris** (`<thead>` berisi dua `<tr>`: baris pertama untuk kolom-kolom "Usulan" dengan `rowspan="2"`, baris kedua untuk sub-kolom "Aktual/Plan" seperti Nama Program, Date Actual, Biaya Actual, Lembaga, Keterangan, Status) — struktur ini **hampir identik** dengan kebutuhan data Form Review (yang juga punya sisi "Usulan" dan sisi "Aktual"). Pola ini **wajib dijadikan referensi** karena mengurangi risiko desain ulang dari nol dan menjaga konsistensi visual antar halaman dalam modul yang sama.

## 3. Perubahan yang Diminta

Pada halaman **persetujuan development**, **Form Review**, tambahkan **versi tabel rapi** dari data-data yang ada (sebagai pelengkap tampilan card yang sudah ada, bukan pengganti), dengan tujuan mempermudah HR memahami/menyisir keseluruhan data secara sepintas.

## 4. Tujuan

Tampilan card saat ini optimal untuk **mengedit** satu baris data secara detail (banyak field, grouping usulan vs aktual, tombol salin, upload file), tapi tidak optimal untuk **membaca cepat** keseluruhan dataset. HR sering perlu melihat ringkasan seluruh pengajuan (siapa, program apa, status apa, due date kapan) sebelum memutuskan mau edit baris yang mana — tabel ringkas lebih cocok untuk kebutuhan ini, konsisten dengan pola yang sudah dipakai di halaman Detail.

## 5. File & Komponen Terkait

- `resources/views/people_development/edit_develop_hrga.blade.php`
  - Blok `<style>` (baris ~38–68) — class `.styled-table` sudah ada, tinggal dipakai.
  - Variabel JS `existingData` (baris ~347) dan `existingEmployeeData` (baris ~671) — sumber data untuk render tabel baru, reuse tanpa query ulang.
  - Area tempat container card `#table-body` (baris ~154) dan `#table-body2` (baris ~187) berada — tempat tabel ringkasan baru kemungkinan disisipkan (di atas, sebagai toggle, atau sebagai tab terpisah — lihat Catatan Teknis).
- Referensi pola: `resources/views/people_development/view_develop_hrga.blade.php` (struktur `<table class="styled-table">` dengan header dua baris, baris 96–119) — **wajib ditiru strukturnya**.
- **Tidak ada perubahan controller/backend yang dibutuhkan** — seluruh data sudah dikirim oleh `editPdPengajuanHRGA()` ke view dalam bentuk yang sama seperti dipakai card saat ini.

## 6. Catatan Teknis & Temuan Investigasi

- **Rekomendasi pendekatan**: render tabel ringkasan ini murni di sisi client (JavaScript), memakai data yang sudah ada di `existingData`/`existingEmployeeData`, dengan struktur `<table class="styled-table">` yang meniru header dua baris dari `view_develop_hrga.blade.php`. Tidak perlu endpoint baru, tidak perlu query database tambahan.
- **Cakupan kolom** — karena Form Review menyimpan field yang lebih banyak dari halaman Detail (termasuk field "Aktual" yang sudah diisi HR: Nama Program Aktual, Date Aktual, Biaya Aktual, Lembaga Aktual, Keterangan Aktual, Status), tabel ringkas ini sebaiknya mencakup kombinasi kolom Usulan + Aktual, mengikuti pola grouping header dua-baris yang sama persis dengan `view_develop_hrga.blade.php` (baris pertama = kolom Usulan dengan `rowspan="2"`, baris kedua = sub-kolom Aktual). Daftar kolom minimal yang perlu dipertimbangkan: No, Section, Job Position, Nama Karyawan, Program Training (Usulan), Kategori Competency, Competency, Due Date (Usulan), Budget (Usulan), Lembaga (Usulan), Keterangan Tujuan, lalu grup Aktual: Nama Program, Date Aktual, Biaya Aktual, Lembaga Aktual, Keterangan Aktual, Status.
- **Dependensi ke Revisi #5 dan #6**: jika Revisi #5 (kolom Objective Learning) dan Revisi #6 (dropdown Kategori Training/Sharing Knowledge) dikerjakan bersamaan atau sebelum revisi ini, tabel ringkas sebaiknya **langsung menyertakan kedua kolom tersebut** dari awal, supaya tidak perlu revisi ulang segera setelah tabel ini jadi. Jika revisi ini dikerjakan lebih dulu, cukup pastikan struktur tabel mudah ditambah kolom baru nantinya (misal `<th>` terakhir sebelum kolom Status, bukan di tengah, untuk meminimalkan perubahan pada baris data yang sudah dirender). **Rekomendasi urutan pengerjaan**: #5 → #6 → #7, supaya tabel ringkas ini bisa langsung final mencakup semua kolom yang relevan.
- **Keputusan UX yang perlu didokumentasikan di implementation plan**:
  - Apakah tabel ringkas ini **menggantikan tampilan card** (default view), **ditampilkan berdampingan** (di atas card, sebagai ringkasan sebelum detail), atau **toggle** (tombol "Tampilan Tabel" / "Tampilan Kartu" yang saling switch, keduanya pakai `existingData`/`existingEmployeeData` yang sama)? Karena card menyimpan fungsi edit-inline yang kompleks (dropdown, textarea, tombol salin, upload file) dan tabel ringkas secara alami read-only, pendekatan **toggle** kemungkinan paling aman — tidak menghilangkan fungsi edit yang sudah ada, hanya menambah mode "lihat cepat".
  - Apakah baris di tabel ringkas perlu **bisa diklik** untuk scroll/jump ke card yang bersangkutan (mempermudah transisi dari "lihat cepat" ke "edit detail")? Ini peningkatan UX yang wajar untuk diusulkan, meski tidak diminta eksplisit di teks revisi.
  - Apakah tabel gabungan "Table 1" + "Table 2" (existing + additional) ditampilkan sebagai satu tabel panjang, atau tetap dipisah dua tabel mengikuti pemisahan yang sudah ada di card (konsisten dengan pola "ADDITIONAL" divider row di `view_develop_hrga.blade.php`, baris 207–212)? Rekomendasi: ikuti pola yang sama (satu tabel, dengan baris pemisah "ADDITIONAL" seperti di Detail) untuk konsistensi visual antar halaman.
- **Performa render**: karena data sudah ada di client (`existingData`/`existingEmployeeData`, hasil `@json($data)`), render tabel tambahan ini murni manipulasi DOM di sisi browser — tidak menambah beban database maupun request AJAX. Untuk jumlah baris besar, pertimbangkan apakah `SimpleDataTables` (library yang sudah di-load di file ini, baris ~343, dipakai juga di `view_develop_hrga.blade.php` untuk fitur search/sort/export tabel Detail) sebaiknya ikut diaktifkan pada tabel baru ini untuk fitur pencarian/sorting — cek pola pemakaiannya di `view_develop_hrga.blade.php` sebagai referensi jika opsi ini diambil.
- Pastikan tabel ringkas **read-only murni** (tidak ada input/select/textarea di dalamnya) untuk menghindari duplikasi state dengan card yang sudah ada — mengedit tetap dilakukan lewat card seperti sekarang, tabel hanya untuk keperluan lihat/scan cepat.

## 7. Kriteria Penerimaan (Acceptance Criteria)

- [ ] Ada versi tabel rapi (styling `.styled-table`, konsisten dengan halaman Detail) yang menampilkan ringkasan seluruh data pengajuan di Form Review.
- [ ] Tabel mencakup kolom Usulan dan Aktual sesuai daftar yang disepakati di implementation plan.
- [ ] Data pada tabel ringkas konsisten/sinkron dengan data pada card (sumber data sama, tidak ada duplikasi logic query).
- [ ] Tabel ringkas tidak mengganggu atau merusak fungsi edit yang sudah ada di tampilan card (submit, copy-to-aktual, upload file, dsb tetap berjalan normal).
- [ ] Keputusan UX (toggle vs berdampingan vs pengganti, klik-untuk-jump, satu tabel vs dua tabel) sudah didokumentasikan dan diimplementasikan sesuai keputusan tersebut.
- [ ] Tampilan tabel tetap wajar/tidak error saat data kosong (belum ada pengajuan sama sekali untuk tahun aktif tersebut).
- [ ] Tidak ada regresi pada tampilan card yang sudah ada.

## 8. Di Luar Cakupan

- Tidak mengubah struktur data/backend — murni perubahan tampilan di sisi frontend.
- Tidak menghapus tampilan card yang sudah ada — tabel rapi ini bersifat **tambahan**, bukan pengganti, kecuali diputuskan lain di implementation plan dan disetujui.
- Tidak menambahkan kolom Objective Learning atau Kategori Training/Sharing Knowledge ke tabel ini jika Revisi #5/#6 belum dikerjakan — kolom tersebut menyusul begitu revisi terkait selesai (lihat Catatan Teknis soal urutan pengerjaan).

## 9. Instruksi untuk AI Agent

1. Baca struktur tabel di `view_develop_hrga.blade.php` (baris 96–327) secara utuh sebagai referensi wajib pola header dua-baris dan baris pemisah "ADDITIONAL", sebelum merancang tabel baru di Form Review.
2. Cek status pengerjaan Revisi #5 dan #6 (kolom Objective Learning, dropdown Kategori) — jika sudah dikerjakan, sertakan kolom-kolom tersebut langsung di rancangan tabel ini.
3. Buat `IMPLEMENTATION-PLAN-07.md` berisi: keputusan UX (toggle/berdampingan/pengganti), daftar kolom final, keputusan satu tabel vs dua tabel, dan apakah SimpleDataTables diaktifkan.
4. Tunggu review implementation plan sebelum eksekusi kode.
