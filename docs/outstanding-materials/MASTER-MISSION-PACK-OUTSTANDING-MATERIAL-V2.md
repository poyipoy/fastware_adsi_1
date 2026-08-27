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

# MISSION 00
## Baseline Audit & Execution Map

## ROLE

Bertindak sebagai Senior Laravel 10 Engineer dan System Analyst.

Pada mission ini, jangan mengimplementasikan fitur bisnis. Fokus pada audit kondisi aktual, baseline test, pemetaan akses, dan dependency perubahan.

## OBJECTIVE

Menghasilkan baseline yang dapat digunakan mission berikutnya tanpa menebak struktur repository.

## TASKS

### 1. Repository State

Jalankan:

```bash
git status
git branch --show-current
git log -1 --oneline
```

Catat:

- Branch aktif.
- Uncommitted changes.
- File Outstanding Material yang sudah berubah.
- Perubahan yang bukan milik mission ini.

Jangan reset, checkout, atau menghapus perubahan existing.

### 2. Inspect Routes

Jalankan:

```bash
php artisan route:list --name=outstanding-materials
```

Catat:

- URI.
- HTTP method.
- Route name.
- Middleware.
- Urutan route specific terhadap route wildcard.

Pastikan mengetahui posisi route:

```text
/{outstandingMaterial}
```

karena route baru harus didefinisikan sebelum wildcard tersebut.

### 3. Inspect Current Controller Flow

Verifikasi ulang snapshot berikut:

- Isi `MANAGER_NAMES`.
- Administrator bypass melalui `role_id`.
- Existing legacy viewer whitelist.
- Signature `actionButtons()` saat ini.
- Scope `applyFilters()` terhadap `number_invoice`.
- Coupling `packing_list_path` dengan `attachment_path`.
- Query dan output `invoiceBaseQuery()`/`invoiceDataTableRow()`.
- Apakah model menggunakan soft delete.
- Disk storage yang benar-benar digunakan.

Petakan method berikut:

```text
index
data
create
store
show
edit
update
destroy
export
import
template
invoiceIndex
invoiceData
invoiceMaterials
updateInvoiceFields
attachment
validatedPayload
dataTableRow
actionButtons
invoiceBaseQuery
invoiceDataTableRow
canManageOutstandingMaterials
authorizeOutstandingMaterialManagement
```

Catat:

- Method view.
- Method mutation.
- Method download.
- Method yang memakai authorization.
- Method yang belum memakai capability tepat.

### 4. Inspect Current UI

Petakan:

```text
index.blade.php
show.blade.php
invoice.blade.php
form.blade.php
```

Catat:

- Toolbar setiap halaman.
- DataTables configuration.
- Filter implementation.
- Sticky CSS.
- Modal.
- Event handler.
- Source CSS/JS yang terduplikasi.
- Tombol yang ditampilkan berdasarkan permission.

### 5. Inspect Access Call Sites

Pastikan audit tidak meregresi legacy viewer berikut:

```text
JESSICA PAUNE
FAJAR BAGASKARA
VIVIAN ANGELIKA
```

Mereka harus tetap memperoleh `canView`, tetapi tidak memperoleh upload/download/manage kecuali memenuhi capability lain secara independen.

Cari:

```bash
grep -R "OUTSTANDING_MATERIAL" app resources routes
grep -R "outstanding_material" app resources routes
grep -R "canManageOutstandingMaterials" app resources
```

Identifikasi minimal:

- Middleware.
- Sidebar/layout.
- ProcurementMenuService.
- Controller.
- Blade.

### 6. Inspect Sales Job Data

Jalankan query read-only melalui database testing atau environment aman:

```sql
SELECT id, name
FROM mst_departments
WHERE LOWER(name) LIKE '%sales%';

SELECT id, position_name, department_id, is_active
FROM mst_job_positions
WHERE LOWER(position_name) LIKE '%sales%';

SELECT
    u.id,
    u.name,
    ujp.mst_job_position_id,
    ujp.is_active,
    mjp.position_name,
    md.name AS department_name
FROM user_job_positions ujp
JOIN users u
    ON u.id = ujp.user_id
JOIN mst_job_positions mjp
    ON mjp.id = ujp.mst_job_position_id
LEFT JOIN mst_departments md
    ON md.id = mjp.department_id
WHERE ujp.is_active = 1
  AND (
      LOWER(mjp.position_name) LIKE '%sales%'
      OR LOWER(COALESCE(md.name, '')) LIKE '%sales%'
  );
```

Jangan melakukan update data.

### 7. Baseline Tests

Jalankan minimal:

```bash
php artisan test --filter=OutstandingMaterial
```

Jika tidak ada test:

- Catat bahwa test belum tersedia.
- Jangan menganggap modul sudah aman.

Jika full suite masuk akal dijalankan:

```bash
php artisan test
```

Catat failure existing sebagai baseline.

## DELIVERABLE

Buat laporan:

```text
1. Current Architecture
2. Current Routes
3. Current Access Flow
4. Current UI Flow
5. Sales Data Source
6. Existing Risks
7. Baseline Test Result
8. File Impact Map
9. Recommended Execution Order
```

## ACCEPTANCE CRITERIA

- Tidak ada business feature yang diubah.
- Tidak ada data yang dimutasi.
- Seluruh access call site teridentifikasi.
- Sumber job Sales terkonfirmasi.
- Baseline test terdokumentasi.
- Route collision risk terdokumentasi.
- File impact untuk seluruh mission berikutnya tersedia.

## STOP CONDITION

Berhenti setelah laporan audit selesai.

Jangan mengerjakan MISSION 01 dalam sesi ini.

---

# MISSION 01
## Capability-Based Authorization & Sales Access

## DEPENDENCY

MISSION 00 sudah selesai.

Gunakan hasil audit MISSION 00 sebagai sumber struktur aktual.

## OBJECTIVE

Membangun fondasi authorization terpusat agar:

- Existing viewer tetap dapat melihat modul.
- Active Sales job user dapat melihat modul.
- Sales tidak memperoleh akses mutasi.
- Hanya Ilyas dapat upload Packing List/MTC.
- Hanya Sales dapat download Packing List/MTC.

## FILE TARGET

Minimal:

```text
app/Services/OutstandingMaterialAccessService.php
app/Http/Middleware/RoleMiddleware.php
app/Enums/ProcurementMenuAccessGroup.php
app/Services/ProcurementMenuService.php
app/Http/Controllers/OutstandingMaterialController.php
resources/views/layout.blade.php
```

Tambahkan atau ubah file lain hanya jika benar-benar diperlukan.

## IMPLEMENTATION

### 1. Create Access Service

Buat:

```text
app/Services/OutstandingMaterialAccessService.php
```

Method:

