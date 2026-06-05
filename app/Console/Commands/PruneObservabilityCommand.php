<?php

namespace App\Console\Commands;

use App\Models\ScanJob;
use App\Models\ScanPipelineLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class PruneObservabilityCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:prune-observability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old scan jobs (90 days) and pipeline logs (30 days) for database maintenance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting observability data pruning...');

        $jobsRetentionDate = Carbon::now()->subDays(90);
        $logsRetentionDate = Carbon::now()->subDays(30);

        try {
            // Prune Scan Jobs
            $prunedJobs = ScanJob::where('created_at', '<', $jobsRetentionDate)->delete();
            $this->info("Pruned {$prunedJobs} scan jobs older than {$jobsRetentionDate->toDateString()}");

            // Prune Scan Pipeline Logs
            $prunedLogs = ScanPipelineLog::where('created_at', '<', $logsRetentionDate)->delete();
            $this->info("Pruned {$prunedLogs} pipeline logs older than {$logsRetentionDate->toDateString()}");

            Log::channel('ai_scan')->info('Observability pruning completed.', [
                'pruned_jobs' => $prunedJobs,
                'pruned_logs' => $prunedLogs,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to prune observability data: ' . $e->getMessage());
            Log::error('Prune observability failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
