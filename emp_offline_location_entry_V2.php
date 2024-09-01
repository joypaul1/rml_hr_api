<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    //$checkValidTokenData = require_once("checkValidTokenData.php");
    //if ($checkValidTokenData['status']) {
        ///if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//

            require_once('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['LIST_LOCATION', 'RML_ID'];  // Define required fields

            // Initialize input validator with POST data **//
            $validator = new InputValidator($_POST);
            if (!$validator->validateRequired($requiredFields)) {
                http_response_code(400);
                $jsonData = ["status" => false, "message" => "Missing Required Parameters."];
                echo json_encode($jsonData);
                die();
            }

            $validator->sanitizeInputs();   // Sanitize Inputs
            $RML_ID = $validator->get('RML_ID');   // Retrieve sanitized inputs

            //*** Start Query & Return Data Response ***//
            try {
                $dataArray = json_decode($_POST['LIST_LOCATION'], true); // Accessing data in PHP

                foreach ($dataArray as $item) {
                    $LOC_LAT        = $item['LAT'];
                    $LOC_LANG       = $item['LANG'];
                    $ENTRY_TIME     = $item['ENTRY_TIME'];
                    $BATTERY_LEVEL  = isset($item['BATTERY_LEVEL']) ? $item['BATTERY_LEVEL'] : '';
                    $APPS_VERSION   = isset($item['APPS_VERSION']) ? $item['APPS_VERSION'] : '';

                    // Regular expression for date format validation: DD/MM/YYYY HH:MI:SS AM/PM
                    $pattern = '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2} (AM|PM)$/';
                    if (!preg_match($pattern, $ENTRY_TIME)) {
                        http_response_code(400); // Bad Request
                        $jsonData = ['status' => false, 'message' => 'Date format is not valid!'];
                        echo json_encode($jsonData);
                        die();
                    }

                    // Correct the SQL query by fixing the date format
                    $SQL = "INSERT INTO RML_HR_APPS_USER_LOCATION
                        (RML_ID, LOC_LAT, LOC_LANG, BATTERY_LEVEL, ENTRY_TIME, APPS_VERSION)
                        VALUES ('$RML_ID', '$LOC_LAT', '$LOC_LANG', '$BATTERY_LEVEL',
                        TO_DATE('$ENTRY_TIME', 'DD/MM/YYYY HH:MI:SS AM'), '$APPS_VERSION')";

                    $strSQL = @oci_parse($objConnect, $SQL);
                    if (@oci_execute($strSQL)) {
                        // Success, continue to the next iteration
                    } else {
                        http_response_code(200);
                        $lastError = error_get_last();
                        $error = $lastError ? $lastError["message"] : "";
                        $jsonData = ["status" => false, "message" => $error];
                        echo json_encode($jsonData);
                        die();
                    }
                }
                http_response_code(200);
                $jsonData = [
                    "status" => true,
                    "message" => 'Successfully Location Entry.'
                ];
                echo json_encode($jsonData);

            } catch (Exception $e) {
                http_response_code(500);
                $jsonData = ["status" => false, "message" => $e->getMessage()];
                echo json_encode($jsonData);
            } finally {
                @oci_close($objConnect);
            }
            //*** End Query & Return Data Response ***//
        // } else {
        //     http_response_code(400);
        //     $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
        //     echo json_encode($jsonData);
        // }
    // } else {
    //     http_response_code(400);
    //     $jsonData = ["status" => false, "message" => "Invalid Token."];
    //     echo json_encode($jsonData);
    // }
} else {
    http_response_code(405);
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();
