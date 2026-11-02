"""Build the approved update from live sources without modifying the application."""
from pathlib import Path
import hashlib
import json
import re
import shutil
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[2]
RELEASE = "SALES-DEPARTMENT-20261026"
DEST = ROOT / "deploy-department-sales" / RELEASE
FILES = [
    "app/Console/Commands/SplitSalesRegionsCommand.php",
    "app/Http/Controllers/MstDepartmentController.php",
    "app/Http/Controllers/MstJobPositionController.php",
    "app/Http/Controllers/UserJobPositionController.php",
    "app/Http/Controllers/PenilaianTCController.php",
    "app/Http/Controllers/TcController.php",
    "app/Http/Controllers/magang/StrukturOrganisasiController.php",
    "app/Services/HR/DepartmentStructureService.php",
    "app/Services/HR/SalesRegionSplitService.php",
    "app/Services/HR/JobPositionAccessService.php",
    "app/Services/HR/HRRoleAccessService.php",
    "app/Services/HR/EmployeeIdentityFormatter.php",
    "app/Services/HR/WorkingExperienceValidationService.php",
    "app/Services/Competency/CompetencyAssessmentService.php",
    "app/Services/HR/TcpdDashboardAccessService.php",
    "app/Services/Dashboard/TcpdDashboardService.php",
    "app/Services/Dashboard/CompetencyDashboardService.php",
    "app/Services/KnowledgeManagement/KmOrganizationAssignmentService.php",
    "app/Services/KnowledgeManagement/KmTargetingService.php",
    "app/Enums/HRMenuAccessGroup.php",
    "app/Enums/TcpdDepartment.php",
    "app/Enums/TrainingStatus.php",
    "app/Http/Requests/ImportWorkingExperienceRequest.php",
    "app/Http/Requests/StoreWorkingExperienceRequest.php",
    "app/Http/Requests/UpdateWorkingExperienceRequest.php",
    "app/Imports/WorkingExperienceImport.php",
    "app/Exports/WorkingExperienceTemplateExport.php",
    "app/Models/MstDepartment.php",
    "app/Models/MstSection.php",
    "app/Models/MstJobPosition.php",
    "app/Models/MstPositionApproval.php",
    "app/Models/UserJobPosition.php",
    "app/Models/UserJobAccess.php",
    "app/Models/WorkingExperience.php",
    "app/Models/TrsPenilaianTc.php",
    "app/Models/TcPeopleDevelopment.php",
    "app/Models/DetailTcPenilaian.php",
    "app/Models/MstTc.php",
    "app/Models/MstSoftSkill.php",
    "app/Models/MstAdditionals.php",
    "app/Models/PoinKategori.php",
    "public/assets/css/hr/departments.css",
    "public/assets/js/hr/assessment-summary.js",
    "resources/views/layout.blade.php",
    "resources/views/mst_job_position/form.blade.php",
    "resources/views/mst_job_position/index.blade.php",
    "resources/views/user_job_position/index.blade.php",
    "resources/views/tc_penilaian/sc_penilaian.blade.php",
    "resources/views/tc_penilaian/dept_penilaian.blade.php",
    "resources/views/tc_penilaian/edit_penilaian.blade.php",
    "resources/views/tc_penilaian/view_penilaian.blade.php",
    "resources/views/tc_penilaian/privew_penilaian.blade.php",
    "resources/views/tc_penilaian/partials/assessment_summary.blade.php",
    "routes/web.php",
]
FILES += [str(p.relative_to(ROOT)).replace("\\", "/") for p in sorted((ROOT / "resources/views/mst_department").glob("*.blade.php"))]
EXCLUDED_ROUTES = ["invoice.archive", "materials.archive", "materials.restore", "invoice.archived.index",
                   "invoice.archived.data", "invoice.archived.materials.scoped", "invoice.restore"]


def digest(data):
    return hashlib.sha256(data).hexdigest()


