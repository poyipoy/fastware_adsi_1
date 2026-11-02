# PR 2 — practical HR 2025 crosswalk review package

22 October 2026. Branch **main**, HEAD **6161d682e1eeaf81c9e83acad523af7c5b688b45**. Existing dirty worktree retained; only this report and the review-package directory were added. No reconciliation implementation, application feature, existing report, SQL dump or current assignment was changed.

**Non-sensitive fallback package complete. Individualized candidate/exception exports BLOCKED:** no restricted local destination outside Git has been explicitly authorized. No real employee, assessment, training, position-label candidate list or master-description row was written into repository review files. Candidate work below is aggregate evidence from in-memory analysis, not delivered private candidate records or resolved business equivalence.

## Open the package

Start with [pr2-crosswalk-review/README.md](pr2-crosswalk-review/README.md). Files are Excel-compatible UTF-8-BOM CSV; CSVs are editable review documents, **not direct input to --crosswalk**. The README and `crosswalk_field_map.csv` document the exact supported JSON correspondence. `crosswalk.pending.json` is valid hash-bound JSON with all mapping/status/event arrays empty. No approval-generating converter or write mode was added.

| Deliverable | Supplied now | Private population still blocked |
| --- | --- | --- |
| Positions | `position_crosswalk.csv`, `position_coverage.csv` | 46 individual legacy label/candidate rows with source/target locators and organization evidence |
| TC / Soft Skills / Additional | Three `*_master_crosswalk.csv` templates, `category_crosswalk.csv`, `master_coverage.csv` | 229 definition rows with both descriptions/standards/context and candidate evidence |
| Assessment exceptions | `assessment_exceptions.csv`, `assessment_identity.csv`, `missing_definitions.csv`, `assessment_issue_summary.csv`, `status_semantics.csv` | Identity exceptions, repeated-row memberships, missing-master locators and exact event comparisons |
| Training | `training_investigation.csv`, `training_cohorts.csv` | 934 individually identified provenance/dependency review records |

The package contains **15 CSVs, one pending JSON and one README**; nine CSVs are header-only private-row templates. The other CSVs contain aggregate counts or field instructions only. HR/DBA decision cells are blank, review status is pending where aggregate rows use it, and all approved mappings/actions remain zero.

## Candidate coverage actually examined

Inventory covers **99 legacy position rows → 46 normalized labels**, compared with **56 target positions** using the existing pinned snapshot parser and position normalizer.

| Position review group | Labels | HR-approved |
| --- | ---: | ---: |
| Exact normalized label proposed | 5 | 0 |
| Token-label suggestion | 25 | 0 |
| Ambiguous suggestions | 9 | 0 |
| No suggestion | 7 | 0 |
| **Total** | **46** | **0** |

Supplementary lexical triage only: tokenize Unicode letters/numbers after existing uppercase/whitespace normalization; exclude `HEAD, STAFF, OPERATOR, SECTION, SECT, SEC, DEPT, DEPARTMENT, DIVISION, SUB, OF, AND, THE`; require Dice token overlap at least **0.50**. Retain up to three target suggestions, sorted by score then target reference. A top-two score gap below **0.15** is ambiguous. The five existing exact-label candidates are kept distinct from these suggestions. No translation aliases, fuzzy approvals, numeric-PK equivalence or current-assignment fallback were introduced. This triage did **not** change the existing resolver or enter the dry-run crosswalk.

Organization evidence is **UNVERIFIED** for historical equivalence. Existing catalog references establish legacy master-to-position context and target department/section IDs, but do not establish historical department/section equivalence or prove that similar names report through the same organization. HR must supply historical organization/version evidence; numeric context IDs and job-level words cannot approve a mapping. Source employee details were not exported to fill that gap.

| Available referenced master review | TC | SK | Additional | Total |
| --- | ---: | ---: | ---: | ---: |
| Available source definitions examined | 133 | 75 | 21 | 229 |
| Exact-label suggestion inside triage context | 0 | 0 | 0 | 0 |
| Token-label suggestion inside triage context | 1 | 0 | 0 | 1 |
| Ambiguous definition suggestions | 0 | 0 | 0 | 0 |
| Position/rubric context pool only | 52 | 54 | 6 | 112 |
| No suggestion or blocked context | 80 | 21 | 15 | 116 |
| HR-approved definitions | 0 | 0 | 0 | 0 |
| Distinct referenced definitions missing from source | 1 | 0 | 4 | 5 |

