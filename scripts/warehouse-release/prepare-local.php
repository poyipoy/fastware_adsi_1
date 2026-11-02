<?php

require __DIR__.'/common.php';
$app = whApp();
whAssert(($argv[1] ?? '') === '--confirm=RESET-WAREHOUSE-dms_adasi_rev1', 'Explicit local reset confirmation required.');
$c = config('database.connections.mysql');
whAssert(app()->environment('local') && $c['database'] === 'dms_adasi_rev1' && $c['host'] === '127.0.0.1' && (int) $c['port'] === 3306, 'Unexpected local target.');
whAssert(! $app->isDownForMaintenance(), 'Application was already in maintenance; do not take ownership.');
$pdo = whPdo('dms_adasi_rev1');
whAssert(str_starts_with((string) $pdo->query('SELECT VERSION()')->fetchColumn(), '8.0.'), 'Expected local MySQL 8.0.');
$actual = array_values(array_filter(whTables($pdo), fn ($t) => preg_match('/^(mst_wh_|trs_wh_|log_wh_|wh_)/', $t)));
$expected = WH_TABLES;
sort($actual);
sort($expected);
whAssert($actual === $expected, 'Warehouse table manifest drift; inspect before resetting.');
whAssert((int) $pdo->query("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE CONSTRAINT_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IN ('".implode("','", WH_TABLES)."') AND TABLE_NAME NOT IN ('".implode("','", WH_TABLES)."')")->fetchColumn() === 0, 'External table references Warehouse.');
$stamp = date('Ymd_His');
$private = storage_path('app/warehouse-release-private/'.$stamp);
mkdir($private, 0700, true);
file_put_contents($private.'/.htaccess', "Require all denied\n");
Illuminate\Support\Facades\Artisan::call('down');
try {
    $before = whFingerprint($pdo);
    $master = whMaster($pdo);
    whJson($private.'/before.json', $before);
    $dump = $private.'/database-before-reset.sql';
    $process = new Symfony\Component\Process\Process([
        'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe',
        '--host=127.0.0.1', '--port=3306', '--user='.$c['username'], '--single-transaction',
        '--skip-lock-tables', '--no-tablespaces', '--set-gtid-purged=OFF', '--hex-blob',
        '--skip-add-drop-table', '--result-file='.$dump, 'dms_adasi_rev1',
    ], base_path(), ['MYSQL_PWD' => (string) $c['password']], null, 240);
    $process->run();
    file_put_contents($private.'/backup-client.log', $process->getErrorOutput());
    whAssert($process->isSuccessful() && is_file($dump) && filesize($dump) > 0, 'Backup failed; see private log.');
    whAssert($before === whFingerprint($pdo), 'Database changed during backup.');
    $test = 'warehouse_restore_'.$stamp.'_testing';
    whTestDatabase($pdo, $test);
    whAssert(whMysql($test, $dump, $private.'/restore-client.log'), 'Backup restore failed; see private log.');
    $testPdo = whPdo($test);
    whAssert($before === whFingerprint($testPdo), 'Full backup restore fingerprint mismatch.');
    echo "Full backup restore verified.\n";
    // Deliberate mid-operation failure proves DML rollback preserves all records.
    try {
        $testPdo->beginTransaction();
        whReset($testPdo);
        throw new RuntimeException('INJECTED_RESET_FAILURE');
    } catch (RuntimeException $e) {
        $testPdo->rollBack();
        whAssert($e->getMessage() === 'INJECTED_RESET_FAILURE', 'Unexpected rehearsal failure.');
    }
    whAssert($before === whFingerprint($testPdo), 'Rollback rehearsal changed data.');
    $testPdo->beginTransaction();
    whReset($testPdo);
    whCheckReset($testPdo, $before, $master);
    $testPdo->commit();
    whCheckReset($testPdo, $before, $master);
    echo "Reset and rollback rehearsals passed.\n";
    whAssert($before === whFingerprint($pdo), 'Local database changed before reset.');
    $pdo->beginTransaction();
    try {
        // Lock every Warehouse row; HTTP writes remain blocked by maintenance.
        foreach (WH_TABLES as $table) {
            $pdo->query('SELECT * FROM `'.$table.'` FOR UPDATE')->fetchAll();
        }
        whReset($pdo);
        $after = whCheckReset($pdo, $before, $master);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    whCheckReset($pdo, $before, $master);
    // Export TIMESTAMP values in the same UTC session used by the delivery SQL.
    $pdo->exec("SET SESSION time_zone='+00:00'");
    $schema = [];
    $columns = [];
    foreach (WH_TABLES as $table) {
        $schema[$table] = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
        $statement = $pdo->prepare('SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
        $statement->execute([$table]);
        $columns[$table] = $statement->fetchAll();
    }
    $users = $pdo->query('SELECT id, npk, role_id, is_active FROM users')->fetchAll();
    $export = ['schema' => $schema, 'columns' => $columns, 'users' => $users, 'timestamp_timezone' => '+00:00'];
    foreach (['mst_wh_consumable_categories', 'mst_wh_consumables', 'mst_wh_restricted_verifiers'] as $table) {
        $export[$table] = $pdo->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll();
    }
    whJson($private.'/export.json', $export);
    $report = ['release' => WH_RELEASE, 'database' => 'dms_adasi_rev1', 'host' => '127.0.0.1:3306', 'backup_path' => $dump, 'backup_sha256' => hash_file('sha256', $dump), 'backup_bytes' => filesize($dump), 'restore_database' => $test, 'backup_restore_verified' => true, 'rollback_rehearsal_passed' => true, 'reset_rehearsal_passed' => true, 'local_reset_committed' => true, 'before' => $before, 'after' => $after, 'protected_tables_verified' => count($before) - count(WH_EMPTY) - 1, 'all_schemas_and_auto_increments_preserved' => true, 'export_path' => $private.'/export.json'];
    whJson($private.'/report.json', $report);
    whJson(storage_path('app/warehouse-release-private/latest.json'), ['private_directory' => $private]);
    echo 'Local reset verified. Private evidence: '.$private.PHP_EOL;
    echo "Maintenance remains enabled until the package/export has been built.\n";
} catch (Throwable $e) {
    Illuminate\Support\Facades\Artisan::call('up');
    throw $e;
}
