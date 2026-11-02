# Gate 2 — PR 1 B6 hierarchy hotfix

Date: 17 October 2026. Repository: `poyipoy/fastware_adsi_1`.

## Outcome and authority

**PR 1 B6 implementation COMPLETE; focused automated verification PASS. Production readiness is not certified.** The user's **B6 Option A** authorizes `mst_position_approvals` as both approval and reporting source. This supersedes the reporting-source blocker in the previous Gate 2 report. Gate 1 and the previous execution report remain unchanged as historical records; Q13–Q15 and PR 2 data approvals are not reopened.

Section, Department and Division Heads now see verified direct/transitive subordinate mappings and subordinate Working Experience read-only. Management retains the existing allowlist/full-access behavior. Invalid graph branches fail closed independently of valid branches.

## Baseline and preservation

- Branch `main`; HEAD `6161d682e1eeaf81c9e83acad523af7c5b688b45` at start and final inspection.
- Existing dirty worktree included the preceding PR 1/PR 3 implementation, Warehouse, Outstanding Material, their tests/documents/migrations, and unrelated `routes/web.php` edits. None was reset, staged or committed.
- Before editing, fingerprints were captured for 3,856 repository files outside generated/dependency directories; the four hotfix source/test originals were retained for comparison. Final scope comparison is reported below.
- Inspected root `AGENTS.md`, `CLAUDE.md`, Laravel cognitive framework, requested boost skill, Gate 1, prior Gate 2 report, active controllers/services/models/routes/views, request authorization and existing isolated tests. No nested `AGENTS.md` was found in applicable source paths.
- The target SQL was inspected as text only for graph/schema evidence. Its SHA-256 matches `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`; no SQL dump was executed or edited. No live development/production graph was queried or changed.

## Validated relationship model

| Contract | Verified evidence and meaning |
| --- | --- |
| Subordinate/source | `position_id` references `mst_job_positions.id`. `MstJobPosition::approvalRoutes()` uses this FK. The master controller writes the newly created/edited source position into this field. |
| Parent/approver | `approver_position_id` references the recipient position; `MstPositionApproval::approverPosition()` and `MstJobPosition::approverForRoutes()` use it. |
| Read traversal | **Parent `approver_position_id` → child `position_id`**. One stored non-null route is an explicit reporting relationship under B6 Option A. Transitive descendants require further explicit routes, never a same-section/title inference. |
| Cardinality | Unique slot `(position_id, approval_level)` in migration and target DDL. A position can legitimately have several recipients across different levels. These are multiple authorized relationships, not proof of one immediate parent or a graph depth. |
| Null recipient | Optional/unconfigured route, no reporting edge. Target FK uses `ON DELETE SET NULL`; source FK uses `ON DELETE CASCADE`. |
| Effective time | Routes have timestamps but no effective interval. Evaluate the current stored graph, active master positions and the existing assignment `activeAt()` interval. Do not derive validity from route timestamps or assessment periods. |

### Levels 0–3

| Level | Verified role | Source evidence |
| ---: | --- | --- |
| 0 | Optional **Sub Section Head**, not self/origin | Live master form labels level 0 explicitly; `MstJobPositionController::store/update` accept 0–3. `PenilaianTCController` index and `submitDraft` resolve the level-0 recipient and check its holder before submitting the draft. |
| 1 | Section Head approval role | Master form/migration; active competency scope and level-1 approval checks. |
| 2 | Department Head approval role | Master form/migration and active competency approval scope. |
| 3 | Optional Division Head approval role | Master form/migration, Division scope/dashboard and assessment route checks. |

The migration/DDL comment lists only 1–3; the active form/controller supply the missing level-0 definition. The form conditionally displays level 0 for Finance and level 3 for Sales; this presentation is not used as a new department-name authorization rule. No Sales rule was edited.