```php
public function canView(?User $user): bool;
public function canManage(?User $user): bool;
public function canUploadInvoiceDocuments(?User $user): bool;
public function canDownloadInvoiceDocuments(?User $user): bool;
public function isSales(?User $user): bool;
```

### 2. Implement `isSales()`

Ikuti pola query service HR existing, terutama penggunaan `UserJobPosition`, `is_active`, dan `whereHas()`.

Gunakan query relasi aktif:

```php
$user->userJobPositions()
    ->where('is_active', true)
    ->whereHas('jobPosition', function ($query) {
        $query->whereRaw('LOWER(position_name) LIKE ?', ['%sales%'])
            ->orWhereHas('department', function ($departmentQuery) {
                $departmentQuery->whereRaw(
                    'LOWER(name) LIKE ?',
                    ['%sales%']
                );
            });
    })
    ->exists();
```

Sesuaikan nama kolom department berdasarkan hasil audit aktual.

Ketentuan:

- Case-insensitive.
- Hanya assignment aktif.
- Jangan memakai `users.section` sebagai sumber utama.
- Jangan memakai whitelist nama.
- Jangan memakai `SalesMenuAccessGroup` sebagai sumber kebenaran semua Sales.

### 3. Implement `canView()`

Pertahankan existing viewer, termasuk Jessica Paune, Fajar Bagaskara, dan Vivian Angelika apabila masih tercantum pada enum aktual:

```text
ProcurementMenuAccessGroup::OUTSTANDING_MATERIAL
```

Kemudian tambahkan Sales job access.

Hindari recursive call antara access service dan enum.

Contoh prinsip:

```php
$legacyAccess =
    ProcurementMenuAccessGroup::OUTSTANDING_MATERIAL
        ->hasAccess((string) $user->name);

return $legacyAccess || $this->isSales($user);
```

### 4. Implement `canManage()`

Pindahkan rule existing manager dari controller ke service tanpa mengubah hasil akhirnya.

Pertahankan:

- `role_id === 1` administrator bypass.
- `ADMINISTRATOR`.
- `ADMINSTRATOR` sebagai typo legacy yang masih mungkin dipakai data.
- `ILYAS NOOR FIRDAUS`.

Jangan menganggap seluruh legacy viewer sebagai manager.

Jangan menambahkan Sales.

### 5. Implement Document Capability

Upload:

```php
return $normalizedName === 'ILYAS NOOR FIRDAUS';
```

Download:

```php
return $this->isSales($user);
```

Administrator tidak otomatis bypass upload/download.

Tambahkan komentar singkat karena rule berasal dari kebutuhan bisnis khusus.

### 6. Integrate Middleware

Grup route memakai:

```text
role:outstanding_material
```

Pastikan middleware menggunakan:

```php
OutstandingMaterialAccessService::canView($user)
```

khusus untuk role enum Outstanding Material.

Jangan mengubah behavior role enum lain.

### 7. Integrate Sidebar/Layout

Cari seluruh pemeriksaan langsung:

```text
ProcurementMenuAccessGroup::OUTSTANDING_MATERIAL
```

Ganti visibility Outstanding Material agar memakai:

```php
OutstandingMaterialAccessService::canView(Auth::user())
```

Jangan hanya mengubah desktop sidebar jika repository memiliki menu alternatif.

### 8. Integrate ProcurementMenuService

Menu API/mobile atau menu service juga harus memakai access service yang sama.

Jangan mengubah seluruh arsitektur service jika tidak diperlukan.

Jika method hanya menerima username:

- Resolve current authenticated user terlebih dahulu jika tersedia.
- Jika diperlukan, resolve user berdasarkan nama secara terbatas.
- Dokumentasikan risiko duplicate name.
- Jangan mengubah semua call site tanpa kebutuhan.

### 9. Integrate Controller

Inject service ke controller melalui constructor atau method injection yang konsisten dengan style repository.

Buat helper jika diperlukan:

```php
private function authorizeView(): void;
private function authorizeManage(): void;
private function authorizeDocumentUpload(): void;
private function authorizeDocumentDownload(): void;
```

Belum perlu mengubah seluruh method bisnis pada mission ini, tetapi siapkan fondasinya.

## TESTS

Tambahkan test service minimal:

- Existing whitelist viewer → `canView = true`.
- Active Sales user → `canView = true`.
- Inactive Sales assignment → `canView = false`.
- Non-Sales non-viewer → `canView = false`.
- Sales → `canManage = false`.
- Manager existing → `canManage = true`.
- Ilyas → `canUploadInvoiceDocuments = true`.
- Administrator non-Ilyas → upload false.
- Sales → download true.
- Manager non-Sales → download false.

## ACCEPTANCE CRITERIA

- Satu service menjadi sumber seluruh capability.
- Sales access berasal dari active job position.
- Tidak ada whitelist nama untuk Sales.
- Existing viewer tidak kehilangan akses.
- Sales tidak mendapat manage access.
- Hanya Ilyas memiliki upload capability.
- Hanya Sales memiliki download capability.
- Menu desktop dan menu service konsisten.
- Middleware tidak lagi menolak active Sales user.
- Behavior role middleware lain tidak berubah.
- Test mission lulus.

## FINAL REPORT

Laporkan:

```text
1. Files Changed
2. Capability Matrix
3. Sales Resolution Logic
4. Middleware Changes
5. Menu Changes
6. Tests Executed
7. Remaining Risks
```

## STOP CONDITION

Berhenti setelah fondasi authorization selesai.

Jangan mengerjakan MISSION 02.

---

# MISSION 02
## Outstanding Material Index Menjadi Read-Only

## DEPENDENCY

MISSION 01 sudah selesai dan access service tersedia.

## OBJECTIVE

Mengubah halaman index menjadi halaman monitoring read-only.

Index tetap dapat:

- Menampilkan summary.
- Menampilkan seluruh material.
- Search.
- Filter.
- Sorting.
- Pagination.
- Horizontal dan vertical scroll.
- Reset filter.
- Membuka detail.

Index tidak boleh menjadi workspace pengelolaan.

## FILE TARGET

```text
app/Http/Controllers/OutstandingMaterialController.php
resources/views/outstanding_materials/index.blade.php
```

## REQUIRED UI BEHAVIOR

### Toolbar Index

Hapus dari index:

```text
Invoice View
Add Material
Import
Template
Export
```

Sisakan kontrol baca:

```text
Search
Filter
Reset Filter
Pagination selector
```

Jangan hanya menyembunyikan dengan CSS.

Hapus markup dan event handler yang tidak lagi dipakai.

### Action Column

Kolom Action hanya menampilkan:

```text
Show / Detail / ikon mata
```

Untuk seluruh user, termasuk manager.

Hilangkan:

```text
Edit
Delete
Update
Upload
```

## BACKEND IMPLEMENTATION

