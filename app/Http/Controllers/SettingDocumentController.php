<?php

namespace App\Http\Controllers;

use App\Models\DocumentSetting;
use App\Models\SettingLogin;
use App\Models\BillingStatus;
use App\Models\BuyerEmail; // ✅ NEW: Import model BuyerEmail
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule; // ✅ NEW: For validation

class SettingDocumentController extends Controller
{
    private $billingApiUrl = 'http://127.0.0.1:50';

    // ========================================
    // EXISTING METHODS - TIDAK DIUBAH
    // ========================================
    
    /**
     * ENHANCED: Static buyer list dengan customer dari berbagai sumber
     */

/**
 * Get list of buyers from buyer_emails table untuk dropdown
 */
public function getBuyersList()
{
    try {
        // Get all buyers from buyer_emails
        $buyers = BuyerEmail::select('buyer_code', 'buyer_name')
            ->distinct()
            ->orderBy('buyer_name')
            ->get();
        
        // Get buyers yang sudah ada di document_settings
        $existingBuyers = DocumentSetting::pluck('customer_name')->toArray();
        
        // Filter out buyers yang sudah ada
        $availableBuyers = $buyers->filter(function($buyer) use ($existingBuyers) {
            return !in_array(strtoupper($buyer->buyer_name), array_map('strtoupper', $existingBuyers));
        })->values();
        
        return response()->json([
            'success' => true,
            'buyers' => $availableBuyers,
            'total' => $availableBuyers->count()
        ]);
        
    } catch (\Exception $e) {
        Log::error('Get buyers list error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to get buyers list: ' . $e->getMessage()
        ], 500);
    }
}

public function addBuyer(Request $request)
{
    try {
        // Validation
        $validated = $request->validate([
            'buyer_name' => 'required|string|max:255',
        ]);
        
        $buyerName = strtoupper(trim($validated['buyer_name']));
        
        // Check if buyer already exists
        $exists = DocumentSetting::where('customer_name', $buyerName)->exists();
        
        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => "Buyer '{$buyerName}' already exists in document settings"
            ], 409);
        }
        
        // Create new buyer with default documents (empty)
        DocumentSetting::create([
            'customer_name' => $buyerName,
            'allowed_documents' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Log::info('New buyer added to document settings', [
            'buyer_name' => $buyerName,
            'user' => session('setting_user')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Buyer '{$buyerName}' successfully added to document settings",
            'buyer_name' => $buyerName
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Add buyer error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to add buyer: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Delete buyer from document settings
 */
public function deleteBuyer(Request $request)
{
    try {
        // Validation
        $validated = $request->validate([
            'buyer_name' => 'required|string',
        ]);
        
        $buyerName = $validated['buyer_name'];
        
        // Check if buyer exists
        $setting = DocumentSetting::where('customer_name', $buyerName)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => "Buyer '{$buyerName}' not found in document settings"
            ], 404);
        }
        
        // Get document count before delete
        $allowedDocsJson = $setting->allowed_documents;
        
        // Handle both string and array cases
        if (is_string($allowedDocsJson)) {
            $allowedDocs = json_decode($allowedDocsJson, true) ?? [];
        } else if (is_array($allowedDocsJson)) {
            $allowedDocs = $allowedDocsJson;
        } else {
            $allowedDocs = [];
        }
        
        $docCount = count($allowedDocs);
        
        // Delete the buyer
        $setting->delete();
        
        Log::info('Buyer deleted from document settings', [
            'buyer_name' => $buyerName,
            'documents_removed' => $docCount,
            'user' => session('setting_user')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Buyer '{$buyerName}' and {$docCount} document setting(s) successfully deleted",
            'deleted_documents' => $docCount
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Delete buyer error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete buyer: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Add new document type to buyer's allowed documents
 */
public function addDocument(Request $request)
{
    try {
        // Validation
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'document_type' => 'required|string|max:100',
        ]);
        
        $customerName = $validated['customer_name'];
        $documentType = strtoupper(trim($validated['document_type']));
        
        // Get buyer setting
        $setting = DocumentSetting::where('customer_name', $customerName)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => "Buyer '{$customerName}' not found"
            ], 404);
        }
        
        // Get current allowed documents - handle both string and array
        $allowedDocsJson = $setting->allowed_documents;
        
        if (is_string($allowedDocsJson)) {
            $allowedDocs = json_decode($allowedDocsJson, true) ?? [];
        } else if (is_array($allowedDocsJson)) {
            $allowedDocs = $allowedDocsJson;
        } else {
            $allowedDocs = [];
        }
        
        // Check if document already exists
        if (in_array($documentType, $allowedDocs)) {
            return response()->json([
                'success' => false,
                'message' => "Document type '{$documentType}' already exists for this buyer"
            ], 409);
        }
        
        // Add new document
        $allowedDocs[] = $documentType;
        
        // Update setting - always save as JSON string
        $setting->allowed_documents = json_encode($allowedDocs);
        $setting->updated_at = now();
        $setting->save();
        
        Log::info('Document type added', [
            'customer_name' => $customerName,
            'document_type' => $documentType,
            'user' => session('setting_user')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Document type '{$documentType}' successfully added",
            'document_type' => $documentType,
            'total_documents' => count($allowedDocs)
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Add document error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to add document: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Delete document type from buyer's allowed documents
 */
public function deleteDocument(Request $request)
{
    try {
        // Validation
        $validated = $request->validate([
            'customer_name' => 'required|string',
            'document_type' => 'required|string',
        ]);
        
        $customerName = $validated['customer_name'];
        $documentType = $validated['document_type'];
        
        // Get buyer setting
        $setting = DocumentSetting::where('customer_name', $customerName)->first();
        
        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => "Buyer '{$customerName}' not found"
            ], 404);
        }
        
        // Get current allowed documents - handle both string and array
        $allowedDocsJson = $setting->allowed_documents;
        
        if (is_string($allowedDocsJson)) {
            $allowedDocs = json_decode($allowedDocsJson, true) ?? [];
        } else if (is_array($allowedDocsJson)) {
            $allowedDocs = $allowedDocsJson;
        } else {
            $allowedDocs = [];
        }
        
        // Check if document exists
        $index = array_search($documentType, $allowedDocs);
        if ($index === false) {
            return response()->json([
                'success' => false,
                'message' => "Document type '{$documentType}' not found for this buyer"
            ], 404);
        }
        
        // Remove document
        array_splice($allowedDocs, $index, 1);
        
        // Update setting - always save as JSON string
        $setting->allowed_documents = json_encode($allowedDocs);
        $setting->updated_at = now();
        $setting->save();
        
        Log::info('Document type deleted', [
            'customer_name' => $customerName,
            'document_type' => $documentType,
            'user' => session('setting_user')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Document type '{$documentType}' successfully deleted",
            'total_documents' => count($allowedDocs)
        ]);
        
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
        
    } catch (\Exception $e) {
        Log::error('Delete document error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete document: ' . $e->getMessage()
        ], 500);
    }
}
    

