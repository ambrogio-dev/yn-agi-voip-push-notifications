#!/usr/bin/env php

<?php

/**
 * v.1.4.0
 * 
 * AGI script to send VoIP push notification through a YouNeed backend service.
 * It requires chmod 775 to run IF installed manually for test purposes.
 */

include_once '/etc/freepbx_db.conf';
define("AGIBIN_DIR", "/var/lib/asterisk/agi-bin");
include(AGIBIN_DIR."/phpagi.php");

$endpoint = 'https://pbx.youneed.it/phonenotifications/incoming_call_notification';
$configuration_path = '/etc/asterisk/nethcti_push_configuration.json';

$agi = new AGI();
$caller_id = get_var($agi, 'ARG1');
$caller_id_name = get_var($agi, 'ARG2');
$extension = get_var($agi, 'ARG3');
$call_id = get_var($agi, 'ARG4');
$pid = getmypid();

openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

syslog(LOG_INFO, "Starting script with AGI ARG3: $extension");
syslog(LOG_INFO, "Starting script (pid: $pid) for: $extension (uniqueID: $caller_id, Call-ID: $call_id)");

// Sometimes ARG3 is empty.
if (empty($extension)) {
   syslog(LOG_INFO, "ARG3 doesn't contain any ext.");
   exit(0);
}

// Check if App Extension
$real_callee = $agi->request['agi_callerid'];
if (strpos($real_callee,"92$extension") === FALSE) {
   syslog(LOG_INFO, "Ignoring $real_callee because it is not an app extension.");
   exit(0);	
}

// Exclude extensions whose state is different from NOT_INUSE
# Possible values:
# UNKNOWN | NOT_INUSE | INUSE | BUSY | INVALID | UNAVAILABLE | RINGING RINGINUSE | HOLDINUSE | ONHOLD
$state = get_var($agi,"EXTENSION_STATE($extension)");
if (empty($state)) {
   $state = "STATE_NOT_FOUND"; # custom state 
}
if ($state != "NOT_INUSE") {
    syslog(LOG_INFO, "Ignoring $extension because is not available ($state).");
    exit(0);
}

try {
   $serverCredentials = json_decode(file_get_contents($configuration_path ),TRUE);
   if (is_null($serverCredentials)) {
      syslog(LOG_ERR, "Error reading public hostname.");
      Throw new Exception('Error reading public hostname.');
   }
} catch (Exception $e) {
   $agi->verbose($e->getMessage());
   exit(1);
}

$public_hostname = $serverCredentials['Host'];
$server_user = $serverCredentials['SystemId'];
$server_secret = $serverCredentials['Secret'];

if (is_null($public_hostname) || empty($public_hostname)) {
   syslog(LOG_ERR, "Undefined hostname in $configuration_path");
   exit(1);
}

if (is_null($server_user) || is_null($server_secret) || empty($server_user) || empty($server_secret)) {
   syslog(LOG_ERR, "Undefined server credentials in $configuration_path");
   exit(1);
}

$callee = $extension . "@" . $public_hostname;
syslog(LOG_INFO, "Sending VoIP notification - from $caller_id (name: $caller_id_name) to $callee.");

$params = array('calleeAor' => $callee, 'callerAor' => $caller_id);
$url = $endpoint . '?' . http_build_query($params);
syslog(LOG_DEBUG, "url: $url");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // Max connection time 2 sec
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // We need the actual response for logging
curl_setopt($ch, CURLOPT_USERPWD, $server_user . ':' . $server_secret);
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

exit(0);

function get_var( $agi, $value) {
   $r = $agi->get_variable( $value );
   if ($r['result'] == 1) {
           $result = $r['data'];
           return $result;
   }
   return '';
}