### 1. Separate Action Context

Saat audit, `actionButtons()` hanya menerima material dan otomatis mengirim Edit/Delete untuk manager. Refactor:

```php
actionButtons()
dataTableRow()
```

Agar rendering action dapat dibedakan berdasarkan context.

Contoh:

```php
private function actionButtons(
    OutstandingMaterial $material,
    bool $allowManage = false
): string
```

Index selalu memanggil:

```php
allowManage: false
```

Halaman detail invoice pada mission berikutnya dapat memanggil:

```php
allowManage: true
```

Jangan mengirim HTML edit/delete dari server untuk index.

### 2. Read Authorization

Pastikan:

```text
index
data
```

menggunakan `authorizeView()`.

### 3. Packing List dan MTC pada Index

Index boleh tetap menampilkan kolom Packing List dan MTC sebagai informasi.

Namun pada index:

- Jangan tampilkan upload.
- Jangan tampilkan replace.
- Jangan tampilkan edit.
- Sebaiknya tampilkan status:

```text
Available
Not Available
```

Download utama akan ditempatkan pada invoice page.

Jika link download existing masih dipertahankan sementara:

- Hanya Sales boleh memperoleh link.
- User lain melihat status tanpa URL.
- Backend download tetap harus terproteksi.

### 4. Remove Import Modal

Pindahkan atau hapus dari index:

```text
#importOutstandingMaterialModal
```

Hapus JavaScript confirmation yang hanya terkait modal tersebut.

Jangan sampai ada selector JavaScript yang mengakses element yang sudah tidak ada.

### 5. Preserve DataTables

Pastikan tetap berfungsi:

- AJAX.
- Search delay.
- Column filter.
- Date range.
- Reset.
- Server-side pagination.
- Sorting.
- Tooltip detail.

## TESTS

Minimal:

- Manager membuka index tetapi tidak melihat Add Material.
- Manager tidak melihat Import.
- Manager tidak melihat Template.
- Manager tidak melihat Export.
- Manager tidak melihat Invoice View.
- Manager tidak melihat Edit/Delete pada action.
- Viewer melihat tombol detail.
- Sales melihat tombol detail.
- Endpoint data tidak mengirim HTML edit/delete.
- Index masih dapat dibuka active Sales user.
- Non-viewer tetap mendapat 403.

## ACCEPTANCE CRITERIA

- Index sepenuhnya read-only.
- Action index hanya ikon mata.
- Tidak ada toolbar operasional.
- Tidak ada modal import.
- Tidak ada dead JavaScript listener.
- Summary dan filter tetap berfungsi.
- DataTables tidak mengalami regresi.
- Authorization tetap sesuai MISSION 01.
- Test mission lulus.

## FINAL REPORT

```text
1. Files Changed
2. Buttons Removed
3. Action Rendering Change
4. DataTables Validation
5. Tests Executed
6. Remaining Issues
```

## STOP CONDITION

Berhenti setelah index read-only selesai.

Jangan mengerjakan MISSION 03.

---

# MISSION 03
## Detail Invoice Menjadi Management Workspace

## DEPENDENCY

MISSION 02 sudah selesai.

Index sudah hanya memiliki tombol detail.

## OBJECTIVE

Mengubah halaman:

```text
outstanding-materials.show
```

dari detail satu material menjadi workspace per invoice.

Saat membuka satu material:

- Ambil invoice dari material tersebut.
- Tampilkan seluruh material dalam invoice yang sama.
- Jangan menampilkan invoice lain.
- Jika invoice kosong, hanya tampilkan material anchor tersebut.

## FILE TARGET

```text
routes/web.php
app/Http/Controllers/OutstandingMaterialController.php
resources/views/outstanding_materials/show.blade.php
resources/views/outstanding_materials/index.blade.php
```

Partial baru dapat dibuat jika diperlukan:

```text
resources/views/outstanding_materials/partials/
```

## BACKEND IMPLEMENTATION

### 1. Show Route

Pertahankan route:

```text
outstanding-materials.show
```

Route-model binding tetap menggunakan:

```php
OutstandingMaterial $outstandingMaterial
```

### 2. Show Controller

`show()` harus mengirim:

```text
anchorMaterial
invoiceNumber
summary
filterOptions
statusOptions
keteranganOptions
canManage
canView
canDownloadDocuments
```

Behavior:

```php
if invoice number terisi:
    scope = exact number_invoice match
else:
    scope = anchor material ID only
```

Jangan query semua row dengan `number_invoice IS NULL`.

### 3. Dedicated Data Endpoint

Current `applyFilters()` menerima `number_invoice` dari request. Karena itu halaman detail tidak boleh sekadar reuse endpoint global dengan fixed filter dari JavaScript.

Buat endpoint khusus:

```text
GET /outstanding-materials/{outstandingMaterial}/invoice-data
```

Contoh route name:

```text
outstanding-materials.invoice-detail.data
```

Definisikan sebelum wildcard `/{outstandingMaterial}` apabila struktur route memerlukannya.

Endpoint harus menentukan mandatory scope dari route-model binding.

Jangan menggunakan nomor invoice bebas dari request sebagai mandatory scope.

Pseudocode:

```php
$query = OutstandingMaterial::query();

if (blank($outstandingMaterial->number_invoice)) {
    $query->whereKey($outstandingMaterial->id);
} else {
    $query->where(
        'number_invoice',
        $outstandingMaterial->number_invoice
    );
}

$this->applyFiltersExceptLockedInvoice($query, $request);
```

Filter user diterapkan setelah mandatory scope.

### 4. Scoped Filter Options

Filter options pada detail sebaiknya berasal dari invoice scope, bukan seluruh database.

Buat helper reusable:

```php
private function filterOptionsForQuery(Builder $query): array;
```

Jangan menampilkan supplier/type/month yang tidak ada dalam invoice tersebut.

### 5. Scoped Summary

Current `summaryStats()` bersifat global. Refactor secara backward-compatible agar dapat menerima base query atau invoice scope.

Buat summary berdasarkan query invoice.

Minimal:

```text
Total Material
Total QTY PCS
Total Estimated KG
On Production
On Shipment
Received
```

Gunakan aggregate database, bukan menghitung hanya page DataTables aktif.

### 6. Scoped Export

Buat endpoint export khusus invoice:

```text
GET /outstanding-materials/{outstandingMaterial}/export
```

Export scope ditentukan oleh anchor material.

Jangan hanya mengandalkan:

```text
?number_invoice=...
```

yang dapat diubah user.

Global export route lama boleh dipertahankan untuk backward compatibility, tetapi tidak ditampilkan pada index.

## FRONTEND IMPLEMENTATION

### 1. Replace Old Detail Card

Hapus tampilan card label-value lama.

Ganti dengan:

