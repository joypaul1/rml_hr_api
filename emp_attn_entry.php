<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once ("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once ('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//

            require_once ('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['LATITUDE', 'LONGITUDE', 'INSIDE_OR_OUTSIDE', 'OUTSIDE_REMAKRKS', 'EMP_DISTANCE'];  // Define required fields

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
            $LATITUDE           = $validator->get('LATITUDE');   // Retrieve sanitized inputs
            $LONGITUDE          = $validator->get('LONGITUDE');   // Retrieve sanitized inputs
            $INSIDE_OR_OUTSIDE  = $validator->get('INSIDE_OR_OUTSIDE');   // Retrieve sanitized inputs
            $OUTSIDE_REMAKRKS   = $validator->get('OUTSIDE_REMAKRKS');   // Retrieve sanitized inputs
            $EMP_DISTANCE       = $validator->get('EMP_DISTANCE');   // Retrieve sanitized inputs
            $RML_ID             = $checkValidTokenData['data']->data->RML_ID;
            $ENTRY_BY           = $RML_ID;

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "BEGIN RML_HR_ATTN_CREATE('$RML_ID','$LATITUDE','$LONGITUDE','$INSIDE_OR_OUTSIDE','$OUTSIDE_REMAKRKS', '$EMP_DISTANCE', '$ENTRY_BY');END;";
                $strSQL = @oci_parse($objConnect, $SQL);
                if (@oci_execute($strSQL)) {
                    http_response_code(200);
                    if ($INSIDE_OR_OUTSIDE == 'Inside Office') {
                        $jsonData = array(
                            "status"        => true,
                            "message"       => "Your office attendance successfully saved.",
                            "push_message"  => '',
                            'push_id'       => ''
                        );
                    } else {
                        $jsonData = array(
                            "status"        => true,
                            'push_id'       => $checkValidTokenData['data']->LINE_MANAGER_RML_ID,
                            "message"       => "Your office attendance successfully saved. It must be approved by your responsible line manager.",
                            "push_message"  => "Your concern $RML_ID, have given outside acctendance. You should approved/denied this attendance."
                        );
                    }
                    echo json_encode($jsonData);
                } else {
                    http_response_code(403);
                    @$lastError = error_get_last();
                    @$error = $lastError ? "" . $lastError["message"] . "" : "";
                    @$str_arr_error = preg_split("/\,/", $error);
                    $jsonData = ["status" => false, "message" => @$str_arr_error];
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
die();
