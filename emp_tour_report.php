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
                $SQL = "SELECT RML_ID,
                        START_DATE,
                        END_DATE,
                        ((END_DATE - START_DATE) + 1) AS TOUR_DAYS,
                        ENTRY_DATE,
                        REMARKS,
                        ENTRY_BY,
                        LINE_MANAGER_ID,
                        CASE 
                            WHEN LINE_MANAGER_APPROVAL_STATUS = 1 THEN 'APPROVED'
                            WHEN LINE_MANAGER_APPROVAL_STATUS = 0  THEN 'DENIED'
                            ELSE 'PENDING'
                        END AS LINE_MANAGER_APPROVAL_STATUS,
                        APPROVAL_DATE,
                        APPROVAL_REMARKS
                        FROM RML_HR_EMP_TOUR
                    WHERE RML_ID='$RML_ID'
                    ORDER BY START_DATE DESC";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "RML_ID"                        => $objResultFound['RML_ID'],
                        "START_DATE"                    => $objResultFound['START_DATE'],
                        "END_DATE"                      => $objResultFound['END_DATE'],
                        "REMARKS"                       => $objResultFound['REMARKS'],
                        //"ENTRY_DATE"                    => $objResultFound['ENTRY_DATE'],
                        //"ENTRY_BY"                      => $objResultFound['ENTRY_BY'],
                        //"LINE_MANAGER_ID"               => $objResultFound['LINE_MANAGER_ID'],
                        //"APPROVAL_DATE"                 =>  $objResultFound['APPROVAL_DATE'],
                        //"APPROVAL_REMARKS"              =>  $objResultFound['APPROVAL_REMARKS'],
                        "IS_APPROVED"                   => $objResultFound['LINE_MANAGER_APPROVAL_STATUS'],
                        "TOUR_DAYS"                     => $objResultFound['TOUR_DAYS']
                    ];
                }

                if (!empty($responseData)) {
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    $jsonData = ["status" => false, "data" => [], "message" => 'No Data Found.'];
                    echo json_encode($jsonData);
                }
            } catch (Exception $e) {
                echo json_encode($error_log($e->getMessage()));
            } finally {
                oci_close($objConnect);
            }
            //**End Query & Return Data Response **//
        } else {
            $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
            echo json_encode($jsonData);
            die();
        }
    }
} else {
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();
