"""Build the approved manual-upload release from canonical source and reset export."""
from pathlib import Path
import hashlib
import json
import re
import shutil
import subprocess
import zipfile

ROOT = Path(__file__).resolve().parents[2]
RELEASE = 'WAREHOUSE-20261022'
DEST = ROOT / 'deploy-warehouse' / RELEASE
PRIVATE = Path(json.loads((ROOT / 'storage/app/warehouse-release-private/latest.json').read_text())['private_directory'])
DATA = json.loads((PRIVATE / 'export.json').read_text())
assert DATA.get('timestamp_timezone') == '+00:00', 'Re-export TIMESTAMP values in UTC before building SQL'
REPORT = json.loads((PRIVATE / 'report.json').read_text())
assert REPORT['local_reset_committed'] and REPORT['backup_restore_verified']
TABLES = list(DATA['schema'])
EMPTY = ['log_wh_verifications', 'trs_wh_stock_transaction_machine_usages', 'trs_wh_stock_ins', 'trs_wh_location_shipments', 'trs_wh_stock_transactions', 'wh_transaction_sequences', 'wh_stock_in_sequences']
CONSOLIDATED = json.loads((ROOT / 'composer.json').read_text())['autoload']['files']
SOURCES = {}
REVISION = subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip()

def write(relative, content):
    path = DEST / relative
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(content.replace('\r\n', '\n'), encoding='utf-8', newline='\n')

def copy(source, target):
    destination = DEST / target
    destination.parent.mkdir(parents=True, exist_ok=True)
    shutil.copyfile(ROOT / source, destination)
    SOURCES[target] = {'source': source, 'sha256': hashlib.sha256((ROOT / source).read_bytes()).hexdigest()}

def text(source):
    return (ROOT / source).read_text(encoding='utf-8-sig')

def integrated(source, content, basis=None):
    write(source, content)
    original = subprocess.check_output(['git', 'show', f'{basis}:{source}'], cwd=ROOT) if basis else (ROOT / source).read_bytes()
    SOURCES[source] = {'source': source, 'source_sha256': hashlib.sha256(original).hexdigest(), 'sha256': hashlib.sha256((DEST / source).read_bytes()).hexdigest(), 'basis_revision': basis, 'integration': 'Warehouse integrated; unrelated uncommitted changes excluded'}

def sql(value):
    if value is None:
        return 'NULL'
    if isinstance(value, (int, float)):
        return str(value)
    if value == '':
        return "''"
    return 'CONVERT(0x' + str(value).encode('utf-8').hex() + ' USING utf8mb4)'

def fail_if(condition, message):
    return f"IF {condition} THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = '{message}'; END IF;\n"

def routine(name, body, transaction=False):
    handler = 'DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;\n' if transaction else ''
    return '-- Import once in the selected application database. Never use --force.\nSET NAMES utf8mb4;\nSET SESSION time_zone = \'+00:00\';\nDELIMITER $$\nCREATE PROCEDURE ' + name + '()\nSQL SECURITY INVOKER\nBEGIN\n' + handler + body + '\nEND$$\nDELIMITER ;\nCALL ' + name + '();\n'

users = {int(u['id']): u for u in DATA['users']}
required = {5639, 5439, 5472}
for table in ['mst_wh_consumable_categories', 'mst_wh_consumables']:
    for row in DATA[table]:
        for field in ['created_by', 'updated_by']:
            if row[field] is not None:
                assert users[int(row[field])]['npk'] is not None
                required.add(int(users[int(row[field])]['npk']))

def resolve(npk):
    # Same duplicate-NPK selection as WarehouseIdentityResolver: unique active
    # account, otherwise exactly one active Administrator (role_id=1).
    return f'(SELECT CASE WHEN COUNT(*)=1 THEN MIN(id) WHEN SUM(role_id=1)=1 THEN MAX(CASE WHEN role_id=1 THEN id END) ELSE NULL END FROM users WHERE npk={npk} AND is_active=0)'

