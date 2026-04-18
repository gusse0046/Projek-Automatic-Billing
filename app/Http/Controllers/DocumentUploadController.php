<?php

namespace App\Http\Controllers;

use App\Models\DocumentUpload;
use App\Models\DocumentSetting;
use App\Models\BillingStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;


class DocumentUploadController extends Controller
{
 /**
 * Ã¢Å“â€¦ COMPLETE: Upload document dengan validasi BL Entry Number
 * 
 * @param Request $request
 * @return \Illuminate\Http\JsonResponse
 */
/**
 * âœ… COMPLETE: Upload document dengan validasi Container Number untuk BL
 */
 public function upload(Request $request)
{
    // ✅ FIX 1: Start output buffering untuk menangkap output yang tidak diinginkan
    ob_start();
    
    try {
        // ✅ STEP 1: LOGGING
        Log::info('=== DOCUMENT UPLOAD REQUEST START ===', [
            'request_all' => $request->except('document_file'),
            'has_file' => $request->hasFile('document_file'),
            'user' => Auth::user()->name ?? 'Guest',
            'document_type' => $request->input('document_type'),
            'delivery_order' => $request->input('delivery_order'),
            'timestamp' => now()->toDateTimeString()
        ]);

$validated = $request->validate([
    'delivery_order' => 'required|string',
    'customer_name' => 'required|string',
    'billing_document' => 'nullable|string',  // ✅ BARIS BARU - TAMBAH INI
    'container_number' => 'nullable|string',  // ✅ BARIS BARU - TAMBAH INI
    'document_type' => 'required|string',
    'document_file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls,xlsm|max:10240',
    'team' => 'nullable|string|in:Finance,Exim,Logistic',
    'notes' => 'nullable|string'
]);

        $deliveryOrder = $validated['delivery_order'];
        $customerName = $validated['customer_name'];
        $documentType = $validated['document_type'];
        $team = $validated['team'] ?? $this->determineTeamFromDocumentType($documentType);
        $notes = $validated['notes'] ?? '';
        
        $billingDocument = $validated['billing_document'] ?? $request->input('billing_document') ?? $deliveryOrder;
        $containerNumber = $validated['container_number'] ?? $request->input('container_number') ?? null;

Log::info('✅ Validation passed', [
    'delivery_order' => $deliveryOrder,
    'customer_name' => $customerName,
    'billing_document' => $billingDocument,  // ✅ BARIS BARU
    'container_number' => $containerNumber,  // ✅ BARIS BARU
    'document_type' => $documentType,
    'team' => $team
]);

     $query = DocumentUpload::where('delivery_order', $deliveryOrder)
    ->where('customer_name', $customerName)
    ->where('billing_document', request('billing_document'));
    $existingCount = $query
    ->where('document_type', $documentType)
    ->count();
        
        if ($existingCount > 0) {
            Log::info('📊 Existing documents detected', [
                'document_type' => $documentType,
                'existing_count' => $existingCount,
                'action' => 'Creating new record (no replacement)'
            ]);
        }

        // ✅ STEP 4: GET FILE INFO
        $file = $request->file('document_file');
        $originalName = $file->getClientOriginalName();
        $fileExtension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        Log::info('📄 File details', [
            'original_name' => $originalName,
            'extension' => $fileExtension,
            'size_mb' => round($fileSize / 1024 / 1024, 2)
        ]);

        // ✅ STEP 5: GENERATE UNIQUE FILENAME
        $safeDeliveryOrder = preg_replace('/[^A-Za-z0-9_-]/', '_', $deliveryOrder);
        $safeCustomerName = preg_replace('/[^A-Za-z0-9_-]/', '_', $customerName);
        $safeDocumentType = preg_replace('/[^A-Za-z0-9_-]/', '_', $documentType);

        $uniqueId = uniqid('', true);
        $timestamp = now()->format('YmdHis');
        $microtime = substr(microtime(), 2, 6);

        $fileName = "{$safeDeliveryOrder}_{$safeCustomerName}_{$safeDocumentType}_{$timestamp}_{$microtime}_{$uniqueId}.{$fileExtension}";

        Log::info('✅ Unique filename generated', [
            'filename' => $fileName
        ]);

        // ✅ STEP 6: SAVE FILE TO STORAGE
        $filePath = $file->storeAs('documents', $fileName, 'public');

        Log::info('💾 File saved to storage', [
            'file_path' => $filePath
        ]);

$uploadData = [
    'delivery_order' => $deliveryOrder,
    'customer_name' => $customerName,
    'billing_document' => $billingDocument,  // ✅ BARIS BARU - TAMBAH INI
    'container_number' => $containerNumber,  // ✅ BARIS BARU - TAMBAH INI
    'document_type' => $documentType,
    'file_name' => $originalName,
    'file_path' => $filePath,
    'file_type' => $fileExtension,
    'file_size' => $fileSize,
    'uploaded_at' => now(),
    'uploaded_by' => Auth::user()->name ?? 'System',
    'notes' => $notes,
    'team' => $team,
    'uploaded_from' => request()->is('*/exim*') ? 'exim' : 'manual'
];

        $documentUpload = DocumentUpload::create($uploadData);

Log::info('✅ Document saved to database', [
    'id' => $documentUpload->id,
    'document_type' => $documentType,
    'billing_document' => $billingDocument,  // ✅ BARIS BARU
    'team' => $team,
    'uploaded_from' => $uploadData['uploaded_from']
]);

        // ✅ STEP 8: NOTIFY FINANCE DASHBOARD (wrapped in try-catch)
        try {
            $this->notifyFinanceDashboard($documentUpload);
            Log::info('📢 Finance dashboard notified', [
                'document_id' => $documentUpload->id
            ]);
        } catch (\Exception $e) {
            // ✅ FIX 2: Jangan biarkan exception dari notification merusak response
            Log::warning('⚠️ Failed to notify Finance dashboard: ' . $e->getMessage());
        }

        // ✅ STEP 9: UPDATE BILLING STATUS (wrapped in try-catch)
        try {
            BillingStatus::updateStatusByBilling(
    $billingDocument,
    true
);

            Log::info('📝 Billing status updated');
        } catch (\Exception $statusError) {
            Log::warning('⚠️ Failed to update billing status: ' . $statusError->getMessage());
        }

        // ✅ STEP 10: RETURN SUCCESS RESPONSE
        Log::info('=== DOCUMENT UPLOAD SUCCESS ===', [
            'document_id' => $documentUpload->id,
            'message' => 'Document uploaded successfully'
        ]);

        // ✅ FIX 3: Clear output buffer dan return clean JSON
        ob_end_clean();
        
return response()->json([
    'success' => true,
    'message' => 'Document uploaded successfully',
    'document' => [
        'id' => $documentUpload->id,
        'delivery_order' => $deliveryOrder,
        'customer_name' => $customerName,
        'billing_document' => $billingDocument,  // ✅ BARIS BARU
        'container_number' => $containerNumber,  // ✅ BARIS BARU
        'document_type' => $documentType,
        'file_name' => $originalName,
        'team' => $team,
        'uploaded_at' => $documentUpload->uploaded_at->toDateTimeString(),
        'uploaded_by' => $documentUpload->uploaded_by,
        'uploaded_from' => $uploadData['uploaded_from']
    ]
        ], 200)->header('Content-Type', 'application/json');

    } catch (\Illuminate\Validation\ValidationException $e) {
        // ✅ FIX 4: Clear buffer pada validation error
        ob_end_clean();
        
        Log::error('❌ Validation failed', [
            'errors' => $e->errors()
        ]);
        
        return response()->json([
            'success' => false,
            'error_type' => 'validation_error',
            'message' => 'Validation failed: ' . implode(', ', array_flatten($e->errors())),
            'validation_errors' => $e->errors()
        ], 422)->header('Content-Type', 'application/json');
        
    } catch (\Exception $e) {
        // ✅ FIX 5: Clear buffer pada exception
        ob_end_clean();
        
        Log::error('❌ Upload failed with exception', [
            'error' => $e->getMessage(),
            'error_type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'error_type' => 'server_error',
            'message' => 'Upload failed: ' . $e->getMessage()
        ], 500)->header('Content-Type', 'application/json');
    }
}

