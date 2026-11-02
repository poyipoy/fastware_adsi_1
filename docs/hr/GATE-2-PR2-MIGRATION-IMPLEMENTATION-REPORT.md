# Gate 2 PR 2 — migration tooling implementation

Date: 21 October 2026. Repository: `poyipoy/fastware_adsi_1`.

**TOOLING AND OFFLINE DRY-RUN COMPLETE — PENDING DATA REVIEW.** This is not historical migration, cleansing, staging-write approval or production readiness. No real historical records were inserted, updated, deleted, relinked, unlocked or synthesized. The actual dry-run results are in [PR2-2025-DRY-RUN-REPORT.md](PR2-2025-DRY-RUN-REPORT.md).

## Baseline and authority

- Branch **main**, HEAD **6161d682e1eeaf81c9e83acad523af7c5b688b45**. No fetch, commit, staging, push or deploy.
- Initial dirty worktree: **30 modified tracked files and 24 existing untracked files** when expanded with `--untracked-files=all`. Existing PR 1/PR 3, Warehouse, Outstanding Material, routes, their tests and reports were preserved. Fingerprints were captured for **3,857 existing files**, excluding generated/dependency folders; the only pre-existing fingerprint changed by this task was PHPUnit's ignored result cache.
- Read project instructions, Gate 1, M2, importer/command/tests, relevant HR models, migrations, provider/console registration and actual target DDL. No interview or business decision was reopened.
- Both supplied dump hashes/byte lengths were checked before parsing and after analysis. Legacy: **2,069,117 bytes**, SHA-256 `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8`. Target: **4,313,503 bytes**, SHA-256 `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`. Both MATCH.
- The target dump remains schema authority. Its MySQL 8.0.30 header is static metadata; independent testing-connection preflight also observed **MySQL 8.0.30**, only on the verified local testing database. No production/development database connection was opened for this workflow.
- Q13–Q15 remain final. All 281 source assessments have business year 2025 through USER_ATTESTATION for this exact hash/cohort. Literal 2025/2026 timestamps remain unchanged. No replacement training source exists; planned legacy training inserts remain zero.

## Implemented components

New code is isolated under `app/Services/HR/Reconciliation`; existing importer, models, controllers, UI, routes and shared authorization services were not edited.

| Component | Responsibility and safety boundary |
| --- | --- |
| `SqlDumpSnapshot` | Verified immutable file read, bounded file size, literal statement/tuple parsing, selected-table/schema/index/FK inspection. No eval or database execution. Supports quoted delimiters, doubled/escaped quotes, NULL and numeric strings without float precision loss. Rejects unsupported expressions, insert suffixes, malformed input and unsupported escape mode. Users are projected to ID/NPK only. |
| `EmployeeIdentityCrosswalk` | Unique valid nonzero digit NPK on both sides, trim only, preserve leading zeros. Separate source/target IDs. No ID, name, email or assignment fallback. |
| `HistoricalPositionCrosswalk` | Exact normalized label candidates kept separate from explicitly reviewed historical equivalence. Conflicting reviews stay unresolved; no aliases inferred from current positions. |
| `CompetencyCrosswalk` | Minimum referenced closure per TC/SK/Additional type; category candidates from four rubric descriptions. Acceptance requires reviewed position/category/master context, definition/standard version decisions and matching target master context. Missing definitions are never manufactured. |
| `AssessmentReconciler` | Preserve source row packing, scores/NULL/zero/total/timestamps and source provenance in memory; no copied legacy target PK. Gate on period, identity, position, master/category, status/lock/modifier review. Detect raw repeated keys, additional collisions introduced by reviewed aliases, and all three declared target component unique keys. Never select a duplicate survivor. Existing-event matching additionally requires exact event review; lock conflicts remain quarantined. |
| `TrainingInvestigator` | Exhaustively partition target 2025 training by actual relations; measure possible repeats, missing parents, cross-employee and selected-master inconsistencies, protected cross-year links. All current rows remain investigation/QUARANTINE. |
| `DependencyInspector` | Declared FK/cascade evidence, assessment-child distributions, participants, period-protected records, shared masters and ambiguous history associations. Unknown logical/history/filesystem dependencies remain explicitly pending. |
| `Hr2025DryRun` | Hash/cohort binding, target schema/unique-index validation, canonical deterministic aggregate output, zero approval counters. Does not invoke Eloquent, the old importer, endpoints, queues, seeders or events. |

The existing `CompetencyTrainingDataImportService` is unsuitable for this scope: it filters assessment eligibility using `created_at`, can use numeric user IDs/current-position fallback, supports 2025/2026 dummy generation and can create fallback masters. No inspect/import/generator method from that service was executed or reused. Its small parser tests were inspected, but new strict literal parsing is independent of its data/write behavior. The old importer and command remain unchanged and must not be used for this PR 2 workflow.

## Command contract and actual execution

Preferred offline entry point (used for the actual runs):

```powershell
php scripts/hr-2025-dry-run.php '--legacy=dms_adasi_rev1.sql' '--target=dms_adasi_rev1 (12).sql' '--year=2025'
```