def checks():
    body = fail_if("DATABASE() IS NULL", 'Select the application database first')
    body += fail_if("VERSION() NOT REGEXP '^8[.]' OR VERSION() LIKE '%MariaDB%'", 'This release requires MySQL 8; MariaDB is not verified')
    support = {'users': ['id', 'npk', 'role_id', 'is_active'], 'migrations': ['id', 'migration', 'batch'], 'mst_departments': ['id', 'name', 'is_active'], 'mst_job_positions': ['id', 'department_id', 'is_active'], 'user_job_positions': ['user_id', 'mst_job_position_id', 'is_active']}
    for table, columns in support.items():
        body += fail_if(f"(SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND ENGINE='InnoDB')<>1", f'Missing InnoDB prerequisite: {table}')
        quoted = ','.join("'" + c + "'" for c in columns)
        body += fail_if(f"(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND COLUMN_NAME IN ({quoted}))<>{len(columns)}", f'Prerequisite columns differ: {table}')
    body += fail_if("EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_job_positions' AND COLUMN_NAME='effective_from') AND NOT EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='user_job_positions' AND COLUMN_NAME='effective_until')", 'Assignment date columns are incomplete')
    for npk in sorted(required):
        body += fail_if(resolve(npk) + ' IS NULL', f'NPK {npk} missing, inactive or ambiguous')
    names = ','.join("'" + p.stem + "'" for p in migrations)
    body += fail_if('EXISTS(SELECT 1 FROM migrations WHERE migration IN (' + names + ') GROUP BY migration HAVING COUNT(*)>1)', 'Duplicate Warehouse migration ledger entries; inspect first')
    return body

# Runtime files only. Reset commands, seeders and private data stay outside.
directories = ['app/Data/Warehouse', 'app/Enums/Warehouse', 'app/Http/Controllers/Warehouse', 'app/Http/Requests/Warehouse', 'app/Models/Warehouse', 'app/Services/Warehouse', 'resources/css/warehouse', 'resources/js/warehouse', 'resources/views/warehouse', 'resources/views/components/warehouse']
for directory in directories:
    for path in sorted((ROOT / directory).rglob('*')):
        if path.is_file() and path.name != 'WarehouseResetBackupService.php':
            relative = path.relative_to(ROOT).as_posix()
            copy(relative, relative)
for relative in ['app/Exceptions/WarehouseDomainException.php', 'app/Exports/WarehouseTransactionsExport.php', 'app/Http/Middleware/EnsureWarehousePermission.php', 'config/warehouse.php', 'resources/views/vendor/pagination/warehouse-bootstrap-5.blade.php']:
    copy(relative, relative)
for path in sorted((ROOT / 'public/build').rglob('*')):
    if path.is_file():
        relative = path.relative_to(ROOT).as_posix()
        copy(relative, relative)

loader = "<?php\n\n// Load after vendor/autoload.php, before Laravel boots its providers.\n// Safe whether or not Composer already includes the consolidated files.\n"
for source in CONSOLIDATED:
    loader += f"require_once dirname(__DIR__).'/{source}';\n"
write('bootstrap/warehouse-autoload.php', loader)
bootstrap = text('bootstrap/app.php')
assert bootstrap.count('return $app;') == 1
integrated('bootstrap/app.php', bootstrap.replace('return $app;', "require_once __DIR__.'/warehouse-autoload.php';\n\nreturn $app;"))

routes = text('routes/web.php')
start = routes.index("    Route::prefix('warehouse')->name('warehouse.')->group(function () {")
end = routes.index('\n    });\n});', start) + len('\n    });')
route_block = routes[start:end]
# Full shared files follow the committed application baseline, with just the
# current Warehouse block replaced. Do not ship unrelated uncommitted routes.
baseline_routes = subprocess.check_output(['git', 'show', f'{REVISION}:routes/web.php'], cwd=ROOT).decode('utf-8-sig')
base_start = baseline_routes.index("    Route::prefix('warehouse')->name('warehouse.')->group(function () {")
base_end = baseline_routes.index('\n    });\n});', base_start) + len('\n    });')
integrated('routes/web.php', baseline_routes[:base_start] + route_block + baseline_routes[base_end:], REVISION)
layout = text('resources/views/layout.blade.php')
start = layout.rfind('                        @php', 0, layout.index('$warehouseMenuVisible'))
end = layout.index('                        <!-- Dropdown Sales -->', start)
menu_block = layout[start:end]
baseline_layout = subprocess.check_output(['git', 'show', f'{REVISION}:resources/views/layout.blade.php'], cwd=ROOT).decode('utf-8-sig')
base_start = baseline_layout.rfind('                        @php', 0, baseline_layout.index('$warehouseMenuVisible'))
base_end = baseline_layout.index('                        <!-- Dropdown Sales -->', base_start)
integrated('resources/views/layout.blade.php', baseline_layout[:base_start] + menu_block + baseline_layout[base_end:], REVISION)
for source in ['app/Http/Kernel.php', 'app/Providers/AuthServiceProvider.php', 'app/Providers/RouteServiceProvider.php', 'app/Models/MstDepartment.php', 'app/Models/MstJobPosition.php', 'app/Models/UserJobPosition.php', 'vite.config.js']:
    copy(source, source)

