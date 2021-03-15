<?php


# This script is merely used to simulate a cURL command

$endpoint = 'https://beta.youneed.it/phonenotifications/incoming_call_notification?calleeAor=201@1772-neth-01.youneed.tech&callerAor=3471921073';

openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
#curl_setopt($ch, CURLOPT_USERPWD, $serverCredentials['SystemId'] . ':' . $serverCredentials['Secret']);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
  syslog(LOG_ERR, "Error: notification server answered $httpCode");
} else {
  syslog(LOG_ERR, "Sent VoIP notification");
}

closelog();
