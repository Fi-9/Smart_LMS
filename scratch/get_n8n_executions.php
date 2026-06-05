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

// We want to list executions for workflow xRZhptq03pAwX3qR
$url = "https://n8n.smkmustaqbal.sch.id/api/v1/executions?workflowId=xRZhptq03pAwX3qR&limit=5";

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
    $executions = $data['data'] ?? [];
    echo "Found " . count($executions) . " executions.\n";
    foreach ($executions as $exec) {
        echo "Execution ID: {$exec['id']} | Status: {$exec['status']} | Started At: {$exec['startedAt']}\n";
        
        $detailsUrl = "https://n8n.smkmustaqbal.sch.id/api/v1/executions/{$exec['id']}?includeData=true";
        $chD = curl_init($detailsUrl);
        curl_setopt($chD, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chD, CURLOPT_HTTPHEADER, [
            'X-N8N-API-KEY: ' . $n8nKey,
            'Accept: application/json'
        ]);
        curl_setopt($chD, CURLOPT_SSL_VERIFYPEER, false);
        $resD = curl_exec($chD);
        $statusD = curl_getinfo($chD, CURLINFO_HTTP_CODE);
        curl_close($chD);

        if ($statusD === 200) {
            $execData = json_decode($resD, true);
            $data = $execData['data'] ?? [];
            $resultData = $data['resultData'] ?? [];
            $runData = $resultData['runData'] ?? [];
            
            if (isset($resultData['error'])) {
                echo "  Error: " . $resultData['error']['message'] . " (Node: " . ($resultData['error']['node']['name'] ?? 'unknown') . ")\n";
            }
            
            $nodesExecuted = array_keys($runData);
            echo "  Executed Nodes: " . implode(', ', $nodesExecuted) . "\n";
            
            if (isset($runData['Respond'])) {
                $respondData = $runData['Respond'][0]['data']['main'][0]['json'] ?? [];
                echo "  Respond output: " . json_encode($respondData) . "\n";
            }
        }
        echo "--------------------------------------------------\n";
    }
} else {
    echo "Error response: " . $response . "\n";
}
