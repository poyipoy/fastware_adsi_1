<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (Schema::hasColumn('tc_job_positions', 'approval_status')) {
        Schema::table('tc_job_positions', function (Blueprint $table) {
            $table->dropColumn('approval_status');
        });
        echo "Column 'approval_status' removed from 'tc_job_positions'.<br>";
    } else {
        echo "Column 'approval_status' does not exist in 'tc_job_positions'. No action needed.<br>";
    }
    
    echo "Done.";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
