<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class GlobalAutoUpload extends Command
{
    /**
     * The name and signature of the console command.
     */
   protected $signature = 'documents:global-auto-upload 
                        {--test : Test mode - show info without uploading}
                        {--force : Force re-upload even if documents already exist}
                        {--sync : Sync all deliveries (same as --force)}
                        {--status=* : Filter by status (outstanding,progress,sent,complete)}
                        {--delivery= : Test with specific delivery order only}';
    /**
     * The console command description.
     */
    protected $description = 'Global auto-upload for ALL buyers at once (supports force/sync mode)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        
        $this->info("========================================");
        $this->info("🚀 GLOBAL AUTO UPLOAD - ALL BUYERS");
        $this->info("========================================");
        $this->info("⏰ Run Time: {$startTime->format('Y-m-d H:i:s')}");
        
        // Check mode
        $forceMode = $this->option('force') || $this->option('sync');
        $testMode = $this->option('test');
        $statuses = $this->option('status');
        
        if ($forceMode) {
            $this->warn("🔄 FORCE/SYNC MODE - Will process ALL deliveries");
        }
        
        if ($testMode) {
            $this->warn("🧪 TEST MODE");
            
            // Show what would be processed
            $query = DB::table('billing_statuses');
            
            if (!$forceMode) {
                $query->whereIn('status', ['outstanding', 'progress'])
                      ->whereNull('email_sent_at');
            } else if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
            
            $count = $query->count();
            $buyerCount = $query->distinct('customer_name')->count('customer_name');
            
            $this->info("\nWould process:");
            $this->line("  • Deliveries: {$count}");
            $this->line("  • Buyers: {$buyerCount}");
            $this->line("  • Force mode: " . ($forceMode ? 'Yes' : 'No'));
            
            if (!empty($statuses)) {
                $this->line("  • Status filter: " . implode(', ', $statuses));
            }
            
            $this->info("\n✅ Configuration looks good!");
            return 0;
        }
        
        try {
            $this->line("\n📄 Processing upload directly...");
            
            // Get deliveries yang perlu diupload
            $query = DB::table('billing_statuses');
            
            if (!$forceMode) {
                $query->whereIn('status', ['outstanding', 'progress'])
                      ->whereNull('email_sent_at');
            } else if (!empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
            
            if ($this->option('delivery')) {
                $query->where('delivery_order', $this->option('delivery'));
            }
            
            $deliveries = $query->get();
            $buyersCount = $query->distinct('customer_name')->count('customer_name');
            
            $this->info("Found {$deliveries->count()} deliveries from {$buyersCount} buyers");
            
            $totalUploaded = 0;
            $totalProcessed = 0;
            $processedDeliveries = [];
            
            // Process each delivery
            $uploadController = app(\App\Http\Controllers\DocumentUploadController::class);
            
            foreach ($deliveries as $delivery) {
                $this->line("Processing: {$delivery->delivery_order}");
                
                // Buat request untuk upload
                $uploadRequest = new Request([
                    'delivery_order' => $delivery->delivery_order,
                    'force' => $forceMode
                ]);
                
                try {
                    // Panggil autoUpload method
                    $uploadResponse = $uploadController->autoUpload($uploadRequest);
                    $uploadResult = json_decode($uploadResponse->getContent(), true);
                    
                    if ($uploadResult['success'] ?? false) {
                        $uploaded = $uploadResult['uploaded_count'] ?? 0;
                        $totalUploaded += $uploaded;
                        $totalProcessed++;
                        
                        if ($uploaded > 0) {
                            $processedDeliveries[] = [
                                'delivery_order' => $delivery->delivery_order,
                                'uploaded_count' => $uploaded
                            ];
                            $this->info("  ✓ Uploaded {$uploaded} file(s)");
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("  ✗ Failed for {$delivery->delivery_order}: " . $e->getMessage());
                }
            }
            
            $result = [
                'buyers_count' => $buyersCount,
                'total_processed' => $totalProcessed,
                'total_uploaded' => $totalUploaded,
                'processed_deliveries' => $processedDeliveries
            ];
            
            $duration = now()->diffInSeconds($startTime);
            
            $this->info("\n========================================");
            $this->info("✅ UPLOAD SUCCESS");
            $this->info("========================================\n");
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Mode', $forceMode ? 'FORCE/SYNC' : 'Normal'],
                    ['Buyers Processed', $result['buyers_count'] ?? 0],
                    ['Deliveries Processed', $result['total_processed'] ?? 0],
                    ['Files Uploaded', $result['total_uploaded'] ?? 0],
                    ['Duration', "{$duration} seconds"],
                ]
            );
            
            if (($result['total_uploaded'] ?? 0) > 0) {
                $this->info("\n🎉 Successfully uploaded {$result['total_uploaded']} files!");
                
                if (isset($result['processed_deliveries']) && !empty($result['processed_deliveries'])) {
                    $this->info("\n📋 Upload details:");
                    foreach ($result['processed_deliveries'] as $processed) {
                        $this->line("  • {$processed['delivery_order']}: {$processed['uploaded_count']} file(s)");
                    }
                }
            } else {
                $this->warn("\n⚠️  No new files found in Z:\\sd");
            }
            
            Log::info('Global auto upload completed via command', [
                'result' => $result,
                'duration' => $duration,
                'force_mode' => $forceMode,
                'timestamp' => now()
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("\n❌ GLOBAL AUTO UPLOAD FAILED");
            $this->error("Error: " . $e->getMessage());
            
            Log::error('Global auto upload failed via command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }
}