**Level is not rank or graph distance.** The target has ten level-1 routes to a `dept_head` and a level-0 route to a `dept_head`. Thus a strict level-to-`job_level` ranking would incorrectly discard existing designated recipients. Reader eligibility still requires the actor to hold an effective active `sec_head`, `dept_head` or `div_head` assignment. A staff-coded route recipient does not gain Mapping permission from an edge alone. Intermediate graph positions do not require an occupant; assignments select actors and readable employees, not graph edges.

### Static graph verification — target snapshot only

Narrow offline parsing of only `mst_job_positions` and `mst_position_approvals` corroborated:

| Level | Total slots | Non-null edges | Null recipients |
| ---: | ---: | ---: | ---: |
| 0 | 17 | 2 | 15 |
| 1 | 40 | 37 | 3 |
| 2 | 49 | 46 | 3 |
| 3 | 17 | 4 | 13 |
| **Total** | **123** | **89** | **34** |

All **56** master positions in this snapshot are active. Found **0** duplicate slots, orphan references, self-edges, cyclic edges, or explicit cross-department edges where both departments are populated. Of the non-null edges: level 0 targets one Section Head and one Department Head; level 1 targets 27 Section Heads and ten Department Heads; level 2 targets Department Heads from 37 staff and nine Section Head positions; level 3 targets Division Heads from four staff positions. These counts describe this immutable snapshot, not current deployment data.

No per-edge approval-only exception flag exists in the inspected schema/model. All validated explicit routes use the business authority granted by Option A. Unknown levels, invalid references and conflicting slots are not interpreted as special exceptions. Synthetic transitive head-to-head fixtures exercise relationships representable by the existing schema/controller; they are not claims that every such chain is present in the snapshot. No route was added to a real application database.

## Implementation

### Graph and assignment scope

`JobPositionAccessService::getReadableMappingScope()` now:

1. Preserves the manager branch exactly; ordinary staff receive no reader grant.
2. Obtains all date-effective actor assignments on active master positions, using only head positions as traversal roots. Missing date/graph columns fail closed for readers.
3. Loads active position IDs/levels and graph endpoint/level fields in batches. Valid levels are the verified set 0–3, not a numeric ordering rule.
4. Deduplicates equal edges and equal recipients in the same slot. Different recipients in one slot, including null versus a recipient, quarantine that source and its downstream branch. Different slots are legitimate alternate paths.
5. Ignores inactive/missing source positions; an active source referencing a missing/inactive parent, self or unknown level is unsafe. No such path authorizes a read. Missing employees cannot produce readable mapping rows.
6. Uses an iterative Kahn pass to close cyclic components and their downstream branches, including alternate paths into the unsafe branch. Unrelated valid branches remain readable. Traversal is finite and uses visited sets; there is no recursive SQL/query-per-node or unbounded call stack.
7. Computes ancestors of all effective actor positions and excludes them, all actor-held positions and the actor's own employee rows. This is conservative when the actor holds several ranks: ancestors of any held position remain excluded. Traverses the union of remaining safe head-root branches, deduplicates and numerically sorts descendant IDs.
8. Applies descendant position IDs to **individual effective active mapping rows**. One eligible employee assignment never exposes that employee's unrelated assignments.

No authorization cache was added. Every request resolves current assignments/graph. A private in-memory adjacency/visited set lives only for that resolver call. Index reuses its scoped builder for listing and filter options; employee-position AJAX reuses the same computed scope for its target gate and payload.

### Controllers and endpoint protection

- Index scopes before search/filter/pagination. Position options come from readable assignments; reader employee/organization management options remain absent. Stable secondary ordering by mapping ID handles tied timestamps for reader pagination. Malformed search/position-filter shapes are validated.
- `getPositionsByUser` gates the employee through the same row scope and returns only authorized positions, deduplicated, with `id`, name, department/section IDs and job level. Readers do not receive approval-route ancestors, users or account attributes. Manager payload behavior retains existing relations.
- Working Experience is readable at employee level only when an eligible subordinate mapping exists. It continues through `canViewEmployeeWorkingExperience`; the existing response field allowlist is unchanged. Its `user_id` input now requires an integer.
- Existing management gates, mutation Form Requests, assignment transactions, overlap validation, deactivate and audit behavior are unchanged. Scoped read permission does not authorize mapping or Working Experience writes, import, template download, method-spoofed requests or Master Job Position management.

