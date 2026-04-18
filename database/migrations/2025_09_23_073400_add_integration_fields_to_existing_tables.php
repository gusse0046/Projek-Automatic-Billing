<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Tambah field integration ke document_uploads
        Schema::table('document_uploads', function (Blueprint $table) {
            // Check if columns don't exist before adding
            if (!Schema::hasColumn('document_uploads', 'uploaded_by')) {
                $table->string('uploaded_by')->nullable()->after('uploaded_at');
            }
            if (!Schema::hasColumn('document_uploads', 'team')) {
                $table->string('team')->nullable()->after('uploaded_by');
            }
            if (!Schema::hasColumn('document_uploads', 'is_cross_team_visible')) {
                $table->boolean('is_cross_team_visible')->default(false)->after('team');
            }
            if (!Schema::hasColumn('document_uploads', 'integration_source')) {
                $table->string('integration_source')->nullable()->after('is_cross_team_visible');
            }
            if (!Schema::hasColumn('document_uploads', 'notes')) {
                $table->text('notes')->nullable()->after('integration_source');
            }
        });

        // Update existing records
        DB::statement("
            UPDATE document_uploads 
            SET team = CASE 
                WHEN document_type IN ('INVOICE', 'PACKING_LIST', 'PAYMENT_INSTRUCTION', 'CONTAINER_LOAD_PLAN') THEN 'Finance'
                ELSE 'Exim'
            END,
            is_cross_team_visible = CASE 
                WHEN document_type IN ('PEB', 'COO', 'FUMIGASI', 'PYTOSANITARY', 'LACEY_ACT', 'ISF', 'TSCA', 'GCC', 'PPDF', 'VLEGAL', 'AWB') THEN 1
                ELSE 0
            END,
            uploaded_by = COALESCE(uploaded_by, 'System'),
            integration_source = 'Legacy_Upload'
            WHERE team IS NULL
        ");
    }

    public function down()
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            $table->dropColumn(['uploaded_by', 'team', 'is_cross_team_visible', 'integration_source', 'notes']);
        });
    }
};