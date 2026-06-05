<?php

use App\Services\AppSettingsService;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settingsService = app(AppSettingsService::class);
$n8nKey = $settingsService->get('ai.n8n.api_key');

if ($n8nKey) {
    $masked = substr($n8nKey, 0, 4) . str_repeat('*', strlen($n8nKey) - 8) . substr($n8nKey, -4);
    echo "Decrypted Key (masked): $masked\n";
    
    // Let's test the key against n8n REST API
    $ch = curl_init('https://n8n.smkmustaqbal.sch.id/api/v1/workflows');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'X-N8N-API-KEY: ' . $n8nKey,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "n8n API Status: $status\n";
    if ($status === 200) {
        echo "API key is VALID!\n";
    } else {
        echo "API key is INVALID! Response: $response\n";
    }
} else {
    echo "No API key found in app_settings.\n";
}
