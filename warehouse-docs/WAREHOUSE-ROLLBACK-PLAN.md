# Warehouse Consumable — Rollback Plan

## Prinsip utama

Revisi Tahap 2 menambah data audit dan saldo per lokasi/kondisi. Setelah transaksi Revisi 2 tercatat, rollback schema dapat menghilangkan informasi `TRANSFER`, `item_condition`, lokasi asal/tujuan, foto, tipe mesin, serta breakdown saldo. Karena itu produksi mengutamakan **stop write + forward fix**, bukan rollback migration otomatis.

## Trigger

Hentikan release bila migration preflight gagal, saldo total tidak sama dengan jumlah lokasi, verifier terbatas tidak tepat, route/view/build gagal, atau smoke test menemukan mutasi parsial/otorisasi bocor.

## Application rollback

1. Nonaktifkan sementara mutasi Warehouse dan catat waktu, user, item, operation key, serta transaction number terakhir tanpa menyimpan raw scan.
2. Simpan log aplikasi, hasil preflight, `migrate:status`, checksum artifact, dan backup database/foto.
3. Jika **belum ada transaksi Revisi 2**, restore artifact aplikasi sebelumnya melalui prosedur deploy normal; biarkan kolom tambahan tetap ada sampai keputusan schema dibuat.
4. Jika sudah ada transaksi Bekas/Transfer atau saldo sudah tersebar antar lokasi, jangan jalankan aplikasi lama yang enum/model-nya tidak memahami data tersebut. Gunakan forward fix atau build compatibility yang direview.

## Schema rollback

- Jangan menjalankan `migrate:rollback` umum pada checkout ini.
- Empat migration Revisi 2 hanya boleh direhearsal pada database berakhiran `_testing` atau dilakukan di produksi setelah incident owner menyetujui potensi kehilangan data dan backup restore telah diuji.
- Urutan down adalah kebalikan: seed verifier, tabel verifier, kolom audit transaksi, lalu kolom inventory master.
- Migration inventory pertama menjatuhkan kolom per-lokasi/kondisi, `machine_type`, dan `photo_path`; migration transaksi menjatuhkan `operation_key`, `item_condition`, dan from/to location. Down bukan mekanisme pemulihan data.
- File foto pada disk tidak otomatis dipulihkan/dihapus oleh migration. Pertahankan backup `storage/app/public/warehouse/consumables` sampai incident ditutup.

## Koreksi operasional

- Transaksi IN/OUT/Adjustment yang salah dikoreksi dengan reversal terotorisasi; baris asal tetap immutable.
- Transfer salah dikoreksi dengan transfer balik oleh RAGIL/ARY dan alasan yang jelas; transfer tidak dapat direversal.
- Jika ada mismatch saldo, hentikan mutasi item, audit ledger dan operation key, lalu lakukan restricted Adjustment hanya setelah persetujuan incident owner.
- Jangan mengubah saldo, kondisi, lokasi, transaction type, atau restricted verifier langsung melalui SQL.

## Resume

Sebelum membuka kembali Warehouse, buktikan semua invariant saldo, jalankan targeted suite/build/view cache, ulangi smoke role/scanner/mobile, dan tambahkan keputusan serta evidence ke `WAREHOUSE-EXECUTION-LOG.md`.
