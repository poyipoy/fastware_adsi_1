<?php

require __DIR__.'/common.php';
whApp();
$source = whPdo('dms_adasi_rev1');
$private = json_decode(file_get_contents(storage_path('app/warehouse-release-private/latest.json')), true)['private_directory'];
$package = base_path('deploy-warehouse/'.WH_RELEASE);
$manifest = json_decode(file_get_contents($package.'/database/schema-manifest.json'), true);
$name = 'warehouse_migrations_'.date('Ymd_His').'_testing';
whTestDatabase($source, $name);
$pdo = whPdo($name);
foreach (['users', 'migrations', 'mst_departments', 'mst_job_positions', 'user_job_positions'] as $table) {
    $pdo->exec('CREATE TABLE `'.$table.'` LIKE `dms_adasi_rev1`.`'.$table.'`');
    $pdo->exec('INSERT INTO `'.$table.'` SELECT * FROM `dms_adasi_rev1`.`'.$table.'`');
}
$connection = config('database.connections.mysql');
$connection['database'] = $name;
config(['database.connections.warehouse_release_replay' => $connection]);
Illuminate\Support\Facades\DB::setDefaultConnection('warehouse_release_replay');
app()->detectEnvironment(fn () => 'testing');
whAssert(app()->environment('testing') && str_ends_with(Illuminate\Support\Facades\DB::connection()->getDatabaseName(), '_testing'), 'Unsafe migration rehearsal target.');
foreach ($manifest['migrations'] as $migration) {
    (require database_path('migrations/'.$migration.'.php'))->up();
}

function schemaMetadata(PDO $pdo, string $table): array
{
    $definitions = [
        'columns' => 'SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION',
        'indexes' => 'SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,COLLATION,SUB_PART,INDEX_TYPE FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX',
        'foreign_keys' => 'SELECT k.CONSTRAINT_NAME,k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME=? ORDER BY k.CONSTRAINT_NAME,k.ORDINAL_POSITION',
    ];
    $result = [];
    foreach ($definitions as $key => $sql) {
        $query = $pdo->prepare($sql);
        $query->execute([$table]);
        $result[$key] = $query->fetchAll();
    }

    return $result;
}

$differences = [];
foreach (WH_TABLES as $table) {
    $local = schemaMetadata($source, $table);
    $replayed = schemaMetadata($pdo, $table);
    if ($local !== $replayed) {
        $differences[$table] = ['local' => $local, 'migrations' => $replayed];
    }
}
whJson($private.'/migration-schema-differences.json', $differences);
whAssert($differences === [], 'Migration/schema differences require reconciliation: '.implode(', ', array_keys($differences)));
$result = ['testing_database' => $name, 'migrations_replayed' => count($manifest['migrations']), 'tables_compared' => count(WH_TABLES), 'columns_indexes_foreign_keys_match_local' => true];
whJson($private.'/migration-rehearsal.json', $result);
whJson($package.'/docs/MIGRATION-REHEARSAL-RESULT.json', $result);
echo count($manifest['migrations'])." Warehouse migrations replayed; all columns, indexes and foreign keys match local schema.\n";
