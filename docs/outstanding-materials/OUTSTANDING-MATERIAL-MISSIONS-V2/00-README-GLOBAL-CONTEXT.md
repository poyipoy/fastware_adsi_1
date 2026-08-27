# MASTER MISSION PACK V2
## Revisi Outstanding Material — Invoice-Centric Workflow + Sales Access

---

# CARA MENGGUNAKAN

Eksekusi mission secara berurutan:

```text
MISSION 00 → Baseline Audit
MISSION 01 → Access Control Foundation
MISSION 02 → Read-Only Index
MISSION 03 → Invoice Detail Workspace
MISSION 04 → Invoice Documents Workflow
MISSION 05 → Form Cleanup & Data Consistency
MISSION 06 → Sticky Table & Frontend Reuse
MISSION 07 → Tests & Final Integration
```

Aturan eksekusi:

1. Jalankan hanya satu mission dalam satu sesi kerja.
2. Jangan mengerjakan mission berikutnya sebelum acceptance criteria mission aktif terpenuhi.
3. Setiap mission harus berakhir dengan laporan perubahan dan hasil test.
4. Jangan menghapus atau menimpa perubahan lain yang tidak berkaitan.
5. Jangan melakukan refactor di luar scope mission.
6. Gunakan branch kerja yang sama agar hasil mission sebelumnya tetap tersedia.
7. Jangan melakukan commit atau push kecuali diminta.
8. Jangan mengklaim test berhasil jika test tidak dijalankan.

Untuk sesi agent yang benar-benar terpisah, sertakan bagian **Global Project Context** bersama mission yang akan dieksekusi.

---

# GLOBAL PROJECT CONTEXT

## Status Dokumen dan Source of Truth

Dokumen ini menggabungkan:

1. Struktur eksekusi bertahap dan security-first dari Master Mission Pack.
2. Temuan audit repository yang lebih presisi dari mission gabungan.
3. Keputusan bisnis final yang mengikuti requirement terbaru.

Seluruh nomor baris, jumlah baris file, dan posisi method yang disebut dalam dokumen ini adalah **snapshot hasil inspeksi repository**. Sebelum menulis patch, agent wajib membaca ulang kode aktual.

Aturan konflik:

```text
Kode aktual menentukan lokasi dan signature implementasi.
Dokumen ini menentukan objective, security invariant, capability, dan acceptance criteria.
```

Apabila nomor baris berubah, jangan menggagalkan mission dan jangan memaksakan patch berdasarkan nomor baris. Cari method, route name, selector, atau symbol yang relevan.

## Repository

```text
Repository : poyipoy/fastware_adsi_1
Branch     : main
Framework  : Laravel 10
Database   : MySQL
Frontend   : Blade, Bootstrap 5.3.2, jQuery, DataTables
Module     : Outstanding Material
```

## File Utama

Pelajari file berikut sebelum mengubah implementasi:

```text
app/Http/Controllers/OutstandingMaterialController.php
app/Models/OutstandingMaterial.php
app/Models/User.php
app/Models/UserJobPosition.php
app/Models/MstJobPosition.php
app/Models/MstDepartment.php
app/Enums/ProcurementMenuAccessGroup.php
app/Services/ProcurementMenuService.php
app/Http/Middleware/RoleMiddleware.php
resources/views/layout.blade.php
resources/views/outstanding_materials/index.blade.php
resources/views/outstanding_materials/show.blade.php
resources/views/outstanding_materials/invoice.blade.php
resources/views/outstanding_materials/form.blade.php
routes/web.php
database/migrations/2026_06_04_000001_split_outstanding_material_attachments.php
```

## Snapshot Repository yang Sudah Terverifikasi

Gunakan temuan ini sebagai orientasi awal, lalu verifikasi ulang terhadap kode aktual:

### Controller dan Authorization Existing

- `OutstandingMaterialController` saat audit memuat seluruh flow index, CRUD, invoice grouping, attachment, import/export, dan DataTables.
- Konstanta `MANAGER_NAMES` berisi:

```text
ADMINISTRATOR
ADMINSTRATOR
ILYAS NOOR FIRDAUS
```

- Typo legacy `ADMINSTRATOR` jangan dihapus tanpa audit data user karena mungkin masih dipakai.
- `canManageOutstandingMaterials()` juga memiliki administrator bypass melalui `role_id === 1`.
- Existing viewer access lebih luas daripada manager access. Whitelist `OUTSTANDING_MATERIAL` mencakup administrator serta:

```text
ILYAS NOOR FIRDAUS
JESSICA PAUNE
FAJAR BAGASKARA
VIVIAN ANGELIKA
```

Jessica, Fajar, dan Vivian adalah contoh legacy viewer yang harus tetap bisa melihat modul, tetapi tidak otomatis boleh manage atau download PL/MTC.

### Security Gap Existing

