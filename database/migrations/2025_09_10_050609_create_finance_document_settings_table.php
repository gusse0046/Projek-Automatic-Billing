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
        Schema::create('finance_document_settings', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name'); // Nama customer/buyer
            $table->json('allowed_documents'); // JSON array dokumen Finance yang diizinkan (sama seperti document_settings)
            $table->timestamps();
            
            // Index untuk performa dan constraint yang sama seperti document_settings
            $table->unique('customer_name'); 
            $table->index(['customer_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finance_document_settings');
    }
};