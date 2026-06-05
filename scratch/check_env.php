<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "AI_RUNTIME_PROFILE: " . env('AI_RUNTIME_PROFILE', 'n8n-gemini') . "\n";
echo "N8N_BASE_URL: " . env('N8N_BASE_URL') . "\n";
echo "N8N_WEBHOOK_SMARTLMS_VISION: " . env('N8N_WEBHOOK_SMARTLMS_VISION') . "\n";
$key = env('GEMINI_API_KEY');
echo "GEMINI_API_KEY (masked): " . ($key ? substr($key, 0, 8) . "..." : 'not set') . "\n";

$keysStr = env('GEMINI_API_KEYS');
if ($keysStr) {
    $keys = explode(',', $keysStr);
    echo "GEMINI_API_KEYS pool has " . count($keys) . " key(s):\n";
    foreach ($keys as $idx => $k) {
        echo "  [$idx]: " . substr(trim($k), 0, 8) . "...\n";
    }
} else {
    echo "GEMINI_API_KEYS is not set or empty.\n";
}

echo "TAVILY_API_KEY (masked): " . (env('TAVILY_API_KEY') ? substr(env('TAVILY_API_KEY'), 0, 8) . "..." : 'not set') . "\n";
