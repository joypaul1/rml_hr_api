<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {
            $RML_ID = $checkValidTokenData['data']->data->RML_ID; // set RML Variable Data
            //**Start data base connection  & status check **//
            include_once('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                http_response_code(401);
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection  & status check **//

            //**Start Query & Return Data Response **//
            try {
                $current_year = date("Y");
                $SQL = "SELECT RML_ID, START_DATE, END_DATE, ((END_DATE - START_DATE) + 1) LEAVE_DAYS, 
                        REMARKS, LEAVE_TYPE, 
                        CASE
                            WHEN IS_APPROVED = 1  THEN 'APPROVED'
                            WHEN IS_APPROVED = 0  THEN 'DENIED'
                            ELSE 'PENDING'
                        END AS IS_APPROVED
                        FROM RML_HR_EMP_LEAVE
                        WHERE RML_ID = '$RML_ID'
                        AND TRUNC(START_DATE) BETWEEN TO_DATE('01/01/" . $current_year . "','dd/mm/yyyy')
                        AND TO_DATE('31/12/" . $current_year . "','dd/mm/yyyy')
                        ORDER BY START_DATE DESC";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "RML_ID"        => $objResultFound['RML_ID'],
                        "START_DATE"    => date('d M Y', strtotime($objResultFound['START_DATE'])),
                        "END_DATE"      => date('d M Y', strtotime($objResultFound['END_DATE'])),
                        "REMARKSs"      => $objResultFound['REMARKS'],
                        "LEAVE_TYPE"    => $objResultFound['LEAVE_TYPE'],
                        "IS_APPROVED"   => $objResultFound['IS_APPROVED'],
                        "LEAVE_DAYS"    => $objResultFound['LEAVE_DAYS']
                    ];
                }

                if (!empty($responseData)) {
                    http_response_code(200);
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(404);
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
