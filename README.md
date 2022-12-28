# apns-notification

Send iOS VoIP alerts, or Push Notifications

API ENDpoint http://localhost/apns-notification/index.php

Request:

```php
<?php

$curl = curl_init();
$bundleId = 'com.bundle.id'; //for voip append .voip in bundleId i.e. com.bundle.id.voip
curl_setopt_array($curl, array(
  CURLOPT_URL => 'http://localhost/apns/index.php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array('device_token' => '<device_token>','bundle_id' => $bundleId,'team_id' => '<ABCTEAM>','key_id' => '<ABCKEY1>','title' => '<title>','body' => '<body>','cert'=> new CURLFILE('</path_to_key_file/file.p8>')),
  CURLOPT_HTTPHEADER => array(
    'Accept: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;

```
