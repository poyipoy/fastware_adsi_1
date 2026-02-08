<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('mst_qst', function (Blueprint $table) {
            $table->id();
            $table->string('user_name');
            $table->string('jabatan')->nullable();
            $table->string('system_name');
            $table->json('core_metrics')->nullable()->comment('JSON: akurasi, responsivitas, stabilitas, efisiensi');
            $table->json('features')->nullable()->comment('JSON: dynamic feature evaluations');
            $table->text('obstacles')->nullable()->comment('Kendala utama');
            $table->text('suggestions')->nullable()->comment('Saran pengembangan');
            $table->tinyInteger('is_active')->default(1)->comment('1=Active, 2=Inactive');
            $table->string('status')->nullable();
            $table->string('modified_at')->nullable()->comment('Nama user yang memodifikasi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mst_qst');
    }
};
