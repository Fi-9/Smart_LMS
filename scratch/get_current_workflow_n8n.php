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

$workflowId = 'xRZhptq03pAwX3qR';
$url = "https://n8n.smkmustaqbal.sch.id/api/v1/workflows/{$workflowId}";

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

echo "Status: $status\n";
if ($status === 200) {
    $data = json_decode($response, true);
    
    // Find Call Gemini Vision node
    $nodes = $data['nodes'] ?? [];
    foreach ($nodes as $node) {
        if ($node['name'] === 'Call Gemini Vision') {
            echo "=== CALL GEMINI VISION NODE ===\n";
            echo json_encode($node, JSON_PRETTY_PRINT) . "\n";
        }
        if ($node['name'] === 'Tavily Websearch') {
            echo "=== TAVILY WEBSEARCH NODE ===\n";
            $headers = $node['parameters']['headerParameters']['parameters'] ?? [];
            foreach ($headers as $h) {
                if ($h['name'] === 'Authorization') {
                    echo "Authorization Header Value: " . $h['value'] . "\n";
                }
            }
        }
    }
} else {
    echo "Error: " . $response . "\n";
}
