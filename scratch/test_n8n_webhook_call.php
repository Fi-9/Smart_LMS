<?php

use App\Services\AppSettingsService;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settingsService = app(AppSettingsService::class);
$n8nKey = $settingsService->get('ai.n8n.api_key');
$geminiKey = $settingsService->get('ai.gemini.api_key', config('services.gemini.api_key'));
$tavilyKey = $settingsService->get('ai.websearch.tavily_api_key', config('services.tavily.api_key'));

$url = 'https://n8n.smkmustaqbal.sch.id/webhook/smartlms-vision-v3';

// Send request to n8n webhook with real keys
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'image' => new CURLFile(__DIR__ . '/../storage/app/public/book-scans/front_af1b1fb5-81f5-4296-a6ef-5c5eb5e47d25.jpg', 'image/jpeg', 'front_af1b1fb5-81f5-4296-a6ef-5c5eb5e47d25.jpg')
]);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-N8N-API-KEY: ' . $n8nKey,
    'X-Gemini-API-Key: ' . $geminiKey,
    'X-Tavily-API-Key: ' . $tavilyKey
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
}
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $status\n";
echo "Response Body: $response\n";
