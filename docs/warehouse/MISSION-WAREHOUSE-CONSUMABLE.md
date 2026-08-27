# MISSION PLAN — Warehouse Consumable Monitoring

**Project:** Fastware ADSI  
**Repository:** `poyipoy/fastware_adsi_1`  
**Framework:** Laravel 10, PHP 8.1+, MySQL, Blade, Bootstrap 5.3.2  
**Module:** Warehouse  

```text
Warehouse
├── Dashboard Consumable
└── Form Stock In/Out
```

Dokumen ini menjadi source of truth untuk planning dan eksekusi modul Warehouse Consumable. Setiap mission dirancang sebagai satu sesi kerja agent yang dapat diselesaikan, diuji, dan ditutup secara independen tanpa memutus dependency antarmission.

---

## 1. Executive Summary

Modul Warehouse Consumable digunakan untuk mencatat dan memonitor pergerakan barang habis pakai di Gudang DS8.

```text
STOCK IN
Warehouse → pilih Stock In → scan barang → input quantity + lokasi penyimpanan
          → scan ID karyawan → konfirmasi → stok bertambah dan lokasi tersimpan

STOCK OUT
User → pilih Stock Out → scan barang → input quantity saja
     → scan ID karyawan → konfirmasi → stok berkurang
```

Sistem menyediakan dua surface utama:

1. **Dashboard Consumable** untuk monitoring stok, tren transaksi, low stock, penggunaan per barang, pengguna, dan section.
2. **Form Stock In/Out** untuk transaksi cepat berbasis scanner barcode/QR dan verifikasi identitas pelaku transaksi.

Implementasi harus menyatu dengan arsitektur Fastware ADSI. Jangan membuat aplikasi React/Tailwind terpisah, jangan menambahkan framework UI baru, dan jangan bergantung pada CDN baru. Prototype HTML digunakan sebagai referensi alur interaksi, bukan stack implementasi.

---

## 2. Repository Basis

Implementasi mengikuti kondisi repository berikut:

- Aplikasi menggunakan Laravel 10.
- Route aplikasi masih terpusat di `routes/web.php`.
- Layout utama dan navbar berada di `resources/views/layout.blade.php`.
- Data pengguna tersedia pada `App\Models\User`.
- Identitas relevan: `id`, `name`, `npk`, `section`, `role_id`, dan `is_active`.
- Repository memiliki pengaturan akses menu melalui `LayoutMenuController` dan `MenuAccessStorage`.
- UI existing menggunakan Blade dan aset Bootstrap lokal.
- Tidak ada modul warehouse consumable existing yang terbukti dapat langsung digunakan ulang.
- Asset borrowing, return date, overdue tools, dan loan reminder pada prototype berada di luar scope.

---

## 3. Locked Product Decisions

### 3.1 Scope

- Modul hanya menangani **barang consumable**.
- Asset, tools pinjaman, pengembalian, due date, dan overdue reminder tidak termasuk.
- Satu transaksi memproses satu barang.
- Setiap transaksi menghasilkan satu stock movement immutable.
- Perubahan stok tidak boleh dilakukan dengan update database manual dari UI.
- Koreksi dilakukan melalui reversal atau stock adjustment berotorisasi.
- Sistem tidak membutuhkan queue worker untuk MVP.
- Scanner utama adalah USB/Bluetooth HID scanner yang bertindak seperti keyboard.
- Camera-based scanner dan offline mode tidak termasuk MVP.
- Input barcode manual tetap tersedia sebagai fallback.

### 3.2 Stock In

- Dilakukan Warehouse, administrator, atau PIC berwenang.
- Menambah stok.
- Quantity dan lokasi penyimpanan wajib diisi pada form transaksi.
- Lokasi menjadi lokasi aktif item dan disalin ke snapshot transaksi.
- Verifikasi pelaku tetap wajib walaupun operator sudah login.

### 3.3 Stock Out

- Dilakukan user aktif yang memiliki akses.
- Mengurangi stok.
- Tidak boleh menghasilkan stok negatif.
- Quantity adalah satu-satunya field detail yang perlu diisi operator.
- Lokasi item ditampilkan sebagai konteks read-only dari master; tidak dapat diubah pada Stock Out.
- Section disimpan sebagai snapshot saat transaksi.

### 3.4 Verifikasi identitas

- MVP menggunakan scan barcode/QR ID karyawan.
- PIN tidak diwajibkan untuk transaksi reguler.
- Resolver mengutamakan mapping kartu pada tabel warehouse.
- Fallback ke `users.npk` dapat diaktifkan apabila barcode kartu sama dengan NPK.
- User atau kartu nonaktif tidak dapat memverifikasi transaksi.
- Scan gagal dicatat dalam verification log tanpa membuat transaksi.

### 3.5 Quantity

- Database menggunakan `DECIMAL(15,3)` agar mendukung `pcs`, `box`, `roll`, `liter`, `kg`, dan satuan pecahan.
- Master item memiliki flag `allow_fraction`.
- Item non-fraction hanya menerima bilangan bulat.
- Quantity harus lebih besar dari nol.

---

## 4. Global Engineering Guardrails

### 4.1 Database safety

Sebelum migration atau test yang memodifikasi database:

```text
APP_ENV harus testing
DB_DATABASE harus berakhiran _testing
```

Wajib merekam baseline:

```bash
php artisan test
php artisan route:list
php artisan view:cache
```

Jumlah pass, fail, dan error existing dicatat. Pengembangan tidak boleh menambah regression baru.

### 4.2 UI stack

Wajib:

- Blade.
- Bootstrap 5.3.2 bawaan proyek.
- Vanilla JavaScript atau JavaScript existing.
- Icon set lokal yang sudah tersedia.
- `@push('styles')` dan `@push('scripts')`.

Dilarang:

- React atau Vue baru.
- Tailwind.
- Alpine atau jQuery baru.
- CDN dan UI kit baru.
- Menyalin prototype secara langsung.
- Build pipeline baru hanya untuk modul ini.

### 4.3 Domain integrity

- Semua mutasi stok berada di dalam `DB::transaction()`.
- Item diambil menggunakan `lockForUpdate()`.
- `stock_before` dan `stock_after` disimpan sebagai snapshot.
- Nomor transaksi dan idempotency key harus unik.
- Store harus aman terhadap double-submit.
- Validasi server adalah source of truth.
- Menu tersembunyi bukan authorization; route dan service tetap melindungi akses.

### 4.4 Naming

