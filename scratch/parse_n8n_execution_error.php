<?php

use App\Services\AppSettingsService;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settingsService = app(AppSettingsService::class);
$n8nKey = $settingsService->get('ai.n8n.api_key');

if (!$n8nKey) {
    echo "No API key found.\n";
    exit(1);
}

$executionId = '82';
if (isset($argv[1])) {
    $executionId = $argv[1];
}

$url = "https://n8n.smkmustaqbal.sch.id/api/v1/executions/{$executionId}?includeData=true";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-N8N-API-KEY: ' . $n8nKey,
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($status === 200) {
    $data = json_decode($response, true);
    
    // Check if there is an error in execution
    if (isset($data['data']['resultData']['error'])) {
        echo "=== EXECUTION ERROR ===\n";
        echo json_encode($data['data']['resultData']['error'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "No error found in root resultData. Checking runData...\n";
    }

    if (isset($data['data']['resultData']['runData'])) {
        echo "=== RUN DATA NODES ===\n";
        foreach ($data['data']['resultData']['runData'] as $nodeName => $runs) {
            foreach ($runs as $index => $run) {
                if (isset($run['error'])) {
                    echo "Node [{$nodeName}] run [{$index}] failed:\n";
                    echo json_encode($run['error'], JSON_PRETTY_PRINT) . "\n";
                } else {
                    echo "Node [{$nodeName}] run [{$index}] succeeded.\n";
                }
            }
        }
    }
} else {
    echo "Error response: " . $response . "\n";
}
