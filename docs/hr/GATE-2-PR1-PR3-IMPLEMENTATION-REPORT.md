# Gate 2 — PR 1 & PR 3 implementation report

Execution: 16–17 October 2026. Repository: `poyipoy/fastware_adsi_1`.

## Result and readiness

| Mission | Status | Actual outcome |
| --- | --- | --- |
| Mission 01 / PR 1 — Mapping Karyawan | **PARTIALLY COMPLETE** | Management authorization is preserved; read/manage boundaries, endpoint protection, reader UI and fail-closed queries are implemented. Positive subordinate visibility is **BLOCKED by B6**, including subordinate Working Experience. |
| Mission 02 / PR 3 — assessment information | **COMPLETE — implementation and focused verification** | Existing-data-only information for TC, Soft Skills and Additional is integrated into the five active forms, including the shared Division Head and yearly-history views. Full application/browser release QA remains NOT RUN. |
| Mission 03 — integration/verification | **PARTIALLY COMPLETE** | Focused HR, related regressions, syntax and isolated component browser checks pass. B6, existing formatting failures and unperformed full application QA prevent a production-ready conclusion. |

**Gate 2 is partially complete.** This report does not certify positive head-to-subordinate authorization or authorize release. The user explicitly permitted independently safe PR 1 work and completion of PR 3 when reporting hierarchy cannot be established.

## Baseline and scope protection

- Actual branch: `main`; HEAD: `6161d682e1eeaf81c9e83acad523af7c5b688b45` at inspection and final review.
- The initial worktree was already dirty: 17 modified tracked files and 15 untracked file entries. Existing work includes Outstanding Material, Warehouse, their tests/documents, two archived-at migrations, and the Gate 1/M2 documents. These are outside this mission. `routes/web.php` already contained unrelated Outstanding Material changes; no route edit was made here.
- Read project `AGENTS.md`, `CLAUDE.md`, the Laravel cognitive framework, canonical Gate 1, relevant source/routes/models/services/views/tests. Current source was used to establish active paths. Historical dated/copy controllers and views were not edited.
- No application schema migration, seeder, importer, reconciliation tool, master-data restoration or package/configuration change was created or run. `.env`, SQL dumps and Gate 1/M2 plans were not edited. No commit, staging, push or deployment was performed.

Final immutable input checks:

| Input | Bytes | SHA-256 | Result |
| --- | ---: | --- | --- |
| `dms_adasi_rev1.sql` | 2,069,117 | `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8` | MATCH |
| `dms_adasi_rev1 (12).sql` | 4,313,503 | `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db` | MATCH |
| `docs/hr/GATE-1-HR-REVISION-PR1-PR3-PLAN.md` | 91,586 | `3beca7d78cd627785f97ef188d94dc8906328996c41924233bbb29340858ae0b` | UNCHANGED |

The SQL dumps were not executed. Gate 1's static schema evidence was reused; this mission did not repeat historical reconciliation or change Q13–Q15.

## PR 1 implementation and authorization

`JobPositionAccessService` now separates `canManageMapping`, `canViewMapping`, `getReadableMappingScope` and `canViewEmployeeWorkingExperience`.

- Management uses the exact existing `HRMenuAccessGroup::JOB_POSITION` permission: its established three-name allowlist plus existing full-access IDs. No generic role grant, name replacement or silent revocation was introduced. HR must review any future discrepancy between this established allowlist and a broader role-based HR policy separately.
- Non-manager reader eligibility requires an active Section/Department/Division Head master position and a current effective assignment using the existing `activeAt()` scope. Future, expired, inactive, missing-start and inactive-master assignments do not grant the reader page. Multiple assignments are evaluated together, but do not authorize an unverified reporting edge.
- **B6 fail-closed behavior is explicit:** non-manager readable mapping scope is empty. No ancestor, unrelated branch, employee details, counts or filter options are returned. Head pages show that reporting relationships still require HR validation. Approval routes and level 0 are not interpreted as reporting access.
- Index, employee-position AJAX and Working Experience AJAX enforce view and employee scope. Mapping create/update/toggle/deactivate, Working Experience mutations, import and template remain management-only. Form Request authorization rejects prohibited requests before presence/file validation. Existing assignment service/audit behavior is preserved.
- Mapping view hides assign/edit/delete/import/template/Working Experience mutation controls for readers and contains a Detail/read-only path. AJAX requests are aborted/versioned; stale responses and callbacks after modal close cannot populate the previous employee's detail area.
- Mapping navigation is separated from Master Job Position navigation; the latter retains its original full-access guard. Ordinary staff receive no mapping access.

