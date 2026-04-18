<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // TAMBAHAN: Import Auth facade

class BillingController extends Controller
{
    private $billingApiUrl = 'http://127.0.0.1:50';
    private $sapLoginApiUrl = 'http://127.0.0.1:51'; // Backup service

    public function index()
    {
        try {
            // Log attempt to connect
            Log::info('Attempting to connect to billing API: ' . $this->billingApiUrl . '/api/billing_data');
           
            // Check if service is reachable first with health check
            try {
                $healthCheck = Http::timeout(10)->get($this->billingApiUrl . '/health');
                if (!$healthCheck->successful()) {
                    Log::warning('Health check failed for billing service');
                    throw new \Exception('Health check failed');
                }
                Log::info('Health check passed for billing service');
            } catch (\Exception $healthError) {
                Log::warning('Health check error: ' . $healthError->getMessage());
                // Continue anyway, maybe health endpoint doesn't exist
            }

            // PERBAIKAN: Tingkatkan timeout menjadi 180 detik (3 menit)
            Log::info('Calling billing data endpoint with 180 second timeout...');
            $response = Http::timeout(180)->get($this->billingApiUrl . '/api/billing_data');
           
            // Debug response
            Log::info('Billing API Response Status: ' . $response->status());
            Log::info('Billing API Response Headers: ' . json_encode($response->headers()));
           
            if ($response->successful()) {
                $data = $response->json();
                Log::info('Billing API Response Data Keys: ' . json_encode(array_keys($data)));
               
                $billingData = $data['data'] ?? [];
                Log::info('Billing Data Count: ' . count($billingData));

                // Filter hanya field yang diperlukan
                $filteredData = $this->filterRequiredFields($billingData);

                return view('billing.index', [
                    'billingData' => $filteredData,
                    'totalRecords' => count($filteredData),
                    'responseTime' => $data['response_time'] ?? 'N/A',
                    'service_used' => 'bill.py (port 50)',
                    'success' => true,
                    'timeout_used' => '180 seconds'
                ]);
            } else {
                $errorBody = $response->body();
                Log::error('Billing API failed with status: ' . $response->status());
                Log::error('Billing API error response: ' . $errorBody);
               
                return view('billing.index', [
                    'billingData' => [],
                    'totalRecords' => 0,
                    'error' => 'Failed to fetch billing data from SAP. Status: ' . $response->status() . '. Response: ' . substr($errorBody, 0, 500),
                    'responseTime' => 'N/A',
                    'service_used' => 'bill.py (port 50) - HTTP ERROR',
                    'timeout_used' => '180 seconds'
                ]);
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection Error to Billing API: ' . $e->getMessage());
           
            // PERBAIKAN: Analisis timeout lebih detail
            $errorMessage = $e->getMessage();
            $isTimeout = strpos($errorMessage, 'timeout') !== false || strpos($errorMessage, 'timed out') !== false;
            
            return view('billing.index', [
                'billingData' => [],
                'totalRecords' => 0,
                'error' => $isTimeout ? 
                    'SAP connection timeout. The system is taking longer than 3 minutes to respond. This usually indicates SAP server is slow or overloaded.' :
                    'Cannot connect to billing service on port 50. Please check if bill.py is running.',
                'responseTime' => 'N/A',
                'service_used' => 'bill.py (port 50) - ' . ($isTimeout ? 'TIMEOUT' : 'CONNECTION FAILED'),
                'debug_info' => [
                    'service_url' => $this->billingApiUrl,
                    'error_type' => 'ConnectionException',
                    'is_timeout' => $isTimeout,
                    'solution' => $isTimeout ? 
                        'Check SAP server status, SAP Router connectivity, or increase timeout further' :
                        'Run: python bill.py',
                    'timeout_used' => '180 seconds'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Billing API Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return view('billing.index', [
                'billingData' => [],
                'totalRecords' => 0,
                'error' => 'Error connecting to billing service: ' . $e->getMessage(),
                'responseTime' => 'N/A',
                'service_used' => 'bill.py (port 50) - ERROR',
                'debug_info' => [
                    'error_type' => get_class($e),
                    'error_message' => $e->getMessage(),
                    'timeout_used' => '180 seconds'
                ]
            ]);
        }
    }

    /**
     * BARU: Endpoint untuk test dengan timeout berbeda-beda
     */
    public function indexWithCustomTimeout(Request $request)
    {
        $customTimeout = $request->get('timeout', 60); // Default 60 detik
        $endpoint = $request->get('endpoint', 'api/billing_data'); // Default endpoint
        
        try {
            Log::info("Testing with custom timeout: {$customTimeout} seconds, endpoint: {$endpoint}");
            
            $response = Http::timeout($customTimeout)->get($this->billingApiUrl . '/' . $endpoint);
            
            if ($response->successful()) {
                $data = $response->json();
                $billingData = $data['data'] ?? [];
                $filteredData = $this->filterRequiredFields($billingData);

                return view('billing.index', [
                    'billingData' => $filteredData,
                    'totalRecords' => count($filteredData),
                    'responseTime' => $data['response_time'] ?? 'N/A',
                    'service_used' => "bill.py - {$endpoint}",
                    'success' => true,
                    'timeout_used' => "{$customTimeout} seconds",
                    'custom_test' => true
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'timeout_used' => "{$customTimeout} seconds"
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'timeout_used' => "{$customTimeout} seconds"
            ]);
        }
    }

    /**
     * Test connection to both services - ENHANCED
     */
    public function testConnection()
    {
        $results = [
            'timestamp' => now()->toDateTimeString(),
            'services' => []
        ];
       
        // Test bill.py service health (quick check)
        try {
            Log::info('Testing connection to bill.py service...');
            $response = Http::timeout(10)->get($this->billingApiUrl . '/health');
           
            $results['services']['bill_py_health'] = [
                'name' => 'bill.py Health Check',
                'url' => $this->billingApiUrl . '/health',
                'status' => $response->successful() ? 'OK' : 'FAILED',
                'response_code' => $response->status(),
                'response_time' => $response->transferStats ? $response->transferStats->getTransferTime() : 'N/A',
                'response_body' => $response->successful() ? $response->json() : $response->body()
            ];
        } catch (\Exception $e) {
            $results['services']['bill_py_health'] = [
                'name' => 'bill.py Health Check',
                'url' => $this->billingApiUrl . '/health',
                'status' => 'ERROR',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'solution' => 'Run command: python bill.py'
            ];
        }

        // BARU: Test SAP connection khusus
        try {
            Log::info('Testing SAP connection specifically...');
            $response = Http::timeout(30)->get($this->billingApiUrl . '/api/test_sap_connection');
           
            $results['services']['sap_connection'] = [
                'name' => 'SAP Connection Test',
                'url' => $this->billingApiUrl . '/api/test_sap_connection',
                'status' => $response->successful() ? 'OK' : 'FAILED',
                'response_code' => $response->status(),
                'response_time' => $response->transferStats ? $response->transferStats->getTransferTime() : 'N/A',
                'response_body' => $response->successful() ? $response->json() : $response->body()
            ];
        } catch (\Exception $e) {
            $results['services']['sap_connection'] = [
                'name' => 'SAP Connection Test',
                'url' => $this->billingApiUrl . '/api/test_sap_connection',
                'status' => 'ERROR',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage()
            ];
        }
       
        // Test sap_login.py service (backup)
        try {
            Log::info('Testing connection to sap_login.py service...');
            $response = Http::timeout(10)->get($this->sapLoginApiUrl . '/api/health');
           
            $results['services']['sap_login_py'] = [
                'name' => 'sap_login.py (SAP Login Service)',
                'url' => $this->sapLoginApiUrl . '/api/health',
                'status' => $response->successful() ? 'OK' : 'FAILED',
                'response_code' => $response->status(),
                'response_time' => $response->transferStats ? $response->transferStats->getTransferTime() : 'N/A',
                'response_body' => $response->successful() ? $response->json() : $response->body()
            ];
        } catch (\Exception $e) {
            $results['services']['sap_login_py'] = [
                'name' => 'sap_login.py (SAP Login Service)',
                'url' => $this->sapLoginApiUrl . '/api/health',
                'status' => 'ERROR',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'solution' => 'Run command: python sap_login.py'
            ];
        }

        // Test billing data endpoint dengan timeout lebih besar
        try {
            Log::info('Testing billing data endpoint with extended timeout...');
            $response = Http::timeout(60)->get($this->billingApiUrl . '/api/billing_data_fast');
           
            $results['services']['billing_data_fast'] = [
                'name' => 'Billing Data Fast Endpoint',
                'url' => $this->billingApiUrl . '/api/billing_data_fast',
                'status' => $response->successful() ? 'OK' : 'FAILED',
                'response_code' => $response->status(),
                'response_time' => $response->transferStats ? $response->transferStats->getTransferTime() : 'N/A',
                'data_count' => $response->successful() ? count($response->json()['data'] ?? []) : 0,
                'timeout_used' => '60 seconds'
            ];
        } catch (\Exception $e) {
            $results['services']['billing_data_fast'] = [
                'name' => 'Billing Data Fast Endpoint',
                'url' => $this->billingApiUrl . '/api/billing_data_fast',
                'status' => 'ERROR',
                'error_type' => get_class($e),
                'error_message' => $e->getMessage(),
                'timeout_used' => '60 seconds'
            ];
        }

        // Overall status
        $allOk = true;
        foreach ($results['services'] as $service) {
            if ($service['status'] !== 'OK') {
                $allOk = false;
                break;
            }
        }

        $results['overall_status'] = $allOk ? 'ALL_OK' : 'SOME_FAILED';
        $results['recommendations'] = $this->getRecommendations($results['services']);

        return response()->json($results);
    }

    /**
     * Get recommendations based on service status - ENHANCED
     */
    private function getRecommendations($services)
    {
        $recommendations = [];

        if ($services['bill_py_health']['status'] !== 'OK') {
            $recommendations[] = [
                'issue' => 'bill.py service not running',
                'solution' => 'Run command: python bill.py',
                'priority' => 'HIGH'
            ];
        }

        if (isset($services['sap_connection']) && $services['sap_connection']['status'] !== 'OK') {
            $errorMsg = $services['sap_connection']['error_message'] ?? '';
            
            if (strpos($errorMsg, 'timeout') !== false) {
                $recommendations[] = [
                    'issue' => 'SAP connection timeout',
                    'solution' => 'Check SAP Router (180.250.178.70), SAP server status, or network latency',
                    'priority' => 'HIGH'
                ];
            } else {
                $recommendations[] = [
                    'issue' => 'SAP connection failed',
                    'solution' => 'Check SAP credentials, SAP server status, and RFC function Z_FM_BILLING_INITIATOR1',
                    'priority' => 'HIGH'
                ];
            }
        }

        if (isset($services['billing_data_fast']) && $services['billing_data_fast']['status'] !== 'OK') {
            $recommendations[] = [
                'issue' => 'Billing data endpoint timeout/error',
                'solution' => 'SAP system might be slow. Consider using background jobs for data fetching.',
                'priority' => 'MEDIUM'
            ];
        }

        if ($services['sap_login_py']['status'] !== 'OK') {
            $recommendations[] = [
                'issue' => 'sap_login.py service not running',
                'solution' => 'Run command: python sap_login.py (backup service)',
                'priority' => 'LOW'
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'status' => 'All services are running properly',
                'priority' => 'INFO'
            ];
        }

        return $recommendations;
    }

    /**
     * Quick service status check
     */
    public function quickStatus()
    {
        try {
            $response = Http::timeout(5)->get($this->billingApiUrl . '/health');
           
            return response()->json([
                'status' => $response->successful() ? 'online' : 'offline',
                'response_code' => $response->status(),
                'message' => $response->successful() ? 'Billing service is running' : 'Billing service is not responding',
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'message' => 'Cannot connect to billing service: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 503);
        }
    }

    private function filterRequiredFields($data)
    {
        $fieldMapping = [
            'Delivery' => 'VBELN',
            'Customer Name' => 'NAME1',
            'Billing Document' => 'VBELN_VBRK',
            'Net Value in Document Currency' => 'NETWR',
            'Billing Date' => 'FKDAT',
            'Currency' => 'WAERK',
            'Description' => 'ARKTX',
            'Material Number' => 'MATNR'
        ];

        $filteredData = [];
        foreach ($data as $item) {
            $filteredItem = [];
            foreach ($fieldMapping as $displayName => $apiField) {
                $filteredItem[$displayName] = $item[$apiField] ?? '';
            }
            $filteredData[] = $filteredItem;
        }

        return $filteredData;
    }

    public function submitBilling(Request $request)
    {
        // Placeholder untuk fungsi submit
        $delivery = $request->input('delivery');

        // Log the action
        Log::info('Submit billing action for delivery: ' . $delivery);

        return response()->json([
            'status' => 'success',
            'message' => 'Submit action for delivery: ' . $delivery,
            'action' => 'submit',
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    public function detailBilling(Request $request)
    {
        // Placeholder untuk fungsi detail
        $delivery = $request->input('delivery');

        // Log the action
        Log::info('Detail billing action for delivery: ' . $delivery);

        return response()->json([
            'status' => 'success',
            'message' => 'Detail action for delivery: ' . $delivery,
            'action' => 'detail',
            'timestamp' => now()->toDateTimeString()
        ]);
    }

    /**
     * Enhanced billing submit with retry mechanism
     */
    public function submitBillingWithRetry(Request $request)
    {
        $delivery = $request->input('delivery');
        $maxRetries = 3;
        $retryDelay = 2; // seconds
        
        Log::info("Submit billing with retry for delivery: $delivery");
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("Submit billing attempt $attempt for delivery: $delivery");
                
                // Call SAP service to submit billing
                $response = Http::timeout(60)->post($this->billingApiUrl . '/api/submit_billing', [
                    'delivery_order' => $delivery,
                    'action' => 'submit'
                ]);
                
                if ($response->successful()) {
                    Log::info("Submit billing successful on attempt $attempt for delivery: $delivery");
                    return response()->json([
                        'status' => 'success',
                        'message' => "Billing submitted successfully for delivery: $delivery",
                        'attempt' => $attempt,
                        'data' => $response->json(),
                        'timestamp' => now()->toDateTimeString()
                    ]);
                }
                
                Log::warning("Submit billing failed on attempt $attempt for delivery: $delivery - Status: " . $response->status());
                
            } catch (\Exception $e) {
                Log::error("Submit billing attempt $attempt failed for delivery: $delivery - Error: " . $e->getMessage());
                
                if ($attempt < $maxRetries) {
                    sleep($retryDelay);
                }
            }
        }
        
        return response()->json([
            'status' => 'error',
            'message' => "Failed to submit billing for delivery: $delivery after $maxRetries attempts",
            'attempts' => $maxRetries,
            'timestamp' => now()->toDateTimeString()
        ], 500);
    }

    /**
     * Enhanced billing detail with fallback
     */
    public function detailBillingWithFallback(Request $request)
    {
        $delivery = $request->input('delivery');
        
        Log::info("Detail billing with fallback for delivery: $delivery");
        
        try {
            // Try primary endpoint first
            $response = Http::timeout(30)->post($this->billingApiUrl . '/api/billing_detail', [
                'delivery_order' => $delivery
            ]);
            
            if ($response->successful()) {
                Log::info("Detail billing successful via primary endpoint for delivery: $delivery");
                return response()->json([
                    'status' => 'success',
                    'message' => "Billing details retrieved for delivery: $delivery",
                    'data' => $response->json(),
                    'source' => 'primary_endpoint',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::warning("Primary endpoint failed for delivery detail: $delivery - " . $e->getMessage());
        }
        
        try {
            // Try fallback endpoint
            $response = Http::timeout(30)->get($this->billingApiUrl . '/api/billing_data_fast', [
                'filter_delivery' => $delivery
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $filteredData = array_filter($data['data'] ?? [], function($item) use ($delivery) {
                    return isset($item['VBELN']) && $item['VBELN'] === $delivery;
                });
                
                Log::info("Detail billing successful via fallback endpoint for delivery: $delivery");
                return response()->json([
                    'status' => 'success',
                    'message' => "Billing details retrieved via fallback for delivery: $delivery",
                    'data' => array_values($filteredData),
                    'source' => 'fallback_endpoint',
                    'timestamp' => now()->toDateTimeString()
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error("Fallback endpoint also failed for delivery detail: $delivery - " . $e->getMessage());
        }
        
        return response()->json([
            'status' => 'error',
            'message' => "Failed to retrieve billing details for delivery: $delivery",
            'timestamp' => now()->toDateTimeString()
        ], 500);
    }

    /**
     * Refresh billing data
     */
    public function refreshBillingData(Request $request)
    {
        try {
            Log::info('Refreshing billing data...');
            
            $response = Http::timeout(120)->get($this->billingApiUrl . '/api/billing_data_fast');
            
            if ($response->successful()) {
                $data = $response->json();
                $billingData = $data['data'] ?? [];
                
                Log::info('Billing data refreshed successfully - Count: ' . count($billingData));
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Billing data refreshed successfully',
                    'data_count' => count($billingData),
                    'last_updated' => now()->toDateTimeString()
                ]);
            }
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to refresh billing data - HTTP ' . $response->status()
            ], $response->status());
            
        } catch (\Exception $e) {
            Log::error('Error refreshing billing data: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error refreshing billing data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get billing data status
     */
    public function getBillingDataStatus()
    {
        try {
            $response = Http::timeout(10)->get($this->billingApiUrl . '/api/data_status');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'status' => 'unknown',
                'message' => 'Unable to fetch data status',
                'last_check' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error checking data status: ' . $e->getMessage(),
                'last_check' => now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Generate single billing document
     */
    public function generateSingleBilling(Request $request)
    {
        try {
            $delivery = $request->input('delivery');
            
            if (!$delivery) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delivery order is required'
                ], 400);
            }
            
            // Log the billing generation request
            Log::info("Generating billing for delivery: $delivery");
            
            // Di implementasi nyata, panggil SAP atau sistem billing
            // Contoh call ke SAP:
            // $response = Http::timeout(60)->post($this->billingApiUrl . '/api/generate_billing', [
            //     'delivery_order' => $delivery
            // ]);
            
            // Untuk simulasi, kita buat billing document dummy
            $billingDocument = 'INV' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
            
            // Simulasi proses generation
            sleep(2); // Simulasi processing time
            
            Log::info("Successfully generated billing document: $billingDocument for delivery: $delivery");
            
            return response()->json([
                'status' => 'success',
                'message' => 'Billing document generated successfully',
                'data' => [
                    'delivery' => $delivery,
                    'billing_document' => $billingDocument,
                    'generated_at' => now()->toDateTimeString(),
                    'generated_by' => Auth::check() ? Auth::user()->name : 'System' // PERBAIKAN: Gunakan Auth::check()
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating billing: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate billing: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Generate bulk billing documents
     */
    public function generateBulkBilling(Request $request)
    {
        try {
            $deliveries = $request->input('deliveries', []);
            
            if (!is_array($deliveries) || empty($deliveries)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delivery orders array is required'
                ], 400);
            }
            
            Log::info("Generating bulk billing for " . count($deliveries) . " deliveries: " . implode(', ', $deliveries));
            
            $results = [];
            $successCount = 0;
            $failCount = 0;
            
            foreach ($deliveries as $delivery) {
                try {
                    // Validate delivery format
                    if (empty($delivery) || !is_string($delivery)) {
                        throw new \Exception("Invalid delivery order format");
                    }
                    
                    // Simulate billing generation untuk setiap delivery
                    $billingDocument = 'INV' . str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT);
                    
                    // Simulasi random success/failure untuk demo
                    $isSuccess = rand(1, 10) > 1; // 90% success rate
                    
                    if ($isSuccess) {
                        $results[] = [
                            'delivery' => $delivery,
                            'status' => 'success',
                            'billing_document' => $billingDocument,
                            'generated_at' => now()->toDateTimeString()
                        ];
                        
                        $successCount++;
                        Log::info("Successfully generated billing document: $billingDocument for delivery: $delivery");
                        
                    } else {
                        throw new \Exception("SAP connection timeout");
                    }
                    
                    // Simulasi processing delay
                    usleep(500000); // 0.5 second delay per item
                    
                } catch (\Exception $e) {
                    $results[] = [
                        'delivery' => $delivery,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                        'attempted_at' => now()->toDateTimeString()
                    ];
                    
                    $failCount++;
                    Log::error("Failed to generate billing for delivery $delivery: " . $e->getMessage());
                }
            }
            
            $message = "Bulk billing generation completed: $successCount successful, $failCount failed";
            Log::info($message);
            
            return response()->json([
                'status' => $failCount === 0 ? 'success' : 'partial',
                'message' => $message,
                'results' => $results,
                'summary' => [
                    'total_requested' => count($deliveries),
                    'successful' => $successCount,
                    'failed' => $failCount,
                    'success_rate' => count($deliveries) > 0 ? round(($successCount / count($deliveries)) * 100, 1) : 0
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error generating bulk billing: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate bulk billing: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Download billing document
     */
    public function downloadBillingDocument($billingDocument)
    {
        try {
            Log::info("Download request for billing document: $billingDocument");
            
            // Validate billing document format
            if (!preg_match('/^INV\d+$/', $billingDocument)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid billing document format'
                ], 400);
            }
            
            // Di implementasi nyata, ambil file dari SAP atau storage
            // Contoh:
            // $response = Http::timeout(30)->get($this->billingApiUrl . '/api/download_billing/' . $billingDocument);
            // if ($response->successful()) {
            //     return response($response->body())
            //         ->header('Content-Type', 'application/pdf')
            //         ->header('Content-Disposition', "attachment; filename=\"billing_{$billingDocument}.pdf\"");
            // }
            
            // Untuk simulasi, buat PDF dummy
            $pdfContent = $this->generateDummyBillingPDF($billingDocument);
            
            return response($pdfContent)
                ->header('Content-Type', 'text/html') // Change to text/html for demo
                ->header('Content-Disposition', "attachment; filename=\"billing_{$billingDocument}.html\"");
            
        } catch (\Exception $e) {
            Log::error('Error downloading billing document: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download billing document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resend billing document
     */
    public function resendBillingDocument(Request $request)
    {
        try {
            $delivery = $request->input('delivery');
            $email = $request->input('email'); // Optional: specific email
            
            if (!$delivery) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Delivery order is required'
                ], 400);
            }
            
            Log::info("Resending billing document for delivery: $delivery" . ($email ? " to email: $email" : ""));
            
            // Di implementasi nyata, kirim ulang melalui email atau sistem lain
            // Contoh:
            // $response = Http::timeout(30)->post($this->billingApiUrl . '/api/resend_billing', [
            //     'delivery_order' => $delivery,
            //     'email' => $email
            // ]);
            
            // Simulasi pengiriman email
            $recipient = $email ?: 'customer@example.com'; // Default email jika tidak ada
            
            // Simulasi delay untuk pengiriman
            sleep(1);
            
            Log::info("Successfully resent billing document for delivery: $delivery to: $recipient");
            
            return response()->json([
                'status' => 'success',
                'message' => 'Billing document resent successfully',
                'data' => [
                    'delivery' => $delivery,
                    'sent_to' => $recipient,
                    'sent_at' => now()->toDateTimeString(),
                    'sent_by' => Auth::check() ? Auth::user()->name : 'System', // PERBAIKAN: Gunakan Auth::check()
                    'method' => 'email'
                ],
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error resending billing document: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to resend billing document: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Get API status for AJAX calls
     */
    public function getApiStatus()
    {
        try {
            $startTime = microtime(true);
            
            // Test connection ke billing service
            $response = Http::timeout(5)->get($this->billingApiUrl . '/health');
            
            $responseTime = microtime(true) - $startTime;
            
            if ($response->successful()) {
                $serviceData = $response->json();
                
                return response()->json([
                    'status' => 'ok',
                    'service_status' => 'connected',
                    'response_time' => round($responseTime * 1000, 2) . 'ms',
                    'service_info' => $serviceData,
                    'last_check' => now()->toDateTimeString()
                ]);
            } else {
                return response()->json([
                    'status' => 'warning',
                    'service_status' => 'degraded',
                    'response_time' => round($responseTime * 1000, 2) . 'ms',
                    'error_code' => $response->status(),
                    'last_check' => now()->toDateTimeString()
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'service_status' => 'disconnected',
                'error' => $e->getMessage(),
                'last_check' => now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Retry connection to billing service
     */
    public function retryConnection(Request $request)
    {
        try {
            Log::info('Retrying connection to billing service...');
            
            $maxRetries = 3;
            $retryDelay = 2; // seconds
            
            for ($i = 1; $i <= $maxRetries; $i++) {
                try {
                    Log::info("Connection attempt $i of $maxRetries");
                    
                    $response = Http::timeout(10)->get($this->billingApiUrl . '/health');
                    
                    if ($response->successful()) {
                        Log::info("Connection successful on attempt $i");
                        
                        return response()->json([
                            'status' => 'success',
                            'message' => "Connection restored on attempt $i",
                            'attempts' => $i,
                            'service_data' => $response->json(),
                            'timestamp' => now()->toDateTimeString()
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    Log::warning("Connection attempt $i failed: " . $e->getMessage());
                    
                    if ($i < $maxRetries) {
                        sleep($retryDelay);
                    }
                }
            }
            
            // Jika semua attempt gagal
            return response()->json([
                'status' => 'error',
                'message' => 'All connection attempts failed',
                'attempts' => $maxRetries,
                'timestamp' => now()->toDateTimeString()
            ], 503);
            
        } catch (\Exception $e) {
            Log::error('Error during connection retry: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Retry process failed: ' . $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ], 500);
        }
    }

    /**
     * Health check endpoint
     */
    public function healthCheck()
    {
        try {
            $response = Http::timeout(5)->get($this->billingApiUrl . '/health');
            
            return response()->json([
                'laravel_service' => 'OK',
                'billing_service' => $response->successful() ? 'OK' : 'FAILED',
                'billing_response_code' => $response->status(),
                'timestamp' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'laravel_service' => 'OK',
                'billing_service' => 'ERROR',
                'error' => $e->getMessage(),
                'timestamp' => now()->toDateTimeString()
            ]);
        }
    }

    /**
     * Get connection details
     */
    public function getConnectionDetails()
    {
        return response()->json([
            'billing_api_url' => $this->billingApiUrl,
            'sap_login_api_url' => $this->sapLoginApiUrl,
            'current_time' => now()->toDateTimeString(),
            'server_info' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_name' => $_SERVER['SERVER_NAME'] ?? 'unknown',
                'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown'
            ]
        ]);
    }

    /**
     * Debug connection untuk development
     */
    public function debugConnection()
    {
        if (app()->environment('production')) {
            abort(404);
        }
        
        $debug = [
            'timestamp' => now()->toDateTimeString(),
            'billing_api_url' => $this->billingApiUrl,
            'php_version' => PHP_VERSION,
            'curl_version' => curl_version(),
            'tests' => []
        ];
        
        // Test basic connectivity
        try {
            $response = Http::timeout(5)->get($this->billingApiUrl . '/health');
            $debug['tests']['health_check'] = [
                'status' => 'success',
                'response_code' => $response->status(),
                'response_body' => $response->json(),
                'headers' => $response->headers()
            ];
        } catch (\Exception $e) {
            $debug['tests']['health_check'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ];
        }
        
        // Test endpoints
        $endpoints = [
            '/api/billing_data_fast',
            '/api/test_sap_connection',
            '/api/billing_data'
        ];
        
        foreach ($endpoints as $endpoint) {
            try {
                $response = Http::timeout(10)->get($this->billingApiUrl . $endpoint);
                $debug['tests']['endpoint_' . str_replace(['/', '_'], ['_', ''], $endpoint)] = [
                    'status' => 'success',
                    'response_code' => $response->status(),
                    'response_size' => strlen($response->body()),
                    'content_type' => $response->header('Content-Type')
                ];
            } catch (\Exception $e) {
                $debug['tests']['endpoint_' . str_replace(['/', '_'], ['_', ''], $endpoint)] = [
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return response()->json($debug, 200, [], JSON_PRETTY_PRINT);
    }

    /**
     * Generate dummy PDF for demonstration
     */
    private function generateDummyBillingPDF($billingDocument)
    {
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Billing Document - $billingDocument</title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 40px;
                    line-height: 1.6;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px;
                    border-bottom: 2px solid #333;
                    padding-bottom: 20px;
                }
                .company-info {
                    text-align: left;
                    margin-bottom: 30px;
                }
                .billing-info { 
                    margin-bottom: 30px;
                    background-color: #f5f5f5;
                    padding: 15px;
                    border-radius: 5px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px; 
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 12px; 
                    text-align: left; 
                }
                th { 
                    background-color: #333;
                    color: white;
                    font-weight: bold;
                }
                .total-row { 
                    font-weight: bold; 
                    background-color: #e9ecef;
                    font-size: 1.1em;
                }
                .footer {
                    margin-top: 40px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    text-align: center;
                    color: #666;
                }
                .amount {
                    text-align: right;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>BILLING DOCUMENT</h1>
                <h2 style='color: #0066cc;'>$billingDocument</h2>
            </div>
            
            <div class='company-info'>
                <strong>PT. EXAMPLE COMPANY</strong><br>
                Jl. Example Street No. 123<br>
                Jakarta 12345, Indonesia<br>
                Phone: +62-21-1234567<br>
                Email: billing@example.com
            </div>
            
            <div class='billing-info'>
                <div style='display: flex; justify-content: space-between;'>
                    <div>
                        <strong>Bill To:</strong><br>
                        Customer Company Name<br>
                        Customer Address<br>
                        Customer City, Postal Code
                    </div>
                    <div style='text-align: right;'>
                        <strong>Date:</strong> " . date('d/m/Y') . "<br>
                        <strong>Due Date:</strong> " . date('d/m/Y', strtotime('+30 days')) . "<br>
                        <strong>Terms:</strong> Net 30 days<br>
                        <strong>Generated:</strong> " . date('d/m/Y H:i:s') . "
                    </div>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Product/Service Item 1</td>
                        <td style='text-align: center;'>1</td>
                        <td class='amount'>1,000.00</td>
                        <td class='amount'>1,000.00</td>
                    </tr>
                    <tr>
                        <td>Product/Service Item 2</td>
                        <td style='text-align: center;'>2</td>
                        <td class='amount'>500.00</td>
                        <td class='amount'>1,000.00</td>
                    </tr>
                    <tr>
                        <td>Shipping & Handling</td>
                        <td style='text-align: center;'>1</td>
                        <td class='amount'>250.00</td>
                        <td class='amount'>250.00</td>
                    </tr>
                    <tr class='total-row'>
                        <td colspan='3'><strong>Subtotal</strong></td>
                        <td class='amount'><strong>2,250.00</strong></td>
                    </tr>
                    <tr class='total-row'>
                        <td colspan='3'><strong>Tax (10%)</strong></td>
                        <td class='amount'><strong>225.00</strong></td>
                    </tr>
                    <tr class='total-row' style='background-color: #d4edda;'>
                        <td colspan='3'><strong>TOTAL AMOUNT</strong></td>
                        <td class='amount'><strong>2,475.00 USD</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <div class='footer'>
                <p><strong>Payment Terms:</strong> Net 30 days</p>
                <p><strong>Payment Method:</strong> Bank Transfer</p>
                <p><strong>Due Date:</strong> " . date('d/m/Y', strtotime('+30 days')) . "</p>
                <hr>
                <p>Thank you for your business!</p>
                <p><em>This is a computer-generated document. No signature required.</em></p>
            </div>
        </body>
        </html>";
        
        return $html;
    }

    /**
     * Get service configuration info - ENHANCED
     */
    public function getServiceInfo()
    {
        return response()->json([
            'services' => [
                'primary' => [
                    'name' => 'bill.py',
                    'url' => $this->billingApiUrl,
                    'port' => 50,
                    'endpoints' => [
                        '/health',
                        '/api/billing_data',
                        '/api/billing_data_fast',
                        '/api/billing_unbilled',
                        '/api/billing_summary',
                        '/api/test_sap_connection'
                    ]
                ],
                'secondary' => [
                    'name' => 'sap_login.py',
                    'url' => $this->sapLoginApiUrl,
                    'port' => 5020,
                    'endpoints' => [
                        '/api/health',
                        '/api/sap-login'
                    ]
                ]
            ],
            'timeout_settings' => [
                'health_check' => '10 seconds',
                'sap_connection_test' => '30 seconds',
                'billing_data' => '180 seconds',
                'billing_data_fast' => '60 seconds'
            ],
            'requirements' => [
                'python_packages' => ['flask', 'pyrfc', 'flask-cors'],
                'sap_connection' => [
                    'host' => '192.168.254.154',
                    'client' => '300',
                    'user' => 'auto_email'
                ]
            ],
            'troubleshooting' => [
                'check_services' => 'Visit /test-billing-connection',
                'restart_command' => 'python bill.py && python sap_login.py',
                'check_ports' => 'netstat -an | grep 50',
                'test_custom_timeout' => 'Visit /billing-custom?timeout=300&endpoint=api/billing_data_fast'
            ]
        ]);
    }
}