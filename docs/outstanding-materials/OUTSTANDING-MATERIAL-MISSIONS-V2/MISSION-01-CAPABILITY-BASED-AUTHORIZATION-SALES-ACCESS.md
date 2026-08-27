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