- Page header.
- Invoice identity.
- Invoice-scoped summary.
- Toolbar.
- Server-side table.
- Filter row.
- Action column.

### 2. Table Columns

Gunakan kolom setara index:

```text
NO
Supplier
TYPE
Thickness
Width
Diameter
Length
QTY PCS
Est QTY KG
Number Invoice
Status
ETA Port
ETA Warehouse
Estimasi Bulan ETA
Keterangan
Delay ETA Port
Delay ETA Warehouse
Packing List
MTC
Action
```

Untuk invoice kosong, Number Invoice dapat menampilkan `-`.

### 3. Locked Invoice Filter

Filter invoice pada detail:

- Tidak boleh berupa dropdown yang dapat diganti.
- Tampilkan nilai invoice sebagai teks statis atau readonly.
- Jangan mengirim nilai tersebut sebagai satu-satunya pengaman backend.

### 4. Toolbar Detail

Untuk seluruh viewer:

```text
Back to Index
Invoice List
Export Invoice
Reset Filter
```

Untuk manager:

```text
Add Material
Import
Template
```

Import tetap global. Tambahkan catatan UI:

```text
Import akan memproses seluruh baris pada file dan tidak dibatasi
pada invoice yang sedang dibuka.
```

### 5. Add Material Context

Tombol Add Material membawa context anchor:

```text
?invoice_context=<anchor material id>
```

Implementasi lock form diselesaikan pada MISSION 05.

### 6. Detail Action Column

Viewer dan Sales:

```text
No edit
No delete
```

Manager:

```text
Edit
Delete
```

Gunakan:

```php
actionButtons($material, allowManage: $canManage)
```

### 7. Import Modal

Pindahkan import modal dari index ke detail.

Hanya manager yang dapat melihat dan submit.

## TESTS

Minimal:

- Show invoice menampilkan seluruh material invoice sama.
- Show tidak menampilkan material invoice berbeda.
- Invoice null hanya menampilkan anchor row.
- Dedicated data endpoint tidak dapat diganti scope melalui request.
- Summary hanya menghitung invoice aktif.
- Scoped export hanya berisi invoice aktif.
- Sales membuka detail read-only.
- Manager melihat Add/Import/Template.
- Manager melihat Edit/Delete di detail.
- Index tetap tidak menampilkan Edit/Delete.
- Non-viewer tetap 403.

## ACCEPTANCE CRITERIA

- Detail berubah menjadi invoice workspace.
- Mandatory invoice scope diterapkan backend.
- Tidak ada kebocoran data antar-invoice.
- Invoice kosong aman.
- Summary dan filter invoice-scoped.
- Export invoice-scoped.
- Toolbar mengikuti capability.
- Import modal tidak lagi berada di index.
- Test mission lulus.

## FINAL REPORT

```text
1. Routes Added
2. Controller Methods Added/Changed
3. Invoice Scoping Strategy
4. Toolbar Access Matrix
5. Summary and Filter Scope
6. Tests Executed
7. Remaining Risks
```

## STOP CONDITION

Berhenti setelah invoice detail workspace selesai.

Jangan mengerjakan MISSION 04.

---

# MISSION 04
## Invoice Page, Packing List, MTC, Upload & Download

## DEPENDENCY

MISSION 03 sudah selesai.

Invoice detail route sudah tersedia.

## OBJECTIVE

Merevisi grouped invoice page agar memiliki:

- Tombol detail.
- Kolom Packing List.
- Kolom MTC.
- Upload/replace oleh Ilyas.
- Download hanya oleh Sales.
- Bulk document synchronization per invoice.
- Penghapusan file lama yang aman.

## FILE TARGET

```text
routes/web.php
app/Http/Controllers/OutstandingMaterialController.php
resources/views/outstanding_materials/invoice.blade.php
app/Services/OutstandingMaterialAccessService.php
```

## ROUTE AUTHORIZATION MATRIX

```text
invoice.index
    → canView

invoice.data
    → canView

invoice.materials
    → canManage

invoice.update
    → canManage

invoice.documents.upload
    → canUploadInvoiceDocuments

packing-list download
    → canDownloadInvoiceDocuments

mtc download
    → canDownloadInvoiceDocuments
```

Sales tidak boleh membuka mutation endpoint.

## GROUPED QUERY

Saat audit, grouped query hanya mempunyai invoice, count, supplier sample, gabungan status/keterangan, dan ETA warehouse. Output DataTables masih sekitar tujuh key dan belum membawa metadata dokumen.

### Required Aggregates

Tambahkan minimal:

```php
DB::raw('MIN(id) AS representative_id'),
DB::raw('COUNT(*) AS material_count'),
DB::raw('MIN(supplier) AS supplier_sample'),
DB::raw('MAX(estimasi_eta_warehouse) AS latest_eta_warehouse')
```

### Document Anchor IDs

Jangan hanya memakai:

```php
MAX(packing_list_path)
MAX(mtc_path)
```

karena string `MAX()` tidak menunjukkan file terbaru atau file benar.

Gunakan aggregate ID:

```php
DB::raw(
    'MIN(
        CASE
            WHEN packing_list_path IS NOT NULL
             AND packing_list_path <> \'\'
            THEN id
        END
    ) AS packing_list_material_id'
),
DB::raw(
    'MIN(
        CASE
            WHEN mtc_path IS NOT NULL
             AND mtc_path <> \'\'
            THEN id
        END
    ) AS mtc_material_id'
)
```

Tambahkan deteksi inconsistency:

```php
DB::raw(
    'COUNT(DISTINCT NULLIF(packing_list_path, \'\'))
     AS packing_list_variant_count'
),
DB::raw(
    'COUNT(DISTINCT NULLIF(mtc_path, \'\'))
     AS mtc_variant_count'
)
```

Jika variant count lebih dari satu:

- Tampilkan warning legacy inconsistency.
- Jangan menghapus file saat halaman dibuka.
- Jangan memilih file lain secara diam-diam untuk mutation.
- Upload baru oleh Ilyas boleh menormalisasi seluruh invoice.

## INVOICE TABLE

Kolom:

```text
Number Invoice
Supplier
Total Row
Status
Keterangan
Latest ETA Warehouse
Packing List
MTC
Action
```

### Action Cell

Seluruh viewer:

```text
Detail / ikon mata
```

Manager:

```text
Update Materials
```

Ilyas:

```text
Upload Documents
Replace Documents
```

Sales:

```text
Download Packing List
Download MTC
```

User non-Sales:

- Melihat status file.
- Tidak menerima download URL.

## DETAIL BUTTON

Gunakan:

```php
route(
    'outstanding-materials.show',
    $invoice->representative_id
)
```

Jangan membuat route detail dari nomor invoice mentah.

## DOCUMENT UPLOAD ENDPOINT