### Exact B6 blocker

Observed evidence: `MstPositionApproval` and `2026_06_30_000002_create_mst_position_approvals_table.php` define **approval routes**, not a validated reporting relation. The migration documents levels 1–3; current competency scope also uses level 0 and organizational/name fallbacks. Gate 1 explicitly prohibits reusing that scope as recursive employee reporting authority. No independent reporting-parent relation or approved equivalence was established by inspected evidence.

**Owner: HR Organization Owner + Authorization Owner.** Required evidence: authoritative reporting edges and level-0 semantics, active/date-effective rules, direct/indirect closure, multiple simultaneous assignments, exclusion of actor/ancestors/unrelated branches, and handling of ambiguous/cyclic/missing links. After that decision, implement the bounded reporting scope and positive hierarchy tests. Do not change Sales or active assignments to manufacture the evidence.

Consequently, positive direct/indirect Section/Department/Division subordinate visibility, a scoped union across branches and head access to subordinate Working Experience are **NOT IMPLEMENTED**. Current tests prove denial and absence of leakage; they do not stand in for those positive acceptance tests.

## PR 3 implementation and UI

- Added shared competency partial and plain JavaScript asset, reused by `sc_penilaian`, `edit_penilaian`, `dept_penilaian`, `view_penilaian` and `privew_penilaian`. Division Head uses the existing department form; yearly history uses the existing read-only form.
- Each existing competency header gets an **Informasi Kompetensi** trigger using competency type + master ID. The native dialog shows name, category, specific description, existing standard score and the separate category rubric 1–4. A compact disclosure also lists individual masters, including same-name records with distinct IDs.
- Missing descriptions show exactly **Deskripsi kompetensi belum tersedia.** No master description, score guidance or training history is invented or restored.
- Description/rubric rendering uses DOM `textContent`; affected header/attribute text is escaped. Removed four duplicated assessment-modal raw-HTML renderers in favor of the shared safe renderer. Existing summary trigger/target remains supported.
- Native disclosure/dialog controls have accessible names, expanded/controls relationships, dialog labelling, keyboard operation and focus restoration. Scoped styles support narrow screens; no framework/dependency or global redesign was added.
- Metadata requests are aborted/versioned when position changes, old content is cleared, late responses are ignored, and errors expose a retry state. The Section Head table also ignores responses for a previously selected position.

### Endpoint and schema contracts

- `TcController::fetchDetails` and `fetchDetails2` validate/resolve the position and enforce the existing competency permission and active master-position scope. Root JSON keys `tcs`, `softSkills`, `additionals` remain compatible with current callers. Explicit columns use `deskripsi_tc`, `deskripsi_sk`, `deskripsi_ad`; rubric uses `tc_poin_kategoris.deskripsi_1..4`. No `deskripsi_level_1..4` query or migration replay is required.
- `getJobPositionData`, `getJobPositionDataEdit`, `getNilaiDataEdit`, `getDataTrs`, preview/view and yearly history now enforce role/position read authorization. They reuse existing competency scope, independently of the intentionally blocked PR 1 reporting scope.
- Employee list queries require effective active assignments. Shared-position employee-section filtering is batched instead of doing an employee/section lookup per row. Read dates use the application date as bound parameters.
- Assessment detail reads validate and scope the year; existing forms pass their selected assessment/history year. Default for callers omitting year is the current year. This intentionally prevents the supporting reads from mixing periods. `getJobPositionData`'s existing `has_penilaian` contract was preserved.
- `getDataTrs` exposes only required user fields (`id`, NPK, name, section) to authorized application callers, instead of serializing unrelated email/token fields. Employee result arrays were removed from diagnostic read logging. No personal records appear in this report.
- Ten mutation methods were compared with HEAD using PHP tokens and normalized newlines: `savePenilaian`, `updateTrs`, `updateTrs2`, `updateTrs3`, `updateCatatan`, `kirimSC`, `kirimDept`, `kirimDiv`, `submitDraft`, `correctPenilaian` are **UNCHANGED**. Input names, score calculations, submission payloads, approval transitions and Sales routing were not rewritten.

## Verification environment and results

### Isolation

Read-only PDO preflight established a loopback MySQL testing connection and verified `SELECT DATABASE()` as `fastware_adsi_1_testing`. No production/development database test was run. Tests refuse non-testing environment, another database, remote host, URL/socket overrides or nonempty password.

