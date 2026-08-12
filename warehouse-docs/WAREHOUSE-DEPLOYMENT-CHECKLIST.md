# Warehouse Consumable — Deployment Checklist

## Current release snapshot — 2026-08-12

- Source refactor: `refactor/warehouse-structure-cleanup` at `9bf70d5`.
- Six structural phases are complete; the focused Warehouse gate is `102` tests and `685` assertions.
- Module structure is now `51` files in `10` folders. The `warehouse.*` route inventory remains `24` routes.
- `warehouse-consumable/` source is not part of this refactor artifact. Its documentation is synchronized, but its code must be regenerated/redeployed separately if it is used for deployment.

## Pre-deployment

- [ ] Confirm the source commit and review `warehouse-docs/WAREHOUSE-EXECUTION-LOG.md`.
- [ ] Take the normal application/database backup and confirm the restore path.
- [ ] Confirm production environment values, especially `APP_ENV`, database, timezone, and Warehouse permission configuration.
- [ ] Confirm no import or non-goal feature is included.
- [ ] Confirm the NPK and item-barcode scanner sends its expected value followed by Enter/Tab; record characteristics without storing raw values.

## Migration and access

- [ ] Run only the reviewed Warehouse migrations through the controlled deployment process. Keep `mst_wh_user_cards` as an inert legacy table; do not drop it during this release.
- [ ] Verify table/foreign-key/index creation and active organization assignments for the three authorized departments.
- [ ] Do not run migration or destructive setup against production from a local test command.

## Smoke test

- [ ] Administrator plus one active user from each authorized department sees all Warehouse actions; a user outside those departments sees none and receives 403 on direct URLs.
- [ ] Register one test consumable and verify that no Pemetaan ID Karyawan page, link, or route is exposed.
- [ ] Scan an active numeric NPK directly and confirm name, NPK, and section; test unknown, inactive, unauthorized, and ambiguous NPK rejection.
- [ ] Perform a Stock In, Stock Out, insufficient-stock rejection, and idempotent replay.
- [ ] Confirm dashboard KPI, low-stock state, history snapshot, reversal, adjustment, and filtered XLSX export.
- [ ] Confirm raw scan input is not persisted in verification logs and is not echoed in error responses.
- [ ] Run the targeted Warehouse suite, `php artisan route:list --name=warehouse`, `php artisan view:cache`, and targeted Pint checks.

## Rollback trigger

Stop deployment if any Warehouse acceptance criterion fails, a new Warehouse regression appears, migration verification differs from the reviewed schema, or scanner evidence conflicts with the normalization contract. Follow `WAREHOUSE-ROLLBACK-PLAN.md`.
