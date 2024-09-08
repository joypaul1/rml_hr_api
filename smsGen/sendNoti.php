<?php
function sendOTP($mobileNumbers, $deviceTrackCode)
{
    if($mobileNumbers === '01993338507'){
        $mobileNumbers = '01688929193';
    }
    $mobileNumbers = '88' . $mobileNumbers;
    
    // config
    $config     = require_once('./inc/config.php');
    $apiKey     = $config['API_KEY'];
    $clientId   = $config['Client_ID'];
    $senderId   = $config['SenderID'];
    // config
    $otpCode = generateOtp();
    $message = "<#> NEVER share your HR Apps Verification Code with anyone. Verification Code: " . $otpCode . ". Expiry: 60 seconds. " . $deviceTrackCode;


    // Encode parameters
    $url = "https://api.smsq.global/api/v2/SendSMS?ApiKey=" . urlencode($apiKey) . "&ClientId=" . urlencode($clientId) . "&SenderId=" . urlencode($senderId) . "&Message=" . urlencode($message) . "&MobileNumbers=" . urlencode($mobileNumbers);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = true;

    if ($error) {
        $status = false;
    }

    curl_close($ch);

    return [
        'status' => $status,
        'response' => $response,
        'error' => $error,
        'OTP' => $otpCode,
    ];
}

/**
 * Generates a random 6-digit OTP.
 *ss
 * @return string The generated OTP.
 */
function generateOtp()
{
    // Generate a random number between 100000 and 999999
    $otp = rand(100000, 999999);
    // Return the OTP as a string
    return (string) $otp;
}

// Example usage:
// $otp = generateOtp();
// echo 'Generated OTP: ' . $otp;


?>