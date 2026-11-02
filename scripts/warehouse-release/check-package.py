"""Verify payload integrity and re-seal only the already-generated release."""
from pathlib import Path
import hashlib
import json
import re
import subprocess
import zipfile

root = Path(__file__).resolve().parents[2]
package = root / 'deploy-warehouse/WAREHOUSE-20261022'
checks = {}
manifest = json.loads((package / 'source-manifest.json').read_text())
for target, value in manifest.items():
    original = subprocess.check_output(['git', 'show', value['basis_revision'] + ':' + value['source']], cwd=root) if value.get('basis_revision') else (root / value['source']).read_bytes()
    assert hashlib.sha256(original).hexdigest() == value.get('source_sha256', value['sha256']), value['source']
    assert hashlib.sha256((package / target).read_bytes()).hexdigest() == value['sha256'], target
checks['source_files_matched'] = len(manifest)

vite = json.loads((package / 'public/build/manifest.json').read_text())
for key, value in vite.items():
    assert (package / 'public/build' / value['file']).is_file(), key
    for field in ['css', 'assets']:
        for asset in value.get(field, []):
            assert (package / 'public/build' / asset).is_file(), asset
    for field in ['imports', 'dynamicImports']:
        for dependency in value.get(field, []):
            assert dependency in vite, dependency
for path in (package / 'resources/views/warehouse').rglob('*.blade.php'):
    for entry in re.findall(r'@vite\([\'\"]([^\'\"]+)', path.read_text()):
        assert entry in vite, entry
checks['vite_manifest_entries_verified'] = len(vite)

php_files = sorted(p for area in ['app', 'bootstrap', 'config', 'database/migrations', 'resources', 'routes'] for p in (package / area).rglob('*.php'))
for path in php_files:
    process = subprocess.run(['php', '-l', str(path)], capture_output=True, text=True)
    assert process.returncode == 0, str(path) + process.stdout + process.stderr
checks['php_files_linted'] = len(php_files)
js_files = sorted((package / 'resources/js/warehouse').glob('*.js'))
for path in js_files:
    process = subprocess.run(['node', '--check', str(path)], capture_output=True, text=True)
    assert process.returncode == 0, str(path) + process.stderr
checks['javascript_files_syntax_checked'] = len(js_files)

sql_files = sorted((package / 'database').glob('*.sql'))
for path in sql_files:
    data = path.read_text()
    assert not re.search(r'(?im)^\s*(DROP|DELETE|TRUNCATE|REPLACE|UPDATE)\b', data), path
    assert not re.search(r'FOREIGN_KEY_CHECKS\s*=\s*0', data, re.I), path
    for table in ['trs_wh_stock_transactions', 'trs_wh_stock_ins', 'log_wh_verifications', 'mst_wh_user_cards']:
        assert not re.search(r'INSERT\s+INTO\s+`?' + table, data, re.I), (path, table)
checks['delivery_sql_static_checks'] = True
for path in package.rglob('*'):
    assert path.name not in ['.env', 'auth.json', 'database-before-reset.sql', 'export.json', 'public/hot'], path
    assert not path.relative_to(package).as_posix().startswith(('vendor/', 'runtime/vendor/')), path
for removed in ['runtime', 'shared-integration', 'support']:
    assert not (package / removed).exists(), removed
assert "require_once __DIR__.'/warehouse-autoload.php';" in (package / 'bootstrap/app.php').read_text()
assert 'archiveMaterials' not in (package / 'routes/web.php').read_text()
assert 'canViewMapping' not in (package / 'resources/views/layout.blade.php').read_text()
root_routes = (root / 'routes/web.php').read_text(encoding='utf-8-sig')
package_routes = (package / 'routes/web.php').read_text()
route_start = "    Route::prefix('warehouse')->name('warehouse.')->group(function () {"
def route_block(contents):
    start = contents.index(route_start)
    end = contents.index('\n    });\n});', start) + len('\n    });')
    return contents[start:end]
assert route_block(root_routes) == route_block(package_routes)
assert package_routes.count(route_start) == 1
root_layout = (root / 'resources/views/layout.blade.php').read_text(encoding='utf-8-sig')
package_layout = (package / 'resources/views/layout.blade.php').read_text()
def menu_block(contents):
    start = contents.rfind('                        @php', 0, contents.index('$warehouseMenuVisible'))
    end = contents.index('                        <!-- Dropdown Sales -->', start)
    return contents[start:end]
assert menu_block(root_layout) == menu_block(package_layout)
checks['warehouse_routes_and_menu_match_active_source'] = True
checks['root_layout_verified'] = True
checks['private_artifacts_excluded'] = True
(package / 'docs/PACKAGE-VERIFICATION.json').write_text(json.dumps(checks, indent=2) + '\n', encoding='utf-8')

inventory = ['# Inventori paket', '', 'Struktur mengikuti root Laravel. File bersama berisi integrasi lengkap Warehouse; periksa kecocokan baseline production sebelum menggantinya.', '']
for area in ['app', 'bootstrap', 'config', 'database', 'public', 'resources', 'routes', 'docs']:
    paths = sorted(p.relative_to(package).as_posix() for p in (package / area).rglob('*') if p.is_file())
    inventory += [f'## {area} — {len(paths)} file', '', '```text', *paths, '```', '']
(package / 'INVENTORY.md').write_text('\n'.join(inventory), encoding='utf-8')

lines = []
for path in sorted(package.rglob('*')):
    if path.is_file() and path.name != 'SHA256SUMS.txt':
        lines.append(hashlib.sha256(path.read_bytes()).hexdigest() + '  ' + path.relative_to(package).as_posix())
(package / 'SHA256SUMS.txt').write_text('\n'.join(lines) + '\n', encoding='utf-8')
archive = package.parent / (package.name + '.zip')
with zipfile.ZipFile(archive, 'w', zipfile.ZIP_DEFLATED) as output:
    for path in sorted(package.rglob('*')):
        if path.is_file():
            output.write(path, path.relative_to(package).as_posix())
with zipfile.ZipFile(archive) as output:
    assert output.testzip() is None
    assert len(output.namelist()) == len(lines) + 1
    for line in lines:
        expected, relative = line.split('  ', 1)
        assert hashlib.sha256(output.read(relative)).hexdigest() == expected, relative
    assert not any(name.startswith(('runtime/', 'shared-integration/', 'support/', package.name + '/')) for name in output.namelist())
digest = hashlib.sha256(archive.read_bytes()).hexdigest()
(archive.parent / (archive.name + '.sha256')).write_text(digest + '  ' + archive.name + '\n', encoding='utf-8')
print(json.dumps({**checks, 'package_files': len(lines) + 1, 'zip_bytes': archive.stat().st_size, 'zip_sha256': digest}, indent=2))
