<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                http_response_code(401);
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//


            require_once('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['OLD_PASSWORD', 'NEW_PASSWORD'];  // Define required fields

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
            $OLD_PASSWORD = $validator->get('OLD_PASSWORD');   // Retrieve sanitized inputs
            $NEW_PASSWORD = $validator->get('NEW_PASSWORD');   // Retrieve sanitized inputs
            $RML_ID = $checkValidTokenData['data']->data->RML_ID;
            $ENTRY_BY = $RML_ID;
            // Hash the passwords using MD5 in PHP
            $OLD_PASSWORD_MD5 = strtoupper(md5($OLD_PASSWORD));
            $NEW_PASSWORD_MD5 = strtoupper(md5($NEW_PASSWORD));

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "SELECT PASS_MD5,RML_ID FROM RML_HR_APPS_USER WHERE RML_ID = '$RML_ID'";
                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $objResultFound = @oci_fetch_assoc($strSQL);
                $prevousPassword = $objResultFound['PASS_MD5'];
                if ($prevousPassword === $OLD_PASSWORD_MD5) {
                    $SQL2 = "UPDATE RML_HR_APPS_USER SET PASS_MD5 = '$NEW_PASSWORD_MD5' WHERE RML_ID = '$RML_ID'";
                    $strSQL2 = @oci_parse($objConnect, $SQL2);
                    if (@oci_execute($strSQL2)) {
                        http_response_code(200);
                        $jsonData = [
                            "status" => true,
                            "message" => 'Successfully Password Change.'
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
                } else {
                    http_response_code(response_code: 400);
                    $jsonData = [
                        "status" => false,
                        "message" => 'Old password is not match!'
                    ];
                    echo json_encode($jsonData);
                    die();
                }

            } catch (Exception $e) {
                http_response_code(500);
                $jsonData = ["status" => false, "message" => $e->getMessage()];
                echo json_encode($jsonData);
            } finally {
                oci_close($objConnect);
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
