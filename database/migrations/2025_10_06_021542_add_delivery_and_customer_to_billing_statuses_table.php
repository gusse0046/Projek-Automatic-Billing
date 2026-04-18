<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('billing_statuses', function (Blueprint $table) {
            // Add missing columns
            $table->string('delivery_order')->nullable()->after('id');
            $table->string('customer_name')->nullable()->after('delivery_order');
            $table->string('billing_document')->nullable()->after('customer_name');
            $table->string('booking_number')->nullable()->after('billing_document');
            $table->timestamp('email_sent_at')->nullable()->after('sent_at');
            $table->string('sent_by')->nullable()->after('email_sent_at');
            $table->text('notes')->nullable()->after('sent_by');
            
            // Add indexes for better performance
            $table->index(['delivery_order', 'customer_name']);
            $table->index('billing_document');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::table('billing_statuses', function (Blueprint $table) {
            $table->dropIndex(['delivery_order', 'customer_name']);
            $table->dropIndex(['billing_document']);
            $table->dropIndex(['status']);
            
            $table->dropColumn([
                'delivery_order',
                'customer_name',
                'billing_document',
                'booking_number',
                'email_sent_at',
                'sent_by',
                'notes'
            ]);
        });
    }
};