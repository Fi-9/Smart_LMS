<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\OllamaService::class);
$text = "Keep Working, Keep Playing, Keep Creating. Austin Kleon offers ten rules for how to stay creative and true to yourself.";

try {
    $translated = $service->translateTextToIndonesian($text);
    echo "Translated: " . $translated . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