This requires Composer autoload only: no Laravel application bootstrap, environment/database config or production connection fallback. A test also runs it successfully with intentionally unusable DB environment settings. It emits aggregate JSON to stdout; it does not create a private manifest or write any report/data file by itself.

Artisan wrapper, tested only inside the isolated testing environment:

```powershell
php artisan hr:reconcile-2025 '--legacy=dms_adasi_rev1.sql' '--target=dms_adasi_rev1 (12).sql' '--year=2025'
```

The wrapper is auto-discovered by the existing Console Kernel; no registration edit was required. The service itself never requests a database connection. Use the standalone entry point when application bootstrap is inappropriate. Default/only mode is dry-run. Explicit paths to both immutable files are required. Year other than 2025, write/delete/dummy options and live-connection options are rejected; there is no apply/delete implementation or hidden enable flag. Unknown/malformed input failures are sanitized rather than printing employee payloads or raw parser/JSON exceptions.

**Two final actual offline invocations returned byte-identical aggregate JSON.** Repeated service and Artisan execution were also tested. No real crosswalk/action manifest was supplied or populated.

### Read-only review input

Optional `--crosswalk=<path>` reads a previously reviewed crosswalk; it never applies it. Current actual runs omit it. It must bind `source_sha256` and `target_sha256` to the two fixed snapshots; unsupported fields, including deletion approval fields, are rejected.

Review sections are `positions`, `categories`, typed `masters.tc/sk/ad`, `statuses`, and optional `assessment_identities`, with a revision reference. Entries require `decision=APPROVED` plus nonempty `hr_review`, `dba_review`, and `evidence` references before mapping is accepted. Position entries use historical `source_label` plus `target_id`; category/master entries use source/target locators, separated by type. Definition/standard/rubric differences require their explicit reviewed flags. Status entries explicitly supply target status, new-record lock state, `null_total_semantics=PRESERVE`, and zero/modifier review where relevant. Exact existing-event review uses source and target transaction locators.

These are **mapping/evidence assertions, not data-action approval**. Signature authenticity is not cryptographically verified by this tooling and must be verified externally by HR/DBA. No write path exists even with review input. Actual employee-identifying crosswalk/manifest material belongs only in a separately approved restricted location outside the tracked repository. No such destination or actual manifest was created in this task. Unit examples contain synthetic data only.

## Verification results

Executed serially using `php vendor/bin/phpunit <path>`:

| Path | Tests | Assertions | Result |
| --- | ---: | ---: | --- |
| `tests/Unit/Hr2025ReconciliationTest.php` | 32 | 114 | PASS |
| `tests/Feature/HR` (includes 2 new command tests) | 46 | 431 | PASS |
| `tests/Unit/CompetencyTrainingDataImportServiceTest.php` | 3 | 8 | PASS |
| `tests/Unit/HRRoleAccessServiceTest.php` | 3 | 12 | PASS |
| `tests/Unit/HRMenuAccessGroupTest.php` | 11 | 74 | PASS |
| `tests/Unit/HRMenuServiceTest.php` | 4 | 10 | PASS |
| `tests/Unit/JobPositionAccessServiceTest.php` | 3 | 4 | PASS |
| `tests/Feature/WorkingExperienceImportUploadTest.php` | 1 | 4 | PASS |
| **Selected total** | **103** | **657** | **PASS; 0 failures/skips** |

The new tests alone account for **34 tests / 139 assertions**, including the 2 command tests (**25 assertions**) inside the HR directory. Counts above do not double-count those tests or repeated diagnostic runs. Existing importer tests call pure parser/normalization functions; the importer command, inspect, import and dummy generation were not run.

Coverage includes parser syntax/immutability, pinned hashes, year attestation versus timestamps, leading-zero NPK and duplicate rejection, explicit historical/type/category/definition/status crosswalks, NULL/zero preservation, raw and mapped repeated keys, target uniqueness versus event identity, lock preservation, training cohorts, protected 2026/2027 links, deterministic real-snapshot results, repeated no-write command execution, rejected apply/delete/dummy/year options, schema drift and no database fallback. Regression tests preserve mapping security, Working Experience, score submission, history reads and existing non-Sales/Sales approval behavior.

### Database isolation evidence

Before database-backed tests, checked `phpunit.xml` forced testing settings, absence of `.env.testing`/cached-config overrides, loopback host, no URL/socket override and the approved local testing credentials without printing their values. `SELECT DATABASE()` verified **fastware_adsi_1_testing**. Reused `HrIsolatedDatabaseTestCase`: minimal synthetic schema exists only in a newly created, random, explicitly owned `fastware_adsi_1_hr_gate2_<20hex>_testing` database; cleanup checks its exact name/current connection. No existing test/development/production schema was migrated or cleared. No `migrate:fresh`, `db:wipe`, seeder or dump execution was used.

The new feature test places protected synthetic 2026 locked assessment/2027 training/account/assignment fixtures in that owned database, runs Artisan twice, asserts **zero database queries during command execution**, identical output, unchanged fixture snapshots, and no sent mail/notifications/queued jobs. Final read-only metadata check found **0 remaining owned fixture databases**.

### Static checks and exceptions