migrations = sorted(p for p in (ROOT / 'database/migrations').glob('*.php') if re.search(r'wh_|legacy_location_shipments|legacy_stock_out_machine_usages', p.name))
for path in migrations:
    relative = path.relative_to(ROOT).as_posix()
    copy(relative, relative)
assert len(migrations) == 23, 'Reconcile the migration manifest before changing the release'

preflight = "-- READ ONLY. Save the results. 01_schema repeats the blocking checks.\nSELECT DATABASE() AS target_database, VERSION() AS engine_version, @@version_comment AS engine_comment;\nSHOW GRANTS FOR CURRENT_USER();\n"
preflight += "SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND (TABLE_NAME LIKE 'mst\\_wh\\_%' OR TABLE_NAME LIKE 'trs\\_wh\\_%' OR TABLE_NAME LIKE 'log\\_wh\\_%' OR TABLE_NAME LIKE 'wh\\_%');\n"
for npk in sorted(required):
    preflight += f'SELECT {npk} AS npk, {resolve(npk)} AS resolved_production_user_id;\n'
preflight += "SELECT d.name, COUNT(DISTINCT a.user_id) AS active_assigned_users FROM mst_departments d JOIN mst_job_positions p ON p.department_id=d.id JOIN user_job_positions a ON a.mst_job_position_id=p.id JOIN users u ON u.id=a.user_id WHERE d.is_active=1 AND p.is_active=1 AND a.is_active=1 AND u.is_active=0 GROUP BY d.name;\n"
write('database/00_preflight.sql', preflight)

schema_body = checks()
quoted_tables = ','.join("'" + t + "'" for t in TABLES)
schema_body += fail_if(f'(SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({quoted_tables}))<>0', 'Warehouse already exists or installation is partial; stop')
schema_body += fail_if("EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME REGEXP '^(mst_wh_|trs_wh_|log_wh_|wh_)')", 'Unexpected Warehouse tables already exist; stop')
foreign_keys = []
for table, ddl in DATA['schema'].items():
    lines = ddl.splitlines()
    for line in lines:
        if line.lstrip().startswith('CONSTRAINT ') and 'FOREIGN KEY' in line:
            foreign_keys.append(f'ALTER TABLE `{table}` ADD {line.strip().rstrip(",")};')
    lines = [line for line in lines if not (line.lstrip().startswith('CONSTRAINT ') and 'FOREIGN KEY' in line)]
    ddl = '\n'.join(lines)
    ddl = re.sub(r',\n\)', '\n)', ddl)
    ddl = re.sub(r' AUTO_INCREMENT=\d+', '', ddl)
    schema_body += ddl + ';\n'
schema_body += '\n'.join(foreign_keys) + '\n'
schema_body += "SELECT 'SCHEMA_OK: 11 tables created; now import 02_master_access.sql' AS result;\n"
write('database/01_schema.sql', routine('warehouse_release_20261022_schema', schema_body))

