<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\UserTypeController;
use App\Http\Controllers\Auth\SapLoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\SettingDocumentController;
use App\Http\Controllers\DocumentUploadController;
use App\Http\Controllers\OptimizedSmartformController;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

// ===================================================================
// ROOT & BASIC ROUTES
// ===================================================================

Route::get('/test-route', function () {
    return 'Test route working';
});

Route::get('/', function () {
    return redirect()->route('login');
});

// ===================================================================
// AUTHENTICATION ROUTES
// ===================================================================

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ===================================================================
// SETTING DOCUMENT ROUTES
// ===================================================================

Route::prefix('setting-document')->group(function () {
    // Public routes
    Route::get('/login', [SettingDocumentController::class, 'showLoginForm'])->name('setting-document.login');
    Route::post('/login', [SettingDocumentController::class, 'login'])->name('setting-document.login.submit');
    Route::post('/logout', [SettingDocumentController::class, 'logout'])->name('setting-document.logout');

    // ✅ EXISTING: Buyers CRUD (untuk Document Settings tab)
    Route::post('/add-buyer', [SettingDocumentController::class, 'addBuyer'])
        ->name('setting-document.add-buyer');
    Route::post('/delete-buyer', [SettingDocumentController::class, 'deleteBuyer'])
        ->name('setting-document.delete-buyer');
    Route::get('/get-buyers-list', [SettingDocumentController::class, 'getBuyersList'])
        ->name('setting-document.get-buyers-list');

    // ✅ EXISTING: Documents CRUD (untuk Document Settings tab)
    Route::post('/add-document', [SettingDocumentController::class, 'addDocument'])
        ->name('setting-document.add-document');
    Route::post('/delete-document', [SettingDocumentController::class, 'deleteDocument'])
        ->name('setting-document.delete-document');
    
    Route::get('/get-settings/{customerName}', [SettingDocumentController::class, 'getDocumentSettings'])
         ->name('setting-document.get-settings');
    
    Route::get('/debug-settings/{customerName}', [SettingDocumentController::class, 'debugSettings'])
         ->name('setting-document.debug-settings');
    
    Route::get('/test-connection', function() {
        try {
            $totalCustomers = \App\Models\DocumentSetting::count();
            $sampleCustomers = \App\Models\DocumentSetting::limit(5)->pluck('customer_name')->toArray();
            
            return response()->json([
                'success' => true,
                'message' => 'Setting document system is working',
                'database_connection' => 'OK',
                'total_customers' => $totalCustomers,
                'sample_customers' => $sampleCustomers,
                'timestamp' => now()->toDateTimeString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    })->name('setting-document.test-connection');
    
    // Protected routes
    Route::middleware(['setting.auth'])->group(function () {
        // ✅ EXISTING ROUTES - Document Settings Management
        Route::get('/dashboard', [SettingDocumentController::class, 'dashboard'])->name('setting-document.dashboard');
        Route::get('/', [SettingDocumentController::class, 'dashboard'])->name('setting.document');
        Route::post('/update-settings', [SettingDocumentController::class, 'updateDocumentSettings'])->name('setting-document.update-settings');
        Route::get('/team-documents/{team}', [SettingDocumentController::class, 'getTeamDocuments'])
             ->where('team', 'Finance|Exim|Logistic')
             ->name('setting-document.team-documents');
        Route::post('/bulk-update', [SettingDocumentController::class, 'bulkUpdateSettings'])->name('setting-document.bulk-update');
        Route::get('/export/{format?}', [SettingDocumentController::class, 'exportSettings'])
             ->where('format', 'json|csv')
             ->name('setting-document.export');
        Route::post('/import', [SettingDocumentController::class, 'importSettings'])->name('setting-document.import');
        Route::get('/statistics', [SettingDocumentController::class, 'getStatistics'])->name('setting-document.statistics');
        Route::get('/usage-analytics', [SettingDocumentController::class, 'getUsageAnalytics'])->name('setting-document.analytics');
        Route::get('/suggest-documents/{customerName}', [SettingDocumentController::class, 'suggestDocuments'])->name('setting-document.suggest');
        Route::post('/auto-configure', [SettingDocumentController::class, 'autoConfigureCustomer'])->name('setting-document.auto-configure');
        
        // ✅ BUYER EMAIL CRUD - Buyer Email Management Tab
        Route::prefix('buyer-email')->group(function () {
            // Read operations
            Route::get('/', [SettingDocumentController::class, 'getBuyerEmails'])->name('buyer-email.index');
            Route::get('/buyer-codes', [SettingDocumentController::class, 'getBuyerCodes'])->name('buyer-email.buyer-codes');
            Route::get('/by-buyer/{buyerCode}', [SettingDocumentController::class, 'getEmailsByBuyerCode'])->name('buyer-email.by-buyer');
            
            // Create operation
            Route::post('/', [SettingDocumentController::class, 'storeBuyerEmail'])->name('buyer-email.store');
            
            // Update operation
            Route::put('/{id}', [SettingDocumentController::class, 'updateBuyerEmail'])->name('buyer-email.update');
            
            // Delete operations
            Route::delete('/{id}', [SettingDocumentController::class, 'deleteBuyerEmail'])->name('buyer-email.delete');
            
            // ✅ NEW: Delete buyer dengan semua emailnya (Updated feature)
            Route::delete('/buyer/{buyerCode}', [SettingDocumentController::class, 'deleteBuyerWithEmails'])->name('buyer-email.delete-buyer');
        });
    });
});

// ===================================================================
// PUBLIC API ROUTES
// ===================================================================

Route::prefix('api/billing')->group(function () {
    Route::get('/test-connection', [BillingController::class, 'testConnection'])->name('billing.test');
    Route::get('/quick-status', [BillingController::class, 'quickStatus'])->name('billing.quick-status');
    Route::get('/service-info', [BillingController::class, 'getServiceInfo'])->name('billing.service-info');
    Route::get('/health-check', [BillingController::class, 'healthCheck'])->name('billing.health-check');
    Route::get('/connection-details', [BillingController::class, 'getConnectionDetails'])->name('billing.connection-details');
});

Route::prefix('api/dashboard')->group(function () {
    Route::get('/test-billing-connection', [DashboardController::class, 'testBillingConnection'])->name('dashboard.test-billing');
    Route::get('/refresh-billing-data', [DashboardController::class, 'refreshBillingData'])->name('dashboard.refresh-billing');
    Route::get('/quick-status', [DashboardController::class, 'quickStatus'])->name('dashboard.quick-status');
    Route::get('/connection-diagnostics', [DashboardController::class, 'connectionDiagnostics'])->name('dashboard.diagnostics');
});

Route::prefix('api/integration')->group(function () {
    Route::get('/check-allowed-documents/{delivery}/{customer}', [DocumentUploadController::class, 'checkAllowedDocuments']);
    Route::post('/notify-finance-upload', [DocumentUploadController::class, 'notifyFinanceDashboard']);
    Route::get('/finance-new-uploads', [DashboardController::class, 'getNewUploadsForFinance']);
    Route::post('/dashboard-sync', function() {
        return response()->json(['success' => true, 'message' => 'Sync OK']);
    })->name('api.integration.sync');
});

Route::get('/test-billing-connection', [BillingController::class, 'testConnection'])->name('billing.test.legacy');
Route::get('/billing-quick-status', [BillingController::class, 'quickStatus'])->name('billing.quick-status.legacy');
Route::get('/billing-service-info', [BillingController::class, 'getServiceInfo'])->name('billing.service-info.legacy');

// ===================================================================
// TEST SMARTFORM FILES ROUTE (Public for debugging)
// ===================================================================

Route::get('/test-smartform-files', function() {
    $smartformFolder = 'Z:\\sd';
    
    if (!is_dir($smartformFolder)) {
        return response()->json([
            'success' => false,
            'error' => 'Z:\\sd folder not accessible',
            'folder' => $smartformFolder
        ]);
    }
    
    $allFiles = scandir($smartformFolder);
    $files = [];
    
    foreach ($allFiles as $file) {
        if ($file !== '.' && $file !== '..' && is_file($smartformFolder . '\\' . $file)) {
            $files[] = [
                'filename' => $file,
                'size' => filesize($smartformFolder . '\\' . $file),
                'modified' => date('Y-m-d H:i:s', filemtime($smartformFolder . '\\' . $file))
            ];
        }
    }
    
    return response()->json([
        'success' => true,
        'folder' => $smartformFolder,
        'accessible' => true,
        'total_files' => count($files),
        'files' => $files
    ]);
})->name('test.smartform.files');

// ===================================================================
// AUTHENTICATED ROUTES
// ===================================================================

Route::middleware(['auth'])->group(function () {
    
    Route::get('/select-user-type', [UserTypeController::class, 'index'])->name('user-type.select');
    Route::post('/select-user-type', [UserTypeController::class, 'select'])->name('user-type.store');
    
    Route::get('/sap-login', [SapLoginController::class, 'showLoginForm'])->name('sap.login.form');
    Route::post('/sap-login', [SapLoginController::class, 'login'])->name('sap.login');
    
    Route::get('/dashboard/daily-sync-info', [DashboardController::class, 'getDailySyncInfo'])->name('dashboard.daily-sync-info');
    Route::post('/sap/sync-now', [DashboardController::class, 'syncSapNow'])->name('sap.sync-now');
    Route::get('/sap/storage-info', [DashboardController::class, 'getStorageInfo'])->name('sap.storage-info');
    
    // ===============================================================
    // SAP AUTHENTICATED ROUTES
    // ===============================================================
    
    Route::middleware(['sap.auth'])->group(function () {
        
        // ==========================================================
        // SMARTFORM AUTO-UPLOAD ROUTES - Z:\sd Integration
        // ==========================================================
        
        Route::prefix('smartform')->group(function () {
            // Batch upload untuk buyer tertentu
            Route::post('/batch-upload-for-buyer', [OptimizedSmartformController::class, 'batchUploadForBuyer'])
                ->name('smartform.batch-upload-for-buyer');
            
            // Real-time monitoring dan auto-upload
            Route::post('/monitor-auto-upload', [OptimizedSmartformController::class, 'monitorAndAutoUpload'])
                ->name('smartform.monitor-auto-upload');
            
            // Manual upload untuk delivery order tertentu
            Route::post('/manual-upload', [OptimizedSmartformController::class, 'manualUploadForDelivery'])
                ->name('smartform.manual-upload');
            
            // Batch process semua deliveries
            Route::post('/batch-process', [OptimizedSmartformController::class, 'batchProcessAllFiles'])
                ->name('smartform.batch-process');
            
            // Health check untuk monitoring system
            Route::get('/health-check', [OptimizedSmartformController::class, 'healthCheck'])
                ->name('smartform.health-check');
        });
        
        // ==========================================================
        // MAIN DASHBOARD ROUTES
        // ==========================================================
        
        Route::prefix('dashboard')->group(function () {
            Route::get('/admin-finance', [DashboardController::class, 'adminFinance'])->name('dashboard.admin-finance');
            Route::get('/exim', [DashboardController::class, 'exim'])->name('dashboard.exim');
             Route::get('/logistic', [DashboardController::class, 'logistic'])->name('dashboard.logistic');
            Route::get('/admin-finance/outstanding', [DashboardController::class, 'adminFinanceOutstanding'])->name('dashboard.admin-finance.outstanding');
            Route::get('/admin-finance/onprogress', [DashboardController::class, 'adminFinanceOnProgress'])->name('dashboard.admin-finance.onprogress');
            Route::get('/admin-finance/completed', [DashboardController::class, 'adminFinanceCompleted'])->name('dashboard.admin-finance.completed');
            Route::get('/admin-finance/sent', [DashboardController::class, 'adminFinanceSent'])->name('dashboard.admin-finance.sent');
            Route::get('/admin-finance/refresh', [DashboardController::class, 'refreshAdminFinance'])->name('dashboard.admin-finance.refresh');
            Route::get('/exim/refresh', [DashboardController::class, 'refreshExim'])->name('dashboard.exim.refresh');
            Route::get('/logistic/refresh', [DashboardController::class, 'refreshLogistic'])->name('dashboard.logistic.refresh');
            Route::get('/test-connection', [DashboardController::class, 'testBillingConnection'])->name('dashboard.test-connection');
            Route::post('/force-refresh', [DashboardController::class, 'forceRefreshData'])->name('dashboard.force-refresh');
        });

        // ==========================================================
        // SAP DATA STORAGE ROUTES
        // ==========================================================
        
        Route::prefix('sap')->group(function () {
            Route::post('/sync-now', [DashboardController::class, 'syncSapNow'])->name('sap.sync-now');
            Route::get('/storage-info', [DashboardController::class, 'getStorageInfo'])->name('sap.storage-info');
        });
        
        // ==========================================================
        // DOCUMENT UPLOAD ROUTES
        // ==========================================================
        
        Route::prefix('documents')->group(function () {
            Route::get('/preview/{uploadId}', [DocumentUploadController::class, 'preview'])
                 ->where('uploadId', '[0-9]+')
                 ->name('documents.preview');
            Route::post('/upload', [DocumentUploadController::class, 'upload'])->name('documents.upload');
            Route::post('/auto-upload-smartform', [DocumentUploadController::class, 'autoUploadFromSmartform'])->name('documents.auto-upload-smartform');
            Route::get('/uploads/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getUploads'])->name('documents.get-uploads');
            Route::get('/download/{uploadId}', [DocumentUploadController::class, 'download'])->name('documents.download');
            Route::delete('/delete/{uploadId}', [DocumentUploadController::class, 'delete'])->name('documents.delete');
            Route::get('/view/{uploadId}', [DocumentUploadController::class, 'view'])->name('documents.view');
            Route::get('/stream/{uploadId}', [DocumentUploadController::class, 'streamContent'])->name('documents.stream');
            Route::get('/info/{uploadId}', [DocumentUploadController::class, 'getFileInfo'])->name('documents.info');
            Route::get('/uploads/{deliveryOrder}/{customerName}/team/{team}', [DocumentUploadController::class, 'getUploadsByTeam'])
                 ->where('team', 'Finance|Exim|Logistic')
                 ->name('documents.get-uploads-by-team');
            Route::get('/allowed-documents/{customerName}/{team?}', [DocumentUploadController::class, 'getAllowedDocuments'])
                 ->where('team', 'Finance|Exim|Logistic')
                 ->name('documents.get-allowed');
            
            // ✅ NEW: Route untuk get allowed documents dengan filter team (khusus Logistic)
            Route::get('/allowed-documents-by-team/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getAllowedDocumentsByTeam'])
                 ->name('documents.allowed-documents-by-team');
            
            Route::post('/bulk-upload', [DocumentUploadController::class, 'bulkUpload'])->name('documents.bulk-upload');
            Route::delete('/bulk-delete', [DocumentUploadController::class, 'bulkDelete'])->name('documents.bulk-delete');
            Route::post('/validate-document', [DocumentUploadController::class, 'validateDocument'])->name('documents.validate-document');
            Route::post('/re-validate/{uploadId}', [DocumentUploadController::class, 'reValidateDocument'])->name('documents.re-validate');
            Route::get('/exim-documents/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getEximDocumentsForFinance'])->name('documents.exim-for-finance');
            Route::get('/status-progress/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getStatusProgress'])->name('documents.status-progress');
            Route::post('/mark-as-sent', [DocumentUploadController::class, 'markAsSent'])->name('documents.mark-as-sent');
            Route::post('/send-to-multiple-buyers', [DashboardController::class, 'sendToBuyerMultipleEmails'])->name('documents.send-to-multiple-buyers');
        });
        
        // ===================================================================
        // LOGISTIC ROUTES
        // ===================================================================
        Route::middleware(['auth', 'sap.auth'])->prefix('logistic')->group(function () {
            Route::get('/', [LogisticController::class, 'index'])->name('logistic.index');
            Route::post('/upload', [LogisticController::class, 'uploadDocument'])->name('logistic.upload');
            Route::get('/documents', [LogisticController::class, 'getDocuments'])->name('logistic.documents');
            Route::get('/documents/{id}/download', [LogisticController::class, 'downloadDocument'])->name('logistic.download');
            Route::get('/documents/{id}/preview', [LogisticController::class, 'previewDocument'])->name('logistic.preview');
            Route::delete('/documents/{id}', [LogisticController::class, 'deleteDocument'])->name('logistic.delete');
            Route::get('/search', [LogisticController::class, 'searchDocuments'])->name('logistic.search');
            Route::get('/statistics', [LogisticController::class, 'getStatistics'])->name('logistic.statistics');
        });

        // ==========================================================
        // BUYER EMAIL ROUTES
        // ==========================================================
        
        Route::prefix('buyer-emails')->group(function () {
            Route::get('/get/{customerName}', [DashboardController::class, 'getBuyerEmails'])->name('buyer-emails.get');
            Route::get('/validate/{email}', function($email) {
                return response()->json([
                    'valid' => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                    'email' => $email
                ]);
            })->name('buyer-emails.validate');
            Route::get('/check/{customerName}', function($customerName) {
                try {
                    $count = DB::table('buyer_emails')->where('buyer_name', $customerName)->count();
                    return response()->json([
                        'success' => true,
                        'customer_name' => $customerName,
                        'email_count' => $count,
                        'has_emails' => $count > 0
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
            })->name('buyer-emails.check');
        });
        
        // ==========================================================
        // BILLING ROUTES
        // ==========================================================
        
        Route::prefix('billing')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('billing.index');
            Route::post('/submit', [BillingController::class, 'submitBilling'])->name('billing.submit');
            Route::post('/detail', [BillingController::class, 'detailBilling'])->name('billing.detail');
            Route::post('/submit-with-retry', [BillingController::class, 'submitBillingWithRetry'])->name('billing.submit-retry');
            Route::post('/detail-with-fallback', [BillingController::class, 'detailBillingWithFallback'])->name('billing.detail-fallback');
            Route::get('/refresh-data', [BillingController::class, 'refreshBillingData'])->name('billing.refresh-data');
            Route::get('/data-status', [BillingController::class, 'getBillingDataStatus'])->name('billing.data-status');
        });
        
        // ==========================================================
        // API ROUTES FOR AJAX
        // ==========================================================
        
        Route::prefix('api')->group(function () {
            Route::get('/dashboard/data/{type}', [DashboardController::class, 'getDashboardData'])->name('api.dashboard.data');
            Route::post('/dashboard/refresh/{type}', [DashboardController::class, 'refreshDashboardData'])->name('api.dashboard.refresh');
            Route::get('/buyer-notifications', [DashboardController::class, 'getBuyerNotifications'])->name('api.buyer-notifications');

            Route::get('/dashboard/data/onprogress', function() {
                return response()->json([
                    'status' => 'success',
                    'message' => 'On progress data retrieved',
                    'timestamp' => now()->toDateTimeString()
                ]);
            })->name('api.dashboard.data.onprogress');
            Route::post('/dashboard/update-status', [DashboardController::class, 'updateItemStatus'])->name('api.dashboard.update-status');
            Route::get('/dashboard/export/{type}', [DashboardController::class, 'exportData'])->name('api.dashboard.export');
            
            Route::prefix('documents')->group(function () {
                Route::get('/by-team/{team}', [DocumentUploadController::class, 'getDocumentsByTeam'])
                     ->where('team', 'Finance|Exim|Logistic')
                     ->name('api.documents.by-team');
                Route::post('/upload-with-validation', [DocumentUploadController::class, 'uploadWithTeamValidation'])->name('api.documents.upload-validated');
                Route::get('/progress/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getStatusProgress'])->name('api.documents.progress');
                Route::post('/sync-teams', [DocumentUploadController::class, 'syncBetweenTeams'])->name('api.documents.sync-teams');
                Route::get('/check-updates/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'checkDocumentUpdates'])->name('documents.check-updates');
                Route::post('/batch-load', [DashboardController::class, 'getBatchDocuments'])->name('api.documents.batch-load');
                Route::post('/batch-counts', [DashboardController::class, 'getDocumentCounts'])->name('api.documents.batch-counts');
            });
            
            Route::prefix('settings')->group(function () {
                Route::get('/customers-by-team/{team}', [SettingDocumentController::class, 'getCustomersByTeam'])->name('api.settings.customers-by-team');
                Route::get('/template/{template}', [SettingDocumentController::class, 'getDocumentTemplate'])
                     ->where('template', 'furniture|logistics|manufacturing|default')
                     ->name('api.settings.template');
                Route::post('/apply-template', [SettingDocumentController::class, 'applyTemplate'])->name('api.settings.apply-template');
                Route::get('/validation-rules/{team}', [SettingDocumentController::class, 'getValidationRules'])->name('api.settings.validation-rules');
            });
            
            Route::prefix('teams')->group(function () {
                Route::get('/workload', [DocumentUploadController::class, 'getTeamWorkload'])->name('api.teams.workload');
                Route::get('/performance', [DocumentUploadController::class, 'getTeamPerformance'])->name('api.teams.performance');
                Route::post('/reassign-documents', [DocumentUploadController::class, 'reassignDocuments'])->name('api.teams.reassign');
            });
            
            Route::prefix('sync')->group(function () {
                Route::post('/trigger/{type}', [DocumentUploadController::class, 'triggerSync'])
                     ->where('type', 'upload|setting|status|all')
                     ->name('api.sync.trigger');
                Route::get('/status', [DocumentUploadController::class, 'getSyncStatus'])->name('api.sync.status');
                Route::post('/force-refresh', [DocumentUploadController::class, 'forceRefresh'])->name('api.sync.force-refresh');
            });
            
            Route::get('/billing/status', [BillingController::class, 'getApiStatus'])->name('api.billing.status');
            Route::post('/billing/retry-connection', [BillingController::class, 'retryConnection'])->name('api.billing.retry');
            Route::post('/billing/generate', [BillingController::class, 'generateSingleBilling'])->name('api.billing.generate');
            Route::post('/billing/generate-bulk', [BillingController::class, 'generateBulkBilling'])->name('api.billing.generate-bulk');
            Route::get('/billing/download/{billingDocument}', [BillingController::class, 'downloadBillingDocument'])->name('api.billing.download');
            Route::post('/billing/resend', [BillingController::class, 'resendBillingDocument'])->name('api.billing.resend');
            
            Route::get('/status/progress/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getStatusProgress'])->name('api.status.progress');
            Route::post('/status/mark-as-sent', [DocumentUploadController::class, 'markAsSent'])->name('api.status.mark-as-sent');
            Route::get('/status/counts', function() {
                return response()->json(\App\Models\BillingStatus::getStatusCounts());
            })->name('api.status.counts');
            
            Route::get('/buyer-emails/{customerName}', [DashboardController::class, 'getBuyerEmails'])->name('api.buyer-emails.get');
            Route::post('/buyer-emails/send-multiple', [DashboardController::class, 'sendToBuyerMultipleEmails'])
                ->name('api.buyer-emails.send-multiple');
            Route::post('/buyer-emails/send-bulk', [DashboardController::class, 'sendToBuyerMultipleEmails'])->name('api.buyer-emails.send-bulk');
            Route::get('/buyer-emails/list-all', function() {
                try {
                    $allEmails = DB::table('buyer_emails')
                        ->select('buyer_name', 'email', 'contact_name', 'email_type', 'is_primary')
                        ->orderBy('buyer_name')
                        ->orderBy('is_primary', 'desc')
                        ->get()
                        ->groupBy('buyer_name');
                    
                    return response()->json([
                        'success' => true,
                        'emails_by_buyer' => $allEmails,
                        'total_buyers' => $allEmails->count(),
                        'total_emails' => $allEmails->flatten(1)->count()
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
            })->name('api.buyer-emails.list-all');

            Route::get('/sap/storage-stats', [DashboardController::class, 'getStorageInfo'])->name('api.sap.storage-stats');
            Route::post('/sap/sync-manual', [DashboardController::class, 'syncSapNow'])->name('api.sap.sync-manual');
            Route::get('/sap/cache-status', function() {
                try {
                    $storageStats = \App\Models\SapDataStorage::getStorageStats();
                    $cacheAge = \App\Models\SapDataStorage::getCacheAgeMinutes();
                    
                    return response()->json([
                        'success' => true,
                        'cache_age_minutes' => $cacheAge,
                        'storage_stats' => $storageStats,
                        'cache_status' => $cacheAge <= 30 ? 'fresh' : ($cacheAge <= 120 ? 'moderate' : 'stale'),
                        'timestamp' => now()->toDateTimeString()
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
                }
            })->name('api.sap.cache-status');
            
            Route::get('/system/health', function () {
                return response()->json([
                    'status' => 'ok',
                    'timestamp' => now(),
                    'services' => [
                        'laravel' => 'running',
                        'database' => 'connected',
                        'sap_billing' => 'checking...',
                        'buyer_email_system' => 'active',
                        'sap_storage' => 'active'
                    ]
                ]);
            })->name('api.system.health');
        });

        Route::prefix('integration')->group(function () {
            Route::get('/status/{customerName}', [SettingDocumentController::class, 'getIntegrationStatus'])->name('integration.status');
            Route::post('/sync-settings', [SettingDocumentController::class, 'syncSettingsToExim'])->name('integration.sync-settings');
            Route::get('/cross-team-documents/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getCrossTeamDocuments'])->name('integration.cross-team-documents');
        });
        
        Route::prefix('api/integration')->group(function () {
            Route::get('/dashboard-sync', function() {
                return response()->json([
                    'setting_dashboard' => 'active',
                    'exim_dashboard' => 'active', 
                    'finance_dashboard' => 'active',
                    'integration_status' => 'connected',
                    'timestamp' => now()->toDateTimeString()
                ]);
            })->name('api.integration.dashboard-sync');
        });

        // ==========================================================
        // EMAIL TEST ROUTES - TAMBAHAN BARU
        // ==========================================================
        
        Route::get('/test-simple-email', function() {
            try {
                Log::info('=== TESTING SIMPLE EMAIL SENDING ===');
                
                Mail::raw('This is a test email from KMI Finance System at ' . now()->toDateTimeString(), function($message) {
                    $message->to('ar.kmi@pawindo.com')
                            ->subject('Test Email - ' . now()->format('H:i:s'))
                            ->from('ar.kmi@pawindo.com', 'KMI Finance Test');
                });
                
                Log::info('Simple email sent successfully');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully! Check ar.kmi@pawindo.com inbox.',
                    'mail_config' => [
                        'driver' => config('mail.default'),
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port'),
                        'encryption' => config('mail.mailers.smtp.encryption'),
                        'username' => config('mail.mailers.smtp.username'),
                        'from_address' => config('mail.from.address'),
                        'from_name' => config('mail.from.name')
                    ],
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                Log::error('Simple email test failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email failed: ' . $e->getMessage(),
                    'error_type' => get_class($e),
                    'error_file' => basename($e->getFile()),
                    'error_line' => $e->getLine(),
                    'mail_config' => [
                        'driver' => config('mail.default'),
                        'host' => config('mail.mailers.smtp.host'),
                        'port' => config('mail.mailers.smtp.port')
                    ]
                ], 500);
            }
        })->name('test.simple.email');
        
        Route::get('/test-email-with-attachment', function() {
            try {
                Log::info('=== TESTING EMAIL WITH ATTACHMENT ===');
                
                $testFile = storage_path('app/public/test.txt');
                
                if (!file_exists($testFile)) {
                    file_put_contents($testFile, 'This is a test attachment from KMI Finance System');
                }
                
                Mail::raw('Email with attachment test at ' . now()->toDateTimeString(), function($message) use ($testFile) {
                    $message->to('ar.kmi@pawindo.com')
                            ->subject('Test Email with Attachment - ' . now()->format('H:i:s'))
                            ->from('ar.kmi@pawindo.com', 'KMI Finance Test')
                            ->attach($testFile, [
                                'as' => 'test-attachment.txt',
                                'mime' => 'text/plain'
                            ]);
                });
                
                Log::info('Email with attachment sent successfully');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email with attachment sent! Check ar@pawindo.com inbox.',
                    'attachment_info' => [
                        'file' => 'test-attachment.txt',
                        'size' => filesize($testFile) . ' bytes',
                        'exists' => file_exists($testFile)
                    ],
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                Log::error('Email with attachment test failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Email with attachment failed: ' . $e->getMessage(),
                    'error_type' => get_class($e)
                ], 500);
            }
        })->name('test.email.attachment');
        
        Route::get('/test-email-template', function() {
            try {
                Log::info('=== TESTING EMAIL TEMPLATE RENDERING ===');
                
                $testData = [
                    'delivery_order' => 'TEST123456',
                    'customer_name' => 'TEST CUSTOMER',
                    'billing_document' => 'BILL789',
                    'booking_number' => 'BOOK456',
                    'is_billing_email' => true,
                    'subject' => 'Test Subject',
                    'email_message' => 'This is a test email message',
                    'primary_email' => 'test@example.com',
                    'primary_contact_name' => 'Test Contact',
                    'cc_emails' => [
                        ['email' => 'cc1@example.com', 'contact_name' => 'CC Contact 1'],
                        ['email' => 'cc2@example.com', 'contact_name' => 'CC Contact 2']
                    ],
                    'total_recipients' => 3,
                    'sent_at' => now()->toDateTimeString(),
                    'sent_by' => 'Test User'
                ];
                
                $html = view('emails.documents-to-buyer', $testData)->render();
                
                Log::info('Email template rendered successfully', [
                    'html_length' => strlen($html)
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Email template rendered successfully',
                    'html_preview' => substr($html, 0, 500) . '...',
                    'html_length' => strlen($html),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                Log::error('Email template rendering failed', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Template rendering failed: ' . $e->getMessage(),
                    'error_type' => get_class($e),
                    'error_file' => basename($e->getFile()),
                    'error_line' => $e->getLine()
                ], 500);
            }
        })->name('test.email.template');
        
        Route::get('/test-buyer-emails/{customerName}', function($customerName) {
            try {
                Log::info("=== TESTING BUYER EMAIL LOOKUP ===", [
                    'customer_name' => $customerName
                ]);
                
                $emails = DB::table('buyer_emails')
                    ->where('buyer_name', $customerName)
                    ->orderBy('is_primary', 'desc')
                    ->orderBy('email_type')
                    ->get();
                
                if ($emails->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No emails found for customer: ' . $customerName,
                        'total_customers_in_db' => DB::table('buyer_emails')->distinct('buyer_name')->count(),
                        'sample_customers' => DB::table('buyer_emails')->distinct()->pluck('buyer_name')->take(10)
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'customer_name' => $customerName,
                    'emails_found' => $emails->count(),
                    'emails' => $emails->map(function($email) {
                        return [
                            'id' => $email->id,
                            'email' => $email->email,
                            'contact_name' => $email->contact_name,
                            'email_type' => $email->email_type,
                            'is_primary' => $email->is_primary
                        ];
                    }),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ], 500);
            }
        })->name('test.buyer.emails');
        
        Route::get('/test-smtp-connection', function() {
            try {
                Log::info('=== TESTING SMTP CONNECTION ===');
                
                $config = [
                    'driver' => config('mail.default'),
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'username' => config('mail.mailers.smtp.username'),
                    'from_address' => config('mail.from.address')
                ];
                
                $host = $config['host'];
                $port = $config['port'];
                
                $socket = @fsockopen($host, $port, $errno, $errstr, 10);
                
                if (!$socket) {
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot connect to SMTP server: $errstr ($errno)",
                        'smtp_config' => $config,
                        'error_code' => $errno,
                        'error_message' => $errstr
                    ], 500);
                }
                
                fclose($socket);
                
                return response()->json([
                    'success' => true,
                    'message' => 'SMTP connection successful',
                    'smtp_config' => $config,
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'SMTP test failed: ' . $e->getMessage()
                ], 500);
            }
        })->name('test.smtp.connection');
        
        Route::get('/test-mail-queue', function() {
            try {
                Log::info('=== TESTING MAIL QUEUE ===');
                
                $queueConnection = config('queue.default');
                $queueDriver = config("queue.connections.{$queueConnection}.driver");
                
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                return response()->json([
                    'success' => true,
                    'queue_connection' => $queueConnection,
                    'queue_driver' => $queueDriver,
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Queue test failed: ' . $e->getMessage()
                ], 500);
            }
        })->name('test.mail.queue');
        
        Route::get('/test-document-files/{deliveryOrder}/{customerName}', function($deliveryOrder, $customerName) {
            try {
                Log::info("=== TESTING DOCUMENT FILES ===", [
                    'delivery_order' => $deliveryOrder,
                    'customer_name' => $customerName
                ]);
                
                $documents = \App\Models\DocumentUpload::where('delivery_order', $deliveryOrder)
                    ->where('customer_name', $customerName)
                    ->get();
                
                if ($documents->isEmpty()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No documents found',
                        'delivery_order' => $deliveryOrder,
                        'customer_name' => $customerName
                    ]);
                }
                
                $fileChecks = [];
                foreach ($documents as $doc) {
                    $filePath = storage_path('app/public/' . $doc->file_path);
                    $fileChecks[] = [
                        'id' => $doc->id,
                        'file_name' => $doc->file_name,
                        'document_type' => $doc->document_type,
                        'file_path' => $doc->file_path,
                        'full_path' => $filePath,
                        'exists' => file_exists($filePath),
                        'size' => file_exists($filePath) ? filesize($filePath) : 0,
                        'readable' => file_exists($filePath) && is_readable($filePath)
                    ];
                }
                
                return response()->json([
                    'success' => true,
                    'total_documents' => $documents->count(),
                    'files_check' => $fileChecks,
                    'files_exist' => collect($fileChecks)->where('exists', true)->count(),
                    'files_missing' => collect($fileChecks)->where('exists', false)->count(),
                    'timestamp' => now()->toDateTimeString()
                ]);
                
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document test failed: ' . $e->getMessage()
                ], 500);
            }
        })->name('test.document.files');
        
        Route::get('/debug-mail-config', function() {
            return response()->json([
                'mail_default' => config('mail.default'),
                'mail_mailers' => config('mail.mailers'),
                'mail_from' => config('mail.from'),
                'env_mail_mailer' => env('MAIL_MAILER'),
                'env_mail_host' => env('MAIL_HOST'),
                'env_mail_port' => env('MAIL_PORT'),
                'env_mail_username' => env('MAIL_USERNAME'),
                'env_mail_encryption' => env('MAIL_ENCRYPTION'),
                'env_mail_from_address' => env('MAIL_FROM_ADDRESS'),
                'timestamp' => now()->toDateTimeString()
            ]);
        })->name('debug.mail.config');
        
    }); // End SAP Auth middleware
    
}); // End Auth middleware

