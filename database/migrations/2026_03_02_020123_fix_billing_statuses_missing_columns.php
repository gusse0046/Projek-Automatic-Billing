<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('billing_statuses', 'delivery_order')) {
                $table->string('delivery_order')->nullable()->after('id');
            }
            if (!Schema::hasColumn('billing_statuses', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('delivery_order');
            }
            if (!Schema::hasColumn('billing_statuses', 'billing_document')) {
                $table->string('billing_document')->nullable()->after('customer_name');
            }
            if (!Schema::hasColumn('billing_statuses', 'required_documents')) {
                $table->json('required_documents')->nullable()->after('billing_document');
            }
            if (!Schema::hasColumn('billing_statuses', 'uploaded_documents_count')) {
                $table->integer('uploaded_documents_count')->default(0)->after('required_documents');
            }
            if (!Schema::hasColumn('billing_statuses', 'total_required_documents')) {
                $table->integer('total_required_documents')->default(0)->after('uploaded_documents_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('billing_statuses', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_order', 'customer_name', 'billing_document',
                'required_documents', 'uploaded_documents_count', 'total_required_documents'
            ]);
        });
    }
};