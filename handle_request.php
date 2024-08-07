<?php

// Function to get all headers
function get_all_headers() {
    if (!function_exists('getallheaders')) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    } else {
        return getallheaders();
    }
}

// Function to log request details
function log_request($logFile, $requestMethod, $requestUrl, $getParams, $postParams, $fileParams, $headers) {
    $logData = "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    $logData .= "Request URL: " . $requestUrl . "\n";
    $logData .= "Request Method: " . $requestMethod . "\n";
    //$logData .= "GET Parameters: " . json_encode($getParams) . "\n";
    $logData .= "POST Parameters: " . json_encode($postParams) . "\n";
    $logData .= "File Parameters: " . json_encode($fileParams) . "\n";
    $logData .= "Headers: " . json_encode($headers) . "\n";
    $logData .= "-------------------------------------\n";

    file_put_contents($logFile, $logData, FILE_APPEND);
}

// Retrieve request method
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Retrieve GET parameters
$getParams = $_GET;

// Retrieve POST parameters
$postParams = $_POST;

// Retrieve file parameters
$fileParams = $_FILES;

// Retrieve headers
$headers = get_all_headers();

// Construct the request URL
$requestUrl = (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

// Define log file
$logFile = 'request_log.txt';

// Log the request details
log_request($logFile, $requestMethod, $requestUrl, $getParams, $postParams, $fileParams, $headers);

?>
