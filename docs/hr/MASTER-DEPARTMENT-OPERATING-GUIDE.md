# Master Department dan pemisahan Sales

## Pengelolaan harian

Menu: **HR → Base Competency → Master Department**. Akses mengikuti pengelola Mapping Karyawan existing, dengan pemeriksaan server pada setiap endpoint.

- Tambah department, cari/filter status, lalu buka Detail / Edit untuk mengganti nama atau status.
- Department yang masih memiliki section atau posisi aktif tidak dapat dinonaktifkan. Tidak ada hapus permanen.
- Pindahkan section dari halaman detail department asal. Seluruh posisi dalam section ikut berpindah, termasuk yang nonaktif. Karyawan tetap memegang ID posisi yang sama.
- Approval tidak otomatis berubah pada pemindahan harian. HR mengaturnya terpisah melalui Master Job Position.
- Posisi tanpa section dikelola melalui Master Job Position. Halaman lama yang sudah tidak sesuai struktur akan ditolak; muat ulang sebelum mencoba kembali.

Department adalah master organisasi; section berada pada satu department; posisi memiliki department dan section opsional. Karyawan dapat memegang beberapa posisi melalui Mapping Karyawan existing.

## Preview pemisahan Sales

```powershell
php artisan hr:split-sales-regions
```

Preview tidak menulis data atau snapshot. Periksa status, tabel section/posisi tujuan, sumber pejabat, perubahan approval, dan hash. Hash berlaku untuk keadaan data serta tanggal preview tersebut; ulangi preview jika data/tanggal berubah.

Pemetaan awal yang telah disepakati:

| Job kepala lama | Job baru untuk pejabat yang sama |
| --- | --- |
| Sales Dept Head Region 1&2 | Sales Dept Head Region 1 dan Region 3 |
| Sales Dept. Head Region 3&4 | Sales Dept Head Region 2 dan Region 4 |

Empat section regional dan posisi existing berpindah ke department Sales Region 1–4 masing-masing. Referensi master approval ke kepala gabungan dialihkan sesuai region bawahannya, termasuk level 1 dan 2 pada Region 3–4. Kepala baru terhubung ke Sales Div Head pada level 3.

## Penerapan setelah review

```powershell
php artisan hr:split-sales-regions --apply --actor=<ID_PENGELOLA_HR> --reviewed-hash=<HASH_PREVIEW>
```

Ganti placeholder dengan ID user pengelola existing dan hash preview yang telah diperiksa. Jadwalkan saat aktivitas penataan organisasi/approval rendah; transaksi memegang lock master organisasi sampai selesai.

- Command memvalidasi ulang data dan actor, lalu menyimpan snapshot private sebelum menulis data.
- Lokasi snapshot dicetak setelah berhasil: `storage/app/private/hr/sales-region-split/`. Isinya memuat referensi assignment; batasi akses ke operator berwenang dan jangan publikasikan.
- Semua perubahan database dan audit berada dalam satu transaksi. Kegagalan membatalkan perubahan database; snapshot yang sudah dibuat tetap tersedia untuk pemeriksaan.
- Data ambigu, tujuan yang sudah ada tanpa jurnal selesai, assignment kepala yang tidak valid, atau target KM pada department/job kepala lama memblokir penerapan. Selesaikan penyebabnya, lalu review preview baru.
- Setelah berhasil, eksekusi ulang membaca jurnal penyelesaian dan tidak membuat duplikasi atau mengembalikan perubahan pejabat berikutnya oleh HR.

## Pemeriksaan sesudahnya

Pastikan empat department/job kepala aktif, dua job/dept gabungan nonaktif, assignment baru efektif pada tanggal penerapan, dan kepala lama menempati pasangan 1&3 atau 2&4. Periksa approval dan cakupan TCPD menggunakan masing-masing pejabat.

Job baru **tanpa standar kompetensi**: HR harus mengisi TC, SK, dan Additional sebelum membuat penilaian baru. Training, assessment, standar lama, dan Working Experience tidak disalin atau ditulis ulang. Histori job lama tetap berlabel lama; akses baca assessment arsip tersedia untuk full-access HR.

Tidak ada rollback otomatis yang menghapus job atau mengembalikan snapshot secara massal. Jika perlu koreksi setelah commit, periksa snapshot dan perubahan HR sesudah cutover sebelum membuat perubahan korektif. Jangan mengulang restore lama karena dapat menimpa perubahan yang sah.

## Bukti verifikasi implementasi, 26 Oktober 2026

- HR/TCPD: 88 tes / 738 assertion lulus melalui `php artisan test tests/Feature/HR tests/Feature/Dashboard/TcpdDashboardAuthorizationTest.php tests/Unit/JobPositionAccessServiceTest.php tests/Unit/TcpdDashboardAccessServiceTest.php`.
- KM: 11 tes / 145 assertion lulus melalui `php artisan test tests/Feature/KnowledgeManagement/KmApprovalWorkflowTest.php tests/Feature/KnowledgeManagement/KmRoadmapPriorityTest.php`. Percobaan seluruh suite KM dihentikan untuk membatasi pemeriksaan pada integrasi yang terdampak; tidak ada klaim seluruh suite KM lulus.
- Cutover: 12 tes / 109 assertion lulus setelah fixture diberi unique constraint dan foreign key organisasi/assignment/audit; termasuk rollback, snapshot gagal, stale hash, retry tanpa perubahan, dan pemetaan/cakupan pejabat.
- Pint terbatas pada sembilan berkas terkait lulus; sintaks sebelas berkas PHP dan skrip browser lulus. Build `npm.cmd run build` lulus dengan peringatan existing PDF.js `eval` dan ukuran chunk.
- Browser fixture: ekspor melalui `DEPARTMENT_BROWSER_EXPORT=1` saat menjalankan `DepartmentManagementTest`, kemudian `node tests/Browser/DepartmentManagement.browser.mjs`. Empat skenario pada 1440/375 px, 28 assertion lulus. Screenshot dan laporan berada di `storage/framework/department-browser-qa/` dan diabaikan Git. Semua network aplikasi diblokir dalam skenario fixture ini.
- Preview database lokal berstatus `ready`: empat department, empat kepala, empat section, sepuluh referensi approval, dua assignment lama. Tidak ada `--apply` pada database aplikasi.
- Endpoint nyata berhasil diarahkan ke login, tetapi alur UI dengan sesi HR nyata, staging/produksi, dan suite aplikasi penuh belum diverifikasi.
- `git diff --check` untuk berkas fitur bersih. Pemeriksaan seluruh workspace masih menemukan whitespace pada perubahan Outstanding Material dan Warehouse yang sudah ada sebelum task ini; perubahan tersebut dipertahankan.
