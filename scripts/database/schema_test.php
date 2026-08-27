<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$columns = Schema::getColumnListing('tc_poin_kategoris');
file_put_contents('schema_out.txt', implode(', ', $columns));
echo "Done";
