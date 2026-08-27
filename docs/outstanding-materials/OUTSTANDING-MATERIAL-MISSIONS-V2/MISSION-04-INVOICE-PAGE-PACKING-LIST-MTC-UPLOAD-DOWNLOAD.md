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
