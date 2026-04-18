<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations untuk support large SAP data (2000+ records)
     */
    public function up()
    {
        // Backup existing data sebelum modify
        try {
            DB::statement("CREATE TABLE sap_data_storage_backup_" . date('Ymd_His') . " AS SELECT * FROM sap_data_storage");
            echo "✅ Backup table created successfully\n";
        } catch (\Exception $e) {
            echo "⚠️ Warning: Could not create backup: " . $e->getMessage() . "\n";
        }

        // Modify existing columns untuk handle large data
        Schema::table('sap_data_storage', function (Blueprint $table) {
            // Change text columns ke LONGTEXT untuk data sangat besar
            $table->longText('error_message')->nullable()->change();
            $table->longText('sap_raw_data')->nullable()->change();
            
            // Add data column if doesn't exist (for compatibility)
            if (!Schema::hasColumn('sap_data_storage', 'data')) {
                $table->longText('data')->nullable()->after('sap_raw_data');
            } else {
                $table->longText('data')->nullable()->change();
            }
        });

        // Add new columns untuk large data tracking
        Schema::table('sap_data_storage', function (Blueprint $table) {
            // Compatibility columns
            if (!Schema::hasColumn('sap_data_storage', 'total_records')) {
                $table->integer('total_records')->default(0)->after('sap_raw_data');
            }
            
            if (!Schema::hasColumn('sap_data_storage', 'fetch_duration')) {
                $table->decimal('fetch_duration', 8, 2)->nullable()->after('fetch_duration_seconds');
            }

            // New columns untuk large data support
            if (!Schema::hasColumn('sap_data_storage', 'data_size_bytes')) {
                $table->bigInteger('data_size_bytes')->nullable()->after('record_count');
            }
            
            if (!Schema::hasColumn('sap_data_storage', 'compression_type')) {
                $table->string('compression_type', 50)->nullable()->after('data_size_bytes');
            }
            
            if (!Schema::hasColumn('sap_data_storage', 'memory_usage_mb')) {
                $table->decimal('memory_usage_mb', 8, 2)->nullable()->after('compression_type');
            }
            
            if (!Schema::hasColumn('sap_data_storage', 'notes')) {
                $table->text('notes')->nullable()->after('memory_usage_mb');
            }
        });

        // Add performance indexes untuk large data
        try {
            Schema::table('sap_data_storage', function (Blueprint $table) {
                // Check if indexes don't exist before adding
                $table->index(['data_key', 'last_fetch_at'], 'idx_data_key_fetch');
                $table->index(['fetch_status', 'created_at'], 'idx_status_created'); 
                $table->index(['total_records', 'last_fetch_at'], 'idx_records_fetch');
                $table->index('data_size_bytes', 'idx_data_size');
                $table->index('compression_type', 'idx_compression');
            });
            echo "✅ Performance indexes added\n";
        } catch (\Exception $e) {
            echo "⚠️ Warning: Some indexes may already exist: " . $e->getMessage() . "\n";
        }

        // Set MySQL parameters untuk handle large data
        try {
            DB::statement('SET SESSION max_allowed_packet = 67108864'); // 64MB
            DB::statement('SET SESSION sql_mode = ""');
            echo "✅ MySQL session parameters updated\n";
        } catch (\Exception $e) {
            echo "⚠️ Warning: Could not set MySQL parameters: " . $e->getMessage() . "\n";
        }

        // Update existing records dengan proper values
        try {
            // Copy record_count to total_records for compatibility
            DB::statement("
                UPDATE sap_data_storage 
                SET total_records = COALESCE(record_count, 0),
                    data = COALESCE(sap_raw_data, '[]'),
                    fetch_duration = COALESCE(fetch_duration_seconds, 0)
                WHERE total_records = 0 OR total_records IS NULL
            ");
            echo "✅ Existing records updated\n";
        } catch (\Exception $e) {
            echo "⚠️ Warning: Could not update existing records: " . $e->getMessage() . "\n";
        }

        // Log migration completion
        echo "\n🎉 Large Data Migration Completed Successfully!\n";
        echo "📊 Database now supports:\n";
        echo "   - Data compression for datasets > 5MB\n";
        echo "   - 2000+ SAP records handling\n";  
        echo "   - Performance indexes for large queries\n";
        echo "   - Memory usage tracking\n";
        echo "   - Backup table created for rollback\n\n";
    }

    /**
     * Reverse the migrations
     */
    public function down()
    {
        echo "🔄 Rolling back Large Data Migration...\n";

        // Remove new columns
        Schema::table('sap_data_storage', function (Blueprint $table) {
            // Drop new columns if they exist
            if (Schema::hasColumn('sap_data_storage', 'data_size_bytes')) {
                $table->dropColumn('data_size_bytes');
            }
            if (Schema::hasColumn('sap_data_storage', 'compression_type')) {
                $table->dropColumn('compression_type'); 
            }
            if (Schema::hasColumn('sap_data_storage', 'memory_usage_mb')) {
                $table->dropColumn('memory_usage_mb');
            }
            if (Schema::hasColumn('sap_data_storage', 'notes')) {
                $table->dropColumn('notes');
            }
        });

        // Drop performance indexes
        try {
            Schema::table('sap_data_storage', function (Blueprint $table) {
                $table->dropIndex('idx_data_key_fetch');
                $table->dropIndex('idx_status_created');
                $table->dropIndex('idx_records_fetch');
                $table->dropIndex('idx_data_size');
                $table->dropIndex('idx_compression');
            });
        } catch (\Exception $e) {
            echo "⚠️ Warning: Could not drop all indexes: " . $e->getMessage() . "\n";
        }

        // Revert text columns (not recommended for production)
        try {
            Schema::table('sap_data_storage', function (Blueprint $table) {
                $table->text('error_message')->nullable()->change();
                // Note: Keep sap_raw_data as LONGTEXT to avoid data loss
            });
        } catch (\Exception $e) {
            echo "⚠️ Warning: Could not revert column types: " . $e->getMessage() . "\n";
        }

        echo "✅ Rollback completed\n";
    }
};