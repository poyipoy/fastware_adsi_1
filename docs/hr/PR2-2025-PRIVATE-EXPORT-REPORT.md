# PR 2 — actual private crosswalk export

22 October 2026. Branch `main`, HEAD `6161d682e1eeaf81c9e83acad523af7c5b688b45`. Existing dirty worktree preserved. This report contains aggregate evidence only; individualized review data is outside `www` and Git.

**Delivered:** `C:\laragon\private\fastware-2025-review\export-20261022-042509`.

Open `HR2025-crosswalk-review.xlsx` for editable review. The directory also contains 15 populated CSVs, 17 byte-identical copies of existing package files under `templates/`, `README-PRIVATE.txt`, the unapproved offline dry-run, `validation.json`, and `checksums.json`. `latest-export.json` in the private parent identifies this delivered version. An internal first-pass export is explicitly marked `SUPERSEDED.txt`; use the delivered version above. No HR/DBA approval or executable action manifest was generated.

| Actual review data | Rows, excluding header |
| --- | ---: |
| `position_crosswalk.csv` | 46 normalized legacy labels, referencing 99 source position rows |
| `tc_master_crosswalk.csv` | 133 |
| `soft_skill_master_crosswalk.csv` | 75 |
| `additional_master_crosswalk.csv` | 21 |
| `category_crosswalk.csv` | 3 rubric-signature candidate pairs |
| `competency_candidate_details.csv` | 945 target context-pool comparisons; **not** 945 approved mappings |
| `assessment_exceptions.csv` | 281, all QUARANTINE |
| `legacy_assessments.csv` / `assessment_identity.csv` | 281 each; preserved source payload / event-review queue |
| `identity_exceptions.csv` | 17, a subset of the 281 |
| `repeated_component_rows.csv` | 44, an overlapping subset of the 281 |
| `missing_definitions.csv` | 5 distinct missing definitions |
| `target_assessments_2025.csv` | 320 preserved target assessment review records |
| `training_investigation.csv` | 934, all QUARANTINE |
| `target_position_catalog.csv` | 56 |

Fifteen CSVs contain **3,442 review rows**, including overlapping queues and comparison rows; these are not counts of distinct transactions. Training partition remains **T25-A 490 / T25-B 433 / T25-C 10 / T25-D 1**. Twelve training-2025 references reach seven protected assessment-2026 parents. Eighteen training-2027 records, protected participants, current accounts/assignments, shared masters and unrelated modules remain untouched.

## Security and export validation

- Created the approved private parent/destination with protected NTFS ACLs: inheritance disabled, one Allow FullControl entry for the verified current Windows owner only. No additional HR/DBA, Users, Everyone, administrator-group or service-account grant was added. Individual HR/DBA sharing still requires an authorized access decision. Verified descendant ownership/ACLs and absence of private reparse points after export.
- Checked Apache process/configuration, effective includes, document roots, aliases and root-directory denial; none maps the private directory or its ancestors. Configuration fingerprints were unchanged through verification and no configuration file was newer than Apache startup. Nginx was not running; its inspected static roots exclude this location. No PHP/Node/Python static-server listener was identified. No server configuration was changed.
- Scanned **64,015 directories**, **12 reparse points**, and **2,782 transitive target directories**, with zero scan errors or paths into the private destination. No Git ancestor exists for the private folder. Five Apache loopback canary probes returned **404/404/404/400/400**. The separate ASUS loopback application returned its own HTTP 200 responses for two candidate paths without the canary content; this is not evidence of a static alias or a general security audit of that application.
- These are point-in-time local configuration, filesystem and URL checks, not penetration testing or a guarantee after configuration/ACL changes. Detailed security evidence stays in private `security-verification.json`, `export-security-final.json` and `independent-export-validation.json`.
- XLSX: **112,397 cells** checked; nonempty values explicitly string typed, text formatted and quote-prefixed; zero formulas, hyperlinks, macros or external workbook links. CSV: UTF-8 BOM/CRLF, with one transport apostrophe prefixed to every nonempty data value, including IDs/NPK, to preserve text and neutralize formula interpretation. Prefer XLSX for editing; CSV prefix handling is documented privately. No source value was normalized into an approval.
- Independent read-back compared **111,937 data cells** between CSV and XLSX. All **281 source payloads** and **1,254 target payloads** (320 assessments + 934 training) matched parsed immutable snapshots exactly. Original timestamps, leading-zero NPK text, zero scores and all 281 NULL totals were preserved. Private JSON payload cells retain actual JSON nulls; display `<NULL>` markers are explained in the private README.
- All **17 template copies** are byte-identical. All **15,525 decision/review-reference cells** checked remained blank. Verified **36 checksummed package files** against `checksums.json`; the manifest's own hash is pinned separately in `latest-export.json` and the private final security record.

