# PR 2 — historical position crosswalk integration

22 October 2026. Branch `main`; HEAD `6161d682e1eeaf81c9e83acad523af7c5b688b45`. Offline review integration only. Existing reconciliation services were reused without modification; no database connection, SQL execution or data action occurred.

Private output: `C:\laragon\private\fastware-2025-review\position-integration-20261022-054529\HR2025-position-crosswalk-integrated.xlsx`.

The version contains eight populated worksheets, eight text-safe CSV counterparts, `crosswalk.pending.json`, `crosswalk.proposed.pending.json`, the actual offline dry-run, provenance/verification records, README and checksums. `latest-position-integration.json` in the private parent points to this version. Internal earlier passes are marked superseded. The delivered `export-20261022-042509` remains unchanged.

## Input actually inspected

The requested reference was initially absent from `references/`. Local copies existed in Downloads and `database/data/`; their binary fingerprints differ, but the complete raw sheet/cell-content comparison was identical. Copied the Downloads version, without modifying either original, to the approved private `references/OK-ADASI_Mapping_JobPosition_Section_Dept.xlsx`.

- Private reference: **26,199 bytes**, SHA-256 `d1d1064710c44f1373be6233827b449fce7c8fcb104726f21fd8e8ac729654a1`.
- Existing repository reference: **26,195 bytes**, SHA-256 `14353218fedb5982c1729e4aafbe4e7595f09406e44833f792d7a8b14240e1c9`.
- Legacy SQL: `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8`.
- Target SQL: `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`.

| Reference worksheet | Data coverage | Period evidence |
| --- | ---: | --- |
| `Mapping (OK)` | 94 position/name-label rows; section from position/user data and named head/department context | **Effective period UNVERIFIED** |
| `Update Section & Dept` | 15 section/department rows; four rows explicitly contain division values | Title explicitly states **ACTUAL 2026** |

Six section cells contain formulas. None was evaluated; cached values are flagged unverified and excluded from alias section-context corroboration. Blank division values were not filled. File timestamps are not organization-validity dates. The workbook does **not** independently establish the 2025 organization or historical employee identity.

There are seven exact legacy-label anchors and 43 exact target-label anchors in workbook position rows. Observed name-label groups: zero with multiple distinct position labels, four Section Head label groups spanning multiple section labels, and five Department Head label groups spanning multiple section labels. These are workbook observations, not verified unique employees, effective assignments or reporting permissions. Combined roles are not split/merged; Division Head relationships are not documented. No PR 1 graph or current assignment was rewritten.

## Crosswalk and actual impact

| Position classification | Labels |
| --- | ---: |
| `EXACT_NAME_CANDIDATE` | 6 |
| `WORKBOOK_ALIAS_CANDIDATE` | 11 |
| `AMBIGUOUS` | 8 |
| `UNMATCHED` | 7 |
| `HISTORICAL_EVIDENCE_REQUIRED` | 14 |
| **Total / approved** | **46 / 0** |

The six exact-name candidates comprise the previous five whitespace/case matches plus one match under conservative punctuation normalization. Three exact-name candidates lack full section/department corroboration. All require HR review.

The eleven alias candidates are **inferred, target-label-only workbook-context candidates**, not explicit signed old/new aliases. Each has identifiable workbook row provenance, literal section/department labels matching an internally consistent target catalog context, and a conservative token-order/common-abbreviation/operator-word hypothesis. The workbook corroborates the target context; source historical equivalence remains unverified. Head/leader/staff words are not discarded to force equivalence. Prior unapproved lexical suggestions remain visible separately, including eight unresolved ambiguous cases. Matching organizational IDs across databases is never used as evidence.

Assessment coverage: **135/281** have a uniquely proposed position and still require review; **146** remain position-unresolved. **122** combine a position candidate with a unique NPK identity candidate. The eleven alias proposals potentially affect **102 assessments**, all with unique NPK identity candidates. Overall identity status remains **264 candidates / 17 unresolved**, with no name/numeric-ID fallback.

All **281 source rows remain QUARANTINE**, with original payloads, timestamps, scores/zeros and NULL totals preserved. All 44 original repeated-component memberships remain recorded. Eight rows participate in the restricted position-only projection; zero newly participating rows were detected in that partial projection. This does not resolve the remaining repetitions or represent fully mapped target keys. Ninety-two source rows have possible target component-key conflicts under unapproved candidate pools; these are investigation signals, not established event matches, duplicates or overwrite decisions. Full uniqueness/event validation remains blocked by semantic crosswalks. All 320 target assessments remain unchanged.

