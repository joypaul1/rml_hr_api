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

            //**Start Query & Return Data Response **//
            try {
                $SQL = "SELECT RML_ID,LEAVE_TYPE,
                                LEAVE_PERIOD,
                                LEAVE_ASSIGN,
                                LEAVE_TAKEN,
                                LATE_LEAVE
                        FROM LEAVE_DETAILS_INFORMATION
                        WHERE RML_ID='$RML_ID'
                        and LEAVE_PERIOD='2024'
                        AND LEAVE_TYPE in ('CL','EL','SL')";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "LEAVE_TYPE"    => $objResultFound['LEAVE_TYPE'] . '-' . $objResultFound['LEAVE_PERIOD'],
                        "LEAVE_PERIOD"  => $objResultFound['LEAVE_PERIOD'],
                        "LEAVE_ASSIGN"  => $objResultFound['LEAVE_ASSIGN'],
                        "LEAVE_TAKEN"   => $objResultFound['LEAVE_TAKEN'],
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
