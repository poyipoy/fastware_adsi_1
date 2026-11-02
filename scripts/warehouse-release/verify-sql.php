<?php

require __DIR__.'/common.php';
whApp();
$private = json_decode(file_get_contents(storage_path('app/warehouse-release-private/latest.json')), true)['private_directory'];
$report = json_decode(file_get_contents($private.'/report.json'), true);
$export = json_decode(file_get_contents($private.'/export.json'), true);
$source = whPdo('dms_adasi_rev1');
$package = base_path('deploy-warehouse/'.WH_RELEASE);
$stamp = date('Ymd_His');
$created = [];

function fixture(string $name): PDO
{
    global $source, $created;
    whTestDatabase($source, $name);
    $created[] = $name;
    $pdo = whPdo($name);
    foreach (['users', 'migrations', 'mst_departments', 'mst_job_positions', 'user_job_positions'] as $table) {
        $pdo->exec('CREATE TABLE `'.$table.'` LIKE `dms_adasi_rev1`.`'.$table.'`');
        $pdo->exec('INSERT INTO `'.$table.'` SELECT * FROM `dms_adasi_rev1`.`'.$table.'`');
    }
    // Prove user IDs are resolved by NPK, never copied from the local IDs.
    $pdo->exec('UPDATE users SET id=id+1000000 ORDER BY id DESC');
    $pdo->exec('UPDATE user_job_positions SET user_id=user_id+1000000');
    $pdo->exec("DELETE FROM migrations WHERE migration IN ('2026_10_06_000002_create_trs_wh_stock_transaction_machine_usages_table','2026_09_24_000001_create_wh_stock_in_sequences_table')");

    return $pdo;
}

function rejectCall(PDO $pdo, string $call, string $expected): void
{
    try {
        $statement = $pdo->query($call);
        do {
            $statement->fetchAll();
        } while ($statement->nextRowset());
        $statement->closeCursor();
        throw new RuntimeException('Expected SQL rejection did not occur.');
    } catch (PDOException $e) {
        whAssert(str_contains($e->getMessage(), $expected), 'Unexpected SQL rejection.');
    }
}

$badName = 'warehouse_invalid_'.$stamp.'_testing';
$bad = fixture($badName);
$bad->exec('UPDATE users SET npk=1547299 WHERE npk=5472');
whAssert(! whMysql($badName, $package.'/database/01_schema.sql', $private.'/sql-missing-user.log'), 'Missing NPK should reject schema install.');
whAssert(count(array_intersect(WH_TABLES, whTables($bad))) === 0, 'Invalid NPK created partial Warehouse schema.');
$bad->exec('UPDATE users SET npk=5472 WHERE npk=1547299');
$extra = $bad->query('SELECT id FROM users WHERE is_active=0 AND role_id<>1 AND npk NOT IN (5639,5439,5472,5661,5584) LIMIT 1')->fetchColumn();
whAssert($extra !== false, 'No duplicate-NPK fixture candidate.');
$bad->exec('UPDATE users SET npk=5472 WHERE id='.(int) $extra);
rejectCall($bad, 'CALL warehouse_release_20261022_schema()', 'NPK 5472');
whAssert(count(array_intersect(WH_TABLES, whTables($bad))) === 0, 'Ambiguous NPK created Warehouse tables.');
echo "Missing and ambiguous NPK rejected before schema creation.\n";

