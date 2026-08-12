# Daftar Migration Tertunda KM Engagement Foundation

Tanggal: 27 Juli 2026  
Status database aplikasi lokal: **BELUM DIJALANKAN**  
Status Git remote: **BELUM DI-PUSH**

Dokumen ini adalah daftar pekerjaan schema yang harus dijalankan kemudian pada environment target setelah backup dan preflight. Implementasi saat ini hanya menguji siklus migration pada MySQL testing dengan nama database bersuffix `_testing`.

## Urutan Wajib

| Urutan | Migration Laravel | SQL manual pasangan | Dampak |
| ---: | --- | --- | --- |
| 1 | `2026_07_27_130001_create_km_notifications_table.php` | `2026_07_27_130001_create_km_notifications_table.sql` | Membuat notification center user-scoped dengan event key unik. |
| 2 | `2026_07_27_130002_add_km_reading_progress_to_km_transaksis.php` | `2026_07_27_130002_add_km_reading_progress_to_km_transaksis.sql` | Menambah bitmap halaman unik, waktu aktif, persen, halaman terakhir, dan index progress. |
| 3 | `2026_07_27_130003_extend_km_insights_social.php` | `2026_07_27_130003_extend_km_insights_social.sql` | Menambah thread/soft delete/featured insight serta tabel reaction dan mention. |
| 4 | `2026_07_27_130004_create_km_point_ledger_table.php` | `2026_07_27_130004_create_km_point_ledger_table.sql` | Membuat ledger append-only dan opening balance dari `users.km_total_poin`. |

Keempat migration adalah satu release group. Jangan mengaktifkan code Engagement Foundation bila hanya sebagian schema tersedia.

## Pilih Tepat Satu Jalur

### Jalur A â€” Laravel Migration

Gunakan pada environment yang memang dikelola Artisan. Setelah seluruh preflight lolos, jalankan setiap path sesuai urutan. Jangan menjalankan file SQL manual sesudahnya.

```powershell
php artisan migrate --path=database/migrations/2026_07_27_130001_create_km_notifications_table.php
php artisan migrate --path=database/migrations/2026_07_27_130002_add_km_reading_progress_to_km_transaksis.php
php artisan migrate --path=database/migrations/2026_07_27_130003_extend_km_insights_social.php
php artisan migrate --path=database/migrations/2026_07_27_130004_create_km_point_ledger_table.php
```

### Jalur B â€” SQL Manual Production

Ikuti `deploy-km/DEPLOY.md`. Jalankan empat `.sql` sesuai urutan. Masing-masing SQL memasukkan row migration secara guarded, sehingga **jangan** menjalankan Artisan migration untuk empat file yang sama.

## Preflight Sebelum Eksekusi

- Pastikan backup database selesai dan dapat dibaca.
- Pastikan sebelas migration KM `100001`â€“`120001` yang disebut di `deploy-km/DEPLOY.md` sudah ran pada target. Bila belum, hentikan deployment.
- `km_transaksis.points_awarded_at` harus tersedia sebelum `130002`.
- Pastikan tidak ada tabel/kolom/index/constraint parsial dengan nama yang sama.
- Pastikan key `users.id` adalah `BIGINT UNSIGNED`, sedangkan `km_pengajuans.id` dan `km_insights.id` kompatibel dengan `INT` legacy target.
- Pastikan `users.km_total_poin` dan `users.section` tersedia untuk opening balance serta fallback snapshot.
- Audit assignment organisasi aktif. `130004` memakai assignment aktif terbaru; `users.section` hanya fallback.
- Catat jumlah user dengan `km_total_poin > 0` sebelum opening balance.

## Verifikasi Setelah Eksekusi

```powershell
php artisan route:list --path=km
php artisan view:cache
php artisan km:health --json
php artisan km:readiness --json
php artisan km:reconcile-points --json
```

Hasil reconciliation harus tidak memiliki drift. Verifikasi juga:

- event key notification dan ledger unik;
- `unique_pages_count <= pages_total` serta `progress_percent <= 100`;
- tidak ada parent insight lintas dokumen;
- reaction maksimum satu row per user/insight;
- opening balance tepat satu row per user yang memiliki saldo lama.

## Rollback

Migration Laravel memiliki `down()` nyata tetapi sengaja diberi guard: hanya boleh berjalan saat `APP_ENV=testing` dan nama database berakhiran `_testing`. Untuk production, rollback code lebih dahulu dan pertahankan data additive. Bagian rollback SQL tersedia sebagai komentar dan memerlukan approval terpisah karena dapat menghapus notification, progress, diskusi, atau ledger.
