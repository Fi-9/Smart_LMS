<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$jobId = 7;
echo "=== Scan Job ID: $jobId ===\n";
$job = DB::table('scan_jobs')->where('id', $jobId)->first();
if ($job) {
    echo json_encode($job, JSON_PRETTY_PRINT) . "\n";
} else {
    echo "Job not found.\n";
}

echo "\n=== Latest 5 Scan Pipeline Logs ===\n";
$latestLogs = DB::table('scan_pipeline_logs')->orderBy('created_at', 'desc')->limit(5)->get();
foreach ($latestLogs as $log) {
    echo "[{$log->created_at}] scan_id={$log->scan_id} [{$log->provider}] [{$log->status}] duration={$log->duration_ms}ms\n";
    if ($log->error) {
        echo "  Error: {$log->error}\n";
    }
    echo "--------------------------------------------------\n";
}