def schema_checks():
    result = ''
    for table, columns in DATA['columns'].items():
        tests = []
        for column in columns:
            tests.append("(COLUMN_NAME=" + sql(column['COLUMN_NAME']) + ' AND COLUMN_TYPE=' + sql(column['COLUMN_TYPE']) + ' AND IS_NULLABLE=' + sql(column['IS_NULLABLE']) + ')')
        result += fail_if(f"(SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}')<>{len(columns)} OR (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND (" + ' OR '.join(tests) + f'))<>{len(columns)}', 'Schema columns differ: ' + table)
        result += fail_if(f"(SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND ENGINE='InnoDB')<>1", 'Expected InnoDB table: ' + table)
        indexes = []
        fks = []
        for line in DATA['schema'][table].splitlines():
            match = re.match(r'\s*(PRIMARY KEY|UNIQUE KEY `([^`]+)`|KEY `([^`]+)`) \(([^)]+)\)', line)
            if match:
                name = 'PRIMARY' if match[1] == 'PRIMARY KEY' else (match[2] or match[3])
                non_unique = 0 if match[1].startswith(('PRIMARY', 'UNIQUE')) else 1
                names = re.findall(r'`([^`]+)`', match[4])
                indexes.append(name)
                result += fail_if(f"(SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND INDEX_NAME='{name}')<>{len(names)}", 'Index shape differs: ' + name)
                for ordinal, column in enumerate(names, 1):
                    result += fail_if(f"(SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}' AND INDEX_NAME='{name}' AND SEQ_IN_INDEX={ordinal} AND COLUMN_NAME='{column}' AND NON_UNIQUE={non_unique} AND SUB_PART IS NULL)<>1", 'Index definition differs: ' + name)
            match = re.match(r'\s*CONSTRAINT `([^`]+)` FOREIGN KEY \(`([^`]+)`\) REFERENCES `([^`]+)` \(`([^`]+)`\)(.*)', line)
            if match:
                name, column, target, target_column, tail = match.groups()
                deletion = re.search(r'ON DELETE (SET NULL|CASCADE|RESTRICT|NO ACTION)', tail)
                rule = deletion[1] if deletion else 'NO ACTION'
                fks.append(name)
                result += fail_if(f"(SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME='{table}' AND k.CONSTRAINT_NAME='{name}' AND k.COLUMN_NAME='{column}' AND k.REFERENCED_TABLE_SCHEMA=DATABASE() AND k.REFERENCED_TABLE_NAME='{target}' AND k.REFERENCED_COLUMN_NAME='{target_column}' AND r.DELETE_RULE='{rule}' AND r.UPDATE_RULE='NO ACTION')<>1", 'Foreign key differs: ' + name)
        result += fail_if(f"(SELECT COUNT(DISTINCT INDEX_NAME) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='{table}')<>{len(indexes)}", 'Unexpected index count: ' + table)
        result += fail_if(f"(SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='{table}')<>{len(fks)}", 'Unexpected FK count: ' + table)
    return result

master_body = checks() + schema_checks()
for table in TABLES:
    master_body += fail_if(f'(SELECT COUNT(*) FROM `{table}`)<>0', 'Expected empty table: ' + table)
master_body += 'START TRANSACTION;\n'
for table in ['mst_wh_consumable_categories', 'mst_wh_consumables']:
    for row in DATA[table]:
        fields, values = [], []
        for key, value in row.items():
            fields.append('`' + key + '`')
            if key in ['created_by', 'updated_by'] and value is not None:
                values.append(resolve(int(users[int(value)]['npk'])))
            else:
                values.append(sql(value))
        master_body += f'INSERT INTO `{table}` (' + ','.join(fields) + ') VALUES (' + ','.join(values) + ');\n'
for row in DATA['mst_wh_restricted_verifiers']:
    npk = int(users[int(row['user_id'])]['npk'])
    assert npk in [5639, 5439, 5472]
    master_body += 'INSERT INTO mst_wh_restricted_verifiers (id,user_id,scope,is_active,created_at,updated_at) VALUES (' + ','.join([sql(row['id']), resolve(npk), sql(row['scope']), sql(row['is_active']), sql(row['created_at']), sql(row['updated_at'])]) + ');\n'
master_body += fail_if('(SELECT COUNT(*) FROM mst_wh_consumables WHERE current_stock<>0 OR stock_ds8<>0 OR stock_deltamas<>0 OR stock_used_ds8<>0 OR stock_used_deltamas<>0)<>0', 'Master stock is not zero')
# Do not copy local ledger IDs or batches. Existing migration entries stay intact.
master_body += 'SET @warehouse_release_batch=(SELECT COALESCE(MAX(batch),0)+1 FROM migrations);\n'
for path in migrations:
    name = path.stem
    master_body += f"INSERT INTO migrations (migration,batch) SELECT '{name}',@warehouse_release_batch WHERE NOT EXISTS(SELECT 1 FROM migrations WHERE migration='{name}');\n"
master_body += "COMMIT;\nSELECT 'MASTER_ACCESS_OK: master imported; no transaction data imported' AS result;\n"
write('database/02_master_access.sql', routine('warehouse_release_20261022_master', master_body, True))

