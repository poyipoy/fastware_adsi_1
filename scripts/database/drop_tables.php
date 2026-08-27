<?php
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

try {
    if (Schema::hasTable('zz_disabled_tc_job_positions')) Schema::dropIfExists('zz_disabled_tc_job_positions');
    if (Schema::hasTable('zz_disabled_tc_user_job_accesses')) Schema::dropIfExists('zz_disabled_tc_user_job_accesses');
    if (Schema::hasTable('user_job_accesses')) Schema::dropIfExists('user_job_accesses');
    if (Schema::hasTable('tc_user_job_accesses')) Schema::dropIfExists('tc_user_job_accesses');
    if (Schema::hasTable('tc_job_positions')) Schema::dropIfExists('tc_job_positions');
    
    // Also delete from migrations table so it doesn't cause issues
    DB::table('migrations')->where('migration', '2026_07_01_065000_disable_legacy_job_position_tables')->delete();
    
    echo "Successfully dropped legacy tables.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