public function deleteBuyerWithEmails($buyerCode)
{
    try {
        // Decode URL-encoded buyer code
        $buyerCode = urldecode($buyerCode);
        
        if (!$buyerCode) {
            return response()->json([
                'success' => false,
                'message' => 'Buyer code is required'
            ], 400);
        }
        
        // Get count of emails to be deleted
        $emailCount = BuyerEmail::where('buyer_code', $buyerCode)->count();
        
        if ($emailCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Buyer not found or has no emails'
            ], 404);
        }
        
        // Delete all emails for this buyer
        BuyerEmail::where('buyer_code', $buyerCode)->delete();
        
        Log::info('Buyer with all emails deleted', [
            'buyer_code' => $buyerCode,
            'emails_deleted' => $emailCount,
            'user' => session('setting_user')
        ]);
        
        return response()->json([
            'success' => true,
            'message' => "Buyer '{$buyerCode}' dan {$emailCount} email(s) berhasil dihapus",
            'deleted_count' => $emailCount
        ]);
        
    } catch (\Exception $e) {
        Log::error('Delete buyer with emails error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to delete buyer: ' . $e->getMessage()
        ], 500);
    }
}

    
    private function getStaticBuyerList()
    {
        return collect([
            'ETHAN ALLEN OPERATIONS, INC.',
            'CENTURY FURNITURE',
            'BRUNSWICK BILLIARDS-LIFE FITNE',
            'VANGUARD FURNITURE',
            'HICKORY CHAIR, LLC',
            'THE UTTERMOST Co.',
            'LAKESHORE LEARNING MATERIALS,LLC',
            'ROWE FINE FURNITURE INC',
            'THAYER COGGIN, INC',
            'PT SKYLINE JAYA',
            'GABBY',
            'SAMPLE CUSTOMER',
            'TROPICAL OUTDOOR',
            'CRATE&BARREL',
            'EUROMARKET DESIGNS, INC',
            'ARHAUS',
            'BLUE PRINT',
            'MASSOUD',
            'LEE INDUSTRIES',
            'MARKOR INTERNATIONAL FURN',
            'MAKERSPALM, LLC',
            'LULU & GEORGIA',
            'TANJUNG BENOA INDONESIA',
            'SMB KENZAI Co., Ltd.',
            'SA POREAUX ET CIE_e',
        ])->sort()->values();
    }

    public function showLoginForm()
    {
        return view('setting-document.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $settingUser = SettingLogin::where('username', $request->username)
                                 ->where('is_active', true)
                                 ->first();

        if ($settingUser && $settingUser->checkPassword($request->password)) {
            session([
                'setting_authenticated' => true,
                'setting_user' => $settingUser->name,
                'setting_username' => $settingUser->username,
                'setting_role' => $settingUser->role ?? 'admin'
            ]);

            Log::info('Setting document login successful', [
                'username' => $settingUser->username,
                'user' => $settingUser->name,
                'role' => $settingUser->role ?? 'admin'
            ]);

            return redirect()->route('setting-document.dashboard');
        }

        return back()->withErrors(['msg' => 'Username atau password salah']);
    }

    public function dashboard()
    {
        try {
            if (!session('setting_authenticated')) {
                return redirect()->route('setting-document.login');
            }
            
            Log::info('=== SETTING DOCUMENT DASHBOARD LOADING ===');
            
            // Gunakan method yang sudah ada
            $customerNames = $this->getStaticBuyerList()->toArray();
            
            // Get existing settings
            $documentSettings = [];
            try {
                $settings = DocumentSetting::all();
                foreach ($settings as $setting) {
                    $documentSettings[$setting->customer_name] = $setting->allowed_documents ?? [];
                }
            } catch (\Exception $e) {
                Log::warning('Could not load document settings: ' . $e->getMessage());
            }
            
            // Available documents
            $availableDocuments = [
                'PEB', 'INVOICE', 'PACKING_LIST', 'COO', 'FUMIGASI', 
                'PYTOSANITARY', 'LACEY_ACT', 'ISF', 'TSCA', 'BL', 'AWB','BILL OF LADING','CONTAINER_LOAD','CONTAINER_LOAD_PLAN','CONTAINER_CHEKLIST','GCC','PPDF','VLEGAL','CARB','IREX','FLEGT'
            ];
            
            return view('setting-document.dashboard', compact(
                'customerNames',
                'documentSettings', 
                'availableDocuments'
            ));
            
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            
            return view('setting-document.dashboard', [
                'customerNames' => ['SAMPLE CUSTOMER', 'TEST BUYER'],
                'documentSettings' => [],
                'availableDocuments' => ['PEB', 'INVOICE', 'PACKING_LIST'],
                'error' => $e->getMessage()
            ]);
        }
    }

    // [SEMUA METHOD EXISTING LAINNYA TETAP SAMA - TIDAK PERLU DITAMPILKAN SEMUA DI SINI]
    // Copy paste semua method dari file original Anda yang belum saya tampilkan
    
    // ========================================
    // ✅ NEW METHODS: BUYER EMAIL CRUD
    // ========================================
    
    /**
     * Get all buyer emails with grouping by buyer_code
     */
    public function getBuyerEmails()
    {
        try {
            $buyerEmails = BuyerEmail::orderBy('buyer_code')
                ->orderBy('is_primary', 'desc')
                ->orderBy('email_type')
                ->get();
            
            // Group by buyer_code
            $grouped = $buyerEmails->groupBy('buyer_code')->map(function ($emails, $buyerCode) {
                $firstEmail = $emails->first();
                return [
                    'buyer_code' => $buyerCode,
                    'buyer_name' => $firstEmail->buyer_name,
                    'emails' => $emails->map(function ($email) {
                        return [
                            'id' => $email->id,
                            'email' => $email->email,
                            'contact_name' => $email->contact_name,
                            'email_type' => $email->email_type,
                            'is_primary' => $email->is_primary,
                            'created_at' => $email->created_at->format('Y-m-d H:i:s')
                        ];
                    })->toArray()
                ];
            })->values();
            
            return response()->json([
                'success' => true,
                'data' => $grouped
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get buyer emails error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load buyer emails: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get buyer codes for dropdown
     */
    public function getBuyerCodes()
    {
        try {
            $buyerCodes = BuyerEmail::select('buyer_code', 'buyer_name')
                ->distinct()
                ->orderBy('buyer_code')
                ->get()
                ->unique('buyer_code')
                ->values();
            
            return response()->json([
                'success' => true,
                'data' => $buyerCodes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get buyer codes error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load buyer codes'
            ], 500);
        }
    }
    
    /**
     * Store new buyer email
     */
    public function storeBuyerEmail(Request $request)
    {
        try {
            $validated = $request->validate([
                'buyer_code' => 'required|string|max:50',
                'buyer_name' => 'nullable|string|max:150',
                'email' => 'required|email|max:150',
                'contact_name' => 'nullable|string|max:150',
                'email_type' => 'required|in:To,CC,BCC',
                'is_primary' => 'boolean'
            ]);
            
            // Jika is_primary = true, set email lain dari buyer ini jadi false
            if ($validated['is_primary'] ?? false) {
                BuyerEmail::where('buyer_code', $validated['buyer_code'])
                    ->update(['is_primary' => false]);
            }
            
            // Jika email type = To dan belum ada primary, set as primary
            if ($validated['email_type'] === 'To') {
                $hasPrimary = BuyerEmail::where('buyer_code', $validated['buyer_code'])
                    ->where('is_primary', true)
                    ->exists();
                
                if (!$hasPrimary) {
                    $validated['is_primary'] = true;
                }
            }
            
            $buyerEmail = BuyerEmail::create($validated);
            
            Log::info('Buyer email created', [
                'id' => $buyerEmail->id,
                'buyer_code' => $buyerEmail->buyer_code,
                'email' => $buyerEmail->email,
                'user' => session('setting_user')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Buyer email berhasil ditambahkan',
                'data' => $buyerEmail
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Store buyer email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create buyer email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Update buyer email
     */
    public function updateBuyerEmail(Request $request, $id)
    {
        try {
            $buyerEmail = BuyerEmail::findOrFail($id);
            
            $validated = $request->validate([
                'buyer_code' => 'required|string|max:50',
                'buyer_name' => 'nullable|string|max:150',
                'email' => 'required|email|max:150',
                'contact_name' => 'nullable|string|max:150',
                'email_type' => 'required|in:To,CC,BCC',
                'is_primary' => 'boolean'
            ]);
            
            // Jika is_primary = true, set email lain dari buyer ini jadi false
            if ($validated['is_primary'] ?? false) {
                BuyerEmail::where('buyer_code', $validated['buyer_code'])
                    ->where('id', '!=', $id)
                    ->update(['is_primary' => false]);
            }
            
            $buyerEmail->update($validated);
            
            Log::info('Buyer email updated', [
                'id' => $buyerEmail->id,
                'buyer_code' => $buyerEmail->buyer_code,
                'email' => $buyerEmail->email,
                'user' => session('setting_user')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Buyer email berhasil diupdate',
                'data' => $buyerEmail
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Update buyer email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update buyer email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete buyer email
     */
    public function deleteBuyerEmail($id)
    {
        try {
            $buyerEmail = BuyerEmail::findOrFail($id);
            $buyerCode = $buyerEmail->buyer_code;
            $wasPrimary = $buyerEmail->is_primary;
            
            $buyerEmail->delete();
            
            // Jika yang dihapus adalah primary, set email pertama yang tersisa jadi primary
            if ($wasPrimary) {
                $firstRemaining = BuyerEmail::where('buyer_code', $buyerCode)
                    ->where('email_type', 'To')
                    ->first();
                
                if ($firstRemaining) {
                    $firstRemaining->update(['is_primary' => true]);
                }
            }
            
            Log::info('Buyer email deleted', [
                'id' => $id,
                'buyer_code' => $buyerCode,
                'user' => session('setting_user')
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Buyer email berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Delete buyer email error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete buyer email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get emails by buyer code
     */
    public function getEmailsByBuyerCode($buyerCode)
    {
        try {
            $emails = BuyerEmail::where('buyer_code', $buyerCode)
                ->orderBy('is_primary', 'desc')
                ->orderBy('email_type')
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $emails
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get emails by buyer code error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load emails'
            ], 500);
        }
    }
    
    /**
     * ENHANCED: Get customers dari billing data dengan caching
     */
    private function getBuyersFromBillingData()
    {
        try {
            return Cache::remember('billing_customers', 30 * 60, function () {
                Log::info('Fetching buyers from billing data...');
                
                $response = Http::timeout(15)->get($this->billingApiUrl . '/api/billing_data_fast');
                
                if (!$response->successful()) {
                    Log::warning('Fast endpoint failed, trying standard...');
                    $response = Http::timeout(30)->get($this->billingApiUrl . '/api/billing_data');
                }
                
                if ($response->successful()) {
                    $data = $response->json();
                    $billingData = $data['data'] ?? [];
                    
                    $customers = collect($billingData)
                        ->pluck('Customer Name')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();
                    
                    Log::info('Found ' . count($customers) . ' customers from billing data');
                    return $customers;
                }
                
                return collect([]);
            });
            
        } catch (\Exception $e) {
            Log::warning('Could not fetch buyers from billing data: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * NEW: Get customers dari document uploads
     */
    private function getCustomersFromUploads()
    {
        try {
            return Cache::remember('upload_customers', 10 * 60, function () {
                if (!DB::getSchemaBuilder()->hasTable('document_uploads')) {
                    return collect([]);
                }
                
                $customers = DB::table('document_uploads')
                    ->select('customer_name')
                    ->distinct()
                    ->whereNotNull('customer_name')
                    ->where('customer_name', '!=', '')
                    ->pluck('customer_name')
                    ->sort()
                    ->values();
                
                Log::info('Found ' . count($customers) . ' customers from uploads');
                return $customers;
            });
        } catch (\Exception $e) {
            Log::warning('Could not fetch customers from uploads: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function logout(Request $request)
    {
        $request->session()->forget([
            'setting_authenticated', 
            'setting_user', 
            'setting_username',
            'setting_role'
        ]);
        
        Log::info('Setting document logout');
        
        return redirect()->route('setting-document.login');
    }

    /**
     * ENHANCED: Available documents dengan team categorization
     */
    private function getAvailableDocumentsWithTeams()
    {
        $allDocuments = DocumentSetting::getAvailableDocuments();
        
        $categorized = [
            'Finance' => [
                'INVOICE',
                'PACKING_LIST', 
                'PAYMENT_INSTRUCTION'
            ],
            'Logistic' => [
                'CARB',
                'CONTAINER_LOAD',
                'CONTAINER_LOAD_CHEKLIST'
                
            ],
            'Exim' => [
                'PEB',
                'COO',
                'FUMIGASI',
                'PYTOSANITARY',
                'LACEY_ACT',
                'ISF',
                'CONTAINER_LOAD',
                'CONTAINER_CHEKLIST',
                'TSCA',
                'GCC',
                'PPDF',
                'VLEGAL',
                'CARB',
                'BILL OF LADING',
                'IREX',
                'AWB',
                'FLEGT'
            ]
        ];
        
        return [
            'all' => $allDocuments,
            'by_team' => $categorized,
            'team_colors' => [
                'Finance' => 'danger',
                'Logistic' => 'warning', 
                'Exim' => 'success'
            ]
        ];
    }

 // Di SettingDocumentController.php
private function getSettingStatistics()
{
    try {
        $stats = [
            'total_customers' => 0,
            'configured_customers' => 0,
            'total_documents_configured' => 0,
            'most_used_documents' => [],
            'team_distribution' => [
                'Finance' => 0,
                'Logistic' => 0,
                'Exim' => 0
            ],
            'recent_updates' => []
        ];

        // Cek table dengan try-catch
        try {
            $settings = DocumentSetting::all();
        } catch (\Exception $e) {
            Log::warning('DocumentSetting table issue: ' . $e->getMessage());
            return $stats; // Return default stats
        }

        $stats['configured_customers'] = $settings->count();
        
        // Document frequency analysis
        $documentFrequency = [];
        foreach ($settings as $setting) {
            if ($setting->allowed_documents && is_array($setting->allowed_documents)) {
                foreach ($setting->allowed_documents as $doc) {
                    $documentFrequency[$doc] = ($documentFrequency[$doc] ?? 0) + 1;
                }
                $stats['total_documents_configured'] += count($setting->allowed_documents);
            }
        }
        
        // Sort by frequency
        arsort($documentFrequency);
        $stats['most_used_documents'] = array_slice($documentFrequency, 0, 10, true);
        
        // Team distribution
        $teamDocs = [
            'Finance' => ['INVOICE', 'PACKING_LIST', 'PAYMENT_INSTRUCTION'],
            'Logistic' => ['CARB', 'CONTAINER_LOAD', 'CONTAINER_CHEKLIST'],
            'Exim' => ['PEB', 'COO', 'FUMIGASI', 'PYTOSANITARY', 'LACEY_ACT', 'ISF', 'TSCA', 'GCC', 'PPDF','CONTAINER_LOAD', 'VLEGAL','BILL OF LADING','CARB','CONTAINER_CHEKLIST','IREX','AWB','FLEGT']
        ];
        
        foreach ($settings as $setting) {
            if ($setting->allowed_documents && is_array($setting->allowed_documents)) {
                foreach ($setting->allowed_documents as $doc) {
                    foreach ($teamDocs as $team => $docs) {
                        if (in_array($doc, $docs)) {
                            $stats['team_distribution'][$team]++;
                        }
                    }
                }
            }
        }
        
        // Recent updates
        $stats['recent_updates'] = DocumentSetting::orderBy('updated_at', 'desc')
            ->limit(5)
            ->get(['customer_name', 'updated_at'])
            ->toArray();

        return $stats;
        
    } catch (\Exception $e) {
        Log::error('Error getting statistics: ' . $e->getMessage());
        return [
            'total_customers' => 0,
            'configured_customers' => 0,
            'total_documents_configured' => 0,
            'most_used_documents' => [],
            'team_distribution' => ['Finance' => 0, 'Logistic' => 0, 'Exim' => 0],
            'recent_updates' => []
        ];
    }
}

public function updateDocumentSettings(Request $request)
{
    try {
        Log::info('=== UPDATE DOCUMENT SETTINGS ===', [
            'request_data' => $request->all(),
            'user' => session('setting_user'),
            'authenticated' => session('setting_authenticated')
        ]);

        // ✅ CHECK: Authentication
        if (!session('setting_authenticated')) {
            Log::warning('❌ Unauthenticated request to update settings');
            
            return response()->json([
                'success' => false,
                'message' => 'Session expired. Please login again.',
                'error_code' => 'UNAUTHENTICATED'
            ], 401);
        }

        // ✅ VALIDATION
        try {
$validated = $request->validate([
    'customer_name' => 'required|string',
    'allowed_documents' => 'nullable|array',  // ✅ FIX
]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ Validation failed', [
                'errors' => $e->errors()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', array_flatten($e->errors())),
                'error_code' => 'VALIDATION_ERROR',
                'validation_errors' => $e->errors()
            ], 422);
        }

$customerName = $validated['customer_name'];
$allowedDocuments = $validated['allowed_documents'] ?? [];

        // ✅ SAVE TO DATABASE
        try {
            $setting = DocumentSetting::updateOrCreate(
                ['customer_name' => $customerName],
                ['allowed_documents' => $allowedDocuments]
            );

            Log::info('✅ Settings saved to database', [
                'customer' => $customerName,
                'documents_count' => count($allowedDocuments)
            ]);

        } catch (\Exception $dbError) {
            Log::error('❌ Database error', [
                'error' => $dbError->getMessage(),
                'trace' => $dbError->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $dbError->getMessage(),
                'error_code' => 'DATABASE_ERROR'
            ], 500);
        }

        // ✅ UPDATE CACHE (multiple keys for sync)
        try {
            $cacheKeys = [
                "exim_settings_{$customerName}",
                "integration_settings_{$customerName}",
                "document_settings_" . md5($customerName),
            ];

            $cacheData = [
                'customer_name' => $customerName,
                'allowed_documents' => $allowedDocuments,
                'updated_at' => now(),
                'status' => count($allowedDocuments) > 0 ? 'active' : 'inactive'
            ];

            foreach ($cacheKeys as $key) {
                Cache::put($key, $cacheData, now()->addHours(24));
                Log::info("✅ Cache updated: {$key}");
            }

        } catch (\Exception $cacheError) {
            Log::warning('⚠️ Cache update failed (non-critical)', [
                'error' => $cacheError->getMessage()
            ]);
            // Continue - cache failure is not critical
        }

        // ✅ BROADCAST UPDATE (optional - for WebSocket)
        try {
            // broadcast(new \App\Events\DocumentSettingsUpdated($customerName, $allowedDocuments));
        } catch (\Exception $broadcastError) {
            Log::warning('⚠️ Broadcast failed (non-critical)', [
                'error' => $broadcastError->getMessage()
            ]);
        }

        Log::info("✅ Settings saved successfully for: {$customerName}", [
            'documents' => $allowedDocuments,
            'cache_keys_updated' => count($cacheKeys ?? [])
        ]);

        // ✅ ALWAYS RETURN JSON
        return response()->json([
            'success' => true,
            'message' => "Settings saved for {$customerName}",
            'documents_count' => count($allowedDocuments),
            'cache_updated' => true,
            'customer_name' => $customerName,
            'timestamp' => now()->toDateTimeString()
        ], 200);

    } catch (\Exception $e) {
        Log::error('❌ Unexpected error in updateDocumentSettings', [
            'error' => $e->getMessage(),
            'type' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);

        // ✅ ALWAYS RETURN JSON (even on unexpected errors)
        return response()->json([
            'success' => false,
            'message' => 'Unexpected error: ' . $e->getMessage(),
            'error_code' => 'UNEXPECTED_ERROR',
            'error_type' => get_class($e),
            'timestamp' => now()->toDateTimeString()
        ], 500);
    }
}
 public function getDocumentSettings($customerName)
{
    try {
        set_time_limit(120);
        
        // ✅ PRIORITY 1: Check EXIM integration cache (paling fresh)
        $integrationKey = "exim_settings_{$customerName}";
        $cachedData = Cache::get($integrationKey);
        
        if ($cachedData) {
            Log::info("✅ Found EXIM cache for: {$customerName}");
            return response()->json([
                'success' => true,
                'allowed_documents' => $cachedData['allowed_documents'],
                'customer_name' => $cachedData['customer_name'],
                'source' => 'exim_cache',
                'updated_at' => $cachedData['updated_at']
            ]);
        }
        
        // ✅ PRIORITY 2: Check integration cache
        $integrationKey2 = "integration_settings_{$customerName}";
        $cachedData = Cache::get($integrationKey2);
        
        if ($cachedData) {
            Log::info("✅ Found integration cache for: {$customerName}");
            
            // Update EXIM cache juga
            Cache::put("exim_settings_{$customerName}", $cachedData, now()->addHours(24));
            
            return response()->json([
                'success' => true,
                'allowed_documents' => $cachedData['allowed_documents'],
                'customer_name' => $cachedData['customer_name'],
                'source' => 'integration_cache',
                'updated_at' => $cachedData['updated_at']
            ]);
        }
        
        // ✅ PRIORITY 3: Fallback ke database
        $setting = $this->findDocumentSettingByCustomerName($customerName);
        
        if ($setting) {
            Log::info("✅ Found database setting for: {$customerName}");
            
            $cacheData = [
                'customer_name' => $setting->customer_name,
                'allowed_documents' => $setting->allowed_documents,
                'updated_at' => now(),
                'status' => count($setting->allowed_documents) > 0 ? 'active' : 'inactive'
            ];
            
            // ✅ Update BOTH caches
            Cache::put("exim_settings_{$customerName}", $cacheData, now()->addHours(24));
            Cache::put("integration_settings_{$customerName}", $cacheData, now()->addHours(24));
            
            return response()->json([
                'success' => true,
                'allowed_documents' => $setting->allowed_documents,
                'customer_name' => $setting->customer_name,
                'source' => 'database_cached',
                'updated_at' => $setting->updated_at
            ]);
        }
        
        // ✅ NO SETTINGS FOUND
        Log::warning("⚠️ No settings found for: {$customerName}");
        
        $emptyCacheData = [
            'customer_name' => $customerName,
            'allowed_documents' => [],
            'updated_at' => now(),
            'status' => 'not_configured'
        ];
        
        Cache::put("exim_settings_{$customerName}", $emptyCacheData, now()->addMinutes(30));
        
        return response()->json([
            'success' => true,
            'allowed_documents' => [],
            'customer_name' => $customerName,
            'message' => 'No settings configured for this customer',
            'source' => 'none'
        ]);
        
    } catch (\Exception $e) {
        Log::error('❌ Error getting document settings: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'source' => 'error'
        ], 500);
    }
}
    /**
     * NEW: Get suggested documents based on customer patterns
     */
    private function getSuggestedDocuments($customerName)
    {
        try {
            // Basic suggestions based on customer name patterns
            $suggestions = [];
            
            $customerUpper = strtoupper($customerName);
            
            // Industry-based suggestions
            if (strpos($customerUpper, 'FURNITURE') !== false || 
                strpos($customerUpper, 'CHAIR') !== false) {
                $suggestions = ['PEB', 'INVOICE', 'PACKING_LIST', 'COO', 'FUMIGASI'];
            } elseif (strpos($customerUpper, 'LOGISTICS') !== false || 
                      strpos($customerUpper, 'FREIGHT') !== false ||
                      strpos($customerUpper, 'SHIPPING') !== false) {
                $suggestions = ['BL', 'AWB', 'CONTAINER_LOAD_PLAN', 'FREIGHT_INVOICE'];
            } else {
                // Default suggestions
                $suggestions = ['PEB', 'INVOICE', 'PACKING_LIST', 'COO'];
            }
            
            return $suggestions;
            
        } catch (\Exception $e) {
            return ['PEB', 'INVOICE', 'PACKING_LIST'];
        }
    }

    /**
     * NEW: Get documents by team
     */
    private function getDocumentsByTeam($team)
    {
        $teamDocuments = [
            'Finance' => ['INVOICE', 'PACKING_LIST', 'PAYMENT_INSTRUCTION'],
            'Logistic' => ['CARB','CONTAINER_LOAD','CONTAINER_CHEKLIST'],
            'Exim' => ['PEB', 'COO', 'FUMIGASI','AWB', 'PYTOSANITARY', 'LACEY_ACT', 'ISF', 'TSCA', 'GCC', 'PPDF', 'VLEGAL','CONTAINER_LOAD','BILL OF LADING', 'CONTAINER_LOAD_PLAN','CONTAINER_CHEKLIST','CARB','IREX','FLEGT']
        ];
        
        return $teamDocuments[$team] ?? [];
    }

    /**
     * NEW: Trigger dashboard sync
     */
    private function triggerDashboardSync($customerName, $allowedDocuments)
    {
        try {
            // Log untuk dashboard refresh
            Log::info("=== SETTING UPDATED - DASHBOARD SYNC ===", [
                'customer_name' => $customerName,
                'allowed_documents' => $allowedDocuments,
                'timestamp' => now()->toDateTimeString(),
                'trigger' => 'document_setting_update'
            ]);
            
            // TODO: Add WebSocket notification untuk real-time update
            // TODO: Add cache invalidation untuk dashboard data
            
        } catch (\Exception $e) {
            Log::warning('Dashboard sync trigger failed: ' . $e->getMessage());
        }
    }

    /**
     * ENHANCED: Normalisasi document settings
     */
    private function normalizeDocumentSettings($documentSettings)
    {
        $normalized = [];
        
        foreach ($documentSettings as $customerName => $allowedDocs) {
            // Simpan dengan nama asli
            $normalized[$customerName] = $allowedDocs;
            
            // Buat mapping variations untuk backward compatibility
            $variations = [
                $this->normalizeCustomerName($customerName),
                strtoupper($customerName),
                preg_replace('/[^a-zA-Z0-9\s]/', '', $customerName)
            ];
            
            foreach ($variations as $variation) {
                if ($variation !== $customerName && !isset($normalized[$variation])) {
                    $normalized[$variation] = $allowedDocs;
                }
            }
        }
        
        return $normalized;
    }

    /**
     * ENHANCED: Find document setting dengan multiple strategies
     */
    private function findDocumentSettingByCustomerName($customerName)
    {
        // Strategy 1: Exact match
        $setting = DocumentSetting::where('customer_name', $customerName)->first();
        if ($setting) {
            Log::info("Found exact match for customer: {$customerName}");
            return $setting;
        }
        
        // Strategy 2: Normalized match
        $normalizedName = $this->normalizeCustomerName($customerName);
        $setting = DocumentSetting::where('customer_name', $normalizedName)->first();
        if ($setting) {
            Log::info("Found normalized match: {$customerName} -> {$normalizedName}");
            return $setting;
        }
        
        // Strategy 3: Case insensitive
        $setting = DocumentSetting::whereRaw('UPPER(customer_name) = ?', [strtoupper($customerName)])->first();
        if ($setting) {
            Log::info("Found case insensitive match: {$customerName}");
            return $setting;
        }
        
        // Strategy 4: Clean name match (tanpa special characters)
        $cleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $customerName);
        $settings = DocumentSetting::all();
        foreach ($settings as $setting) {
            $settingCleanName = preg_replace('/[^a-zA-Z0-9\s]/', '', $setting->customer_name);
            if (strtoupper($cleanName) === strtoupper($settingCleanName)) {
                Log::info("Found clean name match: {$customerName} -> {$setting->customer_name}");
                return $setting;
            }
        }
        
        // Strategy 5: Fuzzy matching (similarity)
        foreach ($settings as $setting) {
            $similarity = 0;
            similar_text(strtoupper($customerName), strtoupper($setting->customer_name), $similarity);
            if ($similarity > 90) { // 90% similarity threshold
                Log::info("Found fuzzy match: {$customerName} -> {$setting->customer_name} ({$similarity}% similarity)");
                return $setting;
            }
        }
        
        Log::info("No document setting found for customer: {$customerName}");
        return null;
    }

    /**
     * Normalize customer name
     */
    private function normalizeCustomerName($name)
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    /**
     * NEW: Bulk update settings (untuk admin)
     */
    public function bulkUpdateSettings(Request $request)
    {
        $request->validate([
            'customers' => 'required|array',
            'customers.*' => 'string',
            'documents' => 'required|array',
            'documents.*' => 'string|in:' . implode(',', DocumentSetting::getAvailableDocuments()),
            'operation' => 'required|string|in:replace,add,remove'
        ]);

        try {
            $customers = $request->customers;
            $documents = $request->documents;
            $operation = $request->operation;
            $updatedCount = 0;
            $results = [];

            DB::beginTransaction();

            foreach ($customers as $customerName) {
                try {
                    $setting = DocumentSetting::firstOrNew(['customer_name' => $customerName]);
                    $currentDocs = $setting->allowed_documents ?? [];

                    switch ($operation) {
                        case 'replace':
                            $newDocs = $documents;
                            break;
                        case 'add':
                            $newDocs = array_unique(array_merge($currentDocs, $documents));
                            break;
                        case 'remove':
                            $newDocs = array_diff($currentDocs, $documents);
                            break;
                        default:
                            $newDocs = $currentDocs;
                    }

                    $setting->allowed_documents = array_values($newDocs);
                    $setting->save();

                    $results[] = [
                        'customer' => $customerName,
                        'success' => true,
                        'documents_before' => count($currentDocs),
                        'documents_after' => count($newDocs)
                    ];
                    $updatedCount++;

                } catch (\Exception $e) {
                    $results[] = [
                        'customer' => $customerName,
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // Clear caches
            Cache::flush();

            Log::info("Bulk update completed", [
                'operation' => $operation,
                'total_customers' => count($customers),
                'updated_count' => $updatedCount,
                'documents' => $documents
            ]);

            return response()->json([
                'success' => true,
                'message' => "Bulk {$operation} completed for {$updatedCount} customers",
                'updated_count' => $updatedCount,
                'total_customers' => count($customers),
                'operation' => $operation,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * NEW: Export settings
     */
    public function exportSettings(Request $request)
    {
        try {
            $format = $request->get('format', 'json');
            $settings = DocumentSetting::all();

            $data = $settings->map(function($setting) {
                return [
                    'customer_name' => $setting->customer_name,
                    'allowed_documents' => $setting->allowed_documents,
                    'documents_count' => count($setting->allowed_documents),
                    'notes' => $setting->notes ?? '',
                    'updated_at' => $setting->updated_at->toDateTimeString()
                ];
            });

            $filename = 'document_settings_' . now()->format('Y-m-d_H-i-s');

            if ($format === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"{$filename}.csv\""
                ];

                $callback = function() use ($data) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, ['Customer Name', 'Allowed Documents', 'Count', 'Notes', 'Updated At']);
                    
                    foreach ($data as $row) {
                        fputcsv($file, [
                            $row['customer_name'],
                            implode(';', $row['allowed_documents']),
                            $row['documents_count'],
                            $row['notes'],
                            $row['updated_at']
                        ]);
                    }
                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            } else {
                // JSON format
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'total_settings' => count($data),
                    'exported_at' => now()->toDateTimeString()
                ])->header('Content-Disposition', "attachment; filename=\"{$filename}.json\"");
            }

        } catch (\Exception $e) {
            Log::error('Export failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API endpoints untuk dashboard integration
     */

    public function getBuyerList()
    {
        try {
            $staticCustomers = $this->getStaticBuyerList();
            $billingCustomers = $this->getBuyersFromBillingData();
            $uploadCustomers = $this->getCustomersFromUploads();
            
            $allCustomers = $staticCustomers
                ->merge($billingCustomers)
                ->merge($uploadCustomers)
                ->unique()
                ->sort()
                ->values();
            
            return response()->json([
                'success' => true,
                'data' => $allCustomers->map(function($name) {
                    return ['customer_name' => $name];
                }),
                'sources' => [
                    'static' => count($staticCustomers),
                    'billing' => count($billingCustomers),
                    'uploads' => count($uploadCustomers),
                    'total_unique' => count($allCustomers)
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting buyer list: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $this->getStaticBuyerList()->map(function($name) {
                    return ['customer_name' => $name];
                })
            ]);
        }
    }

    public function getAllDocumentSettings()
    {
        try {
            $cacheKey = 'all_document_settings';
            
            $result = Cache::remember($cacheKey, 15 * 60, function () {
                $settings = DocumentSetting::all();
                $settingsMap = [];
                
                foreach ($settings as $setting) {
                    $customerName = $setting->customer_name;
                    $allowedDocs = $setting->allowed_documents;
                    
                    // Store dengan berbagai variasi untuk compatibility
                    $variations = [
                        $customerName,
                        $this->normalizeCustomerName($customerName),
                        strtoupper($customerName),
                        preg_replace('/[^a-zA-Z0-9\s]/', '', $customerName)
                    ];
                    
                    foreach ($variations as $variation) {
                        if (!isset($settingsMap[$variation])) {
                            $settingsMap[$variation] = $allowedDocs;
                        }
                    }
                }
                
                return [
                    'settings_map' => $settingsMap,
                    'original_count' => $settings->count(),
                    'variations_count' => count($settingsMap)
                ];
            });
            
            Log::info('Document settings API called', [
                'original_count' => $result['original_count'],
                'variations_count' => $result['variations_count']
            ]);
            
            return response()->json([
                'success' => true,
                'data' => $result['settings_map'],
                'count' => $result['original_count'],
                'variations_count' => $result['variations_count'],
                'cached' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting all document settings: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

  

    /**
 * Handle settings update notification untuk EXIM integration
 */
public function handleSettingsUpdate(Request $request)
{
    try {
        $customerName = $request->customer_name;
        $allowedDocuments = $request->allowed_documents ?? [];
        
        // Store integration cache untuk EXIM dashboard
        $cacheKey = "integration_settings_{$customerName}";
        Cache::put($cacheKey, [
            'customer_name' => $customerName,
            'allowed_documents' => $allowedDocuments,
            'updated_at' => now(),
            'integration_status' => count($allowedDocuments) > 0 ? 'active' : 'inactive'
        ], now()->addHours(24));
        
        Log::info('EXIM Integration cache updated', [
            'customer' => $customerName,
            'documents_count' => count($allowedDocuments),
            'status' => count($allowedDocuments) > 0 ? 'active' : 'inactive'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Integration updated successfully',
            'integration_status' => count($allowedDocuments) > 0 ? 'active' : 'inactive'
        ]);
        
    } catch (\Exception $e) {
        Log::error('Integration update failed: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'message' => 'Integration update failed'
        ], 500);
    }
}



/**
 * Get customer settings untuk EXIM dashboard
 */
public function getCustomerSettingsForExim($customer)
{
    try {
        // Check cache first
        $cacheKey = "integration_settings_{$customer}";
        $cachedData = Cache::get($cacheKey);
        
        if ($cachedData) {
            return response()->json([
                'success' => true,
                'allowed_documents' => $cachedData['allowed_documents'],
                'integration_status' => $cachedData['integration_status'],
                'source' => 'cache'
            ]);
        }
        
        // Fallback ke database
        $setting = $this->findDocumentSettingByCustomerName($customer);
        
        if ($setting) {
            return response()->json([
                'success' => true,
                'allowed_documents' => $setting->allowed_documents,
                'integration_status' => 'active',
                'source' => 'database'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'allowed_documents' => [],
                'integration_status' => 'no_settings',
                'source' => 'none'
            ]);
        }
        
    } catch (\Exception $e) {
        Log::error('Error getting customer settings for EXIM: ' . $e->getMessage());
        
        return response()->json([
            'success' => false,
            'allowed_documents' => [],
            'integration_status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
}
public function getSettings($customer)
{
    try {
        Log::info('=== GET SETTINGS REQUEST ===', [
            'customer_raw' => $customer,
            'customer_decoded' => urldecode($customer),
            'timestamp' => now()->toDateTimeString(),
            'ip' => request()->ip()
        ]);
        
        // Decode customer name dari URL
        $decodedCustomer = urldecode($customer);
        
        // ✅ Method 1: Try exact match first
        $setting = DocumentSetting::where('customer_name', $decodedCustomer)->first();
        
        // ✅ Method 2: Try normalized match if exact fails
        if (!$setting) {
            Log::info('Exact match not found, trying normalized search...', [
                'customer' => $decodedCustomer
            ]);
            
            // Check if findDocumentSettingByCustomerName method exists
            if (method_exists($this, 'findDocumentSettingByCustomerName')) {
                $setting = $this->findDocumentSettingByCustomerName($decodedCustomer);
            }
        }
        
        // ✅ Method 3: Try case-insensitive search
        if (!$setting) {
            Log::info('Normalized match not found, trying case-insensitive...', [
                'customer' => $decodedCustomer
            ]);
            $setting = DocumentSetting::whereRaw('LOWER(customer_name) = ?', [strtolower($decodedCustomer)])->first();
        }
        
        // ✅ SUCCESS: Settings found
        if ($setting) {
            Log::info('✅ Settings found successfully', [
                'customer_requested' => $decodedCustomer,
                'customer_in_db' => $setting->customer_name,
                'documents_count' => count($setting->allowed_documents ?? []),
                'documents' => $setting->allowed_documents
            ]);
            
            return response()->json([
                'success' => true,
                'allowed_documents' => $setting->allowed_documents ?? [],
                'customer_name' => $setting->customer_name,
                'integration_status' => 'active',
                'source' => 'database'
            ]);
        } 
        
        // ⚠️ WARNING: No settings found
        else {
            Log::warning('⚠️ No settings found for customer', [
                'customer_requested' => $decodedCustomer,
                'all_customers_in_db' => DocumentSetting::pluck('customer_name')->toArray(),
                'total_settings' => DocumentSetting::count(),
                'suggestion' => 'Customer may need configuration in Document Settings dashboard'
            ]);
            
            return response()->json([
                'success' => true,
                'allowed_documents' => [],
                'customer_name' => $decodedCustomer,
                'integration_status' => 'no_settings',
                'message' => 'No document settings configured for this customer. Please configure in Document Settings.',
                'source' => 'none'
            ]);
        }
        
    } catch (\Exception $e) {
        Log::error('❌ Get settings error', [
            'customer_raw' => $customer,
            'customer_decoded' => urldecode($customer),
            'error_message' => $e->getMessage(),
            'error_line' => $e->getLine(),
            'error_file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'allowed_documents' => [],
            'integration_status' => 'error',
            'message' => 'Failed to load settings: ' . $e->getMessage(),
            'source' => 'error'
        ], 500);
    }
}

}