### UI

- Replaced the obsolete B6-blocked notice with read-only scope guidance and a scoped empty state.
- Reader Detail shows the authorized selected mapping position and employee's read-only Working Experience. No mutation controls/import/template were added for heads; manager controls remain.
- Existing abort/version guards clear loading content and reject stale callbacks across employee switches/modal close. Added explicit 403 feedback, network failure text and selected-position text clearing on close. Database-derived text uses escaped Blade or jQuery `.text()`.
- Global layout/navigation was not edited. The actual Mapping/Master fragment was rendered in an isolated test to verify that eligible heads see Mapping, not Master Job Position, and that invalidated assignments remove Mapping visibility.

## Verification actually executed

### Isolation

Before database-backed tests, read-only preflight verified forced PHPUnit `testing`/MySQL database settings, no `.env.testing`/cached-config override, a loopback connection, and selected database **`fastware_adsi_1_testing`**. No credential values were printed. Reused the existing `HrIsolatedDatabaseTestCase`: each test creates a random, explicitly owned `fastware_adsi_1_hr_gate2_<20hex>_testing` database, verifies the switched connection, uses synthetic records/minimal fixture tables and a temporary compiled-view directory, and drops only its owned schema after exact name/current-connection checks.

No migration, dump import, seeder, importer, `RefreshDatabase`, `migrate:fresh` or cleanup command ran. Malformed/duplicate routes and route-order changes occur exclusively in these disposable fixtures; the fixture table intentionally allows corruption cases that the declared production unique/FK constraints normally reject. Final read-only metadata check: **0 owned fixture schemas remain**.

### Tests and exact results

Commands executed serially as `php vendor/bin/phpunit <path>`:

| Path | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| `tests/Feature/HR/EmployeeMappingAuthorizationTest.php` | 35 | 253 | PASS |
| `tests/Feature/HR/CompetencyInformationTest.php` | 9 | 103 | PASS |
| `tests/Unit/HRRoleAccessServiceTest.php` | 3 | 12 | PASS |
| `tests/Unit/HRMenuAccessGroupTest.php` | 11 | 74 | PASS |
| `tests/Unit/HRMenuServiceTest.php` | 4 | 10 | PASS |
| `tests/Unit/JobPositionAccessServiceTest.php` | 3 | 4 | PASS |
| `tests/Feature/WorkingExperienceImportUploadTest.php` | 1 | 4 | PASS |
| `tests/Unit/WorkingExperienceValidationServiceTest.php` | 5 | 5 | PASS |
| `tests/Unit/EmployeeIdentityFormatterTest.php` | 7 | 8 | PASS |
| `tests/Feature/TrainingHistoryEndpointTest.php` | 1 | 12 | PASS |
| **Total, final selected runs** | **79** | **485** | **PASS; 0 failures/skips** |

PR 1 feature tests cover each head level, direct/indirect chains through several positions, multiple eligible roots, repeated paths, ineligible extra actor assignments, same-name unrelated positions, per-row multi-position employee scope, subordinate Working Experience and minimal AJAX fields, staff/guest denial, all mutation/import/template gates including valid subordinate targets, spoofed DELETE, ID/filter manipulation, pagination/options, inactive/future/expired/missing-start actor and target assignments, inclusive date boundaries, self/ancestor exclusion, null and level-0 semantics, invalid/conflicting edges, cycles/downstream quarantine, fresh graph resolution and row-order independence. All three head pages and manager page have their rendered inline scripts syntax-checked.

The first expanded run had **1 test expectation failure**: malformed array `user_id` was correctly rejected with 403 before validation, while the new test expected 422. The test was corrected to assert that established safe authorization behavior; production permissions were not weakened. Subsequent focused runs passed. Final totals above count final cases once, not repeated diagnostic runs.

