<?php

// Function to send the notification
function sendNotification($RML_ID, $EMP_NAME, $LM_KEY, $type, $START_DATE, $END_DATE)
{
	$type ='leave';
	if($type == 'leave'){
		$title = 'Leave Request!';
		$body	= "User ID: $RML_ID ($EMP_NAME) has requested leave from [$START_DATE] to [$END_DATE]. Please review and approve in the app or web portal.";
	}
	$url = 'https://fcm.googleapis.com/fcm/send';

	// Your Firebase Server API Key
	$apiKey = 'AAAAYQJP-_Q:APA91bE5NRrsjcbEkW71tJ57oXPJkqqiaR0wllx9W-065cE4IyqrHxiONWlBnf-72CLJmLHVNGnmBTrb0U2GrCPk8G4yRoFCORaH8CP5qrnBURo9DjjGJll4CSKqCapfwaB08fESymUX';

	$notification = [
		'title' => $title,
		'body' => $body,
		'sound' => 'default'
	];

	$data = [
		'to' => $LM_KEY,
		'notification' => $notification
	];

	$headers = [
		'Authorization: key=' . $apiKey,
		'Content-Type: application/json'
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

	$result = curl_exec($ch);
	if ($result === FALSE) {
		die('FCM Send Error: ' . curl_error($ch));
	}

	curl_close($ch);
	return $result;
}

// Function to log the notification
// function logNotification($firebaseToken, $title, $body, $response)
// {
// 	$logData = [
// 		'token' => $firebaseToken,
// 		'title' => $title,
// 		'body' => $body,
// 		'response' => $response,
// 		'timestamp' => date('Y-m-d H:i:s')
// 	];

// 	// Append log data to a file
// 	file_put_contents('notification_log.txt', json_encode($logData) . PHP_EOL, FILE_APPEND);
// }

// Example usage
//joy
// $firebaseToken = 'd5acv0QeStmEuGqR8SJKl5:APA91bHxAPvU9bztWBROh-CkRfibzUvhau8V_rT6VG1Fr5P8Zvgo_8H1i7EOGd0YFGRSDYEzBGryz8ARerc7JWE6YPv8ge6QGftLg0eV_wrAC7-ER6ue_pYiC2t1sdItr569zK6A1xJf';
//rafiq
// $firebaseToken = 'eUOhJlW4QPaOsvyTSxWQQX:APA91bEQNrsZe2rbLrHlzOSeWBq5AgUVDVJ8dsxHYlaRmShv514XS1WF9U9Io8LjUQoboClMaGhKbBM0qolestRssShZazhvXZv_F-TXPZeJnAlj5D7GD9s5YV4ijHh9salvVRMy0o5x';
//kabir
//$firebaseToken = 'dL-lIWYOREyvCcw1fv0Ose:APA91bEqThmSqYBfPwewrsMkKg9fa9ZdFjOF528Dl_P1grt-ydD9nBysEkv3Y-egAjQ_4gy0MiwL2FoYq4a94bbItyy4xVPiW9WVz_oAbOPJlsM2J4Li7ourtmiSAplBMQwYH3NkQWra';
//mizan
//$firebaseToken = 'dKmR3DgBS6aJda3N4kVU7Z:APA91bENuKYmfcPn_Y91m4pojjAaheqDQ_2Zncj0eArPfnll_G69uqj6Nuau3Mp2dEHsacVVUy9ssczp3z0ciUzJ6lAtSApoH4ONgwRH_62Bab7DOQW3a7XAK5QOIIVVQ0hAagfRadcZ';
//sholayman
// $firebaseToken = 'ccZCw9f_j-8:APA91bFcPFO-cl79Ozano3M0_3l2OMcPpGrcSpFjZbcmNfaz6w2TfWI1kItZR-atVrtFnILEqTqNGWt7Sjx7CdQetC58ZpU9cz7MX-tbtP497P0Y3ETA4kkJl_vNW5QAoSzN42JuVDUi';
// $title = 'Hey dude!';
// $body = "What's Up? how's your day?";

// $response = sendNotification($firebaseToken, $title, $body);

// Print the response and notification details
// echo "Notification Title: $title\n";
// echo "Notification Body: $body\n";
// echo "FCM Response: $response\n";

// Log the notification details
// logNotification($firebaseToken, $title, $body, $response);
