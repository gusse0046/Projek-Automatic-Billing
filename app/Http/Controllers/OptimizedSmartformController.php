<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\BillingStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OptimizedSmartformController extends Controller
{
    private $smartformFolder = 'Z:\\sd';
    private $logPrefix = '[SMARTFORM-AUTO-UPLOAD]';
    
    /**
     * REAL-TIME: Monitor Z:\sd folder dan auto-upload file baru
     */
    public function monitorAndAutoUpload(Request $request)
    {
        try {
            Log::info("{$this->logPrefix} === REAL-TIME MONITORING STARTED ===", [
                'folder' => $this->smartformFolder,
                'timestamp' => now()->toDateTimeString()
            ]);

            if (!is_dir($this->smartformFolder)) {
                Log::error("{$this->logPrefix} Folder Z:\\sd not accessible");
                return response()->json([
                    'success' => false,
                    'message' => 'Folder Z:\\sd tidak dapat diakses dari server',
                    'folder_path' => $this->smartformFolder
                ], 500);
            }

            // Scan semua file di folder Z:\sd
            $allFiles = $this->scanSmartformFolder();
            
            // Get cache untuk file yang sudah diproses
            $processedFiles = $this->getProcessedFilesCache();
            
            // Filter file baru yang belum diproses
            $newFiles = $this->filterNewFiles($allFiles, $processedFiles);
            
            Log::info("{$this->logPrefix} File scan results", [
                'total_files' => count($allFiles),
                'processed_files' => count($processedFiles),
                'new_files' => count($newFiles)
            ]);

            if (empty($newFiles)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No new files to process',
                    'total_files' => count($allFiles),
                    'new_files' => 0,
                    'timestamp' => now()->toDateTimeString()
                ]);
            }

            // Process file baru
            $results = $this->processNewFiles($newFiles);
            
            // Update cache
            $this->updateProcessedFilesCache($allFiles);
            
            Log::info("{$this->logPrefix} Processing completed", [
                'processed_count' => count($results['processed']),
                'failed_count' => count($results['failed']),
                'ignored_count' => count($results['ignored'])
            ]);

            return response()->json([
                'success' => true,
                'message' => "Processed {$results['summary']['uploaded']} new files from Z:\\sd",
                'results' => $results,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} Monitoring failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Real-time monitoring failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MANUAL: Upload untuk delivery order tertentu
     */
    public function manualUploadForDelivery(Request $request)
    {
        try {
            $validated = $request->validate([
                'delivery_order' => 'required|string',
                'customer_name' => 'required|string',
                'billing_document' => 'nullable|string'
            ]);

            $deliveryOrder = $validated['delivery_order'];
            $customerName = $validated['customer_name'];
            $billingDocument = $validated['billing_document'] ?: $deliveryOrder;

            Log::info("{$this->logPrefix} Manual upload for delivery", [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'billing_document' => $billingDocument
            ]);

            if (!is_dir($this->smartformFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder Z:\\sd tidak dapat diakses'
                ]);
            }

            // Cari file yang matching dengan billing document
            $matchingFiles = $this->findMatchingFiles($billingDocument);
            
            if (empty($matchingFiles)) {
                $debug = $this->getDebugInfo($billingDocument);
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada file ditemukan untuk billing document: {$billingDocument}",
                    'debug_info' => $debug
                ]);
            }

            // Process matching files
            $results = $this->processFilesForDelivery($matchingFiles, $deliveryOrder, $customerName);
            
            return response()->json([
                'success' => true,
                'message' => "Berhasil upload {$results['uploaded']} dokumen untuk {$deliveryOrder}",
                'uploaded_files' => $results['files'],
                'billing_document' => $billingDocument,
                'total_found' => count($matchingFiles)
            ]);

        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} Manual upload failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Manual upload gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * BATCH: Process semua file untuk multiple deliveries
     */
    public function batchProcessAllFiles(Request $request)
    {
        try {
            $limit = $request->get('limit', 50); // Limit untuk mencegah timeout
            
            Log::info("{$this->logPrefix} Batch processing started", [
                'limit' => $limit,
                'folder' => $this->smartformFolder
            ]);

            if (!is_dir($this->smartformFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder Z:\\sd tidak dapat diakses'
                ]);
            }

            // Get semua delivery orders dari database
            $deliveryOrders = $this->getActiveDeliveryOrders($limit);
            
            $totalProcessed = 0;
            $totalUploaded = 0;
            $processedDeliveries = [];

            foreach ($deliveryOrders as $delivery) {
                $billingDoc = $delivery['billing_document'] ?: $delivery['delivery_order'];
                $matchingFiles = $this->findMatchingFiles($billingDoc);
                
                if (!empty($matchingFiles)) {
                    $results = $this->processFilesForDelivery(
                        $matchingFiles, 
                        $delivery['delivery_order'], 
                        $delivery['customer_name']
                    );
                    
                    if ($results['uploaded'] > 0) {
                        $totalUploaded += $results['uploaded'];
                        $processedDeliveries[] = [
                            'delivery_order' => $delivery['delivery_order'],
                            'customer_name' => $delivery['customer_name'],
                            'billing_document' => $billingDoc,
                            'uploaded_count' => $results['uploaded'],
                            'files' => $results['files']
                        ];
                    }
                }
                
                $totalProcessed++;
                
                // Small delay to prevent server overload
                usleep(100000); // 0.1 second
            }

            Log::info("{$this->logPrefix} Batch processing completed", [
                'deliveries_processed' => $totalProcessed,
                'files_uploaded' => $totalUploaded,
                'successful_deliveries' => count($processedDeliveries)
            ]);

            return response()->json([
                'success' => true,
                'message' => "Batch processing selesai: {$totalUploaded} file berhasil diupload",
                'deliveries_processed' => $totalProcessed,
                'files_uploaded' => $totalUploaded,
                'successful_deliveries' => $processedDeliveries,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} Batch processing failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Batch processing gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan folder Z:\sd untuk semua file
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
            Log::error("{$this->logPrefix} Error scanning folder: " . $e->getMessage());
        }
        
        return $files;
    }

    /**
     * Filter file baru yang belum diproses
     */
    private function filterNewFiles($allFiles, $processedFiles)
    {
        return array_filter($allFiles, function($file) use ($processedFiles) {
            $fileKey = $file['filename'] . '_' . $file['modified_time'];
            return !in_array($fileKey, $processedFiles);
        });
    }

    /**
     * Process file-file baru
     */
    private function processNewFiles($newFiles)
    {
        $results = [
            'processed' => [],
            'failed' => [],
            'ignored' => [],
            'summary' => [
                'uploaded' => 0,
                'failed' => 0,
                'ignored' => 0
            ]
        ];

        foreach ($newFiles as $file) {
            try {
                $processingResult = $this->processIndividualFile($file);
                
                if ($processingResult['success']) {
                    $results['processed'][] = $processingResult;
                    $results['summary']['uploaded']++;
                } else if ($processingResult['ignored']) {
                    $results['ignored'][] = $processingResult;
                    $results['summary']['ignored']++;
                } else {
                    $results['failed'][] = $processingResult;
                    $results['summary']['failed']++;
                }
                
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'filename' => $file['filename'],
                    'error' => $e->getMessage(),
                    'success' => false,
                    'ignored' => false
                ];
                $results['summary']['failed']++;
            }
        }

        return $results;
    }

    /**
     * Process individual file dengan pattern matching yang akurat
     */
    private function processIndividualFile($file)
    {
        $filename = $file['filename'];
        
        // Extract billing document dan document type dari filename
        $extracted = $this->extractDocumentInfo($filename);
        
        if (!$extracted) {
            return [
                'filename' => $filename,
                'success' => false,
                'ignored' => true,
                'reason' => 'Filename tidak sesuai pattern yang diharapkan'
            ];
        }

        // Cari delivery order yang matching
        $deliveryInfo = $this->findDeliveryByBillingDocument($extracted['billing_document']);
        
        if (!$deliveryInfo) {
            return [
                'filename' => $filename,
                'success' => false,
                'ignored' => true,
                'reason' => "Tidak ditemukan delivery order untuk billing document: {$extracted['billing_document']}"
            ];
        }

        // Process file upload
        $uploadResult = $this->uploadFileToSystem(
            $file, 
            $extracted['document_type'], 
            $deliveryInfo['delivery_order'], 
            $deliveryInfo['customer_name']
        );

        return $uploadResult;
    }

    /**
     * Extract document info dari filename dengan pattern yang akurat
     */
