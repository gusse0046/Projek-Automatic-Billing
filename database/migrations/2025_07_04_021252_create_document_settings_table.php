<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->json('allowed_documents'); // JSON array berisi dokumen yang diizinkan
            $table->timestamps();
            
            $table->unique('customer_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('document_settings');
    }
};