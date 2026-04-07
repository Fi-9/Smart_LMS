<?php
if (!file_exists('storage/logs/laravel.log')) {
    exit('Log file not found.');
}
$lines = file('storage/logs/laravel.log');
$recentLines = array_slice($lines, -100);
$found = 0;
foreach ($recentLines as $line) {
    if (
        str_contains(strtolower($line), 'pipeline') ||
        str_contains(strtolower($line), 'translation') ||
        str_contains(strtolower($line), 'translat') ||
        str_contains(strtolower($line), 'ollamaservice')
    ) {
        echo trim($line) . "\n";
        $found++;
    }
}
if ($found === 0) {
    echo "No translation or pipeline logs in the last 100 lines.\n";
}
