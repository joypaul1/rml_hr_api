<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once("checkValidTokenData.php");
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

            require_once('InputValidator.php');  // Include InputValidator class
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
                $SQL = "SELECT
                            attn.ID,
                            attn.RML_ID,
                            attn.ATTN_DATE,
                            attn.LAT,
                            attn.LANG,
                            attn.OUTSIDE_REMARKS,
                            RML_HR_FKEY(attn.RML_ID, 'NU') AS NU_FKEY,
                            (SELECT a.EMP_NAME
                            FROM RML_HR_APPS_USER a
                            WHERE a.RML_ID = attn.RML_ID) AS EMP_NAME,
                            NVL((SELECT B.USER_IMAGE
                                FROM RML_HR_APPS_USER_IMAGE B
                                WHERE B.USER_ID = attn.RML_ID),
                                'http://202.40.181.98:9050/rml_hr_api/image/user.png') AS USER_IMAGE
                        FROM
                            RML_HR_ATTN_DAILY attn
                        WHERE
                            attn.LINE_MANAGER_ID = '$RML_ID'
                            AND attn.INSIDE_OR_OUTSIDE = 'Outside Office'
                            AND TRUNC(attn.ATTN_DATE) BETWEEN TRUNC(SYSDATE) -
                                (SELECT KEY_VALUE
                                FROM HR_GLOBAL_CONFIGARATION
                                WHERE KEY_TYPE = 'ATTN_OUTDOOR_APPROVAL')
                                AND TRUNC(SYSDATE)
                            AND attn.LINE_MANAGER_APPROVAL IS NULL
                            AND attn.IS_ALL_APPROVED = 0
                        ORDER BY
                            attn.ATTN_DATE DESC";
                $SQL .= " OFFSET $START_ROW ROWS FETCH NEXT $LIMIT_ROW ROWS ONLY";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "ID" => $objResultFound['ID'],
                        "RML_ID" => $objResultFound['RML_ID'],
                        "EMP_NAME" => $objResultFound['EMP_NAME'],
                        "ATTN_DATE" => $objResultFound['ATTN_DATE'],
                        "LAT" => $objResultFound['LAT'],
                        "LANG" => $objResultFound['LANG'],
                        "OUTSIDE_REMARKS" => $objResultFound['OUTSIDE_REMARKS'],
                        "EMP_IMAGE" => $objResultFound['USER_IMAGE']
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
