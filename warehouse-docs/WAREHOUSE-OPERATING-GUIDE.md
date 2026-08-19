# Warehouse Consumable - Operating Guide

## Version note - 2026-08-19

Panduan ini sesuai dengan Warehouse Consumable Revisi Tahap 2 dan Location Shipment. Stok disimpan per lokasi (`DS8`/`Deltamas`) dan per kondisi (`Baru`/`Bekas`), sedangkan `current_stock` tetap menjadi total seluruh lokasi dan kondisi. Master item tidak lagi menyimpan lokasi default.

## Prasyarat

1. Pengguna harus login sebagai Administrator atau memiliki assignment aktif pada departemen `Logistic & Warehouse`, `Production`, atau `PDCA, Inventory, Procurement & IT`.
2. Verifikasi karyawan memakai barcode NPK langsung dari `users.npk`; tabel kartu lama tidak digunakan.
3. Adjustment, opening balance, dan reversal atas Adjustment membutuhkan restricted verifier yang diselesaikan berdasarkan NPK `5639` atau `5439`.
4. Validator Pengiriman Antar Lokasi harus memiliki akses Warehouse dan tidak boleh menjadi pengirim shipment yang sama.
5. Pastikan symlink storage publik tersedia agar foto katalog dapat ditampilkan: `php artisan storage:link` bila belum pernah dibuat.

## Master Consumable

- Item baru selalu dibuat dengan saldo nol.
- `Tipe Mesin` berupa teks opsional dan dapat diisi setelah struktur tersedia.
- Foto bersifat opsional, memakai disk `public`, dengan format JPG/JPEG/PNG/WebP dan maksimum 5 MB.
- Mengganti foto menghapus foto lama setelah update database berhasil. Update tanpa foto baru mempertahankan foto yang sudah ada.
- Opening balance selalu masuk sebagai kondisi **Baru** pada lokasi yang dipilih dan tersimpan sebagai movement audit; jangan mengedit kolom stok langsung.

## Stock In / Stock Out

1. Pilih menu **Transaksi Barang Baru** atau **Transaksi Barang Bekas**.
2. Pilih barang dari katalog foto, cari nama/Item Code, atau pindai Item Code. Scanner tetap mendukung terminator Enter.
3. Pilih Stock In atau Stock Out, satu field **Lokasi**, dan jumlah. Sistem menampilkan saldo kondisi yang tersedia serta proyeksi setelah transaksi.
4. Stock Out hanya berarti barang dipakai/consumed; tidak ada selector tujuan warehouse.
5. Untuk Stock Out Barang Baru, opsi **kembalikan barang bekas** dapat diaktifkan. Barang yang kembali boleh sama atau berbeda dan dicatat sebagai Stock In Bekas.
6. Pindai NPK verifikator dan centang konfirmasi akhir. Perubahan barang, lokasi, jumlah, atau NPK setelah verifikasi UI akan membatalkan verifikasi dan wajib diverifikasi ulang.

Stock Out Baru dan Stock In Bekas pada opsi pengembalian disimpan secara atomik dalam satu operation key. Jika salah satu leg gagal, keduanya rollback. Pengiriman ulang dengan idempotency key yang sama tidak membuat transaksi ganda.

## Pengiriman Antar Lokasi

