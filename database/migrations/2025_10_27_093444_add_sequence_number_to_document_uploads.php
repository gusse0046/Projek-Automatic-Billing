<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Cek apakah kolom sequence_number sudah ada
        if (!Schema::hasColumn('document_uploads', 'sequence_number')) {
            Schema::table('document_uploads', function (Blueprint $table) {
                $table->integer('sequence_number')->default(1)->after('document_type');
                $table->index(['delivery_order', 'customer_name', 'document_type', 'sequence_number'], 'doc_sequence_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        if (Schema::hasColumn('document_uploads', 'sequence_number')) {
            Schema::table('document_uploads', function (Blueprint $table) {
                $table->dropIndex('doc_sequence_idx');
                $table->dropColumn('sequence_number');
            });
        }
    }
};