<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$keysString = config('services.gemini.api_keys');
$keys = array_values(array_filter(array_map('trim', explode(',', $keysString))));

echo "Total Gemini API Keys in pool: " . count($keys) . "\n";
foreach ($keys as $i => $key) {
    $masked = substr($key, 0, 4) . '...' . substr($key, -4);
    $blocked = \Illuminate\Support\Facades\Cache::has("gemini_key_blocked:" . md5($key));
    echo "Key #$i: $masked " . ($blocked ? "[BLOCKED]" : "[ACTIVE]") . "\n";
}

$singleKey = config('services.gemini.api_key');
if ($singleKey) {
    $maskedSingle = substr($singleKey, 0, 4) . '...' . substr($singleKey, -4);
    echo "Single default key: $maskedSingle\n";
} else {
    echo "No default single key.\n";
}