```text
App\Http\Controllers\Warehouse
App\Http\Requests\Warehouse
App\Models\Warehouse
App\Services\Warehouse
resources/views/warehouse
tests/Feature/Warehouse
```

Route memakai prefix `warehouse` dan name prefix `warehouse.`.

---

## 5. UI Direction — Clean Industrial Interface

UI harus terasa seperti aplikasi operasional internal: cepat, padat, stabil, dan jelas. Bukan landing page dan bukan kumpulan card dekoratif.

### 5.1 Visual principles

- Latar netral terang.
- Navy Fastware sebagai warna navigasi dan primary action.
- Hijau hanya untuk Stock In dan berhasil.
- Oranye untuk Stock Out.
- Merah hanya untuk error, out of stock, dan kondisi kritis.
- Radius maksimum sekitar `8px`.
- Border tipis lebih diutamakan daripada shadow tebal.
- Shadow hanya untuk sticky panel atau dropdown.
- Typography mengikuti layout existing.
- Ikon dipakai untuk mempercepat pemindaian informasi, bukan dekorasi.

### 5.2 Anti-slop rules

Jangan menggunakan:

- Gradient atau glassmorphism.
- Giant hero section.
- Ilustrasi abstrak atau stock photo warehouse.
- Animasi mengambang.
- Card dengan radius berlebihan.
- Badge berwarna untuk setiap nilai.
- Copy pemasaran seperti “Transform your inventory experience”.
- Chart tanpa fungsi keputusan.
- Tiga lapis navigation untuk dua halaman.
- Modal untuk alur utama.
- Statistik vanity.

### 5.3 Dashboard composition

```text
┌──────────────────────────────────────────────────────────────────┐
│ Dashboard Consumable                          [Kelola Master]     │
│ Ringkasan stok dan aktivitas                  [Export]            │
├──────────────────────────────────────────────────────────────────┤
│ Filter tanggal | kategori | item | section | type | [Terapkan]   │
├────────────┬────────────┬────────────┬────────────┬───────────────┤
│ Item aktif │ Stock sehat│ In hari ini│Out hari ini│ Low stock     │
├───────────────────────────────────────────────┬──────────────────┤
│ Tren Stock In vs Stock Out                    │ Top Usage        │
├───────────────────────────────────────────────┴──────────────────┤
│ Low Stock / Out of Stock                                         │
├──────────────────────────────────────────────────────────────────┤
│ Transaksi Terbaru                                                │
└──────────────────────────────────────────────────────────────────┘
```

### 5.4 Form composition

```text
┌──────────────────────────────────────────────────────────────────┐
│ Form Stock In/Out                                                │
│ [ Stock In ] [ Stock Out ]                                       │
├──────────────────────────────────────┬───────────────────────────┤
│ Scan barang                          │ Ringkasan transaksi       │
│ Item result                          │ Type, item, qty           │
│ Quantity & detail                    │ Stock before/after        │
│ Scan ID karyawan                     │ Verifier                  │
│ Employee result                      │                           │
│ [Reset]                 [Konfirmasi] │                           │
└──────────────────────────────────────┴───────────────────────────┘
```

Mobile menggunakan satu kolom, filter collapsible, KPI dua kolom, dan touch target minimal `44px`.

### 5.5 Required interaction states

- Initial dan loading.
- Success.
- Validation error.
- Item/user not found.
- Item/user inactive.
- Insufficient stock.
- Duplicate submit blocked.
- Server error.
- Empty dashboard.
- No filter result.

Pesan harus spesifik, misalnya:

```text
Stok Isolasi Listrik tidak mencukupi. Tersedia 6 roll.
```

---

# MISSION 00 — Baseline, Discovery, and Safety Gate

## Goal

Membekukan kondisi awal repository dan memastikan pengembangan Warehouse dilakukan pada source of truth yang benar tanpa merusak modul existing.

## Dependency

Tidak ada. Selalu dijalankan pertama.

## In Scope

- Verifikasi branch dan commit awal.
- Verifikasi Laravel, PHP, Bootstrap, layout, route, authentication, dan model User.
- Audit pola menu dan access control.
- Audit apakah master item atau transaksi stok existing dapat digunakan ulang.
- Audit isi barcode ID terhadap `users.npk`.
- Audit format barcode barang.
- Rekam baseline test.
- Konfigurasi awal modul.
- Catatan keputusan discovery.

## Out of Scope

Migration warehouse, menu, form transaksi, dashboard, import master, dan perubahan production data.

## Deliverables

```text
config/warehouse.php
warehouse-docs/WAREHOUSE-DISCOVERY.md
tests/Support/Concerns/GuardsWarehouseTestingDatabase.php
```

Konfigurasi minimum:

```php
return [
    'identity' => [
        'allow_npk_fallback' => false,
    ],
    'transaction' => [
        'require_storage_location_for_in' => true,
        'duplicate_submission_ttl_seconds' => 30,
    ],
    'scanner' => [
        'duplicate_scan_window_ms' => 1500,
        'auto_focus' => true,
    ],
    'dashboard' => [
        'default_period_days' => 30,
        'low_stock_inclusive' => true,
    ],
];
```

Discovery document mencatat commit SHA, versi framework, layout aktif, Bootstrap, middleware auth, mekanisme menu, format NPK/card/barcode, master existing, baseline failure, database engine, dan kebutuhan fraction quantity.

## Implementation Steps

1. Checkout source of truth dari `main`.
2. Jalankan baseline commands.
3. Inspect route, layout, User, dan access storage/config.
4. Search model/migration untuk inventory, consumable, stock, material, barcode, movement.
5. Lakukan read-only sample check.
6. Scan satu ID asli ke plain text field dan bandingkan dengan NPK.
7. Scan barcode item representatif.
8. Tambahkan database testing guard.
9. Tulis discovery document.

## Acceptance Criteria

- Source of truth teridentifikasi.
- Tidak ada production data yang berubah.
- Baseline test terdokumentasi.
- Identity scan strategy berdasarkan sample nyata.
- Format barcode item terdokumentasi.
- Tidak membuat tabel domain duplikat tanpa audit reuse.
- Testing guard memblokir destructive setup di luar `_testing`.
- `route:list` dan `view:cache` baseline tercatat.

## Completion Evidence

```bash
php artisan about
php artisan route:list
php artisan view:cache
php artisan test
```

---

# MISSION 01 — Warehouse Domain Foundation

## Goal

Membangun database, model, enum, dan service untuk stock movement consumable yang konsisten dan aman terhadap concurrency.

## Dependency

MISSION 00 selesai.

## In Scope