`updateInvoiceFields()` saat audit hanya memastikan setiap `material_ids.*` ada di tabel. Method tersebut belum memastikan bahwa seluruh ID berasal dari `number_invoice` yang dikirim.

Konsekuensi:

```text
material_ids dari invoice B dapat dikirim bersama invoice A
dan berpotensi ikut ter-update.
```

Perbaikan cross-invoice ownership adalah mandatory, bukan optional refactor.

### Detail dan Filter Existing

- `show()` masih menampilkan satu record.
- `summaryStats()` masih global.
- `applyFilters()` menerima `number_invoice` langsung dari request.
- Detail invoice baru tidak boleh bergantung pada `number_invoice` dari JavaScript atau query string untuk mandatory scope.
- Gunakan route-model-bound anchor material untuk menentukan invoice di backend.

### Attachment Existing

- `packing_list_path`, `mtc_path`, dan `attachment_path` disimpan per row.
- Upload Packing List lama memiliki coupling ke `attachment_path`.
- Generic legacy attachment dapat mengisi fallback `packing_list_path`.
- Coupling tersebut jangan dibawa ke endpoint upload invoice baru.
- Existing `attachmentDisplay()` sebelumnya dapat menampilkan link kepada user yang lolos middleware modul.
- Requirement baru **secara sengaja memperketat** akses PL/MTC menjadi Sales-only. Legacy viewer tetap bisa melihat status file, tetapi tidak menerima URL download kecuali juga merupakan active Sales user.

### Invoice Query Existing

`invoiceBaseQuery()` saat audit sudah mengelompokkan berdasarkan `number_invoice`, tetapi belum memiliki representative material ID dan document consistency metadata.

Jangan memakai:

```sql
MAX(packing_list_path)
MAX(mtc_path)
```

sebagai bukti file invoice yang benar. `MAX()` pada string hanya bersifat leksikografis.

Gunakan representative material ID, document anchor ID, dan variant count untuk mendeteksi inkonsistensi legacy.

### View Existing

- `index.blade.php` masih berisi toolbar manage, modal import, CSS besar, konfigurasi DataTables, dan sticky filter dengan offset hardcoded.
- `show.blade.php` masih berupa card detail satu record.
- `invoice.blade.php` sudah mempunyai grouped DataTable dan modal update material.
- `form.blade.php` masih mempunyai field upload Packing List dan MTC.
- CSS filter row existing memakai offset sekitar `top: 40px`, yang rapuh untuk header dua baris.

### Access Call Sites Existing

Akses Outstanding Material tidak hanya ditentukan controller. Audit minimal harus mencakup:

```text
RoleMiddleware
ProcurementMenuAccessGroup
layout.blade.php
ProcurementMenuService
OutstandingMaterialController
Blade view terkait
```

Mengubah controller saja tidak cukup karena Sales masih dapat ditolak oleh middleware atau tidak melihat menu.

### Preseden HR Existing

- Jangan memakai `SalesMenuAccessGroup` sebagai sumber seluruh user Sales karena enum tersebut merupakan whitelist per fitur.
- Ikuti pola query aktif dari `HRRoleAccessService`.
- `HRMenuService` memiliki preseden resolve `User|string` yang dapat dijadikan referensi gaya implementasi.
- `TcpdDepartment::Sales` dan data seeder dapat dipakai untuk investigasi, bukan sebagai fallback authorization permanen.
- Source of truth final tetap active `user_job_positions` dan relasi job position/department.

### Route Ordering Existing

Route `/show-based-on-invoice/*` dan route spesifik lainnya sudah ditempatkan sebelum wildcard:

```text
/{outstandingMaterial}
```

Semua route baru harus mempertahankan urutan tersebut.

Cari juga seluruh referensi berikut di repository:

```text
OUTSTANDING_MATERIAL
outstanding-materials.
canManageOutstandingMaterials
packing_list_path
mtc_path
attachment_path
```

## Kondisi Saat Ini

- Index menggunakan server-side DataTables.
- Index masih memiliki tombol operasional dan aksi edit/delete.
- Halaman `show` masih menampilkan satu record dalam bentuk card.
- Halaman invoice sudah mengelompokkan data berdasarkan `number_invoice`.
- Packing List dan MTC disimpan per row material.
- Form material masih memiliki upload Packing List dan MTC.
- Middleware grup route masih memakai `role:outstanding_material`.
- Hak akses menu saat ini masih dominan berbasis whitelist nama.
- User sudah memiliki relasi ke `user_job_positions` dan `mst_job_positions`.
- Kolom berikut sudah tersedia:

```text
attachment_path
packing_list_path
mtc_path
```

Jangan membuat tabel invoice baru dan jangan membuat migration baru jika kolom yang ada sudah cukup.

---

# GLOBAL ACCESS MATRIX

Gunakan capability terpisah. Jangan memakai satu boolean untuk semua fungsi.

