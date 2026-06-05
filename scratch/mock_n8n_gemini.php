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

// 2. Modify "Call Gemini Vision" node to be a Code node
$modified = false;
foreach ($data['nodes'] as &$node) {
    if ($node['name'] === 'Call Gemini Vision') {
        $node['type'] = 'n8n-nodes-base.code';
        $node['typeVersion'] = 2;
        $node['parameters'] = [
            'jsCode' => "return [{\n  json: {\n    candidates: [{\n      content: {\n        parts: [\n          { text: '{\\n  \"isbn\": \"9786020626314\",\\n  \"title\": \"Atomic Habits\",\\n  \"author\": \"James Clear\",\\n  \"publisher\": \"Gramedia Pustaka Utama\",\\n  \"category\": \"Self Development\"\\n}' }\n        ]\n      }\n    }]\n  }\n}];"
        ];
        echo "Successfully modified Call Gemini Vision node to return mock JSON.\n";
        $modified = true;
    }
}

if (!$modified) {
    echo "Could not find node 'Call Gemini Vision'\n";
    exit(1);
}

// 3. Save workflow back to n8n
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'name' => $data['name'],
    'nodes' => $data['nodes'],
    'connections' => $data['connections'],
    'settings' => [
        'executionOrder' => 'v1'
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-N8N-API-KEY: ' . $n8nKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$putResponse = curl_exec($ch);
$putStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Update Status: $putStatus\n";
if ($putStatus === 200) {
    echo "Workflow updated with Mock Gemini successfully!\n";
} else {
    echo "Update failed. Response: $putResponse\n";
}
