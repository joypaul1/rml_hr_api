<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
$RML_ID = $START_DATE   =   $END_DATE = null;

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//


            require_once('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['START_DATE', 'END_DATE', 'LEAVE_TYPE'];  // Define required fields

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
            $START_DATE     = $validator->get('START_DATE');   // Retrieve sanitized inputs
            $END_DATE       = $validator->get('END_DATE');   // Retrieve sanitized inputs
            $LEAVE_TYPE     = $validator->get('LEAVE_TYPE');   // Retrieve sanitized inputs
            $RML_ID         = $checkValidTokenData['data']->data->RML_ID;
            $LEAVE_REMARKS  = $_POST['LEAVE_REMARKS']? str_replace("'", "''", $_POST['LEAVE_REMARKS'] ?? ' '):' ';
            $ENTRY_BY       = $RML_ID;

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "BEGIN RML_HR_LEAVE_CREATE('$RML_ID','$START_DATE','$END_DATE','$LEAVE_REMARKS','$LEAVE_TYPE','$ENTRY_BY');END;";

                $strSQL = @oci_parse($objConnect, $SQL);
                if (@oci_execute($strSQL)) {
                    http_response_code(200);
                    $jsonData = [
                        "status" => true,
                        "message" => 'Your leave successfully created. It must be approved by your responsible line manager and department head.'
                    ];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(500);
                    @$lastError = error_get_last();
                    @$error = $lastError ? "" . $lastError["message"] . "" : "";
                    $str_arr_error = preg_split("/\,/", $error);
                    $jsonData = ["status" => false,  "message" => @$error ];
                    echo json_encode($jsonData);
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

// START NOTIFICATION CONFIGURATION

include_once('./firebase_noti/lm_noti.php'); // INCLUDE FIREBASE NOTI FILE

// SQL QUERY //
$SQL = "SELECT A.RML_ID,A.EMP_NAME, A.MOBILE_NO ,
(SELECT MOBILE_NO FROM RML_HR_APPS_USER WHERE  RML_ID= A.LINE_MANAGER_RML_ID) AS LM_MOBILE_NO
(SELECT FIRE_BASE_ID FROM RML_HR_APPS_USER WHERE  RML_ID= A.LINE_MANAGER_RML_ID) AS LM_FKEY
FROM RML_HR_APPS_USER A
WHERE A.RML_ID ='$RML_ID'
AND A.IS_ACTIVE = 1";
// SQL QUERY  //

$objResultFound=[];
$strSQL = @oci_parse($objConnect, $SQL);
if (@oci_execute($strSQL)) {
    $objResultFound = @oci_fetch_assoc($strSQL);
    sendNotification($RML_ID, $objResultFound['EMP_NAME'], $objResultFound['LM_FKEY'], 'leave', $START_DATE, $END_DATE);
}
// END NOTIFICATION CONFIGURATION

die();
