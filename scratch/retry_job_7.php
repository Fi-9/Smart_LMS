<?php

use App\Jobs\ProcessBookScanJob;
use App\Models\ScanJob;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobId = 7;
$scanJob = ScanJob::find($jobId);

if (!$scanJob) {
    echo "Scan job $jobId not found.\n";
    exit(1);
}

// Reset job status to waiting/processing
$scanJob->update([
    'status' => 'waiting',
    'error_message' => null,
    'started_at' => null,
    'finished_at' => null,
    'current_stage' => 'identification',
    'stage_status' => 'waiting',
    'stage_message' => null
]);

echo "Running ProcessBookScanJob for job ID $jobId...\n";

try {
    $job = new ProcessBookScanJob($jobId);
    app()->call([$job, 'handle']);
    echo "Job finished successfully!\n";
} catch (Throwable $e) {
    echo "Job failed with error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
