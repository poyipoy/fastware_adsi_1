# PR 2 — actual 2025 offline dry-run report

Date: 21 October 2026. Baseline: `main` @ `6161d682e1eeaf81c9e83acad523af7c5b688b45`.

**REAL OFFLINE DRY-RUN COMPLETED. NO HISTORICAL DATA WRITES.** This report was generated from two actual executions of the implemented tooling against the original immutable snapshots. It is an aggregate diagnostic report, not an approved change manifest or migration/cleanup result. Implementation, tests and exact changed files are recorded in [the implementation report](GATE-2-PR2-MIGRATION-IMPLEMENTATION-REPORT.md).

## Execution and immutable inputs

```powershell
php scripts/hr-2025-dry-run.php '--legacy=dms_adasi_rev1.sql' '--target=dms_adasi_rev1 (12).sql' '--year=2025'
```

| Input | Bytes | SHA-256 |
| --- | ---: | --- |
| Legacy | 2,069,117 | `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8` |
| Target schema/data reference | 4,313,503 | `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db` |

Both inputs MATCH Gate 1/M2. Hashes were checked before parsing and rechecked after analysis; source-dependent processing rejects drift. No dump was executed or loaded into MySQL. The actual run used Composer autoload without Laravel bootstrap and made **0 database connections / 0 writes**. No endpoints, model events, seeders, importers, dummy generators or external integrations were called. No real review crosswalk was supplied.

The two final stdout JSON results were byte-identical. SHA-256 of the aggregate JSON, including its trailing LF: **`022a4a58eda59b31ec4c12ad0297418269bb60b84482dbb5b9aad43b84262934`**. No run timestamp or random identifier is injected into the JSON; this Markdown report is a separate reporting artifact.

## Assessment reconciliation — verified static

| Diagnostic | Actual |
| --- | ---: |
| Legacy assessment rows evaluated | 281 |
| Business year | 2025 — USER_ATTESTATION, exact immutable cohort |
| Literal created_at year 2025 / 2026 | 10 / 271 |
| Original source total_nilai explicitly NULL | 281 |
| Unique valid NPK pairs, all source users | 105 |
| Assessment identity candidates / unresolved | 264 / 17 |
| Invalid/zero NPK assessment rows / ambiguous source NPK rows | 4 / 13 |
| Source position rows / normalized labels / exact target-label candidates | 99 / 46 / 5 |
| Assessment rows with exact position-label candidate | 25 |
| Reviewed historical position pairs / reviewed assessment-position rows | 0 / 0 |
| Source rows with missing master definitions | 9 |
| Repeated component-key participation, union | 44 |
| Repeated TC keys: groups / rows | 16 / 44 |
| Repeated SK keys: groups / rows | 12 / 32 |
| Repeated Additional keys: groups / rows | 0 / 0 |
| Repeated full component-tuple groups | 10 |
| Legacy assessment PK collisions / different employee | 281 / 281 |
| Modifier NPK identity candidates / unresolved | 271 / 10 |
| Target assessments 2025 | 320 |
| Target status 4 / status 2 / locked rows | 312 / 8 / 320 |
| Target nonnull totals / differs from component sum | 320 / 320 |
| Established reviewed source-target business identity matches | 0 |
| Source rows checked against mapped target unique keys | 0 |
| Technically established INSERT_CANDIDATE | 0 |
| Source QUARANTINE | 281 |

Year eligibility is closed through USER_ATTESTATION; entry timestamps do not determine year and are not rewritten. All 281 source transactions retain original scores, NULL/zero totals and row packing in memory. Legacy status distribution is **1:7 / 2:32 / 3:242**. Historical meanings/lock/actors still require review. A mapped modifier is not proof of approval.

Primary non-overlapping waterfall: **W1 identity 17; W2 position 252; W3 missing source master 2; W4 crosswalk/review 10 = 281**. Overlapping reasons include missing source masters 9, unresolved source-master position 6, reference-with-NULL-score 12, score-without-reference 4, zero-score review 5, and repeated component keys 44. All rows also remain subject to unapproved status/master/category and actor semantics; overlapping reasons must not be added as extra transactions.

All 281 numeric PK collisions are diagnostic only; none is used as a target match or copied target PK. No source record is merged, discarded or selected as a duplicate survivor.

**Target unique/score/status conflict comparisons are not established:** the mapped-key comparison gate evaluated **0 rows**, because technical crosswalk/review gates are not satisfied. Its zero conflict count does not mean real conflicts are absent. The target's three component unique keys have zero duplicate groups on the snapshot, but that does not prove event authenticity. Existing target 2025 rows remain **QUARANTINE_PENDING_PROVENANCE_AND_RECORD_REVIEW**, physically preserved.

## Minimum competency/category crosswalk

| Referenced master diagnostic | TC | Soft Skills | Additional |
| --- | ---: | ---: | ---: |
| Source catalog | 193 | 144 | 75 |
| Target catalog | 207 | 205 | 203 |
| Source reference occurrences | 281 | 142 | 48 |
| Distinct referenced IDs | 134 | 75 | 25 |
| Available referenced definitions | 133 | 75 | 21 |
| Missing definitions / affected occurrences | 1 / 5 | 0 / 0 | 4 / 4 |
| Available definitions with exact position candidate | 19 | 10 | 1 |
| Available definitions with rubric-category candidate | 133 | 75 | 20 |
| Label-only target candidate | 0 | 3 | 0 |
| Type + position + rubric + label contextual candidate | 0 | 0 | 0 |
| Reviewed master pairs | 0 | 0 | 0 |
| Referenced source descriptions nonblank | 133 | 75 | 20 |
| Target descriptions nonblank | 7 | 5 | 3 |

