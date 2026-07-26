# Audit Insiden File KM 2026-07-20

## Ringkasan

Script sementara di web root pernah membuat PDF dummy lalu menjalankan `km:migrate-private-files` terhadap database lokal `dms_adasi_rev1`. Script tersebut sudah dihapus dari `public/` sebagai bagian perbaikan Jangka Menengah.

## Bukti

- Manifest terdampak: `storage/app/private/km/file-migrations/20260720_143916_842407.json`.
- Dokumen terdampak: ID 21 sampai 31.
- Seluruh entry memiliki ukuran 590 byte dan checksum SHA-256 identik `7f9c0a7dbe8783056ec38027cb476e663e00785f251ffddf5ec19b91d1fb89c5`.
- Isi script menunjukkan file sumber ditimpa PDF dummy sebelum command migrasi dijalankan.
- Path `legacy-backup` dalam manifest dibuat dari sumber yang sudah ditimpa, sehingga bukan salinan dokumen asli yang dapat dipercaya.

## Keputusan Aman

- Tidak ada pemulihan file atau perubahan metadata database otomatis dalam mission ini.
- Dokumen 21 sampai 31 harus dianggap rusak sampai checksum dapat dicocokkan dengan backup eksternal yang dibuat sebelum 20 Juli 2026 pukul 14:39 WIB.
- Cleanup atau restore database/file harus menjadi rollout terpisah setelah backup terverifikasi dan persetujuan pemilik data.

## Langkah Rollout Terpisah

1. Ambil snapshot database dan seluruh `storage/app/private/km`.
2. Dapatkan backup dokumen asli dari sumber di luar manifest terdampak.
3. Cocokkan nama asli, pemilik, ukuran, MIME, dan checksum per dokumen.
4. Pulihkan satu dokumen per transaksi menggunakan nama file privat acak.
5. Perbarui metadata checksum dan dispatch ulang thumbnail hanya setelah verifikasi.
6. Jalankan `km:readiness` dan simpan manifest pemulihan.

## Hasil Rollout 2026-07-22

- Snapshot database dan 176 file pada `storage/app/private/km` disimpan di
  `storage/app/private/km-rollout-snapshots/20260722_185334`.
- Dokumen ID 20 dipulihkan dari backup eksternal yang ukuran dan checksum SHA-256-nya
  cocok dengan manifest migrasi `20260719_144030_500162.json`.
- Metadata ID 20 dialihkan melalui conditional transaction ke path privat acak dan
  thumbnail baru berhasil berstatus `ready`.
- Sebelas PDF dummy publik ID 21 sampai 31 disalin sebagai bukti ke snapshot lalu
  dihapus dari `public/assets/image`.
- Backup asli ID 21 sampai 31 tidak ditemukan di workspace, riwayat Git, arsip
  Laragon, Downloads, Desktop, Documents, maupun OneDrive yang tersedia. Metadata
  dan lifecycle dokumen tersebut tidak diubah.
- Readiness berubah dari 11 public exposure dan 12 checksum mismatch menjadi
  0 public exposure dan 11 checksum mismatch.
- Manifest operasional:
  `storage/app/private/km/recovery-manifests/20260722_185334.json`.
- Status rollout: `partial_blocked_missing_sources`. Deployment tetap diblokir
  sampai backup asli ID 21 sampai 31 tersedia dan terverifikasi.

## Keputusan Final 2026-07-22

- Pemilik data mengonfirmasi bahwa dokumen asli ID 21 sampai 31 sudah tidak ada
  dan materi akan diinput ulang secara manual sebagai dokumen baru.
- Setelah snapshot database dan storage diverifikasi tetap tersedia, 11 record
  `km_pengajuans` ID 21 sampai 31 dihapus dalam satu transaksi.
- Relasi baca, suka, transaksi, bookmark, tag, dan co-author yang terkait telah
  terhapus melalui constraint database; pemeriksaan pascapenghapusan menunjukkan
  tidak ada relasi tersisa untuk ID tersebut.
- File dan manifest insiden pada snapshot tetap dipertahankan sebagai bukti audit.
  File dummy tidak digunakan untuk membuat ulang dokumen.
- Hasil `km:readiness` setelah penghapusan adalah 10 PASS, 2 WARN, dan 0 FAIL;
  pemeriksaan checksum melaporkan 0 file hilang atau mismatch.
- Status pemulihan file ditutup sebagai `resolved_by_record_retirement`. Input
  ulang manual harus membuat record dan file privat baru melalui alur KM normal.
