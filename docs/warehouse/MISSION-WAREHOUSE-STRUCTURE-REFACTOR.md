# MISSION: Refactor Struktur File/Folder — Menu Warehouse
**Repo:** `poyipoy/fastware_adsi_1` · **Modul:** Warehouse (Consumable, Transaksi, Dashboard, Kategori, Scan, Export, Adjustment)
**Tipe misi:** Structural refactor (folder/file count reduction) — **BUKAN** perubahan behavior/business logic
**Mode eksekusi:** Autonomous agent (Codex CLI, plan mode), commit per fase, hard-stop jika ada penyimpangan dari asumsi di bawah

---

## 0. Konteks

Modul Warehouse saat ini **sudah rapi secara arsitektur** (domain-driven: `Controllers/Warehouse`, `Services/Warehouse`, `Models/Warehouse`, dst — pola yang sama juga dipakai modul lain seperti `KnowledgeManagement`). Masalahnya bukan "berantakan", tapi **jumlah file/folder yang terlalu banyak untuk ukuran fiturnya** — banyak file kecil (20–30 baris) yang tersebar di banyak folder bertingkat.

Tujuan misi ini: **mengurangi jumlah file dan folder** modul Warehouse tanpa mengubah behavior, route, nama route, atau isi logic apa pun. Ini murni house-keeping struktural.

**Fakta teknis penting yang menentukan strategi (sudah diverifikasi dari `composer.json`):**
```json
"config": { "optimize-autoloader": true, ... }
```
Karena `optimize-autoloader: true` di-set di level `config` (bukan cuma flag CLI `-o`), Composer **selalu** generate classmap yang di-scan dari isi file (bukan cuma dari nama file), di semua environment. Artinya **aman** menaruh lebih dari satu class/enum dalam satu file PHP di project ini — autoloading tidak akan patah. Ini yang membuka opsi merge file kecil tanpa trik aneh-aneh. Tetap jalankan `composer dump-autoload` setiap kali struktur file class berubah.

---

## 1. Prinsip & Batasan (Non-Negotiable)

1. **Namespace dan nama class TIDAK BOLEH berubah**, kecuali eksplisit disebut di Fase 4 (dan itu pun optional). Kalau class dipindah ke file lain tapi namespace/nama tetap sama, tidak ada file lain yang perlu diedit (semua `use` statement & type-hint tetap valid).
2. **Tidak ada perubahan pada `routes/web.php`.** Route group `warehouse.*` (baris ~1076–1127) sudah bersih, satu blok, tidak fragmented — di luar scope.
3. **Tidak ada perubahan pada isi Controllers atau Services** kecuali Fase 6 (optional, prioritas rendah).
4. **Views yang berbeda tujuan (index/create/show/edit/reverse) TIDAK digabung jadi satu file.** Menggabungkan blade view yang berbeda tanggung jawab lewat kondisional itu justru menurunkan keterbacaan — bukan tujuan misi ini.
5. Setiap fase = 1 commit terpisah, dengan pesan commit yang jelas (`refactor(warehouse): ...`).
6. Setelah setiap fase: jalankan `composer dump-autoload`, lalu `php artisan route:list --name=warehouse` untuk pastikan semua route masih resolve, lalu smoke-test manual tiap halaman yang tersentuh (lihat checklist Bagian 5).
7. Jangan hapus file apa pun yang penggunaannya tidak 100% terverifikasi lewat grep. Kalau ragu, stop dan laporkan (lihat Bagian 4).

---

## 2. Inventaris Kondisi Saat Ini (Grounded — hasil analisis langsung ke repo)

| Area | Path | Jumlah File | Total Baris |
|---|---|---|---|
| Controllers | `app/Http/Controllers/Warehouse/` | 8 | 634 |
| Form Requests | `app/Http/Requests/Warehouse/` | 12 | 384 |
| Services | `app/Services/Warehouse/` | 8 | 1083 |
| Models | `app/Models/Warehouse/` | 4 | 235 |
| Data (DTO) | `app/Data/Warehouse/` | 3 | 139 |
| Enums | `app/Enums/Warehouse/` | 2 | 30 |
| Exceptions | `app/Exceptions/Warehouse/` | 1 | 16 |
| Exports | `app/Exports/Warehouse/` | 1 | 53 |
| Middleware | `app/Http/Middleware/EnsureWarehousePermission.php` | 1 | 18 |
| Console Command (dev tool, di luar scope) | `app/Console/Commands/WarehouseResetAndSeedApprovedCommand.php` | 1 | – |
| View pages | `resources/views/warehouse/**` | 12 | – |
| View components | `resources/views/components/warehouse/` | 6 | – |
| **TOTAL** | | **59 file** di **14 folder berbeda** | |