// ===================================================================
// ADDITIONAL HELPER ROUTES
// ===================================================================

Route::post('/smartform/force-process-all', function() {
    try {
        Cache::forget('smartform_processed_files');
        
        $controller = app(\App\Http\Controllers\OptimizedSmartformController::class);
        return $controller->batchProcessAllFiles(request());
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Force process failed: ' . $e->getMessage()
        ], 500);
    }
})->middleware(['auth', 'sap.auth']);

Route::get('/check-uploaded-from', function() {
    try {
        $columns = DB::select("SHOW COLUMNS FROM document_uploads WHERE Field = 'uploaded_from'");
        
        if (empty($columns)) {
            return response()->json(['error' => 'Column uploaded_from not found']);
        }
        
        $stats = DB::select("
            SELECT uploaded_from, COUNT(*) as count 
            FROM document_uploads 
            GROUP BY uploaded_from
        ");
        
        $samples = DB::select("
            SELECT id, document_type, uploaded_by, uploaded_from, team 
            FROM document_uploads 
            LIMIT 10
        ");
        
        return response()->json([
            'success' => true,
            'column_info' => $columns[0],
            'distribution' => $stats,
            'sample_data' => $samples
        ]);
        
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
})->middleware(['auth', 'sap.auth']);

// ===================================================================
// SYSTEM HEALTH CHECK ROUTE (Public)
// ===================================================================

Route::get('/health', function() {
    try {
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'error: ' . $e->getMessage();
    }
    
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toDateTimeString(),
        'laravel_version' => app()->version(),
        'php_version' => PHP_VERSION,
        'database' => $dbStatus,
        'storage_writable' => is_writable(storage_path()),
        'cache_writable' => is_writable(storage_path('framework/cache'))
    ]);
});

// ===================================================================
// FALLBACK ROUTE
// ===================================================================

Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

Route::get('/test-notifications/{location}', function($location) {
    $controller = app(\App\Http\Controllers\DashboardController::class);
    
    // Simulate getting data
    $billingData = $controller->getBillingDataWithDatabaseCaching();
    
    if (!$billingData) {
        return response()->json(['error' => 'No billing data']);
    }
    
    $groupedData = $controller->groupBillingDataWithStatusFixed($billingData);
    
    // Add location
    foreach ($groupedData as &$group) {
        $group['location'] = $controller->getLocationFromDeliveryOrder($group['delivery']);
    }
    
    // Filter by location
    $filtered = array_filter($groupedData, function($g) use ($location) {
        return strtolower($g['location']) === strtolower($location);
    });
    
    // Get notifications
    $notifications = $controller->getBuyerNotificationCounts($filtered);
    
    return response()->json([
        'location' => $location,
        'total_deliveries' => count($filtered),
        'notifications' => $notifications,
        'sample_data' => array_slice($filtered, 0, 3)
    ]);
})->middleware(['auth', 'sap.auth']);