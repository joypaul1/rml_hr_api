<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once ("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {
            $RML_ID = $checkValidTokenData['data']->data->RML_ID; // set RML Variable Data
            //**Start data base connection  & status check **//
            include_once ('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection  & status check **//

            //**Start Query & Return Data Response **//
            try {
                $TODAY = date('d/m/Y');
                $SQL = "SELECT a.RML_ID,
                                NVL(b.STATUS, 'A') AS ATTN_STATUS
                            FROM RML_HR_APPS_USER a
                            LEFT JOIN RML_HR_ATTN_DAILY_PROC b
                                ON a.RML_ID = b.RML_ID
                                AND TRUNC(b.ATTN_DATE) = TO_DATE('$TODAY', 'DD/MM/YYYY')
                            WHERE a.RML_ID = '$RML_ID' AND IS_ACTIVE = 1";
                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $objResultFound = @oci_fetch_assoc($strSQL);
                $responseData = [];
                if ($objResultFound) {
                    $responseData = [
                        "ATTN_STATUS" => $objResultFound["ATTN_STATUS"]
                    ];
                    http_response_code(200);
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(401);
                    $jsonData = ["status" => false, "message" => "Invalid credentials or user not active."];
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
