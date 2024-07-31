<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $checkValidTokenData    =   require_once("checkValidTokenData.php");
    if ($checkValidTokenData['status']) {
        if ($checkValidTokenData['data']->data->RML_ID) {

            //** ORACLE DATA CONNECTION***//
            include_once('../test_api/inc/connoracle.php');
            if ($isDatabaseConnected !== 1) {
                $jsonData = ["status" => false, "message" => "Database Connection Failed."];
                echo json_encode($jsonData);
                die();
            }
            //** ORACLE DATA CONNECTION***//


            // require_once('InputValidator.php');  // Include InputValidator class
            // $requiredFields = ['USER_IMAGE'];  // Define required fields

            // Initialize input validator with POST data **//
            // $validator = new InputValidator($_POST);
            if (!isset($_FILES['USER_IMAGE'])) {
                // Set the HTTP status code to 400 Bad Request
                http_response_code(400);
                $jsonData = ["status" => false, "message" => "Missing Required Parameters!"];
                echo json_encode($jsonData);
                die();
            }
            // **Initialize input validator with POST Data**//

            $RML_ID         = $checkValidTokenData['data']->data->RML_ID;
            $ENTRY_BY       = $RML_ID;

            //image upload process
            if (isset($_FILES['USER_IMAGE'])) {
                $file      = $_FILES['USER_IMAGE'];
                $uploadDir = '../rHRT/uploads/';

                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                // Generate a unique filename using timestamp and original extension
                $extension      = pathinfo($file['name'], PATHINFO_EXTENSION);
                $customFileName = 'user_img_' . $RML_ID . '_' . time() . '.' . $extension;
                $uploadFile     = $uploadDir . $customFileName;

                // Check uploaded image dimensions
                list($width, $height) = getimagesize($file['tmp_name']);

                // Maximum dimensions for resizing
                $maxWidth  = 200;
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
                //Update database after resizing and saving the image
                $imageFinalName = str_replace('../', '', $uploadFile);
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $imageURL = $protocol . $_SERVER['HTTP_HOST'] . '/' . $imageFinalName;
                // echo $base_url;
                //*** Start Query & Return Data Response ***//
                $SQL = "MERGE INTO RML_HR_APPS_USER_IMAGE USING dual
                ON (USER_ID = '$RML_ID')
                WHEN MATCHED THEN
                    UPDATE SET USER_IMAGE =  '$imageFinalName', UPLOAD_DATE_TIME = SYSDATE
                WHEN NOT MATCHED THEN
                    INSERT (USER_ID, USER_IMAGE,UPLOAD_DATE_TIME) VALUES ('$RML_ID', '$imageFinalName', SYSDATE)";

                $strSQL = @oci_parse($objConnect, $SQL);

                // Execute the statement
                if (@oci_execute($strSQL)) {
                    http_response_code(200);
                    // @oci_free_statement($stid);
                    // @oci_close($conn);
                    $jsonData = ["status" => true,  "message" => 'Successfully Upload Image.', 'imageURL' => $imageURL];
                    echo json_encode($jsonData);
                } else {
                    http_response_code(403);
                    $e = @oci_error($strSQL);
                    $jsonData = ["status" => false,  "message" => htmlentities($e['message'], ENT_QUOTES)];
                    echo json_encode($jsonData);
                }
                //*** End Query & Return Data Response ***//

            } else {
                http_response_code(400); // Internal Server Error
                echo json_encode(array("message" => "Sorry! File is not select.", "status" => false));
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
