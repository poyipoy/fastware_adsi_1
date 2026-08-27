# INSTRUKSI UNTUK AI AGENT — Implementasi Revisi Sistem HR
### (People Development, Dashboard TCPD, Base Competency, Training Development)

## Konteks & Instruksi Umum

Anda adalah AI Agent yang bertugas mengimplementasikan revisi berikut pada sistem HR internal perusahaan. Sistem terdiri dari 4 modul yang saling terhubung:

1. **People Development** — mencakup dataset `dsDetailCompetency` dan halaman `/dashboard-competency`
2. **Dashboard TCPD** (Training & Competency Planning Dashboard)
3. **Base Competency** — mencakup menu Mapping Karyawan
4. **Training Development** — mencakup menu List Training dan halaman `/editPengajuan-HRGA`

**Sebelum mengimplementasikan perubahan apa pun, lakukan langkah berikut:**

1. Eksplorasi struktur codebase & database terkait keempat modul di atas — termasuk struktur dataset `dsDetailCompetency`, struktur tabel "Area Development" yang sudah ada, struktur menu "Mapping Karyawan", dan halaman `/editPengajuan-HRGA` beserta section "Additional" di dalamnya.
2. Pahami pola penamaan, konvensi kode, dan struktur komponen yang sudah dipakai agar implementasi baru konsisten dengan sistem existing (bukan pola baru yang berbeda sendiri).
3. Identifikasi sumber data untuk perhitungan "nilai aktual kompetensi" vs "nilai standar kompetensi", karena logika ini dipakai berulang di beberapa fitur (lihat bagian **Cross-Module Dependencies**).
4. Untuk poin yang ditandai ambigu (lihat bagian **Pertanyaan Terbuka**), jangan berasumsi sendiri untuk hal yang berdampak besar pada struktur data — laporkan dan minta konfirmasi terlebih dahulu, **kecuali** untuk bagian yang sudah diberi "Default/Rekomendasi" di bawah, yang bisa langsung dipakai sebagai baseline implementasi sambil menunggu konfirmasi final.

Kerjakan modul per modul sesuai urutan di bawah. Setiap poin berisi: **Objective, Detail Requirement, Business Rules, UI/UX Notes, Acceptance Criteria.**

---

## MODUL 1 — PEOPLE DEVELOPMENT

### 1.1 Tabel Baru "Working Experience" pada `dsDetailCompetency`

**Objective:** Menampilkan riwayat perjalanan karir (journey) tiap karyawan, dari posisi jabatan awal sampai akhir.

**Field:**

| Field | Tipe | Keterangan |
|---|---|---|
| Year Start | Number/Year | Tahun mulai menjabat |
| Year End | Number/Year | Tahun selesai menjabat |
| Job Position | Text/Ref | Nama jabatan |
| Section | Text/Ref | Section terkait |
| Departemen | Text/Ref | Departemen terkait |
| Keterangan | Text | Catatan tambahan (free text) |

**Business Rules:**
- Tabel ini **read-only** di halaman `dsDetailCompetency`. Pengelolaan data (create/edit/delete) dilakukan melalui menu **Mapping Karyawan** (lihat 3.1) — tabel ini hanya menarik data yang sudah diinput di sana, sesuai karyawan yang bersangkutan.
- Data diurutkan kronologis (Year Start terlama → terbaru).

**Placement:** Tepat di bawah tabel **"Area Development"** yang sudah ada.

**Acceptance Criteria:**
- [ ] Tabel tampil di bawah tabel Area Development
- [ ] Data sesuai dengan input di Mapping Karyawan untuk karyawan terkait
- [ ] Urutan data kronologis
- [ ] Tidak ada tombol edit/delete di halaman ini (read-only)

---

### 1.2 Tabel Baru "Strength" pada `dsDetailCompetency`

**Objective:** Menampilkan kompetensi yang menjadi kekuatan karyawan — kebalikan dari tabel "Area Development" (yang menampilkan defisit).

**Business Rules:**
- Kriteria: kompetensi dengan **nilai aktual >= nilai standar**.
- Sumber data: sama dengan sumber data Area Development (data assessment kompetensi existing), hanya beda filter arah (Area Development = actual < standard; Strength = actual >= standard).

