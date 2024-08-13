<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {
            $RML_ID = $checkValidTokenData['data']->data->RML_ID; // set RML Variable Data
            //**Start data base connection  & status check **//
            include_once('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection  & status check **//
            // Include InputValidator class
            require_once('InputValidator.php');
            // Define required fields
            $requiredFields = ['START_DATE', 'END_DATE'];

            // Initialize input validator with POST data
            $validator = new InputValidator($_POST);

            if (!$validator->validateRequired($requiredFields)) {
                 http_response_code(400);
        $jsonData = ["status" => false, "message" => "Missing required parameters."];
                echo json_encode($jsonData); 
                die();
            }
            // **Initialize input validator with POST Data**//

            $validator->sanitizeInputs();   // Sanitize Inputs
            $START_DATE     = $validator->get('START_DATE');   // Retrieve sanitized inputs
            $END_DATE       = $validator->get('END_DATE');   // Retrieve sanitized inputs
 
            //**Start Query & Return Data Response **//
            try {
                $SQL = "SELECT ATTN_DATE,IN_TIME,OUT_TIME,STATUS ATTN_STATUS,DAY_NAME
                from RML_HR_ATTN_DAILY_PROC
                where RML_ID='$RML_ID'
                and trunc(ATTN_DATE) between to_date('$START_DATE','dd/mm/yyyy')
                and to_date('$END_DATE','dd/mm/yyyy')
                order by ATTN_DATE desc";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "ATTN_DATE"     => $objResultFound['ATTN_DATE'],
                        "IN_TIME"       => $objResultFound['IN_TIME'],
                        "OUT_TIME"      => $objResultFound['OUT_TIME'],
                        "ATTN_STATUS"   => $objResultFound['ATTN_STATUS'],
                        "DAY_NAME"      => $objResultFound['DAY_NAME']
                    ];
                }

                if (!empty($responseData)) {
                    http_response_code(200);
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(200);
                    $jsonData = ["status" => true, "data" => [], "message" => 'No Data Found.'];
                    echo json_encode($jsonData);
                }
            } catch (Exception $e) {
                http_response_code(500);
            $jsonData = ["status" => false, "message" => $e->getMessage()];
                echo json_encode($jsonData);
            } finally {
                oci_close($objConnect);
            }
            //**End Query & Return Data Response **//
        } else {
            // Set the HTTP status code to 400 Bad Request
            http_response_code(400);
            $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
            echo json_encode($jsonData);
            die();
        }
    }
} else {
    http_response_code(405);
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();
