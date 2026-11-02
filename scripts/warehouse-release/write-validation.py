"""Summarize executed evidence; do not convert suite failures into release approval."""
from pathlib import Path
import json
import xml.etree.ElementTree as ET

root = Path(__file__).resolve().parents[2]
package = root / 'deploy-warehouse/WAREHOUSE-20261022'
private = root / 'storage/app/warehouse-release-private'
xml = ET.parse(private / 'warehouse-tests.xml').getroot()
totals = xml.find('testsuite').attrib
failures = []
for case in xml.iter('testcase'):
    issue = case.find('failure')
    if issue is None:
        issue = case.find('error')
    if issue is not None:
        failures.append({'class': case.attrib['class'], 'test': case.attrib['name'], 'type': issue.attrib.get('type')})
assert len(failures) == 5
result = {'tests': int(totals['tests']), 'passed': int(totals['tests']) - len(failures), 'failed': len(failures), 'assertions': int(totals['assertions']), 'skipped': int(totals['skipped']), 'duration_seconds': float(totals['time']), 'failures': failures, 'database': 'fastware_adsi_1_testing', 'compiled_views': 'storage/framework/warehouse-release-test-views', 'full_suite_passed': False, 'production_executed': False}
(package / 'docs/WAREHOUSE-TEST-RESULT.json').write_text(json.dumps(result, indent=2) + '\n', encoding='utf-8')
report = '''# Hasil validasi — Warehouse 20261022

## Dilakukan dan lulus

- Backup database lokal penuh (113 tabel) dipulihkan ke database baru `_testing`; jumlah dan hash data serta schema/auto-increment dibandingkan. Backup 3.966.785 byte; SHA-256 `8d5d0cfe9790fd58018d43a9e60fae14775cd84089e50f5dffa7bedeae5fdf2b`.
- Rehearsal reset, rollback kegagalan yang disengaja, reset lokal dan perbandingan pasca-reset lulus. Tujuh tabel transaksi/sequence kosong; empat item tetap, lima saldo nol. Sebanyak 105 tabel lain tetap identik; atribut non-saldo master juga identik.
- `php scripts/warehouse-release/verify-migrations.php`: 23 migration Warehouse direplay pada database baru `_testing`; 11 tabel cocok pada metadata kolom, default, indeks dan foreign key dengan schema lokal.
- `php scripts/warehouse-release/verify-sql.php`: instalasi MySQL 8.0.30 lulus; NPK hilang/ambigu ditolak sebelum DDL; schema kehilangan FK ditolak; kegagalan import master rollback; ID user yang berbeda dipetakan benar; ledger lama dipertahankan; 23 migration masing-masing tercatat satu kali; replay ditolak tanpa perubahan.
- `php scripts/warehouse-release/verify-loader.php`: 61 class/enum payload berhasil dimuat tanpa enam entri autoload.files Warehouse milik Composer lokal; include ganda aman.
- `npm.cmd run build`: lulus. Percobaan sandbox awal gagal spawn EPERM; build diulang di luar sandbox. Warning existing pdfjs eval dan chunk >500 kB masih muncul.
- `php artisan view:cache`: lulus. `php artisan route:list --path=warehouse --except-vendor`: 36 route terdaftar.
- `php vendor/bin/pint scripts/warehouse-release --test`: lulus. Formatting hanya tooling baru. `git diff --check -- scripts/warehouse-release`: lulus.
- `python scripts/warehouse-release/check-package.py`: mencocokkan seluruh source manifest, PHP lint, sintaks JavaScript, dependency Vite, SQL non-destruktif, pengecualian artifact privat, serta isi ZIP/checksum. Angka final tersedia di PACKAGE-VERIFICATION.json.

## Suite Warehouse — 187 lulus, 5 gagal, 1.098 assertion

Perintah: `php artisan test tests/Feature/Warehouse tests/Unit/Warehouse --compact --log-junit=storage/app/warehouse-release-private/warehouse-tests.xml`.
Database: `fastware_adsi_1_testing`; dijalankan serial dengan VIEW_COMPILED_PATH terisolasi. Durasi 1.026,53 detik. Percobaan pertama dihentikan karena sandbox menolak penulisan compiled view; angka di atas berasal dari pengulangan lengkap di luar sandbox.

Kelima kegagalan terdapat pada WarehouseSchemaTest:

1. `test_all_domain_tables_and_required_columns_exist`: masih mengharapkan `mst_wh_consumables.machine_type`; migration 2026_10_06_000003 menghapus kolom tersebut.
2. `test_warehouse_migration_can_be_rolled_back_and_reapplied`: daftar down test belum memasukkan migration terbaru sehingga mencoba menghapus machine_type yang sudah tidak ada.
3. `test_revision_two_backfills_existing_stock_as_new_at_the_legacy_location`: setup down revision lama mengalami error kolom machine_type yang sama.
4. `test_revision_two_stops_before_schema_change_when_nonzero_legacy_stock_has_no_location`: setup down mengalami error yang sama sebelum skenario guard dijalankan.
5. `test_restricted_verifier_seed_resolves_exact_active_name_and_npk_idempotently`: pada suite guard NPK 5639 menemukan jumlah akun aktif yang tidak sesuai. Pengulangan test ini secara terisolasi berhasil mencapai assertion terakhir, tetapi gagal karena test mengharapkan dua verifier sedangkan migration baru menambahkan Yusuf sehingga ada tiga. Pengulangan terisolasi: 1 gagal, 7 assertion.

Source aplikasi, migration lama dan test tersebut tidak diubah oleh pekerjaan packaging ini. Kegagalan suite tetap terbuka; tidak ada klaim seluruh test lulus. Rehearsal forward-install yang terpisah membuktikan schema terbaru dan ketiga verifier sesuai paket, bukan membuktikan rollback migration lama.

## Belum diverifikasi

Production belum diakses. Versi MySQL, hak CREATE ROUTINE/EXECUTE, schema organisasi, assignment/user production, kecocokan baseline file bersama server, filesystem foto/symlink, Composer authoritative/preload, OPcache, browser desktop/mobile, dan scanner fisik production belum diuji. Gunakan preflight dan smoke checklist. Rehearsal dilakukan dengan mysql CLI lokal; import antarmuka phpMyAdmin hosting belum dijalankan.

## Penyesuaian struktur paket

Paket mengikuti root Laravel: app/, bootstrap/, config/, database/, public/, resources/, routes/. Tidak ada folder runtime, shared-integration, atau support. File bersama tersedia lengkap dengan integrasi Warehouse; bootstrap loader sudah terpasang. Routes dan layout diambil dari baseline commit dalam source-manifest.json dengan blok Warehouse aktif terbaru, sehingga perubahan lokal HR/Outstanding Material yang belum di-commit tidak ikut terbawa.
Penyesuaian ini hanya pada artifact dan tooling paket. Loader, class bersama, bootstrap, kesamaan blok route/menu Warehouse, lint PHP/JavaScript, path, manifest Vite, dan checksum ZIP diperiksa ulang. SQL dan hasil reset tidak berubah; suite Warehouse tidak diulang untuk perubahan foldering ini dan hasil 187/5 di atas tetap merupakan hasil pengujian sebelumnya.

## Kondisi akhir lokal dan bahan privat

Database lokal dms_adasi_rev1 hanya mengalami reset yang disetujui; fingerprint akhir cocok dengan kondisi pasca-reset. Aplikasi telah keluar dari maintenance. Tidak ada commit, push, atau akses production.
Backup penuh dan dump rehearsal berisi data sensitif dan disimpan di `storage/app/warehouse-release-private/20261022_134904/`, di luar ZIP. Database rehearsal `_testing` sengaja dipertahankan untuk bukti, termasuk salinan backup penuh; jangan dipublikasikan. Tidak ada database rehearsal yang dihapus otomatis.
'''
(package / 'docs/VALIDATION.md').write_text(report, encoding='utf-8')
print(json.dumps({'passed': result['passed'], 'failed': result['failed'], 'assertions': result['assertions']}))
