<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('email_sent_logs', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_order')->index();
            $table->string('customer_name')->index();
            $table->string('billing_document')->nullable();
            $table->string('booking_number')->nullable();
            
            // Email details
            $table->json('recipient_emails'); // Array of email addresses
            $table->integer('total_recipients')->default(0);
            $table->text('email_subject')->nullable();
            $table->text('email_message')->nullable();
            
            // Sender info
            $table->string('sent_by');
            $table->timestamp('sent_at');
            
            // Document snapshot saat email dikirim
            $table->json('documents_sent')->nullable(); // List dokumen yang di-attach
            $table->json('required_documents_snapshot')->nullable(); // Setting saat itu
            
            // Status & notes
            $table->enum('send_status', ['success', 'failed', 'partial'])->default('success');
            $table->text('notes')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['delivery_order', 'customer_name'], 'idx_delivery_customer');
            $table->index('sent_at');
            $table->index('send_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('email_sent_logs');
    }
};