### Performance and regression evidence

- Resolver test increases fixture mappings from **1 to 21**, with additional graph nodes: database query count stays equal. Algorithm cost is O(V+E) for graph validation/closure, plus scoped mapping queries. No full employee collection is loaded for readers. This is query-growth evidence, not a production latency/load benchmark.
- PR 3 tests pass unchanged: metadata/descriptions/rubric, authorization, five active templates, score submission, historical read snapshots and existing non-Sales/Sales approval paths. PR 3 assets/controllers/templates and the existing service tail starting at `canViewEmployeeWorkingExperience` are byte-unchanged from hotfix start.
- Existing manager create/update/toggle/deactivate, assignment auditing, Working Experience CRUD/import staging and HR menu/role tests pass. No production organization or approval workflow was exercised or changed.
- `php -l` on the changed controller, service and feature test: **PASS, 3 files**.
- Scoped `git diff --check`: **PASS**.
- `php vendor/bin/pint --test app/Http/Controllers/UserJobPositionController.php tests/Feature/HR/EmployeeMappingAuthorizationTest.php`: **PASS, 2 files**.
- Pint also inspected `JobPositionAccessService.php`: **FAIL, 1 legacy file** with existing formatting/import issues. The previous report already records that baseline failure; no broad formatting was applied to hide it or churn PR 3/legacy methods. The previously reported legacy `TcController`/`PenilaianTCController` formatting failures were not changed or presented as fixed.

## Exact changed files and protected work

This hotfix changes four existing files and creates this report:

1. `app/Services/HR/JobPositionAccessService.php`
2. `app/Http/Controllers/UserJobPositionController.php`
3. `resources/views/user_job_position/index.blade.php`
4. `tests/Feature/HR/EmployeeMappingAuthorizationTest.php`
5. `docs/hr/GATE-2-PR1-B6-HIERARCHY-HOTFIX-REPORT.md` — new.

Fingerprint comparison against hotfix start found no other source/input changes. PHPUnit regenerated its ignored `.phpunit.result.cache`; generated test/storage/temporary compilation artifacts are not application edits. Global layout, requests, models, enums, role service, routes, schema/migration files, SQL dumps, environment files, Gate 1/M2/prior report, PR 3 code, Warehouse and Outstanding Material work remain preserved. No reset/stash/stage/commit/push/deploy was performed.

## Limitations and production readiness

- **B6 source/level/direction blocker is resolved** by the final business decision plus the technical evidence above. No unresolved level-0 interpretation is used to grant access.
- Deployment graph/schema/ledger and actual employee assignments were **NOT VERIFIED** against a live development/production database. Static snapshot findings must not be presented as current live-data cleanliness. The runtime resolver validates current data and excludes unsafe branches rather than repairing them.
- Full authenticated browser QA with real global layout, Bootstrap/Select2 modal/network lifecycle, screen readers, representative viewport/device checks and production-like volume is **NOT RUN in this hotfix**. Prior PR 3 component browser results remain historical; the unchanged components were not relabelled as newly browser-tested. Menu-fragment rendering is not full-shell browser QA.
- Full application test suite, production Vite build, staging, deployment and application-level release smoke tests were **NOT RUN**. No Vite inputs/dependencies changed. Only the verified isolated fixtures were written.
- Ancestor exclusion and downstream quarantine deliberately favor denial over an uncertain grant; HR may investigate missing rows privately. No automatic relink, graph rewrite or fallback by title/department is provided.
- PR 2 historical migration/2025 cleanup/data approvals and PR 4 Sales organization/approval changes remain untouched. No historical employee manifests or personal records were created in the repository/report.

The implemented PR 1 behavior meets the focused positive/negative automated acceptance criteria. Production release still requires the normal environment/schema checks and authenticated application QA; this report is not deployment approval.

**GATE 2 PR 1 B6 HOTFIX COMPLETE — PENDING USER REVIEW**

**STOP — PENDING USER REVIEW.**
