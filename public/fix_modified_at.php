<?php
/**
 * Fix `modified_at` in mst_pd_pengajuans.
 * The column expects the user's name (string), but the dummy seeder inserted the user ID.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
set_time_limit(120);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$pengajuans = DB::table('mst_pd_pengajuans')->get();
$updated = 0;
$log = [];

foreach ($pengajuans as $pd) {
    // If modified_at is purely numeric (or matches the user ID)
    if (is_numeric($pd->modified_at)) {
        $user = DB::table('users')->where('id', $pd->modified_at)->first();
        if ($user) {
            DB::table('mst_pd_pengajuans')
                ->where('id', $pd->id)
                ->update(['modified_at' => $user->name]);
            $updated++;
        }
    }
}

// Also check trs_penilaian_tcs just in case modified_at there is also supposed to be user ID or name.
// Wait, TrsPenilaianTc model says:
//    // Relasi ke User berdasarkan modified_at
//    public function userModifier() { return $this->belongsTo(User::class, 'modified_at', 'id'); }
// So in trs_penilaian_tcs, modified_at is indeed an ID. We only need to fix mst_pd_pengajuans.

$log[] = "✅ Updated {$updated} records in mst_pd_pengajuans to use User Name instead of ID in modified_at column.";

echo json_encode([
    'status' => 'Fix applied',
    'updated' => $updated,
    'log' => $log
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
