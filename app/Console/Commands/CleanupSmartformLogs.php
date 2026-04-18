<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanupSmartformLogs extends Command
{
    protected $signature = 'smartform:cleanup-logs {--days=30 : Keep logs for N days}';
    protected $description = 'Cleanup old smartform auto-upload logs';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);
        
        $deleted = DB::table('smartform_auto_upload_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();
        
        $this->info("🗑️  Deleted {$deleted} old log entries (older than {$days} days)");
        
        return 0;
    }
}