    /**
     * Determine team from document type
     */
   private function determineTeamFromDocumentType($documentType)
    {
        $financeDocuments = ['FAKTUR_PAJAK', 'INVOICE', 'COMMERCIAL_INVOICE', 'PROFORMA_INVOICE'];
        $eximDocuments = ['BL', 'PACKING_LIST', 'COO', 'FUMIGATION', 'PHYTOSANITARY', 'LACEY_ACT'];
        
        if (in_array($documentType, $financeDocuments)) {
            return 'Finance';
        } elseif (in_array($documentType, $eximDocuments)) {
            return 'Exim';
        }
        
        return 'Logistic';
    }

    /**
     * Get uploads untuk delivery tertentu
     */
public function getUploads($deliveryOrder, $customerName)
{
    try {
        $isEximDashboard =
            request()->is('*/exim*') ||
            request()->header('X-Dashboard') === 'exim' ||
            (request()->header('Referer') && str_contains(request()->header('Referer'), '/exim'));

        // ✅ BASE QUERY (DO-based fallback TETAP ADA)
        $query = DocumentUpload::where('delivery_order', $deliveryOrder)
            ->where('customer_name', $customerName);

        // ✅ CONDITIONAL BILLING FILTER (INI KUNCI BUG FIX)
        if (request()->filled('billing_document')) {
            $query->where('billing_document', request('billing_document'));
        }

        // ✅ EXIM hanya lihat upload dari EXIM
        if ($isEximDashboard) {
            $query->where('uploaded_from', 'exim');
        }

        $uploads = $query
            ->orderBy('document_type')
            ->orderBy('uploaded_at', 'desc')
            ->get();

        $uploadsGrouped = $uploads->groupBy('document_type');

        $uploadsFormatted = [];
        foreach ($uploadsGrouped as $docType => $docs) {
            $uploadsFormatted[$docType] = $docs->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'file_name' => $doc->file_name,
                    'file_type' => $doc->file_type,
                    'file_size' => $doc->file_size,
                    'uploaded_at' => $doc->uploaded_at,
                    'uploaded_by' => $doc->uploaded_by,
                    'document_type' => $doc->document_type,
                    'notes' => $doc->notes,
                    'team' => $doc->team,
                    'uploaded_from' => $doc->uploaded_from,
                ];
            })->toArray();
        }

        return response()->json([
            'success' => true,
            'uploads' => $uploadsFormatted,
            'total_count' => $uploads->count(),
        ]);

    } catch (\Exception $e) {
        Log::error('getUploads error', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to load uploads'
        ], 500);
    }
}


    /**
     * Download document
     */
    public function download($uploadId)
    {
        try {
            $upload = DocumentUpload::findOrFail($uploadId);
            $filePath = storage_path('app/public/' . $upload->file_path);
            
            if (!File::exists($filePath)) {
                abort(404, 'File not found');
            }

            return response()->download($filePath, $upload->file_name);

        } catch (\Exception $e) {
            Log::error('Download failed: ' . $e->getMessage());
            abort(500, 'Download failed');
        }
    }

    /**
     * Delete document
     */
    public function delete($uploadId)
    {
        try {
            $upload = DocumentUpload::findOrFail($uploadId);
            
            $deliveryOrder = $upload->delivery_order;
            $customerName = $upload->customer_name;
            
            // Delete file from storage
            $filePath = storage_path('app/public/' . $upload->file_path);
            if (File::exists($filePath)) {
                File::delete($filePath);
            }
            
            // Delete from database
            $upload->delete();

            // Update status after delete
            try {
                BillingStatus::updateStatus($deliveryOrder, $customerName, null, true);
            } catch (\Exception $statusError) {
                Log::warning('Failed to update status after delete: ' . $statusError->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

  /**
 * FIXED: Preview document dengan better error handling dan CORS headers
 */
public function preview($uploadId)
{
    try {
        $upload = DocumentUpload::findOrFail($uploadId);
        $filePath = storage_path('app/public/' . $upload->file_path);
        
        // Check if file exists
        if (!File::exists($filePath)) {
            Log::error('Preview file not found', [
                'upload_id' => $uploadId,
                'file_path' => $filePath
            ]);
            
            abort(404, 'File not found in storage');
        }

        $fileExtension = strtolower($upload->file_type);
        $previewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        
        // Check if file type is previewable
        if (!in_array($fileExtension, $previewableTypes)) {
            Log::warning('File type not previewable', [
                'upload_id' => $uploadId,
                'file_type' => $fileExtension
            ]);
            
            // Return download instead
            return response()->download($filePath, $upload->file_name);
        }

        // Define MIME types
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];

        $mimeType = $mimeTypes[$fileExtension] ?? 'application/octet-stream';
        
        Log::info('Preview document', [
            'upload_id' => $uploadId,
            'file_name' => $upload->file_name,
            'mime_type' => $mimeType,
            'file_size' => filesize($filePath)
        ]);
        
        // Return file with proper headers
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $upload->file_name . '"',
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff'
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::error('Preview: Document not found in database', [
            'upload_id' => $uploadId
        ]);
        abort(404, 'Document not found');
        
    } catch (\Exception $e) {
        Log::error('Preview error', [
            'upload_id' => $uploadId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        abort(500, 'Preview failed: ' . $e->getMessage());
    }
}

    /**
     * Get allowed documents for customer
     */
    public function getAllowedDocuments($customerName, $team = 'Exim')
    {
        try {
            $documentSetting = DocumentSetting::where('customer_name', $customerName)->first();
            
            if (!$documentSetting) {
                // Return default documents jika tidak ada setting
                $defaultDocuments = $this->getDefaultDocumentsByTeam($team);
                
                return response()->json([
                    'success' => true,
                    'allowed_documents' => $defaultDocuments,
                    'customer_name' => $customerName,
                    'team' => $team,
                    'message' => 'Using default documents - no specific settings found'
                ]);
            }

            $allowedDocuments = $documentSetting->allowed_documents ?? [];

            return response()->json([
                'success' => true,
                'allowed_documents' => $allowedDocuments,
                'customer_name' => $customerName,
                'team' => $team
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting allowed documents: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'allowed_documents' => []
            ], 500);
        }
    }

    /**
     * Get default documents by team
     */
    private function getDefaultDocumentsByTeam($team)
    {
        $defaults = [
            'Finance' => ['INVOICE', 'PACKING_LIST', 'PAYMENT_INTRUCTION'],
            'Logistic' => ['CARB','CONTAINER_LOAD','CONTAINER_CHEKLIST'],
            'Exim' => ['PEB', 'COO', 'FUMIGASI', 'PYTOSANITARY', 'LACEY_ACT', 'ISF', 'TSCA','BILL OF LADING', 'CONTAINER_LOAD_PLAN','CONTAINER_CHEKLIST', 'GCC', 'PPDF', 'VLEGAL', 'AWB','CONTAINER_LOAD','CARB','IREX','FLEGT']
        ];
        
        return $defaults[$team] ?? $defaults['Exim'];
    }

    /**
     * Check if document is EXIM type
     */
    private function isEximDocument($documentType)
    {
        $eximDocs = ['PEB', 'COO', 'FUMIGASI', 'PYTOSANITARY', 'LACEY_ACT', 'ISF', 'TSCA', 'GCC', 'PPDF', 'VLEGAL', 'AWB', 'BILL OF LADING', 'CONTAINER_LOAD_PLAN','CONTAINER_CHEKLIST','GCC','PPDF','VLEGAL','CONTAINER_LOAD','CARB','IREX','FLEGT'];
        return in_array($documentType, $eximDocs);
    }

    /**
     * Integration dengan Setting Document Dashboard
     */
    public function checkAllowedDocuments($deliveryOrder, $customerName)
    {
        try {
            $response = Http::timeout(10)->get(route('setting-document.get-settings', $customerName));
            $result = $response->json();
            
            if ($result['success'] && !empty($result['allowed_documents'])) {
                return response()->json([
                    'success' => true,
                    'allowed_documents' => $result['allowed_documents'],
                    'integration_status' => 'active'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'allowed_documents' => [],
                    'integration_status' => 'no_settings',
                    'message' => 'No document settings configured for this customer'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Integration check failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'allowed_documents' => [],
                'integration_status' => 'error',
                'message' => 'Settings integration unavailable'
            ]);
        }
    }

    /**
     * ✅ NEW: Get allowed documents dengan filter team (khusus untuk Logistic)
     */
    public function getAllowedDocumentsByTeam($deliveryOrder, $customerName)
    {
        try {
            $user = Auth::user();
            $userTeam = $user->team ?? 'Exim'; // Default Exim jika tidak ada team
            
            Log::info('🔍 Getting allowed documents by team', [
                'user' => $user->name,
                'team' => $userTeam,
                'customer' => $customerName
            ]);
            
            // ✅ Get buyer settings
            $documentSetting = DocumentSetting::where('customer_name', $customerName)->first();
            
            if (!$documentSetting) {
                return response()->json([
                    'success' => false,
                    'allowed_documents' => [],
                    'message' => 'No document settings configured for this customer'
                ]);
            }
            
            $buyerAllowedDocs = $documentSetting->allowed_documents ?? [];
            
            // ✅ FILTER: Jika user Logistic, hanya tampilkan CONTAINER_LOAD dan CONTAINER_CHECKLIST
            if ($userTeam === 'Logistic') {
                $logisticDocs = ['CONTAINER_LOAD', 'CONTAINER_CHECKLIST'];
                
                // ✅ INTERSECT: Ambil dokumen yang ada di buyer settings DAN di logistic docs
                $allowedDocs = array_intersect($buyerAllowedDocs, $logisticDocs);
                
                Log::info('✅ Logistic user - Filtered documents', [
                    'buyer_allowed' => $buyerAllowedDocs,
                    'logistic_docs' => $logisticDocs,
                    'final_allowed' => $allowedDocs
                ]);
                
                return response()->json([
                    'success' => true,
                    'allowed_documents' => array_values($allowedDocs), // Re-index array
                    'team' => 'Logistic',
                    'integration_status' => 'active'
                ]);
            }
            
            // ✅ EXIM/Finance: Tampilkan semua dokumen dari buyer settings
            return response()->json([
                'success' => true,
                'allowed_documents' => $buyerAllowedDocs,
                'team' => $userTeam,
                'integration_status' => 'active'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Get allowed documents by team failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'allowed_documents' => [],
                'integration_status' => 'error',
                'message' => 'Settings integration unavailable'
            ]);
        }
    }

 /**
 * Notify Finance Dashboard tentang upload baru dari EXIM
 */
private function notifyFinanceDashboard($documentUpload)
{
    try {
        Cache::forget('finance_documents_' . $documentUpload->delivery_order);
        Cache::forget('exim_documents_' . $documentUpload->delivery_order);
        
        
        Log::info('📢 Cache cleared for document upload', [
            'delivery_order' => $documentUpload->delivery_order
        ]);
        
    } catch (\Exception $e) {
        Log::warning('Failed to notify Finance dashboard: ' . $e->getMessage());
    }
}
/**
 * ENHANCED: Auto-upload dari Z:\sd dengan pattern yang lebih akurat
 */
public function enhancedAutoUpload(Request $request)
{
    return app(EnhancedAutoUploadController::class)->autoUploadFromSmartform($request);
}

public function monitorSmartformFolder(Request $request)
{
    return app(EnhancedAutoUploadController::class)->monitorSmartformFolder();
}

public function batchMonitorDocuments(Request $request)
{
    $billingDocuments = $request->get('billing_documents', []);
    $newFilesFound = 0;
    
    foreach ($billingDocuments as $billing) {
        // Check for new files for each billing document
        // Process if found
    }
    
    return response()->json([
        'success' => true,
        'checked_documents' => count($billingDocuments),
        'new_files_found' => $newFilesFound
    ]);
}

/**
 * ✅ NEW: Get progress khusus untuk EXIM dashboard (EXIM documents only)
 */
public function getStatusProgress($deliveryOrder, $customerName)
{
    try {
        Log::info('=== GET STATUS PROGRESS ===', [
            'delivery' => $deliveryOrder,
            'customer' => $customerName,
            'dashboard' => request()->header('X-Dashboard')
        ]);
        
        $customerName = urldecode($customerName);
        
        // ✅ DETECT: Apakah request dari EXIM dashboard?
        $isEximDashboard = request()->header('X-Dashboard') === 'exim';
        
        if ($isEximDashboard) {
            // ✅ EXIM ONLY PROGRESS (tidak include Finance)
            $documentSetting = DocumentSetting::where('customer_name', $customerName)->first();
            $eximRequired = $documentSetting ? $documentSetting->allowed_documents : [];
            
$uploadedQuery = DocumentUpload::where('delivery_order', $deliveryOrder)
    ->where('customer_name', $customerName)
    ->where('uploaded_from', 'exim');

if (request()->filled('billing_document')) {
    $uploadedQuery->where('billing_document', request('billing_document'));
}

$uploadedDocs = $uploadedQuery
    ->pluck('document_type')
    ->unique()
    ->values()
    ->toArray();

            
            $eximUploaded = count(array_intersect($uploadedDocs, $eximRequired));
            $eximTotal = count($eximRequired);
            $eximProgress = $eximTotal > 0 ? round(($eximUploaded / $eximTotal) * 100) : 100;
            
            $missingDocs = array_diff($eximRequired, $uploadedDocs);
            
            return response()->json([
                'success' => true,
                'progress' => [
                    'exim_progress_percentage' => $eximProgress,
                    'overall_progress_percentage' => $eximProgress, // ✅ Same as EXIM
                    'exim_uploaded' => $eximUploaded,
                    'exim_total' => $eximTotal,
                    'finance_uploaded' => 0,  // ✅ Always 0 for EXIM
                    'finance_total' => 0,     // ✅ Always 0 for EXIM
                    'missing_documents' => array_values($missingDocs),
                    'filtered_for' => 'exim_only'
                ],
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName
            ]);
        }
        
        // ✅ FALLBACK: Finance dashboard (include semua)
        $documentSetting = DocumentSetting::where('customer_name', $customerName)->first();
        $eximRequired = $documentSetting ? $documentSetting->allowed_documents : [];
        $financeRequired = ['INVOICE', 'PACKING_LIST', 'PAYMENT_INTRUCTION'];
        
$uploadedDocs = DocumentUpload::where('delivery_order', $deliveryOrder)
    ->where('customer_name', $customerName)
    ->where('billing_document', request('billing_document'))
    ->pluck('document_type')
    ->unique()
    ->toArray();


        
        $financeUploaded = count(array_intersect($uploadedDocs, $financeRequired));
        $financeTotal = count($financeRequired);
        $eximUploaded = count(array_intersect($uploadedDocs, $eximRequired));
        $eximTotal = count($eximRequired);
        
        $totalRequired = $financeTotal + $eximTotal;
        $totalUploaded = $financeUploaded + $eximUploaded;
        $overallProgress = $totalRequired > 0 ? round(($totalUploaded / $totalRequired) * 100) : 0;
        
        return response()->json([
            'success' => true,
            'progress' => [
                'overall_progress_percentage' => $overallProgress,
                'finance_uploaded' => $financeUploaded,
                'finance_total' => $financeTotal,
                'exim_uploaded' => $eximUploaded,
                'exim_total' => $eximTotal,
                'missing_documents' => array_merge(
                    array_diff($financeRequired, $uploadedDocs),
                    array_diff($eximRequired, $uploadedDocs)
                ),
                'filtered_for' => 'all'
            ]
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Progress error:', [
            'error' => $e->getMessage()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}
}