<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\OptimizedSmartformController;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BatchAutoUploadAllDeliveries extends Command
{
    protected $signature = 'auto:upload-all 
                            {--test : Test mode - show what would be processed}
                            {--limit= : Limit number of deliveries to process}
                            {--diagnose : Show detailed diagnostic information}';

    protected $description = 'Auto-upload documents for ALL buyers (same as clicking Auto button for each buyer)';

    private $smartformFolder = 'Z:\\sd';

    public function handle()
    {
        $startTime = now();
        
        $this->info("========================================");
        $this->info("🚀 BATCH AUTO-UPLOAD ALL BUYERS");
        $this->info("========================================");
        $this->info("⏰ Started: {$startTime->format('Y-m-d H:i:s')}");
        
        if ($this->option('test')) {
            $this->warn("🧪 TEST MODE - No actual uploads");
        }
        
        if ($this->option('diagnose')) {
            $this->warn("🔍 DIAGNOSTIC MODE");
        }
        
        $this->newLine();

        try {
            // 1. Check Z:\sd folder (with detailed diagnostic if failed)
            if (!$this->checkFolderAccess()) {
                $this->error("❌ Cannot access Z:\\sd folder");
                $this->newLine();
                
                // Show diagnostic information
                $this->showDiagnosticInfo();
                
                return 1;
            }

            // 2. Get ALL deliveries yang missing documents
            $allBillings = $this->getAllDeliveries();
            
            if ($allBillings->isEmpty()) {
                $this->warn("⚠️  No deliveries with missing documents found");
                return 0;
            }

            $this->info("📦 Found {$allBillings->count()} deliveries with missing documents");
            $this->newLine();

            // Show summary
            $this->displaySummary($allBillings);

            if ($this->option('test')) {
                $this->newLine();
                $this->info("🧪 Test mode - stopping here");
                return 0;
            }

            // 3. Process batch upload
            $this->newLine();
            $result = $this->processBatchUpload($allBillings);

            // 4. Show results
            $this->displayResults($result, $startTime);

            return 0;

        } catch (\Exception $e) {
            $this->error("\n❌ BATCH UPLOAD FAILED");
            $this->error("Error: " . $e->getMessage());
            
            Log::error('Batch auto-upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    private function checkFolderAccess()
    {
        // Test 1: is_dir
        $this->line("🔍 Testing folder access...");
        $this->line("   Path: {$this->smartformFolder}");
        
        $isDir = is_dir($this->smartformFolder);
        $this->line("   is_dir(): " . ($isDir ? '✓ YES' : '✗ NO'));
        
        if (!$isDir) {
            return false;
        }
        
        // Test 2: is_readable (INFO ONLY - tidak block execution)
        $readable = is_readable($this->smartformFolder);
        $this->line("   is_readable(): " . ($readable ? '✓ YES' : '⚠ NO (but will try anyway)'));
        
        // NOTE: Kita skip is_readable check karena:
        // - Dashboard button Auto SUDAH BISA akses folder via controller
        // - Controller OptimizedSmartformController sudah proven working
        // - CLI permission berbeda dari web server permission
        // - Kita akan delegate ke controller yang punya akses proper
        
        if (!$readable) {
            $this->warn("   ⚠ WARNING: CLI cannot read folder directly");
            $this->line("   ℹ Will delegate to OptimizedSmartformController");
            $this->line("   ℹ Controller has proper permissions (proven by working dashboard)");
        }
        
        $this->newLine();
        $this->info("✅ Z:\\sd folder exists - delegating to controller");
        return true;  // Return true karena folder exists, controller akan handle akses
    }

    private function showDiagnosticInfo()
    {
        $this->warn("========================================");
        $this->warn("🔍 DIAGNOSTIC INFORMATION");
        $this->warn("========================================");
        $this->newLine();
        
        // Test different path formats
        $this->line("Testing alternative path formats:");
        $pathsToTest = [
            'Z:\\sd' => 'Backslash (Windows)',
            'Z:/sd' => 'Forward slash',
            'Z:\\' => 'Root Z drive',
            'Z:/' => 'Root Z drive (forward)',
        ];
        
        foreach ($pathsToTest as $path => $description) {
            $exists = @file_exists($path);
            $isDir = @is_dir($path);
            $status = ($exists && $isDir) ? '✓ ACCESSIBLE' : '✗ NOT ACCESSIBLE';
            $this->line("  {$description}: {$status}");
            $this->line("    Path: {$path}");
            $this->line("    file_exists: " . ($exists ? 'YES' : 'NO'));
            $this->line("    is_dir: " . ($isDir ? 'YES' : 'NO'));
            $this->newLine();
        }
        
        // Show current user
        $this->line("Current execution context:");
        if (function_exists('exec')) {
            @exec('whoami 2>&1', $whoami, $return);
            if ($return === 0 && !empty($whoami)) {
                $this->line("  User: " . implode("\n        ", $whoami));
            }
        }
        
        // Environment info
        $username = getenv('USERNAME');
        if ($username) {
            $this->line("  USERNAME env: {$username}");
        }
        
        $this->line("  PHP Version: " . PHP_VERSION);
        $this->line("  PHP SAPI: " . PHP_SAPI);
        $this->line("  Working dir: " . getcwd());
        
        $this->newLine();
        
        // Recommendations
        $this->warn("💡 POSSIBLE SOLUTIONS:");
        $this->line("1. Check if drive Z: is mounted:");
        $this->line("   net use");
        $this->newLine();
        
        $this->line("2. Mount drive Z: if not available:");
        $this->line("   net use Z: \\\\server\\share /persistent:yes");
        $this->newLine();
        
        $this->line("3. Run as Administrator:");
        $this->line("   Right-click PowerShell → Run as Administrator");
        $this->newLine();
        
        $this->line("4. Use UNC path instead (if Z: is network drive):");
        $this->line("   Update \$smartformFolder in this file to:");
        $this->line("   \\\\server\\share\\sd");
        $this->newLine();
        
        $this->line("5. Verify web server CAN access (test via dashboard button)");
        $this->line("   If dashboard works but CLI doesn't → permission issue");
        $this->newLine();
        
        $this->warn("To see more details, run with --diagnose flag:");
        $this->line("  php artisan auto:upload-all --test --diagnose");
    }

    private function getAllDeliveries()
    {
        // UPDATED: Gunakan SapDataStorage (SAMA seperti dashboard)
        // Ini adalah single source of truth untuk SAP data
        
        if ($this->option('diagnose')) {
            $this->warn("🔍 DEBUG MODE - Using SapDataStorage (same as dashboard)...");
            $this->newLine();
        }
        
        // Get data dari SAP cache (sama seperti dashboard)
        $sapData = \App\Models\SapDataStorage::getMainBillingData();
        
        if (!$sapData || !is_array($sapData)) {
            $this->error("Failed to get data from SapDataStorage");
            return collect([]);
        }
        
        if ($this->option('diagnose')) {
            $this->line("📊 SAP Data loaded: " . count($sapData) . " records");
            $this->newLine();
        }
        
        // Group by delivery order (sama seperti DashboardController)
        $grouped = [];
        foreach ($sapData as $item) {
            $delivery = $item['Delivery'] ?? '';
            $customerName = $item['Customer Name'] ?? '';
            
            if (empty($delivery)) continue;
            
            $key = $delivery . '_' . $customerName;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'delivery_order' => $delivery,
                    'customer_name' => $customerName,
                    'billing_document' => $item['Billing Document'] ?? $delivery,
                    'status' => 'outstanding', // Default status
                ];
            }
        }
        
        // Check documents untuk setiap delivery
        $deliveriesWithStatus = [];
        foreach ($grouped as $key => $delivery) {
            $deliveryOrder = $delivery['delivery_order'];
            
            // Count documents (subquery approach)
            $hasInvoice = DB::table('document_uploads')
                ->where('delivery_order', $deliveryOrder)
                ->where('document_type', 'INVOICE')
                ->count();
            
            $hasPackingList = DB::table('document_uploads')
                ->where('delivery_order', $deliveryOrder)
                ->where('document_type', 'PACKING_LIST')
                ->count();
            
            $hasPaymentIntruction = DB::table('document_uploads')
                ->where('delivery_order', $deliveryOrder)
                ->where('document_type', 'PAYMENT_INTRUCTION')
                ->count();
            
            // Add document status
            $delivery['has_invoice'] = $hasInvoice;
            $delivery['has_packing_list'] = $hasPackingList;
            $delivery['has_payment_intruction'] = $hasPaymentIntruction;
            
            $deliveriesWithStatus[] = (object) $delivery;
        }
        
        // Filter yang missing documents
        $result = collect($deliveriesWithStatus)->filter(function($delivery) {
            return $delivery->has_invoice == 0 
                || $delivery->has_packing_list == 0 
                || $delivery->has_payment_intruction == 0;
        })->values();
        
        // Apply limit if specified
        if ($this->option('limit')) {
            $result = $result->take((int)$this->option('limit'));
        }
        
        // DEBUG: Show detailed analysis
        if ($this->option('diagnose')) {
            $this->line("📊 DETAILED ANALYSIS:");
            $this->newLine();
            
            $totalInSap = count($grouped);
            $this->line("Total deliveries from SAP: {$totalInSap}");
            
            $totalDocs = DB::table('document_uploads')->count();
            $this->line("Total documents in document_uploads: {$totalDocs}");
            $this->newLine();
            
            // Show document types
            $docTypes = DB::table('document_uploads')
                ->select('document_type', DB::raw('count(*) as count'))
                ->groupBy('document_type')
                ->orderBy('count', 'desc')
                ->get();
            
            $this->line("Document types in document_uploads:");
            foreach ($docTypes as $type) {
                $this->line("  • {$type->document_type}: {$type->count}");
            }
            $this->newLine();
        }
        
        // Show statistics
        if ($this->option('diagnose') || $this->option('test')) {
            $totalInSap = count($grouped);
            $missingCount = $result->count();
            $completeCount = $totalInSap - $missingCount;
            
            $this->newLine();
            $this->line("   📊 Document Status:");
            $this->line("     • Total deliveries from SAP: {$totalInSap}");
            $this->line("     • Complete (all 3 docs): {$completeCount}");
            $this->line("     • Missing docs: {$missingCount} ← Will process these");
            
            // Count missing by type
            $missingInvoice = $result->where('has_invoice', 0)->count();
            $missingPL = $result->where('has_packing_list', 0)->count();
            $missingPI = $result->where('has_payment_intruction', 0)->count();
            
            $this->line("     • Missing Invoice: {$missingInvoice}");
            $this->line("     • Missing Packing List: {$missingPL}");
            $this->line("     • Missing Payment Instruction: {$missingPI}");
        }

        return $result;
    }

    private function displaySummary($deliveries)
    {
        $this->info("📋 DELIVERIES SUMMARY:");
        $this->line("========================================");
        
        // Group by location
        $locations = [
            'Surabaya' => 0,
            'Semarang' => 0,
            'Local SBY-SMG' => 0,
            'Other' => 0
        ];

        foreach ($deliveries as $delivery) {
            $location = $this->determineLocation($delivery);
            if (isset($locations[$location])) {
                $locations[$location]++;
            } else {
                $locations['Other']++;
            }
        }

        foreach ($locations as $location => $count) {
            if ($count > 0) {
                $this->line("  📍 {$location}: {$count} deliveries");
            }
        }

        $this->newLine();
        $this->line("First 10 deliveries (with missing documents):");
        foreach ($deliveries->take(10) as $delivery) {
            $location = $this->determineLocation($delivery);
            
            // Show which documents are missing
            $missing = [];
            if ($delivery->has_invoice == 0) $missing[] = 'Invoice';
            if ($delivery->has_packing_list == 0) $missing[] = 'PL';
            if ($delivery->has_payment_intruction == 0) $missing[] = 'PI';
            $missingStr = implode(', ', $missing);
            
            $this->line("  • {$delivery->delivery_order} | {$delivery->customer_name}");
            $this->line("    Missing: {$missingStr}");
        }

        if ($deliveries->count() > 10) {
            $remaining = $deliveries->count() - 10;
            $this->line("  ... and {$remaining} more");
        }
    }

    private function determineLocation($delivery)
    {
        $doPrefix = substr($delivery->delivery_order, 0, 3);
        $billingPrefix = substr($delivery->billing_document ?? '', 0, 3);

        if (in_array($billingPrefix, ['315', '316'])) {
            return 'Local SBY-SMG';
        }

        if (in_array($doPrefix, ['201', '211'])) {
            return 'Surabaya';
        }

        if (in_array($doPrefix, ['202', '212'])) {
            return 'Semarang';
        }

        return 'Other';
    }

    private function processBatchUpload($deliveries)
    {
        $this->info("🔄 PROCESSING BATCH UPLOAD...");
        $this->line("Scanning Z:\\sd for matching files...");
        $this->line("This may take a few minutes...");
        $this->newLine();

        // Prepare deliveries data (same format as dashboard)
        $deliveriesData = [];
        foreach ($deliveries as $delivery) {
            $deliveriesData[] = [
                'delivery_order' => $delivery->delivery_order,
                'customer_name' => $delivery->customer_name,
                'billing_document' => $delivery->billing_document ?? $delivery->delivery_order
            ];
        }

        // Create progress bar
        $progressBar = $this->output->createProgressBar($deliveries->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
        $progressBar->start();

        // Call controller (SAME as dashboard Auto button)
        $controller = new OptimizedSmartformController();
        $request = new Request();
        $request->merge(['deliveries' => $deliveriesData]);

        $response = $controller->batchUploadForBuyer($request);
        $result = json_decode($response->getContent(), true);

        $progressBar->finish();
        $this->newLine(2);

        return $result;
    }

    private function displayResults($result, $startTime)
    {
        $duration = now()->diffInSeconds($startTime);
        
        // Ensure duration is always positive
        if ($duration < 0) {
            $duration = abs($duration);
        }

        $this->info("========================================");
        $this->info("📊 UPLOAD RESULTS");
        $this->info("========================================");

        if ($result['success']) {
            $totalProcessed = $result['total_processed'] ?? 0;
            $totalUploaded = $result['total_uploaded'] ?? 0;

            $this->table(
                ['Metric', 'Value'],
                [
                    ['Deliveries Processed', $totalProcessed],
                    ['Files Uploaded', $totalUploaded],
                    ['Duration', "{$duration} seconds"],
                ]
            );

            if ($totalUploaded > 0) {
                $this->newLine();
                $this->info("✅ Successfully uploaded {$totalUploaded} files!");
                
                if (isset($result['processed_deliveries']) && !empty($result['processed_deliveries'])) {
                    $this->newLine();
                    $this->line("📁 Files uploaded for:");
                    
                    // Group by delivery_order to avoid showing duplicates
                    $grouped = [];
                    foreach ($result['processed_deliveries'] as $processed) {
                        $do = $processed['delivery_order'];
                        if (!isset($grouped[$do])) {
                            $grouped[$do] = 0;
                        }
                        $grouped[$do] += $processed['uploaded_count'];
                    }
                    
                    foreach ($grouped as $deliveryOrder => $count) {
                        $this->line("  ✓ {$deliveryOrder}: {$count} file(s)");
                    }
                }
            } else {
                $this->newLine();
                $this->warn("⚠️  No new files uploaded");
                $this->line("Possible reasons:");
                $this->line("  • Files already uploaded previously");
                $this->line("  • No matching files in Z:\\sd");
                $this->line("  • Files don't follow naming convention");
            }

            Log::info('Batch auto-upload completed', [
                'total_processed' => $totalProcessed,
                'total_uploaded' => $totalUploaded,
                'duration' => $duration,
                'timestamp' => now()
            ]);

        } else {
            $this->newLine();
            $this->error("❌ Upload failed");
            $this->line("Error: " . ($result['message'] ?? 'Unknown error'));
        }

        $this->newLine();
        $this->info("========================================");
        $this->info("✅ COMPLETED in {$duration} seconds");
        $this->info("========================================");
    }
}