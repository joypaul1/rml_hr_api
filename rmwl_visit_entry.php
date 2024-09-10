<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


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
            $requiredFields = ['SALE_AMOUNT', 'COLLECTION_AMOUNT', 'REMARKS', 'LAT', 'LANG', 'DISTANCE', 'FAKE_LOCATION','VISIT_ASSIGN_ID'];  // Define required fields

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
            $RETAILER_ID    = $validator->get('RETAILER_ID');   // Retrieve sanitized inputs
            $VISIT_DATE     = $validator->get('VISIT_DATE');   // Retrieve sanitized inputs
            $REMARKS        = $validator->get('REMARKS');   // Retrieve sanitized inputs
            //$RML_ID         = $checkValidTokenData['data']->data->RML_ID;
            //$ENTRY_BY       = $RML_ID;

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "UPDATE WSHOP.VISIT_ASSIGN SET
                            SALES_AMOUNT_COLLECTED      = $P_SALE_AMOUNT,
                            COLLECTION_AMOUNT_COLLECTED = $P_COLLECTION_AMOUNT,
                            AFTER_VISIT_REMARKS         = '$P_VISIT_REMARKS',
                            VISIT_STATUS                =  1,
                            VISIT_LAT                   = '$P_VISIT_LAT',
                            VISIT_LANG                  = '$P_VISIT_LANG',
                            DISTANCE                    = $P_VISIT_DISTANCE,
                            FAKE_LOCATION               = '$P_FAKE_LOCATION',
                            LOCATION_VISITED_DATE       = SYSDATE
                        WHERE  ID = $P_VISIT_ASSIGN_ID";
                $strSQL = @oci_parse($objConnect, $SQL);
                if (@oci_execute($strSQL)) {
                    http_response_code(200);
                    $jsonData = ["status" => true,  "message" =>'Visit entry successfully updated!'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(403);
                    @$lastError = error_get_last();
                    @$error = $lastError ? "" . $lastError["message"] . "" : "";
                    @$str_arr_error = preg_split("/\,/", $error);
                    $jsonData = ["status" => false,  "message" => @$str_arr_error ];
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
