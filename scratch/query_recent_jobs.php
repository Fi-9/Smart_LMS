<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$jobs = DB::table('scan_jobs')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo json_encode($jobs, JSON_PRETTY_PRINT) . "\n";
