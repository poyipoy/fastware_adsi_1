# Implementasi Revisi Sistem Human Resource (TCPD, People Development, Base Competency, Training Development)

Rencana implementasi ini dirancang berdasarkan dokumen **Revision TCPD.md** serta keputusan arsitektur dan business flow yang telah disepakati bersama pengguna melalui sesi interview interaktif (`/grill-me`). Implementasi mencakup 4 modul utama yang saling terhubung dan dirancang dengan pendekatan modular, reusable, serta mempertahankan kompatibilitas penuh terhadap data dan fitur existing.

---

## Ringkasan Kesepakatan & Requirement Final

1. **Working Experience (Modul 1.1 & 3.1):**
   - Ditampilkan sebagai tabel read-only pada profil individu (`dsDetailCompetency`), diurutkan secara kronologis berdasarkan `Year Start`.
   - Dikelola melalui modal/pop-up CRUD pada menu **Mapping Karyawan** (`/hr/user-job-position`).
   - Periode jabatan yang masih dijabat saat ini (`Year End == null`) ditampilkan sebagai **"Present"** di antarmuka.
   - Overlap periode jabatan diperbolehkan untuk mendukung fleksibilitas rangkap jabatan/transisi karir.

2. **Tata Letak Halaman Profil (`dsDetailCompetency` - Modul 1.2):**
   - Urutan penempatan tabel yang diterapkan: **Strength → Area Development → Working Experience → Histori Development**.
   - Tabel **Strength** menampilkan kompetensi di mana Nilai Aktual $\ge$ Nilai Standar.

3. **Perubahan Label Button (Modul 1.3):**
   - Mengubah teks tombol `"Competency Employee"` menjadi **`"Individual Profile"`** pada `/dashboard-competency`.

4. **Grafik Persentase Key Position (Modul 2.1):**
   - Menambahkan grafik persentase **% Memenuhi Standar vs % Defisit** pada posisi-posisi kunci.
   - Master data posisi kunci dikelola dinamis menggunakan kolom baru `is_key_position` (boolean) pada tabel `mst_job_positions` (dengan default seeding 12 posisi kunci awal).

5. **Critical Focus Area (Modul 2.2):**
   - Menerapkan threshold/filter di mana card hanya tampil jika jumlah defisit karyawan $\ge 5$ orang.
   - Menambahkan fitur **Pagination** serta **Empty State** informatif jika tidak ada kompetensi yang memenuhi kriteria.

6. **Badge Nama Karyawan Mentor (Modul 2.3):**
   - Menampilkan badge nama karyawan lain yang memenuhi syarat (aktual $\ge$ standar pada kompetensi yang sama) di bawah nama kompetensi pada tabel Area Development sebagai acuan mentor.

7. **Keterangan Status Training (Modul 4.1):**
   - Menampilkan status deskriptif (`status_2` / `TrainingStatus::colorConfig()`: *Mencari Vendor, Proses Pendaftaran, On Progress, Done, Pending, Ditolak*) pada Menu List Training dan `/editPengajuan-HRGA`.

8. **Year Management (Modul 4.2):**
   - Fitur pengaturan Tahun Aktif Pengajuan Training khusus untuk role **HR** dan **Administrator**.
   - Pengaju hanya dapat memilih due date/tahun usulan sesuai tahun aktif yang diset HR (jika belum diset, default ke tahun berjalan `date('Y')`).

9. **Button "+ Sharing Knowledge" pada `/editPengajuan-HRGA` (Modul 4.3):**
   - Menambahkan tombol **`+ Sharing Knowledge`** di sebelah tombol `+ Tambah Baris` pada section Additional.
   - Membuka form yang sama persis dengan form Plan/Usulan Additional namun **tanpa field Section & Departemen** (`is_sharing_knowledge = 1`).

10. **Tampilan Tabel Review pada `/editPengajuan-HRGA` (Modul 4.4 - bagian 1):**
    - Menambahkan tombol toggle/tab **"Tabel Review"** yang merangkum seluruh pengajuan dalam format tabel interaktif dengan fitur Search, Filter (Section/Status), dan Sorting.

11. **Field "Objective Learning" pada `/buat-training` (Modul 4.4 - bagian 2):**
    - Menambahkan textarea full-width **"Objective Learning (Hasil yang Diharapkan)"** pada form pembuatan training baru (`createPD`).

---

## Daftar File yang Akan Dibuat atau Dimodifikasi

### 1. Database & Migrations
- `database/migrations/2026_07_08_000001_create_working_experiences_table.php` *(NEW)*
- `database/migrations/2026_07_08_000002_add_is_key_position_to_mst_job_positions_table.php` *(NEW)*
- `database/migrations/2026_07_08_000003_create_mst_pd_active_years_table.php` *(NEW)*
- `database/migrations/2026_07_08_000004_add_sharing_knowledge_and_objective_learning_to_pd_tables.php` *(NEW)*

### 2. Models
- `app/Models/WorkingExperience.php` *(NEW)*
- `app/Models/MstPdActiveYear.php` *(NEW)*
- `app/Models/MstJobPosition.php` *(MODIFY)*
- `app/Models/TcPeopleDevelopment.php` *(MODIFY)*

### 3. Services & Helpers
- `app/Services/Competency/CompetencyAssessmentService.php` *(NEW - reusable logic untuk Strength & Badge Mentor)*
- `app/Services/Dashboard/TcpdDashboardService.php` *(MODIFY - penambahan matriks Key Position & threshold/pagination Critical Focus Area)*

### 4. Controllers
- `app/Http/Controllers/PenilaianTCController.php` *(MODIFY)*
- `app/Http/Controllers/UserJobPositionController.php` *(MODIFY)*
- `app/Http/Controllers/DashboardController.php` *(MODIFY)*
- `app/Http/Controllers/PdController.php` *(MODIFY)*

### 5. Views & UI Components
- `resources/views/dashboard/dsCompetency.blade.php` *(MODIFY)*
- `resources/views/dashboard/dsDetailCompetency.blade.php` *(MODIFY)*
- `resources/views/dashboard/dashboardTCPD.blade.php` *(MODIFY)*
- `resources/views/user_job_position/index.blade.php` *(MODIFY)*
- `resources/views/people_development/dept_develop_index.blade.php` *(MODIFY)*
- `resources/views/people_development/hrga_develop_index.blade.php` *(MODIFY)*
- `resources/views/people_development/edit_develop_hrga.blade.php` *(MODIFY)*
- `resources/views/people_development/form_evaluasi.blade.php` / view create training *(MODIFY)*

---

## Langkah Verifikasi Pasca-Implementasi

1. **Automated Testing & Migration Check:**
   - Menjalankan migrasi database dan memeriksa integritas skema (tanpa merusak data existing).
   - Memastikan servis perhitungan kompetensi menghasilkan struktur data yang akurat.
2. **Manual UI & Flow Verification:**
   - Mengecek urutan tabel pada `dsDetailCompetency` dan tampilan label tombol di `/dashboard-competency`.
   - Menguji interaksi modal CRUD Working Experience di menu Mapping Karyawan dan verifikasi sinkronisasinya ke halaman profil individu.
   - Mengecek filter threshold $\ge 5$ orang dan pagination pada Critical Focus Area serta grafik Key Position di Dashboard TCPD.
   - Menguji input form baru (`+ Sharing Knowledge`, `Objective Learning`), pembatasan Year Management, dan fungsionalitas fitur Search/Filter/Sort pada Tabel Review `/editPengajuan-HRGA`.