Buat route:

```text
POST /outstanding-materials/show-based-on-invoice/documents
```

Contoh name:

```text
outstanding-materials.invoice.documents.upload
```

Authorization:

```php
authorizeDocumentUpload()
```

Hanya Ilyas.

## REQUEST VALIDATION

Minimal:

```text
invoice      required|string
packing_list nullable|file
mtc          nullable|file
```

Minimal salah satu file harus ada.

Allowed extensions/MIME konsisten dengan existing rule:

```text
pdf
xls
xlsx
doc
docx
jpg
jpeg
png
```

Maximum:

```text
10 MB per file
```

Pastikan invoice exact match memiliki minimal satu row aktif.

## STORAGE FLOW

Gunakan unique generated filename.

Directory:

```text
outstanding-materials/packing-list/
outstanding-materials/mtc/
```

Urutan aman:

1. Validate request.
2. Authorize upload.
3. Resolve exact invoice records.
4. Simpan file baru.
5. Catat seluruh path lama unik.
6. Jalankan database transaction.
7. Bulk update seluruh row invoice.
8. Isi `updated_by`.
9. Commit.
10. Setelah commit, cek old paths.
11. Hapus old path hanya jika tidak lagi direferensikan row lain.
12. Jika database gagal, hapus file baru yang baru disimpan.

Jangan menghapus file lama sebelum transaction sukses.

Jangan menghapus file yang dipakai invoice lain.

## BULK UPDATE

Gunakan exact invoice match:

```php
OutstandingMaterial::query()
    ->where('number_invoice', $invoice)
    ->update([...]);

Jangan menambahkan `whereNull('deleted_at')` kecuali model dan schema aktual memang menggunakan SoftDeletes.
```

Update hanya field yang file barunya diupload.

Contoh:

```text
upload hanya MTC
    → jangan null-kan packing_list_path
```

Jangan lagi otomatis melakukan:

```text
attachment_path = packing_list_path
```

`attachment_path` hanya dipertahankan untuk backward compatibility.

## DOWNLOAD AUTHORIZATION

Method `attachment()` harus membedakan tipe:

```php
packing-list
mtc
attachment
```

Untuk:

```text
packing-list
mtc
```

wajib:

```php
authorizeDocumentDownload()
```

User non-Sales yang mengetahui URL langsung harus mendapat 403.

Generic legacy `attachment` mengikuti existing read rule kecuali audit menunjukkan rule berbeda.

Pastikan:

- Path berada dalam directory yang diizinkan.
- File benar-benar ada.
- Tidak menerima arbitrary filesystem path.
- Filename di response aman.

## UPDATE MATERIALS SECURITY

Perbaiki:

```php
updateInvoiceFields()
```

Jangan hanya:

```php
whereIn('id', $materialIds)
```

Validasi seluruh ID benar-benar memiliki:

```text
number_invoice = request invoice
```

Contoh:

```php
$validIds = OutstandingMaterial::query()
    ->where('number_invoice', $invoice)
    ->whereIn('id', $materialIds)
    ->pluck('id');

abort atau validation error jika jumlahnya berbeda.
```

Gunakan transaction.

## UI UPLOAD MODAL

Tambahkan modal:

```text
Invoice number readonly
Packing List input
MTC input
Current file status
```

Behavior:

- Hanya Ilyas melihat tombol.
- Submit via AJAX atau standard POST yang konsisten.
- Disable submit saat upload.
- Tampilkan loading.
- Konfirmasi sebelum replace.
- Reload row DataTables setelah sukses.
- Tampilkan validation error secara jelas.

## TESTS

Minimal:

- Viewer existing membuka invoice page.
- Sales membuka invoice page.
- Sales melihat download jika file tersedia.
- Non-Sales tidak mendapat download URL.
- Direct download oleh non-Sales → 403.
- Direct download oleh Sales → berhasil.
- Ilyas upload Packing List.
- Ilyas upload MTC.
- Manager non-Ilyas upload → 403.
- Administrator non-Ilyas upload → 403.
- Upload satu file tidak menghapus field lain.
- Upload menyinkronkan seluruh row invoice.
- Invoice lain tidak berubah.
- `updated_by` tercatat.
- File lama dihapus jika tidak direferensikan.
- File lama tidak dihapus jika masih direferensikan invoice lain.
- Invalid extension ditolak.
- File di atas 10 MB ditolak.
- Empty upload ditolak.
- Sales tidak dapat `invoice.update`.
- Sales tidak dapat `invoice.materials`.
- Material ID invoice lain ditolak dalam bulk update.

## ACCEPTANCE CRITERIA

- Invoice page memiliki detail, Packing List, dan MTC.
- Download hanya Sales.
- Upload hanya Ilyas.
- Backend authorization benar.
- Bulk document update invoice-consistent.
- File replacement tidak membuat orphan.
- Shared old file tidak terhapus salah.
- Invoice mutation tidak menerima material invoice lain.
- UI permission sesuai capability.
- Test mission lulus.

## FINAL REPORT

```text
1. Routes Added
2. Invoice Query Changes
3. Upload Workflow
4. Download Authorization
5. File Cleanup Strategy
6. Bulk Update Protection
7. Tests Executed
8. Remaining Legacy Inconsistencies
```

## STOP CONDITION

Berhenti setelah invoice document workflow selesai.

Jangan mengerjakan MISSION 05.

---

# MISSION 05
## Form Cleanup, Invoice Inheritance & Legacy Attachment Safety

## DEPENDENCY

MISSION 04 sudah selesai.

Invoice page sudah menjadi jalur upload dokumen.

## OBJECTIVE

Menghapus upload Packing List/MTC dari form material dan menjaga konsistensi dokumen saat:

- Material dibuat.
- Material diedit.
- Invoice material dipindah.
- Material dihapus.
- Material baru ditambahkan dari invoice detail.

## FILE TARGET

```text
app/Http/Controllers/OutstandingMaterialController.php
resources/views/outstanding_materials/form.blade.php
routes/web.php
```

## REMOVE FORM FIELDS

Hapus dari form create/edit:

```text
Packing List
MTC
```

Hapus:

```text
input file
current file display
validation error khusus field tersebut
related JavaScript
```

Keterangan tetap ada.

## REMOVE MATERIAL FORM VALIDATION

Dari `validatedPayload()` hapus:

```text
packing_list validation
mtc validation
packing_list storage
mtc storage
```

Generic legacy `attachment` tidak perlu dihapus jika masih mungkin dipakai jalur lama.

Current implementation memiliki coupling legacy antara `attachment_path` dan `packing_list_path`. Hapus hanya handling Packing List/MTC dari form material. Jangan membawa coupling itu ke endpoint invoice baru, dan jangan melakukan refactor generic attachment tanpa bukti bahwa sudah tidak digunakan.