- Master category dan consumable.
- Employee card mapping.
- Immutable stock transaction.
- Verification log.
- Enum, model relation, cast.
- Transaction number generator.
- Stock mutation service.
- Constraint, index, factory, dan domain tests.

## Database Design

### `mst_wh_consumable_categories`

```text
id                  BIGINT UNSIGNED PK
code                VARCHAR(30) UNIQUE
name                VARCHAR(100)
description         TEXT NULL
is_active           BOOLEAN DEFAULT TRUE
created_by          BIGINT UNSIGNED NULL FK users
updated_by          BIGINT UNSIGNED NULL FK users
timestamps
```

### `mst_wh_consumables`

```text
id                  BIGINT UNSIGNED PK
category_id         BIGINT UNSIGNED NULL FK
item_code           VARCHAR(50) UNIQUE
barcode             VARCHAR(120) UNIQUE
item_name           VARCHAR(180)
unit                VARCHAR(30)
allow_fraction      BOOLEAN DEFAULT FALSE
current_stock       DECIMAL(15,3) DEFAULT 0
minimum_stock       DECIMAL(15,3) DEFAULT 0
maximum_stock       DECIMAL(15,3) NULL
storage_location    VARCHAR(120) NULL
description         TEXT NULL
is_active           BOOLEAN DEFAULT TRUE
created_by          BIGINT UNSIGNED NULL FK users
updated_by          BIGINT UNSIGNED NULL FK users
timestamps
```

Indexes:

```text
UNIQUE(item_code)
UNIQUE(barcode)
INDEX(category_id, is_active)
INDEX(is_active, current_stock)
```

### `mst_wh_user_cards`

```text
id                  BIGINT UNSIGNED PK
user_id             BIGINT UNSIGNED FK users
card_code           VARCHAR(150) UNIQUE
is_active           BOOLEAN DEFAULT TRUE
registered_by       BIGINT UNSIGNED NULL FK users
registered_at       TIMESTAMP
timestamps
```

### `trs_wh_stock_transactions`

```text
id                      BIGINT UNSIGNED PK
transaction_number      VARCHAR(40) UNIQUE
idempotency_key         CHAR(36) UNIQUE
transaction_type        VARCHAR(10)
consumable_id           BIGINT UNSIGNED FK
quantity                DECIMAL(15,3)
stock_before            DECIMAL(15,3)
stock_after             DECIMAL(15,3)
verified_user_id        BIGINT UNSIGNED FK users
verified_user_name      VARCHAR(180)
verified_user_npk       VARCHAR(80) NULL
verified_user_section   VARCHAR(120) NULL
reference_number        VARCHAR(120) NULL
purpose                 VARCHAR(255) NULL
usage_location          VARCHAR(180) NULL
notes                   TEXT NULL
reversal_of_id          BIGINT UNSIGNED NULL SELF FK
transaction_at          TIMESTAMP
created_by              BIGINT UNSIGNED FK users
timestamps
```

Indexes pada type/date, item/date, verified user/date, section/date, dan reversal relation.

### `log_wh_verifications`

```text
id                    BIGINT UNSIGNED PK
scanned_code_hash     CHAR(64)
user_id               BIGINT UNSIGNED NULL FK users
transaction_id        BIGINT UNSIGNED NULL FK transaction
status                VARCHAR(20)
failure_reason        VARCHAR(120) NULL
verified_at           TIMESTAMP
ip_address            VARCHAR(45) NULL
user_agent            VARCHAR(500) NULL
timestamps
```

Raw card code tidak disimpan dalam log.

## Enums and Services

```text
WarehouseTransactionType: IN, OUT, ADJUSTMENT, REVERSAL
WarehouseVerificationStatus: SUCCESS, FAILED

WarehouseIdentityResolver
WarehouseTransactionNumberGenerator
WarehouseStockService
```

`WarehouseStockService` wajib:

1. Membuka transaction.
2. Lock item.
3. Revalidate item, user, permission, quantity.
4. Menolak insufficient stock.
5. Menghitung stock after.
6. Menyimpan immutable transaction.
7. Update cached current stock.
8. Link verification log.
9. Commit dan mengembalikan canonical result.

## Files

```text
app/Enums/Warehouse/*
app/Models/Warehouse/*
app/Services/Warehouse/*
app/Data/Warehouse/*
database/migrations/*warehouse*
database/factories/Warehouse/*
tests/Feature/Warehouse/WarehouseSchemaTest.php
tests/Feature/Warehouse/WarehouseStockServiceTest.php
tests/Feature/Warehouse/WarehouseConcurrentStockTest.php
```

## Acceptance Criteria

- Fresh migrate, rollback, dan re-migrate berhasil di MySQL testing.
- Duplicate item code, barcode, dan card code ditolak.
- Quantity nol/negatif ditolak.
- Stock Out melebihi stok tidak mengubah data.
- Concurrent OUT tidak dapat overdraw.
- Snapshot before/after akurat.
- Replay idempotency key tidak membuat transaksi kedua.
- User/item inactive ditolak.
- Verification failure tidak membuat transaction.
- Raw card code tidak tersimpan dalam log.

## Completion Evidence

```bash
php artisan migrate
php artisan migrate:rollback --step=5
php artisan migrate
php artisan test --filter=WarehouseSchemaTest
php artisan test --filter=WarehouseStockServiceTest
php artisan test --filter=WarehouseConcurrentStockTest
```

---

# MISSION 02 — Navigation and Authorization

## Goal

Menambahkan menu utama Warehouse dengan dua submenu dan melindungi menu, route, controller, dan service secara konsisten.

## Dependency

MISSION 01 selesai.

## Navigation Contract

```text
Warehouse
├── Dashboard Consumable
└── Form Stock In/Out
```

Master, history, adjustment, dan export tidak menjadi submenu; semuanya diakses melalui action dari Dashboard sesuai permission.

## Permission Abilities

```text
warehouse.dashboard.view
warehouse.stock-in.create
warehouse.stock-out.create
warehouse.master.manage
warehouse.transaction.view
warehouse.transaction.reverse
warehouse.report.export
```

## Default Access Matrix

| Actor | Dashboard | Stock In | Stock Out | Master | Reverse | Export |
|---|---:|---:|---:|---:|---:|---:|
| Administrator | Yes | Yes | Yes | Yes | Yes | Yes |
| Warehouse PIC | Yes | Yes | Yes | Yes | Conditional | Yes |
| Employee | Limited | No | Yes | No | No | No |
| Management | Yes | No | No | No | No | Yes |

