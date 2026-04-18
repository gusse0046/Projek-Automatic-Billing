<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SapDataStorage;
use Carbon\Carbon;

class SyncSapToDatabase extends Command
{
    protected $signature = 'sap:sync-to-db 
                           {--force : Force sync even if cache is fresh}
                           {--endpoint=fast : SAP endpoint to use (fast|standard)}
                           {--timeout=300 : Timeout in seconds}
                           {--daily : Daily sync mode - detect new data only}
                           {--compare : Compare with existing data to detect changes}';

    protected $description = 'Sync SAP billing data to database with daily auto-detection';

    private $billingApiUrl = 'http://127.0.0.1:50';

    public function handle()
    {
        $this->info('🚀 Starting SAP Daily Auto-Sync System...');
        
        $endpoint = $this->option('endpoint');
        $force = $this->option('force');
        $timeout = (int)$this->option('timeout');
        $dailyMode = $this->option('daily');
        $compareMode = $this->option('compare');
        
        $this->displaySyncInfo($endpoint, $force, $timeout, $dailyMode);

        // DAILY MODE: Check if sync needed based on data changes
        if ($dailyMode && !$force) {
            $syncNeeded = $this->isDailySyncNeeded();
            if (!$syncNeeded) {
                $this->info("✅ Daily sync not needed - no new data detected");
                return 0;
            }
        }

        // START SYNC PROCESS
        $startTime = microtime(true);
        
        try {
            // Get current data for comparison
            $existingData = null;
            if ($compareMode || $dailyMode) {
                $existingData = SapDataStorage::getMainBillingData();
                $this->info("📊 Loaded existing data: " . (is_array($existingData) ? count($existingData) : 0) . " records");
            }

            // Fetch new data from SAP
            $newBillingData = $this->fetchFromSap($endpoint, $timeout);
            
            if ($newBillingData === null) {
                throw new \Exception('Failed to fetch data from SAP');
            }

            $fetchDuration = round(microtime(true) - $startTime, 2);
            $newRecordCount = count($newBillingData);

            // COMPARE DATA if in daily/compare mode
            if (($compareMode || $dailyMode) && $existingData !== null) {
                $comparison = $this->compareData($existingData, $newBillingData);
                $this->displayComparison($comparison);
                
                // Only store if there are significant changes
                if (!$comparison['has_changes'] && !$force) {
                    $this->info("✅ No significant changes detected - skipping storage");
                    return 0;
                }
            }

            // STORE NEW DATA
            $stored = SapDataStorage::storeBillingData($newBillingData, $endpoint, $fetchDuration);
            
            $this->displaySuccessInfo($newRecordCount, $fetchDuration, $stored);
            
            // LOG DAILY SYNC ACTIVITY
            $this->logDailySyncActivity($newRecordCount, $fetchDuration, $endpoint);
            
            return 0;

        } catch (\Exception $e) {
            return $this->handleSyncError($e, $startTime, $endpoint);
        }
    }

    private function isDailySyncNeeded(): bool
    {
        $cacheAge = SapDataStorage::getCacheAgeMinutes();
        $now = Carbon::now();
        
        if ($cacheAge === null) {
            $this->info("🔄 No cache found - sync needed");
            return true;
        }

        if ($now->isWeekday() && $now->hour >= 7 && $now->hour <= 18) {
            if ($cacheAge > 360) {
                $this->info("🔄 Cache too old during business hours - sync needed");
                return true;
            }
        }

        if ($cacheAge > 1440) {
            $this->info("🔄 Daily sync needed - last sync over 24h ago");
            return true;
        }

        $lastSyncDate = SapDataStorage::getLastSyncDate();
        if ($lastSyncDate && $lastSyncDate->format('Y-m-d') < $now->format('Y-m-d')) {
            if ($now->hour >= 8) {
                $this->info("🔄 New business day detected - sync needed");
                return true;
            }
        }

        return false;
    }

