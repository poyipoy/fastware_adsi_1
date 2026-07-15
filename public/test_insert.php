<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(Illuminate\Http\Request::capture());

use App\Models\ItemCode;

$item = new ItemCode();
$item->nomor_pengajuan = 'TEST-' . rand(1000, 9999);
$item->type = 'new_product';
$item->category = 'Material';
$item->supplier = 'Test Supplier';
$item->product_code = 'TEST-' . rand(1000, 9999);
$item->description = 'Test Description';
$item->qty = 1;
$item->unit = 'PCS';
$item->amount = 0;
$item->currency = 'IDR';
$item->price_per_pcs = 500002688.31;
$item->tanggal = '2026-06-25';
$item->status = 'draft';
$item->created_by = 1;
$item->save();

echo "Saved ID: " . $item->id . "\n";
echo "price_per_pcs in PHP: " . $item->price_per_pcs . "\n";

$dbItem = ItemCode::find($item->id);
echo "price_per_pcs in DB: " . $dbItem->price_per_pcs . "\n";
