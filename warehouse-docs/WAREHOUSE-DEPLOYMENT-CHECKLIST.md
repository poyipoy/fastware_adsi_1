# Warehouse Consumable - Deployment Checklist

## Release snapshot - 2026-08-19 / Revisi Tahap 2 + Location Shipment

Release ini menambah saldo Baru/Bekas per lokasi, workflow Pengiriman Antar Lokasi, verifier terbatas untuk Adjustment, workspace riwayat, tipe mesin, dashboard chart terpisah, reporting matrix bulanan, foto katalog, dan UI responsif. Jangan menjalankan seluruh pending migration pada checkout legacy ini.

## Backup dan preflight

- [ ] Catat commit/artifact yang akan dipasang dan simpan diff shared files.
- [ ] Backup database aplikasi, source produksi, `.env`, serta `storage/app/public/warehouse/consumables` bila folder tersebut sudah ada.
- [ ] Konfirmasi `APP_ENV`, nama database, timezone `Asia/Jakarta`, MySQL 8+, PHP/Laravel, dan ruang penyimpanan.
- [ ] Pastikan saldo memenuhi `current_stock = stock_deltamas + stock_ds8` dan `0 <= stock_used <= stock` untuk setiap lokasi/kondisi.
- [ ] Pastikan tepat satu user aktif cocok untuk NPK `5639` dan tepat satu untuk NPK `5439`. Seed restricted verifier menggunakan NPK sebagai source of truth.
- [ ] Pastikan tepat satu user aktif ada untuk workspace Foreman NPK `5488` dan `5472`.
- [ ] Review `php artisan migrate:status`; jangan gunakan `php artisan migrate` tanpa `--path` karena checkout memiliki migration legacy lain yang masih Pending.

## File dan migration order

1. Copy file dedicated dari `warehouse-consumable/runtime/` sesuai path relatif.
2. Merge perubahan Warehouse dari sepuluh snapshot `warehouse-consumable/shared-files/`; jangan overwrite shared files secara wholesale.
3. Jalankan **hanya** migration berikut secara berurutan:

   ```powershell
   php artisan migrate --path=database/migrations/2026_08_18_000001_add_revision_two_inventory_fields_to_mst_wh_consumables_table.php
   php artisan migrate --path=database/migrations/2026_08_18_000002_add_revision_two_audit_fields_to_trs_wh_stock_transactions_table.php
   php artisan migrate --path=database/migrations/2026_08_18_000003_create_mst_wh_restricted_verifiers_table.php
   php artisan migrate --path=database/migrations/2026_08_18_000004_seed_mst_wh_restricted_verifiers.php
   php artisan migrate --path=database/migrations/2026_08_19_000001_create_trs_wh_location_shipments_table.php
   php artisan migrate --path=database/migrations/2026_08_19_000002_add_location_shipment_id_to_trs_wh_stock_transactions_table.php
   php artisan migrate --path=database/migrations/2026_08_19_000003_drop_storage_location_from_mst_wh_consumables_table.php
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
- [ ] `mst_wh_consumables` tidak lagi memiliki kolom master `storage_location`; lokasi hanya berasal dari transaksi/shipment.
- [ ] Tabel `mst_wh_restricted_verifiers` berisi satu row per NPK approved dengan `scope=ALL`, `is_active=1`.
- [ ] Tabel `trs_wh_location_shipments` dan link `location_shipment_id` tersedia.
- [ ] Semua tujuh migration Warehouse tampil `Ran`; migration lain tidak ikut dijalankan.

## Smoke test fungsional

- [ ] Administrator dan user aktif dari tiap departemen resmi melihat menu; user luar ditolak 403.
- [ ] Katalog menampilkan placeholder untuk item tanpa foto dan foto nyata untuk item yang sudah diunggah.
- [ ] Stock In/Out Baru dan Bekas berhasil pada DS8 serta Deltamas dengan satu field user-facing `Lokasi`; Stock Out tidak menampilkan tujuan warehouse.
- [ ] Stock Out Baru + pengembalian item Bekas yang berbeda menghasilkan dua transaksi dengan operation key sama; kegagalan salah satu leg tidak meninggalkan transaksi parsial.
- [ ] Buat Pengiriman Antar Lokasi dari konteks Stock In; pembuatan hanya membuat reservation dan tidak mengubah saldo.
- [ ] Validator tujuan yang berbeda dari pengirim dapat mencatat sesuai (satu ledger TRANSFER internal, global stock tetap); mismatch wajib berisi catatan dan tidak mengubah saldo; cancel melepas reservation.
- [ ] Adjustment dan opening balance menolak user Warehouse biasa yang bukan verifier restricted.
- [ ] Tab Semua/Foreman 1/Foreman 2 mempertahankan filter dan total bawah mencakup semua hasil filter.
- [ ] Dashboard menampilkan trend terpisah dan dua chart/fallback tabel untuk item serta tipe mesin; reporting matrix berhenti pada bulan data terakhir.
- [ ] Reporting desktop menampilkan Minimum/Maximum dan matrix bulanan; mobile menampilkan `.warehouse-card-list` yang terbaca tanpa bergantung hanya pada horizontal scroll.
- [ ] Export XLSX memuat condition, machine type, operation key, from/to location.
- [ ] Uji viewport 360 px dan 390 px, keyboard focus, reduced-motion, serta scanner fisik Enter/Tab. QA browser/scanner tidak digantikan oleh automated test.

## Gate teknis

- [ ] Targeted Warehouse feature/unit suite lulus pada database `*_testing`.
- [ ] `php artisan view:cache`, PHP lint, `git diff --check`, dan `npm.cmd run build` lulus.
- [ ] SHA-256 source-to-package: 143 artifacts, missing 0, mismatch 0, reader-copy mismatch 0.
- [ ] Warning Vite existing terkait `pdfjs-dist` dicatat terpisah; tidak dianggap perubahan Warehouse.

## Stop condition

Hentikan deployment bila preflight saldo/user tidak valid, backfill menghasilkan mismatch, ada migration non-Warehouse ikut berjalan, scanner menghasilkan payload berbeda, reservation/ledger tidak atomik, atau salah satu acceptance smoke gagal. Jangan memperbaiki ledger dengan SQL langsung; ikuti `WAREHOUSE-ROLLBACK-PLAN.md`.