New database-backed tests create synthetic fixtures only in a newly created random database named `fastware_adsi_1_hr_gate2_<20 hex>_testing`, switch the connection and verify the selected name. They create only the minimal fixture schema, use a private temporary compiled-view directory and drop only their exact owned database after name/current-connection checks. No `RefreshDatabase`, migrations, seeders, dump import, `migrate:fresh` or `db:wipe` is used. This avoids altering the existing partial testing schema. A final read-only metadata check found **0 remaining owned fixture databases**.

Initial TEMPORARY-table fixtures exposed MySQL error 1137 in the existing UNION query. The harness was corrected to the isolated disposable schema above. Other initial fixture failures (compiled-view location, master/category fixtures and snapshot casts) were corrected without weakening production authorization or altering scoring/approval logic.

### Automated tests executed

Commands were run serially as `php vendor/bin/phpunit <path>`. Final HR rerun after the last Blade change: **24 tests / 219 assertions, PASS**. Related regression runs below were also executed in this task. Combined final results: **95 tests / 462 assertions, PASS**; this is a selected set, not the full suite.

| Path (after `tests/`) | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| `Feature/HR` | 24 | 219 | PASS |
| `Feature/WorkingExperienceImportUploadTest.php` | 1 | 4 | PASS |
| `Feature/TrainingHistoryEndpointTest.php` | 1 | 12 | PASS |
| `Unit/HRRoleAccessServiceTest.php` | 3 | 12 | PASS |
| `Unit/HRMenuAccessGroupTest.php` | 11 | 74 | PASS |
| `Unit/HRMenuServiceTest.php` | 4 | 10 | PASS |
| `Unit/JobPositionAccessServiceTest.php` | 3 | 4 | PASS |
| `Unit/EmployeeIdentityFormatterTest.php` | 7 | 8 | PASS |
| `Unit/WorkingExperienceValidationServiceTest.php` | 5 | 5 | PASS |
| `Unit/TrainingParticipantServiceTest.php` | 3 | 3 | PASS |
| `Unit/TrainingHistoryPresentationServiceTest.php` | 2 | 6 | PASS |
| `Unit/TrainingStatusSummaryUiTest.php` | 2 | 18 | PASS |
| `Unit/TrainingEvaluationServiceTest.php` | 8 | 17 | PASS |
| `Unit/TrainingEvaluationUiTest.php` | 4 | 20 | PASS |
| `Unit/UpdateTrainingEvaluationRequestTest.php` | 2 | 2 | PASS |
| `Unit/TrainingFollowUpTableUiTest.php` | 4 | 17 | PASS |
| `Unit/TrainingHrgaDashboardUiTest.php` | 2 | 13 | PASS |
| `Unit/TrainingWorkbookStructureTest.php` | 3 | 11 | PASS |
| `Unit/UpdateTrainingFollowUpRequestTest.php` | 6 | 7 | PASS |

New tests exercise actual routes and authorization, manager assignment/audit/Working Experience CRUD, all head denial boundaries, invalid effective assignments, multiple assignments without invented reporting scope, staff/guest denial, manual and spoofed mutation requests, import/template denial, inaccessible employee AJAX and empty search/filter/pagination scope. PR 3 tests exercise all three metadata types without tier columns, unauthorized/outside-scope reads, year isolation, unchanged read snapshots, real synthetic score submission and existing non-Sales/Sales approval paths. All five active templates and mapping manager page render; extracted inline JavaScript passes `node --check`.

### Browser checks actually run

Executed `tests/Browser/HrCompetencyInformation.browser.js` in an isolated blank Chromium page with the actual production component script and partial CSS, synthetic DOM/metadata and mocked fetch. **19 checks PASS**: type+ID identity, same-name definitions, empty-description fallback, ordered rubric, malicious text/attribute escaping, matching header dialog, focus restoration, no form submission, unique IDs, stale-response rejection, retry and unchanged fixture score/year values.

Additional native browser actions: Enter opens the header dialog and focuses Close; Escape closes it and returns focus with `aria-expanded=false`. Earlier disclosure Enter/Space/Escape checks also passed. Emulated **320 px** viewport: dialog width **288 px**, inside viewport, no internal horizontal overflow, header trigger **44 px** tall. At **1280 px**, dialog width **512 px** and document fits. The isolated page was closed afterward. These are component checks, not authenticated application/browser QA.

### Static/style checks

