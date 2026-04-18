<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DocumentUpload;
use App\Models\DocumentSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class BillingStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order',
        'customer_name',
        'status',
        'email_sent_at',
        'sent_by',
        'notes',
        'required_documents',
        'uploaded_documents_count',
        'total_required_documents',
        'sent_to_buyer',
        'sent_to_email',
        'sent_at',
        'email_notes',
        'sent_to_emails',
        'billing_document'
    ];

    protected $casts = [
        'email_sent_at' => 'datetime',
        'sent_at' => 'datetime',
        'required_documents' => 'array',
        'sent_to_buyer' => 'boolean',
        'sent_to_emails' => 'array'
    ];

    const STATUS_OUTSTANDING = 'outstanding';
    const STATUS_PROGRESS = 'progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SENT = 'sent';

const FINANCE_REQUIRED_DOCUMENTS = [
    'INVOICE',
    'PACKING_LIST', 
    'PAYMENT_INTRUCTION', 
                    
];

    //'CARB'

    /**
     * ORIGINAL METHOD - TIDAK DIUBAH
     */
    public static function updateStatus($deliveryOrder, $customerName, $forceStatus = null, $forceRecalculate = false, $sapBillingData = null)
    {
        try {
            $existingStatus = self::where('delivery_order', $deliveryOrder)
                                ->where('customer_name', $customerName)
                                ->first();
            
            if ($existingStatus && $existingStatus->status === self::STATUS_SENT && !$forceStatus && !$forceRecalculate) {
                Log::info('Status update BLOCKED - Already sent', [
                    'delivery_order' => $deliveryOrder,
                    'customer_name' => $customerName,
                    'current_status' => self::STATUS_SENT
                ]);
                return $existingStatus;
            }
            
            $status = $forceStatus ?: self::calculateStatusStatic($deliveryOrder, $customerName, $forceRecalculate);
            
            $documentSetting = self::findDocumentSettingByCustomerName($customerName);
            $eximRequiredDocuments = $documentSetting ? $documentSetting->allowed_documents : [];
            $financeRequiredDocuments = self::FINANCE_REQUIRED_DOCUMENTS;
            $allRequiredDocuments = array_merge($eximRequiredDocuments, $financeRequiredDocuments);
            
            $uploadedCount = DocumentUpload::where('delivery_order', $deliveryOrder)
                                         ->where('customer_name', $customerName)
                                         ->distinct('document_type')
                                         ->count('document_type');
            
            // CRITICAL FIX: Resolve billing document dengan prioritas SAP data
            $billingDocument = self::resolveBillingDocumentFixed($deliveryOrder, $customerName, $sapBillingData, $existingStatus);
            
            Log::info('=== BILLING DOCUMENT RESOLUTION ===', [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'sap_billing_data' => $sapBillingData ? 'PROVIDED' : 'NOT_PROVIDED',
                'resolved_billing_document' => $billingDocument,
                'is_same_as_delivery' => $billingDocument === $deliveryOrder
            ]);
            
            $updateData = [
                'status' => $status,
                'required_documents' => $allRequiredDocuments,
                'uploaded_documents_count' => $uploadedCount,
                'total_required_documents' => count($allRequiredDocuments)
            ];
            
            // CRITICAL: Only store billing document if it's different from delivery order
            if ($billingDocument && $billingDocument !== $deliveryOrder) {
                $updateData['billing_document'] = $billingDocument;
                Log::info('✅ Valid billing document stored', [
                    'delivery_order' => $deliveryOrder,
                    'billing_document' => $billingDocument
                ]);
            } else {
                // Set to NULL if no valid billing document found
                $updateData['billing_document'] = null;
                Log::info('⚠️ No valid billing document found, setting to NULL', [
                    'delivery_order' => $deliveryOrder,
                    'attempted_billing' => $billingDocument
                ]);
            }
            
            if ($existingStatus && $existingStatus->status === self::STATUS_SENT && $status === self::STATUS_SENT) {
                unset($updateData['email_sent_at']);
                unset($updateData['sent_by']);
                unset($updateData['sent_to_buyer']);
                unset($updateData['sent_to_email']);
                unset($updateData['sent_at']);
                unset($updateData['email_notes']);
                unset($updateData['sent_to_emails']);
            }
            
            $billingStatus = self::updateOrCreate(
                [
                    'delivery_order' => $deliveryOrder,
                    'customer_name' => $customerName
                ],
                $updateData
            );
            
            return $billingStatus;
            
        } catch (\Exception $e) {
            Log::error('Error updating billing status', [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
 * ENHANCED: Calculate status based on ACTUAL document uploads
 */
public static function calculateDynamicStatus($deliveryOrder, $customerName)
{
    try {
        // Check if already sent (prioritas tertinggi)
        $billingStatus = self::where('delivery_order', $deliveryOrder)
                            ->where('customer_name', $customerName)
                            ->first();
        
        if ($billingStatus && $billingStatus->status === self::STATUS_SENT) {
            return 'sent';
        }
        
        // Get all uploaded documents
        $uploadedDocuments = DocumentUpload::where('delivery_order', $deliveryOrder)
                                          ->where('customer_name', $customerName)
                                          ->get();
        
        // Check jika tidak ada document sama sekali
        if ($uploadedDocuments->isEmpty()) {
            return 'outstanding';
        }
        
        // Get required documents dari DocumentSetting (EXIM documents)
        $documentSetting = self::findDocumentSettingByCustomerName($customerName);
        $eximRequired = $documentSetting ? $documentSetting->allowed_documents : [];
        
        // Finance required documents
        $financeRequired = self::FINANCE_REQUIRED_DOCUMENTS;
        
        // Check uploaded EXIM documents
        $eximUploaded = $uploadedDocuments->whereIn('document_type', $eximRequired)
                                         ->pluck('document_type')
                                         ->unique()
                                         ->toArray();
        
        // Check uploaded Finance documents
        $financeUploaded = $uploadedDocuments->whereIn('document_type', $financeRequired)
                                            ->pluck('document_type')
                                            ->unique()
                                            ->toArray();
        
        $eximCount = count($eximUploaded);
        $financeCount = count($financeUploaded);
        
        $eximTotal = count($eximRequired);
        $financeTotal = count($financeRequired);
        
        // LOGIC STATUS sesuai requirement Anda:
        
        // COMPLETED - Semua documents lengkap (EXIM + Finance)
        $eximComplete = ($eximTotal == 0) ? true : ($eximCount >= $eximTotal);
        $financeComplete = ($financeCount >= $financeTotal);
        
        if ($eximComplete && $financeComplete) {
            return 'completed';
        }
        
        // PROGRESS - Ada upload tapi belum lengkap
        if ($eximCount > 0 || $financeCount > 0) {
            return 'progress';
        }
        
        // OUTSTANDING - Belum ada document sama sekali
        return 'outstanding';
        
    } catch (\Exception $e) {
        Log::error("Error calculating dynamic status: " . $e->getMessage());
        return 'outstanding';
    }
}

    /**
 * ENHANCED: Dynamic status calculation untuk Finance Dashboard
 */
public static function calculateDynamicFinanceStatus($deliveryOrder, $customerName)
{
    try {
        // Check if already sent (prioritas tertinggi)
        $billingStatus = self::where('delivery_order', $deliveryOrder)
                            ->where('customer_name', $customerName)
                            ->first();
        
        if ($billingStatus && $billingStatus->status === self::STATUS_SENT) {
            return 'sent';
        }
        
        // Get all uploaded documents
        $uploadedDocuments = DocumentUpload::where('delivery_order', $deliveryOrder)
                                          ->where('customer_name', $customerName)
                                          ->get();
        
        // Check jika tidak ada document sama sekali
        if ($uploadedDocuments->isEmpty()) {
            return 'outstanding';
        }
        
        // Finance required documents
        $financeRequired = self::FINANCE_REQUIRED_DOCUMENTS;
        
        // Check uploaded finance documents
        $financeUploaded = $uploadedDocuments->whereIn('document_type', $financeRequired)
                                           ->pluck('document_type')
                                           ->unique()
                                           ->toArray();
        
        $financeCount = count($financeUploaded);
        $financeTotal = count($financeRequired);
        
        // LOGIC STATUS sesuai requirement Anda:
        
        // COMPLETED - Semua finance documents lengkap
        if ($financeCount >= $financeTotal) {
            return 'completed';
        }
        
        // PROGRESS - Ada upload tapi belum lengkap
        if ($financeCount > 0) {
            return 'progress';
        }
        
        // OUTSTANDING - Belum ada document finance sama sekali
        return 'outstanding';
        
    } catch (\Exception $e) {
        Log::error("Error calculating dynamic finance status: " . $e->getMessage());
        return 'outstanding';
    }
}

    /**
     * CRITICAL FIX: Resolve billing document dengan prioritas yang benar
     */
    private static function resolveBillingDocumentFixed($deliveryOrder, $customerName, $sapBillingData = null, $existingStatus = null)
    {
        Log::info('=== RESOLVING BILLING DOCUMENT - FIXED VERSION ===', [
            'delivery_order' => $deliveryOrder,
            'customer_name' => $customerName,
            'sap_data_provided' => $sapBillingData ? 'yes' : 'no',
            'existing_status' => $existingStatus ? 'yes' : 'no'
        ]);

        // PRIORITY 1: SAP Billing Data (paling penting)
        if ($sapBillingData && is_array($sapBillingData)) {
            $sapBillingDocument = $sapBillingData['Billing Document'] ?? $sapBillingData['billing_document'] ?? null;
            
            // CRITICAL: Pastikan billing document bukan delivery order
            if ($sapBillingDocument && 
                $sapBillingDocument !== '' && 
                $sapBillingDocument !== $deliveryOrder &&
                $sapBillingDocument !== '0' &&
                strlen(trim($sapBillingDocument)) > 0) {
                
                Log::info('✅ Valid billing document from SAP data', [
                    'billing_document' => $sapBillingDocument,
                    'delivery_order' => $deliveryOrder,
                    'source' => 'sap_data'
                ]);
                return $sapBillingDocument;
            } else {
                Log::info('❌ Invalid SAP billing document', [
                    'sap_billing_value' => $sapBillingDocument,
                    'delivery_order' => $deliveryOrder,
                    'reason' => 'empty_or_same_as_delivery'
                ]);
            }
        }

        // PRIORITY 2: Existing billing_document di database (jika valid)
        if ($existingStatus && 
            !empty($existingStatus->billing_document) && 
            $existingStatus->billing_document !== $deliveryOrder &&
            $existingStatus->billing_document !== '0') {
            
            Log::info('✅ Valid billing document from existing status', [
                'billing_document' => $existingStatus->billing_document,
                'source' => 'existing_status'
            ]);
            return $existingStatus->billing_document;
        }

        // PRIORITY 3: Extract dari filename dokumen INVOICE
        $billingFromFilename = self::extractBillingFromInvoiceFilename($deliveryOrder, $customerName);
        if ($billingFromFilename && 
            $billingFromFilename !== $deliveryOrder &&
            $billingFromFilename !== '0') {
            
            Log::info('✅ Valid billing document from INVOICE filename', [
                'billing_document' => $billingFromFilename,
                'source' => 'invoice_filename'
            ]);
            return $billingFromFilename;
        }

        // PRIORITY 4: Try API call ke billing service
        $billingFromApi = self::getBillingFromApi($deliveryOrder);
        if ($billingFromApi && 
            $billingFromApi !== $deliveryOrder &&
            $billingFromApi !== '0') {
            
            Log::info('✅ Valid billing document from API', [
                'billing_document' => $billingFromApi,
                'source' => 'api_call'
            ]);
            return $billingFromApi;
        }

        // CRITICAL: Return NULL if no valid billing document found
        Log::warning('❌ No valid billing document found anywhere', [
            'delivery_order' => $deliveryOrder,
            'customer_name' => $customerName,
            'returning' => 'NULL'
        ]);
        return null;
    }

public static function getDetailedProgress($deliveryOrder, $customerName)
{
    try {
        Log::info('📊 Getting detailed progress', [
            'delivery' => $deliveryOrder,
            'customer' => $customerName
        ]);
        
        // ✅ GET ALL UPLOADED DOCUMENTS (with multiple customer name attempts)
        $allUploads = DocumentUpload::where('delivery_order', $deliveryOrder)
                                   ->where(function($query) use ($customerName) {
                                       $query->where('customer_name', $customerName)
                                             ->orWhere('customer_name', self::normalizeCustomerName($customerName));
                                   })
                                   ->get();
        
        Log::info('📁 Documents found', [
            'total_count' => $allUploads->count(),
            'document_types' => $allUploads->pluck('document_type')->toArray(),
            'file_names' => $allUploads->pluck('file_name')->toArray()
        ]);
        
        // ✅ FINANCE REQUIRED DOCUMENTS (with case variations)
        $financeRequiredDocs = [
            'INVOICE',
            'PACKING_LIST', 
            'PAYMENT_INTRUCTION' // Note: Typo in DB, keep it for compatibility
        ];
        
        // ✅ GET CUSTOMER'S ALLOWED DOCUMENTS (for EXIM)
        $documentSetting = DocumentSetting::where(function($query) use ($customerName) {
            $query->where('customer_name', $customerName)
                  ->orWhere('customer_name', self::normalizeCustomerName($customerName));
        })->first();
        
        $allowedDocuments = $documentSetting ? $documentSetting->allowed_documents : [];
        
        // ✅ SEPARATE FINANCE vs EXIM DOCUMENTS (CASE INSENSITIVE)
        $financeUploaded = [];
        $eximUploaded = [];
        
        foreach ($allUploads as $upload) {
            $docType = strtoupper(trim($upload->document_type)); // ✅ Normalize to uppercase
            
            Log::info('Checking document', [
                'original_type' => $upload->document_type,
                'normalized_type' => $docType,
                'file_name' => $upload->file_name
            ]);
            
            // ✅ CHECK: Is this Finance document? (case insensitive)
            $isFinanceDoc = false;
            foreach ($financeRequiredDocs as $requiredDoc) {
                if (strtoupper($requiredDoc) === $docType) {
                    $isFinanceDoc = true;
                    if (!in_array($requiredDoc, $financeUploaded)) {
                        $financeUploaded[] = $requiredDoc;
                        Log::info('✅ Finance document found', [
                            'type' => $requiredDoc,
                            'file' => $upload->file_name
                        ]);
                    }
                    break;
                }
            }
            
            // ✅ CHECK: Is this EXIM document? (case insensitive)
            if (!$isFinanceDoc) {
                foreach ($allowedDocuments as $allowedDoc) {
                    if (strtoupper($allowedDoc) === $docType) {
                        if (!in_array($allowedDoc, $eximUploaded)) {
                            $eximUploaded[] = $allowedDoc;
                            Log::info('✅ EXIM document found', [
                                'type' => $allowedDoc,
                                'file' => $upload->file_name
                            ]);
                        }
                        break;
                    }
                }
            }
        }
        
        // ✅ CALCULATE COUNTS
        $financeUploadedCount = count($financeUploaded);
        $financeTotalCount = count($financeRequiredDocs);
        $eximUploadedCount = count($eximUploaded);
        $eximTotalCount = count($allowedDocuments);
        
        Log::info('📊 Progress counts', [
            'finance_uploaded' => $financeUploadedCount,
            'finance_total' => $financeTotalCount,
            'finance_docs' => $financeUploaded,
            'exim_uploaded' => $eximUploadedCount,
            'exim_total' => $eximTotalCount,
            'exim_docs' => $eximUploaded
        ]);
        
        // ✅ CALCULATE PROGRESS PERCENTAGES
        $financeProgress = $financeTotalCount > 0 
            ? ($financeUploadedCount / $financeTotalCount) * 100 
            : 0;
        
        $eximProgress = $eximTotalCount > 0 
            ? ($eximUploadedCount / $eximTotalCount) * 100 
            : 100; // ✅ If no EXIM required, count as 100%
        
        // ✅ OVERALL = Average of Finance + EXIM
        $overallProgress = ($financeProgress + $eximProgress) / 2;
        
        // ✅ CHECK COMPLETION STATUS
        $financeComplete = ($financeUploadedCount >= $financeTotalCount);
        $eximComplete = ($eximTotalCount === 0) || ($eximUploadedCount >= $eximTotalCount);
        
        // ✅ MISSING DOCUMENTS
        $missingFinanceDocs = array_diff($financeRequiredDocs, $financeUploaded);
        $missingEximDocs = array_diff($allowedDocuments, $eximUploaded);
        $allMissingDocs = array_merge($missingFinanceDocs, $missingEximDocs);
        
        // ✅ GET BILLING STATUS
        $billingStatus = self::where('delivery_order', $deliveryOrder)
                            ->where('customer_name', $customerName)
                            ->first();
        
        $status = 'outstanding';
        $canBeSent = false;
        $isSent = false;
        $sentDetails = null;
        
        if ($billingStatus) {
            $status = $billingStatus->status;
            $isSent = ($status === 'sent');
            
            if ($isSent) {
                $sentDetails = [
                    'sent_at' => $billingStatus->email_sent_at,
                    'sent_by' => $billingStatus->sent_by,
                    'notes' => $billingStatus->notes
                ];
            }
        }
        
        // ✅ CAN BE SENT = All documents uploaded AND not sent yet
        $canBeSent = $financeComplete && $eximComplete && !$isSent;
        
        // ✅ AUTO UPDATE STATUS based on progress
        if ($overallProgress >= 100 && !$isSent) {
            $status = 'completed';
        } elseif ($overallProgress > 0 && $overallProgress < 100) {
            $status = 'progress';
        } elseif ($isSent) {
            $status = 'sent';
        } else {
            $status = 'outstanding';
        }
        
        $result = [
            'exim_required_documents' => $allowedDocuments,
            'finance_required_documents' => $financeRequiredDocs,
            'exim_uploaded_documents' => $eximUploaded,
            'finance_uploaded_documents' => $financeUploaded,
            'exim_progress_percentage' => round($eximProgress, 1),
            'finance_progress_percentage' => round($financeProgress, 1),
            'overall_progress_percentage' => round($overallProgress, 1),
            'exim_complete' => $eximComplete,
            'finance_complete' => $financeComplete,
            'finance_uploaded' => $financeUploadedCount,
            'finance_total' => $financeTotalCount,
            'exim_uploaded' => $eximUploadedCount,
            'exim_total' => $eximTotalCount,
            'missing_documents' => array_values($allMissingDocs),
            'status' => $status,
            'can_be_sent' => $canBeSent,
            'is_sent' => $isSent,
            'billing_document' => $billingStatus->billing_document ?? null,
            'has_valid_billing_document' => !empty($billingStatus->billing_document ?? null),
            'sent_details' => $sentDetails,
            'next_action' => self::getNextAction($overallProgress, $isSent, $allMissingDocs)
        ];
        
        Log::info('✅ Progress calculated', [
            'delivery' => $deliveryOrder,
            'overall' => round($overallProgress, 1) . '%',
            'finance' => "{$financeUploadedCount}/{$financeTotalCount}",
            'exim' => "{$eximUploadedCount}/{$eximTotalCount}",
            'status' => $status,
            'can_be_sent' => $canBeSent
        ]);
        
        return $result;
        
    } catch (\Exception $e) {
        Log::error('❌ Error in getDetailedProgress', [
            'delivery' => $deliveryOrder,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Return safe default
        return [
            'exim_required_documents' => [],
            'finance_required_documents' => ['INVOICE', 'PACKING_LIST', 'PAYMENT_INTRUCTION'],
            'exim_uploaded_documents' => [],
            'finance_uploaded_documents' => [],
            'exim_progress_percentage' => 0,
            'finance_progress_percentage' => 0,
            'overall_progress_percentage' => 0,
            'exim_complete' => false,
            'finance_complete' => false,
            'finance_uploaded' => 0,
            'finance_total' => 3,
            'exim_uploaded' => 0,
            'exim_total' => 0,
            'missing_documents' => ['INVOICE', 'PACKING_LIST', 'PAYMENT_INTRUCTION'],
            'status' => 'outstanding',
            'can_be_sent' => false,
            'is_sent' => false,
            'billing_document' => null,
            'has_valid_billing_document' => false,
            'sent_details' => null,
            'next_action' => 'Error loading progress',
            'error' => $e->getMessage()
        ];
    }
}

/**
 * ✅ Helper: Get next action text
 */
private static function getNextAction($overallProgress, $isSent, $missingDocs)
{
    if ($isSent) {
        return 'Dokumen sudah dikirim ke buyer';
    }
    
    if ($overallProgress >= 100) {
        return 'Semua dokumen lengkap - Siap dikirim ke buyer';
    }
    
    if ($overallProgress > 0) {
        $missingCount = count($missingDocs);
        return "Upload {$missingCount} dokumen yang masih missing";
    }
    
    return 'Upload dokumen untuk memulai proses';
}

/**
 * ✅ Helper: Normalize customer name
 */
private static function normalizeCustomerName($name)
{
    $normalized = str_replace([',', '.', '  '], [' ', ' ', ' '], $name);
    $suffixes = [
        'INC.' => 'INC',
        'Inc.' => 'INC', 
        'LLC.' => 'LLC',
        'Corp.' => 'CORP',
        'Ltd.' => 'LTD',
        'CO.' => 'CO'
    ];
    
    foreach ($suffixes as $from => $to) {
        $normalized = str_ireplace($from, $to, $normalized);
    }
    
    return preg_replace('/\s+/', ' ', trim($normalized));
}

    /**
     * FIXED: Get billing document untuk progress
     */
    private static function getBillingDocumentForProgressFixed($deliveryOrder, $customerName, $existingStatus = null, $uploadedDocuments = null)
    {
        // Check existing status first
        if ($existingStatus && 
            !empty($existingStatus->billing_document) && 
            $existingStatus->billing_document !== $deliveryOrder &&
            $existingStatus->billing_document !== '0') {
            return $existingStatus->billing_document;
        }

        // Extract from uploaded documents
        if ($uploadedDocuments) {
            $invoiceDoc = $uploadedDocuments->where('document_type', 'INVOICE')->first();
            if ($invoiceDoc && $invoiceDoc->file_name) {
                $patterns = [
                    '/(3\d{9,})/',           // Pattern: 3000012345
                    '/(\d{10,})/',           // Pattern: 10+ digit number
                    '/INV[_-]?(\d{8,})/',    // Pattern: INV_12345678
                    '/BILL[_-]?(\d{8,})/',   // Pattern: BILL_12345678
                ];
                
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $invoiceDoc->file_name, $matches)) {
                        $extracted = $matches[1];
                        if ($extracted !== $deliveryOrder) {
                            return $extracted;
                        }
                    }
                }
            }
        }

        // Try API as last resort
        $apiResult = self::getBillingFromApi($deliveryOrder);
        if ($apiResult && $apiResult !== $deliveryOrder) {
            return $apiResult;
        }

        // Return NULL if no valid billing document found
        return null;
    }

    /**
     * Extract billing document dari filename INVOICE
     */
    private static function extractBillingFromInvoiceFilename($deliveryOrder, $customerName)
    {
        try {
            $invoiceDoc = DocumentUpload::where('delivery_order', $deliveryOrder)
                                      ->where('customer_name', $customerName)
                                      ->where('document_type', 'INVOICE')
                                      ->first();

            if (!$invoiceDoc || !$invoiceDoc->file_name) {
                return null;
            }

            $filename = $invoiceDoc->file_name;
            
            $patterns = [
                '/(3\d{9,})/',           // Standard billing: 3000012345
                '/(\d{10,})/',           // Any 10+ digit number
                '/INV[_-]?(\d{8,})/',    // INV_12345678 format
                '/BILL[_-]?(\d{8,})/',   // BILL_12345678 format
                '/(\d{8,})/',            // Any 8+ digit number
            ];

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $filename, $matches)) {
                    $extracted = $matches[1];
                    if ($extracted !== $deliveryOrder && $extracted !== '0') {
                        Log::info('Extracted billing number from filename', [
                            'pattern' => $pattern,
                            'extracted' => $extracted,
                            'filename' => $filename
                        ]);
                        return $extracted;
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error extracting billing from filename: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get billing document dari API
     */
    private static function getBillingFromApi($deliveryOrder)
    {
        try {
            $billingApiUrl = 'http://127.0.0.1:50';
            $response = Http::timeout(10)->get($billingApiUrl . '/api/billing_document/' . $deliveryOrder);
            
            if ($response->successful()) {
                $data = $response->json();
                $billingDoc = $data['billing_document'] ?? null;
                
                if ($billingDoc && $billingDoc !== $deliveryOrder && $billingDoc !== '0') {
                    Log::info('Billing document retrieved from API', [
                        'delivery_order' => $deliveryOrder,
                        'billing_document' => $billingDoc
                    ]);
                    return $billingDoc;
                }
            }
        } catch (\Exception $e) {
            Log::warning('API call for billing document failed: ' . $e->getMessage());
        }
        
        return null;
    }

    /**
     * STATIC version of calculateStatus method to fix the error
     */
    public static function calculateStatusStatic($deliveryOrder, $customerName, $forceRecalculate = false)
    {
        try {
            $existingStatus = self::where('delivery_order', $deliveryOrder)
                                ->where('customer_name', $customerName)
                                ->first();
            
            if ($existingStatus && $existingStatus->status === self::STATUS_SENT && !$forceRecalculate) {
                return self::STATUS_SENT;
            }
            
            $documentSetting = self::findDocumentSettingByCustomerName($customerName);
            $eximRequiredDocuments = $documentSetting ? $documentSetting->allowed_documents : [];
            $financeRequiredDocuments = self::FINANCE_REQUIRED_DOCUMENTS;
            
            $uploadedDocuments = DocumentUpload::where('delivery_order', $deliveryOrder)
                                             ->where('customer_name', $customerName)
                                             ->get();
            
            $uploadedDocTypes = $uploadedDocuments->pluck('document_type')->unique()->toArray();
            $totalUploadedCount = count($uploadedDocTypes);
            
            if ($totalUploadedCount == 0) {
                return self::STATUS_OUTSTANDING;
            }
            
            $eximUploaded = array_intersect($uploadedDocTypes, $eximRequiredDocuments);
            $financeUploaded = array_intersect($uploadedDocTypes, $financeRequiredDocuments);
            
            $eximTotal = count($eximRequiredDocuments);
            $financeTotal = count($financeRequiredDocuments);
            $eximUploadedCount = count($eximUploaded);
            $financeUploadedCount = count($financeUploaded);
            
            $eximComplete = ($eximTotal == 0) ? true : ($eximUploadedCount >= $eximTotal);
            $financeComplete = ($financeUploadedCount >= $financeTotal);
            
            if ($eximComplete && $financeComplete) {
                return self::STATUS_COMPLETED;
            }
            
            return self::STATUS_PROGRESS;
            
        } catch (\Exception $e) {
            Log::error('Error calculating status: ' . $e->getMessage());
            return self::STATUS_OUTSTANDING;
        }
    }

    // ... (method lainnya tetap sama)

    public function calculateStatus($deliveryOrder, $customerName, $forceRecalculate = false)
    {
        try {
            $existingStatus = self::where('delivery_order', $deliveryOrder)
                                ->where('customer_name', $customerName)
                                ->first();
            
            if ($existingStatus && $existingStatus->status === self::STATUS_SENT && !$forceRecalculate) {
                return self::STATUS_SENT;
            }
            
            $documentSetting = self::findDocumentSettingByCustomerName($customerName);
            $eximRequiredDocuments = $documentSetting ? $documentSetting->allowed_documents : [];
            $financeRequiredDocuments = self::FINANCE_REQUIRED_DOCUMENTS;
            
            $uploadedDocuments = DocumentUpload::where('delivery_order', $deliveryOrder)
                                             ->where('customer_name', $customerName)
                                             ->get();
            
            $uploadedDocTypes = $uploadedDocuments->pluck('document_type')->unique()->toArray();
            $totalUploadedCount = count($uploadedDocTypes);
            
            if ($totalUploadedCount == 0) {
                return self::STATUS_OUTSTANDING;
            }
            
            $eximUploaded = array_intersect($uploadedDocTypes, $eximRequiredDocuments);
            $financeUploaded = array_intersect($uploadedDocTypes, $financeRequiredDocuments);
            
            $eximTotal = count($eximRequiredDocuments);
            $financeTotal = count($financeRequiredDocuments);
            $eximUploadedCount = count($eximUploaded);
            $financeUploadedCount = count($financeUploaded);
            
            $eximComplete = ($eximTotal == 0) ? true : ($eximUploadedCount >= $eximTotal);
            $financeComplete = ($financeUploadedCount >= $financeTotal);
            
            if ($eximComplete && $financeComplete) {
                return self::STATUS_COMPLETED;
            }
            
            return self::STATUS_PROGRESS;
            
        } catch (\Exception $e) {
            Log::error('Error calculating status: ' . $e->getMessage());
            return self::STATUS_OUTSTANDING;
        }
    }

    private static function getNextActionMessage($status, $eximProgress, $financeProgress)
    {
        switch ($status) {
            case self::STATUS_OUTSTANDING:
                return 'Upload dokumen untuk memulai proses';
            case self::STATUS_PROGRESS:
                $messages = [];
                if ($eximProgress < 100) {
                    $messages[] = 'Exim: Upload dokumen yang diperlukan';
                }
                if ($financeProgress < 100) {
                    $messages[] = 'Finance: Upload Invoice, Packing List, Payment Intruction';
                }
                return implode(' | ', $messages);
            case self::STATUS_COMPLETED:
                return 'Siap untuk dikirim ke buyer';
            case self::STATUS_SENT:
                return 'Sudah dikirim ke buyer';
            default:
                return 'Status tidak diketahui';
        }
    }

    public function markAsSent($sentBy, $buyerEmail = null, $notes = null, $billingDocument = null)
    {
        if (!$this->canBeMarkedAsSent()) {
            throw new \Exception('Cannot mark as sent. Status must be completed first.');
        }
        
        if ($this->status === self::STATUS_SENT) {
            throw new \Exception('This delivery has already been sent to buyer.');
        }
        
        $updateData = [
            'status' => self::STATUS_SENT,
            'email_sent_at' => now(),
            'sent_at' => now(),
            'sent_by' => $sentBy,
            'sent_to_buyer' => true
        ];
        
        if ($buyerEmail) {
            $updateData['sent_to_email'] = $buyerEmail;
        }
        
        if ($notes) {
            $updateData['email_notes'] = $notes;
        }

        if ($billingDocument && $billingDocument !== $this->delivery_order) {
            $updateData['billing_document'] = $billingDocument;
        }
        
        $this->update($updateData);
        
        return $this;
    }

    public function canBeMarkedAsSent()
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public static function isAlreadySent($deliveryOrder, $customerName)
    {
        $billingStatus = self::where('delivery_order', $deliveryOrder)
                           ->where('customer_name', $customerName)
                           ->first();
        
        return $billingStatus && $billingStatus->status === self::STATUS_SENT;
    }

    public static function getStatusCounts()
    {
        return [
            'outstanding' => self::where('status', self::STATUS_OUTSTANDING)->count(),
            'progress' => self::where('status', self::STATUS_PROGRESS)->count(),
            'completed' => self::where('status', self::STATUS_COMPLETED)->count(),
            'sent' => self::where('status', self::STATUS_SENT)->count(),
            'total' => self::count()
        ];
    }

    public function getStatusDisplayAttribute()
    {
        $statuses = [
            self::STATUS_OUTSTANDING => 'Outstanding',
            self::STATUS_PROGRESS => 'On Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_SENT => 'Sent'
        ];
        return $statuses[$this->status] ?? 'Unknown';
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    protected static function findDocumentSettingByCustomerName($customerName)
    {
        $setting = DocumentSetting::where('customer_name', $customerName)->first();
        if ($setting) {
            return $setting;
        }
        
        $setting = DocumentSetting::whereRaw('UPPER(customer_name) = ?', [strtoupper($customerName)])->first();
        if ($setting) {
            return $setting;
        }
        
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $customerName);
        $settings = DocumentSetting::all();
        foreach ($settings as $setting) {
            $settingCleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $setting->customer_name);
            if (strtoupper($cleanName) === strtoupper($settingCleanName)) {
                return $setting;
            }
        }
        
        return null;
    }

    /* ===============================================
     * TAMBAHAN BARU - HANYA UNTUK BILLING DOCUMENT
     * =============================================== */

    /**
     * NEW METHOD: Get billing document dari SAP API untuk email
     */
    public static function getBillingDocumentFromSapForEmail($deliveryOrder, $customerName)
    {
        try {
            Log::info("=== GETTING BILLING DOCUMENT FROM SAP FOR EMAIL ===", [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName
            ]);

            $billingApiUrl = 'http://127.0.0.1:50';
            $response = Http::timeout(15)->get($billingApiUrl . '/api/billing_data_fast');
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    foreach ($responseData['data'] as $record) {
                        $recordDelivery = $record['Delivery'] ?? '';
                        $recordCustomer = $record['Customer Name'] ?? '';
                        
                        if (trim($recordDelivery) === trim($deliveryOrder) && 
                            trim(strtoupper($recordCustomer)) === trim(strtoupper($customerName))) {
                            
                            $billingDocument = $record['Billing Document'] ?? '';
                            
                            if ($billingDocument && 
                                trim($billingDocument) !== '' && 
                                $billingDocument !== $deliveryOrder &&
                                $billingDocument !== '0') {
                                
                                Log::info("✅ BILLING DOCUMENT FOUND FROM SAP FOR EMAIL", [
                                    'delivery_order' => $deliveryOrder,
                                    'billing_document' => $billingDocument,
                                    'customer_name' => $customerName
                                ]);
                                
                                return $billingDocument;
                            }
                        }
                    }
                }
            }
            
            Log::warning("❌ No billing document found in SAP for email: {$deliveryOrder}");
            return null;
            
        } catch (\Exception $e) {
            Log::error("Error getting billing document from SAP for email: " . $e->getMessage());
            return null;
        }
    }

    /**
     * NEW METHOD: Get final billing document untuk template email
     */
    public static function getFinalBillingForEmailTemplate($deliveryOrder, $customerName)
    {
        // STEP 1: Coba SAP API dulu
        $sapBilling = self::getBillingDocumentFromSapForEmail($deliveryOrder, $customerName);
        if ($sapBilling) {
            return $sapBilling;
        }

        // STEP 2: Coba dari database
        $billingStatus = self::where('delivery_order', $deliveryOrder)
                           ->where('customer_name', $customerName)
                           ->first();

        if ($billingStatus && 
            !empty($billingStatus->billing_document) && 
            $billingStatus->billing_document !== $deliveryOrder &&
            $billingStatus->billing_document !== '0') {
            return $billingStatus->billing_document;
        }

        // STEP 3: Coba extract dari filename
        $extractedBilling = self::extractBillingFromInvoiceFilename($deliveryOrder, $customerName);
        if ($extractedBilling && $extractedBilling !== $deliveryOrder) {
            return $extractedBilling;
        }

        // Return NULL jika tidak ada
        return null;
    }

    /**
     * NEW METHOD: Get email data dengan billing document yang benar
     */
    public static function getEmailDataWithCorrectBilling($deliveryOrder, $customerName, $emailData, $emailMessage = null, $notes = null)
    {
        try {
            // Get billing document yang benar
            $billingDocument = self::getFinalBillingForEmailTemplate($deliveryOrder, $customerName);
            
            // Tentukan reference number yang akan digunakan di template
            $documentReference = ($billingDocument && $billingDocument !== $deliveryOrder) ? $billingDocument : $deliveryOrder;
            
            Log::info("=== EMAIL DATA WITH CORRECT BILLING ===", [
                'delivery_order' => $deliveryOrder,
                'billing_document' => $billingDocument,
                'document_reference' => $documentReference,
                'will_use_billing' => $billingDocument ? 'YES' : 'NO'
            ]);

            // Generate message dengan document reference yang benar
            $greeting = $emailData->contact_name ? "Dear {$emailData->contact_name}," : "Dear Buyer,";
            $personalizedMessage = $emailMessage ?: "{$greeting}\n\nPlease find the attached documents for {$documentReference}.\n\nPlease review all documents carefully.\n\nBest Regards,\nKMI Finance - Account Receivable";

            return [
                // Data asli
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'buyer_email' => $emailData->email,
                'buyer_contact_name' => $emailData->contact_name,
                'buyer_email_type' => $emailData->email_type,
                'email_message' => $personalizedMessage,
                'sender_name' => Auth::user()->name ?? 'KMI Finance Team',
                'sent_at' => now()->toDateTimeString(),
                'notes' => $notes,
                
                // CRITICAL: Template variables menggunakan billing document
                'billing_document' => $billingDocument,
                'document_reference' => $documentReference,
                'billing_number' => $documentReference,
                'subject' => "Doc {$documentReference}",
                'official_billing_number' => $documentReference,
                
                // System info
                'system_name' => 'KMI Finance - Automated Billing System',
                'contact_email' => 'ar.kmi@pawindo.com',
                'department_name' => 'KMI Finance - Account Receivable'
            ];
            
        } catch (\Exception $e) {
            Log::error("Error getting email data with correct billing: " . $e->getMessage());
            
            // Fallback menggunakan delivery order
            return [
                'delivery_order' => $deliveryOrder,
                'customer_name' => $customerName,
                'buyer_email' => $emailData->email,
                'buyer_contact_name' => $emailData->contact_name,
                'document_reference' => $deliveryOrder,
                'billing_number' => $deliveryOrder,
                'subject' => "Doc {$deliveryOrder}",
                'email_message' => $emailMessage ?: "Please find the attached documents for {$deliveryOrder}.",
                'system_name' => 'KMI Finance - Automated Billing System',
                'contact_email' => 'ar.kmi@pawindo.com',
                'department_name' => 'KMI Finance - Account Receivable',
                'sender_name' => Auth::user()->name ?? 'KMI Finance Team',
                'sent_at' => now()->toDateTimeString(),
                'notes' => $notes,
                'error' => 'Fallback to delivery order'
            ];
        }
    }

    /**
 * ✅ NEW: Get detailed progress khusus untuk EXIM dashboard
 */
public static function getDetailedProgressForExim($deliveryOrder, $customerName)
{
    try {
        Log::info('=== GET DETAILED PROGRESS FOR EXIM ===', [
            'delivery' => $deliveryOrder,
            'customer' => $customerName
        ]);
        
        $documentSetting = self::findDocumentSettingByCustomerName($customerName);
        $eximRequiredDocuments = $documentSetting ? $documentSetting->allowed_documents : [];
        
        // ✅ CRITICAL: Hanya ambil dokumen yang uploaded_from = 'exim'
        $uploadedDocuments = DocumentUpload::where('delivery_order', $deliveryOrder)
                                         ->where('customer_name', $customerName)
                                         ->where('uploaded_from', 'exim')  // ✅ FILTER
                                         ->get();
        
        $uploadedDocTypes = $uploadedDocuments->pluck('document_type')->unique()->toArray();
        
        // EXIM calculation (Finance selalu 0 karena difilter)
        $eximUploaded = array_intersect($uploadedDocTypes, $eximRequiredDocuments);
        $eximTotal = count($eximRequiredDocuments);
        $eximUploadedCount = count($eximUploaded);
        $eximProgress = $eximTotal > 0 ? round(($eximUploadedCount / $eximTotal) * 100) : 100;
        
        // Overall progress (hanya EXIM)
        $overallProgress = $eximProgress;
        
        // Missing documents
        $missingExim = array_diff($eximRequiredDocuments, $eximUploaded);
        
        Log::info('✅ EXIM Progress calculated', [
            'exim_uploaded' => $eximUploadedCount,
            'exim_total' => $eximTotal,
            'progress' => $overallProgress . '%',
            'total_documents_checked' => $uploadedDocuments->count()
        ]);
        
        return [
            'exim_required_documents' => $eximRequiredDocuments,
            'exim_uploaded_documents' => $eximUploaded,
            'exim_progress_percentage' => $eximProgress,
            'overall_progress_percentage' => $overallProgress,
            'exim_complete' => $eximUploadedCount >= $eximTotal,
            'exim_uploaded' => $eximUploadedCount,
            'exim_total' => $eximTotal,
            'missing_documents' => array_values($missingExim),
            'filtered_for' => 'exim_only',
            'finance_excluded' => true
        ];
        
    } catch (\Exception $e) {
        Log::error('❌ Error getting EXIM progress', [
            'delivery' => $deliveryOrder,
            'error' => $e->getMessage()
        ]);
        
        return [
            'exim_progress_percentage' => 0,
            'overall_progress_percentage' => 0,
            'exim_complete' => false,
            'error' => $e->getMessage()
        ];
    }
}

}