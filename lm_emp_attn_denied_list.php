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
                $SQL = "SELECT attn.ID,attn.RML_ID,attn.ATTN_DATE,attn.LAT,attn.LANG,attn.OUTSIDE_REMARKS,RML_HR_FKEY(attn.RML_ID,'NU') NU_FKEY,
                        (SELECT a.EMP_NAME FROM RML_HR_APPS_USER a WHERE a.RML_ID=attn.RML_ID)EMP_NAME
                        FROM RML_HR_ATTN_DAILY attn
                    WHERE attn.LINE_MANAGER_ID='$RML_ID'
                        AND attn.INSIDE_OR_OUTSIDE='Outside Office'
                        AND trunc(ATTN.ATTN_DATE) BETWEEN  trunc(SYSDATE)-( select KEY_VALUE FROM HR_GLOBAL_CONFIGARATION
                        WHERE KEY_TYPE='ATTN_OUTDOOR_APPROVAL') AND  trunc(SYSDATE)
                        AND attn.LINE_MANAGER_APPROVAL = 0
                        ORDER BY ATTN_DATE desc";
                $SQL .= " OFFSET $START_ROW ROWS FETCH NEXT $LIMIT_ROW ROWS ONLY";
                // AND attn.IS_ALL_APPROVED= 1
                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "ID"                => $objResultFound['ID'],
                        "RML_ID"            => $objResultFound['RML_ID'],
                        "EMP_NAME"          => $objResultFound['EMP_NAME'],
                        "ATTN_DATE"         =>$objResultFound['ATTN_DATE'],
                        "LAT"               => $objResultFound['LAT'],
                        "LANG"              => $objResultFound['LANG'],
                        "OUTSIDE_REMARKS"   => $objResultFound['OUTSIDE_REMARKS'],
                        "NU_FKEY"           => $objResultFound['NU_FKEY']
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