Exact mapping mengikuti hasil MISSION 00.

## Proposed Routes

```php
Route::middleware(['web', 'auth'])
    ->prefix('warehouse')
    ->name('warehouse.')
    ->group(function () {
        Route::get('/dashboard', [WarehouseDashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/transactions/create', [WarehouseTransactionController::class, 'create'])
            ->name('transactions.create');
    });
```

## UI and Access Rules

- Gunakan dropdown navbar existing, bukan sidebar baru.
- Active state berdasarkan `request()->routeIs('warehouse.*')`.
- Menu tampil bila user memiliki minimal satu ability Warehouse.
- Child menu mengikuti ability spesifik.
- User Stock Out-only dapat membuka form tetapi tidak melihat selector Stock In.
- Management tanpa ability transaction ditolak jika membuka URL langsung.

## Files

```text
routes/web.php
resources/views/layout.blade.php
config/server_driven_navigation.php        # bila aktif
app/Http/Middleware/EnsureWarehousePermission.php
app/Providers/AuthServiceProvider.php
app/Http/Controllers/Warehouse/WarehouseDashboardController.php
app/Http/Controllers/Warehouse/WarehouseTransactionController.php
resources/views/warehouse/dashboard/index.blade.php
resources/views/warehouse/transactions/create.blade.php
tests/Feature/Warehouse/WarehouseNavigationAccessTest.php
```

## Acceptance Criteria

- Menu dan child mengikuti permission.
- Dashboard dan form menolak unauthorized direct URL.
- Stock Out-only user tidak melihat Stock In.
- Active state benar.
- Mobile navbar tidak rusak.
- Route name tidak berbenturan.
- Shell view bukan lorem ipsum dan dapat di-cache.

## Completion Evidence

```bash
php artisan route:list --name=warehouse
php artisan view:cache
php artisan test --filter=WarehouseNavigationAccessTest
```

---

# MISSION 03 — Master Consumable and Barcode Readiness

## Goal

Menyediakan pengelolaan master consumable dan mapping ID karyawan tanpa menambah submenu baru.

## Dependency

MISSION 02 selesai.

## Entry Point

Dashboard menampilkan action untuk user dengan `warehouse.master.manage`:

```text
[Kelola Master Consumable]
```

## In Scope

- List, create, edit, activate/deactivate item.
- Category management sederhana.
- Barcode uniqueness.
- Opening balance melalui stock movement.
- Import CSV/XLSX bila format disepakati.
- Card mapping user.
- Search, filter, validation, dan audit metadata.

## Out of Scope

Hard delete item yang pernah ditransaksikan, barcode label designer, supplier master penuh, multi-warehouse, dan direct stock edit.

## Master List UI

Header:

```text
Master Consumable
Data barang yang dapat digunakan pada transaksi Warehouse.
[Import] [Tambah Item]
```

Columns:

```text
Item Code | Barcode | Item Name | Category | Unit
Current Stock | Minimum Stock | Location | Status | Action
```

Rules:

- Barcode sebagai monospace text, bukan visual barcode dekoratif.
- Low stock memakai warning yang jelas.
- Action: Detail, Edit, Activate/Deactivate.
- Tidak ada delete permanen.
- Empty state singkat dan actionable.

## Create/Edit Form

Sections:

1. Identity: item code, barcode, name, category.
2. Stock configuration: unit, fraction, min/max.
3. Storage: location.
4. Status dan notes.

`current_stock` tidak dapat diedit langsung.

## Opening Balance

- Item dibuat dengan stock 0.
- Action `Set Opening Balance` membuat movement adjustment.
- Pelaku dan alasan wajib tercatat.
- Direct edit current stock dilarang.

## Import Contract

```text
item_code
barcode
item_name
category_code
category_name
unit
allow_fraction
minimum_stock
maximum_stock
storage_location
opening_balance
is_active
```

Import harus preview, memisahkan valid/invalid row, menolak duplicate, tidak silent overwrite, membuat opening movement, dan menghasilkan error report.

## User Card Mapping

- Search user berdasarkan NPK/name.
- Scan card code.
- Register dan activate/deactivate.
- Prevent duplicate ownership.
- Jangan tampilkan raw card code pada list umum.

## Routes

```text
GET/POST       warehouse/consumables
GET/PUT        warehouse/consumables/{consumable}/edit
PATCH          warehouse/consumables/{consumable}/status
GET/POST/PUT   warehouse/categories
GET/POST       warehouse/user-cards
PATCH          warehouse/user-cards/{card}/status
```

## Files

```text
WarehouseConsumableController
WarehouseCategoryController
WarehouseUserCardController
Store/Update Warehouse requests
resources/views/warehouse/consumables/*
resources/views/warehouse/user-cards/*
WarehouseConsumableManagementTest
WarehouseUserCardManagementTest
```

## Acceptance Criteria

- Duplicate item code/barcode ditolak.
- Leading zero barcode dipertahankan.
- Current stock tidak bisa diubah dari edit master.
- Item dengan transaction tidak dapat hard delete.
- Inactive item tidak valid untuk scan.
- Opening balance menghasilkan movement.
- Max stock tidak lebih kecil dari min stock.
- Fraction mengikuti item.
- Duplicate card ownership ditolak.
- User inactive tidak menerima card aktif.
- Unauthorized user menerima 403.
- UI mobile usable.

## Completion Evidence

```bash
php artisan test --filter=WarehouseConsumableManagementTest
php artisan test --filter=WarehouseUserCardManagementTest
php artisan view:cache
```

---

# MISSION 04 — Stock In/Out Scanning Workflow

## Goal

Membangun form transaksi utama yang cepat, keyboard-first, aman terhadap double-submit, dan sesuai alur operasional Warehouse DS8.

## Dependency

MISSION 03 selesai dan minimal satu consumable aktif tersedia.

## In Scope

- Selector Stock In/Stock Out.
- Item scan endpoint.
- Employee scan endpoint.
- Quantity dan detail transaksi.
- Live transaction summary.
- Final confirmation.
- Atomic stock commit.
- Success receipt.
- Reset dan next transaction.
- Scanner focus management.
- Error/retry state.
- Authorization, idempotency, verification log.

## Out of Scope

Multi-item batch, camera scanner, offline mode, PIN, asset borrowing, print label, auto replenishment, dan approval reguler.

## Route Contract

```text
GET  warehouse/transactions/create
POST warehouse/scans/item
POST warehouse/scans/user
POST warehouse/transactions
GET  warehouse/transactions/{transaction}
```

## Form State Machine

