<?php
$apiKey = 'nvFmb2SW5aHM4rJQRe7ukjqzbnXuEE1k+ijPAvq/3wA=';
$clientId = 'b1a9a16e-2bbe-4939-8a27-8fec496b1925';
$senderId = '8809617601212';
$message = 'test';
$mobileNumbers = '8801705102555';

// Encode parameters
$url = "https://api.smsq.global/api/v2/SendSMS?ApiKey=" . urlencode($apiKey) . "&ClientId=" . urlencode($clientId) . "&SenderId=" . urlencode($senderId) . "&Message=" . urlencode($message) . "&MobileNumbers=" . urlencode($mobileNumbers);

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    echo 'Response:' . $response;
}

curl_close($ch);
?>
