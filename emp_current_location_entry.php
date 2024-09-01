<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    //$checkValidTokenData = require_once("checkValidTokenData.php");
    //if ($checkValidTokenData['status']) {
    // if ($checkValidTokenData['data']->data->RML_ID) {

    include_once('../rml_hr_api/inc/connoracle.php');
    if ($isDatabaseConnected !== 1) {
        echo json_encode(["status" => false, "message" => "Database Connection Failed."]);
        die();
    }
    // PRINT_R($_POST);
    require_once('InputValidator.php');
    $requiredFields = ['LOC_LAT', 'LOC_LANG', 'ENTRY_TIME', 'BATTERY_LEVEL', 'APPS_VERSION','RML_ID'];

    $validator = new InputValidator($_POST);
    if (!$validator->validateRequired($requiredFields)) {
        http_response_code(400);
        echo json_encode(["status" => false, "message" => "Missing Required Parameters."]);
        die();
    }

    $validator->sanitizeInputs();
    $LOC_LAT        = $validator->get('LOC_LAT');
    $LOC_LANG       = $validator->get('LOC_LANG');
    $ENTRY_TIME     = $validator->get('ENTRY_TIME');
    $BATTERY_LEVEL  = $validator->get('BATTERY_LEVEL');
    $APPS_VERSION   = $validator->get('APPS_VERSION');
    $RML_ID         = $validator->get('RML_ID');
    //$RML_ID = $checkValidTokenData['data']->data->RML_ID;
    //$ENTRY_BY = $RML_ID;

    $pattern = '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2} (AM|PM)$/';
    if (!preg_match($pattern, $ENTRY_TIME)) {
        http_response_code(400);
        echo json_encode(['status' => false, 'message' => 'Date format is not valid!']);
        die();
    }

    try {
        $SQL = "INSERT INTO RML_HR_APPS_USER_LOCATION
                        (RML_ID, LOC_LAT, LOC_LANG, BATTERY_LEVEL, ENTRY_TIME, APPS_VERSION, LOCATION_TYPE)
                        VALUES (:RML_ID, :LOC_LAT, :LOC_LANG, :BATTERY_LEVEL, TO_DATE(:ENTRY_TIME, 'DD/MM/YYYY HH:MI:SS AM'), :APPS_VERSION, 1)";

        $strSQL = oci_parse($objConnect, $SQL);
        oci_bind_by_name($strSQL, ':RML_ID', $RML_ID);
        oci_bind_by_name($strSQL, ':LOC_LAT', $LOC_LAT);
        oci_bind_by_name($strSQL, ':LOC_LANG', $LOC_LANG);
        oci_bind_by_name($strSQL, ':BATTERY_LEVEL', $BATTERY_LEVEL);
        oci_bind_by_name($strSQL, ':ENTRY_TIME', $ENTRY_TIME);
        oci_bind_by_name($strSQL, ':APPS_VERSION', $APPS_VERSION);
        // oci_bind_by_name($strSQL, ':LOCATION_TYPE', 1);
        // ECHO $strSQL;
        if (oci_execute($strSQL)) {
            http_response_code(200);
            echo json_encode(["status" => true, "message" => 'Successfully Location Entry.']);
        } else {
            http_response_code(500);
            $error = oci_error($strSQL);
            echo json_encode(["status" => false, "message" => $error['message']]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => false, "message" => $e->getMessage()]);
    } finally {
        oci_close($objConnect);
    }
    // } else {
    //     http_response_code(400);
    //     echo json_encode(["status" => false, "message" => "Missing Token Required Parameters."]);
    // }
    //}
} else {
    http_response_code(405);
    echo json_encode(["status" => false, "message" => "Request method not accepted"]);
}
