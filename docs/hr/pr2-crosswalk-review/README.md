# HR 2025 — editable crosswalk review package

22 October 2026. **NON-SENSITIVE TEMPLATE / AGGREGATE EDITION.** No restricted export destination has been authorized. All individualized source/target candidate records and employee/transaction queues are therefore **NOT EXPORTED**. Candidate analysis was performed in memory; no mappings or data actions are approved.

## Files to use

| File | Intended use | Current contents |
| --- | --- | --- |
| `position_crosswalk.csv` | Historical label/organization equivalence, proposed target reference and HR/DBA review | Header only; 46 individualized inventory/candidate rows withheld |
| `tc_master_crosswalk.csv` | Technical Competency definitions | Header only; 133 source definitions withheld |
| `soft_skill_master_crosswalk.csv` | Soft Skills definitions | Header only; 75 source definitions withheld |
| `additional_master_crosswalk.csv` | Additional definitions | Header only; 21 source definitions withheld |
| `category_crosswalk.csv` | Four-score rubric meaning and category version | Header only; 3 candidate pairs require independent review |
| `missing_definitions.csv` | Authentic recovery evidence for missing definitions | Header only; 5 distinct source definitions missing, affecting 9 assessments |
| `assessment_exceptions.csv` | Private identity/repeated-key/score/status/lineage queue | Header only; issues overlap and no duplicate survivor is selected |
| `assessment_identity.csv` | Exact source-target event equivalence, when independently established | Header only; current established event matches = 0 |
| `training_investigation.csv` | Per-record provenance and dependency review | Header only; 934 private records withheld |
| `status_semantics.csv` | HR/DBA definitions for source status 1/2/3 | Three cohort-frequency rows; proposed status/lock fields and decisions blank |
| `position_coverage.csv` | Label-level diagnostic coverage | Aggregate counts only; no position labels/references |
| `master_coverage.csv` | Per-type diagnostic coverage | Aggregate counts only; no definition references/payloads |
| `assessment_issue_summary.csv` | Prioritize exceptions | Aggregate counts and required evidence; counts overlap |
| `training_cohorts.csv` | Prioritize T25-A/B/C/D | Four aggregate rows, evidence requests and cross-year risk |
| `crosswalk_field_map.csv` | Map review columns to supported JSON fields | Schema instructions only; not a converter or approval engine |
| `crosswalk.pending.json` | Valid input for the existing offline command | Both immutable hashes; all mapping/status/event arrays empty |

CSV encoding is UTF-8 with BOM, comma delimiter and CRLF, suitable for Excel's CSV import. Import private locator/ID columns as **Text**. These CSVs are review documents, **not direct command input**. Never pass a CSV path to `--crosswalk`.

## Review steps

1. First authorize a restricted local destination **outside the tracked repository**, with HR/DBA-only access and a designated owner. Do not populate employee-identifying locators or transaction details into these repository templates. Copy the templates to the authorized destination before any private population.
2. HR reviews historical position equivalence using actual historical organization evidence; target department/section IDs alone do not prove it. Exact labels and token overlap remain suggestions. Current assignments and Sales approvals must not be changed to make a mapping fit.
3. Review each available master by competency type, historical position, category/rubric, definition/description version and standard. A context pool is not a proposed equivalent definition. Missing definitions/category/standard are not defaulted or manufactured. Record disagreements and the evidence still needed.
4. Use the exception/training queues separately. Preserve every source row, original timestamps, NULL versus zero, grouping, and 2026/2027 dependencies. Do not choose a survivor, infer dummy provenance, generate replacement training, or turn a bad link into a relink/delete instruction.
5. HR and DBA fill their own decision/evidence fields. `PENDING`, `RETURN`, `REJECT`, or a proposed target never counts as approval. This package does not fill any approval automatically. Signed evidence authenticity must be verified independently; nonempty reference text is not signature validation.
6. Convert only explicitly reviewed mappings into the supported JSON structure **privately**, using `crosswalk_field_map.csv`. Conversion is manual in this package; no approval-generating converter is provided. Preserve both snapshot hashes and record a review revision. Current pending JSON has zero entries and must remain unapproved.

Use review statuses `PROPOSED`, `AMBIGUOUS`, `UNMATCHED`, `PENDING`, `RETURN`, `REJECT`, or `HR_APPROVED_DBA_PENDING` as appropriate. HR approval alone is not an accepted mapping: DBA review and independently verified evidence are also required before a later approved JSON entry can be prepared. Preserve raw evidence separately; import spreadsheet fields as text and neutralize spreadsheet formula interpretation when an authorized private exporter is later used.

## JSON compatibility details

- Positions: `source_label`, `target_id` under `positions[]`.
- Categories: `source_id`, `target_id`, and explicit `rubric_difference_reviewed` when applicable under `categories[]`.
- Masters: `source_id`, `target_id` under `masters.tc[]`, `masters.sk[]`, `masters.ad[]`. CSV `description_difference_reviewed` corresponds to JSON **`definition_difference_reviewed`**; preserve `standard_difference_reviewed`. Required type/position/category context must be reviewed separately.
- Statuses: `source_status`, `target_status`, **JSON boolean** `is_locked`, `null_total_semantics="PRESERVE"`, and explicit boolean `zero_score_reviewed` / `modifier_reviewed` where relevant. Blank booleans/statuses must not be guessed.
- Existing events: `source_id`, `target_id` under `assessment_identities[]`, only with actual event-equivalence evidence. Reused numeric PK is not evidence.
- Reviewed entries require `decision`, `hr_review`, `dba_review`, `evidence`. Only a later genuine, independently verified HR/DBA decision may populate `decision="APPROVED"`; this is mapping review, never data-write approval. No such entry exists in the current package.
- Identity exceptions, duplicate-survivor/grouping decisions, missing-definition recovery and training decisions **are not consumed directly by the current crosswalk JSON**. Keep them in the private review queue; any future technical support needs its own scoped authorization. Do not add unsupported root keys or edit the immutable dumps to bypass these blockers.

## Run the current pending input

From the repository root:

```powershell
php scripts/hr-2025-dry-run.php '--legacy=dms_adasi_rev1.sql' '--target=dms_adasi_rev1 (12).sql' '--year=2025' '--crosswalk=docs/hr/pr2-crosswalk-review/crosswalk.pending.json'
```

This is offline and read-only. It does not bootstrap Laravel, connect to staging/production, approve data changes, or write records. No apply/delete mode exists. Current expected eligibility: **0 safe insert candidates**, **281 source QUARANTINE**, **934 training under investigation**, and all three write-approval counters zero.

See [the concise coverage/blocker report](../PR2-2025-CROSSWALK-REVIEW-REPORT.md). Private candidate export remains blocked until a destination is explicitly authorized; completion of this fallback package does not mean individualized queues have been delivered or semantic mapping has been resolved.