A context pool means only same competency type, one of the **unapproved position suggestions**, and a matching four-score rubric signature. It does not propose one equivalent target definition. Within those pools, the same lexical threshold/tie rules were used for label suggestions; descriptions, standards, version and organization equivalence still require HR decisions. No source/target ID pair was accepted. Existing conservative crosswalk remains **0 contextual matches**; three SK label-only matches elsewhere in the catalog still lack required context. Three rubric-signature category candidates remain unapproved. The single token suggestion is not a technical migration candidate.

## Focused decisions needed next

- **Identity — HR Identity Owner + DBA:** privately resolve 17 assessment rows (4 invalid/zero NPK, 13 ambiguous source NPK) with unique business-identity evidence. Do not use matching numeric IDs or employee names as fallback.
- **Repetition — HR Assessor + DBA:** review all 44 participating source rows and their overlapping component groups. Decide event/revision representation and preservation; no automatic survivor or row merging.
- **Missing definitions — Competency Owner:** recover authentic versions for five distinct absent source definitions, affecting nine assessments. No invented source master, default category or assumed standard.
- **Status/score — HR Approver + DBA:** define source statuses 1/2/3 (7/32/242 rows), historical lock/actor semantics, 281 NULL totals, 12 rows with reference-plus-NULL score, 4 with score-without-reference and 5 zero-TC scores. Preserve literals and timestamps.
- **Event identity — HR + DBA:** establish exact source-target assessment equivalence after semantic crosswalks. All 281 legacy PKs collide with unrelated target employee rows; no business event match is established. Conflict comparisons must not be invented from those IDs.
- **Training provenance — HR Training Owner + DBA:** T25-A **490** needs original event/parent lineage evidence; T25-B **433** needs ownership evidence and protection of **12 links to 7 assessment-2026 parents**; T25-C **10** needs historical competency-selection evidence; T25-D **1** still needs authentic event provenance. Coherent links, timestamps, source absence and possible repeats do not prove dummy data. All four cohorts remain QUARANTINE and DELETE_APPROVED=0.

**Current input limitation:** the supported JSON handles position/category/master/status crosswalks and reviewed existing-event pairs. It does not accept employee-identity overrides, duplicate-survivor decisions, supplementary missing-definition records, or training delete/relink actions. Completed private queues are evidence for later scoped decisions; filling CSVs alone cannot unblock those cases. Any future adapter/contract change needs separate authorization. Do not edit immutable snapshots or add unsupported JSON fields as a workaround.

First required operational input is an explicitly authorized outside-repository restricted directory, access scope/owner and HR/DBA reviewers. Then individualized files can be populated for actual review. No private export is claimed in this package, and no staging connection was supplied/authorized; all work remained offline.

## Actual validation and re-run

- CSV round-trip/UTF-8-BOM/header uniqueness/row-width checks: **PASS for all 15 CSVs**. Coverage sums verified: **46 positions, 229 available masters, 934 training**. Nine private templates have zero data rows; no HR/DBA approval cell was filled. The first validation identified a missing source-reference column in the target-training template; it was added explicitly without inventing a training source.
- Existing offline unit tests: **32 tests / 114 assertions PASS**. Synthetic cases remain isolated unit tests; no synthetic review was passed into actual snapshot runs. No database-backed tests or SQL execution were run in this mission.
- Ran the existing standalone dry-run both **without crosswalk** and **with crosswalk.pending.json**. Both succeeded with byte-identical JSON, confirming schema compatibility and no approved mappings. Aggregate output SHA-256: **`022a4a58eda59b31ec4c12ad0297418269bb60b84482dbb5b9aad43b84262934`**, unchanged from the previous dry-run.
- Actual unresolved result: **264 identity candidates / 17 unresolved; 0 reviewed position/category/master mappings; 0 established event matches; 0 safe insert candidates; 281 source assessments QUARANTINE; 320 existing target assessments preserved; 934 training under investigation; all three action-approval counters zero**. Source period remains 2025 USER_ATTESTATION; literal timestamps remain unchanged.
- Original-file fingerprints: all pre-existing files unchanged except ignored PHPUnit result cache. Source/target checksums remain respectively `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8` / `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`.

Only this report and `docs/hr/pr2-crosswalk-review/` were added. No PR 1/PR 3/PR 4 or unrelated dirty work changed; no database connection, SQL writes, importer/seeder, dummy generation, apply, commit, push or deploy occurred.

**PR 2 CROSSWALK REVIEW PACKAGE COMPLETE — PENDING HR/DBA MAPPING APPROVAL**

Private candidate-record export remains **BLOCKED — NO AUTHORIZED RESTRICTED DESTINATION**. This completion is the explicitly permitted non-sensitive fallback package, not completed semantic mapping or historical migration.
