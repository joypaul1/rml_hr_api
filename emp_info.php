<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once ("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {
            $RML_ID = $checkValidTokenData['data']->data->RML_ID; // set RML Variable Data
            //**Start data base connection  & status check **//
            include_once ('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection  & status check **//

            //**Start Query & Return Data Response **//
            try {
                $SQL = "SELECT 
                RML_ID, R_CONCERN, IEMI_NO, DESIGNATION,
                USER_ROLE,  EMP_NAME,MAIL, MOBILE_NO,
                LINE_MANAGER_RML_ID, LINE_MANAGER_MOBILE, DEPT_HEAD_RML_ID, DEPT_HEAD_MOBILE_NO,
                (SELECT SUBUSER.EMP_NAME
                FROM DEVELOPERS2.RML_HR_APPS_USER SUBUSER
                WHERE SUBUSER.RML_ID = U.LINE_MANAGER_RML_ID) AS LINE_MANAGER_NAME,
                (SELECT SUBUSER.EMP_NAME
                FROM DEVELOPERS2.RML_HR_APPS_USER SUBUSER
                WHERE SUBUSER.RML_ID = U.DEPT_HEAD_RML_ID) AS DEPT_HEAD_NAME,
                NVL ((IMAGE.USER_IMAGE),
                'http://192.168.172.61:8080/test_api/image/user.png')
                AS USER_IMAGE
                FROM
                    DEVELOPERS2.RML_HR_APPS_USER U
                    LEFT JOIN DEVELOPERS2.RML_HR_APPS_USER_IMAGE IMAGE
                            ON U.RML_ID = IMAGE.USER_ID
                WHERE
                    RML_ID = '$RML_ID' AND IS_ACTIVE = 1";

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
