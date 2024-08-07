<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    include_once ('../test_api/inc/connoracle.php');

    if ($isDatabaseConnected !== 1) {
        $jsonData = ["status" => false, "message" => "Database Connection Failed."];
        echo json_encode($jsonData);
        die();
    }

    // Include InputValidator class
    require_once ('InputValidator.php');
    // Define required fields
    $requiredFields = ['rml_id', 'user_password', 'iemiNumber', 'fkey'];

    // Initialize input validator with POST data
    $validator = new InputValidator($_POST);

    if (!$validator->validateRequired($requiredFields)) {
        http_response_code(400);
        $jsonData = ["status" => false, "message" => "Missing required parameters."];
        echo json_encode($jsonData);
        die();
    } else {
        // Sanitize inputs
        $validator->sanitizeInputs();

        // Retrieve sanitized inputs
        $rml_id = $validator->get('rml_id');
        $user_password = strtoupper(md5($validator->get('user_password')));
        $iemiNumber = $validator->get('iemiNumber');
        $firebaseKey = $validator->get('fkey');
        try {
            $SQL = "SELECT RML_ID,
                        R_CONCERN,
                        IEMI_NO,
                        DESIGNATION,
                        FIRE_BASE_ID,
                        USER_ROLE,
                        EMP_NAME,
                        LINE_MANAGER_RML_ID,
                        LINE_MANAGER_MOBILE,
                        DEPT_HEAD_RML_ID,
                        DEPT_HEAD_MOBILE_NO,
                        NVL ((IMAGE.USER_IMAGE),
                            'http://192.168.172.61:8080/test_api/image/user.png')
                            AS USER_IMAGE
                    FROM DEVELOPERS2.RML_HR_APPS_USER U
                        LEFT JOIN DEVELOPERS.RML_HR_APPS_USER_IMAGE IMAGE
                            ON U.RML_ID = IMAGE.USER_ID
                    WHERE RML_ID = '$rml_id'
                        AND PASS_MD5 = '$user_password'
                        AND IS_ACTIVE = 1";

            $strSQL = @oci_parse($objConnect, $SQL);
            @oci_execute($strSQL);
            $objResultFound = @oci_fetch_assoc($strSQL);

            if ($objResultFound) {

                $DatabaseiemiNo = $objResultFound["IEMI_NO"];
                $Fdatabasekey = $objResultFound["FIRE_BASE_ID"];

                if ($DatabaseiemiNo != $iemiNumber) {
                    $SQL = "UPDATE DEVELOPERS.RML_HR_APPS_USER SET IEMI_NO = '$iemiNumber' WHERE RML_ID = '$rml_id'";
                    $strSQLFkeyUpdate = @oci_parse($objConnect, $SQL);
                    @oci_execute($strSQLFkeyUpdate);
                }

                if (strlen($firebaseKey) > 0 && $firebaseKey != $Fdatabasekey) {
                    $strSQLFkeyUpdate = @oci_parse($objConnect, "UPDATE DEVELOPERS.RML_HR_APPS_USER SET FIRE_BASE_ID = '$firebaseKey', FKEY_UPDATED_DATE = SYSDATE WHERE RML_ID = '$rml_id'");
                    @oci_execute($strSQLFkeyUpdate);
                }

                $SESSTION_SQL = @oci_parse($objConnect, "BEGIN DEVELOPERS.HR_APPS_USER_SESSION_CREATE('$rml_id'); END;");
                @oci_execute($SESSTION_SQL);

                $responseData = [
                    "RML_ID" => $objResultFound["RML_ID"],
                    "EMP_NAME" => $objResultFound["EMP_NAME"],
                    "DESIGNATION" => $objResultFound["DESIGNATION"],
                    "USER_ROLE" => $objResultFound["USER_ROLE"],
                    "CONCERN" => $objResultFound["R_CONCERN"],
                    "LINE_MANAGER_RML_ID" => $objResultFound["LINE_MANAGER_RML_ID"],
                    "LINE_MANAGER_MOBILE" => $objResultFound["LINE_MANAGER_MOBILE"],
                    "DEPT_HEAD_RML_ID" => $objResultFound["DEPT_HEAD_RML_ID"],
                    "DEPT_HEAD_MOBILE_NO" => $objResultFound["DEPT_HEAD_MOBILE_NO"],
                    "USER_IMAGE" => "http://192.168.172.61:8080/test_api/image/user.png",
                ];
                //incldue jwt token
                include_once ('createToken.php');
                $jwtData = generate_jwt_token($responseData);
                http_response_code(200); // status successful
                $jsonData = ["status" => true, "data" => $jwtData, "message" => 'Successfully Data Found.'];
                echo json_encode($jsonData);
            } else {
                http_response_code(401); // invalid user or credentials
                $jsonData = ["status" => false, "message" => "Invalid credentials or user not active."];
                echo json_encode($jsonData);
                die();
            }
        } catch (Exception $e) {
            // Set the HTTP status code to 500 Internal Server Error
            http_response_code(500);
            $jsonData = ["status" => false, "message" => $e->getMessage()];
            echo json_encode($jsonData);
            die();
        } finally {
            oci_close($objConnect);
        }
    }
} else {
    http_response_code(405);
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();
