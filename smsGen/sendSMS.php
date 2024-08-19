<?php
function sendSms($mobileNumbers)
{
    // sms config key
    $config = require_once('./inc/config.php');
    $API_KEY = $config['API_KEY'];
    $Client_ID = $config['Client_ID'];
    $SenderID = $config['SenderID'];
    // sms config key


    // get  api signature

    // Define the API URL
    $apiUrl = 'https://api.smsq.global/api/v2/SendSMS';

    // Define the parameters
    $params = [
        'ApiKey' => $API_KEY,
        'ClientId' => $Client_ID,
        'SenderId' => $SenderID,
        'Message' => generateOtp(),
        'MobileNumbers' => $mobileNumbers,
    ];

    // Build the query string from the parameters
    $queryString = http_build_query($params);

    // Initialize cURL
    $ch = curl_init();

    // Set the cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '?' . $queryString);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Set to true to return the response as a string

    // Execute the request
    $response = curl_exec($ch);

    // Check for errors
    if (curl_errno($ch)) {
        $status = false;
        $error = 'cURL Error: ' . curl_error($ch);
    } else {
        $status = true;
        $error = null;
    }

    // Close the cURL session
    curl_close($ch);

    // Return the result
    return [
        'status' => $status,
        'response' => $response,
        'error' => $error,
    ];
}

/**
 * Generates a random 6-digit OTP.
 *
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