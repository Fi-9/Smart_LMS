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

// 1. Get current workflow JSON
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

if ($status !== 200) {
    echo "Failed to get workflow. Status: $status. Response: $response\n";
    exit(1);
}

$data = json_decode($response, true);

// 2. Modify nodes to use gemini-1.5-flash
$modified = false;
foreach ($data['nodes'] as &$node) {
    if ($node['name'] === 'Call Gemini Vision' && isset($node['parameters']['url'])) {
        $oldUrl = $node['parameters']['url'];
        if (strpos($oldUrl, 'gemini-2.5-flash') !== false) {
            $node['parameters']['url'] = str_replace('gemini-2.5-flash', 'gemini-1.5-flash', $oldUrl);
            echo "Updating Call Gemini Vision URL: $oldUrl -> {$node['parameters']['url']}\n";
            $modified = true;
        }
    }
}

if (!$modified) {
    echo "No matching node using gemini-2.5-flash found or already modified.\n";
} else {
    echo "Current settings: " . json_encode($data['settings'] ?? []) . "\n";
    
    // Clean settings to only allowed fields or remove it
    $body = [
        'name' => $data['name'],
        'nodes' => $data['nodes'],
        'connections' => $data['connections'],
        'settings' => [
            'executionOrder' => 'v1'
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-N8N-API-KEY: ' . $n8nKey,
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $putResponse = curl_exec($ch);
    $putStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Update Status without settings: $putStatus\n";
    if ($putStatus === 200) {
        echo "Workflow updated successfully!\n";
    } else {
        echo "Update failed. Response: $putResponse\n";
    }
}
