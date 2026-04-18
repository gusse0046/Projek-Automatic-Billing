<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sap_data_storage', function (Blueprint $table) {
            $table->id();
            $table->string('data_key')->default('main_billing_data');
            $table->longText('sap_raw_data')->nullable();
            $table->longText('data')->nullable(); // For compatibility
            $table->timestamp('last_fetch_at')->nullable();
            $table->decimal('fetch_duration_seconds', 8, 2)->nullable();
            $table->decimal('fetch_duration', 8, 2)->nullable(); // For compatibility
            $table->string('fetch_status')->default('pending');
            $table->text('error_message')->nullable();
            $table->string('endpoint_used')->nullable();
            $table->integer('record_count')->default(0);
            $table->timestamps();
            
            $table->index('data_key');
            $table->index('last_fetch_at');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sap_data_storage');
    }
};