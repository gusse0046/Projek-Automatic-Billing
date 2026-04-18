<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_uploads', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order');
            $table->string('customer_name');
            $table->string('document_type'); // PEB, INVOICE, dll
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, excel, jpg
            $table->bigInteger('file_size');
            $table->timestamp('uploaded_at');
            $table->timestamps();
            
            $table->index(['delivery_order', 'customer_name']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_uploads');
    }
};