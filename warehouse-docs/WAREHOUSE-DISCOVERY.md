# Warehouse Consumable Discovery

Status: `DONE WITH DEC-006 EVIDENCE DEVIATION`

## Source of truth

- Mission source: `Warehouse Consumable docs/MISSION-WAREHOUSE-CONSUMABLE.md`
- SHA-256: `670EE9E6DBAA1483B4CF6AD8F4682B9B132E0D4220FEEC1008A1A5FDEC259FC6`
- Branch: `main`
- HEAD at discovery: `cadb51058401bf7f092a1443d550b80d7b46e1b2`
- Worktree: dirty; 325 status entries were present before Warehouse changes
- Execution decision: current dirty worktree remains authoritative; no reset, checkout, commit, or push

## Runtime baseline

- Laravel: `10.50.0`
- PHP: `8.2.30`
- Composer: `2.6.0`
- MySQL: `8.0.30`
- Application timezone default: `Asia/Jakarta`
- Bootstrap: local `5.3.2`
- Vite: `5.x` existing pipeline
- Excel: `maatwebsite/excel 3.1.55`

## Existing application facts

- `routes/web.php` contains 730 routes and no `warehouse.*` route.
- The active layout is `resources/views/layout.blade.php`; its navbar is a Bootstrap top dropdown and exposes style/script stacks.
- `App\\Models\\User` has `id`, `name`, `npk`, `section`, `role_id`, and `is_active`; live `npk` is `INT`.
- Existing login semantics are inverse of a normal boolean: `users.is_active=0` is login-enabled and `1` is inactive.
- `users.npk` is the canonical numeric employee-scan identity. Duplicate groups are handled deterministically: exactly one login-enabled Administrator wins; every other ambiguous group is rejected.
- `LayoutMenuController` and `MenuAccessStorage` are dormant for the active Blade navbar. Warehouse uses a module access service plus Gates.
- No consumable ledger, Warehouse card mapping, barcode resolver, or stock movement domain exists.
- `spareparts`, `mst_material`, and `outstanding_materials` are separate domains and are not reused or modified.
- The live database reports 58 ran and 19 pending migrations. Blanket migration is prohibited.

## Baseline evidence

| Check | Result | Gate use |
|---|---|---|
| `php artisan route:list` | Exit 0; 730 routes | Minimum regression baseline |
| `php artisan view:cache` | Exit 0 | Minimum regression baseline |
| Unit suite | 95 passed, 383 assertions | Historical context only under DEC-001 |
| Non-KM Feature subset | 26 passed, 213 assertions | Historical context only under DEC-001 |
| Full `php artisan test` | Timeout after approximately 904 seconds; no summary | Explicitly waived as development gate by DEC-001 |
| KM broad suite | Same timeout behavior; orphan runners were stopped | Not a Warehouse gate |

## Access decision

- Administrator: role ID `1`.
- Authorized active departments: `Logistic & Warehouse`, `Production`, and `PDCA, Inventory, Procurement & IT` (including IT Staff).
- Login-enabled employee value: `users.is_active=0`.
- Warehouse access: Administrator or at least one current active assignment through an active position and active authorized department.
- Every eligible user receives all Warehouse abilities and may verify both Stock In and Stock Out.
- Employee scanner identity: direct numeric lookup against `users.npk`; the legacy `mst_wh_user_cards` table is runtime-inert.

## Scan evidence gate

The approved barcode PDF establishes numeric employee scans that correspond to NPK and uppercase alphanumeric/hyphen item identities. The operator confirmed a valid scanner sends `Enter`; `Tab` remains accepted as a terminator. A physical scanner smoke test on the target workstation remains a release gate.

Raw scanned values are not written to this document or the execution log. Item identity keeps exact Item Code semantics after whitespace/terminator cleanup. Employee identity must be positive numeric input and is normalized to the integer representation used by `users.npk`, so leading zeroes do not create a separate identity. If the physical scanner emits a materially different payload, stop deployment and obtain an owner decision.

## Safety decisions

- No migration or production/local application data mutation was performed.
- Warehouse destructive tests must use `APP_ENV=testing` and a database ending `_testing`.
- Import is excluded from this implementation run.
- No new React, Vue, Tailwind, Alpine, jQuery, CDN, framework, or global navigation refactor is in scope.

## Additional local-artifact audit

The available SQL dumps contain generic `Consumable` planning records and improvement notes such as `Warehouse Consumable Deltamas` and `Digitalisasi Barcode Material`, but no employee card scan value, item barcode value, card mapping table, or barcode column that can establish a scan format. These records are contextual evidence only and were not imported or used as Warehouse master data.

## Current post-refactor snapshot — 2026-08-12

- Current branch: `refactor/warehouse-structure-cleanup`.
- Current HEAD: `9bf70d5` after six structural refactor commits.
- Current worktree: clean.
- Warehouse route inventory: `24` routes; `routes/web.php` remains unchanged.
- Warehouse module structure: `51` files in `10` folders after Fase 1–6.
- Final focused gate: `102` Warehouse tests and `685` assertions passed.
- The refactor changed file placement and Composer autoload registration only; controller/service behavior, validation, routes, schema, migration, and application data were preserved.
- The deployment package `warehouse-consumable/` was intentionally not regenerated. Its documentation is synchronized separately, while its source code remains the pre-refactor artifact until a manual redeploy is approved.