## PRESERVE EXISTING PATHS ON EDIT

Material edit biasa tidak boleh:

- Mengosongkan `packing_list_path`.
- Mengosongkan `mtc_path`.
- Menghapus file.
- Mengganti dokumen invoice.

Dokumen hanya diubah melalui invoice document endpoint.

## ADD MATERIAL FROM DETAIL

Tombol dari MISSION 03 membawa:

```text
invoice_context=<anchor material id>
```

Pada `create()`:

1. Resolve anchor material.
2. Ambil invoice number.
3. Kirim ke form sebagai locked context.
4. Render Number Invoice sebagai readonly.
5. Sertakan hidden `invoice_context_id`.

Pada `store()`:

- Jika context valid, gunakan invoice dari anchor.
- Jangan hanya mempercayai input `number_invoice`.
- Manager tetap dapat membuat material global jika form dibuka tanpa context.

## DOCUMENT INHERITANCE ON CREATE

Jika material baru dibuat ke invoice yang sudah memiliki dokumen:

- Inherit `packing_list_path`.
- Inherit `mtc_path`.
- Jangan membuat upload baru.
- Jangan membuat copy file fisik.

Pilih source path secara deterministik dari invoice.

Jika invoice memiliki legacy path inconsistency:

- Jangan memilih secara diam-diam.
- Gunakan path yang sudah dinormalisasi oleh workflow baru jika tersedia.
- Jika tidak dapat dipastikan, simpan null dan catat warning log atau laporan.

## INVOICE CHANGE ON EDIT

Jika `number_invoice` berubah:

1. Jangan membawa dokumen invoice lama ke invoice baru.
2. Cari dokumen existing pada destination invoice.
3. Jika destination memiliki dokumen konsisten:
   - Set row ke destination paths.
4. Jika destination tidak memiliki dokumen:
   - Set row paths menjadi null.
5. Jangan menghapus file source invoice jika masih direferensikan row lain.
6. Jangan mengubah row lain tanpa kebutuhan.

Gunakan transaction apabila update melibatkan penyesuaian path.

## SAFE DESTROY

Current delete behavior berisiko karena dokumen invoice disimpan pada banyak row.

Saat satu material dihapus:

1. Catat path row.
2. Hapus row atau soft-delete sesuai model.
3. Setelah berhasil, cek apakah path masih direferensikan row lain.
4. Hapus file hanya jika reference count nol.
5. Jangan null-kan dokumen row lain.
6. Jangan menghapus file invoice yang masih memiliki material lain.

Buat helper reusable, misalnya:

```php
private function deleteStoredAttachmentIfUnreferenced(
    ?string $path
): void;
```

Reference check harus mempertimbangkan:

```text
attachment_path
packing_list_path
mtc_path
```

## FORM UI

Jika invoice context terkunci:

- Input `number_invoice` readonly.
- Tampilkan info bahwa material akan masuk ke invoice tersebut.
- Back button kembali ke detail invoice anchor.

Jika tanpa context:

- Input tetap dapat diedit sesuai behavior existing.
- Back button mengikuti flow existing.

## TESTS

Minimal:

- Form tidak memiliki field Packing List.
- Form tidak memiliki field MTC.
- Store berhasil tanpa file.
- Update berhasil tanpa file.
- Existing paths tidak hilang saat edit field lain.
- Create from invoice context mengunci invoice.
- Tampered invoice input tidak mengalahkan context anchor.
- Material baru mewarisi document paths invoice.
- Global create tetap bekerja.
- Pindah invoice tidak membawa file invoice lama.
- Pindah ke invoice yang memiliki dokumen mewarisi destination paths.
- Destroy satu row tidak menghapus shared invoice file.
- Destroy row terakhir menghapus file jika tidak direferensikan tempat lain.
- Legacy `attachment_path` tidak rusak.

## ACCEPTANCE CRITERIA

- Upload PL/MTC hilang dari form.
- Form submit tetap berhasil.
- Invoice context bekerja.
- Document paths konsisten saat create/edit/delete.
- Shared files tidak terhapus salah.
- Tidak ada mutation dokumen melalui form material.
- Test mission lulus.

## FINAL REPORT

```text
1. Fields Removed
2. Validation Changes
3. Invoice Context Flow
4. Document Inheritance Rules
5. Invoice Move Handling
6. Safe Delete Handling
7. Tests Executed
8. Remaining Legacy Risks
```

## STOP CONDITION

Berhenti setelah form cleanup dan consistency selesai.

Jangan mengerjakan MISSION 06.

---

# MISSION 06
## Sticky Header, Sticky Filter & Reusable Frontend Components

## DEPENDENCY

MISSION 05 sudah selesai.

Semua business behavior sudah bekerja sebelum frontend refactor dilakukan.

## OBJECTIVE

Memperbaiki scrolling table dan mengurangi duplikasi yang aman tanpa melakukan redesign besar.

Target halaman:

```text
Outstanding Material Index
Invoice Detail Workspace
Grouped Invoice Page
```

## CONSTRAINTS

- Pertahankan design token `.om-*`.
- Jangan menambah CDN.
- Jangan mengganti DataTables.
- Jangan mengubah visual branding.
- Jangan mengubah business logic controller.
- Jangan memaksa refactor besar jika meningkatkan risiko regresi.

## STICKY BEHAVIOR

Saat vertical scroll di table container:

- Header nama kolom tetap terlihat.
- Filter row tetap terlihat di bawah header.
- Tidak overlap.
- Tidak ada gap.
- Background tidak transparan.
- Z-index benar.

Saat horizontal scroll:

- Header dan filter tetap sinkron dengan body.

Saat resize:

- Offset filter dihitung ulang.

## REMOVE HARDCODED OFFSET

Jangan bergantung pada:

```css
.om-filter-row th {
    top: 40px;
}
```

karena header dapat memiliki teks dua baris.

Gunakan dynamic measurement.

Contoh:

```js
function syncStickyOffsets(tableSelector) {
    const table = document.querySelector(tableSelector);

    if (!table) {
        return;
    }

    const firstHeaderRow = table.querySelector(
        'thead tr:first-child'
    );

    if (!firstHeaderRow) {
        return;
    }

    const headerHeight =
        Math.ceil(firstHeaderRow.getBoundingClientRect().height);

    table.style.setProperty(
        '--om-table-header-height',
        `${headerHeight}px`
    );
}
```

CSS:

```css
.om-table thead tr:first-child th {
    position: sticky;
    top: 0;
    z-index: 4;
    background: var(--om-gray-50);
}

.om-table thead .om-filter-row th {
    position: sticky;
    top: var(--om-table-header-height);
    z-index: 3;
    background: #fff;
}
```

Gunakan `ResizeObserver` jika tersedia.