Detail per file yang relevan untuk keputusan merge/hapus ada di Appendix A & B.

---

## 3. Rencana Perubahan per Fase

### FASE 0 — Persiapan
- Buat branch baru: `refactor/warehouse-structure-cleanup`.
- Pastikan working tree bersih (`git status`) sebelum mulai.

### FASE 1 — Hapus dead file (zero risk, wajib)
File `resources/views/components/warehouse/action-bar.blade.php` **tidak pernah dipakai**. Sudah diverifikasi lewat grep menyeluruh: tidak ada satupun pemanggilan `<x-warehouse.action-bar>` di seluruh `resources/views`. Satu-satunya kemunculan string "action-bar" lain adalah class CSS `warehouse-action-bar` di `warehouse/transactions/show.blade.php` — itu div HTML biasa, bukan pemanggilan komponen Blade, tidak terkait.

**Aksi:**
- Hapus `resources/views/components/warehouse/action-bar.blade.php`.
- Hasil: Components 6 → 5 file.

### FASE 2 — Merge Data (DTO) & Enum yang berpasangan erat (wajib)

**2a. Data/Warehouse: gabung `WarehouseStockCommand` + `WarehouseStockResult`**
Kedua class ini adalah pasangan command/result yang SELALU dipakai bersama di `WarehouseStockService.php` dan `WarehouseVerifierPolicy.php`. `WarehouseDashboardFilter.php` (96 baris) TIDAK digabung karena dipakai di flow berbeda (filter dashboard) dan sudah cukup besar sendiri.

**Aksi:**
- Buat file baru `app/Data/Warehouse/WarehouseStockData.php` berisi KEDUA class persis seperti aslinya (namespace `App\Data\Warehouse`, nama class tidak berubah):
  ```php
  <?php

  namespace App\Data\Warehouse;

  use App\Enums\Warehouse\WarehouseTransactionType;
  use App\Models\Warehouse\WarehouseStockTransaction;

  final readonly class WarehouseStockCommand
  {
      public function __construct(
          public WarehouseTransactionType $type,
          public int $consumableId,
          public string $quantity,
          public int $verifiedUserId,
          public ?string $referenceNumber = null,
          public ?string $purpose = null,
          /** @deprecated Use storageLocation for new Stock In commands. */
          public ?string $usageLocation = null,
          public ?string $notes = null,
          public ?string $idempotencyKey = null,
          public ?int $createdBy = null,
          public ?string $adjustmentReasonCategory = null,
          public ?string $adjustmentReason = null,
          public ?string $adjustmentDirection = null,
          public ?int $reversalOfId = null,
          public ?string $verificationCodeHash = null,
          public ?string $storageLocation = null,
      ) {
      }
  }

  final readonly class WarehouseStockResult
  {
      public function __construct(
          public WarehouseStockTransaction $transaction,
          public bool $idempotentReplay = false,
      ) {
      }
  }
  ```
- Hapus `app/Data/Warehouse/WarehouseStockCommand.php` dan `app/Data/Warehouse/WarehouseStockResult.php`.
- **Tidak perlu edit file lain** — namespace & nama class sama persis.
- Hasil: Data 3 → 2 file.

**2b. Enums/Warehouse: gabung `WarehouseTransactionType` + `WarehouseVerificationStatus`**

**Aksi:**
- Buat file baru `app/Enums/Warehouse/WarehouseEnums.php`:
  ```php
  <?php

  namespace App\Enums\Warehouse;

  enum WarehouseTransactionType: string
  {
      case IN = 'IN';
      case OUT = 'OUT';
      case ADJUSTMENT = 'ADJUSTMENT';
      case REVERSAL = 'REVERSAL';

      public function isInbound(): bool
      {
          return $this === self::IN;
      }

      public function isOutbound(): bool
      {
          return $this === self::OUT;
      }
  }

  enum WarehouseVerificationStatus: string
  {
      case SUCCESS = 'SUCCESS';
      case FAILED = 'FAILED';
  }
  ```
