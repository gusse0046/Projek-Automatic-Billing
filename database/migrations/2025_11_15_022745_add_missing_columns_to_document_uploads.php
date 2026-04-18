<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            // Add missing columns
            if (!Schema::hasColumn('document_uploads', 'file_extension')) {
                $table->string('file_extension', 10)->nullable()->after('file_type');
            }
            
            if (!Schema::hasColumn('document_uploads', 'uploaded_by')) {
                $table->string('uploaded_by', 100)->nullable()->after('uploaded_at');
            }
            
            if (!Schema::hasColumn('document_uploads', 'team')) {
                $table->string('team', 50)->nullable()->after('uploaded_by');
            }
            
            if (!Schema::hasColumn('document_uploads', 'notes')) {
                $table->text('notes')->nullable()->after('team');
            }
            
            if (!Schema::hasColumn('document_uploads', 'uploaded_from')) {
                $table->string('uploaded_from', 50)->nullable()->after('notes');
            }
            
            if (!Schema::hasColumn('document_uploads', 'container_number')) {
                $table->string('container_number', 100)->nullable()->after('uploaded_from');
            }
            
            if (!Schema::hasColumn('document_uploads', 'billing_document')) {
                $table->string('billing_document')->nullable()->after('container_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            $table->dropColumn([
                'file_extension',
                'uploaded_by',
                'team',
                'notes',
                'uploaded_from',
                'container_number',
                'billing_document'
            ]);
        });
    }
};