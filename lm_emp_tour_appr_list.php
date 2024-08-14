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
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection  & status check **//
            require_once ('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['START_ROW', 'LIMIT_ROW'];  // Define required fields

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
            $START_ROW = $validator->get('START_ROW');   // Retrieve sanitized inputs
            $LIMIT_ROW = $validator->get('LIMIT_ROW');   // Retrieve sanitized inputs

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
                        AND a.LINE_MANAGER_APPROVAL_STATUS IS NULL
                        order by START_DATE";
                $SQL .= " OFFSET $START_ROW ROWS FETCH NEXT $LIMIT_ROW ROWS ONLY";


                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "ID"                => $objResultFound['ID'],
                        "RML_ID"            => $objResultFound['RML_ID'],
                        "ENTRY_DATE"        => $objResultFound['ENTRY_DATE'],
                        "START_DATE"        => $objResultFound['START_DATE'],
                        "END_DATE"          => $objResultFound['END_DATE'],
                        "REMARKS"           => $objResultFound['REMARKS'],
                        "ENTRY_BY"          => $objResultFound['ENTRY_BY'],
                        "LINE_MANAGER_ID"   => $objResultFound['LINE_MANAGER_ID'],
                        "LINE_MANAGER_APPROVAL_STATUS" => $objResultFound['LINE_MANAGER_APPROVAL_STATUS'],
                        "APPROVAL_DATE"     =>  $objResultFound['APPROVAL_DATE'],
                        "APPROVAL_REMARKS"  =>  $objResultFound['APPROVAL_REMARKS']
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
