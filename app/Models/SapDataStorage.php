<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SapDataStorage extends Model
{
    protected $table = 'sap_data_storage';
    
    protected $fillable = [
        'data_key',
        'sap_raw_data',
        'data', // For compatibility
        'total_records',
        'record_count', // For compatibility 
        'last_fetch_at',
        'fetch_duration_seconds',
        'fetch_duration', // For compatibility
        'fetch_status',
        'error_message',
        'endpoint_used',
        'data_size_bytes',
        'compression_type',
        'memory_usage_mb',
        'notes'
    ];
    
    protected $casts = [
        'last_fetch_at' => 'datetime',
        'fetch_duration_seconds' => 'decimal:2',
        'fetch_duration' => 'decimal:2',
        'memory_usage_mb' => 'decimal:2',
        'data_size_bytes' => 'integer',
        'total_records' => 'integer',
        'record_count' => 'integer'
    ];

    /**
     * Store large billing data dengan compression dan optimisasi
     */
    public static function storeBillingData($billingData, $endpoint = 'unknown', $fetchDuration = 0)
    {
        try {
            Log::info('🔄 Storing large billing data to database', [
                'record_count' => is_array($billingData) ? count($billingData) : 0,
                'endpoint' => $endpoint,
                'fetch_duration' => $fetchDuration,
                'memory_before' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB'
            ]);

            // Convert to JSON and calculate size
            $jsonData = json_encode($billingData, JSON_UNESCAPED_UNICODE);
            if ($jsonData === false) {
                throw new \Exception('Failed to encode billing data to JSON: ' . json_last_error_msg());
            }

            $originalSize = strlen($jsonData);
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            $recordCount = is_array($billingData) ? count($billingData) : 0;

            // Compression untuk data besar (> 5MB)
            $compressionType = null;
            $finalData = $jsonData;
            
            if ($originalSize > 5 * 1024 * 1024) { // 5MB threshold
                Log::info("📦 Data size large ({$originalSize} bytes), attempting compression...");
                
                $compressedData = gzencode($jsonData, 6);
                if ($compressedData !== false) {
                    $compressedSize = strlen($compressedData);
                    $compressionRatio = round((1 - $compressedSize / $originalSize) * 100, 1);
                    
                    if ($compressedSize < $originalSize * 0.8) { // Only use if 20%+ compression
                        $finalData = base64_encode($compressedData);
                        $compressionType = 'gzip_base64';
                        
                        Log::info("✅ Compression successful: {$compressionRatio}% reduction", [
                            'original_size' => round($originalSize / 1024 / 1024, 2) . ' MB',
                            'compressed_size' => round(strlen($finalData) / 1024 / 1024, 2) . ' MB'
                        ]);
                    }
                }
            }

            // Database transaction untuk consistency
            DB::beginTransaction();

            try {
                // Backup current data sebelum update (untuk rollback jika perlu)
                $existingData = self::where('data_key', 'main_billing_data')
                                  ->where('fetch_status', 'success')
                                  ->latest('last_fetch_at')
                                  ->first();

                // Store new data
                $stored = self::updateOrCreate(
                    ['data_key' => 'main_billing_data'],
                    [
                        'sap_raw_data' => $finalData,
                        'data' => $finalData, // For compatibility
                        'total_records' => $recordCount,
                        'record_count' => $recordCount, // For compatibility
                        'last_fetch_at' => Carbon::now(),
                        'fetch_duration_seconds' => $fetchDuration,
                        'fetch_duration' => $fetchDuration, // For compatibility
                        'fetch_status' => 'success',
                        'error_message' => null,
                        'endpoint_used' => $endpoint,
                        'data_size_bytes' => $originalSize,
                        'compression_type' => $compressionType,
                        'memory_usage_mb' => round($memoryUsage, 2),
                        'notes' => "Large dataset: {$recordCount} records, " . 
                                  round($originalSize / 1024 / 1024, 2) . "MB" .
                                  ($compressionType ? " (compressed)" : "")
                    ]
                );

                // Clean old data (keep last 3 successful syncs for rollback)
                self::cleanOldData(3);

                DB::commit();

                Log::info('✅ Large billing data stored successfully', [
                    'storage_id' => $stored->id,
                    'record_count' => $stored->total_records,
                    'original_size_mb' => round($originalSize / 1024 / 1024, 2),
                    'final_size_mb' => round(strlen($finalData) / 1024 / 1024, 2),
                    'compression' => $compressionType ?? 'none',
                    'memory_usage_mb' => $stored->memory_usage_mb
                ]);

                return $stored;

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('❌ Failed to store large billing data: ' . $e->getMessage(), [
                'endpoint' => $endpoint,
                'record_count' => is_array($billingData) ? count($billingData) : 0,
                'error_trace' => $e->getTraceAsString()
            ]);

            // Store error info
            return self::storeFetchError($endpoint, $fetchDuration, $e->getMessage());
        }
    }
    
    /**
     * Get main billing data dengan decompression support
     */
    public static function getMainBillingData()
    {
        try {
            $data = self::where('data_key', 'main_billing_data')
                       ->where('fetch_status', 'success')
                       ->latest('last_fetch_at')
                       ->first();
            
            if (!$data || !$data->sap_raw_data) {
                Log::info('⚠️ No valid cached data found in database');
                return null;
            }

            $rawData = $data->sap_raw_data;
            
            // Handle decompression if needed
            if ($data->compression_type === 'gzip_base64') {
                Log::info('📦 Decompressing cached data...');
                
                $compressedData = base64_decode($rawData);
                if ($compressedData === false) {
                    Log::error('❌ Failed to decode base64 compressed data');
                    return null;
                }
                
                $rawData = gzdecode($compressedData);
                if ($rawData === false) {
                    Log::error('❌ Failed to decompress gzip data');
                    return null;
                }
                
                Log::info('✅ Data decompressed successfully');
            }
            
            // Decode JSON
            $billingData = json_decode($rawData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('❌ JSON decode error: ' . json_last_error_msg());
                return null;
            }
            
            if (!is_array($billingData)) {
                Log::error('❌ Decoded data is not an array');
                return null;
            }
            
            Log::info('✅ Retrieved cached billing data successfully', [
                'record_count' => count($billingData),
                'cache_age_minutes' => $data->last_fetch_at->diffInMinutes(Carbon::now()),
                'data_size_mb' => round(($data->data_size_bytes ?? 0) / 1024 / 1024, 2),
                'compressed' => $data->compression_type ? true : false
            ]);
            
            return $billingData;
            
        } catch (\Exception $e) {
            Log::error('❌ Error retrieving cached data: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if cache is fresh
     */
    public static function isCacheFresh($minutesThreshold = 30)
    {
        try {
            $data = self::where('data_key', 'main_billing_data')
                       ->where('fetch_status', 'success')
                       ->latest('last_fetch_at')
                       ->first();
            
            if (!$data) {
                return false;
            }
            
            $ageMinutes = $data->last_fetch_at->diffInMinutes(Carbon::now());
            return $ageMinutes < $minutesThreshold;
            
        } catch (\Exception $e) {
            Log::error('Error checking cache freshness: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cache age in minutes
     */
    public static function getCacheAgeMinutes()
    {
        try {
            $data = self::where('data_key', 'main_billing_data')
                       ->latest('last_fetch_at')
                       ->first();
            
            if (!$data) {
                return null;
            }
            
            return $data->last_fetch_at->diffInMinutes(Carbon::now());
            
        } catch (\Exception $e) {
            Log::error('Error getting cache age: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Store fetch error with proper error handling
     */
    public static function storeFetchError($endpoint, $fetchDuration, $errorMessage)
    {
        try {
            // Limit error message size untuk avoid column overflow
            $truncatedError = substr($errorMessage, 0, 65535); // Max LONGTEXT size
            
            return self::updateOrCreate(
                ['data_key' => 'main_billing_data'],
                [
                    'last_fetch_at' => Carbon::now(),
                    'fetch_duration_seconds' => $fetchDuration,
                    'fetch_duration' => $fetchDuration, // For compatibility
                    'fetch_status' => 'error',
                    'error_message' => $truncatedError,
                    'endpoint_used' => $endpoint,
                    'total_records' => 0,
                    'record_count' => 0, // For compatibility
                    'notes' => 'Fetch failed at ' . Carbon::now()->toDateTimeString()
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to store fetch error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get last successful sync date
     */
    public static function getLastSyncDate(): ?Carbon
    {
        try {
            $data = self::where('data_key', 'main_billing_data')
                       ->where('fetch_status', 'success')
                       ->latest('last_fetch_at')
                       ->first();
            
            return $data ? $data->last_fetch_at : null;
            
        } catch (\Exception $e) {
            Log::error('Error getting last sync date: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if today's sync is completed
     */
    public static function isTodaySyncCompleted(): bool
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            
            $todaySync = self::where('data_key', 'main_billing_data')
                            ->where('fetch_status', 'success')
                            ->whereDate('last_fetch_at', $today)
                            ->exists();
            
            return $todaySync;
            
        } catch (\Exception $e) {
            Log::error('Error checking today sync status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get daily sync statistics
     */
    public static function getDailySyncStats($days = 7): array
    {
        try {
            $stats = self::where('data_key', 'main_billing_data')
                        ->where('last_fetch_at', '>=', Carbon::now()->subDays($days))
                        ->selectRaw('
                            DATE(last_fetch_at) as sync_date,
                            COUNT(*) as sync_count,
                            SUM(CASE WHEN fetch_status = "success" THEN 1 ELSE 0 END) as successful_syncs,
                            SUM(CASE WHEN fetch_status = "error" THEN 1 ELSE 0 END) as failed_syncs,
                            AVG(fetch_duration_seconds) as avg_duration,
                            MAX(total_records) as max_records,
                            MAX(data_size_bytes) as max_data_size,
                            AVG(memory_usage_mb) as avg_memory_usage
                        ')
                        ->groupBy('sync_date')
                        ->orderBy('sync_date', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->max_data_size_mb = round(($item->max_data_size ?? 0) / 1024 / 1024, 2);
                            $item->avg_memory_usage = round($item->avg_memory_usage ?? 0, 2);
                            $item->avg_duration = round($item->avg_duration ?? 0, 2);
                            return $item;
                        });
            
            return $stats->toArray();
            
        } catch (\Exception $e) {
            Log::error('Error getting daily sync stats: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get comprehensive storage statistics
     */
    public static function getStorageStats()
    {
        try {
            $stats = self::selectRaw('
                COUNT(*) as total_entries,
                SUM(CASE WHEN fetch_status = "success" THEN 1 ELSE 0 END) as successful_fetches,
                SUM(CASE WHEN fetch_status = "error" THEN 1 ELSE 0 END) as failed_fetches,
                SUM(total_records) as total_sap_records,
                AVG(fetch_duration_seconds) as avg_fetch_duration,
                MAX(data_size_bytes) as largest_dataset,
                SUM(data_size_bytes) as total_storage_size,
                AVG(memory_usage_mb) as avg_memory_usage,
                COUNT(CASE WHEN compression_type IS NOT NULL THEN 1 END) as compressed_entries,
                MAX(last_fetch_at) as latest_fetch,
                MIN(last_fetch_at) as oldest_fetch
            ')->first();
            
            $hasActiveStorage = self::where('data_key', 'main_billing_data')
                                   ->where('fetch_status', 'success')
                                   ->exists();
            
            $cacheAge = self::getCacheAgeMinutes();
            
            return [
                'total_entries' => $stats->total_entries ?? 0,
                'successful_fetches' => $stats->successful_fetches ?? 0,
                'failed_fetches' => $stats->failed_fetches ?? 0,
                'total_sap_records' => $stats->total_sap_records ?? 0,
                'avg_fetch_duration' => round($stats->avg_fetch_duration ?? 0, 2),
                'largest_dataset_mb' => round(($stats->largest_dataset ?? 0) / 1024 / 1024, 2),
                'total_storage_mb' => round(($stats->total_storage_size ?? 0) / 1024 / 1024, 2),
                'avg_memory_usage_mb' => round($stats->avg_memory_usage ?? 0, 2),
                'compressed_entries' => $stats->compressed_entries ?? 0,
                'compression_usage_percent' => $stats->total_entries > 0 ? 
                    round(($stats->compressed_entries / $stats->total_entries) * 100, 1) : 0,
                'latest_fetch' => $stats->latest_fetch ? Carbon::parse($stats->latest_fetch)->diffForHumans() : 'Never',
                'oldest_fetch' => $stats->oldest_fetch ? Carbon::parse($stats->oldest_fetch)->diffForHumans() : 'Never',
                'has_active_storage' => $hasActiveStorage,
                'cache_age_minutes' => $cacheAge,
                'is_cache_fresh' => self::isCacheFresh()
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting storage stats: ' . $e->getMessage());
            return [
                'total_entries' => 0,
                'has_active_storage' => false,
                'cache_age_minutes' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get detailed storage information
     */
    public static function getStorageInfo()
    {
        try {
            $latestData = self::where('data_key', 'main_billing_data')
                             ->latest('last_fetch_at')
                             ->first();
            
            if (!$latestData) {
                return [
                    'has_data' => false,
                    'message' => 'No storage data found'
                ];
            }
            
            return [
                'has_data' => true,
                'fetch_status' => $latestData->fetch_status,
                'last_sync' => $latestData->last_fetch_at,
                'last_sync_formatted' => $latestData->last_fetch_at->format('Y-m-d H:i:s'),
                'last_sync_human' => $latestData->last_fetch_at->diffForHumans(),
                'record_count' => $latestData->total_records,
                'fetch_duration' => $latestData->fetch_duration_seconds,
                'endpoint_used' => $latestData->endpoint_used ?? 'unknown',
                'error_message' => $latestData->error_message,
                'cache_age_minutes' => $latestData->last_fetch_at->diffInMinutes(Carbon::now()),
                'data_size_mb' => round(($latestData->data_size_bytes ?? 0) / 1024 / 1024, 2),
                'memory_usage_mb' => $latestData->memory_usage_mb,
                'compression_type' => $latestData->compression_type,
                'is_compressed' => !empty($latestData->compression_type),
                'notes' => $latestData->notes
            ];
            
        } catch (\Exception $e) {
            Log::error('Error getting storage info: ' . $e->getMessage());
            return [
                'has_data' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get comprehensive daily storage information
     */
    public static function getDailyStorageInfo(): array
    {
        try {
            $basicInfo = self::getStorageInfo();
            $todayCompleted = self::isTodaySyncCompleted();
            $lastSyncDate = self::getLastSyncDate();
            $dailyStats = self::getDailySyncStats(7);
            $storageStats = self::getStorageStats();
            
            return array_merge($basicInfo, [
                'today_sync_completed' => $todayCompleted,
                'last_sync_date' => $lastSyncDate ? $lastSyncDate->format('Y-m-d') : null,
                'last_sync_time' => $lastSyncDate ? $lastSyncDate->format('H:i:s') : null,
                'daily_stats_7days' => $dailyStats,
                'storage_stats' => $storageStats,
                'is_business_day' => Carbon::now()->isWeekday(),
                'current_time' => Carbon::now()->format('Y-m-d H:i:s'),
                'next_scheduled_sync' => self::getNextScheduledSync(),
                'system_performance' => [
                    'avg_fetch_time' => $storageStats['avg_fetch_duration'] ?? 0,
                    'compression_used' => $storageStats['compressed_entries'] ?? 0,
                    'total_storage_mb' => $storageStats['total_storage_mb'] ?? 0
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting daily storage info: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Get next scheduled sync time
     */
    public static function getNextScheduledSync(): string
    {
        $now = Carbon::now();
        
        // Business hours: every 30 minutes
        if ($now->isWeekday() && $now->hour >= 7 && $now->hour <= 18) {
            return $now->addMinutes(30)->format('H:i');
        }
        
        // Next business day at 8 AM
        $nextBusinessDay = $now->next(Carbon::MONDAY);
        return $nextBusinessDay->hour(8)->minute(0)->format('Y-m-d H:i');
    }
    
    /**
     * Clean old data entries
     */
    public static function cleanOldData($keepCount = 5)
    {
        try {
            // For main_billing_data, keep only recent successful syncs
            $mainDataIds = self::where('data_key', 'main_billing_data')
                              ->where('fetch_status', 'success')
                              ->orderBy('last_fetch_at', 'desc')
                              ->skip($keepCount)
                              ->pluck('id');

            $deletedMain = 0;
            if ($mainDataIds->count() > 0) {
                $deletedMain = self::whereIn('id', $mainDataIds)->delete();
            }

            // Clean very old error entries (older than 7 days)
            $cutoffDate = Carbon::now()->subDays(7);
            $deletedErrors = self::where('fetch_status', 'error')
                                ->where('last_fetch_at', '<', $cutoffDate)
                                ->delete();

            $totalDeleted = $deletedMain + $deletedErrors;
            
            if ($totalDeleted > 0) {
                Log::info("🧹 Cleaned old SAP data entries", [
                    'deleted_main_data' => $deletedMain,
                    'deleted_errors' => $deletedErrors,
                    'total_deleted' => $totalDeleted,
                    'kept_recent' => $keepCount
                ]);
            }

            return $totalDeleted;
            
        } catch (\Exception $e) {
            Log::error('Error cleaning old data: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Force refresh cache (mark as stale)
     */
    public static function forceRefresh($dataKey = 'main_billing_data')
    {
        try {
            $updated = self::where('data_key', $dataKey)
                          ->update(['last_fetch_at' => Carbon::now()->subHours(25)]); // Force stale
            
            Log::info("🔄 Forced cache refresh for data key: {$dataKey}", [
                'updated_records' => $updated
            ]);
            
            return $updated;
            
        } catch (\Exception $e) {
            Log::error('Error forcing refresh: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get data health status
     */
    public static function getHealthStatus(): array
    {
        try {
            $info = self::getStorageInfo();
            $stats = self::getStorageStats();
            $cacheAge = self::getCacheAgeMinutes();

            $health = [
                'status' => 'unknown',
                'message' => '',
                'details' => []
            ];

            if (!$info['has_data']) {
                $health['status'] = 'no_data';
                $health['message'] = 'No cached data available';
                return $health;
            }

            if ($info['fetch_status'] === 'error') {
                $health['status'] = 'error';
                $health['message'] = 'Last fetch failed: ' . substr($info['error_message'], 0, 100);
                return $health;
            }

            // Determine health based on cache age and data quality
            if ($cacheAge === null) {
                $health['status'] = 'unknown';
                $health['message'] = 'Cannot determine cache age';
            } elseif ($cacheAge < 30) {
                $health['status'] = 'excellent';
                $health['message'] = 'Data is fresh and recent';
            } elseif ($cacheAge < 120) {
                $health['status'] = 'good';
                $health['message'] = 'Data is reasonably fresh';
            } elseif ($cacheAge < 360) {
                $health['status'] = 'moderate';
                $health['message'] = 'Data is getting stale';
            } else {
                $health['status'] = 'stale';
                $health['message'] = 'Data is very old and needs refresh';
            }

            $health['details'] = [
                'cache_age_minutes' => $cacheAge,
                'record_count' => $info['record_count'],
                'data_size_mb' => $info['data_size_mb'],
                'last_sync' => $info['last_sync_human'],
                'compression_used' => $info['is_compressed']
            ];

            return $health;

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Health check failed: ' . $e->getMessage(),
                'details' => []
            ];
        }
    }

    /**
     * Perform data integrity check
     */
    public static function performIntegrityCheck(): array
    {
        try {
            $issues = [];
            $checks = [
                'data_exists' => false,
                'json_valid' => false,
                'records_count_match' => false,
                'compression_integrity' => true
            ];

            $latest = self::where('data_key', 'main_billing_data')
                         ->where('fetch_status', 'success')
                         ->latest('last_fetch_at')
                         ->first();

            if (!$latest) {
                $issues[] = 'No successful data found';
                return ['checks' => $checks, 'issues' => $issues, 'overall_status' => 'failed'];
            }

            $checks['data_exists'] = true;

            // Test data retrieval
            $data = self::getMainBillingData();
            if ($data === null) {
                $issues[] = 'Data retrieval failed';
                $checks['compression_integrity'] = false;
            } else {
                $checks['json_valid'] = is_array($data);
                $actualCount = count($data);
                $expectedCount = $latest->total_records;
                
                $checks['records_count_match'] = ($actualCount === $expectedCount);
                if (!$checks['records_count_match']) {
                    $issues[] = "Record count mismatch: expected {$expectedCount}, got {$actualCount}";
                }
            }

            $overallStatus = empty($issues) ? 'passed' : 'failed';

            return [
                'checks' => $checks,
                'issues' => $issues,
                'overall_status' => $overallStatus,
                'checked_at' => Carbon::now()->toDateTimeString()
            ];

        } catch (\Exception $e) {
            return [
                'checks' => ['error' => true],
                'issues' => ['Integrity check failed: ' . $e->getMessage()],
                'overall_status' => 'error',
                'checked_at' => Carbon::now()->toDateTimeString()
            ];
        }
    }
}