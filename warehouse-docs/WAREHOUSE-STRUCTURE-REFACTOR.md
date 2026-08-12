# Warehouse Structure Refactor

Version: `2026-08-12`
Status: `DONE`

## Scope

Refactor struktural Warehouse Fase 1 sampai Fase 6 selesai tanpa perubahan behavior, route, schema, migration, atau data aplikasi.

Perubahan yang diterapkan:

- menghapus komponen Blade `action-bar` yang tidak memiliki consumer;
- menggabungkan DTO ke `WarehouseStockData.php`;
- menggabungkan enum ke `WarehouseEnums.php`;
- menggabungkan Form Request menjadi `TransactionRequests.php`, `ScanRequests.php`, dan `ConsumableRequests.php`;
- memindahkan exception dan export ke namespace flat;
- memindahkan view kategori dan penyesuaian satu-file ke folder `resources/views/warehouse/`;
- menempatkan `WarehouseTransactionNumberGenerator` di `WarehouseStockService.php`.

Class, namespace, constructor, route name, validasi, dan aturan bisnis tetap dipertahankan. View dengan tujuan berbeda tidak digabung.

## Commit sequence

1. `943f539` — `refactor(warehouse): remove unused action bar component`
2. `702f434` — `refactor(warehouse): consolidate stock data and enums`
3. `d6416fa` — `refactor(warehouse): consolidate feature requests`
4. `aa12ba4` — `refactor(warehouse): flatten exception and export namespaces`
5. `1fb6770` — `refactor(warehouse): flatten single-view folders`
6. `9bf70d5` — `refactor(warehouse): co-locate transaction number generator`

## Struktur hasil

- File modul: `59` menjadi `51`.
- Folder modul: `14` menjadi `10`.
- Route Warehouse: tetap `24` dan fingerprint route tidak berubah.
- `routes/web.php`: tidak berubah.
- Database, migration, dan data aplikasi: tidak disentuh.

Karena beberapa file hasil konsolidasi berisi lebih dari satu class, `composer.json` mendaftarkan file tersebut melalui `autoload.files` dan mengecualikannya dari classmap PSR-4. FQCN lama tetap dapat di-autoload dan consumer tidak perlu mengubah `use` statement.

## Verification

- `composer dump-autoload`: berhasil tanpa warning autoload Warehouse.
- `php artisan route:list --name=warehouse`: `24` route, identik dengan baseline.
- `php artisan optimize:clear`: berhasil.
- `php artisan view:cache`: berhasil.
- `php artisan test tests/Feature/Warehouse tests/Unit/Warehouse --compact`: `102` test, `685` assertion, seluruhnya lulus.
- Worktree setelah commit: bersih.

## Deployment artifact note

Folder `warehouse-consumable/` sengaja berada di luar scope refactor dan tidak disentuh. Dokumentasi pada `warehouse-consumable/docs/warehouse-docs/` diselaraskan dengan versi ini, tetapi source code artifact di folder tersebut masih versi sebelum refactor. Jika folder itu masih dipakai untuk deployment, regenerasi atau redeploy artifact harus dilakukan secara manual sebagai pekerjaan terpisah.
