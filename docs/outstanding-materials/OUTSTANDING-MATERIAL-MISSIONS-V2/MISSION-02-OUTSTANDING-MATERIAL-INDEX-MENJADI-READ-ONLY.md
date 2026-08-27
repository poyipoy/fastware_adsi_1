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
