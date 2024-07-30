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
                $SQL = "SELECT a.ID,b.EMP_NAME,
                            (b.EMP_NAME ||'('||a.RML_ID||')') RML_ID,
                            a.ENTRY_DATE,
                            a.START_DATE,
                            a.END_DATE,
                            a.REMARKS,
                            a.ENTRY_BY,
                            a.LINE_MANAGER_ID,
                            a.LINE_MANAGER_APPROVAL_STATUS,
                            a.APPROVAL_DATE,
                            a.APPROVAL_REMARKS
                        FROM RML_HR_EMP_TOUR a, RML_HR_APPS_USER b
                        WHERE A.RML_ID=B.RML_ID
                        and a.LINE_MANAGER_ID='$RML_ID'
                        AND rownum <= 20
                        AND a.LINE_MANAGER_APPROVAL_STATUS IS NULL
                        order by START_DATE";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "ID"            => $objResultFound['ID'],
                        "RML_ID"        => $objResultFound['RML_ID'],
                        "START_DATE"    => $objResultFound['START_DATE'],
                        "END_DATE"      => $objResultFound['END_DATE'],
                        "REMARKS"       => $objResultFound['REMARKS'],
                        "LEAVE_DAYS"    => $objResultFound['LEAVE_DAYS'],
                        "LEAVE_TYPE"    => $objResultFound['LEAVE_TYPE']
                    ];
                }

                if (!empty($responseData)) {
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    $jsonData = ["status" => true, "data" => [], "message" => 'No Data Found.'];
                    echo json_encode($jsonData);
                }
            } catch (Exception $e) {
                $jsonData = ["status" => false, "message" => $e->getMessage()];
                echo json_encode($jsonData);
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
