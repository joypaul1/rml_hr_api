<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require_once 'vendor/autoload.php';
use Firebase\JWT\JWT;

$secret_key = '$_RMLIT2024@#_$';

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData = require_once ("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once ('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//

            require_once ('InputValidator.php');  // Include InputValidator class
            $requiredFields = ['USER_IMAGE'];  // Define required fields

            // Initialize input validator with POST data **//
            $validator = new InputValidator($_POST);
            if (!isset($_FILES['USER_IMAGE'])) {
                // Set the HTTP status code to 400 Bad Request
                http_response_code(400);
                $jsonData = ["status" => false, "message" => "Missing Required Parameters!"];
                echo json_encode($jsonData);
                die();
            }
            // **Initialize input validator with POST Data**//

            $RML_ID = $checkValidTokenData['data']->data->RML_ID;
            $ENTRY_BY = $RML_ID;

            // Check if an old image exists
            $SQL = "SELECT USER_IMAGE FROM RML_HR_APPS_USER_IMAGE WHERE USER_ID = '$RML_ID'";
            $query = @oci_parse($objConnect, $SQL);
            @oci_execute($query);
            $oldImageURL = @oci_fetch_assoc($query)['USER_IMAGE'];
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

            if ($oldImageURL) {
                // Extract the file path from the URL
                $oldImagePath = str_replace($protocol . $_SERVER['HTTP_HOST'] . '/', '../', $oldImageURL);
                // Remove the old image file if it exists
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }

            //image upload process
            if (isset($_FILES['USER_IMAGE'])) {
                $file = $_FILES['USER_IMAGE'];
                $uploadDir = '../rHRT/uploads/';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate a unique filename using timestamp and original extension
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $customFileName = 'user_img_' . $RML_ID . '_' . time() . '.' . $extension;
                $uploadFile = $uploadDir . $customFileName;

                // Check uploaded image dimensions
                list($width, $height) = getimagesize($file['tmp_name']);

                // Maximum dimensions for resizing
                $maxWidth = 200;
                $maxHeight = 200;

                // Create an image resource from the uploaded file
                $source = imagecreatefromstring(file_get_contents($file['tmp_name']));

                // Create a blank image with the desired dimensions
                $target = imagecreatetruecolor($maxWidth, $maxHeight);

                // Resize the source image to fit into the target dimensions
                imagecopyresampled($target, $source, 0, 0, 0, 0, $maxWidth, $maxHeight, $width, $height);

                // Save the resized image
                imagepng($target, $uploadFile); // Change format if needed (e.g., imagejpeg for JPEG)

                // Free up memory
                imagedestroy($source);
                imagedestroy($target);
                // Update database after resizing and saving the image
                $imageFinalName = str_replace('../', '', $uploadFile);
                $imageURL = $protocol . $_SERVER['HTTP_HOST'] . '/' . $imageFinalName;

                //*** Start Query & Return Data Response ***//
                $SQL = "MERGE INTO RML_HR_APPS_USER_IMAGE USING dual
                ON (USER_ID = '$RML_ID')
                WHEN MATCHED THEN
                    UPDATE SET USER_IMAGE =  '$imageURL', UPLOAD_DATE_TIME = SYSDATE
                WHEN NOT MATCHED THEN
                    INSERT (USER_ID, USER_IMAGE, UPLOAD_DATE_TIME) VALUES ('$RML_ID', '$imageURL', SYSDATE)";

                $strSQL = @oci_parse($objConnect, $SQL);

                // Execute the statement
                if (@oci_execute($strSQL)) {

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
                        LEFT JOIN DEVELOPERS2.RML_HR_APPS_USER_IMAGE IMAGE
                            ON U.RML_ID = IMAGE.USER_ID
                    WHERE RML_ID = '$RML_ID'
                        AND IS_ACTIVE = 1";

                    $strSQL = @oci_parse($objConnect, $SQL);
                    @oci_execute($strSQL);
                    $objResultFound = @oci_fetch_assoc($strSQL);
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

                    $jwtData = generate_jwt_token($responseData);

                    http_response_code(200);
                    $jsonData = [
                        "status" => true,
                        "message" => 'Successfully Uploaded Image.',
                        'imageURL' => $imageURL,
                        'updateToken' => $jwtData
                    ];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(403);
                    $e = @oci_error($strSQL);
                    $jsonData = ["status" => false, "message" => htmlentities($e['message'], ENT_QUOTES)];
                    echo json_encode($jsonData);
                }
                //*** End Query & Return Data Response ***//

            } else {
                http_response_code(400); // Internal Server Error
                echo json_encode(array("message" => "Sorry! File is not selected.", "status" => false));
            }
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


function generate_jwt_token($data)
{
    global $secret_key;
    $issued_at = time();
    // $expiration_time = $issued_at + (60 * 60); // valid for 1 hour
    $expiration_time = $issued_at + (24 * 60 * 60); // valid for 1 day

    $payload = array(
        'iss' => 'rangsmotors',
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'data' => $data
    );

    return JWT::encode($payload, $secret_key, 'HS256');
}
