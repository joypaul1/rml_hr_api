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
                http_response_code(401);
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection  & status check **//

            //**Start Query & Return Data Response **//
            try {
                $SQL = "SELECT 
                        RML_ID,
                        R_CONCERN,
                        DESIGNATION,
                        BRANCH_NAME,
                        GENDER,
                        USER_ROLE,
                        EMP_NAME,
                        MAIL,
                        MOBILE_NO,
                        BLOOD,
                        LINE_MANAGER_RML_ID,
                        LINE_MANAGER_MOBILE,
                        DEPT_HEAD_RML_ID,
                        DEPT_HEAD_MOBILE_NO,
                        (SELECT SUBUSER.EMP_NAME
                            FROM RML_HR_APPS_USER SUBUSER
                            WHERE SUBUSER.RML_ID = U.LINE_MANAGER_RML_ID) AS LINE_MANAGER_NAME,
                        (SELECT SUBUSER.EMP_NAME
                            FROM RML_HR_APPS_USER SUBUSER
                            WHERE SUBUSER.RML_ID = U.DEPT_HEAD_RML_ID) AS DEPT_HEAD_NAME,
                        NVL(IMAGE.USER_IMAGE, 'http://202.40.181.98:9050/rml_hr_api/image/user.png') AS USER_IMAGE,
                        CASE
                            WHEN EXISTS (SELECT 1
                                        FROM RML_COLL_APPS_USER  APP
                                        WHERE APP.RML_ID = TO_CHAR(TO_NUMBER(SUBSTR(U.RML_ID, INSTR(U.RML_ID, '-') + 1)))
                                        AND APP.IS_ACTIVE = 1  AND  APP.ACCESS_APP = 'RML_COLL')
                            THEN 'yes'
                            ELSE 'no'
                        END AS col_status,
                        CASE
                            WHEN EXISTS (SELECT 1
                                        FROM RML_COLL_APPS_USER  APP
                                        WHERE  APP.RML_ID = LPAD(SUBSTR(U.RML_ID, INSTR(U.RML_ID, '-') + 1), 6, '0')
                                        AND APP.IS_ACTIVE = 1  AND  APP.ACCESS_APP = 'RML_SAL' OR APP.ACCESS_APP = 'RML_RSAL')
                            THEN 'yes'
                            ELSE 'no'
                        END AS sal_status,
                        CASE
                            WHEN EXISTS (SELECT 1
                                        FROM RML_COLL_APPS_USER  APP
                                        WHERE APP.RML_ID = LPAD(SUBSTR(U.RML_ID, INSTR(U.RML_ID, '-') + 1), 6, '0')
                                        AND APP.IS_ACTIVE = 1 AND  APP.ACCESS_APP = 'RML_WSHOP' )
                            THEN 'yes'
                            ELSE 'no'
                        END AS wk_status,
                        TO_CHAR(TO_NUMBER(SUBSTR(U.RML_ID, INSTR(U.RML_ID, '-') + 1))) AS coll_data,
                        LPAD(SUBSTR(U.RML_ID, INSTR(U.RML_ID, '-') + 1), 6, '0') AS sal_data,
                        TO_CHAR(TO_NUMBER(SUBSTR(U.RML_ID, INSTR(U.RML_ID, '-') + 1))) AS wk_data
                    FROM
                        RML_HR_APPS_USER U
                    LEFT JOIN
                        RML_HR_APPS_USER_IMAGE IMAGE ON U.RML_ID = IMAGE.USER_ID
                    WHERE
                        RML_ID = '$RML_ID'
                        AND IS_ACTIVE = 1";
                // echo $SQL;
                $strSQL = @oci_parse($objConnect, $SQL);
                @oci_execute($strSQL);
                $objResultFound = @oci_fetch_assoc($strSQL);

                if ($objResultFound) {
                    $responseData = [
                        "RML_ID" => $objResultFound["RML_ID"],
                        "EMP_NAME" => $objResultFound["EMP_NAME"],
                        "MOBILE_NO" => $objResultFound["MOBILE_NO"],
                        "DESIGNATION" => $objResultFound["DESIGNATION"],
                        "USER_ROLE" => $objResultFound["USER_ROLE"],
                        "CONCERN" => $objResultFound["R_CONCERN"],
                        "LINE_MANAGER_RML_ID" => $objResultFound["LINE_MANAGER_RML_ID"],
                        "LINE_MANAGER_MOBILE" => $objResultFound["LINE_MANAGER_MOBILE"],
                        "DEPT_HEAD_RML_ID" => $objResultFound["DEPT_HEAD_RML_ID"],
                        "DEPT_HEAD_MOBILE_NO" => $objResultFound["DEPT_HEAD_MOBILE_NO"],
                        "USER_IMAGE" => $objResultFound["USER_IMAGE"],
                        "BLOOD" => $objResultFound["BLOOD"],
                        "BRANCH_NAME" => $objResultFound["BRANCH_NAME"],
                        "GENDER" => $objResultFound["GENDER"],
                        "COL_STATUS" => $objResultFound["COL_STATUS"],
                        "SAL_STATUS" => $objResultFound["SAL_STATUS"],
                        "WK_STATUS" => $objResultFound["WK_STATUS"],
                        "COLL_DATA" => $objResultFound["COLL_DATA"],
                        "SAL_DATA" => $objResultFound["SAL_DATA"],
                        "WK_DATA" => $objResultFound["WK_DATA"],
                    ];
                    http_response_code(200);
                    $jsonData = ["status" => true, "data" => $responseData, "message" => 'Successfully Data Found.'];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(401);
                    $jsonData = ["status" => false, "message" => "Invalid credentials or user not active."];
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
