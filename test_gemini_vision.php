<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$apiKey = config('services.gemini.api_key');
$model = 'gemini-2.5-flash';

if (empty($apiKey)) {
    echo "FAIL: GEMINI_API_KEY is not configured in .env\n";
    exit(1);
}

echo "Using Model: $model\n";
echo "API Key (first 4): " . substr($apiKey, 0, 4) . "...\n";

$payload = [
    'contents' => [[
        'parts' => [
            ['text' => "Hello, tell me the name of the author of Laskar Pelangi. Return only the name."],
        ],
    ]],
    'generationConfig' => ['temperature' => 0, 'maxOutputTokens' => 50],
];

$ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}");
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "FAIL (curl): $error\n";
    exit(1);
}

echo "HTTP Code: $httpCode\n";
if ($httpCode !== 200) {
    echo "Response: $response\n";
    exit(1);
}

$data = json_decode($response, true);
$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'NO TEXT';
echo "Gemini response: " . trim($text) . "\n";
