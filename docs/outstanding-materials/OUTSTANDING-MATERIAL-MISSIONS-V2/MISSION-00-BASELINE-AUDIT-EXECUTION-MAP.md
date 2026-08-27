# EXECUTION CONTEXT — OUTSTANDING MATERIAL V2

Repository: `poyipoy/fastware_adsi_1`  
Framework: Laravel 10  
Frontend: Bootstrap 5.3.2, Blade, jQuery, DataTables  
Database: MySQL

## Final Capability Policy

| Capability | Rule |
|---|---|
| View | Legacy Outstanding Material viewer OR active Sales job user |
| Manage | Existing manager rule, including role_id 1 and legacy manager names |
| Upload PL/MTC | Only `ILYAS NOOR FIRDAUS` |
| Download PL/MTC | Only active Sales job user |

Legacy viewers such as Jessica Paune, Fajar Bagaskara, and Vivian Angelika retain module view access but do not receive PL/MTC download unless they are also active Sales users.

## Security Invariants

- Mandatory invoice scope comes from backend route-model binding.
- Never trust invoice number or material IDs from the client.
- Validate every bulk-update material ID belongs to the submitted invoice.
- Do not delete files before database commit.
- Do not delete files still referenced by another row.
- No frontend-only authorization.
- Use testing database for mutating tests.
- Read current code before patching; line numbers in the mission are orientation only.
- Stop after completing this mission.

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
