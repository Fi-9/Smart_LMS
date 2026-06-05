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

$workflows = ['xRZhptq03pAwX3qR', 'Axx4ZObs0Z7aqvns', 'UAh0WJFMmktYaEsO'];
foreach ($workflows as $workflowId) {
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

    echo "=== Workflow ID: $workflowId (Status: $status) ===\n";
    if ($status === 200) {
        $data = json_decode($response, true);
        echo "Workflow Name: " . ($data['name'] ?? 'Unknown') . "\n";
        $nodes = $data['nodes'] ?? [];
        foreach ($nodes as $node) {
            echo "  Node: {$node['name']} (Type: {$node['type']})\n";
            if ($node['type'] === 'n8n-nodes-base.webhook') {
                echo "    Webhook path: " . ($node['parameters']['path'] ?? 'none') . "\n";
            }
        }
    } else {
        echo "Error response: " . $response . "\n";
    }
}