private function extractDocumentInfo($filename)
{
    $filenameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    
    // ✅ UPDATED: Pattern lebih spesifik dengan anchoring
    $patterns = [
        '/^Invoice(\d{10,})$/i' => 'INVOICE',
        '/^PackingList(\d{10,})$/i' => 'PACKING_LIST',
        '/^PaymentIntruction(\d{10,})$/i' => 'PAYMENT_INTRUCTION', // Typo SAP yang valid
      
      
    ];

    foreach ($patterns as $pattern => $documentType) {
        if (preg_match($pattern, $filenameWithoutExt, $matches)) {
            Log::info("✅ Pattern MATCHED", [
                'filename' => $filename,
                'pattern' => $pattern,
                'document_type' => $documentType,
                'billing_document' => $matches[1]
            ]);
            
            return [
                'document_type' => $documentType,
                'billing_document' => $matches[1],
                'original_filename' => $filename
            ];
        }
    }

    Log::warning("❌ No pattern matched for filename: {$filename}");
    return null;
}
    /**
     * Cari delivery order berdasarkan billing document
     */
    private function findDeliveryByBillingDocument($billingDocument)
    {
        try {
            // Cari di billing_status table terlebih dahulu
            $billingStatus = DB::table('billing_statuses')
                ->where('billing_document', $billingDocument)
                ->first();

            if ($billingStatus) {
                return [
                    'delivery_order' => $billingStatus->delivery_order,
                    'customer_name' => $billingStatus->customer_name,
                    'billing_document' => $billingDocument
                ];
            }

            // Jika tidak ada, coba cari dari SAP data yang ter-cache
            $sapData = \App\Models\SapDataStorage::getMainBillingData();
            
            if ($sapData && is_array($sapData)) {
                foreach ($sapData as $record) {
                    $sapBillingDoc = $record['Billing Document'] ?? '';
                    
                    if ($sapBillingDoc === $billingDocument) {
                        return [
                            'delivery_order' => $record['Delivery'] ?? '',
                            'customer_name' => $record['Customer Name'] ?? '',
                            'billing_document' => $billingDocument
                        ];
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} Error finding delivery: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Upload file ke system dengan validasi
     */
private function uploadFileToSystem($file, $documentType, $deliveryOrder, $customerName)
{
    try {
        Log::info("📤 UPLOADING FILE", [
            'filename' => $file['filename'],
            'document_type' => $documentType,
            'delivery_order' => $deliveryOrder,
            'billing_from_filename' => $this->extractDocumentInfo($file['filename'])['billing_document'] ?? 'N/A'
        ]);

        // ✅ CRITICAL: Check if source file still exists
        if (!file_exists($file['full_path'])) {
            throw new \Exception("Source file not found: {$file['full_path']}");
        }

        // Check and delete existing
$billingDoc = $this->extractDocumentInfo($file['filename'])['billing_document'] ?? null;

$existing = DocumentUpload::where('billing_document', $billingDoc) // 🔑 WAJIB
    ->where('document_type', $documentType)
    ->first();


        if ($existing) {
            $oldPath = storage_path('app/public/' . $existing->file_path);
            if (File::exists($oldPath)) {
                File::delete($oldPath);
            }
            $existing->delete();
            Log::info("🗑️ Deleted existing document: {$documentType}");
        }

        // Generate unique filename
        $extension = pathinfo($file['filename'], PATHINFO_EXTENSION) ?: 'pdf';
        $newFilename = $billingDoc . '_' . $documentType . '_auto_' . time() . '.' . $extension;

        $storageFolder = storage_path('app/public/documents');
        $storagePath = $storageFolder . '/' . $newFilename;

        // ✅ CRITICAL: Ensure folder exists with proper permissions
        if (!is_dir($storageFolder)) {
            if (!mkdir($storageFolder, 0755, true)) {
                throw new \Exception("Failed to create storage folder: {$storageFolder}");
            }
        }

        // ✅ CRITICAL: Verify folder is writable
        if (!is_writable($storageFolder)) {
            throw new \Exception("Storage folder not writable: {$storageFolder}");
        }

        // Copy file with error checking
        Log::info("📋 Copying file", [
            'from' => $file['full_path'],
            'to' => $storagePath,
            'source_size' => filesize($file['full_path'])
        ]);

        if (!copy($file['full_path'], $storagePath)) {
            $lastError = error_get_last();
            throw new \Exception("Failed to copy file: " . ($lastError['message'] ?? 'Unknown error'));
        }

        // ✅ CRITICAL: Verify copied file
        if (!file_exists($storagePath)) {
            throw new \Exception("File not found after copy: {$storagePath}");
        }

        $copiedSize = filesize($storagePath);
        if ($copiedSize !== $file['size']) {
            throw new \Exception("File size mismatch after copy. Expected: {$file['size']}, Got: {$copiedSize}");
        }

$billingDoc = $this->extractDocumentInfo($file['filename'])['billing_document'] ?? $deliveryOrder;

$uploadData = [
    'delivery_order' => $deliveryOrder,
    'billing_document' => $billingDoc, // ✅ TAMBAH INI
    'customer_name' => $customerName,
    'document_type' => $documentType,
    'file_name' => $file['filename'],
    'file_path' => 'documents/' . $newFilename,
    'file_type' => $extension,
    'file_size' => $file['size'],
    'uploaded_at' => now(),
    'uploaded_by' => 'Smartform Auto-Upload',
    'team' => 'Finance',
    'uploaded_from' => 'smartform', // ← TAMBAH BARIS INI
    'notes' => "Auto-uploaded from Z:\\sd\\{$file['filename']}"
];

        $documentUpload = DocumentUpload::create($uploadData);

        // Update billing status
        try {
  BillingStatus::updateStatusByBilling(
    $billingDoc,
    true
);

        } catch (\Exception $e) {
            Log::warning("Status update failed: " . $e->getMessage());
        }

        Log::info("✅ UPLOAD SUCCESS", [
            'filename' => $file['filename'],
            'storage_id' => $documentUpload->id,
            'storage_path' => $storagePath,
            'copied_size' => $copiedSize
        ]);

        return [
            'filename' => $file['filename'],
            'document_type' => $documentType,
            'delivery_order' => $deliveryOrder,
            'customer_name' => $customerName,
            'storage_id' => $documentUpload->id,
            'success' => true,
            'ignored' => false
        ];

    } catch (\Exception $e) {
        Log::error("❌ UPLOAD FAILED: {$file['filename']}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'filename' => $file['filename'],
            'error' => $e->getMessage(),
            'success' => false,
            'ignored' => false
        ];
    }
}

    /**
     * Cari file yang matching dengan billing document
     */
    private function findMatchingFiles($billingDocument)
    {
        $allFiles = $this->scanSmartformFolder();
        $matchingFiles = [];

        foreach ($allFiles as $file) {
            $extracted = $this->extractDocumentInfo($file['filename']);
            
            if ($extracted && $extracted['billing_document'] === $billingDocument) {
                $matchingFiles[] = array_merge($file, $extracted);
            }
        }

        return $matchingFiles;
    }

    /**
     * Process files untuk delivery tertentu
     */
    private function processFilesForDelivery($matchingFiles, $deliveryOrder, $customerName)
    {
        $results = [
            'uploaded' => 0,
            'files' => []
        ];

        foreach ($matchingFiles as $file) {
            $uploadResult = $this->uploadFileToSystem(
                $file, 
                $file['document_type'], 
                $deliveryOrder, 
                $customerName
            );
            

            if ($uploadResult['success']) {
                $results['uploaded']++;
                $results['files'][] = $uploadResult;
            }
        }

        return $results;
    }

    /**
     * Get active delivery orders dari database
     */
    private function getActiveDeliveryOrders($limit = 50)
    {
        try {
            // Get dari SAP data cache atau billing_status
            $deliveries = DB::table('billing_statuses')
                ->select('delivery_order', 'customer_name', 'billing_document')
                ->whereIn('status', ['outstanding', 'progress', 'completed'])
                ->limit($limit)
                ->get()
                ->toArray();

            return array_map(function($delivery) {
                return [
                    'delivery_order' => $delivery->delivery_order,
                    'customer_name' => $delivery->customer_name,
                    'billing_document' => $delivery->billing_document
                ];
            }, $deliveries);

        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} Error getting active deliveries: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cache management untuk file yang sudah diproses
     */
    private function getProcessedFilesCache()
    {
        return Cache::get('smartform_processed_files', []);
    }

    private function updateProcessedFilesCache($allFiles)
    {
        $fileKeys = array_map(function($file) {
            return $file['filename'] . '_' . $file['modified_time'];
        }, $allFiles);

        Cache::put('smartform_processed_files', $fileKeys, now()->addHours(24));
    }

    /**
     * Get debug info untuk troubleshooting
     */
    private function getDebugInfo($billingDocument)
    {
        $allFiles = $this->scanSmartformFolder();
        
        return [
            'folder_path' => $this->smartformFolder,
            'folder_accessible' => is_dir($this->smartformFolder),
            'total_files' => count($allFiles),
            'billing_document_searched' => $billingDocument,
            'expected_patterns' => [
                "Invoice{$billingDocument}",
                "PackingList{$billingDocument}",
                "PaymentIntruction{$billingDocument}",
                "ContainerLoadPlan{$billingDocument}"
            ],
            'all_files' => array_column($allFiles, 'filename'),
            'potential_matches' => array_filter($allFiles, function($file) use ($billingDocument) {
                return strpos($file['filename'], $billingDocument) !== false;
            })
        ];
    }

    /**
     * Health check untuk monitoring system
     */
    public function healthCheck()
    {
        try {
            $health = [
                'folder_accessible' => is_dir($this->smartformFolder),
                'folder_path' => $this->smartformFolder,
                'total_files' => 0,
                'writable_storage' => is_writable(storage_path('app/public/documents')),
                'database_connection' => true,
                'cache_working' => true,
                'timestamp' => now()->toDateTimeString()
            ];

            if ($health['folder_accessible']) {
                $allFiles = $this->scanSmartformFolder();
                $health['total_files'] = count($allFiles);
                $health['recent_files'] = array_slice($allFiles, -5);
            }

            // Test database
            try {
                DB::connection()->getPdo();
            } catch (\Exception $e) {
                $health['database_connection'] = false;
                $health['database_error'] = $e->getMessage();
            }

            // Test cache
            try {
                Cache::put('health_check_test', time(), 60);
                $health['cache_working'] = Cache::get('health_check_test') !== null;
            } catch (\Exception $e) {
                $health['cache_working'] = false;
                $health['cache_error'] = $e->getMessage();
            }

            return response()->json([
                'success' => true,
                'health' => $health
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Batch upload untuk buyer (OPTIMIZED)
     * 
     * Perubahan dari versi lama:
     * 1. Scan Z:\sd hanya SEKALI, bukan per delivery
     * 2. Build file index (billing_document => files) untuk lookup O(1)
     * 3. Batch query existing documents — 1 query, bukan N query
     * 4. Hapus usleep(100000) — tidak ada alasan untuk delay
     */
    public function batchUploadForBuyer(Request $request)
    {
        try {
            $startTime = microtime(true);
            Log::info("{$this->logPrefix} BATCH UPLOAD START (OPTIMIZED)");
            
            $validated = $request->validate([
                'deliveries' => 'required|array|min:1',
                'deliveries.*.delivery_order' => 'required|string',
                'deliveries.*.customer_name' => 'required|string',
                'deliveries.*.billing_document' => 'nullable|string'
            ]);

            $deliveries = $validated['deliveries'];
            
            if (!is_dir($this->smartformFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder Z:\\sd tidak dapat diakses'
                ], 500);
            }

            // ✅ FIX #1: Scan Z:\sd SEKALI SAJA (bukan per delivery)
            $allFiles = $this->scanSmartformFolder();

            Log::info("{$this->logPrefix} Folder scanned once", [
                'total_files' => count($allFiles),
                'deliveries_to_process' => count($deliveries)
            ]);

            // ✅ FIX #2: Build index billing_document → files
            // Sehingga lookup per delivery = O(1), tidak scan ulang
            $fileIndex = [];
            foreach ($allFiles as $file) {
                $extracted = $this->extractDocumentInfo($file['filename']);
                if ($extracted) {
                    $billingDoc = $extracted['billing_document'];
                    if (!isset($fileIndex[$billingDoc])) {
                        $fileIndex[$billingDoc] = [];
                    }
                    $fileIndex[$billingDoc][] = array_merge($file, $extracted);
                }
            }

            Log::info("{$this->logPrefix} File index built", [
                'indexed_billing_docs' => count($fileIndex)
            ]);

            // ✅ FIX #3: Batch query existing documents SEKALI
            // Ambil semua billing_document yang sudah punya upload
            $allBillingDocs = array_filter(array_column($deliveries, 'billing_document'));
            $existingUploads = [];
            if (!empty($allBillingDocs)) {
                $rows = DB::table('document_uploads')
                    ->whereIn('billing_document', $allBillingDocs)
                    ->select('billing_document', 'document_type')
                    ->get();

                foreach ($rows as $row) {
                    $existingUploads[$row->billing_document][$row->document_type] = true;
                }
            }

            $totalUploaded = 0;
            $totalProcessed = 0;
            $processedDeliveries = [];

            foreach ($deliveries as $delivery) {
                $deliveryOrder = $delivery['delivery_order'];
                $customerName = $delivery['customer_name'];
                $billingDoc = $delivery['billing_document'] ?? $deliveryOrder;

                try {
                    // ✅ FIX #2: Lookup dari index — tidak scan folder lagi
                    $matchingFiles = $fileIndex[$billingDoc] ?? [];

                    if (!empty($matchingFiles)) {
                        $results = $this->processFilesForDelivery(
                            $matchingFiles,
                            $deliveryOrder,
                            $customerName
                        );

                        if ($results['uploaded'] > 0) {
                            $totalUploaded += $results['uploaded'];
                            $processedDeliveries[] = [
                                'delivery_order' => $deliveryOrder,
                                'uploaded_count' => $results['uploaded']
                            ];
                        }
                    }

                } catch (\Exception $e) {
                    Log::error("{$this->logPrefix} Error processing {$deliveryOrder}: " . $e->getMessage());
                }

                $totalProcessed++;
                // ✅ FIX #4: Hapus usleep(100000) — tidak perlu delay buatan
            }

            $duration = round(microtime(true) - $startTime, 2);

            Log::info("{$this->logPrefix} BATCH UPLOAD COMPLETED", [
                'duration_seconds' => $duration,
                'total_processed' => $totalProcessed,
                'total_uploaded' => $totalUploaded,
            ]);

            return response()->json([
                'success' => true,
                'message' => $totalUploaded > 0
                    ? "Berhasil upload {$totalUploaded} file dalam {$duration} detik"
                    : "Tidak ada file baru ditemukan di Z:\\sd",
                'total_processed' => $totalProcessed,
                'total_uploaded' => $totalUploaded,
                'processed_deliveries' => $processedDeliveries,
                'duration_seconds' => $duration
            ]);

        } catch (\Exception $e) {
            Log::error("{$this->logPrefix} Batch upload failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Batch upload gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}