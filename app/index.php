<?php

# This script is merely used to simulate a cURL command

$endpoint = 'https://beta.youneed.it/phonenotifications/incoming_call_notification?calleeAor=201@1772-neth-01.youneed.tech&callerAor=3471921073';
//$endpoint = 'https://beta.youneed.it/phonenotifications/incoming_call_notification?calleeAor=45@214-neth-01.youneed.tech&callerAor=56';

openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
#curl_setopt($ch, CURLOPT_USERPWD, $serverCredentials['SystemId'] . ':' . $serverCredentials['Secret']);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Max connection time 2 sec
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // To get response data
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
  syslog(LOG_ERR, "Error: notification server answered $httpCode");
} else {
  syslog(LOG_ERR, "Voip Notification Report:");
  $reports = json_decode($response);
  if (empty($reports)) {
    syslog(LOG_ERR, "The callee didn't have any push-notification enabled clients.");
  } else {
    foreach ($reports as $key => $report) {
      foreach($report as $key => $value) {
        syslog(LOG_ERR, " $key: $value");
     }
     syslog(LOG_ERR,"\n");
  }
}
  
}

closelog();
