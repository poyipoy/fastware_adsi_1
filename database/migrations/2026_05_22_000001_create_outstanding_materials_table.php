<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outstanding_materials', function (Blueprint $table) {
            $table->id();
            $table->string('supplier');
            $table->string('type');
            $table->decimal('thickness', 15, 2)->nullable();
            $table->decimal('width', 15, 2)->nullable();
            $table->decimal('diameter', 15, 2)->nullable();
            $table->string('length')->nullable();
            $table->decimal('qty_pcs', 15, 2)->nullable();
            $table->decimal('est_qty_kg', 15, 2)->nullable();
            $table->string('number_invoice')->nullable();
            $table->string('status');
            $table->string('estimasi_eta_port', 100)->nullable();
            $table->string('estimasi_eta_warehouse', 100)->nullable();
            $table->string('estimasi_bulan_eta')->nullable();
            $table->string('keterangan')->nullable();
            $table->string('estimasi_delay_eta_port', 100)->nullable();
            $table->string('estimasi_delay_eta_warehouse', 100)->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('supplier');
            $table->index('type');
            $table->index('status');
            $table->index('keterangan');
            $table->index('estimasi_bulan_eta');
            $table->index('estimasi_eta_port');
            $table->index('estimasi_eta_warehouse');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outstanding_materials');
    }
};