**Placement (Default/Rekomendasi — perlu konfirmasi urutan final):** Area Development → Strength → Working Experience.

**Acceptance Criteria:**
- [ ] Tabel Strength menampilkan seluruh kompetensi dengan nilai aktual >= nilai standar
- [ ] Struktur kolom mirror dengan tabel Area Development (nama kompetensi, nilai standar, nilai aktual, dst.)
- [ ] Data mengikuti hasil assessment terbaru (bukan data statis)

---

### 1.3 Perubahan Label Button pada `/dashboard-competency`

**Objective:** Mengubah label button **"Competency Employee"** menjadi **"Individual Profile"**.

**Business Rules:** Perubahan teks saja, tidak ada perubahan fungsi/routing.

**Acceptance Criteria:**
- [ ] Label berubah di semua tempat button ini muncul
- [ ] Fungsi/link button tidak berubah
- [ ] Tidak ada reference lain (tooltip, alt text, dsb.) yang masih pakai label lama

---

## MODUL 2 — DASHBOARD TCPD

### 2.1 Grafik Persentase "Key Position"

**Objective:** Menambahkan grafik persentase khusus untuk memantau kompetensi pada Key Position.

**Definisi Key Position:** Peran krusial yang berdampak langsung pada target operasional, keuangan, atau strategi bisnis. Simpan sebagai **master data yang bisa dikelola** (bukan hardcode di kode), agar mudah diubah ke depannya. Daftar awal:

1. Business Development
2. Sales Dept Head Region 1&2
3. Sales Dept Head Region 3&4
4. Finance Accounting & HRGA Dept Head
5. PDCA Proc Inv IT Dept Head
6. Key Account Management
7. Sales Engineer (All region, dari region1 sampai 4)
10. Production Dept Head
11. Production Heat Treatment Sect. Head
12. Machining & MC Custom Sec Head
13. Logistic & Warehouses Dept Head

**Default/Rekomendasi (perlu konfirmasi):** Requirement tidak menyebut "persentase dari apa" secara eksplisit. Default: grafik menampilkan **persentase pemegang Key Position yang kompetensinya memenuhi standar vs yang masih defisit** (konsisten dengan metric lain di dashboard TCPD).

**Acceptance Criteria:**
- [ ] Chart persentase baru khusus untuk 12 posisi di atas
- [ ] List Key Position dikelola sebagai master data
- [ ] Definisi perhitungan persentase sudah dikonfirmasi ke stakeholder

---

### 2.2 Critical Focus Area — Threshold & Pagination

**Objective:** Card "Critical Focus Area" hanya tampil untuk kompetensi dengan jumlah karyawan defisit terbanyak >= 5 orang.

**Business Rules:**
- Filter: card kompetensi hanya tampil jika jumlah karyawan defisit pada kompetensi tsb >= 5.
- Tambahkan pagination pada kumpulan card ini untuk antisipasi data banyak ke depannya.

**Edge Case:** Jika tidak ada kompetensi yang memenuhi threshold, tampilkan empty state informatif (mis. "Belum ada Critical Focus Area saat ini").

**Acceptance Criteria:**
- [ ] Card hanya tampil untuk kompetensi dengan jumlah defisit >= 5
- [ ] Pagination berfungsi baik saat card banyak
- [ ] Empty state tersedia saat tidak ada data yang memenuhi threshold

---

### 2.3 Badge "Nama Karyawan" pada Area Development

**Objective:** Menampilkan badge nama karyawan di bawah nama competency pada Area Development, agar HR tahu siapa yang bisa jadi mentor untuk karyawan yang nilainya di bawah standar.

**Business Rules:**
- Logika sama dengan tabel **Strength (1.2)**: karyawan lain dengan kompetensi sama, nilai aktual >= nilai standar.
- Bisa lebih dari 1 karyawan qualified → tampilkan sebagai multiple badge/list nama.
- Jika tidak ada karyawan yang qualified, badge tidak perlu ditampilkan.

**Cross-Dependency:** Gunakan logic/service yang sama dengan 1.2 (Strength) — buat 1 fungsi reusable, misal `GetEmployeesMeetingStandard(competencyId)`, agar tidak duplikasi logika.