Fallback:

```js
window.addEventListener('resize', ...);
```

Panggil setelah:

```text
DataTables init
DataTables draw
window resize
font/layout change yang relevan
```

## TABLE CONTAINER

Pastikan container memiliki:

```css
max-height: ...
overflow: auto;
overscroll-behavior: contain;
position: relative;
```

Jangan membuat page-wide sticky yang menutupi navbar utama.

## FILTER PANEL

Jika halaman memiliki toolbar/filter panel di luar `<thead>`:

- Pertahankan tetap terlihat hanya jika tidak menutupi application header.
- Gunakan offset berdasarkan layout aktual.
- Jangan hardcode tinggi navbar tanpa inspeksi.

Jika filter menggunakan inline filter row, requirement fixed filter dianggap terpenuhi oleh sticky filter row.

## FRONTEND REUSE

Ekstrak reusable component hanya jika behavior sudah stabil.

Prioritas:

```text
shared table styles
shared column markup
shared filter controls
shared DataTables initialization helper
shared sticky offset helper
```

Contoh lokasi:

```text
resources/views/outstanding_materials/partials/
public/assets/js/outstanding-materials/
public/assets/css/
```

Jangan memindahkan seluruh file besar sekaligus tanpa test.

Pendekatan aman:

1. Ekstrak sticky helper.
2. Ekstrak shared CSS.
3. Ekstrak table/filter markup yang benar-benar identik.
4. Ekstrak DataTables common options.
5. Biarkan page-specific options di masing-masing Blade.

## DATATABLES CONSIDERATIONS

Periksa:

- `scrollX`.
- Wrapper cloning header.
- `.dataTables_scrollHead`.
- Existing `.om-table-wrap`.
- `drawCallback`.
- Tooltip initialization.
- Filter event listener.
- Date range popover.
- Search delay.
- Export URL sync.

Jangan menghasilkan dua scrollbar horizontal yang saling bertumpuk.

## RESPONSIVE QA

Validasi minimal:

```text
Desktop 1920px
Laptop 1366px
Tablet width
Mobile width
Browser zoom 80%
Browser zoom 125%
```

Periksa:

- Two-line header.
- Date filter.
- Dropdown filter.
- Tooltip.
- Modal.
- Long invoice.
- Long filename.
- Pagination.

## TESTS & QA

Automated test tidak dapat membuktikan seluruh sticky behavior.

Lakukan:

- Blade render test.
- Asset reference check.
- Browser/manual QA.
- Console error check.
- DataTables AJAX check.

Pastikan tidak ada:

```text
undefined selector
duplicate event handler
double DataTables initialization
JS syntax error
missing asset
```

## ACCEPTANCE CRITERIA

- Header sticky bekerja.
- Filter row sticky bekerja.
- Offset mengikuti actual header height.
- Resize tidak merusak posisi.
- Horizontal scroll tetap sinkron.
- Tidak ada overlap.
- Tidak ada double scrollbar yang mengganggu.
- Index dan detail berbagi helper yang aman.
- Tidak ada CDN/framework baru.
- Tidak ada business behavior yang berubah.
- Manual QA terdokumentasi.

## FINAL REPORT

```text
1. Shared Components Created
2. Sticky Strategy
3. DataTables Integration
4. Responsive QA Result
5. Console/AJAX Validation
6. Files Changed
7. Remaining UI Risks
```

## STOP CONDITION

Berhenti setelah UI stabilization selesai.

Jangan mengerjakan MISSION 07.

---

# MISSION 07
## Security Tests, Regression Validation & Final Integration

## DEPENDENCY

MISSION 00–06 sudah selesai.

## OBJECTIVE

Memvalidasi seluruh revisi Outstanding Material secara terintegrasi.

Mission ini tidak boleh menambah fitur baru kecuali untuk memperbaiki failure yang ditemukan.

## TEST FILE

Buat atau lengkapi Feature Test, misalnya:

```text
tests/Feature/OutstandingMaterial/
tests/Feature/OutstandingMaterialAccessTest.php
tests/Feature/OutstandingMaterialInvoiceTest.php
tests/Feature/OutstandingMaterialDocumentTest.php
```

Ikuti struktur testing repository.

## TEST DATA

Gunakan:

```text
User
MstDepartment
MstJobPosition
UserJobPosition
OutstandingMaterial
Storage::fake('public')
```

Buat data terisolasi.

Jangan bergantung pada nama atau data production existing selain rule bisnis yang diuji.

## REQUIRED TEST MATRIX

### A. Existing Viewer

Gunakan minimal satu legacy viewer aktual, misalnya Jessica Paune, Fajar Bagaskara, atau Vivian Angelika apabila masih tercantum di enum.

- Melihat menu.
- Membuka index.
- Membuka detail.
- Membuka invoice list.
- Tidak dapat manage jika bukan manager.
- Tidak dapat upload.
- Tidak dapat download PL/MTC jika bukan Sales.
- Melihat status file tanpa menerima URL download.

### B. Active Sales User

- Melihat menu sidebar.
- Melihat menu service/API.
- Membuka index.
- Membuka data endpoint.
- Membuka invoice detail.
- Membuka invoice page.
- Download Packing List berhasil.
- Download MTC berhasil.
- Tidak dapat create.
- Tidak dapat store.
- Tidak dapat edit.
- Tidak dapat update.
- Tidak dapat destroy.
- Tidak dapat import.
- Tidak dapat template.
- Tidak dapat invoice materials mutation.
- Tidak dapat invoice update.
- Tidak dapat upload documents.

### C. Inactive Sales Assignment

- Tidak dianggap Sales.
- Tidak mendapat Sales-only download.
- Tidak mendapat view kecuali memiliki legacy viewer access.

### D. Manager

- Dapat manage material.
- Index tetap tidak menampilkan edit/delete.
- Detail menampilkan edit/delete.
- Dapat Add Material.
- Dapat Import.
- Dapat Template.
- Tidak dapat upload documents jika bukan Ilyas.
- Tidak dapat download PL/MTC jika bukan Sales.
- Melihat status file tanpa menerima URL download.

### E. Ilyas Noor Firdaus

- Dapat upload Packing List.
- Dapat upload MTC.
- Dapat replace.
- Tidak otomatis dapat download jika bukan Sales.
- Upload menyinkronkan seluruh invoice.
- Upload tidak memengaruhi invoice lain.
- `updated_by` tercatat.

### F. Administrator

- Dapat manage.
- Tidak dapat upload jika bukan Ilyas.
- Tidak dapat download jika bukan Sales.

### G. Unauthorized User

- Tidak melihat menu.
- Index 403.
- Data 403.
- Detail 403.
- Invoice page 403.
- Direct attachment URL 403.
- Mutation endpoint 403.

### H. Invoice Scoping

