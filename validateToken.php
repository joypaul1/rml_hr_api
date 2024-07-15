<?php
require_once 'vendor/autoload.php';

use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function validate_jwt_token($jwt_token, $secret_key) {
    // Assuming the token is prefixed with 'Bearer ', split and get the actual token part
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
