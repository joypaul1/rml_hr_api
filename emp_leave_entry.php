<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once('../test_api/inc/connoracle.php');
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
                $jsonData = ["status" => "false", "message" => "Missing Required Parameters."];
                echo json_encode($jsonData);
                die();
            }
            // **Initialize input validator with POST Data**//

            $validator->sanitizeInputs();   // Sanitize Inputs
            $START_DATE     = $validator->get('START_DATE');   // Retrieve sanitized inputs
            $END_DATE       = $validator->get('END_DATE');   // Retrieve sanitized inputs
            $LEAVE_TYPE     = $validator->get('LEAVE_TYPE');   // Retrieve sanitized inputs
            $RML_ID         = $checkValidTokenData['data']->data->RML_ID;
            $LEAVE_REMARKS  = $_POST['LEAVE_REMARKS']?$_POST['LEAVE_REMARKS']:' ';
            $ENTRY_BY       = $RML_ID;

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "BEGIN RML_HR_LEAVE_CREATE('$RML_ID','$START_DATE','$END_DATE','$LEAVE_REMARKS','$LEAVE_TYPE','$ENTRY_BY');END;";
                $strSQL = @oci_parse($objConnect, $SQL);
                if (@oci_execute($strSQL)) {
                    $jsonData = [
                        "status" => true,
                        "message" => 'Your leave successfully created. It must be approved by your responsible line manager and department head.'
                    ];
                    echo json_encode($jsonData);
                } else {
                    @$lastError = error_get_last();
                    @$error = $lastError ? "" . $lastError["message"] . "" : "";
                    $str_arr_error = preg_split("/\,/", $error);
                    $jsonData = ["status" => false,  "message" => @$error ];
                    echo json_encode($jsonData);
                }
            } catch (Exception $e) {
                echo json_encode($error_log($e->getMessage()));
            } finally {
                oci_close($objConnect);
            }
            //*** End Query & Return Data Response ***//
        } else {
            $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
            echo json_encode($jsonData);
        }
    }
} else {
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();
