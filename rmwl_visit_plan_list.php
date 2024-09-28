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
                http_response_code(401);
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
                $SQL = "SELECT A.ID,
                        A.VISIT_DATE,
                        (SELECT P.TITLE FROM WSHOP.PRODUCT_BRAND P WHERE P.ID=A.PRODUCT_BRAND_ID) BRAND_NAME,
                        A.USER_REMARKS,
                        A.VISIT_STATUS,
                        A.SALES_AMOUNT_COLLECTED,
                        A.COLLECTION_AMOUNT_COLLECTED,
                        A.AFTER_VISIT_REMARKS,
                        B.USER_NAME,
                        B.USER_MOBILE,
                        B.RML_IDENTITY_ID,
                        B.LAT,
                        B.LANG,
                        (SELECT P.NAME FROM WSHOP.DISTRICT P WHERE P.ID=B.DISTRICT_ID) DISTRICT
						FROM WSHOP.VISIT_ASSIGN A,WSHOP.USER_PROFILE B
						WHERE A.RETAILER_ID=B.ID
						AND A.VISIT_STATUS=0
						AND TRUNC(A.VISIT_DATE)=TO_DATE(SYSDATE,'DD/MM/RRRR')
						AND A.USER_ID=(select ID from WSHOP.USER_PROFILE
                        WHERE RML_IDENTITY_ID='$RML_ID')";
                $SQL .= " OFFSET $START_ROW ROWS FETCH NEXT $LIMIT_ROW ROWS ONLY";
               

                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                    "ID"                            => $objResultFound['ID'],
					"VISIT_DATE"                    => $objResultFound['VISIT_DATE'],
					"BRAND_NAME"                    => $objResultFound['BRAND_NAME'],
					"USER_REMARKS"                  => $objResultFound['USER_REMARKS'],
					"VISIT_STATUS"                  => $objResultFound['VISIT_STATUS'],
					"SALES_AMOUNT_COLLECTED"        => $objResultFound['SALES_AMOUNT_COLLECTED'],
					"COLLECTION_AMOUNT_COLLECTED"   => $objResultFound['COLLECTION_AMOUNT_COLLECTED'],
					"AFTER_VISIT_REMARKS"           => $objResultFound['AFTER_VISIT_REMARKS'],
					"USER_NAME"                     => $objResultFound['USER_NAME'],
					"USER_MOBILE"                   => $objResultFound['USER_MOBILE'],
					"RML_IDENTITY_ID"               => $objResultFound['RML_IDENTITY_ID'],
					"LAT"                           => $objResultFound['LAT'],
					"LANG"                          => $objResultFound['LANG'],
					"DISTRICT"                      => $objResultFound['DISTRICT']
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
