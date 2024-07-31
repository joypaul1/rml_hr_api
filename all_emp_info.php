<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if($checkValidTokenData['status']){
        if($checkValidTokenData['data']->data->RML_ID){
            
            //** ORACLE DATA CONNECTION***//
            include_once('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            } 
            //** ORACLE DATA CONNECTION***//

           
            require_once('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['START_ROW','LIMIT_ROW'];  // Define required fields

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
            $START_ROW       = $validator->get('START_ROW');   // Retrieve sanitized inputs
            $LIMIT_ROW       = $validator->get('LIMIT_ROW');   // Retrieve sanitized inputs


            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "SELECT 
                            U.RML_ID, 
                            U.EMP_NAME,
                            U.MAIL, 
                            U.MOBILE_NO, 
                            U.R_CONCERN, 
                            U.DESIGNATION, 
                            U.USER_ROLE, 
                            U.BRANCH_NAME,
                            U.DEPT_NAME, 
                            U.GENDER,
                            U.BLOOD,
                            U.LINE_MANAGER_RML_ID,
                            U.LINE_MANAGER_MOBILE,
                            U.DEPT_HEAD_RML_ID,
                            U.DEPT_HEAD_MOBILE_NO,
                            (SELECT SUBUSER.EMP_NAME
                            FROM DEVELOPERS.RML_HR_APPS_USER SUBUSER
                            WHERE SUBUSER.RML_ID = U.LINE_MANAGER_RML_ID) AS LINE_MANAGER_NAME,
                            (SELECT SUBUSER.EMP_NAME
                            FROM DEVELOPERS.RML_HR_APPS_USER SUBUSER
                            WHERE SUBUSER.RML_ID = U.DEPT_HEAD_RML_ID) AS DEPT_HEAD_NAME
                        FROM 
                            DEVELOPERS.RML_HR_APPS_USER U
                        WHERE   
                            U.IS_ACTIVE = 1";

                if (isset($_POST['SEARCH_DATA'])) {
                    $SEARCH_DATA = trim($_POST['SEARCH_DATA']);
                    $SQL .= " AND (
                                LOWER(U.RML_ID) LIKE LOWER('%$SEARCH_DATA%')
                                OR LOWER(U.MAIL) LIKE LOWER('%$SEARCH_DATA%')
                                OR LOWER(U.DESIGNATION) LIKE LOWER('%$SEARCH_DATA%')
                                OR LOWER(U.EMP_NAME) LIKE LOWER('%$SEARCH_DATA%')
                                OR LOWER(U.DEPT_NAME) LIKE LOWER('%$SEARCH_DATA%')
                                OR LOWER(U.MOBILE_NO) LIKE LOWER('%$SEARCH_DATA%')
                            )";
                }

                $SQL .= " OFFSET $START_ROW ROWS FETCH NEXT $LIMIT_ROW ROWS ONLY";

                $strSQL = @oci_parse($objConnect, $SQL);            
                @oci_execute($strSQL);
                $responseData = [];
                while ($objResultFound = @oci_fetch_assoc($strSQL)) {
                    $responseData[] = [
                        "RML_ID"                => $objResultFound["RML_ID"],
                        "EMP_NAME"              => $objResultFound["EMP_NAME"],
                        "MOBILE_NO"             => $objResultFound["MOBILE_NO"],
                        "MAIL"                  => $objResultFound["MAIL"],
                        'DEPT_NAME'             => $objResultFound["DEPT_NAME"],
                        "DESIGNATION"           => $objResultFound["DESIGNATION"],
                        "BRANCH_NAME"           => $objResultFound["BRANCH_NAME"],
                        "USER_ROLE"             => $objResultFound["USER_ROLE"],
                        "CONCERN"               => $objResultFound["R_CONCERN"],
                        "GENDER"                => $objResultFound["GENDER"],
                        "BLOOD"                 => $objResultFound["BLOOD"],
                        "LINE_MANAGER_RML_ID"   => $objResultFound["LINE_MANAGER_RML_ID"],
                        "LINE_MANAGER_MOBILE"   => $objResultFound["LINE_MANAGER_MOBILE"],
                        "DEPT_HEAD_RML_ID"      => $objResultFound["DEPT_HEAD_RML_ID"], 
                        "DEPT_HEAD_MOBILE_NO"   => $objResultFound["DEPT_HEAD_MOBILE_NO"],
                        "LINE_MANAGER_NAME"     => $objResultFound["LINE_MANAGER_NAME"],
                        "DEPT_HEAD_NAME"        => $objResultFound["DEPT_HEAD_NAME"],
                        "USER_IMAGE"            => "http://192.168.172.61:8080/test_api/image/user.png",
                    ];
                }

                if (!empty($responseData)) {
                    http_response_code(200); // status successful
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(200); // status successful
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
            //*** End Query & Return Data Response ***//
        }else{
            // Set the HTTP status code to 400 Bad Request
            http_response_code(400);
            $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
            echo json_encode($jsonData); 
            
        }
    }
} else {
    http_response_code(405);
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();

?>