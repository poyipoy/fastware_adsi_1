<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

try {
    Illuminate\Support\Facades\DB::statement("ALTER TABLE item_codes MODIFY price_per_pcs DECIMAL(20,2) NOT NULL");
    Illuminate\Support\Facades\DB::statement("ALTER TABLE item_codes MODIFY harga_baru DECIMAL(20,2) NULL");
    echo "Successfully altered table to DECIMAL(20,2)";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
