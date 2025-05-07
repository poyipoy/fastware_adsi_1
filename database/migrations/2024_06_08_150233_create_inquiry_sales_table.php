<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInquirySalesTable extends Migration
{
    public function up()
    {
        Schema::create('inquiry_sales', function (Blueprint $table) {
            $table->id();
            $table->string('kode_inquiry')->unique();
            $table->string('jenis_inquiry');
            $table->string('type');
            $table->string('size');
            $table->string('supplier');
            $table->integer('qty');
            $table->string('order_from');
            $table->string('create_by');
            $table->string('to_approve');
            $table->string('to_validate');
            $table->text('note')->nullable();
            $table->string('attach_file')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inquiry_sales');
    }
}
