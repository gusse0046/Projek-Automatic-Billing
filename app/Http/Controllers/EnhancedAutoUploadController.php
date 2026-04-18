<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\BillingStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EnhancedAutoUploadController extends Controller
{
    /**
     * OPTIMIZED: Auto-upload dengan monitoring real-time
     */
    public function autoUploadFromSmartform(Request $request)
    {
        try {
            Log::info('=== ENHANCED AUTO-UPLOAD STARTED ===', [
                'request_data' => $request->all(),
                'timestamp' => now()->toDateTimeString()
            ]);

            $validated = $request->validate([
                'delivery_order' => 'required|string',
                'customer_name' => 'required|string',
                'billing_document' => 'nullable|string'
            ]);

            $deliveryOrder = $validated['delivery_order'];
            $customerName = $validated['customer_name'];
            $billingDocument = $validated['billing_document'] ?: $deliveryOrder;

            // UPDATED: Path ke folder Z:\sd
            $smartformFolder = 'Z:\\sd';
            
            if (!is_dir($smartformFolder)) {
                Log::error('Smartform folder not accessible', [
                    'folder_path' => $smartformFolder
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Smartform folder D:\\sd not accessible from server'
                ]);
            }

            // ENHANCED: Pattern matching dengan typo handling
            $documentPatterns = [
                'INVOICE' => [
                    "Invoice{$billingDocument}",
                    "Invoice{$billingDocument}.pdf",
                    "invoice{$billingDocument}",
                    "invoice{$billingDocument}.pdf"
                ],
                'PACKING_LIST' => [
                    "PackingList{$billingDocument}",
                    "PackingList{$billingDocument}.pdf",
                    "packinglist{$billingDocument}",
                    "packinglist{$billingDocument}.pdf"
                ],
                'PAYMENT_INTRUCTION' => [
                    // FIXED: Handle both typo and correct spelling
                    "PaymentIntruction{$billingDocument}",      // Typo version
                    "PaymentIntruction{$billingDocument}.pdf",  // Typo version
                    "PaymentIntruction{$billingDocument}",     // Correct version
                    "PaymentIntruction{$billingDocument}.pdf", // Correct version
                    "paymentintruction{$billingDocument}",      // Lowercase typo
                    "paymentintruction{$billingDocument}.pdf",  // Lowercase typo
                ],
           
            ];

            $processedFiles = [];
            $foundFiles = 0;
            $errors = [];

            foreach ($documentPatterns as $documentType => $patterns) {
                $found = false;
                
                foreach ($patterns as $pattern) {
                    $filePath = $smartformFolder . '\\' . $pattern;
                    
                    Log::info("Checking for file: {$pattern}");
                    
                    if (file_exists($filePath)) {
                        try {
                            Log::info("✅ FOUND: {$pattern}");
                            
                            // Delete existing document
                            $this->deleteExistingDocument($deliveryOrder, $customerName, $documentType);

                            // Process file upload
                            $uploadResult = $this->processFileUpload(
                                $filePath, 
                                $pattern, 
                                $deliveryOrder, 
                                $customerName, 
                                $documentType
                            );

                            if ($uploadResult['success']) {
                                $processedFiles[] = $uploadResult['file_info'];
                                $foundFiles++;
                                $found = true;
                                break; // Stop searching once found
                            } else {
                                $errors[] = $uploadResult['error'];
                            }
                            
                        } catch (\Exception $e) {
                            $errors[] = "Error processing {$pattern}: " . $e->getMessage();
                            Log::error("Error processing {$pattern}: " . $e->getMessage());
                        }
                    }
                }
                
                if (!$found) {
                    Log::info("❌ NOT FOUND: {$documentType} for billing {$billingDocument}");
                }
            }

            // Update billing status after successful uploads
            if ($foundFiles > 0) {
                try {
                    BillingStatus::updateStatus($deliveryOrder, $customerName, null, true);
                    Log::info("Billing status updated after auto-upload");
                } catch (\Exception $e) {
                    Log::warning("Failed to update billing status: " . $e->getMessage());
                }
            }

            // Enhanced response with debug info
            $response = [
                'success' => true,
                'processed_files' => $foundFiles,
                'total_expected' => count($documentPatterns),
                'files' => $processedFiles,
                'errors' => $errors,
                'debug_info' => $this->getDebugInfo($smartformFolder, $billingDocument, $documentPatterns),
                'message' => $foundFiles > 0 
                    ? "Successfully processed {$foundFiles} documents from D:\\sd" 
                    : "No matching documents found in D:\\sd for billing document {$billingDocument}"
            ];

            Log::info('=== ENHANCED AUTO-UPLOAD COMPLETED ===', [
                'delivery_order' => $deliveryOrder,
                'billing_document' => $billingDocument,
                'found_files' => $foundFiles,
                'total_expected' => count($documentPatterns)
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Enhanced auto-upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Auto-upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete existing document to prevent duplicates
     */
    private function deleteExistingDocument($deliveryOrder, $customerName, $documentType)
    {
        $existingDocuments = DocumentUpload::where('delivery_order', $deliveryOrder)
            ->where('customer_name', $customerName)
            ->where('document_type', $documentType)
            ->get();
        
        if ($existingDocuments->isEmpty()) {
            return;
        }
        
        foreach ($existingDocuments as $existing) {
            $oldFilePath = storage_path('app/public/' . $existing->file_path);
            if (File::exists($oldFilePath)) {
                File::delete($oldFilePath);
                Log::info("Deleted old file: {$oldFilePath}");
            }
            $existing->delete();
            Log::info("Deleted existing document record: {$documentType} (ID: {$existing->id})");
        }
    }

    /**
     * Process individual file upload
     */
    private function processFileUpload($filePath, $fileName, $deliveryOrder, $customerName, $documentType)
    {
        try {
            // Generate unique filename
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION) ?: 'pdf';
            $newFileName = $deliveryOrder . '_' . $documentType . '_auto_' . time() . '.' . $fileExtension;
            $storageFolder = storage_path('app/public/documents');
            $storagePath = $storageFolder . '/' . $newFileName;

            // Ensure folder exists
            if (!is_dir($storageFolder)) {
                mkdir($storageFolder, 0755, true);
            }

            // Copy file from D:\sd to storage
            if (!copy($filePath, $storagePath)) {
                throw new \Exception("Failed to copy file from D:\\sd to storage");
            }

            // Create database record
            $uploadData = [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'document_type' => $documentType,
                'file_name' => $fileName,
                'file_path' => 'documents/' . $newFileName,
                'file_type' => $fileExtension,
                'file_size' => filesize($filePath),
                'uploaded_at' => now(),
                'uploaded_by' => 'Auto-Upload System',
                'team' => 'Finance',
                'notes' => "Auto-uploaded from D:\\sd\\{$fileName}"
            ];

            $documentUpload = DocumentUpload::create($uploadData);

            return [
                'success' => true,
                'file_info' => [
                    'id' => $documentUpload->id,
                    'filename' => $fileName,
                    'document_type' => $documentType,
                    'status' => 'uploaded',
                    'size' => filesize($filePath),
                    'storage_path' => $storagePath
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Failed to process {$fileName}: " . $e->getMessage()
            ];
        }
    }

    /**
     * Get debug information
     */
    private function getDebugInfo($smartformFolder, $billingDocument, $documentPatterns)
    {
        $debugInfo = [
            'folder_path' => $smartformFolder,
            'folder_accessible' => is_dir($smartformFolder),
            'billing_document' => $billingDocument,
            'expected_patterns' => [],
            'all_files_in_folder' => [],
            'potential_matches' => []
        ];

        // Get expected patterns
        foreach ($documentPatterns as $docType => $patterns) {
            $debugInfo['expected_patterns'][$docType] = $patterns;
        }

        // Scan all files in folder
        try {
            $allFiles = scandir($smartformFolder);
            $debugInfo['all_files_in_folder'] = array_filter($allFiles, function($file) use ($smartformFolder) {
                return $file !== '.' && $file !== '..' && is_file($smartformFolder . '\\' . $file);
            });

            // Find potential matches
            foreach ($debugInfo['all_files_in_folder'] as $file) {
                if (stripos($file, $billingDocument) !== false) {
                    $debugInfo['potential_matches'][] = $file;
                }
            }

        } catch (\Exception $e) {
            $debugInfo['scan_error'] = $e->getMessage();
        }

        return $debugInfo;
    }

    /**
     * REAL-TIME: Monitor folder for new files
     */
    public function monitorSmartformFolder()
    {
        try {
            $smartformFolder = 'D:\\sd';
            
            if (!is_dir($smartformFolder)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Folder D:\\sd not accessible'
                ]);
            }

            $newFiles = $this->scanForNewFiles($smartformFolder);
            $processedCount = 0;

            foreach ($newFiles as $file) {
                $result = $this->processNewFile($file);
                if ($result['success']) {
                    $processedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'scanned_files' => count($newFiles),
                'processed_files' => $processedCount,
                'timestamp' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            Log::error('Folder monitoring failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Monitoring failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Scan for new files based on modification time
     */
    private function scanForNewFiles($folder)
    {
        $newFiles = [];
        $cutoffTime = now()->subMinutes(5)->timestamp; // Files modified in last 5 minutes

        $files = scandir($folder);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $folder . '\\' . $file;
            if (is_file($filePath) && filemtime($filePath) > $cutoffTime) {
                $newFiles[] = [
                    'filename' => $file,
                    'path' => $filePath,
                    'modified' => filemtime($filePath)
                ];
            }
        }

        return $newFiles;
    }

    /**
     * Process individual new file
     */
    private function processNewFile($fileInfo)
    {
        $filename = $fileInfo['filename'];
        
        // Extract billing document number from filename
        $patterns = [
            '/Invoice(\d{10,})/' => 'INVOICE',
            '/PackingList(\d{10,})/' => 'PACKING_LIST',
            '/PaymentIntruction(\d{10,})/' => 'PAYMENT_INTRUCTION', // Typo version
            '/PaymentIntruction(\d{10,})/' => 'PAYMENT_INTRUCTION', // Correct version
           
        ];

        foreach ($patterns as $pattern => $documentType) {
            if (preg_match($pattern, $filename, $matches)) {
                $billingDocument = $matches[1];
                
                // Find corresponding delivery order and customer
                $billingStatus = BillingStatus::where('billing_document', $billingDocument)->first();
                
                if ($billingStatus) {
                    // Process auto-upload
                    return $this->processFileUpload(
                        $fileInfo['path'],
                        $filename,
                        $billingStatus->delivery_order,
                        $billingStatus->customer_name,
                        $documentType
                    );
                }
            }
        }

        return ['success' => false, 'error' => 'No matching pattern or billing status found'];
    }
}
