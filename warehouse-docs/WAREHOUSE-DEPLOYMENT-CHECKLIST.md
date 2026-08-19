# Warehouse Consumable — Deployment Checklist

## Release snapshot — 2026-08-18 / Revisi Tahap 2

Release ini menambah saldo Baru/Bekas per lokasi, transfer, verifier terbatas, workspace riwayat, tipe mesin, dashboard chart, reporting tahunan, foto katalog, dan UI mobile. Jangan menjalankan seluruh pending migration pada checkout legacy ini.

## Backup dan preflight

- [ ] Catat commit/artifact yang akan dipasang dan simpan diff shared files.
- [ ] Backup database aplikasi, source produksi, `.env`, serta `storage/app/public/warehouse/consumables` bila folder tersebut sudah ada.
- [ ] Konfirmasi `APP_ENV`, nama database, timezone `Asia/Jakarta`, MySQL 8+, PHP/Laravel, dan ruang penyimpanan.
- [ ] Pastikan setiap consumable dengan `current_stock > 0` memiliki `storage_location` tepat `DS8` atau `Deltamas`. Migrasi pertama sengaja berhenti sebelum perubahan schema bila preflight ini gagal.
- [ ] Pastikan tepat satu user aktif cocok untuk masing-masing identitas berikut:
  - `RAGIL ISHA RAHMANTO`, NPK `5639`
  - `ARY RODJO PRASETYO`, NPK `5439`
- [ ] Pastikan tepat satu user aktif ada untuk workspace Foreman NPK `5488` dan `5472`.
- [ ] Review `php artisan migrate:status`; jangan gunakan `php artisan migrate` tanpa `--path` karena checkout memiliki migration legacy lain yang masih Pending.

## File dan migration order

1. Copy file dedicated dari `warehouse-consumable/runtime/` sesuai path relatif.
2. Merge perubahan Warehouse dari sepuluh snapshot `warehouse-consumable/shared-files/`; jangan overwrite shared files secara wholesale.
3. Jalankan **hanya** empat migration berikut secara berurutan:

   ```powershell
   php artisan migrate --path=database/migrations/2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table.php
   php artisan migrate --path=database/migrations/2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table.php
   php artisan migrate --path=database/migrations/2026_08_18_000003_create_mst_wh_restricted_verifiers_table.php
   php artisan migrate --path=database/migrations/2026_08_18_000004_seed_mst_wh_restricted_verifiers.php
   ```

4. Jangan jalankan `database/warehouse-consumable.sql` pada instalasi Warehouse yang sudah ada. File itu hanya bootstrap schema lengkap untuk instalasi baru.
5. Build dan refresh cache:

   ```powershell
   composer dump-autoload
   npm.cmd run build
   php artisan optimize:clear
   php artisan view:cache
   php artisan route:list --path=warehouse --except-vendor
   php artisan storage:link
   ```

## Verifikasi data setelah migration

- [ ] `current_stock = stock_deltamas + stock_ds8` untuk seluruh item.
- [ ] `0 <= stock_used_deltamas <= stock_deltamas` dan `0 <= stock_used_ds8 <= stock_ds8`.
- [ ] Stok existing masuk sebagai kondisi Baru pada lokasi legacy; saldo Bekas awal nol.
- [ ] Tabel `mst_wh_restricted_verifiers` berisi tepat RAGIL dan ARY dengan `scope=ALL`, `is_active=1`.
- [ ] Semua empat migration tampil `Ran`; migration lain tidak ikut dijalankan.

## Smoke test fungsional

- [ ] Administrator dan user aktif dari tiap departemen resmi melihat menu; user luar ditolak 403.
- [ ] Katalog menampilkan placeholder untuk item tanpa foto dan foto nyata untuk item yang sudah diunggah.
- [ ] Stock In/Out Baru dan Bekas berhasil pada DS8 serta Deltamas; saldo kondisi/lokasi sesuai.
- [ ] Stock Out Baru + pengembalian item Bekas yang berbeda menghasilkan dua transaksi dengan operation key sama; kegagalan salah satu leg tidak meninggalkan transaksi parsial.
- [ ] Transfer Baru dan Bekas menjaga total stok, menolak saldo asal yang kurang, serta hanya menerima RAGIL/ARY.
- [ ] Adjustment dan opening balance menolak user Warehouse biasa yang bukan verifier terbatas.
- [ ] Tab Semua/Foreman 1/Foreman 2 mempertahankan filter dan total bawah mencakup semua hasil filter.
- [ ] Dashboard menampilkan dua chart/fallback tabel; halaman Reporting konsisten antarbulan dan berhenti pada bulan data terakhir.
- [ ] Export XLSX memuat condition, machine type, operation key, from/to location.
- [ ] Uji viewport 360 px dan 390 px, keyboard focus, reduced-motion, serta scanner fisik Enter/Tab. QA browser/scanner tidak digantikan oleh automated test.

## Gate teknis

- [ ] Targeted Warehouse feature/unit suite lulus pada database `*_testing`.
- [ ] `php artisan view:cache`, scoped Pint, PHP lint, `git diff --check`, dan `npm.cmd run build` lulus.
- [ ] Warning Vite existing terkait `pdfjs-dist` dicatat terpisah; tidak dianggap perubahan Warehouse.

## Stop condition

Hentikan deployment bila preflight user/lokasi tidak tepat satu/valid, backfill menghasilkan mismatch, ada migration non-Warehouse ikut berjalan, scanner menghasilkan payload berbeda, atau salah satu acceptance smoke gagal. Jangan memperbaiki ledger dengan SQL langsung; ikuti `WAREHOUSE-ROLLBACK-PLAN.md`.
