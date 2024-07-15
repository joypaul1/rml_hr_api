<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {
    
    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if($checkValidTokenData['status']){
        if($checkValidTokenData['data']->data->RML_ID){
            $RML_ID = $checkValidTokenData['data']->data->RML_ID; // set RML Variable Data
            //**Start data base connection  & status check **//
            include_once('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //**End data base connection S& status check **//

            //**Start Query & Return Data Response **//
            try {
                $SQL = "SELECT 
                RML_ID, R_CONCERN, DESIGNATION, USER_ROLE,  EMP_NAME,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'P') PRESENT_TOTAL,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'L') LATE_TOTAL,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'A') ABSENT_TOTAL,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'H') HOLIDAY_TOTAL,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'W') WEEKEND_TOTAL,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'T') TOUR_TOTAL,
                DEVELOPERS.RML_HR_ATTN_STATUS_COUNT(RML_ID, TO_DATE(TO_CHAR(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 'dd/mm/yyyy'), 'dd/mm/yyyy'), TO_DATE(TO_CHAR(ADD_MONTHS(TRUNC(SYSDATE) - (TO_NUMBER(TO_CHAR(SYSDATE, 'DD')) - 1), 1) - 1, 'dd/mm/yyyy'), 'dd/mm/yyyy'), 'LV') LEAVE_TOTAL
                FROM 
                    DEVELOPERS.RML_HR_APPS_USER
                WHERE 
                    RML_ID = '$RML_ID' AND IS_ACTIVE = 1";
    
                $strSQL = @oci_parse($objConnect, $SQL);            
                @oci_execute($strSQL);
                $objResultFound = @oci_fetch_assoc($strSQL);
        
                if ($objResultFound) {
                    $responseData = [
                        "RML_ID"                => $objResultFound["RML_ID"],
                        "EMP_NAME"              => $objResultFound["EMP_NAME"],
                        "DESIGNATION"           => $objResultFound["DESIGNATION"],
                        "R_CONCERN"             => $objResultFound["R_CONCERN"],
                        "PRESENT_TOTAL"         => $objResultFound["PRESENT_TOTAL"],
                        "LATE_TOTAL"            => $objResultFound["LATE_TOTAL"],
                        "ABSENT_TOTAL"          => $objResultFound["ABSENT_TOTAL"],
                        "TOUR_TOTAL"            => $objResultFound["TOUR_TOTAL"],
                        "LEAVE_TOTAL"           => $objResultFound["LEAVE_TOTAL"],
                        "HOLIDAY_TOTAL"         => $objResultFound["HOLIDAY_TOTAL"],
                        "WEEKEND_TOTAL"         => $objResultFound["WEEKEND_TOTAL"],
                    ];
                    $jsonData = ["status" => true,  "data" => $responseData, "message" =>'Successfully Data Found.'];
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
            //**End Query & Return Data Response **//
        }else{
            $jsonData = ["status" => false, "message" => "Missing Token Required Parameters."];
            echo json_encode($jsonData); 
            die();
        }
       
    }
} else {
    $jsonData = ["status" => false, "message" => "Request method not accepted"];
    echo json_encode($jsonData);
}
die();

?>