Delivered workbook SHA-256: `235e7a4d67309fefefd7c74286750c4f8e2727e1ea1e1dc02d8ef765009dca18`.

Checksums manifest SHA-256: `7487d23bcbb56434bb04387f420721f907a1b6a87bf8c9fdaadb70ea0d5fdfec`.

## Remaining HR/DBA decisions

Position triage remains **5 exact-label suggestions / 25 token suggestions / 9 ambiguous / 7 unmatched**, none approved. Of **229 available referenced definitions**, one has a token suggestion, 112 have context pools only and 116 lack a usable context suggestion. Labels/pools do not establish historical organization, category/version, definition or standard equivalence. The exported comparison rows provide actual target context for this review.

HR/DBA must resolve the 17 identities, 44 repeated-key memberships without automatic survivors, five missing definitions, historical status/locking and NULL/zero semantics, and exact source-to-target business-event equivalence. Training owners must independently establish provenance; bad links, coherent links, source absence and user suspicion do not prove dummy data. DBA must complete runtime schema, unloaded-table, attachment/history/shared-consumer and cross-year dependency closure, including the 723 history rows without reliable assessment/period lineage. Backup/restore and exact signed record-level action approvals remain separate prerequisites for any future execution.

The existing offline engine was reused with the copied **empty/unapproved** crosswalk. Its aggregate JSON SHA-256 remains `022a4a58eda59b31ec4c12ad0297418269bb60b84482dbb5b9aad43b84262934`: **264 identity candidates / 17 unresolved, zero reviewed semantic mappings, zero established event matches, zero safe insert candidates, 281 source QUARANTINE**. Its built-in private-export capability remains disabled; the separately authorized private file exporter created this review package without changing the engine or its input contract. CSV/XLSX files are not direct `--crosswalk` input or approved write manifests.

Source checksums reverified before/after processing:

- Legacy, 2,069,117 bytes: `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8`.
- Target, 4,313,503 bytes: `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`.

Executed PHP syntax check on the private exporter, actual offline export/read-back/checksum/ACL validation, and `php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Unit/Hr2025ReconciliationTest.php --do-not-cache-result`: **32 tests / 114 assertions PASS**. No database-backed tests, staging/production verification, SQL execution or Laravel bootstrap was performed. No historical writes, synthetic training, cleanup, apply/delete mode, commit, push or deploy occurred. INSERT_APPROVED / UPDATE_APPROVED / DELETE_APPROVED remain **0 / 0 / 0**.

Final repository fingerprint comparison: all **4,360 pre-existing files** checked were byte-identical; only this aggregate report was added. Final private ACL sweep checked **88 entries**, with **zero invalid ACLs**. Exporter, security records and actual review data are private. PR 1, PR 3, PR 4 and unrelated dirty work were not edited.

Global `git diff --check` reported three pre-existing whitespace findings in unrelated Outstanding Material/Warehouse hunks; those files were preserved. The new report's whitespace check passed. As a new untracked file, the report does not appear in ordinary `git diff --stat`; nothing was staged.

**PRIVATE CROSSWALK EXPORT COMPLETE — PENDING HR/DBA REVIEW.**

**STOP — PENDING USER REVIEW.**
