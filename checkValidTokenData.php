<?php
require_once 'vendor/autoload.php';

use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// include_once('validateToken.php');
$config = require_once('inc/config.php');
$secret_key = $config['jwt_token'];
$headers = getallheaders();

if(isset($headers['Authorization']) || isset($headers['authorization'])){
    $tokenData  = $headers['Authorization']?? $headers['authorization'];
    $statusData =  validate_jwt_token($tokenData ,$secret_key);
    return ["data" => $statusData,"status" => true ];
}else {
    $jsonData = ["status" => false, "message" => "Authorization Token Not Found!" , "data" => $_POST,'token' => $headers['authorization'] ];
    echo json_encode($jsonData);
    die();
}



function validate_jwt_token($jwt_token, $secret_key) {
    // The token is prefixed with 'Bearer ', split and get the actual token part
    $tokenData  = explode(' ', $jwt_token);
    $data       = isset($tokenData[1]) ? $tokenData[1] : $tokenData[0];

    try {
        // Decode the JWT
        $decoded = JWT::decode($data, new Key($secret_key, 'HS256'));
        return $decoded;
    } catch (ExpiredException $e) {
        $jsonData = ["status" => false, "message" => "Token Expired!"];
        echo json_encode($jsonData); 
        die();
        // throw new Exception('Token expired');
    } catch (SignatureInvalidException $e) {
        
        $jsonData = ["status" => false, "message" => "Invalid Token Signature!"];
        echo json_encode($jsonData); 
        die();
    } catch (BeforeValidException $e) {
        $jsonData = ["status" => false, "message" => "Token not valid yet!"];
        echo json_encode($jsonData); 
        die();
    } catch (Exception $e) {
        $jsonData = ["status" => false, "message" => "Invalid Token!"];
        echo json_encode($jsonData); 
        die();
    }
}