1. Dari konteks Stock In, pilih **Buat Pengiriman**; ini adalah workflow sekunder, bukan mode lain dari Stock In normal.
2. Pilih item, kondisi, lokasi asal, lokasi tujuan, jumlah, dan catatan pengirim. Lokasi asal dan tujuan harus berbeda.
3. Saat dibuat, shipment berstatus **Menunggu Validasi** dan quantity di lokasi asal di-reserve. Saldo fisik dan ledger belum berubah.
4. Di lokasi tujuan, petugas dengan akses Validasi memindai NPK validator, memeriksa item/kondisi/jumlah, lalu menyimpan hasil.
5. Jika sesuai, sistem melakukan satu movement internal TRANSFER melalui `WarehouseStockService`, mengurangi lokasi asal, menambah lokasi tujuan, menjaga global stock, dan mengaitkan ledger ke shipment.
6. Jika tidak sesuai, status menjadi **Tidak Sesuai**, catatan wajib, saldo tidak berubah, dan reservation tetap menahan quantity sampai shipment dibatalkan atau ditangani sesuai prosedur.
7. Pengirim tidak boleh memvalidasi shipment miliknya sendiri. Pembatalan pada status Menunggu Validasi/Tidak Sesuai melepas reservation tanpa mutasi stok.

## Adjustment dan reversal

- Adjustment wajib memiliki kondisi, lokasi, arah, jumlah, kategori alasan, alasan terperinci, dan restricted verifier.
- Reversal membuat movement lawan tanpa mengubah transaksi asal. Reversal mempertahankan kondisi dan lokasi transaksi asal.
- Ledger TRANSFER internal dari shipment tidak dapat direversal; koreksi dilakukan melalui shipment balik yang tervalidasi.
- Sistem menolak mutasi yang membuat saldo total, saldo Bekas, atau saldo Baru pada lokasi menjadi negatif setelah memperhitungkan reservation aktif.

## Riwayat dan workspace

Riwayat menyediakan tab **Semua**, **Foreman 1**, dan **Foreman 2**. Foreman 1 dipetakan ke NPK 5488 dan Foreman 2 ke NPK 5472. Filter lain tetap dipertahankan ketika berpindah tab. Total transaksi, Stock In, Stock Out, dan Adjustment di bagian bawah dihitung dari seluruh hasil filter; ledger TRANSFER tidak dihitung sebagai konsumsi.

## Dashboard dan reporting

- Dashboard menampilkan KPI stok, panel tren Stock In/Out, chart horizontal Top Item Stock Out, dan chart Top Tipe Mesin Stock Out. Data tabel tersedia sebagai fallback aksesibel untuk masing-masing chart.
- **Reporting Tahunan** menggunakan tahun kalender dan hanya menampilkan kolom bulan sampai bulan transaksi terakhir pada tahun tersebut.
- Semua item, termasuk item tanpa pergerakan, tetap menjadi baris pada matrix. Desktop juga menampilkan Minimum/Maximum; header dua tingkat mengelompokkan Stok Awal, Mutasi (+), Mutasi (-), dan Stok Akhir; Total/Average hanya menghitung saldo akhir bulanan.
- Pada viewport kecil, tersedia `.warehouse-card-list` per item agar reporting tetap terbaca tanpa bergantung hanya pada horizontal scroll; matrix desktop tetap memakai kolom Nama Barang sticky.
- Ekspor riwayat XLSX menyertakan kondisi, tipe mesin, operation key, serta lokasi asal/tujuan dan tunduk pada batas baris konfigurasi.

## Scanner dan kegagalan umum

- Scanner NPK harus menghasilkan angka positif; leading zero tetap diterima dan dinormalisasi ke nilai integer `users.npk`.
- Item scan mempertahankan Item Code setelah whitespace/terminator dibersihkan.
- Jika item/NPK tidak dikenal, nonaktif, ambigu, atau tidak berhak, jangan menyalin nilai mentah ke catatan atau screenshot. Periksa master/assignment aktif lalu ulangi.
- Jika foto tidak tampil, cek `storage:link`, permission `storage/app/public`, dan keberadaan file pada `warehouse/consumables`.

## Aturan keselamatan

Jangan mengedit ledger atau saldo langsung, menghapus transaksi, mengaktifkan kembali `mst_wh_user_cards`, menjalankan seluruh pending migration legacy, atau menjalankan test destruktif pada database yang tidak berakhiran `_testing`. Browser mobile 360/390 px dan scanner fisik tetap merupakan smoke test deployment.
