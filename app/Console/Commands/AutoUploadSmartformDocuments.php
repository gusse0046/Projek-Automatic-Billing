<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use App\Models\DocumentUpload;
use App\Models\BillingStatus;
use App\Models\SapDataStorage;
use Carbon\Carbon;

class AutoUploadSmartformDocuments extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'smartform:auto-upload 
                            {--force : Force upload even if already processed today}
                            {--location= : Filter by location (surabaya/semarang)}
                            {--test : Test mode - dry run without actual upload}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically upload documents from Z:\sd folder at scheduled times (06:00 & 08:00 WIB)';

    private $smartformFolder = 'Z:\\sd';
    private $uploadStats = [
        'total_scanned' => 0,
        'total_matched' => 0,
        'total_uploaded' => 0,
        'total_failed' => 0,
        'total_skipped' => 0,
        'details' => []
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $startTime = microtime(true);
        $runTime = Carbon::now('Asia/Jakarta');
        
        $this->info("========================================");
        $this->info("🚀 SMARTFORM AUTO-UPLOAD STARTED");
        $this->info("========================================");
        $this->info("⏰ Run Time: {$runTime->format('Y-m-d H:i:s')} WIB");
        $this->info("📁 Source Folder: {$this->smartformFolder}");
        
        if ($this->option('test')) {
            $this->warn("🧪 TEST MODE - No actual uploads will be performed");
        }
        
        try {
            // 1️⃣ CHECK FOLDER ACCESS
            if (!$this->checkFolderAccess()) {
                $this->error("❌ Cannot access Z:\sd folder");
                return 1;
            }
            
            // 2️⃣ CHECK IF ALREADY RUN TODAY (unless forced)
            if (!$this->option('force') && $this->hasRunToday()) {
                $this->warn("⚠️  Auto-upload already completed today at this time slot");
                $this->info("Use --force to override");
                return 0;
            }
            
            // 3️⃣ GET ACTIVE DELIVERIES FROM DATABASE
            $deliveries = $this->getActiveDeliveries();
            $this->info("📦 Found {$deliveries->count()} active deliveries to process");
            
            if ($deliveries->isEmpty()) {
                $this->warn("⚠️  No active deliveries found");
                return 0;
            }
            
            // 4️⃣ SCAN SMARTFORM FOLDER
            $scannedFiles = $this->scanSmartformFolder();
            $this->uploadStats['total_scanned'] = count($scannedFiles);
            $this->info("📄 Scanned {$this->uploadStats['total_scanned']} files from Z:\sd");
            
            // 5️⃣ PROCESS EACH DELIVERY
            $progressBar = $this->output->createProgressBar($deliveries->count());
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %message%');
            
            foreach ($deliveries as $delivery) {
                $progressBar->setMessage("Processing: {$delivery->delivery_order}");
                
                $result = $this->processDelivery($delivery, $scannedFiles);
                
                $this->uploadStats['details'][] = $result;
                
                if ($result['uploaded'] > 0) {
                    $this->uploadStats['total_uploaded'] += $result['uploaded'];
                }
                if ($result['failed'] > 0) {
                    $this->uploadStats['total_failed'] += $result['failed'];
                }
                if ($result['skipped']) {
                    $this->uploadStats['total_skipped']++;
                }
                
                $progressBar->advance();
                usleep(50000); // 50ms delay between deliveries
            }
            
            $progressBar->finish();
            $this->newLine(2);
            
            // 6️⃣ LOG COMPLETION
            $this->logCompletionStats($runTime, $startTime);
            
            // 7️⃣ MARK AS COMPLETED
            if (!$this->option('test')) {
                $this->markAsCompleted($runTime);
            }
            
            $this->info("\n✅ AUTO-UPLOAD COMPLETED SUCCESSFULLY");
            return 0;
            
        } catch (\Exception $e) {
            $this->error("\n❌ AUTO-UPLOAD FAILED");
            $this->error("Error: " . $e->getMessage());
            Log::error("Smartform Auto-Upload Failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Check if Z:\sd folder is accessible
     */
    private function checkFolderAccess()
    {
        if (!is_dir($this->smartformFolder)) {
            Log::error("Smartform folder not accessible", [
                'folder' => $this->smartformFolder
            ]);
            return false;
        }
        
        if (!is_readable($this->smartformFolder)) {
            Log::error("Smartform folder not readable", [
                'folder' => $this->smartformFolder
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Check if auto-upload already run today at this time slot
     */
    private function hasRunToday()
    {
        $currentHour = Carbon::now('Asia/Jakarta')->hour;
        $timeSlot = $currentHour < 7 ? '06:00' : '08:00';
        
        $todayRun = DB::table('smartform_auto_upload_logs')
            ->whereDate('run_at', Carbon::today('Asia/Jakarta'))
            ->where('time_slot', $timeSlot)
            ->where('status', 'completed')
            ->exists();
        
        return $todayRun;
    }

    /**
     * Get active deliveries from database
     */
    private function getActiveDeliveries()
    {
        $location = $this->option('location');
        
        // Get deliveries dengan status outstanding, progress, atau completed (belum sent)
        $query = DB::table('billing_status')
            ->select('delivery_order', 'customer_name', 'billing_document', 'status')
            ->whereIn('status', ['outstanding', 'progress', 'completed'])
            ->whereNull('email_sent_at'); // Belum dikirim ke buyer
        
        // Filter by location if specified
        if ($location) {
            $prefix = strtolower($location) === 'surabaya' ? '201' : '202';
            $query->where('delivery_order', 'LIKE', "{$prefix}%");
        }
        
        return $query->get();
    }

    /**
     * Scan Z:\sd folder for all files
     */
    private function scanSmartformFolder()
    {
        $files = [];
        
        try {
            $allItems = scandir($this->smartformFolder);
            
            foreach ($allItems as $item) {
                if ($item === '.' || $item === '..') continue;
                
                $fullPath = $this->smartformFolder . '\\' . $item;
                
                if (is_file($fullPath)) {
                    $files[] = [
                        'filename' => $item,
                        'full_path' => $fullPath,
                        'size' => filesize($fullPath),
                        'modified_time' => filemtime($fullPath),
                        'extension' => strtolower(pathinfo($item, PATHINFO_EXTENSION))
                    ];
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Error scanning smartform folder", [
                'error' => $e->getMessage()
            ]);
        }
        
        return $files;
    }

    /**
     * Process single delivery - find and upload matching files
     */
    private function processDelivery($delivery, $scannedFiles)
    {
        $result = [
            'delivery_order' => $delivery->delivery_order,
            'customer_name' => $delivery->customer_name,
            'billing_document' => $delivery->billing_document,
            'uploaded' => 0,
            'failed' => 0,
            'skipped' => false,
            'files' => []
        ];
        
        try {
            // Use billing document for matching
            $billingDoc = $delivery->billing_document ?: $delivery->delivery_order;
            
            // Find matching files
            $matchingFiles = $this->findMatchingFiles($billingDoc, $scannedFiles);
            
            if (empty($matchingFiles)) {
                $result['skipped'] = true;
                $result['reason'] = 'No matching files found';
                return $result;
            }
            
            // Upload each matching file
            foreach ($matchingFiles as $file) {
                try {
                    if ($this->option('test')) {
                        // Test mode - just log without uploading
                        $result['files'][] = [
                            'filename' => $file['filename'],
                            'document_type' => $file['document_type'],
                            'status' => 'test_mode_skip'
                        ];
                        continue;
                    }
                    
                    $uploadResult = $this->uploadFile(
                        $file,
                        $delivery->delivery_order,
                        $delivery->customer_name
                    );
                    
                    if ($uploadResult['success']) {
                        $result['uploaded']++;
                        $result['files'][] = [
                            'filename' => $file['filename'],
                            'document_type' => $file['document_type'],
                            'status' => 'uploaded',
                            'storage_id' => $uploadResult['storage_id']
                        ];
                    } else {
                        $result['failed']++;
                        $result['files'][] = [
                            'filename' => $file['filename'],
                            'document_type' => $file['document_type'],
                            'status' => 'failed',
                            'error' => $uploadResult['error']
                        ];
                    }
                    
                } catch (\Exception $e) {
                    $result['failed']++;
                    $result['files'][] = [
                        'filename' => $file['filename'],
                        'document_type' => $file['document_type'],
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Update billing status if any files uploaded
            if ($result['uploaded'] > 0 && !$this->option('test')) {
                BillingStatus::updateStatus(
                    $delivery->delivery_order,
                    $delivery->customer_name,
                    null,
                    true
                );
            }
            
        } catch (\Exception $e) {
            $result['failed']++;
            $result['error'] = $e->getMessage();
            Log::error("Error processing delivery", [
                'delivery_order' => $delivery->delivery_order,
                'error' => $e->getMessage()
            ]);
        }
        
        return $result;
    }

    /**
     * Find files matching billing document
     */
    private function findMatchingFiles($billingDocument, $scannedFiles)
    {
        $matchingFiles = [];
        
        foreach ($scannedFiles as $file) {
            $extracted = $this->extractDocumentInfo($file['filename']);
            
            if ($extracted && $extracted['billing_document'] === $billingDocument) {
                $matchingFiles[] = array_merge($file, $extracted);
            }
        }
        
        return $matchingFiles;
    }

    /**
     * Extract document type and billing document from filename
     */
    private function extractDocumentInfo($filename)
    {
        $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
        
        // Pattern matching for document types
        $patterns = [
            '/^Invoice(\d{10,})$/i' => 'INVOICE',
            '/^PackingList(\d{10,})$/i' => 'PACKING_LIST',
            '/^PaymentIntruction(\d{10,})$/i' => 'PAYMENT_INTRUCTION',
        ];

        foreach ($patterns as $pattern => $documentType) {
            if (preg_match($pattern, $filenameWithoutExt, $matches)) {
                return [
                    'document_type' => $documentType,
                    'billing_document' => $matches[1],
                    'original_filename' => $filename
                ];
            }
        }

        return null;
    }

    /**
     * Upload file to Laravel storage
     */
    private function uploadFile($file, $deliveryOrder, $customerName)
    {
        try {
            // Check source file exists
            if (!file_exists($file['full_path'])) {
                return [
                    'success' => false,
                    'error' => 'Source file not found'
                ];
            }

            // Delete existing document if any
            $existing = DocumentUpload::where('delivery_order', $deliveryOrder)
                ->where('customer_name', $customerName)
                ->where('document_type', $file['document_type'])
                ->first();

            if ($existing) {
                $oldPath = storage_path('app/public/' . $existing->file_path);
                if (File::exists($oldPath)) {
                    File::delete($oldPath);
                }
                $existing->delete();
            }

            // Generate new filename
            $extension = $file['extension'] ?: 'pdf';
            $newFilename = $deliveryOrder . '_' . $file['document_type'] . '_auto_' . time() . '.' . $extension;
            $storageFolder = storage_path('app/public/documents');
            $storagePath = $storageFolder . '/' . $newFilename;

            // Ensure folder exists
            if (!is_dir($storageFolder)) {
                mkdir($storageFolder, 0755, true);
            }

            // Copy file
            if (!copy($file['full_path'], $storagePath)) {
                return [
                    'success' => false,
                    'error' => 'Failed to copy file'
                ];
            }

            // Verify copied file
            if (!file_exists($storagePath)) {
                return [
                    'success' => false,
                    'error' => 'File not found after copy'
                ];
            }

            // Create database record
            $uploadData = [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'document_type' => $file['document_type'],
                'file_name' => $file['filename'],
                'file_path' => 'documents/' . $newFilename,
                'file_type' => $extension,
                'file_size' => $file['size'],
                'uploaded_at' => now(),
                'uploaded_by' => 'Smartform Auto-Upload (Scheduled)',
                'team' => 'Finance',
                'uploaded_from' => 'smartform_scheduled',
                'notes' => "Scheduled auto-upload from Z:\\sd at " . Carbon::now('Asia/Jakarta')->format('Y-m-d H:i:s') . " WIB"
            ];

            $documentUpload = DocumentUpload::create($uploadData);

            return [
                'success' => true,
                'storage_id' => $documentUpload->id,
                'document_type' => $file['document_type']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Log completion statistics
     */
    private function logCompletionStats($runTime, $startTime)
    {
        $duration = round(microtime(true) - $startTime, 2);
        
        $this->info("========================================");
        $this->info("📊 UPLOAD STATISTICS");
        $this->info("========================================");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Files Scanned', $this->uploadStats['total_scanned']],
                ['Deliveries Processed', count($this->uploadStats['details'])],
                ['Files Uploaded', $this->uploadStats['total_uploaded']],
                ['Files Failed', $this->uploadStats['total_failed']],
                ['Deliveries Skipped', $this->uploadStats['total_skipped']],
                ['Duration', "{$duration}s"],
            ]
        );
        
        // Show detailed results for uploaded files
        if ($this->uploadStats['total_uploaded'] > 0) {
            $this->info("\n✅ Successfully Uploaded Files:");
            foreach ($this->uploadStats['details'] as $detail) {
                if ($detail['uploaded'] > 0) {
                    $this->line("  • {$detail['delivery_order']} - {$detail['uploaded']} file(s)");
                    foreach ($detail['files'] as $file) {
                        if ($file['status'] === 'uploaded') {
                            $this->line("    └─ {$file['document_type']}: {$file['filename']}");
                        }
                    }
                }
            }
        }
        
        // Show failed uploads
        if ($this->uploadStats['total_failed'] > 0) {
            $this->warn("\n⚠️  Failed Uploads:");
            foreach ($this->uploadStats['details'] as $detail) {
                if ($detail['failed'] > 0) {
                    $this->line("  • {$detail['delivery_order']}");
                    foreach ($detail['files'] as $file) {
                        if (in_array($file['status'], ['failed', 'error'])) {
                            $this->line("    └─ {$file['filename']}: {$file['error']}");
                        }
                    }
                }
            }
        }
        
        // Log to database
        Log::info("Smartform Auto-Upload Completed", [
            'run_time' => $runTime->toDateTimeString(),
            'duration_seconds' => $duration,
            'stats' => $this->uploadStats
        ]);
    }

    /**
     * Mark this run as completed in database
     */
    private function markAsCompleted($runTime)
    {
        $currentHour = $runTime->hour;
        $timeSlot = $currentHour < 7 ? '06:00' : '08:00';
        
        DB::table('smartform_auto_upload_logs')->insert([
            'run_at' => $runTime,
            'time_slot' => $timeSlot,
            'status' => 'completed',
            'files_scanned' => $this->uploadStats['total_scanned'],
            'files_uploaded' => $this->uploadStats['total_uploaded'],
            'files_failed' => $this->uploadStats['total_failed'],
            'deliveries_processed' => count($this->uploadStats['details']),
            'deliveries_skipped' => $this->uploadStats['total_skipped'],
            'execution_time_seconds' => microtime(true) - LARAVEL_START,
            'details' => json_encode($this->uploadStats['details']),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }
}