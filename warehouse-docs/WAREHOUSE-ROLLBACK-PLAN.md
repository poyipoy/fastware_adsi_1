# Warehouse Consumable — Rollback Plan

## Application rollback

1. Stop the release and preserve the execution log, test output, and failing transaction number.
2. Roll back the application release to the last known-good artifact without deleting Warehouse transaction history.
3. Keep the Warehouse tables when the previous application can safely ignore them; do not delete data as an incident response.

## Schema rollback (testing rehearsal only)

The five Warehouse migrations may be rolled back in reverse dependency order only on a database whose name ends in `_testing`. Re-run the migration and the targeted Warehouse suite after the rehearsal. Never use this destructive sequence on production.

## Data and operational recovery

- If a movement is wrong, use the authorized reversal workflow; do not delete or edit the original row.
- If the current stock is inconsistent, stop further mutations, capture the item and transaction evidence, and use a restricted adjustment after the incident owner approves the reason.
- Restore from the normal database backup only under the application's approved database recovery procedure.

## Resume

Update `warehouse-docs/WAREHOUSE-EXECUTION-LOG.md` with the failed gate, baseline comparison, decision, and next mission. Resume from the first mission not marked `DONE`; do not infer progress from file timestamps.
