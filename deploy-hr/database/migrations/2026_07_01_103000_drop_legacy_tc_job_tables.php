<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('zz_disabled_tc_job_positions')) {
            Schema::dropIfExists('zz_disabled_tc_job_positions');
        }
        
        if (Schema::hasTable('zz_disabled_tc_user_job_accesses')) {
            Schema::dropIfExists('zz_disabled_tc_user_job_accesses');
        }
        
        if (Schema::hasTable('user_job_accesses')) {
            Schema::dropIfExists('user_job_accesses');
        }
        
        if (Schema::hasTable('tc_user_job_accesses')) {
            Schema::dropIfExists('tc_user_job_accesses');
        }
        
        if (Schema::hasTable('tc_job_positions')) {
            Schema::dropIfExists('tc_job_positions');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No down migration for dropped legacy tables
    }
};
