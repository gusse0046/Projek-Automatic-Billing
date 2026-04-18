<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FinanceDocumentSetting;
use Illuminate\Support\Facades\DB;

class FinanceDocumentSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample customers dari dashboard
        $sampleCustomers = [
            'ETHAN ALLEN OPERATIONS, INC.',
            'CENTURY FURNITURE',
            'BRUNSWICK BILLIARDS-LIFE FITNESS',
            'VANGUARD FURNITURE',
            'HICKORY CHAIR, LLC',
            'THE UTTERMOST CO.',
            'LAKESHORE LEARNING MATERIALS,LLC',
            'ROWE FINE FURNITURE INC',
            'THAYER COGGIN, INC',
            'PT SKYLINE JAYA',
            'GABBY',
            'SAMPLE CUSTOMER'
        ];

        foreach ($sampleCustomers as $customerName) {
            FinanceDocumentSetting::updateOrCreate(
                ['customer_name' => $customerName],
                [
                    'enabled_documents' => FinanceDocumentSetting::DEFAULT_FINANCE_DOCUMENTS,
                    'is_active' => true,
                    'notes' => 'Default configuration for ' . $customerName,
                    'created_by' => 'System Seeder',
                    'updated_by' => 'System Seeder'
                ]
            );
        }

        // Special configurations for some customers
        FinanceDocumentSetting::updateOrCreate(
            ['customer_name' => 'VANGUARD FURNITURE'],
            [
                'enabled_documents' => [
                    'INVOICE',
                    'PACKING_LIST',
                    'PAYMENT_INSTRUCTION',
                    'CARB_INFO',
                    'CONTAINER_CHECK_LIST'
                ],
                'is_active' => true,
                'notes' => 'Extended configuration - requires CARB_INFO and CONTAINER_CHECK_LIST',
                'created_by' => 'System Seeder',
                'updated_by' => 'System Seeder'
            ]
        );

        FinanceDocumentSetting::updateOrCreate(
            ['customer_name' => 'SAMPLE CUSTOMER'],
            [
                'enabled_documents' => FinanceDocumentSetting::AVAILABLE_FINANCE_DOCUMENTS,
                'is_active' => true,
                'notes' => 'Test customer with all documents enabled',
                'created_by' => 'System Seeder',
                'updated_by' => 'System Seeder'
            ]
        );

        $this->command->info('Finance Document Settings seeded successfully!');
        $this->command->info('Created settings for ' . count($sampleCustomers) . ' customers');
    }
}