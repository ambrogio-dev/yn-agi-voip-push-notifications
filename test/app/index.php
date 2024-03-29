<?php

# This script is merely used to simulate a cURL command

$endpoint = 'https://beta.youneed.it/phonenotifications/incoming_call_notification?calleeAor=45@214-neth-01.youneed.tech&callerAor=123';
//$endpoint = 'https://youneed.it/phonenotifications/incoming_call_notification?calleeAor=45@214-neth-01.youneed.tech&callerAor=56';

openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

$username = 'USERNAME';
$secret = 'PASSWORD';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_USERPWD, $username . ':' . $secret);
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Max connection time 2 sec
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // To get response data
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
   syslog(LOG_ERR, "Error while sending VoIP notification to $callee, server answered $httpCode");
} else {
   $reports = json_decode($response);
   if (empty($reports)) {
      syslog(LOG_ERR, "$callee didn't have any registered smartphones (no push notification tokens found).");
   } else {
      syslog(LOG_INFO, "VoIP notification report for $callee:");
      foreach ($reports as $key => $report) {
         foreach($report as $key => $value) {
            if ($key === "sent") { // extracts the sent status
               $is_sent = $value ? 'true' : 'false';
               syslog(LOG_INFO, " $key: $is_sent");
            } else { // fallback for the other key-value pairs
               syslog(LOG_INFO, " $key: $value");
            }
         }
         syslog(LOG_INFO,"\n");
      }
   }
}

closelog();