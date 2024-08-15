<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once ("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once ('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//


            require_once ('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['LOC_LAT', 'LOC_LANG', 'ENTRY_TIME'];  // Define required fields

            // Initialize input validator with POST data **//
            $validator = new InputValidator($_POST);
            if (!$validator->validateRequired($requiredFields)) {
                // Set the HTTP status code to 400 Bad Request
                http_response_code(400);
                $jsonData = ["status" => false, "message" => "Missing Required Parameters."];
                echo json_encode($jsonData);
                die();
            }


            // **Initialize input validator with POST Data**//

            $validator->sanitizeInputs();   // Sanitize Inputs
            $LOC_LAT = $validator->get('LOC_LAT');   // Retrieve sanitized inputs
            $LOC_LANG = $validator->get('LOC_LANG');   // Retrieve sanitized inputs
            $ENTRY_TIME = $validator->get('ENTRY_TIME');   // Retrieve sanitized inputs
            $RML_ID = $checkValidTokenData['data']->data->RML_ID;
            $ENTRY_BY = $RML_ID;

            // Regular expression for date format validation: DD/MM/YYYY HH:MI:SS AM/PM
            $pattern = '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2} (AM|PM)$/';
            if (!preg_match($pattern, $ENTRY_TIME)) {
                http_response_code(400); // Bad Request
                $jsonData = ['status' => false, 'message' => 'Date format is not valid!'];
                echo json_encode($jsonData);
                die();
            }
            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "INSERT INTO RML_HR_APPS_USER_LOCATION
                        (RML_ID, LOC_LAT, LOC_LANG, ENTRY_TIME)
                        VALUES ('$RML_ID', '$LOC_LAT', '$LOC_LANG',
                        TO_DATE('$ENTRY_TIME', 'DD/MM/YYYY HH:MI:SS AM'))";

                $strSQL = @oci_parse($objConnect, $SQL);
                if (@oci_execute($strSQL)) {
                    http_response_code(200);
                    $jsonData = [
                        "status" => true,
                        "message" => 'Successfully Location Entry.'
                    ];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(500);
                    @$lastError = error_get_last();
                    @$error = $lastError ? "" . $lastError["message"] . "" : "";
                    $str_arr_error = preg_split("/\,/", $error);
                    $jsonData = ["status" => false, "message" => @$error];
                    echo json_encode($jsonData);
                }
            } catch (Exception $e) {
                http_response_code(500);
                $jsonData = ["status" => false, "message" => $e->getMessage()];
                echo json_encode($jsonData);
            } finally {
                @oci_close($objConnect);
            }
            //*** End Query & Return Data Response ***//
        } else {
            // Set the HTTP status code to 400 Bad Request
            http_response_code(400);
            $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
            echo json_encode($jsonData);
        }
    }
} else {
    http_response_code(405);
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();