- Hapus `WarehouseTransactionType.php` dan `WarehouseVerificationStatus.php` yang lama.
- Hasil: Enums 2 → 1 file.

### FASE 3 — Konsolidasi Form Requests per fitur (wajib, dampak terbesar)

12 Request class saat ini semuanya kecil (21–49 baris) dengan pola seragam (`authorize()` delegasi ke `WarehouseAccessService`, `rules()` array validasi). Gabungkan berdasarkan controller/fitur yang memakainya — **bukan digabung sembarangan**, supaya tetap logis dibaca:

| File baru | Class di dalamnya (nama & namespace TIDAK berubah) | Dipakai oleh controller |
|---|---|---|
| `TransactionRequests.php` | `StoreWarehouseTransactionRequest`, `ReverseWarehouseTransactionRequest` | `WarehouseTransactionController` |
| `ScanRequests.php` | `ScanWarehouseItemRequest`, `ScanWarehouseUserRequest` | `WarehouseScanController` |
| `ConsumableRequests.php` | `StoreWarehouseConsumableRequest`, `UpdateWarehouseConsumableRequest`, `StoreWarehouseOpeningBalanceRequest` | `WarehouseConsumableController` |

5 Request lain **TETAP terpisah** (masing-masing satu-satunya untuk fitur/controller-nya, tidak ada pasangan alami untuk digabung tanpa memaksa relasi yang tidak ada): `WarehouseDashboardFilterRequest`, `WarehouseTransactionHistoryRequest`, `StoreWarehouseAdjustmentRequest`, `WarehouseTransactionExportRequest`, `StoreWarehouseCategoryRequest`.

**Aksi per file gabungan:**
- Ambil isi PERSIS dari file-file lama (namespace `App\Http\Requests\Warehouse`, nama class sama), tempel berurutan dalam satu file baru.
- Hapus file-file lama yang sudah digabung.
- **Tidak perlu edit Controller** — type-hint di method controller (mis. `ScanWarehouseItemRequest $request`) tetap resolve normal karena nama class & namespace tidak berubah.
- Hasil: Requests 12 → 8 file (3 file gabungan + 5 file mandiri).

### FASE 4 — Flatten folder single-file (OPSIONAL, prioritas rendah)

Catatan: `app/Exceptions/` juga punya folder domain lain (`KnowledgeManagement/`) dengan pola yang sama, jadi flatten ini akan membuat Warehouse **tidak konsisten** dengan pola tersebut. Lakukan hanya jika memang mau folder Warehouse sesedikit mungkin dan tidak masalah beda pola dengan modul lain.

**4a. `app/Exceptions/Warehouse/WarehouseDomainException.php` → `app/Exceptions/WarehouseDomainException.php`**
- Namespace berubah: `App\Exceptions\Warehouse` → `App\Exceptions`.
- File yang WAJIB diupdate `use` statement-nya (sudah diverifikasi lewat grep, total 6 file):
  - `app/Services/Warehouse/WarehouseIdentityResolver.php`
  - `app/Services/Warehouse/WarehouseVerifierPolicy.php`
  - `app/Services/Warehouse/WarehouseStockService.php`
  - `app/Services/Warehouse/WarehouseQuantity.php`
  - `app/Http/Controllers/Warehouse/WarehouseTransactionController.php`
  - `app/Http/Controllers/Warehouse/WarehouseScanController.php`

**4b. `app/Exports/Warehouse/WarehouseTransactionsExport.php` → `app/Exports/WarehouseTransactionsExport.php`**
- Namespace berubah: `App\Exports\Warehouse` → `App\Exports`.
- File yang WAJIB diupdate: `app/Http/Controllers/Warehouse/WarehouseExportController.php`.

Hasil: jumlah file tetap sama, folder berkurang 2 (`Exceptions/Warehouse/`, `Exports/Warehouse/` hilang).

### FASE 5 — Flatten folder view single-file (OPSIONAL)

