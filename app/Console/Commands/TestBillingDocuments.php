<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SapDataStorage;

class TestBillingDocuments extends Command
{
    protected $signature = 'test:billing-docs';
    protected $description = 'Test if billing_documents array is working';

    public function handle()
    {
        $this->info('Testing billing_documents array...');
        
        // Simulate what Controller does
        $billingData = SapDataStorage::getMainBillingData();
        
        if (!$billingData) {
            $this->error('No data in cache!');
            return 1;
        }
        
        // Group data (simplified version)
        $grouped = [];
        
        foreach ($billingData as $item) {
            $delivery = $item['Delivery'] ?? '';
            $customerName = $item['Customer Name'] ?? '';
            
            if (empty($delivery)) continue;
            
            $key = $delivery . '_' . $customerName;
            
            $validBillingDocument = null;
            $sapBillingDocument = $item['Billing Document'] ?? '';
            
            if (!empty($sapBillingDocument) && 
                trim($sapBillingDocument) !== '' && 
                $sapBillingDocument !== $delivery &&
                $sapBillingDocument !== '0') {
                $validBillingDocument = trim($sapBillingDocument);
            }
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'delivery' => $delivery,
                    'customer_name' => $customerName,
                    'billing_document' => $validBillingDocument,
                    'billing_documents' => []
                ];
            }
            
            // Collect billing documents
            if (!empty($validBillingDocument) && !in_array($validBillingDocument, $grouped[$key]['billing_documents'])) {
                $grouped[$key]['billing_documents'][] = $validBillingDocument;
            }
        }
        
        // Find HICKORY CHAIR 2010004843
        $found = false;
        foreach ($grouped as $key => $group) {
            if ($group['delivery'] === '2010004843' && 
                stripos($group['customer_name'], 'HICKORY') !== false) {
                
                $found = true;
                $this->info('✅ FOUND: ' . $key);
                $this->info('Delivery: ' . $group['delivery']);
                $this->info('Customer: ' . $group['customer_name']);
                $this->info('Primary Billing Doc: ' . ($group['billing_document'] ?? 'NULL'));
                $this->info('All Billing Docs: ' . json_encode($group['billing_documents']));
                $this->info('Count: ' . count($group['billing_documents']));
                
                if (count($group['billing_documents']) > 1) {
                    $this->info('✅ Multiple billing documents detected!');
                } else {
                    $this->warn('⚠️ Only 1 billing document found!');
                }
            }
        }
        
        if (!$found) {
            $this->error('❌ Data not found!');
        }
        
        return 0;
    }
}