Competency review covers **229 available referenced definitions**: **61** have a context pool under the proposed positions; **168** have no pool under this narrower evidence threshold. Zero exact contextual definition-name matches, zero ambiguous exact-name matches, and **zero newly discovered target-context pairs** relative to the earlier broad unapproved triage pool were found. This is narrower review coverage, not loss/deletion of source definitions. All 229 still require category/rubric, definition/version and standard approval; five missing source definitions remain blocked in the existing private exception queue. No proposed-pool definition was observed among the inspected protected-period assessment/training consumers; this does not establish absence of other shared consumers or authorize master changes.

## Verification and remaining decisions

- Eight required output worksheets have **46 / 56 / 109 / 46 / 281 / 229 / 556 / 42** data rows, respectively: Legacy Positions, Target Positions, Workbook Evidence, Proposed Crosswalk, Assessment Impact, Competency Impact, HR DBA Review, Summary.
- Read-back verified **32,304 cells** against the generated data/CSVs; nonempty cells are explicit literal strings with text format/quote prefix. Output formulas, hyperlinks, macros and external workbook links: **0**. CSV values have one documented transport apostrophe on every nonempty data cell. All **7,784** decision/reviewer/evidence cells checked remained blank. Independent verification checked exact preservation of all 281 source payloads/NULL totals and actual alias row/context provenance.
- `crosswalk.pending.json` retains empty lists. The separate compatible proposed input has **17 entries**, each `decision=PENDING` with blank HR/DBA review fields. No unsupported JSON fields or engine changes were introduced. Actual offline runs with both inputs produce identical aggregate JSON, SHA-256 `022a4a58eda59b31ec4c12ad0297418269bb60b84482dbb5b9aad43b84262934`: **0 approved mappings, 0 established assessment event matches, 0 safe insert candidates**. The dry-run is **not meaningfully unblocked** for data actions.
- Checked owner-only NTFS rules across **149 private entries**, including inherited new folders/files: zero invalid ACLs or private reparse points. Refreshed web-root/transitive scan: **64,345 directories**, 12 links, zero unsafe paths/errors. Existing web-configuration fingerprints were unchanged; no additional PHP/Node/Python static server was identified. Canary requests returned 404/400. This is point-in-time local isolation verification, not a penetration test or future-configuration guarantee. Private security/independent-validation records are stored in the approved parent.
- Verified **51 file checksums** across the new package and previous delivered package; immutable sources and the delivered workbook were unchanged. PHP syntax check passed for the private integration script. Existing offline regression command `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Hr2025ReconciliationTest.php --do-not-cache-result`: **32 tests / 114 assertions PASS**. No DB-backed, staging, production or browser checks were run.
- Privacy deviation: an initial tool preview inadvertently displayed several identity cells. This was acknowledged; subsequent output was limited to metadata/aggregates. No identity values or private row material were copied into this repository report. The transcript exposure cannot be claimed absent or undone.

HR must validate historical 2025 position applicability, target-only alias hypotheses, organization/version differences, unresolved identities, repeated grouping, missing definitions and status/lock/NULL/zero semantics. DBA must validate complete unique-key/event identity and shared/cross-year/history/attachment dependencies plus backup/restore evidence. All current assignments, PR 1 hierarchy, PR 3 UI, PR 4 Sales behavior, 934 target training records and protected 2026/2027 data remain untouched. INSERT_APPROVED / UPDATE_APPROVED / DELETE_APPROVED remain **0 / 0 / 0**; future actions need separate exact signed authorization.

Workbook SHA-256: `bcc08e58a9f125721a7dfdf1ecf4255ae3c2c3b4fac25d4eabf64a8b02c3c56f`. Checksums manifest SHA-256: `e56b5b1336183bce281df98a9a35501317436b645e12664c9893de9ef15b4f82`.

Final fingerprint comparison verified all **4,361 pre-existing repository files** checked were byte-identical; only this aggregate report was added. Scoped report whitespace check passed. No reconciliation/application code, original workbook, SQL snapshot, migration, current mapping, historical record or unrelated dirty hunk was edited. No commit, push or deployment occurred.

**PR 2 HISTORICAL POSITION CROSSWALK INTEGRATION COMPLETE — PENDING HR/DBA VALIDATION**

**STOP — PENDING USER REVIEW.**