$name = 'warehouse_install_'.$stamp.'_testing';
$pdo = fixture($name);
$protected = whFingerprint($pdo);
$ledgerBefore = $pdo->query('SELECT * FROM migrations ORDER BY id')->fetchAll();
whAssert(whMysql($name, $package.'/database/00_preflight.sql', $private.'/sql-preflight.log'), 'Preflight failed.');
whAssert(whMysql($name, $package.'/database/01_schema.sql', $private.'/sql-schema.log'), 'Schema install failed; see private log.');
whAssert(count(array_intersect(WH_TABLES, whTables($pdo))) === count(WH_TABLES), 'Schema incomplete.');
// Remove a FK in this fixture to prove master import cannot mark a partial DDL
// installation as migrated. Restore it from the canonical definition afterwards.
$pdo->exec('ALTER TABLE log_wh_verifications DROP FOREIGN KEY log_wh_verifications_user_id_foreign');
whAssert(! whMysql($name, $package.'/database/02_master_access.sql', $private.'/sql-partial-fk.log'), 'Partial schema was accepted.');
whAssert((int) $pdo->query('SELECT COUNT(*) FROM mst_wh_consumables')->fetchColumn() === 0, 'Partial schema imported master.');
$pdo->exec('ALTER TABLE log_wh_verifications ADD CONSTRAINT log_wh_verifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL');
// The rejected routine is intentionally retained. Invoke it after repairing the
// fixture to test rollback, then restore the original procedure only in testing.
$sql = file_get_contents($package.'/database/02_master_access.sql');
$failure = str_replace('warehouse_release_20261022_master', 'warehouse_release_20261022_master_failure', $sql);
$position = strpos($failure, 'INSERT INTO mst_wh_restricted_verifiers');
whAssert($position !== false, 'Cannot inject master rollback rehearsal.');
$failure = substr_replace($failure, "SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='INJECTED_MASTER_FAILURE';\n", $position, 0);
file_put_contents($private.'/master-failure-rehearsal.sql', $failure);
whAssert(! whMysql($name, $private.'/master-failure-rehearsal.sql', $private.'/sql-master-rollback.log'), 'Injected master failure was not rejected.');
foreach (WH_TABLES as $table) {
    whAssert((int) $pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn() === 0, 'Master failure left partial rows.');
}
// This is a rehearsal DB we created above, never the application DB.
$pdo->exec("SET SESSION time_zone='+00:00'");
$call = $pdo->query('CALL warehouse_release_20261022_master()');
do {
    $call->fetchAll();
} while ($call->nextRowset());
$call->closeCursor();
$pdo->exec("SET SESSION time_zone='SYSTEM'");
whAssert(whMysql($name, $package.'/database/03_postcheck.sql', $private.'/sql-postcheck.log'), 'Postcheck failed.');
$installed = whFingerprint($pdo);
foreach ($protected as $table => $value) {
    if ($table !== 'migrations') {
        whAssert($value === $installed[$table], 'Installation changed prerequisite table: '.$table);
    }
}
$ledgerAfter = $pdo->query('SELECT * FROM migrations ORDER BY id')->fetchAll();
foreach ($ledgerBefore as $row) {
    whAssert(in_array($row, $ledgerAfter, true), 'Existing migration ledger row changed.');
}
$manifest = json_decode(file_get_contents($package.'/database/schema-manifest.json'), true);
foreach ($manifest['migrations'] as $migration) {
    $query = $pdo->prepare('SELECT COUNT(*) FROM migrations WHERE migration=?');
    $query->execute([$migration]);
    whAssert((int) $query->fetchColumn() === 1, 'Migration ledger mismatch.');
}
foreach (WH_EMPTY as $table) {
    whAssert((int) $pdo->query('SELECT COUNT(*) FROM `'.$table.'`')->fetchColumn() === 0, 'Installed transaction table not empty.');
}
$pdo->exec("SET SESSION time_zone='+00:00'");
foreach (['mst_wh_consumable_categories', 'mst_wh_consumables'] as $table) {
    $expected = $export[$table];
    foreach ($expected as &$row) {
        foreach (['created_by', 'updated_by'] as $field) {
            if ($row[$field] !== null) {
                $row[$field] += 1000000;
            }
        }
    }
    unset($row);
    whAssert($expected === $pdo->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(), 'Master values/NPK mapping differ.');
}
$pdo->exec("SET SESSION time_zone='SYSTEM'");
$beforeRetry = whFingerprint($pdo);
rejectCall($pdo, 'CALL warehouse_release_20261022_schema()', 'Warehouse already exists');
rejectCall($pdo, 'CALL warehouse_release_20261022_master()', 'Expected empty table');
whAssert($beforeRetry === whFingerprint($pdo), 'Rejected retry changed data.');
whAssert($report['after'] === whFingerprint($source), 'Application DB changed during SQL rehearsal.');
$result = ['mysql_version' => $pdo->query('SELECT VERSION()')->fetchColumn(), 'missing_npk_rejected_before_ddl' => true, 'ambiguous_npk_rejected_before_ddl' => true, 'partial_fk_schema_rejected' => true, 'master_transaction_rollback_passed' => true, 'install_passed' => true, 'user_id_remapping_passed' => true, 'existing_ledger_rows_preserved' => count($ledgerBefore), 'migration_entries_verified' => count($manifest['migrations']), 'retry_rejected_without_changes' => true, 'application_database_unchanged_by_rehearsal' => true, 'testing_databases' => $created];
whJson($private.'/sql-rehearsal.json', $result);
whJson($package.'/docs/SQL-REHEARSAL-RESULT.json', $result);
echo "SQL installation, FK guard, rollback, mapping, ledger and replay checks passed.\n";
