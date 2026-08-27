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
