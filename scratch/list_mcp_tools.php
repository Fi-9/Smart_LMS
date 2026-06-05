<?php

$token = "Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiJmZWI3ZjViNi1mN2M3LTQwYjgtOTcxOS0zOGFlN2IyOGZmYWMiLCJpc3MiOiJuOG4iLCJhdWQiOiJtY3Atc2VydmVyLWFwaSIsImp0aSI6IjQzOWQzMzMzLWRkYmMtNDVhMS05ZWZhLWM1OWZmMTBhYTUyMyIsImlhdCI6MTc4MDYzMDE5NH0.nXCVnX1KkcxOfwBDrFOFxz_XU25te5Yu8q90ABrwEQQ";

$payload = [
    'jsonrpc' => '2.0',
    'id' => 1,
    'method' => 'tools/list'
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