def standalone_sql():
    directory = Path(__file__).parent / "sql"
    validation = (directory / "00_preflight.sql").read_text(encoding="utf-8")
    marker = "CALL sales_dept_20261026_validate(FALSE,@sales_release_completed,@sales_release_actor);"
    assert validation.count(marker) == 1
    validation = validation[validation.index("DELIMITER $$"):validation.index(marker)]
    validation = validation.replace("Impor 01_prerequisites.sql sebelum 02.",
                                    "Tabel audit belum tersedia; split-only dibatalkan tanpa perubahan tabel.")
    apply = (directory / "02_split_sales_regions.sql").read_text(encoding="utf-8")
    apply = apply[apply.index("DELIMITER $$"):]
    postcheck = (directory / "03_postcheck.sql").read_text(encoding="utf-8")
    postcheck = postcheck.replace("DROP PROCEDURE IF EXISTS sales_dept_20261026_prerequisites;\n", "")
    content = """-- SPLIT SALES SAJA: impor SATU file ini setelah backup dan pembatasan akses aplikasi.
-- Tidak memerlukan file 00-03. Jangan mengimpor kedua jalur bersamaan.
-- Tabel audit/semua prasyarat HR wajib SUDAH ADA. Tidak membuat/ALTER tabel aplikasi.
-- Helper routine dan tabel TEMPORARY hanya dipakai saat eksekusi; helper dibersihkan setelah berhasil.
-- Jika ada error, hentikan. Data cutover di-rollback; helper dapat tersisa jika client berhenti pada error.
-- Pilih database HR server tujuan di phpMyAdmin. Tidak ada USE atau ID master/user lokal.
""" + validation + "\nCALL sales_dept_20261026_validate(TRUE,@sales_release_completed,@sales_release_actor);\n" \
        + "SELECT IF(@sales_release_completed,'COMPLETED_NOOP','READY') status,@sales_release_actor resolved_actor_id;\n" \
        + apply + "\n" + postcheck
    content = content.replace("sales_dept_20261026_validate", "sales_dept_20261026_only_validate")
    content = content.replace("sales_dept_20261026_apply", "sales_dept_20261026_only_apply")
    content = content.replace("tmp_sales_dept_20261026_map", "tmp_sales_dept_20261026_only_map")
    assert "01_prerequisites.sql" not in content
    assert not re.search(r"\b(?:CREATE|ALTER|DROP|TRUNCATE)\s+TABLE\b", content, re.I)
    return content.encode("utf-8")


def build():
    DEST.mkdir(parents=True, exist_ok=True)
    origins = []
    for relative in FILES:
        source = ROOT / relative
        payload = source.read_bytes()
        origin = "live-source"
        if relative == "routes/web.php":
            content = payload.decode("utf-8")
            for name in EXCLUDED_ROUTES:
                pattern = rf"^.*->name\('{re.escape(name)}'\);[^\n]*\n"
                content, count = re.subn(pattern, "", content, flags=re.M)
                if count != 1:
                    raise RuntimeError(f"Expected exactly one unrelated route to exclude: {name}")
            payload = content.encode("utf-8")
            origin = "live-source; excludes seven Outstanding Material archive routes only"
        target = DEST / relative
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_bytes(payload)
        origins.append({"path": relative, "origin": origin, "source_sha256": digest(source.read_bytes()), "package_sha256": digest(payload)})
    for directory, target_directory in [("sql", "database/sql/sales-region-split"), ("docs", "docs")]:
        for source in sorted((Path(__file__).parent / directory).glob("*")):
            if not source.is_file():
                continue
            relative = target_directory + "/" + source.name
            target = DEST / relative
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(source, target)
            origins.append({"path": relative, "origin": str(source.relative_to(ROOT)).replace("\\", "/"), "package_sha256": digest(target.read_bytes())})
    relative = "database/sql/sales-region-split/split_sales_regions_only.sql"
    payload = standalone_sql()
    (DEST / relative).write_bytes(payload)
    origins.append({"path": relative, "origin": "generated from 00 validation + 02 transaction + 03 postcheck; existing audit required; no 01", "package_sha256": digest(payload)})
    guide = "docs/hr/MASTER-DEPARTMENT-OPERATING-GUIDE.md"
    (DEST / "docs/FEATURE-GUIDE.md").write_bytes((ROOT / guide).read_bytes())
    origins.append({"path": "docs/FEATURE-GUIDE.md", "origin": guide, "package_sha256": digest((ROOT / guide).read_bytes())})
    (DEST / "manifest-files.txt").write_text("\n".join(f"{x['path']} | {x['origin']}" for x in origins) + "\n", encoding="utf-8")
    (DEST / "docs/source-manifest.json").write_text(json.dumps(origins, indent=2) + "\n", encoding="utf-8")
    expected = {x["path"] for x in origins} | {"manifest-files.txt", "docs/source-manifest.json", "SHA256SUMS.txt"}
    actual = {str(p.relative_to(DEST)).replace("\\", "/") for p in DEST.rglob("*") if p.is_file()}
    if actual - expected:
        raise RuntimeError(f"Unexpected stale package files: {sorted(actual - expected)}")
    checksums = []
    for source in sorted(DEST.rglob("*")):
        if source.is_file() and source.name != "SHA256SUMS.txt":
            checksums.append(f"{digest(source.read_bytes())}  {source.relative_to(DEST).as_posix()}")
    (DEST / "SHA256SUMS.txt").write_text("\n".join(checksums) + "\n", encoding="utf-8")
    archive = DEST.parent / (RELEASE + ".zip")
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED) as output:
        for source in sorted(DEST.rglob("*")):
            if source.is_file():
                output.write(source, source.relative_to(DEST).as_posix())
    archive.with_suffix(".zip.sha256").write_text(digest(archive.read_bytes()) + "  " + archive.name + "\n", encoding="utf-8")
    print(json.dumps({"folder": str(DEST), "zip": str(archive), "files": len(checksums) + 1, "zip_sha256": digest(archive.read_bytes())}))


if __name__ == "__main__":
    if sys.argv[1:] == ["--print-standalone-sql"]:
        sys.stdout.buffer.write(standalone_sql())
    else:
        build()
