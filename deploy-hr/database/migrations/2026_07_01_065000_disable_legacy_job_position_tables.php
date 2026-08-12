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
        if (Schema::hasTable('tc_job_positions')) {
            Schema::rename('tc_job_positions', 'zz_disabled_tc_job_positions');
        }
        
        if (Schema::hasTable('tc_user_job_accesses')) {
            Schema::rename('tc_user_job_accesses', 'zz_disabled_tc_user_job_accesses');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('zz_disabled_tc_job_positions')) {
            Schema::rename('zz_disabled_tc_job_positions', 'tc_job_positions');
        }
        
        if (Schema::hasTable('zz_disabled_tc_user_job_accesses')) {
            Schema::rename('zz_disabled_tc_user_job_accesses', 'tc_user_job_accesses');
        }
    }
};
