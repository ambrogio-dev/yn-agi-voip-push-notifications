<?php


# Endpoint example:
# https://beta.youneed.it/mobile/api/v1/phone/incoming_call_notification?calleeAor=45@214-neth-01.youneed.tech&callerAor=56

// syslog(LOG_ERR, "----------");
// $request_method = $_SERVER["REQUEST_METHOD"];
// $query_string = $_SERVER['QUERY_STRING'];
// syslog(LOG_ERR, "Request method: $request_method");
// syslog(LOG_ERR, "Query string: $query_string");
openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $serverCredentials['NotificationServerURL']);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
#curl_setopt($ch, CURLOPT_USERPWD, $serverCredentials['SystemId'] . ':' . $serverCredentials['Secret']);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
  syslog(LOG_ERR, "Error: notification server answered $httpCode");
} else {
  syslog(LOG_ERR, "OK");
  #syslog(LOG_ERR, "Sent wake up notification for extension $extension");
}

closelog();