<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoUploadDocuments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'documents:auto-upload 
                            {--location= : Filter by location (surabaya/semarang/local)}
                            {--force : Force upload even if already processed today}
                            {--test : Test mode - show what would be processed}';

    /**
     * The console command description.
     */
    protected $description = 'Trigger auto-upload for deliveries with missing documents (alternative to manual dashboard click)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = now();
        $this->info("========================================");
        $this->info("🚀 AUTO UPLOAD DOCUMENTS STARTED");
        $this->info("========================================");
        $this->info("⏰ Run Time: {$startTime->format('Y-m-d H:i:s')}");
        
        if ($this->option('test')) {
            $this->warn("🧪 TEST MODE - No actual processing");
        }

        try {
            // Get deliveries that need auto-upload
            $deliveries = $this->getDeliveriesNeedingUpload();
            
            if ($deliveries->isEmpty()) {
                $this->info("✅ No deliveries need auto-upload at this time");
                return 0;
            }

            $this->info("📦 Found {$deliveries->count()} deliveries needing auto-upload");
            
            // Group by location
            $grouped = $this->groupByLocation($deliveries);
            
            // Display what will be processed
            $this->displaySummary($grouped);
            
            if ($this->option('test')) {
                $this->info("\n🧪 Test mode - stopping here");
                return 0;
            }

            // Trigger auto-upload process
            $this->processAutoUpload($grouped);
            
            $duration = now()->diffInSeconds($startTime);
            $this->info("\n✅ AUTO UPLOAD COMPLETED in {$duration} seconds");
            
            Log::info('Auto upload documents completed', [
                'duration' => $duration,
                'deliveries_processed' => $deliveries->count(),
                'timestamp' => now()
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("\n❌ AUTO UPLOAD FAILED");
            $this->error("Error: " . $e->getMessage());
            
            Log::error('Auto upload documents failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * Get deliveries that need auto-upload
     */
    private function getDeliveriesNeedingUpload()
    {
        $location = $this->option('location');
        
        // Query billing_status untuk deliveries yang masih butuh dokumen
        $query = DB::table('billing_status as bs')
            ->leftJoin('document_uploads as du_invoice', function($join) {
                $join->on('bs.delivery_order', '=', 'du_invoice.delivery_order')
                     ->where('du_invoice.document_type', '=', 'INVOICE');
            })
            ->leftJoin('document_uploads as du_pl', function($join) {
                $join->on('bs.delivery_order', '=', 'du_pl.delivery_order')
                     ->where('du_pl.document_type', '=', 'PACKING_LIST');
            })
            ->leftJoin('document_uploads as du_pi', function($join) {
                $join->on('bs.delivery_order', '=', 'du_pi.delivery_order')
                     ->where('du_pi.document_type', '=', 'PAYMENT_INTRUCTION');
            })
            ->select(
                'bs.delivery_order',
                'bs.billing_document',
                'bs.customer_name',
                'bs.status',
                'du_invoice.id as has_invoice',
                'du_pl.id as has_packing_list',
                'du_pi.id as has_payment_intruction'
            )
            ->whereIn('bs.status', ['outstanding', 'progress'])
            ->whereNull('bs.email_sent_at')
            ->where(function($q) {
                $q->whereNull('du_invoice.id')
                  ->orWhereNull('du_pl.id')
                  ->orWhereNull('du_pi.id');
            });

        // Filter by location if specified
        if ($location) {
            switch (strtolower($location)) {
                case 'surabaya':
                    $query->where('bs.delivery_order', 'LIKE', '201%')
                          ->orWhere('bs.delivery_order', 'LIKE', '211%');
                    break;
                case 'semarang':
                    $query->where('bs.delivery_order', 'LIKE', '202%')
                          ->orWhere('bs.delivery_order', 'LIKE', '212%');
                    break;
                case 'local':
                    $query->where(function($q) {
                        $q->where('bs.billing_document', 'LIKE', '315%')
                          ->orWhere('bs.billing_document', 'LIKE', '316%');
                    });
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Group deliveries by location
     */
    private function groupByLocation($deliveries)
    {
        $groups = [
            'Surabaya' => [],
            'Semarang' => [],
            'Local SBY-SMG' => []
        ];

        foreach ($deliveries as $delivery) {
            $location = $this->determineLocation($delivery);
            $groups[$location][] = $delivery;
        }

        return array_filter($groups); // Remove empty groups
    }

    /**
     * Determine location from delivery order and billing document
     */
    private function determineLocation($delivery)
    {
        $doPrefix = substr($delivery->delivery_order, 0, 3);
        $billingPrefix = substr($delivery->billing_document, 0, 3);

        // Check if LOCAL (billing doc 315 or 316)
        if (in_array($billingPrefix, ['315', '316'])) {
            return 'Local SBY-SMG';
        }

        // Determine by delivery order prefix
        if (in_array($doPrefix, ['201', '211'])) {
            return 'Surabaya';
        }

        if (in_array($doPrefix, ['202', '212'])) {
            return 'Semarang';
        }

        return 'Unknown';
    }

    /**
     * Display summary of what will be processed
     */
    private function displaySummary($grouped)
    {
        $this->info("\n📋 SUMMARY BY LOCATION:");
        $this->info("========================================");

        foreach ($grouped as $location => $deliveries) {
            $count = count($deliveries);
            if ($count > 0) {
                $this->line("\n📍 {$location}: {$count} deliveries");
                
                foreach ($deliveries as $delivery) {
                    $missing = [];
                    if (!$delivery->has_invoice) $missing[] = 'Invoice';
                    if (!$delivery->has_packing_list) $missing[] = 'PL';
                    if (!$delivery->has_payment_intruction) $missing[] = 'PI';
                    
                    $missingDocs = implode(', ', $missing);
                    $this->line("  • {$delivery->delivery_order} - Missing: {$missingDocs}");
                }
            }
        }
    }

    /**
     * Process auto-upload (calls the existing smartform command)
     */
    private function processAutoUpload($grouped)
    {
        $this->info("\n🔄 PROCESSING AUTO-UPLOAD...");
        
        foreach ($grouped as $location => $deliveries) {
            if (empty($deliveries)) continue;
            
            $this->line("\n📍 Processing {$location}...");
            
            // Determine location parameter for smartform command
            $locationParam = null;
            if ($location === 'Surabaya') {
                $locationParam = 'surabaya';
            } elseif ($location === 'Semarang') {
                $locationParam = 'semarang';
            }
            
            // Call the existing smartform:auto-upload command
            $command = 'smartform:auto-upload';
            $params = [];
            
            if ($locationParam) {
                $params['--location'] = $locationParam;
            }
            
            if ($this->option('force')) {
                $params['--force'] = true;
            }
            
            $this->info("  ⚙️  Running: php artisan {$command}" . ($locationParam ? " --location={$locationParam}" : ""));
            
            $exitCode = $this->call($command, $params);
            
            if ($exitCode === 0) {
                $this->info("  ✅ {$location} completed successfully");
            } else {
                $this->error("  ❌ {$location} failed with exit code {$exitCode}");
            }
            
            // Small delay between locations
            sleep(2);
        }
    }
}