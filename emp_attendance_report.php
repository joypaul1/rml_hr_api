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
            require_once('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['START_DATE', 'END_DATE'];  // Define required fields

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
            $END_DATE       = $validator->get('END_DATE');

            //**Start Query & Return Data Response **//
            try {

                //** @ATTENDANCE PRROCESSING **//
                $attnSQL  = @oci_parse($objConnect, "DECLARE  start_date VARCHAR2(100):=to_char(sysdate,'dd/mm/yyyy');
                BEGIN RML_HR_ATTN_PROC('$RML_ID',TO_DATE(start_date,'dd/mm/yyyy') ,TO_DATE(start_date,'dd/mm/yyyy') );END;");
                @oci_execute($attnSQL);
                //** @END ATTENDANCE PRROCESSING **//

                $current_year = date("Y");
                $SQL = "SELECT ATTN_DATE,IN_TIME,OUT_TIME,STATUS AS ATTN_STATUS, DAY_NAME from RML_HR_ATTN_DAILY_PROC
					WHERE RML_ID='$RML_ID'
                    and trunc(ATTN_DATE) between to_date('$attn_start_date','dd/mm/yyyy') 
                    and to_date('$attn_end_date','dd/mm/yyyy')
                    --AND ATTN_DATE BETWEEN TO_DATE((SELECT TO_CHAR(trunc(SYSDATE) - (to_number(to_char(SYSDATE,'DD')) - 1),'dd/mm/yyyy') FROM dual),'dd/mm/yyyy') AND SYSDATE
                    ORDER BY ATTN_DATE DESC";

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
                    http_response_code(200); // status successful
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(200); // status successful
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
