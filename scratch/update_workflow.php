<?php

$token = "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJmZWI3ZjViNi1mN2M3LTQwYjgtOTcxOS0zOGFlN2IyOGZmYWMiLCJpc3MiOiJuOG4iLCJhdWQiOiJtY3Atc2VydmVyLWFwaSIsImp0aSI6IjQzOWQzMzMzLWRkYmMtNDVhMS05ZWZhLWM1OWZmMTBhYTUyMyIsImlhdCI6MTc4MDYzMDE5NH0.nXCVnX1KkcxOfwBDrFOFxz_XU25te5Yu8q90ABrwEQQ";
$code = file_get_contents(__DIR__ . '/workflow_code.js');

$payload = [
    'jsonrpc' => '2.0',
    'id' => 107,
    'method' => 'tools/call',
    'params' => [
        'name' => 'update_workflow',
        'arguments' => [
            'workflowId' => 'xRZhptq03pAwX3qR',
            'code' => $code,
            'name' => 'SmartLMS Vision + Websearch Fallback',
            'description' => 'Extract metadata from book cover images with direct Gemini API, parallel book lookup from Google Books and Open Library, and Tavily websearch fallback.'
        ]
    ]
];

$ch = curl_init('https://n8n.smkmustaqbal.sch.id/mcp-server/http');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . $token,
    'Accept: application/json, text/event-stream',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch) . "\n";
} else {
    echo $response . "\n";
}
curl_close($ch);
