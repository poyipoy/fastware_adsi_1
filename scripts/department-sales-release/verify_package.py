"""Verify package sources, paths, checksums, ZIP parity, and local dependency inventory."""
from pathlib import Path
import hashlib
import json
import re
import subprocess
import zipfile
from build_package import ROOT, RELEASE, DEST, EXCLUDED_ROUTES, standalone_sql


def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()


def verify():
    expected = {}
    for line in (DEST / "SHA256SUMS.txt").read_text(encoding="utf-8").splitlines():
        checksum, relative = line.split("  ", 1)
        expected[relative] = checksum
        assert sha(DEST / relative) == checksum, f"Checksum mismatch: {relative}"
    files = {p.relative_to(DEST).as_posix(): p for p in DEST.rglob("*") if p.is_file()}
    assert set(files) == set(expected) | {"SHA256SUMS.txt"}, "Unexpected package files"
    for relative in files:
        assert not any(part in {"vendor", "node_modules", "storage", ".git", "tests"} for part in Path(relative).parts), relative
        assert Path(relative).suffix in {".php", ".js", ".css", ".md", ".txt", ".json", ".sql"}, relative
        assert "OutstandingMaterial" not in relative and "/warehouse/" not in relative.lower(), relative
    manifest = json.loads((DEST / "docs/source-manifest.json").read_text(encoding="utf-8"))
    for row in manifest:
        path = DEST / row["path"]
        assert sha(path) == row["package_sha256"], row["path"]
        if row["origin"] == "live-source":
            assert sha(ROOT / row["path"]) == row["source_sha256"] == row["package_sha256"], row["path"]
        elif row["path"] == "routes/web.php":
            assert sha(ROOT / row["path"]) == row["source_sha256"], "Routes source changed after package build"
    routes = (DEST / "routes/web.php").read_text(encoding="utf-8")
    for name in EXCLUDED_ROUTES:
        assert f"->name('{name}')" not in routes, name
    assert routes.count("name('mst-department.')") == 1
    standalone = (DEST / "database/sql/sales-region-split/split_sales_regions_only.sql").read_bytes()
    assert standalone == standalone_sql(), "Standalone differs from its reviewed SQL components"
    assert not re.search(rb"\b(?:CREATE|ALTER|DROP|TRUNCATE)\s+TABLE\b", standalone, re.I), "Standalone must not change application tables"
    php_count = js_count = 0
    dependencies = set()
    for relative, path in files.items():
        if path.suffix == ".php" and "/views/" not in relative:
            output = subprocess.run(["php", "-l", str(path)], capture_output=True, text=True)
            assert output.returncode == 0, output.stdout + output.stderr
            php_count += 1
            imported_classes = set(re.findall(r"^use (App\\[A-Za-z0-9_\\]+)(?:\s+as\s+\w+)?;", path.read_text(encoding="utf-8"), re.M))
            if relative != 'routes/web.php':
                imported_classes.update(re.findall(r"\\(App\\[A-Za-z0-9_\\]+)(?=::|[\s;,()])", path.read_text(encoding="utf-8")))
            for imported in imported_classes:
                dependency = "app/" + imported[4:].replace("\\", "/") + ".php"
                assert (ROOT / dependency).is_file(), f"Unresolved source dependency: {dependency}"
                if dependency not in files and relative != 'routes/web.php':
                    dependencies.add(dependency)
        elif path.suffix == ".js":
            output = subprocess.run(["node", "--check", str(path)], capture_output=True, text=True)
            assert output.returncode == 0, output.stderr
            js_count += 1
        if "/views/" in relative:
            for view in re.findall(r"@(?:include|extends)\(['\"]([A-Za-z0-9_.-]+)['\"]", path.read_text(encoding="utf-8")):
                dependency = "resources/views/" + view.replace(".", "/") + ".blade.php"
                assert (ROOT / dependency).is_file(), f"Missing view dependency: {dependency}"
                if "partials" in dependency or "mst_department" in dependency:
                    assert dependency in files, f"Missing packaged partial: {dependency}"
    archive = DEST.parent / (RELEASE + ".zip")
    with zipfile.ZipFile(archive) as bundle:
        assert bundle.testzip() is None
        assert set(bundle.namelist()) == set(files), "ZIP must contain root files directly"
        for relative in files:
            assert bundle.read(relative) == files[relative].read_bytes(), f"ZIP mismatch: {relative}"
    assert sha(archive) == archive.with_suffix(".zip.sha256").read_text(encoding="utf-8").split()[0]
    print(json.dumps({"files_verified": len(files), "php_linted": php_count, "js_checked": js_count,
                      "excluded_unrelated_routes": len(EXCLUDED_ROUTES), "existing_application_dependencies_required": sorted(dependencies),
                      "zip_sha256": sha(archive)}, indent=2))


if __name__ == "__main__":
    verify()