post = "-- READ ONLY. Expected: 4 items, 1 category, 3 verifiers; all transaction counts and balances zero.\n"
for table in TABLES:
    post += f"SELECT '{table}' AS table_name, COUNT(*) AS row_count FROM `{table}`;\n"
post += 'SELECT SUM(current_stock) AS total, SUM(stock_ds8) AS ds8, SUM(stock_deltamas) AS deltamas, SUM(stock_used_ds8) AS used_ds8, SUM(stock_used_deltamas) AS used_deltamas FROM mst_wh_consumables;\n'
post += 'SELECT u.npk, v.scope, v.is_active FROM mst_wh_restricted_verifiers v JOIN users u ON u.id=v.user_id ORDER BY u.npk;\n'
post += "SELECT migration,COUNT(*) AS copies FROM migrations WHERE migration IN (" + ','.join("'" + p.stem + "'" for p in migrations) + ') GROUP BY migration ORDER BY migration;\n'
post += "SELECT TABLE_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME IN (" + quoted_tables + ') ORDER BY TABLE_NAME,CONSTRAINT_NAME;\n'
write('database/03_postcheck.sql', post)
preview = {'timestamp_timezone': '+00:00', 'mst_wh_consumable_categories': [], 'mst_wh_consumables': [], 'restricted_verifier_npks': [5639, 5439, 5472]}
for table in ['mst_wh_consumable_categories', 'mst_wh_consumables']:
    for source in DATA[table]:
        row = dict(source)
        for field in ['created_by', 'updated_by']:
            value = row.pop(field)
            row[field + '_npk'] = None if value is None else int(users[int(value)]['npk'])
        preview[table].append(row)
write('database/master-preview.json', json.dumps(preview, indent=2, ensure_ascii=False) + '\n')