| Jenis User | View Module | Manage Material | Upload PL/MTC | Download PL/MTC |
|---|---:|---:|---:|---:|
| Existing Outstanding Material viewer | Ya | Tidak, kecuali manager | Tidak | Tidak, kecuali juga Sales |
| Active Sales job user | Ya | Tidak | Tidak | Ya |
| Existing manager | Ya | Ya | Tidak, kecuali Ilyas | Hanya jika juga Sales |
| ILYAS NOOR FIRDAUS | Ya | Ya sesuai existing rule | Ya | Hanya jika juga Sales |
| Administrator | Ya | Ya | Tidak secara default | Hanya jika juga Sales |
| User lain | Tidak | Tidak | Tidak | Tidak |

## Keputusan Kebijakan Final

Keputusan berikut tidak boleh diubah agent dengan alasan backward compatibility:

1. **Upload Packing List/MTC hanya ILYAS NOOR FIRDAUS.**
2. **Download Packing List/MTC hanya active Sales job user.**
3. Administrator dan manager non-Sales tidak otomatis dapat download.
4. Administrator non-Ilyas tidak otomatis dapat upload.
5. Legacy viewer seperti Jessica, Fajar, dan Vivian tetap dapat melihat modul, index, detail, serta invoice list, tetapi hanya melihat status PL/MTC tanpa download URL apabila bukan Sales.
6. Pembatasan harus diterapkan pada backend endpoint, bukan hanya pada Blade.

Requirement baru ini secara sengaja dapat mempersempit akses attachment dibanding behavior lama.

## Capability Definition

Buat satu service terpusat:

```text
app/Services/OutstandingMaterialAccessService.php
```

Service minimal menyediakan:

```php
public function canView(?User $user): bool;
public function canManage(?User $user): bool;
public function canUploadInvoiceDocuments(?User $user): bool;
public function canDownloadInvoiceDocuments(?User $user): bool;
public function isSales(?User $user): bool;
```

Ketentuan:

```text
canView
    = legacy Outstanding Material access
      ATAU active Sales job position

canManage
    = existing manager rule:
      ADMINISTRATOR, ADMINSTRATOR, ILYAS NOOR FIRDAUS
      ATAU role_id === 1 sesuai behavior existing

canUploadInvoiceDocuments
    = hanya user bernama ILYAS NOOR FIRDAUS

canDownloadInvoiceDocuments
    = hanya active Sales job position
```

Jangan otomatis memberikan upload kepada semua manager.

Jangan otomatis memberikan download kepada administrator atau manager.

## Sales Job Resolution

Gunakan sumber data utama:

```text
users
 └── user_job_positions
      └── mst_job_positions
           └── mst_departments
```

Sales dianggap valid jika user memiliki assignment aktif:

```text
user_job_positions.is_active = 1
```

dan salah satu kondisi berikut terpenuhi secara case-insensitive:

```text
mst_job_positions.position_name mengandung "Sales"
ATAU
mst_departments.name mengandung "Sales"
```

Contoh posisi yang harus dapat terdeteksi:

```text
Sales Staff
Sales Admin
Sales Engineer
Sales Supervisor
Sales Office Head
Sales Department Head
```

Jangan menggunakan whitelist nama user, `users.section`, `TcpdDepartment::Sales`, atau `SalesMenuAccessGroup` sebagai fallback authorization permanen.

Sumber-sumber tersebut hanya boleh dipakai untuk investigasi dan cross-check data.

Jika relasi job position aktif tidak lengkap:

1. Dokumentasikan user bisnis yang seharusnya Sales tetapi belum terpetakan.
2. Laporkan sebagai data-quality blocker.
3. Jangan memberikan akses berdasarkan tebakan atau whitelist sementara.
4. Jangan memutasi data HR dalam mission ini kecuali ada mission terpisah yang secara eksplisit meminta data remediation.

---

# GLOBAL TECHNICAL CONSTRAINTS

1. Jangan menambah frontend framework baru.
2. Jangan menambah CDN baru.
3. Pertahankan Bootstrap 5.3.2 dan DataTables existing.
4. Jangan mengubah modul lain tanpa kebutuhan langsung.
5. Jangan menghapus backward compatibility attachment lama secara sembarangan.
6. Semua mutation harus divalidasi dan diotorisasi di backend.
7. Hidden button atau Blade condition bukan authorization.
8. Query invoice harus menggunakan exact match.
9. Semua bulk update harus memverifikasi invoice ownership setiap material.
10. Gunakan database testing untuk automated test.
11. Jangan menjalankan migration atau test mutasi pada database production/development.
12. Jangan menghapus file lama sebelum database update berhasil.
13. Jangan menghapus file yang masih direferensikan record lain.
14. Jangan mempercayai nomor invoice atau material IDs dari frontend tanpa verifikasi.

---
