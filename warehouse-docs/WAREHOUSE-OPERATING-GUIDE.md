# Warehouse Consumable — Operating Guide

## Version note — 2026-08-18

Panduan ini sesuai dengan Warehouse Consumable Revisi Tahap 2. Stok disimpan per lokasi (`DS8`/`Deltamas`) dan per kondisi (`Baru`/`Bekas`), sedangkan `current_stock` tetap menjadi total seluruh lokasi dan kondisi.

## Prasyarat

1. Pengguna harus login sebagai Administrator atau memiliki assignment aktif pada departemen `Logistic & Warehouse`, `Production`, atau `PDCA, Inventory, Procurement & IT`.
2. Verifikasi karyawan memakai barcode NPK langsung dari `users.npk`; tabel kartu lama tidak digunakan.
3. Transfer, Adjustment, opening balance, dan reversal atas Adjustment hanya dapat diverifikasi oleh `RAGIL ISHA RAHMANTO` (NPK 5639) atau `ARY RODJO PRASETYO` (NPK 5439).
4. Pastikan symlink storage publik tersedia agar foto katalog dapat ditampilkan: `php artisan storage:link` bila belum pernah dibuat.

## Master Consumable

- Item baru selalu dibuat dengan saldo nol.
- `Tipe Mesin` berupa teks opsional dan dapat diisi setelah struktur tersedia.
- Foto bersifat opsional, memakai disk `public`, dengan format JPG/JPEG/PNG/WebP dan maksimum 5 MB.
- Mengganti foto menghapus foto lama setelah update database berhasil. Update tanpa foto baru mempertahankan foto yang sudah ada.
- Opening balance selalu masuk sebagai kondisi **Baru** pada lokasi yang dipilih dan tersimpan sebagai movement audit; jangan mengedit kolom stok langsung.

## Stock In / Stock Out

1. Pilih menu **Transaksi Barang Baru** atau **Transaksi Barang Bekas**.
2. Pilih barang dari katalog foto, cari nama/Item Code, atau pindai Item Code. Scanner tetap mendukung terminator Enter.
3. Pilih Stock In atau Stock Out, lokasi, dan jumlah. Sistem menampilkan saldo kondisi yang tersedia serta proyeksi setelah transaksi.
4. Untuk Stock Out Barang Baru, opsi **kembalikan barang bekas** dapat diaktifkan. Barang yang kembali boleh sama atau berbeda dan dicatat sebagai Stock In Bekas.
5. Pindai NPK verifikator dan centang konfirmasi akhir. Perubahan barang, lokasi, jumlah, atau NPK setelah verifikasi akan membatalkan verifikasi UI dan wajib diverifikasi ulang.

Stock Out Baru dan Stock In Bekas pada opsi pengembalian disimpan secara atomik dalam satu operation key. Jika salah satu leg gagal, keduanya rollback. Pengiriman ulang dengan idempotency key yang sama tidak membuat transaksi ganda.

## Transfer lokasi

1. Buka **Transfer Lokasi** dan pilih barang serta kondisi Baru/Bekas.
2. Periksa breakdown saldo DS8 dan Deltamas, lalu pilih lokasi asal, tujuan, dan jumlah.
3. Pindai NPK RAGIL atau ARY dan konfirmasi.

Transfer mengurangi saldo kondisi pada lokasi asal dan menambahkannya pada lokasi tujuan. Total `current_stock` tidak berubah. Transfer tidak direversal; buat transfer balik dengan catatan koreksi bila diperlukan.

## Adjustment dan reversal

- Adjustment wajib memiliki kondisi, lokasi, arah, jumlah, kategori alasan, alasan terperinci, dan verifikator terbatas.
- Reversal membuat movement lawan tanpa mengubah transaksi asal. Reversal mempertahankan kondisi dan lokasi transaksi asal.
- Reversal atas transfer ditolak. Reversal atas Adjustment tetap membutuhkan RAGIL atau ARY.
- Sistem menolak mutasi yang membuat saldo total, saldo Bekas, atau saldo Baru pada lokasi menjadi negatif.

## Riwayat dan workspace

Riwayat menyediakan tab **Semua**, **Foreman 1**, dan **Foreman 2**. Foreman 1 dipetakan ke NPK 5488 dan Foreman 2 ke NPK 5472. Filter lain tetap dipertahankan ketika berpindah tab. Total transaksi, Stock In, Stock Out, dan Adjustment di bagian bawah dihitung dari seluruh hasil filter, bukan hanya halaman aktif.

## Dashboard dan reporting

- Dashboard menampilkan KPI stok, tren Stock In/Out, grafik horizontal top item Stock Out, dan top tipe mesin Stock Out. Data tabel tersedia sebagai fallback aksesibel untuk grafik.
- **Reporting Tahunan** menggunakan tahun kalender Januari–Desember, tetapi hanya menampilkan sampai bulan terakhir yang memiliki transaksi pada tahun tersebut.
- Semua item, termasuk item nonaktif atau tanpa pergerakan, tetap masuk laporan. `Total` dan `Average` dihitung dari saldo akhir bulanan yang ditampilkan.
- Ekspor riwayat XLSX menyertakan kondisi, tipe mesin, operation key, serta lokasi asal/tujuan dan tunduk pada batas baris konfigurasi.

## Scanner dan kegagalan umum

- Scanner NPK harus menghasilkan angka positif; leading zero tetap diterima dan dinormalisasi ke nilai integer `users.npk`.
- Item scan mempertahankan Item Code setelah whitespace/terminator dibersihkan.
- Jika item/NPK tidak dikenal, nonaktif, ambigu, atau tidak berhak, jangan menyalin nilai mentah ke catatan atau screenshot. Periksa master/assignment aktif lalu ulangi.
- Jika foto tidak tampil, cek `storage:link`, permission `storage/app/public`, dan keberadaan file pada `warehouse/consumables`.

## Aturan keselamatan

Jangan mengedit ledger atau saldo langsung, menghapus transaksi, mengaktifkan kembali `mst_wh_user_cards`, menjalankan seluruh pending migration legacy, atau menjalankan test destruktif pada database yang tidak berakhiran `_testing`. Browser mobile 360/390 px dan scanner fisik tetap merupakan smoke test deployment.
