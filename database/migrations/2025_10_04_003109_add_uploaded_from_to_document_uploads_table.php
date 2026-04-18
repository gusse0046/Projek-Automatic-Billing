<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
  public function up(): void
{
    Schema::table('document_uploads', function (Blueprint $table) {
        if (!Schema::hasColumn('document_uploads', 'uploaded_from')) {
            $table->enum('uploaded_from', ['manual', 'exim', 'smartform', 'finance'])
                  ->default('exim')  // ✅ Default tetap 'exim'
                  ->after('team')
                  ->comment('Source: manual, exim, smartform, finance');
            
            $table->index('uploaded_from', 'idx_uploaded_from');
        }
    });

        // Update existing records berdasarkan pattern yang ada
        try {
            // 1. Set 'smartform' untuk auto-upload dari smartform
            DB::statement("
                UPDATE document_uploads 
                SET uploaded_from = 'smartform'
                WHERE uploaded_by = 'Smartform Auto-Upload'
                   OR uploaded_by LIKE '%Auto-Upload%'
                   OR notes LIKE '%Auto-uploaded from Z:\\\\sd%'
                   OR notes LIKE '%Auto-uploaded from D:\\\\sd%'
            ");

            // 2. Set 'finance' untuk upload dari Finance team
            DB::statement("
                UPDATE document_uploads 
                SET uploaded_from = 'finance'
                WHERE team = 'Finance' 
                  AND uploaded_from = 'exim'
                  AND uploaded_by != 'Smartform Auto-Upload'
            ");

            // 3. Sisanya adalah 'exim' (default sudah set)
            
            echo "✅ Updated existing records:\n";
            echo "   - Smartform uploads: " . DB::table('document_uploads')->where('uploaded_from', 'smartform')->count() . "\n";
            echo "   - Finance uploads: " . DB::table('document_uploads')->where('uploaded_from', 'finance')->count() . "\n";
            echo "   - EXIM uploads: " . DB::table('document_uploads')->where('uploaded_from', 'exim')->count() . "\n";
            
        } catch (\Exception $e) {
            echo "⚠️ Warning: Could not update existing records: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_uploads', function (Blueprint $table) {
            // Drop index first
            $table->dropIndex('idx_uploaded_from');
            
            // Drop column
            if (Schema::hasColumn('document_uploads', 'uploaded_from')) {
                $table->dropColumn('uploaded_from');
            }
        });
    }
};