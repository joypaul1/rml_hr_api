<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {
        
    include_once('../test_api/inc/connoracle.php');
    // $jsonData =[];
    if ($isDatabaseConnected !== 1) {
        $jsonData = ["status" => false, "message" => "Database connection failed."];
        echo json_encode($jsonData);
        die();
    } 

    // Include InputValidator class
    require_once('InputValidator.php');
    // Define required fields
    $requiredFields = ['rml_id', 'user_password', 'iemiNumber', 'fkey'];
   
    // Initialize input validator with POST data
    $validator = new InputValidator($_POST);

    if (!$validator->validateRequired($requiredFields)) {
      
        $jsonData = ["status" => "false", "message" => "Missing required parameters."];
        echo json_encode($jsonData); 
        die();
    }else{
       
        // Sanitize inputs
        $validator->sanitizeInputs();
        
        // Retrieve sanitized inputs
        $rml_id         = $validator->get('rml_id');
        $user_password  = strtoupper(md5($validator->get('user_password')));
        $iemiNumber     = $validator->get('iemiNumber');
        $firebaseKey    = $validator->get('fkey');       
        try {
            $SQL = "SELECT 
            RML_ID, R_CONCERN, IEMI_NO, DESIGNATION, FIRE_BASE_ID,
            DEVELOPERS.RML_HR_FKEY(RML_ID, 'LINE_MANAGER') LINE_MANAGER_FKEY,
            DEVELOPERS.RML_HR_FKEY(RML_ID, 'DEPT_HEAD') DEPT_MANAGER_FKEY,
            USER_ROLE, APPS_UPDATE_VERSION, LAT, LANG, LAT_2, LANG_2,
            LAT_3, LANG_3, LAT_4, LANG_4, LAT_5, LANG_5, LAT_6, LANG_6,
            ATTN_RANGE_M, IS_ACTIVE_LAT_LANG, EMP_NAME,
            LINE_MANAGER_RML_ID, LINE_MANAGER_MOBILE, DEPT_HEAD_RML_ID, DEPT_HEAD_MOBILE_NO, PUNCH_DATA_SYN,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'P') PRESENT_TOTAL,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'L') LATE_TOTAL,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'A') ABSENT_TOTAL,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'H') HOLIDAY_TOTAL,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'W') WEEKEND_TOTAL,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'T') TOUR_TOTAL,
            DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'LV') LEAVE_TOTAL,
            (SELECT MESSAGE FROM DEVELOPERS.RML_HR_NOTIFICATION WHERE IS_ACTIVE = 1 AND CONCERN = R_CONCERN AND KEY_WORD = 'WELCOME_MESSAGE') USER_NOTIFICATION,
            TRACE_LOCATION
            FROM 
                DEVELOPERS.RML_HR_APPS_USER
            WHERE 
                RML_ID = '$rml_id' AND PASS_MD5 = '$user_password' AND IS_ACTIVE = 1";

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
                    "RML_ID"                => $objResultFound["RML_ID"],
                    "EMP_NAME"              => $objResultFound["EMP_NAME"],
                    "DESIGNATION"           => $objResultFound["DESIGNATION"],
                    "USER_ROLE"             => $objResultFound["USER_ROLE"],
                    "R_CONCERN"             => $objResultFound["R_CONCERN"],
                    // "LAT"                   => $objResultFound["LAT"],
                    // "LANG"                  => $objResultFound["LANG"],
                    // "LAT_2"                 => $objResultFound["LAT_2"],
                    // "LANG_2"                => $objResultFound["LANG_2"],
                    // "LAT_3"                 => $objResultFound["LAT_3"],
                    // "LANG_3"                => $objResultFound["LANG_3"],
                    // "LAT_4"                 => $objResultFound["LAT_4"],
                    // "LANG_4"                => $objResultFound["LANG_4"],
                    // "LAT_5"                 => $objResultFound["LAT_5"],
                    // "LANG_5"                => $objResultFound["LANG_5"],
                    // "LAT_6"                 => $objResultFound["LAT_6"],
                    // "LANG_6"                => $objResultFound["LANG_6"],
                    // "ATTN_RANGE_M"          => $objResultFound["ATTN_RANGE_M"],
                    //"IS_ACTIVE_LAT_LANG"    => $objResultFound["IS_ACTIVE_LAT_LANG"],
                    //"LINE_MANAGER_FKEY"     => $objResultFound["LINE_MANAGER_FKEY"],
                    //"DEPT_MANAGER_FKEY"     => $objResultFound["DEPT_MANAGER_FKEY"],
                    "LINE_MANAGER_RML_ID"   => $objResultFound["LINE_MANAGER_RML_ID"],
                    "LINE_MANAGER_MOBILE"   => $objResultFound["LINE_MANAGER_MOBILE"],
                    "DEPT_HEAD_RML_ID"      => $objResultFound["DEPT_HEAD_RML_ID"],
                    "DEPT_HEAD_MOBILE_NO"   => $objResultFound["DEPT_HEAD_MOBILE_NO"],
                   // "PUNCH_DATA_SYN"        => $objResultFound["PUNCH_DATA_SYN"],
                    "PRESENT_TOTAL"         => $objResultFound["PRESENT_TOTAL"],
                    "LATE_TOTAL"            => $objResultFound["LATE_TOTAL"],
                    "ABSENT_TOTAL"          => $objResultFound["ABSENT_TOTAL"],
                    "TOUR_TOTAL"            => $objResultFound["TOUR_TOTAL"],
                    "LEAVE_TOTAL"           => $objResultFound["LEAVE_TOTAL"],
                    "HOLIDAY_TOTAL"         => $objResultFound["HOLIDAY_TOTAL"],
                    "WEEKEND_TOTAL"         => $objResultFound["WEEKEND_TOTAL"],
                ];
                //incldue jwt token
                include_once('createToken.php');
                $jwtData = generate_jwt_token($responseData);
                $jsonData = ["status" => true,  "data" => $jwtData, "message" =>'Succfully Data Found.'];
                echo json_encode($jsonData);

            } else {
                $jsonData = ["status" => false, "message" => "Invalid credentials or user not active."];
                echo json_encode($jsonData);
            }
        } catch (Exception $e) {
            echo json_encode($error_log($e->getMessage()));
        } finally {
            oci_close($objConnect);
        }
    }
} else {
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();

?>