- `php -l`: PASS for six changed production PHP files, two new HR feature tests, new isolated test harness and fixture Blade (10 files).
- `node --check public/assets/js/hr/competency-information.js` and `node --check tests/Browser/HrCompetencyInformation.browser.js`: PASS.
- Scoped `git diff --check`: PASS.
- `php vendor/bin/pint --test` on `UserJobPositionController`, both changed Working Experience requests and the three new PHP test/harness files: **PASS, 6 files**.
- Pint on `JobPositionAccessService`, `TcController`, `PenilaianTCController`: **FAIL, 3 files**. Baseline originals already fail formatting; baseline comparison was performed during implementation. No blanket formatter was applied to those legacy files because it would churn unrelated methods. Syntax/tests above pass; the style gate is not reported green.

## Remaining limitations and release requirements

1. **B6 / HR Organization + Authorization Owners:** positive reporting scope and subordinate Working Experience are blocked as detailed above. Do not release PR 1 as complete. Add positive direct/indirect/multiple-assignment/excluded-ancestor/filter tests after the reporting rule is approved.
2. **DBA/runtime owner:** actual deployment schema/ledger compatibility is NOT VERIFIED. Fixtures prove the selected description/rubric contract without tier columns; they do not certify production schema. No runtime migration/repair was attempted.
3. **QA/accessibility owner:** full authenticated application rendering with the real global layout, Bootstrap/Select2 integrations, manager import/template downloads, screen-reader announcement, zoom/contrast and representative devices remains NOT RUN. The test layout is deliberately minimal to isolate relevant views from unrelated global database composers.
4. **Legacy behavior:** the score grid's pre-existing name-based grouping and selector behavior were retained. The new information catalogue preserves distinct type/master IDs; this task does not redesign grid scoring identity or historical edit/year-selection behavior. Existing approval/write authorization and lock enforcement were not certified exhaustively by the selected regression tests.
5. The separate master-competency summary page still has its legacy renderer; it was inspected for payload compatibility and not redesigned. The new assessment component's XSS checks are not a global application XSS audit.
6. Full PHPUnit suite, application `route:list`, application view-cache generation and production Vite build were NOT RUN. Routes/Vite inputs were not changed; the new asset is a directly served public script. Avoided rebuilding unrelated dirty frontend assets. No staging/production execution or manual employee-data verification was performed.
7. Three legacy source formatting failures remain visible as reported. No dependency installation, broad refactor or unrelated cleanup was performed to hide them.

## Exact files changed by this mission

Modified existing files (13):

- `app/Services/HR/JobPositionAccessService.php`
- `app/Http/Controllers/UserJobPositionController.php`
- `app/Http/Controllers/TcController.php`
- `app/Http/Controllers/PenilaianTCController.php`
- `app/Http/Requests/StoreWorkingExperienceRequest.php`
- `app/Http/Requests/ImportWorkingExperienceRequest.php`
- `resources/views/layout.blade.php`
- `resources/views/user_job_position/index.blade.php`
- `resources/views/tc_penilaian/sc_penilaian.blade.php`
- `resources/views/tc_penilaian/edit_penilaian.blade.php`
- `resources/views/tc_penilaian/dept_penilaian.blade.php`
- `resources/views/tc_penilaian/view_penilaian.blade.php`
- `resources/views/tc_penilaian/privew_penilaian.blade.php`

New files (8, including this report):

- `resources/views/tc_penilaian/partials/competency_information.blade.php`
- `public/assets/js/hr/competency-information.js`
- `tests/Feature/HR/EmployeeMappingAuthorizationTest.php`
- `tests/Feature/HR/CompetencyInformationTest.php`
- `tests/Support/HrIsolatedDatabaseTestCase.php`
- `tests/Fixtures/hr/views/layout.blade.php`
- `tests/Browser/HrCompetencyInformation.browser.js`
- `docs/hr/GATE-2-PR1-PR3-IMPLEMENTATION-REPORT.md`

## Protected work and stop gate

PR 2 historical assessments/training, cleansing, manifests and migration tooling remain untouched. No SQL dump, credential, active production assignment, historical employee record or master data was written. Q13–Q15 data approval counters remain zero; synthetic tests are not historical write approval. PR 4 Sales organization, assignment, approval definitions and routing code remain untouched. The Sales regression fixture exists only in the disposable test database.

**GATE 2 — PARTIALLY COMPLETE: PR 3 IMPLEMENTED; PR 1 SUBORDINATE VISIBILITY BLOCKED BY B6 — VERIFICATION STATUS REPORTED ABOVE**

**STOP — PENDING USER REVIEW.**
