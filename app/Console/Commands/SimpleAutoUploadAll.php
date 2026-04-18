<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\OptimizedSmartformController;
use Illuminate\Http\Request;

class SimpleAutoUploadAll extends Command
{
    protected $signature = 'upload:all {--test : Test mode}';
    protected $description = 'Upload documents for ALL buyers (batch)';

    public function handle()
    {
        $startTime = now();
        
        $this->info("========================================");
        $this->info("BATCH AUTO UPLOAD - ALL BUYERS");
        $this->info("========================================");
        $this->info("Started: {$startTime->format('Y-m-d H:i:s')}\n");
        
        if ($this->option('test')) {
            $this->warn("TEST MODE\n");
        }

        try {
            // Get ALL deliveries
            $allBillings = DB::table('billing_statuses')->get();
            
            $this->info("Found {$allBillings->count()} deliveries\n");
            
            if ($allBillings->isEmpty()) {
                $this->warn("No deliveries found");
                return 0;
            }

            // Show summary
            $this->line("First 10 deliveries:");
            foreach ($allBillings->take(10) as $b) {
                $this->line("  - {$b->delivery_order} | {$b->customer_name}");
            }
            if ($allBillings->count() > 10) {
                $this->line("  ... and " . ($allBillings->count() - 10) . " more\n");
            }

            if ($this->option('test')) {
                $this->info("Test mode - stopping here");
                return 0;
            }

            // Prepare data
            $this->line("\nPreparing data...");
            $deliveriesData = [];
            foreach ($allBillings as $b) {
                $deliveriesData[] = [
                    'delivery_order' => $b->delivery_order,
                    'customer_name' => $b->customer_name,
                    'billing_document' => $b->billing_document ?? $b->delivery_order
                ];
            }

            // Call controller
            $this->line("Processing upload...\n");
            
            $controller = new OptimizedSmartformController();
            $request = new Request();
            $request->merge(['deliveries' => $deliveriesData]);

            $response = $controller->batchUploadForBuyer($request);
            $result = json_decode($response->getContent(), true);

            // Show results
            $duration = now()->diffInSeconds($startTime);
            
            $this->info("\n========================================");
            $this->info("RESULTS");
            $this->info("========================================");
            
            if ($result['success']) {
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Deliveries Processed', $result['total_processed'] ?? 0],
                        ['Files Uploaded', $result['total_uploaded'] ?? 0],
                        ['Duration', "{$duration} seconds"],
                    ]
                );

                if (($result['total_uploaded'] ?? 0) > 0) {
                    $this->info("\nSuccessfully uploaded {$result['total_uploaded']} files!\n");
                    
                    if (isset($result['processed_deliveries'])) {
                        $this->line("Files uploaded for:");
                        foreach ($result['processed_deliveries'] as $p) {
                            $this->line("  - {$p['delivery_order']}: {$p['uploaded_count']} file(s)");
                        }
                    }
                } else {
                    $this->warn("\nNo new files uploaded");
                    $this->line("  - Files may already be uploaded");
                    $this->line("  - Or no matching files in Z:\\sd\n");
                }
            } else {
                $this->error("\nUpload failed: " . ($result['message'] ?? 'Unknown error'));
            }

            $this->info("\n========================================");
            $this->info("COMPLETED in {$duration} seconds");
            $this->info("========================================");

            return 0;

        } catch (\Exception $e) {
            $this->error("\nERROR: " . $e->getMessage());
            Log::error('Batch upload failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }
}