    private function fetchFromSap($endpoint, $timeout): ?array
    {
        $endpoints = [
            'fast' => $this->billingApiUrl . '/api/billing_data_fast',
            'standard' => $this->billingApiUrl . '/api/billing_data'
        ];

        $url = $endpoints[$endpoint] ?? $endpoints['fast'];
        $this->info("🌐 Fetching from: {$url}");

        $response = Http::timeout($timeout)->retry(2, 100)->get($url);

        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['connection_status']) && $data['connection_status'] === true) {
                return $data['data'] ?? [];
            }
        }

        if ($endpoint === 'fast') {
            $this->warn("⚠️ Fast endpoint failed, trying standard...");
            $fallbackUrl = $endpoints['standard'];
            $response = Http::timeout($timeout * 2)->get($fallbackUrl);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['connection_status']) && $data['connection_status'] === true) {
                    return $data['data'] ?? [];
                }
            }
        }

        return null;
    }

    private function compareData($existingData, $newData): array
    {
        $existingCount = count($existingData);
        $newCount = count($newData);
        
        $countChanged = $existingCount !== $newCount;
        
        $significantChanges = false;
        $newDeliveries = [];
        $updatedDeliveries = [];
        
        if ($countChanged || abs($existingCount - $newCount) > 0) {
            $existingLookup = [];
            foreach ($existingData as $item) {
                $key = ($item['Delivery'] ?? '') . '_' . ($item['Customer Name'] ?? '');
                $existingLookup[$key] = $item;
            }
            
            foreach ($newData as $newItem) {
                $key = ($newItem['Delivery'] ?? '') . '_' . ($newItem['Customer Name'] ?? '');
                
                if (!isset($existingLookup[$key])) {
                    $newDeliveries[] = $newItem['Delivery'] ?? 'Unknown';
                    $significantChanges = true;
                } else {
                    $existingBilling = $existingLookup[$key]['Billing Document'] ?? '';
                    $newBilling = $newItem['Billing Document'] ?? '';
                    
                    if ($existingBilling !== $newBilling) {
                        $updatedDeliveries[] = $newItem['Delivery'] ?? 'Unknown';
                        $significantChanges = true;
                    }
                }
            }
        }
        
        return [
            'has_changes' => $countChanged || $significantChanges,
            'count_changed' => $countChanged,
            'existing_count' => $existingCount,
            'new_count' => $newCount,
            'count_difference' => $newCount - $existingCount,
            'new_deliveries' => $newDeliveries,
            'updated_deliveries' => $updatedDeliveries,
            'significant_changes' => $significantChanges
        ];
    }

    private function displayComparison($comparison): void
    {
        $this->info("📊 DATA COMPARISON RESULTS:");
        $this->info("   Existing records: {$comparison['existing_count']}");
        $this->info("   New records: {$comparison['new_count']}");
        $this->info("   Difference: {$comparison['count_difference']}");
        
        if (!empty($comparison['new_deliveries'])) {
            $this->info("   🆕 New deliveries: " . implode(', ', array_slice($comparison['new_deliveries'], 0, 5)));
        }
        
        if (!empty($comparison['updated_deliveries'])) {
            $this->info("   🔄 Updated deliveries: " . implode(', ', array_slice($comparison['updated_deliveries'], 0, 5)));
        }
        
        $this->info("   Has significant changes: " . ($comparison['has_changes'] ? 'YES' : 'NO'));
    }

    private function logDailySyncActivity($recordCount, $duration, $endpoint): void
    {
        Log::info('🔄 DAILY SAP SYNC COMPLETED', [
            'record_count' => $recordCount,
            'fetch_duration' => $duration,
            'endpoint_used' => $endpoint,
            'sync_timestamp' => Carbon::now()->toDateTimeString(),
            'sync_date' => Carbon::now()->format('Y-m-d'),
            'business_hours' => Carbon::now()->isWeekday() && Carbon::now()->hour >= 7 && Carbon::now()->hour <= 18
        ]);
    }
    
    private function displaySyncInfo($endpoint, $force, $timeout, $dailyMode): void
    {
        $this->info("📡 Endpoint: {$endpoint}");
        $this->info("⚡ Force sync: " . ($force ? 'YES' : 'NO'));
        $this->info("🕐 Timeout: {$timeout} seconds");
        $this->info("📅 Daily mode: " . ($dailyMode ? 'YES' : 'NO'));
        $this->info("🕒 Current time: " . Carbon::now()->format('Y-m-d H:i:s'));
    }
    
    private function displaySuccessInfo($recordCount, $duration, $stored): void
    {
        $this->info("✅ SUCCESS: Synced {$recordCount} records in {$duration}s");
        $this->info("🗄️ Storage ID: {$stored->id}");
        $this->info("🚀 Dashboard will now load 35x faster from cache!");
    }
    
    private function handleSyncError($e, $startTime, $endpoint): int
    {
        $fetchDuration = round(microtime(true) - $startTime, 2);
        $errorMsg = "SAP Sync Error: " . $e->getMessage();
        
        $this->error("❌ {$errorMsg}");
        
        SapDataStorage::storeFetchError($endpoint, $fetchDuration, $errorMsg);
        
        return 1;
    }
}