write('README.md', '''# Warehouse 20261022 — mulai di sini

Paket instalasi Warehouse pertama kali pada aplikasi Fastware yang sudah ada.
Cara deploy: upload file + phpMyAdmin. Production belum diakses atau diubah.

Hasil validasi dan batasannya ada di `docs/VALIDATION.md`. Suite Warehouse belum seluruhnya hijau; baca lima kegagalan test schema sebelum memutuskan aktivasi production.

1. Baca `docs/DEPLOY.md`, khususnya backup, maintenance, integrasi shared, dan privilege SQL.
2. Import `database/00_preflight.sql`, lalu `01_schema.sql`, `02_master_access.sql`, `03_postcheck.sql` **satu per satu** pada database aplikasi yang benar.
3. Upload `app/`, `bootstrap/`, `config/`, `resources/`, dan `routes/` sesuai path yang sama pada root Laravel. Upload `public/build/` ke folder public aktual.
4. File routes, layout, middleware, gates dan rate limiter sudah berupa file lengkap dengan integrasi Warehouse. Periksa kecocokan baseline file bersama production sebelum menggantinya; lihat `docs/DEPLOY.md`.
5. Hapus cache yang disebutkan dalam DEPLOY melalui File Manager, lalu jalankan smoke checklist sebelum membuka kembali akses.

Kode Warehouse berasal dari source aktif, termasuk perubahan lokal Validasi Stok terbaru. Struktur paket mengikuti root project; tidak ada direktori perantara runtime, shared-integration, atau support.
File SQL berada di database/. Migration asli berada di database/migrations/ sebagai rekam schema; pemasangan ini memakai SQL, bukan migrate umum. Tidak ada vendor, .env, backup database, seeder, command reset atau data transaksi di paket.
''')
write('docs/DEPLOY.md', '''# Upload dan SQL melalui hosting

## Sebelum mulai

- Backup database production, source, public/build, bootstrap/cache, dan foto master. Simpan di luar public_html. Catat hasil SHOW GRANTS dan versi PHP/database.
- Gunakan PHP 8.1+ dengan extension dan package aplikasi yang sudah terpasang (Laravel 10, Excel/PhpSpreadsheet, mbstring, fileinfo, PDO MySQL). SQL ini untuk MySQL 8, bukan MariaDB yang belum diuji.
- Hentikan traffic dan semua worker/scheduler yang dapat menulis selama pemasangan. Gunakan fasilitas maintenance hosting; jangan membuat route/web installer publik.
- Pilih database aplikasi yang benar di phpMyAdmin. Database harus memiliki users, migrations, mst_departments, mst_job_positions, user_job_positions. Jangan impor dump lokal penuh.
- Akun SQL harus dapat SELECT, INSERT, CREATE TABLE, ALTER TABLE, INDEX, REFERENCES, CREATE ROUTINE dan EXECUTE dalam database tersebut. Jika CREATE ROUTINE/EXECUTE tidak tersedia, hentikan pemasangan; jangan menghapus guard SQL.
- Semua resolved_production_user_id pada 00_preflight harus terisi. Akun memakai is_active=0 sebagai aktif. Resolusi NPK memakai satu akun aktif; bila duplikat, hanya tepat satu Administrator role_id=1 yang dapat dipilih. Tidak menyalin ID user lokal.
- Assignment departemen aktif sesuai aturan Warehouse harus sudah tersedia. Query ringkasan 00 bukan pengganti pemeriksaan tanggal assignment oleh aplikasi. Jangan menjalankan migration HR atau mengimpor pengguna untuk mengatasi akses.

## Urutan SQL

Import empat file database satu per satu. Hentikan pada error pertama. Tunggu SCHEMA_OK, kemudian MASTER_ACCESS_OK; simpan hasil postcheck.
01 mengulang preflight sebelum membuat sebelas tabel; seluruh tabel harus belum ada. Tabel dibuat tanpa FK terlebih dahulu, kemudian seluruh FK ditambahkan untuk menangani relasi melingkar. Foreign key checks tetap aktif.
02 memeriksa kolom dan tabel kosong, memetakan NPK, mengimpor master dan tiga verifier dalam transaksi, lalu menambah hanya migration Warehouse yang belum tercatat. Tidak menghapus ledger lama. Tabel kartu legacy dibiarkan kosong.
MySQL DDL melakukan implicit commit: kegagalan 01 dapat meninggalkan schema parsial. Jangan mengulang import atau menggunakan opsi continue/force. Catat error dan minta pemeriksaan schema; restore backup saat belum dibuka untuk transaksi merupakan jalan pemulihan yang aman.
Dua procedure bernama warehouse_release_20261022_schema dan warehouse_release_20261022_master tetap ada untuk bukti instalasi; jangan menambahkan routine tersebut ke menu aplikasi. Tidak ada DROP di SQL kiriman.

## Upload sesuai root Laravel

- Paket berisi folder root asli: app/, bootstrap/, config/, database/, public/, resources/, routes/. Salin file menurut path yang sama. docs/, README.md, INVENTORY.md dan manifest adalah bahan deploy; jangan diterbitkan sebagai halaman publik.
- File bootstrap/app.php sudah memuat bootstrap/warehouse-autoload.php. app/Http/Kernel.php sudah memuat alias middleware; AuthServiceProvider sudah memuat gates; RouteServiceProvider sudah memuat limiter; routes/web.php sudah memuat route auth Warehouse; resources/views/layout.blade.php sudah memuat menu Warehouse. Tidak ada snippet yang perlu ditempel.
- File bersama merupakan versi penuh dari baseline commit yang dicatat di source-manifest.json. Routes dan layout mengikuti commit tersebut dengan blok Warehouse terbaru; perubahan lokal HR dan Outstanding Material yang belum di-commit dikeluarkan dari paket. Bandingkan file bersama production dengan baseline sebelum menggantinya agar perubahan khusus server tetap terjaga. Jika berbeda, rekonsiliasikan hanya perubahan Warehouse terlebih dahulu.
- Model organisasi yang diperlukan tersedia di app/Models/ sesuai path asli. Baseline harus mendukung UserJobPosition::activeAt()/jobPosition() dan MstJobPosition::department(); jika effective_from tersedia, effective_until juga harus ada. Paket tidak mengimpor data atau migration HR.
- Jangan menimpa .env atau vendor. File yang tidak ada dalam paket tetap memakai versi production.
- Upload seluruh public/build beserta manifest.json secara bersamaan ke public aplikasi yang sebenarnya. Pertahankan aset production lama sampai smoke selesai. Paket membawa hasil build global agar manifest dan dependency cocok; sumber modul lain tidak perlu di-upload.
- Pastikan public/hot tidak terbawa/tersisa pada production; file itu mengarahkan Vite ke development server.
- Loader bootstrap yang sudah terpasang memakai require_once untuk enam file konsolidasi. Vendor dan Composer server tidak diganti.
- Composer production harus tetap menyediakan PSR-4 App ke app/. Periksa vendor/composer/autoload_real.php melalui File Manager: jika ada setClassMapAuthoritative(true), hentikan dan gunakan fasilitas autoload hosting. Untuk preload/opcache yang mengunci class lama gunakan reload PHP hosting. Loader enam file tidak mengganti seluruh Composer.
- Tidak perlu mengganti composer.json. vite.config.js disertakan di root untuk build berikutnya; hasil build siap upload sudah tersedia di public/build/.
- Migration asli tersedia di database/migrations/. Setelah import SQL, ledger sudah mencatat 23 migration Warehouse; jangan menjalankan migrate umum atau rollback sebagai bagian pemasangan ini.
- Pastikan shell production memuat Bootstrap JS/CSS, Bootstrap Icons, dan assets/vendor/chart.js/chart.umd.js sebelum stack scripts. Grafik Warehouse memakai window.Chart dari file tersebut; tanpa Chart tabel fallback tetap ada. Gunakan versi aset shell yang sudah dipakai production, jangan mengganti library global secara sepihak.

## Cache tanpa terminal

Saat maintenance masih aktif, backup lalu hapus hanya file cache yang ada melalui File Manager: bootstrap/cache/config.php, bootstrap/cache/routes-v7.php (atau routes-*.php versi hosting), bootstrap/cache/events.php, dan file hasil kompilasi *.php dalam storage/framework/views. Pertahankan .gitignore, direktori, services.php, packages.php, session, dan data aplikasi. Laravel akan membaca konfigurasi/routes baru dan mengompilasi view kembali.
Jika opcache.validate_timestamps dimatikan, lakukan restart/reload PHP lewat panel hosting sebelum smoke. Jangan membuat endpoint optimize:clear atau phpinfo publik.

## Foto dan penutupan

Saat ekspor ini semua photo_path master NULL, sehingga tidak ada foto yang perlu dipindahkan. Untuk unggahan berikutnya disk public harus writable dan public/storage harus terhubung ke storage/app/public. Gunakan fasilitas symlink hosting; jangan mengubah semua storage menjadi direktori publik.
Jalankan SMOKE-CHECKLIST.md, simpan hasil postcheck, lalu buka kembali traffic/worker. SQL production dan smoke production harus dilakukan operator; hasil rehearsal lokal bukan bukti production.
''')
write('docs/SMOKE-CHECKLIST.md', '''# Pemeriksaan setelah pemasangan

- [ ] 4 master item, 1 kategori, minimum/maksimum dan atribut benar; seluruh saldo nol.
- [ ] Tujuh tabel transaksi/sequence kosong, kartu legacy kosong, verifier NPK 5639/5439/5472 aktif, 23 migration Warehouse masing-masing tercatat sekali.
- [ ] Administrator, akun departemen resmi dan akses khusus existing memperoleh kemampuan sesuai source; akun di luar akses ditolak pada URL langsung.
- [ ] Semua menu dan halaman Warehouse terbuka tanpa class-not-found, Vite manifest missing, error CSRF atau HTTP 500.
- [ ] Stock In Baru menjadi pending tanpa saldo; verifier berhak memvalidasi dan hanya satu ledger terbentuk.
- [ ] Stock Out dan pengembalian Bekas atomik; stok negatif ditolak; submit ulang tidak menggandakan transaksi.
- [ ] Transfer internal melalui Stock In menghormati reservation; validasi, selisih dan cancel sesuai aturan existing.
- [ ] Adjustment/opening balance/reversal memerlukan verifier yang tepat.
- [ ] Riwayat, detail, dashboard, reporting dan Excel benar, termasuk saldo nol/no-data.
- [ ] Scanner item/NPK fisik Enter/Tab, keyboard, desktop dan mobile 360/390px diperiksa.
- [ ] Public/build dan manifest cocok; menu modul lain tetap tampil; foto baru bisa diunggah/ditampilkan.

Transaksi smoke production harus mengikuti prosedur operasional. Jangan menghapus ledger production setelah smoke; gunakan lingkungan staging atau reversal yang berwenang.
''')
write('docs/ROLLBACK.md', '''# Pemulihan

Pertahankan backup source, database, cache dan build production sebelum pemasangan.
Jika belum ada transaksi production dan pemasangan gagal, pertahankan maintenance dan pulihkan backup aplikasi/database melalui prosedur hosting. SQL schema bukan transaksi atomik dan tidak boleh diulang secara membabi buta.
Jika Warehouse sudah dipakai bertransaksi, hentikan penulisan, backup kondisi terkini dan gunakan forward fix setelah audit. Jangan menghapus transaksi atau menjalankan migration rollback/reset umum.
Saat mengembalikan aplikasi, pulihkan file bersama dari backup production dan build+manifest yang berpasangan. Jangan mengembalikan hanya satu aset ber-hash. Loader bootstrap dilepas hanya ketika kode/layout/routes Warehouse juga telah dinonaktifkan secara konsisten.
''')
write('docs/CONTEXT.md', '''# Warehouse Consumable

Istilah untuk penyerahan master dan pengosongan data operasional Warehouse.

## Bahasa domain

**Master consumable**:
Identitas dan atribut barang beserta kategori, foto, dan batas persediaan.

**Saldo stok**:
Jumlah persediaan serta rinciannya menurut lokasi dan kondisi Baru/Bekas.

**Transaksi Warehouse**:
Pergerakan stok, dokumen penerimaan, riwayat shipment, rincian pemakaian mesin, dan log verifikasi.

**Sequence Warehouse**:
Penghitung nomor dokumen penerimaan atau transaksi stok dalam satu tahun.

**Restricted verifier**:
Akun yang tercatat untuk memverifikasi operasi stok terbatas.
''')
# Exact shared shell hooks, rather than a full layout containing unrelated work.
shell_lines = [line.strip() for line in layout.splitlines() if any(token in line for token in ["@stack('styles')", "@stack('scripts')", "@yield('bodyClass'", "@yield('content')"])]
write('docs/SHELL-CONTRACT.md', '# Kontrak layout aktif\n\nLayout lengkap dalam resources/views/layout.blade.php sudah memuat hook berikut (styles dalam head, scripts sebelum penutup body):\n\n```blade\n' + '\n'.join(shell_lines) + '\n```\n\nJika file layout production memiliki perubahan khusus, pertahankan hook dan menu Warehouse saat rekonsiliasi. Jangan menambahkan hook dua kali. Pertahankan Bootstrap, jQuery/DataTables dan aset shell production yang sudah dipakai aplikasi.\n')

