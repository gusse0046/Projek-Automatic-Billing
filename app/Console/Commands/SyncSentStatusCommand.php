<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\EmailSentLog;
use App\Models\BillingStatus;
use Illuminate\Support\Facades\Log;
class SyncSentStatusCommand extends Command
{
protected $signature = 'billing:sync-sent-status
{--check : Only check inconsistencies without fixing}
{--force : Force sync all records}';
protected $description = 'Sync billing status from email_sent_logs to ensure consistency';

public function handle()
{
    $this->info('');
    $this->info('╔═══════════════════════════════════════════════╗');
    $this->info('║   SYNC SENT STATUS FROM EMAIL LOGS           ║');
    $this->info('╚═══════════════════════════════════════════════╝');
    $this->info('');

    // ✅ STEP 1: Get all successful email sends
    $this->info('📧 Fetching successful email sends from email_sent_logs...');
    
    $successfulSends = EmailSentLog::where('send_status', 'success')
        ->orderBy('sent_at', 'desc')
        ->get();
    
    $totalSends = $successfulSends->count();
    
    $this->info("✅ Found {$totalSends} successful email sends");
    $this->newLine();

    if ($totalSends === 0) {
        $this->warn('⚠️  No successful email sends found in email_sent_logs table');
        return 0;
    }

    // ✅ STEP 2: Check each record
    $this->info('🔍 Checking consistency with billing_status table...');
    $this->newLine();

    $stats = [
        'correct' => 0,
        'missing' => 0,
        'wrong_status' => 0,
        'missing_sent_at' => 0,
        'fixed' => 0,
        'failed' => 0
    ];

    $issues = [];

    foreach ($successfulSends as $emailLog) {
        $billingStatus = BillingStatus::where('delivery_order', $emailLog->delivery_order)
            ->where('customer_name', $emailLog->customer_name)
            ->first();

        if (!$billingStatus) {
            // Missing in billing_status
            $stats['missing']++;
            $issues[] = [
                'type' => 'MISSING',
                'delivery' => $emailLog->delivery_order,
                'customer' => $emailLog->customer_name,
                'sent_at' => $emailLog->sent_at->format('Y-m-d H:i:s'),
                'sent_by' => $emailLog->sent_by
            ];
        } elseif ($billingStatus->status !== 'sent') {
            // Wrong status
            $stats['wrong_status']++;
            $issues[] = [
                'type' => 'WRONG_STATUS',
                'delivery' => $emailLog->delivery_order,
                'customer' => $emailLog->customer_name,
                'current_status' => $billingStatus->status,
                'should_be' => 'sent',
                'sent_at' => $emailLog->sent_at->format('Y-m-d H:i:s')
            ];
        } elseif ($billingStatus->sent_at === null) {
            // Missing sent_at timestamp
            $stats['missing_sent_at']++;
            $issues[] = [
                'type' => 'MISSING_SENT_AT',
                'delivery' => $emailLog->delivery_order,
                'customer' => $emailLog->customer_name,
                'status' => 'sent (correct)',
                'issue' => 'sent_at is NULL',
                'should_be' => $emailLog->sent_at->format('Y-m-d H:i:s')
            ];
        } else {
            // Correct
            $stats['correct']++;
        }
    }

    // ✅ STEP 3: Display summary
    $this->info('📊 CONSISTENCY CHECK RESULTS:');
    $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    $this->table(
        ['Status', 'Count', 'Percentage'],
        [
            ['✅ Correct', $stats['correct'], round(($stats['correct'] / $totalSends) * 100, 1) . '%'],
            ['❌ Missing in billing_status', $stats['missing'], round(($stats['missing'] / $totalSends) * 100, 1) . '%'],
            ['⚠️  Wrong status', $stats['wrong_status'], round(($stats['wrong_status'] / $totalSends) * 100, 1) . '%'],
            ['⚠️  Missing sent_at', $stats['missing_sent_at'], round(($stats['missing_sent_at'] / $totalSends) * 100, 1) . '%'],
        ]
    );
    $this->newLine();

    $totalIssues = count($issues);

    if ($totalIssues === 0) {
        $this->info('✨ Perfect! All sent statuses are consistent!');
        return 0;
    }

    // ✅ STEP 4: Display issues
    $this->warn("⚠️  Found {$totalIssues} inconsistencies:");
    $this->newLine();

    if ($totalIssues <= 20) {
        $this->table(
            ['Type', 'Delivery Order', 'Customer', 'Issue/Details'],
            array_map(function($issue) {
                return [
                    $issue['type'],
                    $issue['delivery'],
                    substr($issue['customer'], 0, 25),
                    $issue['current_status'] ?? $issue['issue'] ?? 'Missing'
                ];
            }, $issues)
        );
    } else {
        $this->table(
            ['Type', 'Delivery Order', 'Customer', 'Issue/Details'],
            array_map(function($issue) {
                return [
                    $issue['type'],
                    $issue['delivery'],
                    substr($issue['customer'], 0, 25),
                    $issue['current_status'] ?? $issue['issue'] ?? 'Missing'
                ];
            }, array_slice($issues, 0, 20))
        );
        $this->info("... and " . ($totalIssues - 20) . " more issues");
    }
    $this->newLine();

    // ✅ STEP 5: Check mode or Fix mode
    if ($this->option('check')) {
        $this->warn('🔍 Check-only mode. Use without --check to fix these issues.');
        return 0;
    }

    // ✅ STEP 6: Confirm and fix
    if (!$this->option('force')) {
        if (!$this->confirm("Do you want to fix {$totalIssues} inconsistencies?")) {
            $this->warn('❌ Fix cancelled by user');
            return 0;
        }
    }

    $this->info('🔧 Fixing inconsistencies...');
    $this->newLine();

    $bar = $this->output->createProgressBar($totalIssues);
    $bar->start();

    foreach ($issues as $issue) {
        try {
            $emailLog = EmailSentLog::where('delivery_order', $issue['delivery'])
                ->where('customer_name', $issue['customer'])
                ->where('send_status', 'success')
                ->orderBy('sent_at', 'desc')
                ->first();

            if ($emailLog) {
                BillingStatus::updateOrCreate(
                    [
                        'delivery_order' => $issue['delivery'],
                        'customer_name' => $issue['customer']
                    ],
                    [
                        'status' => 'sent',
                        'sent_at' => $emailLog->sent_at,
                        'email_sent_at' => $emailLog->sent_at,
                        'sent_by' => $emailLog->sent_by,
                        'sent_to_buyer' => true,
                        'notes' => $emailLog->notes,
                        'updated_at' => now()
                    ]
                );

                $stats['fixed']++;
                
                Log::info('✅ Synced sent status from email log', [
                    'delivery_order' => $issue['delivery'],
                    'customer_name' => $issue['customer'],
                    'sent_at' => $emailLog->sent_at,
                    'source' => 'email_sent_logs'
                ]);
            }
        } catch (\Exception $e) {
            $stats['failed']++;
            
            Log::error('❌ Failed to sync sent status', [
                'delivery_order' => $issue['delivery'],
                'customer_name' => $issue['customer'],
                'error' => $e->getMessage()
            ]);
        }

        $bar->advance();
    }

    $bar->finish();
    $this->newLine(2);

    // ✅ STEP 7: Final summary
    $this->info('');
    $this->info('╔═══════════════════════════════════════════════╗');
    $this->info('║            SYNC COMPLETE                      ║');
    $this->info('╚═══════════════════════════════════════════════╝');
    $this->info('');
    $this->table(
        ['Result', 'Count'],
        [
            ['✅ Fixed Successfully', $stats['fixed']],
            ['❌ Failed to Fix', $stats['failed']],
            ['📧 Total Email Sends', $totalSends],
            ['✓ Already Correct', $stats['correct']],
        ]
    );
    $this->newLine();

    if ($stats['fixed'] > 0) {
        $this->info("✨ Successfully synced {$stats['fixed']} sent statuses!");
    }

    if ($stats['failed'] > 0) {
        $this->error("⚠️  {$stats['failed']} records failed to sync. Check logs for details.");
    }

    return 0;
}
}