Dua folder di `resources/views/warehouse/` cuma berisi SATU file:
- `categories/index.blade.php` (39 baris) → pindah jadi `warehouse/categories.blade.php`
- `adjustments/create.blade.php` (30 baris) → pindah jadi `warehouse/adjustments-create.blade.php`

**Aksi:**
- Pindahkan file, lalu update referensi `view('warehouse.categories.index', ...)` dan `view('warehouse.adjustments.create', ...)` di:
  - `app/Http/Controllers/Warehouse/WarehouseCategoryController.php`
  - `app/Http/Controllers/Warehouse/WarehouseStockAdjustmentController.php`
- Cek juga apakah ada `route()`/`redirect()->route()` yang memanggil view name secara langsung (biasanya tidak, tapi verifikasi).

Hasil: folder berkurang 2 (`categories/`, `adjustments/` hilang), jumlah file view tetap sama.

*(Tidak direkomendasikan menggabungkan `dashboard/partials/recent-transactions.blade.php` ke `dashboard/index.blade.php` — index sudah 146 baris, digabung jadi ~195 baris kurang worth-it untuk 1 file yang cuma dipakai sekali.)*

### FASE 6 — Co-locate service kecil (OPSIONAL, prioritas paling rendah)

`WarehouseTransactionNumberGenerator.php` cuma 13 baris, di-inject via constructor ke `WarehouseStockService`. **Jangan** dijadikan method private di `WarehouseStockService` — itu akan menghilangkan kemampuan mock di test (dependency injection-nya ada gunanya). Kalau tetap mau kurangi 1 file, taruh sebagai class kedua di file yang sama dengan `WarehouseStockService.php` (namespace & nama class tetap sama, jadi tetap injectable & mockable). Kalau ragu-ragu nilainya kecil dibanding risikonya, **skip fase ini**.

---

## 4. Kondisi Hard-Stop

Berhenti dan laporkan ke user (jangan lanjut otomatis) jika:
- Ditemukan pemakaian file/class yang **berbeda** dari yang tercatat di misi ini (misalnya `action-bar` ternyata dipakai di tempat yang tidak ter-grep, atau ada Request/Data/Enum class yang di-reference secara dinamis via string/reflection).
- `composer dump-autoload` atau `php artisan route:list` gagal / error setelah suatu fase.
- Ada test suite yang jalan (`vendor/bin/pest` atau `php artisan test`) dan ada test yang sebelumnya hijau jadi merah.
- Konflik git / working tree tidak bersih di awal fase.
- File yang direncanakan untuk dihapus/dipindah ternyata sudah tidak ada di posisi yang diasumsikan (kemungkinan repo sudah berubah sejak analisis ini dibuat) — re-verify dengan grep sebelum lanjut.

## 5. Verifikasi Akhir (checklist wajib sebelum PR)

- [ ] `composer dump-autoload` sukses tanpa warning class-not-found.
- [ ] `php artisan route:list --name=warehouse` menampilkan semua route seperti sebelumnya (bandingkan jumlah baris sebelum/sesudah).
- [ ] `php artisan optimize:clear` lalu buka manual tiap halaman warehouse yang tersentuh (dashboard, transactions create/index/show/reverse, consumables index/create/edit/show, categories, adjustments create, scan).
- [ ] Submit 1 transaksi Stock In/Out uji coba end-to-end (kalau ada environment staging) untuk pastikan `WarehouseStockCommand`/`WarehouseStockResult` yang baru digabung tetap berfungsi.
- [ ] `git diff --stat` direview manual — pastikan tidak ada file yang "hilang" tanpa disengaja (bukan cuma dipindah/rename).

## 6. Ringkasan Hasil yang Diharapkan

| Area | Sebelum | Sesudah (wajib: Fase 1–3) | Sesudah (+ opsional Fase 4–6) |
|---|---|---|---|
| Requests | 12 file | 8 file | 8 file |
| Data | 3 file | 2 file | 2 file |
| Enums | 2 file | 1 file | 1 file |
| Components | 6 file | 5 file | 5 file |
| Exceptions/Exports folder | 2 folder domain | 2 folder domain | 0 (flatten) |
| Views folder single-file | 2 folder | 2 folder | 0 (flatten) |
| **Total file modul** | **59** | **52** | **52** (folder makin sedikit) |
| **Total folder modul** | **14** | **14** | **10** |