summary = {key: REPORT[key] for key in ['release', 'database', 'host', 'backup_sha256', 'backup_bytes', 'backup_restore_verified', 'rollback_rehearsal_passed', 'reset_rehearsal_passed', 'local_reset_committed', 'protected_tables_verified', 'all_schemas_and_auto_increments_preserved']}
summary['warehouse_before'] = {t: REPORT['before'][t]['count'] for t in TABLES}
summary['warehouse_after'] = {t: REPORT['after'][t]['count'] for t in TABLES}
summary['production_executed'] = False
write('docs/LOCAL-RESET-RESULT.json', json.dumps(summary, indent=2) + '\n')
write('source-manifest.json', json.dumps(SOURCES, indent=2) + '\n')
write('database/schema-manifest.json', json.dumps({'tables': TABLES, 'required_npks': sorted(required), 'migrations': [p.stem for p in migrations], 'foreign_key_count': len(foreign_keys), 'column_count': sum(map(len, DATA['columns'].values()))}, indent=2) + '\n')

def seal():
    lines = []
    for path in sorted(DEST.rglob('*')):
        if path.is_file() and path.name != 'SHA256SUMS.txt':
            lines.append(hashlib.sha256(path.read_bytes()).hexdigest() + '  ' + path.relative_to(DEST).as_posix())
    write('SHA256SUMS.txt', '\n'.join(lines) + '\n')
    archive = DEST.parent / (RELEASE + '.zip')
    with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as output:
        for path in sorted(DEST.rglob('*')):
            if path.is_file():
                output.write(path, path.relative_to(DEST).as_posix())
    print(f'Package: {DEST}\nFiles: {len(lines) + 1}\nZIP SHA256: {hashlib.sha256(archive.read_bytes()).hexdigest()}')

if __name__ == '__main__':
    seal()