- Detail hanya menampilkan invoice anchor.
- Request filter tidak dapat mengganti mandatory scope.
- Invoice null hanya menampilkan anchor row.
- Scoped export hanya berisi invoice anchor.
- Summary hanya menghitung invoice anchor.

### I. Bulk Update Security

- Valid IDs pada invoice berhasil.
- ID dari invoice lain ditolak.
- Invoice string tidak cocok ditolak.
- Partial valid ID set tidak diterima diam-diam.
- Transaction rollback jika update gagal.

### J. File Security

- Invalid extension ditolak.
- File di atas 10 MB ditolak.
- Missing file ditolak.
- File baru dihapus jika DB transaction gagal.
- Replaced file dihapus setelah commit.
- File lama tidak dihapus jika masih direferensikan.
- Filename tidak dapat menyebabkan path traversal.
- Missing storage file menghasilkan 404.
- Non-Sales direct download menghasilkan 403.

### K. Form Consistency

- PL/MTC field tidak ada.
- Create tanpa file berhasil.
- Edit tidak mengosongkan path.
- Create from context mengunci invoice.
- New material mewarisi invoice paths.
- Invoice move tidak membawa source invoice documents.
- Destroy shared row tidak menghapus shared file.

### L. UI Response Assertions

Index:

```text
Tidak mengandung Add Material
Tidak mengandung Import
Tidak mengandung Template
Tidak mengandung Export
Tidak mengandung Invoice View
Tidak mengandung tombol Edit
Tidak mengandung tombol Delete
Mengandung tombol Detail
```

Detail manager:

```text
Mengandung Add Material
Mengandung Import
Mengandung Template
Mengandung Export
Mengandung Edit
Mengandung Delete
```

Detail Sales:

```text
Tidak mengandung mutation control
```

## COMMANDS

Jalankan:

```bash
php artisan route:list --name=outstanding-materials
php artisan test --filter=OutstandingMaterial
```

Kemudian full suite:

```bash
php artisan test
```

Jika full suite terlalu besar, tetap jalankan test terkait dan dokumentasikan alasan full suite tidak dijalankan.

Jalankan formatter/linter yang memang tersedia di repository.

Jangan mengarang command yang tidak tersedia.

## ROUTE VALIDATION

Pastikan:

- Specific routes muncul sebelum wildcard.
- Method benar.
- Name benar.
- Middleware benar.
- Tidak ada duplicate route name.
- Tidak ada collision.

## MANUAL QA

Lakukan minimal:

### Manager

1. Buka index.
2. Pastikan read-only.
3. Buka detail.
4. Pastikan toolbar manage tersedia.
5. Add material.
6. Edit material.
7. Delete material.
8. Import.
9. Scroll table.

### Sales

1. Pastikan menu terlihat.
2. Buka index.
3. Buka detail.
4. Buka invoice list.
5. Download PL.
6. Download MTC.
7. Coba URL mutation langsung dan pastikan 403.

### Ilyas

1. Upload Packing List.
2. Upload MTC.
3. Replace file.
4. Pastikan seluruh row invoice ter-update.
5. Pastikan invoice lain tidak berubah.

### Unauthorized User

1. Menu tidak terlihat.
2. Direct URL menghasilkan 403.

## FINAL SECURITY REVIEW

Periksa manual:

```text
No frontend-only authorization
No invoice scope controlled only by request
No material ID cross-invoice mutation
No unrestricted attachment URL
No arbitrary filesystem path
No early file deletion
No shared-file accidental deletion
No Sales manage escalation
No manager document upload escalation
No non-Sales document download
```

## FINAL REPORT FORMAT

### 1. Executive Summary

Ringkasan implementasi final.

### 2. Files Changed

Daftar file dan fungsi perubahan.

### 3. Final Access Matrix

| User Type | View | Manage | Upload PL/MTC | Download PL/MTC |
|---|---:|---:|---:|---:|

### 4. Routes

Daftar route baru atau berubah.

### 5. Controller Changes

Method baru, refactor, dan authorization.

### 6. UI Changes

Index, detail, invoice, form, sticky.

### 7. Database Impact

Nyatakan secara eksplisit:

```text
Migration added: Yes/No
Schema changed: Yes/No
```

### 8. Storage Impact

Directory, replace strategy, orphan prevention.

### 9. Tests

Cantumkan:

```text
Commands
Passed
Failed
Skipped
Existing failures
New failures
```

### 10. Manual QA

Hasil per user type.

### 11. Security Validation

Invoice scoping, authorization, file access, bulk update.

### 12. Remaining Risks

Hanya risiko nyata.

Jangan menyembunyikan kegagalan.

## FINAL ACCEPTANCE CRITERIA

Project dianggap selesai jika:

1. Index read-only.
2. Index hanya memiliki tombol detail.
3. Semua toolbar operasional pindah ke detail.
4. Detail menampilkan invoice-scoped material.
5. Invoice null aman.
6. Invoice page memiliki detail, PL, dan MTC.
7. Upload hanya Ilyas, termasuk administrator non-Ilyas harus ditolak.
8. Download hanya Sales, termasuk manager/legacy viewer non-Sales harus ditolak.
9. Sales melihat menu berdasarkan active job position.
10. Sales tidak memperoleh mutation access.
11. Form tidak memiliki upload PL/MTC.
12. Dokumen tersinkron per invoice.
13. File lama tidak terhapus salah.
14. Material cross-invoice tidak dapat dimutasi.
15. Sticky header dan filter bekerja.
16. Tidak ada framework/CDN baru.
17. Tidak ada migration yang tidak diperlukan.
18. Test terkait lulus.
19. Full suite tidak menambah failure baru.
20. Final report lengkap.

---

# FINAL NON-NEGOTIABLE INVARIANTS

Seluruh mission dianggap gagal apabila salah satu kondisi berikut terjadi:

1. Index masih mengirim Edit/Delete untuk manager.
2. Invoice detail mandatory scope masih dapat dioverride dari request.
3. `material_ids` invoice lain dapat ikut bulk update.
4. Administrator non-Ilyas dapat upload PL/MTC.
5. User non-Sales dapat download PL/MTC melalui direct URL.
6. Active Sales mendapat akses create/edit/delete/import/update/upload.
7. Legacy viewer kehilangan akses melihat modul.
8. Upload satu jenis dokumen mengosongkan jenis dokumen lain.
9. File lama dihapus sebelum transaction berhasil.
10. File yang masih direferensikan row lain ikut terhapus.
11. Material baru atau pindah invoice membawa document path dari invoice yang salah.
12. Route spesifik ditempatkan setelah wildcard dan mengalami collision.
13. Agent mengklaim test atau QA berhasil tanpa menjalankannya.
14. Agent menjalankan test mutasi pada database non-testing.