The referenced available closure is **229 definitions**, with **5 distinct missing definitions**. This is not an insert quota. There are **3 unique rubric-signature category candidates**, but **0 reviewed category pairs**. Label, rubric and numeric-ID equality do not authorize crosswalks. No shared master definition/description/category/standard was inserted or overwritten.

## Training 2025 investigation

| Cohort | Actual | Interpretation / disposition |
| --- | ---: | --- |
| T25-A | 490 | Parent absent; QUARANTINE, not proof of dummy data |
| T25-B | 433 | Parent employee differs; QUARANTINE |
| T25-C | 10 | Employee/position/year context coherent, selected master differs; QUARANTINE |
| T25-D | 1 | Relationally coherent, event provenance unverified; QUARANTINE |
| Other relation-review cohort | 0 | Reserved for malformed/otherwise incoherent links |
| **Total** | **934** | **All investigation; DELETE_APPROVED = 0** |

- Missing parent links represent **317 distinct missing numeric IDs**.
- **12** training-2025 links point to **7** protected assessment parents outside 2025; preserve both sides, never automatically relink/cascade.
- **416** found-parent position mismatches overlap the cross-employee cohort.
- Selected employee/position/master existence diagnostic: **0 missing** in this cohort; that is not provenance proof.
- Broad possible repeat key: **193 groups / 386 rows**. Stricter employee/position/section/year/type/master/program/due-date key: **2 groups / 4 rows**. Exact full-business-payload duplicate groups: **0**.
- Uniform timestamp/status, orphan links, repeat keys and the existence of an old dummy generator do not establish dummy provenance.
- Legacy training source rows **0**; planned/source-generated training inserts **0**. No replacement source is expected or synthesized.

## Dependencies and protected data

| Diagnostic | Actual / boundary |
| --- | --- |
| Assessment non-2025 | **7**, protected OUT_OF_SCOPE |
| Training non-2025 | **18** in 2027, protected OUT_OF_SCOPE |
| Protected participant pivot | **1**, attached to 2027 training; declared parent delete cascade retained |
| Training references to 2025 parents | **432**, across **267** parents |
| 2025 parent counts with 0/1/2/3/4 training children | **53 / 132 / 106 / 28 / 1** |
| Non-2025 children of 2025 assessment parents | **0 on this snapshot**, not a live guarantee |
| Target detail/history retained | **723**; reliable event/year FK absent |
| History position association: 2025-only / other-only / neither / both | **617 / 10 / 96 / 0**; not event/period proof |
| Source detail/history | **1**, QUARANTINE with lineage unverified; outside assessment attestation |
| Shared masters: 2025 assessment-or-training versus non-2025 assessment | **TC 3 / SK 2 / Additional 4**, protected |
| Shared masters: assessment-only 2025 versus assessment-only 2026 | **0 / 0 / 0** |
| Files, JSON/text/history links, other runtime consumers | **PENDING HR/DBA**, not assumed zero |

**M2 wording correction PR2-E01:** M2 §8 describes 3/2/4 as shared between assessment years. A narrow independent set intersection now verifies those numbers for the **union of assessment/training 2025** against assessment 2026, while assessment-only intersection is zero. The implementation reports both scopes. M2/Gate 1 were not silently rewritten, and all masters/shared consumers remain protected.

Snapshot input hashes are unchanged. No staging/production database or files were read or modified; no claim is made that runtime protected data was fingerprinted. In isolated synthetic database tests, accounts/assignments/locked assessment 2026/training 2027 remained byte-equivalent across two command runs with zero queries during execution.

## Classifications, review and readiness

| Domain | RETAIN | INSERT_CANDIDATE | UPDATE_CANDIDATE | REVIEW_DELETE | QUARANTINE | OUT_OF_SCOPE |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Source assessment transactions | 0 | 0 | 0 | 0 | 281 | 0 |
| Unreviewed target 2025 assessments | 0 | 0 | 0 | 0 | 320 | 0 |
| Target 2025 training | 0 | 0 | 0 | 0 | 934 | 0 |
| Explicit non-2025 assessment/training/participant rows | 0 | 0 | 0 | 0 | 0 | 26 |

These are distinct domains, not matched events or a combined mutation list. Accounts, active organization mappings, approvals, masters and unrelated modules are protected separately; the 26 count does not enumerate all protected database records. **0 INSERT_APPROVED / 0 UPDATE_APPROVED / 0 DELETE_APPROVED.**

Remaining B3–B9 requirements: independent provenance and obsolete/dummy proof; bad-link lineage and protected closure; status/score/NULL/total/repetition semantics; exact historical identity/position/master/category crosswalk; shared-description governance; live schema/ledger/drift; private signed record manifest, backup, restore rehearsal, rollback and separate write authorization. A future private manifest needs opaque identity lookup, source/target locators/hash binding, period attestation, all reasons, exact before/after, parent/child dependencies across years, reviewer decisions and rollback evidence. No such manifest was exported or populated here.

**Staging: NOT RUN / BLOCKED** — no explicitly authorized dedicated read-only staging connection/permission could be established from the available configuration. There was no default/development/production fallback. Physical attachments, full runtime dependency closure and live schema compatibility remain unverified.

Verification actually completed: **103 selected tests / 657 assertions PASS**, including **34 new tests / 139 assertions**; syntax and scoped formatting on **12 new PHP files PASS**. The detailed report distinguishes broad pre-existing diff whitespace failures and unperformed full-suite/browser/staging/production checks. No historical migration/cleansing is complete or authorized.

**GATE 2 PR 2 — MIGRATION TOOLING & DRY-RUN COMPLETE — PENDING DATA REVIEW**

**STOP — PENDING USER REVIEW.**
