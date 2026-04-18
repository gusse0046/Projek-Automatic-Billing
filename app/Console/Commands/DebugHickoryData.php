<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SapDataStorage;
use Illuminate\Support\Facades\Log;

class DebugHickoryData extends Command
{
    protected $signature = 'debug:hickory';
    protected $description = 'Debug HICKORY CHAIR data - check if delivery 2010004843 exists';

    public function handle()
    {
        $this->info('=== DEBUGGING HICKORY CHAIR DATA ===');
        $this->info('Target: Delivery 2010004843, Customer: HICKORY CHAIR, LLC');
        $this->newLine();

        // STEP 1: Check Raw Data from SapDataStorage
        $this->info('STEP 1: Checking raw data from SapDataStorage...');
        $rawData = SapDataStorage::getMainBillingData();
        
        if (!$rawData || !is_array($rawData)) {
            $this->error('❌ No data found in SapDataStorage!');
            return 1;
        }
        
        $this->info("✅ Total records in cache: " . count($rawData));
        $this->newLine();

        // STEP 2: Search for HICKORY CHAIR data
        $this->info('STEP 2: Searching for HICKORY CHAIR records...');
        
        $hickoryRecords = [];
        $hickoryDelivery2010004843 = [];
        
        foreach ($rawData as $index => $item) {
            $customerName = $item['Customer Name'] ?? '';
            $delivery = $item['Delivery'] ?? '';
            
            // Find all HICKORY CHAIR records
            if (stripos($customerName, 'HICKORY') !== false) {
                $hickoryRecords[] = [
                    'index' => $index,
                    'delivery' => $delivery,
                    'customer' => $customerName,
                    'billing_doc' => $item['Billing Document'] ?? 'NULL',
                    'reference' => $item['Reference Document'] ?? 'NULL',
                    'net_value' => $item['Net Value in Document Currency'] ?? '0',
                    'material' => $item['Material Number'] ?? 'NULL'
                ];
                
                // Find specific delivery 2010004843
                if ($delivery === '2010004843') {
                    $hickoryDelivery2010004843[] = $item;
                }
            }
        }
        
        $this->info("Total HICKORY CHAIR records found: " . count($hickoryRecords));
        $this->info("Records with delivery 2010004843: " . count($hickoryDelivery2010004843));
        $this->newLine();

        // STEP 3: Display all HICKORY CHAIR deliveries
        $this->info('STEP 3: All HICKORY CHAIR deliveries:');
        $this->table(
            ['Index', 'Delivery', 'Customer', 'Billing Doc', 'Reference', 'Net Value'],
            array_map(function($record) {
                return [
                    $record['index'],
                    $record['delivery'],
                    substr($record['customer'], 0, 25),
                    $record['billing_doc'],
                    $record['reference'],
                    $record['net_value']
                ];
            }, $hickoryRecords)
        );
        $this->newLine();

        // STEP 4: Detailed inspection of delivery 2010004843
        if (count($hickoryDelivery2010004843) > 0) {
            $this->info('✅ FOUND: Delivery 2010004843 exists in raw data!');
            $this->info('Total items for this delivery: ' . count($hickoryDelivery2010004843));
            $this->newLine();
            
            $this->info('STEP 4: Detailed data for delivery 2010004843:');
            foreach ($hickoryDelivery2010004843 as $idx => $item) {
                $this->warn("--- Item #" . ($idx + 1) . " ---");
                foreach ($item as $key => $value) {
                    $displayValue = is_string($value) ? $value : json_encode($value);
                    $this->line("  {$key}: {$displayValue}");
                }
                $this->newLine();
            }
        } else {
            $this->error('❌ NOT FOUND: Delivery 2010004843 does NOT exist in raw data!');
            $this->warn('This means the data is not being fetched from Python bill.py');
            $this->newLine();
        }

        // STEP 5: Check grouping logic simulation
        $this->info('STEP 5: Simulating grouping logic...');
        
        $grouped = [];
        foreach ($rawData as $item) {
            $delivery = $item['Delivery'] ?? '';
            $customerName = $item['Customer Name'] ?? '';
            
            // Skip if no delivery (same logic as DashboardController)
            if (empty($delivery)) {
                continue;
            }
            
            $key = $delivery . '_' . $customerName;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'delivery' => $delivery,
                    'customer_name' => $customerName,
                    'item_count' => 0
                ];
            }
            
            $grouped[$key]['item_count']++;
        }
        
        // Find HICKORY CHAIR in grouped data
        $hickoryGrouped = array_filter($grouped, function($group) {
            return stripos($group['customer_name'], 'HICKORY') !== false;
        });
        
        $this->info("Grouped HICKORY CHAIR deliveries: " . count($hickoryGrouped));
        
        $has2010004843 = false;
        foreach ($hickoryGrouped as $key => $group) {
            if ($group['delivery'] === '2010004843') {
                $has2010004843 = true;
                $this->info("✅ Delivery 2010004843 found in grouped data!");
                $this->line("  Key: {$key}");
                $this->line("  Items: {$group['item_count']}");
            }
        }
        
        if (!$has2010004843) {
            $this->error('❌ Delivery 2010004843 NOT found after grouping!');
        }
        $this->newLine();

        // STEP 6: Check for empty delivery numbers
        $this->info('STEP 6: Checking for data quality issues...');
        $emptyDeliveries = 0;
        $nullDeliveries = 0;
        
        foreach ($rawData as $item) {
            $delivery = $item['Delivery'] ?? null;
            
            if ($delivery === null) {
                $nullDeliveries++;
            } elseif (empty($delivery)) {
                $emptyDeliveries++;
            }
        }
        
        $this->info("Records with NULL delivery: {$nullDeliveries}");
        $this->info("Records with empty delivery: {$emptyDeliveries}");
        $this->newLine();

        // STEP 7: Summary
        $this->info('=== SUMMARY ===');
        if (count($hickoryDelivery2010004843) > 0) {
            $this->info('✅ Data EXISTS in SapDataStorage cache');
            $this->info('✅ Data should appear in dashboard');
            $this->warn('⚠️  If not appearing, check:');
            $this->line('   1. Browser cache / hard refresh (Ctrl+Shift+R)');
            $this->line('   2. Dashboard filtering logic');
            $this->line('   3. JavaScript console for errors');
        } else {
            $this->error('❌ Data NOT FOUND in SapDataStorage cache');
            $this->warn('⚠️  Action required:');
            $this->line('   1. Check bill.py output manually');
            $this->line('   2. Run: php artisan sap:sync-to-db --force');
            $this->line('   3. Check Python field mapping');
        }

        return 0;
    }
}