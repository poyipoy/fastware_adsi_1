<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use App\Models\MstJobPosition;

try {
    DB::beginTransaction();

    // 1. Add job_level column to mst_job_positions if it doesn't exist
    if (!Schema::hasColumn('mst_job_positions', 'job_level')) {
        Schema::table('mst_job_positions', function (Blueprint $table) {
            $table->string('job_level', 50)->default('staff')->after('section_id');
        });
        echo "Column 'job_level' added successfully.\n";
    } else {
        echo "Column 'job_level' already exists.\n";
    }

    // 2. Classify levels for all positions
    $positions = MstJobPosition::all();
    $updated = 0;

    foreach ($positions as $pos) {
        $name = $pos->position_name;
        $level = 'staff';

        if (preg_match('/Dept\.?\s*Head/i', $name)) {
            $level = 'dept_head';
        } elseif (preg_match('/Div\.?\s*Head/i', $name)) {
            $level = 'div_head';
        } elseif (preg_match('/Sec(t)?\.?\s*Head|Office\s*Head/i', $name)) {
            $level = 'sec_head';
        }

        $pos->job_level = $level;
        $pos->save();
        $updated++;
    }

    DB::commit();
    echo "Successfully updated {$updated} positions with their job levels.\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "Error during migration: " . $e->getMessage() . "\n";
}
