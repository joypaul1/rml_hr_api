<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    include_once('../rml_hr_api/inc/connoracle.php');

    if ($isDatabaseConnected !== 1) {
        $jsonData = ["status" => false, "message" => "Database Connection Failed."];
        echo json_encode($jsonData);
        die();
    }

    // Include InputValidator class
    require_once('InputValidator.php');
    // Define required fields
    $requiredFields = ['rml_id', 'user_password', 'fkey'];

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
        $otpCode = null;

        try {
            $SQL = "SELECT RML_ID,
                        R_CONCERN,
                        IEMI_NO,
                        DESIGNATION,
                        FIRE_BASE_ID,
                        USER_ROLE,
                        EMP_NAME,
                        MOBILE_NO,
                        LINE_MANAGER_RML_ID,
                        LINE_MANAGER_MOBILE,
                        DEPT_HEAD_RML_ID,
                        DEPT_HEAD_MOBILE_NO,
                        NVL ((IMAGE.USER_IMAGE),
                            'http://202.40.181.98:9050/rml_hr_api/image/user.png')
                            AS USER_IMAGE
                    FROM RML_HR_APPS_USER U
                        LEFT JOIN RML_HR_APPS_USER_IMAGE IMAGE
                            ON U.RML_ID = IMAGE.USER_ID
                    WHERE RML_ID = '$rml_id'
                        AND PASS_MD5 = '$user_password'
                        AND IS_ACTIVE = 1";

            $strSQL = @oci_parse($objConnect, $SQL);
            @oci_execute($strSQL);
            $objResultFound = @oci_fetch_assoc($strSQL);

            if ($objResultFound) {

                if (isset($_POST['sys_mobile'])) {
                    if(empty($_POST['sys_mobile']) || $_POST['sys_mobile'] == ''){
                        include_once('./smsGen/sendOTP.php');
                        $otpRES = sendOTP($objResultFound['MOBILE_NO'], $_POST['opt_device_track_code']);
                        if($otpRES['status']){
                            $otpCode = $otpRES['OTP'];
                        }
                    }else if ($_POST['sys_mobile'] != $objResultFound['MOBILE_NO']) {
                        http_response_code(401); // invalid user or credentials
                        $jsonData = [
                            "status" => false,
                            "message" => "Invalid device for this user.",
                            "otpCode" => $otpCode
                        ];
                        echo json_encode($jsonData);
                        die();
                    }
                } else if (!isset($_POST['sys_mobile']) && isset($_POST['opt_device_track_code'])) {
                    include_once('./smsGen/sendOTP.php');
                    $otpRES = sendOTP($objResultFound['MOBILE_NO'], $_POST['opt_device_track_code']);
                    if($otpRES['status']){
                        $otpCode = $otpRES['OTP'];
                    }
                }
                // Additional logic can be placed here
                // IEMI_NO update or create
                //$DatabaseiemiNo = $objResultFound["IEMI_NO"];
                // if ($DatabaseiemiNo != $iemiNumber) {
                //     $SQL = "UPDATE RML_HR_APPS_USER SET IEMI_NO = '$iemiNumber' WHERE RML_ID = '$rml_id'";
                //     $strSQLFkeyUpdate = @oci_parse($objConnect, $SQL);
                //     @oci_execute($strSQLFkeyUpdate);
                // }
                // firebase update or create
                $Fdatabasekey = $objResultFound["FIRE_BASE_ID"];
                if (strlen($firebaseKey) > 0 && $firebaseKey != $Fdatabasekey) {
                    $strSQLFkeyUpdate = @oci_parse($objConnect, "UPDATE RML_HR_APPS_USER SET FIRE_BASE_ID = '$firebaseKey', FKEY_UPDATED_DATE = SYSDATE WHERE RML_ID = '$rml_id'");
                    @oci_execute($strSQLFkeyUpdate);
                }
                // session for login log create
                $SESSTION_SQL = @oci_parse($objConnect, "BEGIN HR_APPS_USER_SESSION_CREATE('$rml_id'); END;");
                @oci_execute($SESSTION_SQL);
                // session for login log create
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
                //incldue jwt token
                include_once('./tokenGen/createToken.php');
                $jwtData = generate_jwt_token($responseData);
                http_response_code(200); // status successful
                $jsonData = [
                    "status"    => true,
                    "data"      => $jwtData,
                    "message"   => 'Successfully Login.',
                    "otpCode"   => $otpCode,
                    "mobile"    => $objResultFound['MOBILE_NO'],
                ];
                echo json_encode($jsonData);
            } else {
                http_response_code(401); // invalid user or credentials
                $jsonData = [
                    "status"    => false,
                    "message" => "Invalid credentials or user not active.",
                    "otpCode" => $otpCode,
                    "mobile"   => null,
                ];
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
