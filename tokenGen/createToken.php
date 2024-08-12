<?php
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;

$config = require_once('./inc/config.php');
$secret_key = $config['jwt_token'];


function generate_jwt_token($data)
{
    global $secret_key;
    $issued_at = time();
    // $expiration_time = $issued_at + (60 * 60); // valid for 1 hour
    $expiration_time = $issued_at + (24 * 60 * 60); // valid for 1 day

    $payload = array(
        'iss' => 'rangsmotors',
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'data' => $data
    );

    return JWT::encode($payload, $secret_key, 'HS256');
}