```text
INITIAL
  → TRANSACTION_TYPE_SELECTED
  → ITEM_VERIFIED
  → DETAILS_VALID
  → USER_VERIFIED
  → READY_TO_CONFIRM
  → SUBMITTING
  → SUCCESS
```

Invalid transition diabaikan atau ditolak. Frontend state tidak menggantikan backend validation.

## Step 1 — Transaction Type

- Render hanya type yang diizinkan.
- Employee melihat Stock Out saja.
- Warehouse PIC melihat Stock In dan Stock Out.
- Gunakan segmented buttons, bukan dropdown.
- Green accent untuk IN dan orange untuk OUT.
- Selected state tidak hanya mengandalkan warna.
- Perubahan type setelah data terisi meminta konfirmasi inline dan mereset field yang tidak kompatibel.

## Step 2 — Item Scan

Input behavior:

- `autocomplete="off"`.
- Preserve leading zero.
- Enter memicu lookup.
- Trim whitespace tanpa uppercase paksa.
- Tombol lookup disabled saat request.
- Focus kembali setelah error.
- Identical scan dalam duplicate window disuppress.
- Manual search tetap tersedia.

Response hanya memuat field operasional:

```json
{
  "data": {
    "id": 10,
    "item_code": "CNS-0001",
    "barcode": "0891234567890",
    "item_name": "Isolasi Listrik",
    "category": "Electrical Consumable",
    "unit": "roll",
    "allow_fraction": false,
    "current_stock": "6.000",
    "minimum_stock": "8.000",
    "storage_location": "Rack C-02",
    "stock_status": "LOW"
  }
}
```

## Step 3 — Quantity and Details

Common field:

- Quantity.

Stock In:

- Storage location wajib diisi.
- Setelah commit, lokasi memperbarui `mst_wh_consumables.storage_location` dan disimpan sebagai snapshot pada `usage_location`.

Stock Out:

- Tidak ada input lokasi manual; lokasi master hanya ditampilkan sebagai konteks read-only.
- Reference, purpose, usage location, dan notes tidak diminta untuk transaksi normal baru.
- Section berasal dari verified user, bukan input client.

Quantity UI:

- Minus, input, plus.
- Step `1` untuk non-fraction.
- Step `0.001` untuk fraction.
- Nilai bilangan bulat ditampilkan tanpa trailing decimal (`3`, bukan `3.000`); nilai pecahan nyata tetap mempertahankan presisinya.
- Tampilkan proyeksi:

```text
Current stock   : 6 roll
Transaction     : -2 roll
Projected stock : 4 roll
```

## Step 4 — Employee Verification

Verifier scan terpisah dari login actor.

```text
Logged-in actor : operator/terminal account
Verified user   : employee whose card was scanned
```

Setelah berhasil tampilkan name, NPK, section, dan status. Jangan tampilkan card code.

Rules:

- Card aktif.
- User aktif.
- Permission sesuai type bila diwajibkan.
- Failure tetap ditulis ke log.
- Mengganti verifier membatalkan ready-to-confirm state.

## Step 5 — Confirmation

Tidak ada stock mutation sebelum konfirmasi eksplisit.

Summary:

```text
Transaction type
Item
Quantity
Stock before
Projected stock after
Storage location (input untuk IN, read-only untuk OUT)
Verified employee
Section
```

Button copy:

```text
Simpan Stock In
Simpan Stock Out
```

Button disabled setelah klik, memakai UUID idempotency key, dan tidak berubah lebar saat loading.

## Success State

```text
Transaksi berhasil dicatat
Transaction number
Item and quantity
Stock before → stock after
Verified employee
Transaction time
```

Actions:

```text
[Transaksi Baru] [Kembali ke Dashboard]
```

Jangan auto-redirect sebelum operator melihat hasil.

## JavaScript Architecture

Gunakan asset convention aktif:

```text
resources/js/warehouse/transaction-form.js
```

atau `public/assets/js/warehouse/transaction-form.js` jika itu convention repository.

Responsibilities:

- State management.
- Fetch wrapper dengan CSRF.
- Scan debounce dan duplicate suppression.
- Focus management.
- Projection rendering.
- Confirmation lock.
- Reset.

Jangan menaruh ratusan baris script langsung di Blade.

## Server Contract

Controller/request:

```text
WarehouseScanController::scanItem
WarehouseScanController::scanUser
WarehouseTransactionController::create
WarehouseTransactionController::store
WarehouseTransactionController::show
StoreWarehouseTransactionRequest
```

Server wajib re-resolve item, user, permission, current stock, quantity rule, field wajib, dan idempotency key.

Jangan percaya client-provided:

- `stock_before` atau `stock_after`.
- User name/section.
- Item unit.
- Permission result.

## Files

```text
app/Http/Controllers/Warehouse/WarehouseScanController.php
app/Http/Controllers/Warehouse/WarehouseTransactionController.php
app/Http/Requests/Warehouse/ScanWarehouseItemRequest.php
app/Http/Requests/Warehouse/ScanWarehouseUserRequest.php
app/Http/Requests/Warehouse/StoreWarehouseTransactionRequest.php
resources/views/warehouse/transactions/create.blade.php
resources/views/warehouse/transactions/show.blade.php
resources/views/warehouse/transactions/partials/*
resources/js/warehouse/transaction-form.js
resources/css/warehouse/transaction-form.css     # hanya bila perlu
tests/Feature/Warehouse/WarehouseItemScanTest.php
tests/Feature/Warehouse/WarehouseUserVerificationTest.php
tests/Feature/Warehouse/WarehouseStockTransactionTest.php
tests/Feature/Warehouse/WarehouseTransactionIdempotencyTest.php
```

## Acceptance Criteria

### Item scan

- Active barcode resolve exact item.
- Unknown/inactive barcode ditolak dengan domain response.
- Leading zero dipertahankan.
- Duplicate rapid scan tidak merusak state.
- Response hanya berisi field wajib.

### User verification

- Active mapped card resolve user.
- NPK fallback mengikuti config.
- Inactive card/user ditolak.
- Failure dicatat.
- Raw card tidak dirender/dikembalikan.
- Section snapshot server-derived.

### Stock In

- Hanya actor authorized.
- Quantity positif menambah stok.
- Storage location wajib dan memperbarui lokasi master secara atomik bersama stock movement.
- Transaction dan stock update commit bersama.

### Stock Out

