<?php
require_once('inc_jwt_helper.php');

try {

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(405);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Method not allowed"]);
        exit();
    }

    $device_token = $_POST["device_token"];
    $bundle_id = $_POST["bundle_id"];
    $team_id = $_POST["team_id"];
    $key_id = $_POST["key_id"];
    $title = $_POST["title"];
    $body = $_POST["body"];

    if (empty($device_token) || empty($bundle_id) || empty($team_id) || empty($key_id) || empty($title) || empty($body)) {
        http_response_code(400);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Bad request - mandatory fields missing. device_token, bundle_id, team_id, key_id, title, body are required"]);
        exit();
    }

    if(empty($_FILES)){
        http_response_code(400);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Bad request - mandatory fields missing. cert file is required"]);
        exit();
    }

    // $authKey = "AuthKey_8R342T9J4R.p8";
    $arParam['teamId'] = $team_id; // Get it from Apple Developer's page
    $arParam['authKeyId'] = $key_id; // Get it from Apple Developer's page
    $arParam['apns-topic'] = $bundle_id;
    $arClaim = ['iss' => $arParam['teamId'], 'iat' => time()];
    $arParam['p_key'] = file_get_contents($_FILES["cert"]["tmp_name"]);
    $arParam['header_jwt'] = JWT::encode($arClaim, $arParam['p_key'], $arParam['authKeyId'], 'RS256');

    // Sending a request to APNS
    $stat = push_to_apns($arParam, $device_token, $title, $body, $ar_msg);
    if ($stat == FALSE) {
        // err handling
        exit();
    }
    http_response_code(200);
    header("Content-Type: application/json");
    echo json_encode(["message" => "notification sent."]);
    exit();
} catch (Exception $e) {
    $error = json_encode(["error" => $e->getMessage(), "stacktrace" => $e->getTrace()]);
    http_response_code($e->getCode());
    header("Content-Type: application/json");
    echo $error;
}

// ***********************************************************************************
function push_to_apns($arParam, $device_token, $title, $body, &$ar_msg)
{

    $arSendData = array();

    $url_cnt = "api.development.push.apple.com:443";
    $arSendData['aps']['alert']['title'] = sprintf($title); // Notification title
    $arSendData['aps']['alert']['body'] = sprintf($body); // body text
    $arSendData['data']['jump-url'] = $url_cnt; // other parameters to send to the app

    $sendDataJson = json_encode($arSendData);

    $endPoint = 'https://api.development.push.apple.com:443/3/device'; // https://api.push.apple.com/3/device

    //　Preparing request header for APNS
    $ar_request_head[] = sprintf("content-type: application/json");
    $ar_request_head[] = sprintf("authorization: bearer %s", $arParam['header_jwt']);
    $ar_request_head[] = sprintf("apns-topic: %s", $arParam['apns-topic']);
    $ar_request_head[] = sprintf("apns-push-type: %s", 'voip');

    $dev_token = $device_token;  // Device token

    $url = sprintf("%s/%s", $endPoint, $dev_token);

    $ch = curl_init($url);


    header("Content-Type: application/json");

    curl_setopt($ch, CURLOPT_POSTFIELDS, $sendDataJson);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2_0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $ar_request_head);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpcode != 200) {
        http_response_code($httpcode);
        echo $response;
    } else {
        http_response_code($httpcode);
        echo $response;
    }

    curl_close($ch);

    return TRUE;
}
