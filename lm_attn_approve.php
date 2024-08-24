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
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//

            require_once('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['DATAID', 'REMAKRS', 'ACCEPTED_STATUS'];  // Define required fields

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
            $DATAID = $validator->get('DATAID');   // Retrieve sanitized inputs
            $REMAKRS = $validator->get('REMAKRS');   // Retrieve sanitized inputs
            $ACCEPTED_STATUS = $validator->get('ACCEPTED_STATUS');   // Retrieve sanitized inputs
            // $RML_ID = $checkValidTokenData['data']->data->RML_ID;
            // $LINE_MANAGER_RML_ID = $checkValidTokenData['data']->data->LINE_MANAGER_RML_ID;
            // $ENTRY_BY = $RML_ID;

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "UPDATE RML_HR_ATTN_DAILY SET
                        LINE_MANAGER_APPROVAL='$ACCEPTED_STATUS',
                        IS_ALL_APPROVED='$ACCEPTED_STATUS',
                        LINE_MANAGER_APPROVAL_REMARKS='$REMAKRS',
                        LINE_MANAGER_APPROVAL_DATE=SYSDATE
                        WHERE ID='$DATAID'";
                $strSQL = @oci_parse($objConnect, $SQL);

                if (@oci_execute($strSQL)) {
                    $attnSQL = oci_parse($objConnect, "declare V_ATTN_DATE VARCHAR2(100); V_RML_ID VARCHAR2(100);
                    BEGIN SELECT TO_CHAR(ATTN_DATE,'dd/mm/yyyy'),RML_ID INTO V_ATTN_DATE,V_RML_ID FROM RML_HR_ATTN_DAILY  WHERE ID='$DATAID';
                    RML_HR_ATTN_PROC(V_RML_ID,TO_DATE(V_ATTN_DATE,'dd/mm/yyyy'),TO_DATE(V_ATTN_DATE,'dd/mm/yyyy'));END;");

                    @oci_execute($attnSQL);
                    http_response_code(200);
                    $jsonData = array(
                        "status" => true,
                        "message" => "Attendance Approval/Denied Successfully Completed.",
                        "push_message" => 'Dear, your outdoor attendance entry is approved by your line manager.',
                        'push_id' => '',
                    );
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
