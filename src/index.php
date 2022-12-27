<?php


use Pushok\AuthProvider\Token;
use Pushok\Client;
use Pushok\Notification;
use Pushok\Payload;
use Pushok\Payload\Alert;


try {

    if ($_SERVER["REQUEST_METHOD"] != "POST") {
        http_response_code(405);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Method not allowed"]);
        exit();
    }

    $device_token = $_POST["device_token"];
    $apns_topic = $_POST["apns_topic"];

    if (empty($device_token) || empty($apns_topic)) {
        http_response_code(400);
        header("Content-Type: application/json");
        echo json_encode(["error" => "Bad request"]);
        exit();
    }


    $p8file = __DIR__ . "/AuthKey_8R342T9J4R.p8";

    $options = [
        'key_id' => 'RQCKA5ZH38', // The Key ID obtained from Apple developer account
        'team_id' => 'DDDDEEEEFF', // The Team ID obtained from Apple developer account
        'app_bundle_id' => $apns_topic, // The bundle ID for app obtained from Apple developer account
        'private_key_path' => $p8file, // Path to private key
        'private_key_secret' => null // Private key secret
    ];


    // Be aware of thing that Token will stale after one hour, so you should generate it again.
    // Can be useful when trying to send pushes during long-running tasks
    $authProvider = Token::create($options);

    $alert = Alert::create()->setTitle('Hello!');
    $alert = $alert->setBody('First push notification');

    $payload = Payload::create()->setAlert($alert);

    //set notification sound to default
    $payload->setSound('default');

    //add custom value to your notification, needs to be customized
    $payload->setCustomValue('key', 'value');

    $deviceTokens = ['<device_token_1>', '<device_token_2>', '<device_token_3>'];

    $notifications = [];
    foreach ($deviceTokens as $deviceToken) {
        $notifications[] = new Notification($payload, $deviceToken);
    }

    // If you have issues with ssl-verification, you can temporarily disable it. Please see attached note.
    // Disable ssl verification
    // $client = new Client($authProvider, $production = false, [CURLOPT_SSL_VERIFYPEER=>false] );
    $client = new Client($authProvider, $production = false);
    $client->addNotifications($notifications);



    $responses = $client->push(); // returns an array of ApnsResponseInterface (one Response per Notification)

    foreach ($responses as $response) {
        // The device token
        $response->getDeviceToken();
        // A canonical UUID that is the unique ID for the notification. E.g. 123e4567-e89b-12d3-a456-4266554400a0
        $response->getApnsId();

        // Status code. E.g. 200 (Success), 410 (The device token is no longer active for the topic.)
        $response->getStatusCode();
        // E.g. The device token is no longer active for the topic.
        $response->getReasonPhrase();
        // E.g. Unregistered
        $response->getErrorReason();
        // E.g. The device token is inactive for the specified topic.
        $response->getErrorDescription();
        $response->get410Timestamp();
    }


    http_response_code($httpcode);
    header("Content-Type: application/json");
    echo json_encode(["response" => $response]);
    die();
} catch (Exception $e) {
    $error = json_encode(["error" => $e->getMessage(), "stacktrace" => $e->getTrace()]);
    http_response_code($e->getCode());
    header("Content-Type: application/json");
    echo $error;
}
