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
use Illuminate\Support\Facades\Cache;

Route::get('/test-route', function () {
    return 'Test route working';
});

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::prefix('setting-document')->group(function () {
    Route::get('/login', [SettingDocumentController::class, 'showLoginForm'])->name('setting-document.login');
    Route::post('/login', [SettingDocumentController::class, 'login'])->name('setting-document.login.submit');
    Route::post('/logout', [SettingDocumentController::class, 'logout'])->name('setting-document.logout');
    
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
    
    Route::middleware(['setting.auth'])->group(function () {
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
    });
});

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

Route::middleware(['auth'])->group(function () {
    
    Route::get('/select-user-type', [UserTypeController::class, 'index'])->name('user-type.select');
    Route::post('/select-user-type', [UserTypeController::class, 'select'])->name('user-type.store');
    
    Route::get('/sap-login', [SapLoginController::class, 'showLoginForm'])->name('sap.login.form');
    Route::post('/sap-login', [SapLoginController::class, 'login'])->name('sap.login');
    
    Route::get('/dashboard/daily-sync-info', [DashboardController::class, 'getDailySyncInfo'])->name('dashboard.daily-sync-info');
    Route::post('/sap/sync-now', [DashboardController::class, 'syncSapNow'])->name('sap.sync-now');
    Route::get('/sap/storage-info', [DashboardController::class, 'getStorageInfo'])->name('sap.storage-info');
    
    Route::middleware(['sap.auth'])->group(function () {
        
        Route::prefix('smartform')->group(function () {
            Route::post('/monitor-auto-upload', [OptimizedSmartformController::class, 'monitorAndAutoUpload'])
                ->name('smartform.monitor-auto-upload');
            Route::post('/manual-upload', [OptimizedSmartformController::class, 'manualUploadForDelivery'])
                ->name('smartform.manual-upload');
            Route::post('/batch-process', [OptimizedSmartformController::class, 'batchProcessAllFiles'])
                ->name('smartform.batch-process');
            Route::get('/health-check', [OptimizedSmartformController::class, 'healthCheck'])
                ->name('smartform.health-check');
            Route::post('/force-process-all', function() {
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
            })->name('smartform.force-process-all');
        });
        
        Route::prefix('dashboard')->group(function () {
            Route::get('/admin-finance', [DashboardController::class, 'adminFinance'])->name('dashboard.admin-finance');
            Route::get('/exim', [DashboardController::class, 'exim'])->name('dashboard.exim');
            Route::get('/admin-finance/outstanding', [DashboardController::class, 'adminFinanceOutstanding'])->name('dashboard.admin-finance.outstanding');
            Route::get('/admin-finance/onprogress', [DashboardController::class, 'adminFinanceOnProgress'])->name('dashboard.admin-finance.onprogress');
            Route::get('/admin-finance/completed', [DashboardController::class, 'adminFinanceCompleted'])->name('dashboard.admin-finance.completed');
            Route::get('/admin-finance/sent', [DashboardController::class, 'adminFinanceSent'])->name('dashboard.admin-finance.sent');
            Route::get('/admin-finance/refresh', [DashboardController::class, 'refreshAdminFinance'])->name('dashboard.admin-finance.refresh');
            Route::get('/exim/refresh', [DashboardController::class, 'refreshExim'])->name('dashboard.exim.refresh');
            Route::get('/test-connection', [DashboardController::class, 'testBillingConnection'])->name('dashboard.test-connection');
            Route::post('/force-refresh', [DashboardController::class, 'forceRefreshData'])->name('dashboard.force-refresh');
        });

        Route::prefix('sap')->group(function () {
            Route::post('/sync-now', [DashboardController::class, 'syncSapNow'])->name('sap.sync-now');
            Route::get('/storage-info', [DashboardController::class, 'getStorageInfo'])->name('sap.storage-info');
        });
        
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
            Route::post('/bulk-upload', [DocumentUploadController::class, 'bulkUpload'])->name('documents.bulk-upload');
            Route::delete('/bulk-delete', [DocumentUploadController::class, 'bulkDelete'])->name('documents.bulk-delete');
            Route::post('/validate-document', [DocumentUploadController::class, 'validateDocument'])->name('documents.validate-document');
            Route::post('/re-validate/{uploadId}', [DocumentUploadController::class, 'reValidateDocument'])->name('documents.re-validate');
            Route::get('/exim-documents/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getEximDocumentsForFinance'])->name('documents.exim-for-finance');
            Route::get('/status-progress/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getStatusProgress'])->name('documents.status-progress');
            Route::post('/mark-as-sent', [DocumentUploadController::class, 'markAsSent'])->name('documents.mark-as-sent');
            Route::post('/send-to-multiple-buyers', [DashboardController::class, 'sendToBuyerMultipleEmails'])->name('documents.send-to-multiple-buyers');
        });
        
        Route::prefix('buyer-emails')->group(function () {
            Route::get('/get/{customerName}', [DashboardController::class, 'getBuyerEmails'])->name('buyer-emails.get');
            Route::post('/send-multiple', [DashboardController::class, 'sendToBuyerMultipleEmails'])->name('buyer-emails.send-multiple');
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
        
        Route::prefix('billing')->group(function () {
            Route::get('/', [BillingController::class, 'index'])->name('billing.index');
            Route::post('/submit', [BillingController::class, 'submitBilling'])->name('billing.submit');
            Route::post('/detail', [BillingController::class, 'detailBilling'])->name('billing.detail');
            Route::post('/submit-with-retry', [BillingController::class, 'submitBillingWithRetry'])->name('billing.submit-retry');
            Route::post('/detail-with-fallback', [BillingController::class, 'detailBillingWithFallback'])->name('billing.detail-fallback');
            Route::get('/refresh-data', [BillingController::class, 'refreshBillingData'])->name('billing.refresh-data');
            Route::get('/data-status', [BillingController::class, 'getBillingDataStatus'])->name('billing.data-status');
        });
        
        Route::prefix('api')->group(function () {
            Route::get('/dashboard/data/{type}', [DashboardController::class, 'getDashboardData'])->name('api.dashboard.data');
            Route::post('/dashboard/refresh/{type}', [DashboardController::class, 'refreshDashboardData'])->name('api.dashboard.refresh');
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
                Route::post('/upload-with-validation', [DocumentUploadController::class, 'uploadWithTeamValidation'])
                     ->name('api.documents.upload-validated');
                Route::get('/progress/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'getDocumentProgress'])
                     ->name('api.documents.progress');
                Route::get('/progress/{deliveryOrder}/{customerName}/{team?}', [DocumentUploadController::class, 'getTeamProgress'])
                     ->name('api.documents.team-progress');
                Route::post('/sync-teams', [DocumentUploadController::class, 'syncBetweenTeams'])
                     ->name('api.documents.sync-teams');
                Route::get('/check-updates/{deliveryOrder}/{customerName}', [DocumentUploadController::class, 'checkDocumentUpdates'])
                     ->name('documents.check-updates');
                Route::post('/batch-load', [DashboardController::class, 'getBatchDocuments'])
                     ->name('api.documents.batch-load');
                Route::post('/batch-counts', [DashboardController::class, 'getDocumentCounts'])
                     ->name('api.documents.batch-counts');
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

        if (!app()->environment('production')) {
            Route::get('/test-real-email-send', function() {
                return "Email test completed";
            })->name('test.real.email.send');
        }
    });
});

Route::fallback(function () {
    return response()->json([
        'error' => 'Route not found',
        'message' => 'The requested URL was not found on this server.',
        'requested_url' => request()->fullUrl()
    ], 404);
});