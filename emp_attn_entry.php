<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once ("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once ('../rml_hr_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//

            require_once ('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['LATITUDE', 'LONGITUDE', 'INSIDE_OR_OUTSIDE', 'OUTSIDE_REMAKRKS', 'EMP_DISTANCE'];  // Define required fields

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
            $LATITUDE = $validator->get('LATITUDE');   // Retrieve sanitized inputs
            $LONGITUDE = $validator->get('LONGITUDE');   // Retrieve sanitized inputs
            $INSIDE_OR_OUTSIDE = $validator->get('INSIDE_OR_OUTSIDE');   // Retrieve sanitized inputs
            $OUTSIDE_REMAKRKS = $validator->get('OUTSIDE_REMAKRKS');   // Retrieve sanitized inputs
            $EMP_DISTANCE = $validator->get('EMP_DISTANCE');   // Retrieve sanitized inputs
            $RML_ID = $checkValidTokenData['data']->data->RML_ID;
            $LINE_MANAGER_RML_ID = $checkValidTokenData['data']->data->LINE_MANAGER_RML_ID;
            $ENTRY_BY = $RML_ID;
            $BATTERY_LEVEL= $_POST['BATTERY_LEVEL']??'';
            $APPS_VERSION = $_POST['APPS_VERSION']??'';

            //*** Start Query & Return Data Response ***//
            try {
                $SQL = "BEGIN RML_HR_ATTN_CREATE('$RML_ID','$LATITUDE','$LONGITUDE','$INSIDE_OR_OUTSIDE','$OUTSIDE_REMAKRKS', '$EMP_DISTANCE', '$ENTRY_BY');END;";
                $strSQL = @oci_parse($objConnect, $SQL);
                // $today_date = date('d/m/Y');
                //** @ATTENDANCE PRROCESSING **//
                $attnSQL = @oci_parse($objConnect, "DECLARE  start_date VARCHAR2(100):=to_char(SYSDATE,'dd/mm/yyyy');
                BEGIN RML_HR_ATTN_PROC('$RML_ID',TO_DATE(start_date,'dd/mm/yyyy') ,TO_DATE(start_date,'dd/mm/yyyy') );END;");
                // ECHO  "DECLARE  start_date VARCHAR2(100):=to_char(SYSDATE,'dd/mm/yyyy');
                // BEGIN RML_HR_ATTN_PROC('$RML_ID',TO_DATE(start_date,'dd/mm/yyyy') ,TO_DATE(start_date,'dd/mm/yyyy') );END;";
                //** @END ATTENDANCE PRROCESSING **//

                //** Location Entry **/
                $SQL3 = "INSERT INTO RML_HR_APPS_USER_LOCATION
                (RML_ID, LOC_LAT, LOC_LANG, BATTERY_LEVEL, ENTRY_TIME, APPS_VERSION)
                VALUES ('$RML_ID', '$LATITUDE', '$LONGITUDE', '$BATTERY_LEVEL', SYSDATE, '$APPS_VERSION')";
                $strSQL3 = @oci_parse($objConnect, $SQL3);
                @oci_execute($strSQL3);

                //** Location Entry **/

                $TODAY = date('d/m/Y');
                $SQL2 = "SELECT a.RML_ID,
                                NVL(b.STATUS, 'A') AS ATTN_STATUS
                            FROM RML_HR_APPS_USER a
                            LEFT JOIN RML_HR_ATTN_DAILY_PROC b
                                ON a.RML_ID = b.RML_ID
                                AND TRUNC(b.ATTN_DATE) = TO_DATE('$TODAY', 'DD/MM/YYYY')
                            WHERE a.RML_ID = '$RML_ID' AND IS_ACTIVE = 1";
                $strSQL2 = @oci_parse($objConnect, $SQL2);
                // @oci_execute($strSQL2);
                

                if (@oci_execute($strSQL)&& @oci_execute($attnSQL) && @oci_execute($strSQL2)) {
                    http_response_code(200);
                    $objResultFound = @oci_fetch_assoc($strSQL2);
                    if ($INSIDE_OR_OUTSIDE == 'Inside Office') {
                        $jsonData = array(
                            "status" => true,
                            "message" => "Your office attendance successfully saved.",
                            "push_message" => '',
                            'push_id' => '',
                            "ATTN_STATUS" => $objResultFound["ATTN_STATUS"]
                        );
                    } else {
                        $jsonData = array(
                            "status" => true,
                            "ATTN_STATUS" => $objResultFound["ATTN_STATUS"],
                            'push_id' => $LINE_MANAGER_RML_ID,
                            "message" => "Attendance logged! Awaiting your line manager's approval to make it visible in the report. 👍",
                            "push_message" => "Your concern $RML_ID, have given outside acctendance. You should approved/denied this attendance."
                        );
                    }
                    echo json_encode($jsonData);
                } else {
                    http_response_code(403);
                    @$lastError = error_get_last();
                    @$error = $lastError ? "" . $lastError["message"] . "" : "";
                    @$str_arr_error = preg_split("/\,/", $error);
                    $jsonData = ["status" => false, "message" => @$str_arr_error];
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
        } else {
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