---

## Appendix A — Peta lengkap file saat ini (hasil `find` + `wc -l`, sudah diverifikasi)

```
app/Http/Controllers/Warehouse/   (8 file, 634 baris — TIDAK disentuh)
  WarehouseCategoryController.php (32)
  WarehouseDashboardController.php (51)
  WarehouseStockAdjustmentController.php (51)
  WarehouseExportController.php (55)
  WarehouseTransactionHistoryController.php (59)
  WarehouseScanController.php (67)
  WarehouseConsumableController.php (140)
  WarehouseTransactionController.php (179)

app/Http/Requests/Warehouse/      (12 file, 384 baris — Fase 3)
  ScanWarehouseItemRequest.php (21)
  ReverseWarehouseTransactionRequest.php (23)
  StoreWarehouseOpeningBalanceRequest.php (24)
  UpdateWarehouseConsumableRequest.php (26)
  StoreWarehouseAdjustmentRequest.php (28)
  WarehouseTransactionExportRequest.php (30)
  WarehouseTransactionHistoryRequest.php (30)
  WarehouseDashboardFilterRequest.php (31)
  ScanWarehouseUserRequest.php (37)
  StoreWarehouseCategoryRequest.php (37)
  StoreWarehouseTransactionRequest.php (48)
  StoreWarehouseConsumableRequest.php (49)

app/Services/Warehouse/           (8 file, 1083 baris — TIDAK disentuh, kecuali Fase 6 opsional)
  WarehouseTransactionNumberGenerator.php (13)
  WarehouseVerifierPolicy.php (83)
  WarehouseAccessService.php (88)
  WarehouseQuantity.php (100)
  WarehouseResetBackupService.php (126)
  WarehouseIdentityResolver.php (164)
  WarehouseDashboardService.php (184)
  WarehouseStockService.php (325)

app/Models/Warehouse/             (4 file, 235 baris — TIDAK disentuh, 1:1 dengan tabel DB)
  WarehouseConsumableCategory.php (41)
  WarehouseVerificationLog.php (44)
  WarehouseStockTransaction.php (70)
  WarehouseConsumable.php (80)

app/Data/Warehouse/               (3 file, 139 baris — Fase 2a)
  WarehouseStockResult.php (14)
  WarehouseStockCommand.php (29)
  WarehouseDashboardFilter.php (96)   <- TIDAK digabung

app/Enums/Warehouse/              (2 file, 30 baris — Fase 2b)
  WarehouseVerificationStatus.php (9)
  WarehouseTransactionType.php (21)

app/Exceptions/Warehouse/         (1 file, 16 baris — Fase 4 opsional)
  WarehouseDomainException.php

app/Exports/Warehouse/            (1 file, 53 baris — Fase 4 opsional)
  WarehouseTransactionsExport.php

app/Http/Middleware/
  EnsureWarehousePermission.php (18) — TIDAK disentuh

resources/views/warehouse/        (12 file — Fase 5 opsional untuk 2 di antaranya)
  layout.blade.php
  dashboard/index.blade.php (146)
  dashboard/partials/recent-transactions.blade.php (49)
  consumables/{form,index,show}.blade.php
  categories/index.blade.php (39)        <- Fase 5
  transactions/{create,index,reverse,show}.blade.php
  adjustments/create.blade.php (30)      <- Fase 5

resources/views/components/warehouse/  (6 file — Fase 1)
  panel.blade.php          (dipakai 18x)
  page-header.blade.php    (dipakai 20x)
  status-badge.blade.php   (dipakai 10x)
  empty-state.blade.php    (dipakai 9x)
  filter-actions.blade.php (dipakai 2x)
  action-bar.blade.php     (dipakai 0x — DEAD, hapus di Fase 1)
```

## Appendix B — Route group Warehouse (tidak diubah, hanya referensi)

Lokasi: `routes/web.php` baris ~1076–1127, di dalam `Route::prefix('warehouse')->name('warehouse.')->group(...)`. Satu blok utuh, tidak fragmented di file lain — dikonfirmasi lewat grep, tidak perlu direfactor.
