# PR 2 — semi-automatic mapping and exception review

26 October 2026. Branch `main`, HEAD `6161d682e1eeaf81c9e83acad523af7c5b688b45`. The existing readiness package, immutable SQL snapshots, ADASI reference and PR 2 reconciliation engine were reused. No application behavior or migration engine was changed.

Private delivery: `C:\laragon\private\fastware-2025-review\semi-auto-mapping-20261026-015631\HR2025-semi-automatic-mapping-review.xlsx`.

The workbook contains the six requested sheets: Auto Mapping Summary, High Confidence Candidates, Review Required, Unresolved Exceptions, Grouped Approval Review and Assessment Impact. Detailed candidate pairs, reusable linguistic/rule definitions, frozen group membership, related exceptions, original assessment payloads, reproducibility checks and usage instructions are private JSON/Python/PHP artifacts beside it. These contain actual source/target records, not empty templates. The previous review package remains unchanged.

## Source-level result

Counts below are distinct source records. Ambiguity is an overlapping diagnostic, not an additional confidence tier.

| Source scope | HIGH CONFIDENCE | REVIEW REQUIRED | UNRESOLVED | Total |
| --- | ---: | ---: | ---: | ---: |
| Position labels | 3 | 25 | 18 | 46 |
| Technical Competency | 0 | 8 | 125 | 133 |
| Soft Skills | 0 | 28 | 47 | 75 |
| Additional | 0 | 0 | 21 | 21 |
| **Total available records** | **3** | **61** | **211** | **275** |

- **530 actual candidate comparisons** were evaluated and retained privately with source/target references, SQL/ADASI context, name/concept evidence, descriptions, standards, ranking dimensions, alternatives and rejection/uncertainty reasons.
- **19 sources** have close competing alternatives, including unsupported cases. Five genuinely missing source definitions are separate exceptions, outside the 229 available definitions.
- All original six exact-name and eleven workbook-alias proposals are preserved as proposals. Additional deterministic position suggestions do not create/rename target positions. The pending engine input has **26 unique position suggestions, 46 master candidate pairs and 3 rubric pairs**, all PENDING with blank HR/DBA review/evidence fields.

HIGH CONFIDENCE means **static naming/organizational equivalence**, never confirmed historical assignment or migration eligibility. All three high records are positions. A high competency would additionally require matching type/position/rubric, nonempty equal definitions, equal standard, unique semantic target and absence of protected/conflicting consumers. **No master met those requirements.** Missing descriptions were not treated as equal definitions. All position applicability in 2025 remains unverified; the ADASI reference includes current-2026 information.

## Less repeated human work

The private reusable evaluator creates **183 exact-basis rule groups** for all 275 records. **39 multi-member groups cover 131 records**. Of these, **12 reviewable groups cover 47 supported suggestions**; a further **17 supported suggestions are singleton cases**. Thus the shared basis of 64 supported suggestions can be reviewed as 12 scoped groups plus 17 individual bases, before historical/record-specific exceptions. The other multi-member groups organize common unresolved evidence and do not establish equivalence.

Each group shows representative names/descriptions/standards, its exact members, all affected assessments, and related cases excluded because their guards differ. Membership requires the same basis, entity, immutable snapshot/version, explicit member allowlist, source/target/candidate fingerprints and full record-material fingerprint. New records, changed text/standards, wrong snapshots and duplicate anchors fail closed. Different definitions, standards, context or alternatives do not inherit coverage from a similar-looking rule.

`preview_rule` provides coverage/exclusion evidence only. It does not authenticate signatures, approve mappings, propagate an approval or execute a data action—even if someone edits a decision string to APPROVED. No rule has been independently verified by HR/DBA. Reviewer decisions and evidence attestations remain blank throughout the workbook and rule files. No approved crosswalk or write mode was generated.

## Semantic method and genuine exceptions

Versioned conservative bilingual phrases/tokens identify explainable concept candidates. Broader topic families generate review hypotheses only; they do not equate broader/narrower skills. Negation, proficiency/qualifier differences, hierarchy level, region numbers, competency type, position, rubric, standard and definition are checked separately. Scores are deterministic ranking indices, not calibrated probabilities, and cannot grant approval.

The principal remaining work is evidence resolution, not manually pairing every row:

- **18 positions / 193 masters** remain unresolved. **84 unresolved masters depend on unresolved source positions**; resolving those position rules can address a shared dependency before individual master review.
- **5 missing source definitions** need authentic recovery. Missing/different target descriptions, differing standards and rubric/version or scope differences need HR competency-owner evidence. No description or standard was invented or overwritten.
- **18 proposed master source records converge toward shared targets** and require specific integrity review. Shared consumers and alternative definitions remain explicit exceptions.
- **17 employee-identity exceptions, 44 original repeated-component memberships and 6 outside-catalog assessment records** remain blocked, alongside historical status/locking/modifier semantics and exact assessment-event identity. NPK matching never uses source user IDs or employee-name fallback.

## Assessment impact

All **281 source assessments** retain their payloads, timestamps, scores/zeros and NULL totals; business year remains **2025 USER_ATTESTATION**. All 281 remain QUARANTINE. The original **73 context-complete assessments appear first**, followed by the rest of the original 122-priority cohort and remaining records.

- **21 assessments**, all within the 73-priority cohort, have semantic/context suggestion support for every referenced component and a unique employee/position candidate. **19** have unique suggestions for all components. Neither number is a validated full crosswalk or safe-insert count.
- **171 assessments** have at least one supported position or component suggestion. This broader partial-benefit measure must not be confused with all-component coverage.
- The original target-key risk registry still flags **92 assessments**. The narrower semi-automatic hypothesis projection flags **47** target-conflict assessments, including **16** of the original 73 priority rows. A smaller hypothetical pool does not resolve the remaining baseline risks.
- The new candidate-key projection has **44 prospective source collision memberships**, **10** overlapping the original repeated cohort. These are not automatically duplicates, and no survivor was selected. The original 44 repeated memberships remain independently preserved.

## Actual verification

- SQL SHA-256 pins remain legacy `e697a99f80994ff8987b0a325f6b54ed7dc0f9767a2226f617afaeb7a1e9c3d8` and target `996ef43a253969d992f29d917cdc021df353c6c89f8081a005b4f8787b1ea3db`. All **56** previous package files remain byte-identical. Independent parsing confirmed all 281 original source payloads/NULL totals and protected snapshot counts: 7 assessment-2026, 934 training-2025 and 18 training-2027.
- Workbook read-back: **21,789 cells**, **4,464 blank human-review fields**, **1,488 decision-dropdown cells** verified. Formula cells, hyperlinks, macros and external links: **0**. Database-derived content is explicit text; no employee names/NPK values or individual rows were printed in terminal/chat previews.
- **12 focused rule-safety tests PASS**: bilingual suggestions, semantic/qualifier distinctions, negation, role/region boundaries, exact group coverage, nonmember/material drift, snapshot/version/duplicate-anchor rejection, no approval from decision text, high-confidence gates and real cohort preservation. Existing pure offline regression suite: **32 tests / 114 assertions PASS**.
- A second full analysis produced byte-identical results for **10 core candidate/rule/workbook-data/pending files**. An initial aggregate-only dictionary-order mismatch was corrected by deterministic exception-key ordering; candidate and rule records were already identical.
- Actual runs through the existing `Hr2025DryRun` service, without mappings and with the new pending input, produced identical aggregate SHA-256 `022a4a58eda59b31ec4c12ad0297418269bb60b84482dbb5b9aad43b84262934`: **0 reviewed mappings / 0 safe INSERT_CANDIDATE / 281 QUARANTINE / 0 data approvals / 0 DB connections / 0 writes**.
- Owner-only NTFS ACLs and outside-webroot/Git storage were checked. The completed transitive scan covered **67,332 directories / 13 links**, with zero unsafe paths or errors; all 32 web-configuration fingerprints were unchanged and no unexpected static server was found. Security is point-in-time local evidence. Source SQL, previous workbooks, current assignments, all training/protected data and PR 1/PR 3/PR 4 remain untouched. Desktop Excel interaction, live schema and staging/production were not tested.

Only this aggregate report is added in the repository. Reusable suggestion/rule code and detailed outputs stay in the approved private version. No migration tooling rebuild, synthetic training/history, data cleanup, apply/delete mode, commit, push or deployment occurred.

**SEMI-AUTOMATIC MAPPING COMPLETE — PENDING EXCEPTION REVIEW.**
