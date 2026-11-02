<?php

// CLI-only release tooling. Never upload this directory to a web root.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

const WH_RELEASE = 'WAREHOUSE-20261022';
const WH_TABLES = [
    'mst_wh_consumable_categories', 'mst_wh_consumables', 'mst_wh_restricted_verifiers',
    'mst_wh_user_cards', 'trs_wh_stock_transactions', 'trs_wh_stock_ins',
    'trs_wh_location_shipments', 'trs_wh_stock_transaction_machine_usages',
    'log_wh_verifications', 'wh_transaction_sequences', 'wh_stock_in_sequences',
];
const WH_EMPTY = [
    'log_wh_verifications', 'trs_wh_stock_transaction_machine_usages',
    'trs_wh_stock_ins', 'trs_wh_location_shipments', 'trs_wh_stock_transactions',
    'wh_transaction_sequences', 'wh_stock_in_sequences',
];
const WH_STOCK = ['current_stock', 'stock_deltamas', 'stock_ds8', 'stock_used_deltamas', 'stock_used_ds8'];

function whApp(): Illuminate\Foundation\Application
{
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    set_exception_handler(function (Throwable $error): void {
        file_put_contents(storage_path('app/warehouse-release-private/last-error.log'), (string) $error);
        fwrite(STDERR, 'Release tooling failed: '.($error instanceof PDOException ? 'database operation failed' : $error->getMessage()).PHP_EOL);
        exit(1);
    });

    return $app;
}

function whAssert(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function whJson(string $file, array $data): void
{
    if (! is_dir(dirname($file))) {
        mkdir(dirname($file), 0700, true);
    }
    whAssert(file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n") !== false, 'Cannot write JSON artifact.');
}

function whPdo(string $database): PDO
{
    whAssert(preg_match('/^[a-zA-Z0-9_]+$/D', $database) === 1, 'Invalid database identifier.');
    $c = config('database.connections.mysql');

    return new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$database.';charset=utf8mb4', $c['username'], $c['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
}

function whTables(PDO $pdo): array
{
    return $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
}

function whFingerprint(PDO $pdo): array
{
    $result = [];
    foreach (whTables($pdo) as $table) {
        // Stable across physical row order and restore; preserve binary values.
        $rows = $pdo->query('SELECT * FROM `'.$table.'`')->fetchAll();
        $serialized = array_map('serialize', $rows);
        sort($serialized, SORT_STRING);
        $ddl = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
        // MySQL expands redundant column CHARACTER SET clauses during a dump
        // round trip. COLLATE already fixes the character set; normalize only
        // that presentation difference, retaining indexes/FKs/auto-increment.
        $ddl = preg_replace('/ CHARACTER SET [A-Za-z0-9_]+(?= COLLATE)/', '', $ddl);
        $result[$table] = ['count' => count($rows), 'sha256' => hash('sha256', implode("\n", $serialized)), 'ddl_sha256' => hash('sha256', $ddl)];
    }

    return $result;
}

function whMaster(PDO $pdo): array
{
    $rows = $pdo->query('SELECT * FROM mst_wh_consumables ORDER BY id')->fetchAll();
    foreach ($rows as &$row) {
        foreach (WH_STOCK as $column) {
            unset($row[$column]);
        }
    }

    return $rows;
}

function whReset(PDO $pdo): void
{
    whAssert($pdo->inTransaction(), 'Reset requires an enclosing transaction.');
    // Detach only nullable links within the approved deletion set. FK checks stay ON.
    $pdo->exec('UPDATE trs_wh_stock_ins SET stock_transaction_id=NULL');
    $pdo->exec('UPDATE trs_wh_location_shipments SET stock_transaction_id=NULL, migrated_stock_in_id=NULL');
    $pdo->exec('UPDATE trs_wh_stock_transactions SET reversal_of_id=NULL, stock_in_id=NULL, location_shipment_id=NULL');
    foreach (WH_EMPTY as $table) {
        $pdo->exec('DELETE FROM `'.$table.'`');
    }
    $pdo->exec('UPDATE mst_wh_consumables SET '.implode(', ', array_map(fn ($c) => '`'.$c.'`=0', WH_STOCK)));
}

function whCheckReset(PDO $pdo, array $before, array $master): array
{
    $after = whFingerprint($pdo);
    whAssert(array_keys($before) === array_keys($after), 'Table set changed.');
    foreach ($before as $table => $value) {
        whAssert($value['ddl_sha256'] === $after[$table]['ddl_sha256'], 'Schema/auto-increment changed: '.$table);
        if (in_array($table, WH_EMPTY, true)) {
            whAssert($after[$table]['count'] === 0, 'Transaction table not empty: '.$table);
        } elseif ($table !== 'mst_wh_consumables') {
            whAssert($value === $after[$table], 'Protected table changed: '.$table);
        }
    }
    whAssert($master === whMaster($pdo), 'Non-stock master attributes changed.');
    $where = implode(' OR ', array_map(fn ($c) => '`'.$c.'`<>0', WH_STOCK));
    whAssert((int) $pdo->query('SELECT COUNT(*) FROM mst_wh_consumables WHERE '.$where)->fetchColumn() === 0, 'Stock not zero.');
    whAssert((int) $pdo->query('SELECT @@FOREIGN_KEY_CHECKS')->fetchColumn() === 1, 'FK checks must remain enabled.');

    return $after;
}

function whMysql(string $database, string $file, string $log): bool
{
    $c = config('database.connections.mysql');
    $binary = 'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe';
    whAssert(is_file($binary), 'MySQL client not found.');
    $process = new Symfony\Component\Process\Process([$binary, '--host='.$c['host'], '--port='.$c['port'], '--user='.$c['username'], '--default-character-set=utf8mb4', '--binary-mode', '--batch', $database], base_path(), ['MYSQL_PWD' => (string) $c['password']], fopen($file, 'rb'), 240);
    $process->run();
    // May contain SQL details; keep this only in private storage.
    file_put_contents($log, $process->getOutput().$process->getErrorOutput());

    return $process->isSuccessful();
}

function whTestDatabase(PDO $pdo, string $name): void
{
    whAssert(str_ends_with($name, '_testing') && preg_match('/^warehouse_[a-z0-9_]+_testing$/D', $name) === 1, 'Unsafe rehearsal target.');
    $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
    $statement->execute([$name]);
    whAssert((int) $statement->fetchColumn() === 0, 'Rehearsal target already exists; will not overwrite.');
    $pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
