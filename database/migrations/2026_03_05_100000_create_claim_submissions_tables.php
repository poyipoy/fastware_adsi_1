<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mst_claim_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('no_pr', 100)->nullable();
            $table->date('submission_date')->nullable();
            $table->text('description_of_issue')->nullable();
            $table->text('proposed_solution')->nullable();
            $table->string('status', 50)->default('open'); // open, on_progress, finished
            $table->string('file', 255)->nullable(); // photo/evidence path
            $table->string('file_name', 255)->nullable(); // original file name
            $table->text('catatan_procurement')->nullable(); // notes from procurement
            $table->string('modified_at', 255)->nullable(); // PIC / user name
            $table->timestamps();
        });

        Schema::create('trs_claim_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_claim')->nullable();
            $table->string('keterangan', 255)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('modified_at', 255)->nullable(); // user name
            $table->timestamps();

            $table->foreign('id_claim')->references('id')->on('mst_claim_submissions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trs_claim_submissions');
        Schema::dropIfExists('mst_claim_submissions');
    }
};
