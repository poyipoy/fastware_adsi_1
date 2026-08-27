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