**Acceptance Criteria:**
- [ ] Badge nama karyawan muncul di bawah nama competency pada Area Development
- [ ] Nama yang muncul sesuai kriteria (aktual >= standar, kompetensi sama)
- [ ] Mendukung multiple nama
- [ ] Konsisten dengan logika tabel Strength (1.2)

---

## MODUL 3 — BASE COMPETENCY

### 3.1 Mapping Karyawan — Fitur "Working Experience" (Pop-up)

**Objective:** Menambahkan pop-up tabel pada menu Mapping Karyawan untuk melihat & mengelola riwayat jabatan karyawan.

**Field (sama dengan 1.1):** Year Start, Year End, Job Position, Section, Departemen, Keterangan.

**Business Rules:**
- UI: pop-up/modal berisi tabel, dibuka per karyawan dari menu Mapping Karyawan.
- Full CRUD: tambah baris, edit, hapus, simpan.
- Ini adalah **source of truth** untuk tabel Working Experience di `dsDetailCompetency` (1.1) — pastikan relasi data ke karyawan benar (via employee ID).
- Rekomendasi validasi: Year Start <= Year End; format tahun 4 digit.

**Acceptance Criteria:**
- [ ] Pop-up bisa diakses dari menu Mapping Karyawan
- [ ] User bisa tambah, edit, hapus, dan simpan data
- [ ] Data tersimpan dan langsung sinkron dengan tampilan di `dsDetailCompetency`
- [ ] Validasi input diterapkan

---

## MODUL 4 — TRAINING DEVELOPMENT

### 4.1 Perubahan Status Pengajuan (Menu List Training)

**Objective:** Mengganti status pengajuan dari sistem tetap (Draft, On Progress, Done) menjadi **"Keterangan Status"** yang lebih deskriptif (contoh: Mencari Vendor, Proses Pendaftaran, dll).

**Default/Rekomendasi (perlu konfirmasi):** Status tetap berbentuk **dropdown/master data yang dikelola admin** (bukan free text penuh), agar data tetap konsisten untuk kebutuhan reporting/filter ke depannya.

**Migration Note:** Data pengajuan existing (status Draft/On Progress/Done) perlu dipetakan ke status baru — tentukan mapping default dan konfirmasi ke stakeholder sebelum migrasi data production.

**Acceptance Criteria:**
- [ ] Status card tidak lagi memakai 3 status tetap lama
- [ ] Status baru berbasis master data yang bisa dikelola
- [ ] Data existing berhasil dimigrasikan tanpa kehilangan histori penting

---

### 4.2 Fitur Pengaturan Tahun (Year Management)

**Objective:** Menambahkan pengaturan tahun yang membatasi tahun due date yang bisa diinput user saat mengajukan.

**Business Rules:**
- Hanya role **HR** (mis. Siti Maria Ulfa) dan **Administrator** yang bisa mengatur tahun aktif. Implementasikan berbasis **role**, bukan hardcode nama user, agar tetap scalable jika ada pergantian personel HR.
- Setelah tahun di-set, user pengaju **hanya bisa memilih due date dalam tahun tersebut** — hari & bulan tetap bebas diedit, tahun terkunci ke tahun yang di-set HR.

**Edge Case:** Tentukan behavior jika tahun belum pernah di-set (submission diblokir dengan pesan, atau default ke tahun berjalan — perlu konfirmasi).

**Acceptance Criteria:**
- [ ] Hanya role HR & Administrator yang bisa mengubah pengaturan tahun
- [ ] User biasa tidak bisa memilih due date di luar tahun yang di-set
- [ ] Hari & bulan tetap bebas diedit dalam batas tahun tsb
- [ ] Ada handling jelas untuk kondisi tahun belum di-set

---

### 4.3 Button "+ Sharing Knowledge" pada `/editPengajuan-HRGA`

**Objective:** Menambahkan button "+ Sharing Knowledge" di sebelah button "+ Tambah Baris" pada section "Additional".

**Business Rules:**
- Saat diklik, membuka form dengan struktur sama seperti form Additional, **tapi hanya bagian Plan/Usulan**.
- Field: sama seperti field Additional Plan/Usulan yang sudah ada, **kecuali field Section dan Departemen** (di-exclude).

