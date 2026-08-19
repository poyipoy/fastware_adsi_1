# Warehouse Consumable - Rollback Plan

## Prinsip utama

Revisi Tahap 2 menambah data audit, saldo per lokasi/kondisi, dan workflow Pengiriman Antar Lokasi. Setelah shipment tervalidasi, rollback schema dapat menghilangkan informasi `TRANSFER`, `item_condition`, lokasi asal/tujuan, foto, tipe mesin, breakdown saldo, reservation, dan audit shipment. Karena itu produksi mengutamakan **stop write + forward fix**, bukan rollback migration otomatis.

## Trigger

Hentikan release bila migration preflight gagal, saldo total tidak sama dengan jumlah lokasi, verifier restricted tidak tepat, route/view/build gagal, reservation tidak konsisten, atau smoke test menemukan mutasi parsial/otorisasi bocor.

## Application rollback

1. Nonaktifkan sementara mutasi Warehouse dan catat waktu, user, item, operation key, shipment number, serta transaction number terakhir tanpa menyimpan raw scan.
2. Simpan log aplikasi, hasil preflight, `migrate:status`, checksum artifact, dan backup database/foto.
3. Jika **belum ada transaksi Revisi 2 atau shipment**, restore artifact aplikasi sebelumnya melalui prosedur deploy normal; biarkan kolom tambahan tetap ada sampai keputusan schema dibuat.
4. Jika sudah ada transaksi Bekas, shipment, atau saldo tersebar antar lokasi, jangan jalankan aplikasi lama yang enum/model-nya tidak memahami data tersebut. Gunakan forward fix atau build compatibility yang direview.

## Schema rollback

- Jangan menjalankan `migrate:rollback` umum pada checkout ini.
- Tujuh migration Warehouse hanya boleh direhearsal pada database berakhiran `_testing` atau dilakukan di produksi setelah incident owner menyetujui potensi kehilangan data dan backup restore telah diuji.
- Urutan down adalah kebalikan: drop master storage-location removal, unlink shipment/stock transaction, drop shipment table, seed/table verifier, audit transaction fields, lalu inventory fields. Down bukan mekanisme pemulihan data.
- Migration terakhir bersifat fail-closed terhadap invariant saldo sebelum menghapus kolom master `storage_location`; jangan bypass preflight dengan SQL manual.
- File foto pada disk tidak otomatis dipulihkan/dihapus oleh migration. Pertahankan backup `storage/app/public/warehouse/consumables` sampai incident ditutup.

## Koreksi operasional

- Transaksi IN/OUT/Adjustment yang salah dikoreksi dengan reversal terotorisasi; baris asal tetap immutable.
- Shipment mismatch dipertahankan sebagai DISCREPANCY dengan catatan; jangan memaksa perubahan saldo. Batalkan shipment untuk melepas reservation atau buat shipment koreksi baru setelah investigasi.
- Ledger TRANSFER dari shipment tidak dapat direversal. Koreksi perpindahan dilakukan dengan shipment balik yang tervalidasi, bukan SQL langsung.
- Jika ada mismatch saldo, hentikan mutasi item, audit ledger, reservation, shipment, dan operation key, lalu lakukan restricted Adjustment hanya setelah persetujuan incident owner.
- Jangan mengubah saldo, kondisi, lokasi, transaction type, shipment status, atau restricted verifier langsung melalui SQL.

## Resume

Sebelum membuka kembali Warehouse, buktikan semua invariant saldo dan reservation, jalankan targeted suite/build/view cache, ulangi smoke role/scanner/mobile, dan tambahkan keputusan serta evidence ke `WAREHOUSE-EXECUTION-LOG.md`.
