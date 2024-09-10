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
                $SQL = "SELECT A.ID USER_ID,
                        A.USER_NAME,
                        A.USER_MOBILE,
                        A.LAT,A.LANG,A.LOCATION_REMARKS,
                        (SELECT TITLE
                            FROM WSHOP.USER_TYPE
                            WHERE ID = A.USER_TYPE_ID)
                            AS USER_TYPE
                        FROM WSHOP.USER_PROFILE A,
                        (SELECT USER_ID
                            FROM WSHOP.USER_MANPOWER_SETUP
                            WHERE PARENT_USER_ID = (select ID frFom WSHOP.USER_PROFILE
                            WHERE RML_IDENTITY_ID='$RML_ID')
                        UNION ALL
                        SELECT USER_ID
                        FROM WSHOP.USER_MANPOWER_SETUP
                        WHERE PARENT_USER_ID INss
                        (SELECT USER_ID
                                FROM WSHOP.USER_MANPOWER_SETUP
                                WHERE PARENT_USER_ID = (select ID from WSHOP.USER_PROFILE
                        WHEREss RML_IDENTITY_ID='$RML_ID'))) B
                        WHERE A.ID=B.USER_ID";
                $SQL .= " OFFSET $START_ROW ROWS FETCH NEXT $LIMIT_ROW ROWS ONLY";

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "USER_ID"           => $responseData['USER_ID'],
                        "USER_NAME"         => $responseData['USER_NAME'],
                        "USER_TYPE"         => $responseData['USER_TYPE'],
                        "USER_MOBILE"       => $responseData['USER_MOBILE'],
                        "LAT"               => $responseData['LAT'],
                        "LANG"              => $responseData['LANG'],
                        "LOCATION_REMARKS"  => $responseData['LOCATION_REMARKS']
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
