<?php

// Re-export only after proving the approved local reset state still matches.
require __DIR__.'/common.php';
whApp();
$private = json_decode(file_get_contents(storage_path('app/warehouse-release-private/latest.json')), true)['private_directory'];
$report = json_decode(file_get_contents($private.'/report.json'), true);
$pdo = whPdo('dms_adasi_rev1');
whAssert($report['local_reset_committed'] && $report['after'] === whFingerprint($pdo), 'Local state changed since approved reset.');
$export = json_decode(file_get_contents($private.'/export.json'), true);
$pdo->exec("SET SESSION time_zone='+00:00'");
foreach (['mst_wh_consumable_categories', 'mst_wh_consumables', 'mst_wh_restricted_verifiers'] as $table) {
    $export[$table] = $pdo->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll();
}
$export['timestamp_timezone'] = '+00:00';
whJson($private.'/export.json', $export);
echo "Read-only master re-export completed with UTC TIMESTAMP values.\n";
