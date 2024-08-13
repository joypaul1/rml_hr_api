<?php


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
            FROM RML_HR_APPS_USER U
                LEFT JOIN RML_HR_APPS_USER_IMAGE IMAGE
                    ON U.RML_ID = IMAGE.USER_ID
            WHERE RML_ID = '$RML_ID'";

    $strSQL = @oci_parse($objConnect, $SQL);
    @oci_execute($strSQL);
    $objResultFound = @oci_fetch_assoc($strSQL);

    if ($objResultFound) {

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
            "USER_IMAGE" => $objResultFound["USER_IMAGE"],
        ];
        //incldue jwt token
        include_once ('./tokenGen/createToken.php');
        $jwtData = generate_jwt_token($responseData);
        return $jwtData;
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
