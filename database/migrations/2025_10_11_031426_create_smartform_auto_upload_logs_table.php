<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('smartform_auto_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->timestamp('run_at');
            $table->string('time_slot', 10); // '06:00' or '08:00'
            $table->enum('status', ['completed', 'failed', 'partial'])->default('completed');
            $table->integer('files_scanned')->default(0);
            $table->integer('files_uploaded')->default(0);
            $table->integer('files_failed')->default(0);
            $table->integer('deliveries_processed')->default(0);
            $table->integer('deliveries_skipped')->default(0);
            $table->decimal('execution_time_seconds', 10, 2)->nullable();
            $table->json('details')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('run_at');
            $table->index(['run_at', 'time_slot']);
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('smartform_auto_upload_logs');
    }
};