- Hanya actor authorized.
- Stock tidak negatif.
- Concurrent requests tidak overdraw.
- Hanya quantity yang diterima sebagai detail; lokasi manual ditolak.
- Lokasi master tidak berubah pada Stock Out.
- Fraction mengikuti item.
- Stale browser projection diabaikan; server memakai locked stock.

### Idempotency

- Key yang sama membuat satu transaction.
- Replay mengembalikan canonical result/idempotent success.
- Double click tidak membuat movement kedua.

### UX

- Scanner field autofocus.
- Focus kembali setelah error.
- Flow lengkap dapat dilakukan dengan keyboard/scanner.
- Mobile tidak overflow.
- Reset membersihkan sensitive scan state.
- Tidak ada React, Tailwind, atau CDN baru.

## Completion Evidence

```bash
php artisan test --filter=WarehouseItemScanTest
php artisan test --filter=WarehouseUserVerificationTest
php artisan test --filter=WarehouseStockTransactionTest
php artisan test --filter=WarehouseTransactionIdempotencyTest
php artisan route:list --name=warehouse
php artisan view:cache
```

---

# MISSION 05 — Dashboard Consumable

## Goal

Membangun dashboard yang membantu Warehouse dan management mengambil tindakan, bukan sekadar menampilkan angka.

## Dependency

MISSION 04 selesai dan transaction data tersedia.

## In Scope

- KPI operational summary.
- Date dan dimension filters.
- Stock In/Out trend.
- Top usage.
- Low/out-of-stock.
- Recent transactions.
- Detail drill-down.
- Authorized master/export actions.
- Query optimization.
- Empty/loading/error states.
- Responsive UI.

## Out of Scope

Forecasting, machine learning, auto purchase recommendation, multi-warehouse, WebSocket, email, worker, dan external BI.

## KPI Row

1. **Active Items** — jumlah consumable aktif.
2. **Healthy Stock Items** — item di atas minimum stock.
3. **Stock In Today** — quantity dan transaction count.
4. **Stock Out Today** — quantity dan transaction count.
5. **Low Stock** — `current_stock <= minimum_stock` bila inclusive.
6. **Out of Stock** — `current_stock = 0`.

Jangan menjumlahkan quantity lintas unit menjadi satu angka ambigu. `3 pcs + 5 liter` bukan `8 stock` yang bermakna.

## Trend Chart

- Series: Stock In dan Stock Out.
- Default: 30 hari.
- Daily grouping sampai 62 hari.
- Tooltip memuat quantity dan transaction count.
- Reversal tidak boleh menyebabkan double counting.
- Gunakan chart library existing; jangan menambahkan library baru hanya untuk satu chart.

## Top Usage

- Top 10 consumables berdasarkan Stock Out quantity.
- Tampilkan item, quantity, unit, dan relative bar.
- Gunakan horizontal bar sederhana.
- Hindari rainbow palette dan chart 3D.

## Low Stock Table

```text
Item | Current Stock | Minimum Stock | Shortage | Unit | Location | Status | Action
```

Action `Stock In` membuka form dengan item terpilih hanya untuk authorized user.

## Recent Transactions

```text
Time | Transaction No. | Type | Item | Quantity
Verified User | Section | Stock After
```

## Filters

```text
Date from
Date to
Transaction type
Category
Consumable
Section
Verified user
Stock status
```

Rules:

- State disimpan pada query string.
- Refresh mempertahankan filter.
- Reset kembali ke default period.
- Invalid range menghasilkan inline validation.
- KPI, chart, dan table memakai filter konsisten secara semantik.
- Stock status tidak mengubah historical metric jika tidak relevan.

## Query Architecture

```text
App\Services\Warehouse\WarehouseDashboardService
App\Data\Warehouse\WarehouseDashboardFilter
```

Methods:

```php
summary(WarehouseDashboardFilter $filter): array
movementTrend(WarehouseDashboardFilter $filter): Collection
topUsage(WarehouseDashboardFilter $filter, int $limit = 10): Collection
lowStock(WarehouseDashboardFilter $filter): LengthAwarePaginator
recentTransactions(WarehouseDashboardFilter $filter): LengthAwarePaginator
```

Performance:

- No N+1.
- Indexed filters.
- Paginate tables.
- Select required columns only.
- Explicit date boundaries.
- Document query count.
- Jangan cache current stock secara prematur.

## UI Detail

Header:

```text
Dashboard Consumable
Monitor stock availability and consumable movement.
[Kelola Master] [Export]
```

KPI compact, tanpa icon circle dekoratif di setiap card dan tanpa animated count.

Chart memiliki title, period, legend sederhana, dan subtle gridline.

Table menggunakan responsive wrapper dan intentional empty state.

## Endpoints

Primary page server-rendered. JSON endpoint optional:

```text
GET warehouse/dashboard/data
GET warehouse/transactions
GET warehouse/transactions/{transaction}
GET warehouse/consumables/{consumable}
```

History/master tetap internal action, bukan navbar submenu.

## Files

```text
WarehouseDashboardController
WarehouseDashboardFilterRequest
WarehouseDashboardService
WarehouseDashboardFilter
resources/views/warehouse/dashboard/index.blade.php
resources/views/warehouse/dashboard/partials/*
WarehouseDashboardTest
WarehouseDashboardFilterTest
WarehouseDashboardQueryTest
```

## Acceptance Criteria

- Authorized dashboard load.
- KPI cocok dengan seeded transactions.
- Today boundary memakai app timezone.
- Date filter inclusive dan deterministic.
- Trend aggregation benar.
- Reversal tidak inflate usage.
- Low/out-of-stock logic benar.
- Top usage ordered correctly.
- User/section filter memakai transaction snapshot.
- Table paginate dan tidak N+1.
- Empty database memiliki empty state.
- Mobile usable.
- Tidak ada framework/CDN baru.
- Visual hierarchy konsisten dengan Fastware.

## Completion Evidence

```bash
php artisan test --filter=WarehouseDashboardTest
php artisan test --filter=WarehouseDashboardFilterTest
php artisan test --filter=WarehouseDashboardQueryTest
php artisan view:cache
```

---

# MISSION 06 — Audit, History, Reversal, Export, and Release Hardening

## Goal

Menutup risiko operasional sebelum release melalui history, controlled reversal, adjustment, export, security hardening, documentation, dan regression verification.

## Dependency

MISSION 05 selesai.

## In Scope

- Transaction history/detail.
- Reversal.
- Restricted stock adjustment.
- Filtered export.
- Security hardening.
- Rate limiting scan endpoints.
- Release tests.
- Operating, deployment, dan rollback documentation.

## Out of Scope

