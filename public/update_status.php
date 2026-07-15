<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\TrsPenilaianTc;

try {
    $updated = DB::table('trs_penilaian_tcs')
        ->where('status', 0)
        ->update(['status' => 4]);

    echo "Successfully updated $updated records from status 0 to status 4.\\n";
    
    $check = DB::table('trs_penilaian_tcs')
        ->select('status', DB::raw('count(*) as count'))
        ->groupBy('status')
        ->get();
    echo "Current Statuses:\\n";
    print_r($check->toArray());
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