**Catatan untuk AI Agent:** Sebelum implementasi, cek dulu field apa saja yang ada di form Additional Plan/Usulan existing sebagai baseline, lalu replikasi minus 2 field tersebut.

**Acceptance Criteria:**
- [ ] Button muncul tepat di sebelah "+ Tambah Baris" di section Additional
- [ ] Form yang terbuka sesuai struktur Plan/Usulan, minus Section & Departemen
- [ ] Data tersimpan dengan benar dan teridentifikasi sebagai entry "Sharing Knowledge"

---

### 4.4 Fitur Tampilan Tabel pada `/editPengajuan-HRGA`

**Objective:** Menambahkan tampilan data dalam bentuk tabel agar HR lebih mudah melakukan review.

**Rekomendasi tambahan (opsional, untuk didiskusikan):** Sertakan sort/filter dasar pada tabel agar benar-benar mempermudah review, bukan sekadar tampilan statis.

**Acceptance Criteria:**
- [ ] Data pengajuan tersedia dalam tampilan tabel
- [ ] Tabel menampilkan data yang relevan untuk review HR
- [ ] (Jika diimplementasikan) fitur sort/filter berfungsi baik

---

### 4.4 Field "Objective Learning (Hasil yang Diharapkan)" pada `/buat-training`

**Objective** Menyediakan field untuk menjelaskan hasil pembelajaran yang diharapkan dari training.

**Field Specification**
Label:
- Objective Learning (Hasil yang Diharapkan)
Type:
- Textarea (Free Text)
Width:
- Full Width
- Mendukung input multi-line.

**Placeholder (opsional):**
Deskripsikan ekspektasi hasil yang diharapkan.

## Cross-Module Dependencies (Keterkaitan Antar Modul)

1. **Working Experience** — dikelola di Mapping Karyawan (3.1) sebagai source of truth, ditampilkan read-only di `dsDetailCompetency` (1.1).
2. **Strength (1.2)** dan **Badge Nama Karyawan (2.3)** memakai logika perhitungan yang sama ("nilai aktual >= nilai standar kompetensi") — gunakan 1 service/fungsi reusable agar tidak duplikasi & mudah dimaintain.
3. **Year Management (4.2)** mempengaruhi validasi input pada form pengajuan yang juga dipakai di `/editPengajuan-HRGA` (4.3, 4.4) — pastikan constraint tahun diterapkan konsisten di semua entry point pengajuan, termasuk form Sharing Knowledge baru.

---

## Pertanyaan Terbuka / Perlu Konfirmasi Sebelum atau Selama Implementasi

1. **(2.1)** Grafik persentase Key Position — persentase dari apa persisnya? (Default: % memenuhi standar vs defisit)
2. **(1.2)** Urutan/posisi final tabel Strength relatif terhadap Area Development dan Working Experience.
3. **(4.1)** Apakah "Keterangan Status" berbentuk dropdown master data (rekomendasi) atau free text penuh? Serta mapping status lama → baru untuk data existing.
4. **(4.2)** Behavior sistem jika HR/Admin belum pernah set tahun aktif.
5. **(3.1)** Apakah periode Working Experience yang overlap diperbolehkan (mis. karyawan merangkap jabatan)?
6. **(1.1)** Representasi "Year End" untuk karyawan yang masih menjabat di posisi tsb (kosong / "Present" / dsb.)?

---

## Master Checklist Sebelum Dianggap Selesai

- [ ] 1.1 — Working Experience table (dsDetailCompetency)
- [ ] 1.2 — Strength table (dsDetailCompetency)
- [ ] 1.3 — Label button "Individual Profile"
- [ ] 2.1 — Grafik Key Position
- [ ] 2.2 — Critical Focus Area threshold + pagination
- [ ] 2.3 — Badge Nama Karyawan
- [ ] 3.1 — Pop-up Working Experience di Mapping Karyawan
- [ ] 4.1 — Keterangan Status
- [ ] 4.2 — Year Management
- [ ] 4.3 — Button Sharing Knowledge
- [ ] 4.4 — Tampilan tabel di editPengajuan-HRGA
- [ ] Semua pertanyaan terbuka sudah dikonfirmasi ke stakeholder terkait