Physical stock opname mobile app, multi-step approval, notification scheduler, worker, purchasing/ERP integration, dan multi-warehouse transfer.

## Transaction History

Entry point dari Dashboard:

```text
[Lihat Semua Transaksi]
```

Columns:

```text
Time | Transaction Number | Type | Item | Quantity
Stock Before | Stock After | Verified User | Section | Created By | Action
```

Filters: date, type, item, category, user, section, reference, transaction number.

## Transaction Detail

Tampilkan immutable facts:

```text
Transaction number and time
Item identity
Type and quantity
Stock before/after
Verified employee snapshot
Reference/purpose/location
Notes
Created by
Reversal relation
Audit timestamps
```

Raw card code tidak ditampilkan.

## Reversal

- Original transaction tetap unchanged.
- Reversal membuat movement lawan.
- Satu transaction hanya dapat direverse sekali.
- Reason wajib.
- Actor membutuhkan `warehouse.transaction.reverse`.
- Menggunakan row lock dan current stock.
- Reversal Stock In ditolak jika current stock tidak cukup untuk dikurangi.
- `reversal_of_id` wajib terhubung.
- Snapshot memakai current stock saat reversal.

Example:

```text
Original IN: +10, stock 20 → 30
Current stock sebelum reversal: 24
Reversal: -10, stock 24 → 14
```

## Stock Adjustment

Hanya Warehouse PIC/administrator.

Use cases:

- Opening balance.
- Physical count correction.
- Damaged item.
- Data migration correction.

Required:

```text
Item
Direction
Quantity
Reason category
Detailed reason
Verified user
```

Adjustment membuat movement dan tidak overwrite current stock langsung.

## Export

- XLSX memakai package existing.
- CSV optional.
- PDF hanya jika diminta.
- Mengikuti filter dan authorization.
- Snapshot values digunakan.
- Synchronous dengan date/row limit.
- Tidak menambahkan worker.

## Security Hardening

- Rate limit scan endpoints.
- Normalize input dan limit length.
- Reject control characters.
- Escape rendered values.
- CSRF untuk mutation.
- Authorization pada detail/export/reversal.
- Jangan log raw card.
- Jangan expose stack trace.
- Jangan percaya hidden fields.
- Validate UUID idempotency key.
- Review mass assignment dan IDOR.

## Operational Documentation

```text
warehouse-docs/WAREHOUSE-OPERATING-GUIDE.md
warehouse-docs/WAREHOUSE-DEPLOYMENT-CHECKLIST.md
warehouse-docs/WAREHOUSE-ROLLBACK-PLAN.md
```

Operating guide: register item/card, Stock In/Out, low stock, reversal, adjustment, export, scanner troubleshooting, dan larangan direct database edit.

Deployment checklist: backup, environment, migration, permission, master import, scanner/card test, transaction smoke test, dashboard validation, rollback trigger.

## Files

```text
WarehouseTransactionHistoryController
WarehouseStockAdjustmentController
ReverseWarehouseTransactionRequest
StoreWarehouseAdjustmentRequest
WarehouseTransactionsExport
resources/views/warehouse/transactions/index.blade.php
resources/views/warehouse/transactions/show.blade.php
resources/views/warehouse/transactions/reverse.blade.php
resources/views/warehouse/adjustments/create.blade.php
WarehouseTransactionHistoryTest
WarehouseReversalTest
WarehouseAdjustmentTest
WarehouseExportTest
WarehouseSecurityTest
```

## Acceptance Criteria

- History filter menghasilkan snapshot yang benar.
- Unauthorized user tidak dapat inspect arbitrary transaction ID.
- Original transaction tidak dapat diedit/dihapus.
- Reversal membuat satu opposite movement.
- Duplicate reversal ditolak.
- Reason wajib.
- Insufficient stock memblokir reversal yang invalid.
- Adjustment menghasilkan movement.
- Export sesuai filter/permission dan memiliki size guard.
- Scan rate limit aktif.
- Raw card tidak ada pada log/response/UI.
- Targeted Warehouse suite lulus.
- Full suite tidak menambah regression.
- Rollback plan diuji pada `_testing`.
- `view:cache`, `route:list`, dan Pint lulus.

## Completion Evidence

```bash
php artisan test --testsuite=Feature --filter=Warehouse
php artisan test
php artisan route:list --name=warehouse
php artisan view:cache
vendor/bin/pint --test
```

---

## 6. Mission Dependency Map

```text
MISSION 00
Baseline, discovery, safety
        ↓
MISSION 01
Database and stock domain
        ↓
MISSION 02
Navigation and authorization
        ↓
MISSION 03
Master consumable and card mapping
        ↓
MISSION 04
Stock In/Out scan workflow
        ↓
MISSION 05
Dashboard consumable
        ↓
MISSION 06
Audit, reversal, export, release hardening
```

Urutan tidak boleh dibalik:

- Form membutuhkan schema dan master data.
- Dashboard membutuhkan canonical transaction data.
- Reversal membutuhkan stock service yang stabil.
- Authorization harus ada sebelum route bisnis dipublikasikan.
- Export dibuat setelah filter dan snapshot data benar.

---

## 7. Proposed Final File Structure

```text
app/
├── Data/Warehouse/
│   ├── WarehouseDashboardFilter.php
│   ├── WarehouseStockCommand.php
│   └── WarehouseStockResult.php
├── Enums/Warehouse/
│   ├── WarehouseTransactionType.php
│   └── WarehouseVerificationStatus.php
├── Exports/Warehouse/
│   └── WarehouseTransactionsExport.php
├── Http/
│   ├── Controllers/Warehouse/
│   │   ├── WarehouseCategoryController.php
│   │   ├── WarehouseConsumableController.php
│   │   ├── WarehouseDashboardController.php
│   │   ├── WarehouseScanController.php
│   │   ├── WarehouseStockAdjustmentController.php
│   │   ├── WarehouseTransactionController.php
│   │   ├── WarehouseTransactionHistoryController.php
│   │   └── WarehouseUserCardController.php
│   ├── Middleware/
│   │   └── EnsureWarehousePermission.php
│   └── Requests/Warehouse/
├── Models/Warehouse/
│   ├── WarehouseConsumable.php
│   ├── WarehouseConsumableCategory.php
│   ├── WarehouseStockTransaction.php
│   ├── WarehouseUserCard.php
│   └── WarehouseVerificationLog.php
└── Services/Warehouse/
    ├── WarehouseDashboardService.php
    ├── WarehouseIdentityResolver.php
    ├── WarehouseStockService.php
    └── WarehouseTransactionNumberGenerator.php

config/
└── warehouse.php

resources/
├── css/warehouse/
├── js/warehouse/
└── views/warehouse/
    ├── dashboard/
    ├── consumables/
    ├── transactions/
    ├── adjustments/
    └── user-cards/

tests/Feature/Warehouse/

warehouse-docs/
├── MISSION-WAREHOUSE-CONSUMABLE.md
├── WAREHOUSE-DISCOVERY.md
├── WAREHOUSE-OPERATING-GUIDE.md
├── WAREHOUSE-DEPLOYMENT-CHECKLIST.md
└── WAREHOUSE-ROLLBACK-PLAN.md
```