- `php -l`: **PASS on all 12 new PHP files**.
- Scoped Pint: **PASS on all 12 new PHP files**. Five initial formatting issues were corrected only in new files. Sandbox restrictions required a narrowly scoped authorized write escalation for directory creation/formatting; no unrelated formatting occurred.
- Explicit whitespace scan of all new files: **0 problems**. All 12 new PHP files were reviewed; no existing source file was changed.
- Broad `git diff --check`: **FAIL on pre-existing dirty whitespace** in `OutstandingMaterialController.php:1429` and Warehouse `stock-in/validate.blade.php:47,53`. Their fingerprints are unchanged; they were not cleaned up as part of PR 2. New PR 2 files are untracked, so the explicit new-file check above supplements Git's tracked diff check.
- Full application suite, browser QA, production Vite build, production runtime schema/ledger, live attachments, backup/restore rehearsal and staging compatibility/drift checks: **NOT RUN**. No frontend/Vite files were changed. No performance/concurrency claim beyond deterministic in-memory execution is made.

## Staging and remaining gates

**Staging verification: BLOCKED / NOT RUN.** `config/database.php` contains generic configured database drivers, not an explicitly authorized staging connection; no dedicated staging/PR2/read-only connection identifier was found in environment key names inspected. No credentials were printed and the development default was not tried as staging. Read-only permissions, environment identity, live schema/ledger/count drift and a stable snapshot must be independently established before any future staging read. This limitation does not block the completed offline tooling/dry-run deliverable.

| Gate / owner | Remaining requirement |
| --- | --- |
| B3 — HR Data Owner + DBA | Independent event provenance/dummy/obsolete proof for exact 2025 assessment/training records. Suspicion or repeat groups are not approval. |
| B4 — DBA + Workflow Owner | Diagnose bad links and the 12 cross-year dependencies; prove lineage without guessed relink or timestamp inference. History remains protected/unresolved. |
| B5 — HR Approver + DBA | Source status/lock/actor/NULL/zero/total semantics and repeated-row representation. No fabricated approvers, calculated totals or survivor selection. |
| B6 — HR Organization + Competency Owner | Historical position/master/category crosswalk and the 17 identity exceptions. Completed PR 1 reporting access is independent and was not modified. |
| B7 — HR Competency Owner | Shared-description/version governance; no master restoration or overwrite by ID/name. |
| B8 — DBA + Staging Operator | Explicit read-only staging authorization and schema/ledger/constraint/count drift verification; no migration replay. |
| B9 — HR + DBA + User | Restricted manifest destination, signed exact-record action decisions, dependency closure, backup checksum, restore rehearsal, rollback evidence and separate environment/action authorization. |

### Future apply contract — documented, not implemented

A future implementation requires a separately approved exact-record manifest bound to immutable snapshots, live before-state hashes, mapping revision and code ref. It must contain private opaque identity lookup, 2025 attestation reference, original timestamps/NULLs/row packing, exact before/after, every inbound/outbound dependency including protected years/shared masters/pivots/history/files, distinct HR/DBA signatures and user action authorization, verified backup and restore rehearsal. No wildcard/year-only actions, PK remap, REPLACE, blanket upsert, truncation, master drop or synthesized training. Any survivor/relink and its affected children require separate explicit decisions. Concurrency/idempotency, serialized execution, transaction/journal behavior, drift rejection and reverse dependency rollback must be reviewed before adding any write mode.

Code rollback for this task would remove only the new PR 2 files, preserving the existing dirty worktree; no database rollback is needed because this tooling did not write historical data. Actual backup/restore and a future rollback executable were not run or generated.

## Exact files added

1. `app/Services/HR/Reconciliation/SqlDumpSnapshot.php`
2. `app/Services/HR/Reconciliation/EmployeeIdentityCrosswalk.php`
3. `app/Services/HR/Reconciliation/HistoricalPositionCrosswalk.php`
4. `app/Services/HR/Reconciliation/CompetencyCrosswalk.php`
5. `app/Services/HR/Reconciliation/AssessmentReconciler.php`
6. `app/Services/HR/Reconciliation/TrainingInvestigator.php`
7. `app/Services/HR/Reconciliation/DependencyInspector.php`
8. `app/Services/HR/Reconciliation/Hr2025DryRun.php`
9. `app/Console/Commands/ReconcileHr2025Command.php`
10. `scripts/hr-2025-dry-run.php`
11. `tests/Unit/Hr2025ReconciliationTest.php`
12. `tests/Feature/HR/Hr2025DryRunCommandTest.php`
13. `docs/hr/GATE-2-PR2-MIGRATION-IMPLEMENTATION-REPORT.md`
14. `docs/hr/PR2-2025-DRY-RUN-REPORT.md`

No pre-existing application file, SQL dump, credential/environment file, route, migration, seeder, importer, PR 1/PR 3 UI/authorization, Sales structure/approval or unrelated dirty work was edited. Only the ignored PHPUnit result cache was regenerated.

**GATE 2 PR 2 — MIGRATION TOOLING & DRY-RUN COMPLETE — PENDING DATA REVIEW**

**STOP — PENDING USER REVIEW.**
