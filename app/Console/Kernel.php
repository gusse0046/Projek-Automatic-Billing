<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ========================================
        // SAP RFC SYNC (Daily at 06:00)
        // ========================================
        // Sync SAP billing data to database cache
        // This runs BEFORE auto-upload to ensure fresh data
        $schedule->command('sap:sync-to-db --daily --endpoint=fast')
                 ->dailyAt('06:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/sap-rfc-sync.log'));

        // ========================================
        // BATCH AUTO-UPLOAD ALL BUYERS
        // ========================================
        // Auto-upload documents for ALL buyers at once
        // Same as clicking "Auto" button for each buyer in dashboard
        // Scans Z:\sd folder and uploads matching files
        
        // First run at 07:00 (1 hour after SAP sync)
        $schedule->command('auto:upload-all')
                 ->dailyAt('07:00')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/auto-upload-all.log'));

        // Optional: Second run at 08:30 for late-arriving files
        $schedule->command('auto:upload-all')
                 ->dailyAt('08:30')
                 ->withoutOverlapping()
                 ->runInBackground()
                 ->appendOutputTo(storage_path('logs/auto-upload-all.log'));

        // ========================================
        // LOG CLEANUP (Monthly)
        // ========================================
        // Rotate large log files to prevent disk space issues
        $schedule->call(function () {
            $logFiles = [
                'sap-rfc-sync.log',
                'auto-upload-all.log'
            ];
            
            foreach ($logFiles as $logFile) {
                $fullPath = storage_path('logs/' . $logFile);
                
                // If log file exceeds 50MB, backup and clear it
                if (file_exists($fullPath) && filesize($fullPath) > 50 * 1024 * 1024) {
                    $backupPath = $fullPath . '.backup.' . date('Y-m-d');
                    copy($fullPath, $backupPath);
                    file_put_contents($fullPath, '');
                    \Log::info("Cleared large log file: {$logFile}");
                }
            }
        })->monthly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}