Gunakan convention asset repository yang benar bila berbeda. Jangan membuat dua delivery path JS/CSS.

---

## 8. Suggested Route Inventory

```text
GET    warehouse/dashboard
GET    warehouse/dashboard/data                     # optional

GET    warehouse/transactions/create
POST   warehouse/scans/item
POST   warehouse/scans/user
POST   warehouse/transactions
GET    warehouse/transactions
GET    warehouse/transactions/{transaction}
POST   warehouse/transactions/{transaction}/reverse

GET    warehouse/consumables
GET    warehouse/consumables/create
POST   warehouse/consumables
GET    warehouse/consumables/{consumable}/edit
PUT    warehouse/consumables/{consumable}
PATCH  warehouse/consumables/{consumable}/status

GET    warehouse/categories
POST   warehouse/categories
PUT    warehouse/categories/{category}

GET    warehouse/user-cards
POST   warehouse/user-cards
PATCH  warehouse/user-cards/{card}/status

GET    warehouse/adjustments/create
POST   warehouse/adjustments

GET    warehouse/exports/transactions
```

Setiap route wajib berada di dalam `auth`, memakai name prefix `warehouse.`, dilindungi ability/policy, dan memiliki feature test.

---

## 9. Business Rule Matrix

| Rule | Enforcement |
|---|---|
| Stock tidak boleh negatif | Request + service + row lock |
| Item barcode unik | Validation + unique index |
| Employee card unik | Validation + unique index |
| Item harus aktif | Scan endpoint + stock service |
| User harus aktif | Identity resolver + stock service |
| Quantity positif | Request + service |
| Fraction mengikuti item | Request + service |
| Stock In restricted | Gate/middleware + service |
| Stock Out restricted | Gate/middleware + service |
| Double submit blocked | UUID idempotency key + unique index |
| Transaction immutable | Tidak ada update/delete route |
| Koreksi traceable | Reversal/adjustment movement |
| Historical section stabil | Snapshot pada transaction |
| Raw card protected | Hash pada log, tidak dirender |
| Current stock consistent | Transaction + `lockForUpdate()` |
| Dashboard canonical | Dedicated query service |
| Master stock tidak editable | Tidak ada direct update field |

---

## 10. Test Matrix

### Authentication and authorization

- Guest tidak dapat membuka Warehouse.
- Dashboard-only user tidak dapat transact.
- Stock Out-only user tidak dapat Stock In.
- Management tidak dapat membuka form.
- Master manager dapat manage item.
- Unauthorized ID enumeration ditolak.

### Item scan

- Valid, unknown, inactive.
- Duplicate rapid scan.
- Leading zero.
- Maximum length.
- Control character.
- Case-sensitive barcode.

### User scan

- Valid mapped card.
- Valid NPK fallback.
- Inactive card/user.
- Unknown code.
- Duplicate ownership.
- Raw code leak check.

### Quantity

- Integer.
- Fraction allowed/rejected.
- Zero/negative.
- Greater than stock.
- Precision edge.
- Maximum accepted value.

### Concurrency

- Dua OUT terhadap stok yang sama.
- IN dan OUT paralel.
- Idempotency replay.
- Double-click dengan key berbeda.
- Reversal ketika transaction lain berjalan.

### Dashboard

- Empty data.
- Today boundary.
- Date range.
- Category, item, user, section.
- Section snapshot.
- Low/out-of-stock.
- Reversal exclusion.
- Pagination dan query count.

### Release

- Fresh migrate.
- Rollback dan migrate.
- View cache.
- Route list.
- Pint.
- Targeted tests.
- Full suite regression comparison.

---

## 11. Definition of Done

Modul siap controlled deployment ketika:

- Semua tujuh mission selesai.
- Menu Warehouse memiliki tepat dua submenu utama.
- Stock In dan Stock Out bekerja dengan scanner nyata.
- Verifikasi bekerja dengan ID karyawan nyata.
- Stock tidak dapat negatif pada concurrent request.
- Setiap mutasi memiliki immutable transaction evidence.
- Dashboard sesuai data transaction.
- Low/out-of-stock actionable.
- Access enforced pada route dan service.
- Master stock tidak dapat diedit langsung.
- Transaction salah dapat direverse tanpa menghapus histori.
- Raw employee card tidak bocor melalui UI, response, atau log.
- UI memakai layout dan Bootstrap existing.
- Tidak ada React, Tailwind, CDN, atau framework baru.
- Mobile dan desktop lolos operational smoke test.
- Targeted Warehouse tests lulus.
- Full suite tidak menambah regression.
- Operating, deployment, dan rollback docs lengkap.

---

## 12. Explicit Non-Goals

Jangan memperluas project tanpa mission baru untuk:

- Asset/tool management.
- Borrowing, return, due date, overdue.
- Purchasing approval atau supplier integration.
- Automatic PO creation.
- Demand forecasting atau AI recommendation.
- OCR, RFID, camera scan.
- Offline/PWA atau native mobile.
- Multi-warehouse dan batch transfer.
- Worker notification, email, WhatsApp.
- ERP integration.
- Redesign seluruh navigation Fastware.
- Framework upgrade.
- Global permission refactor di luar Warehouse.

---

## 13. Final Implementation Principle

Bangun modul ini sebagai bagian dari Fastware ADSI, bukan prototype yang ditempelkan ke dalamnya.

```text
Correct stock
→ Clear audit
→ Fast scan workflow
→ Reliable authorization
→ Actionable dashboard
→ Visual polish
```

Visual polish tidak boleh mengorbankan kecepatan scan, integritas stok, atau keterbacaan data. UI dianggap berhasil ketika operator dapat menyelesaikan transaksi dengan sedikit langkah, management memahami kondisi stok tanpa penjelasan tambahan, dan developer berikutnya dapat memelihara modul tanpa membongkar logika yang tersebar di Blade.
