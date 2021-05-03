#!/usr/bin/env php

<?php

/**
 * v.1.1.0
 * 
 * AGI script to send VoIP push notification through a YouNeed backend service.
 * It requires chmod 775 to run IF installed manually for test purposes.
 */

include_once '/etc/freepbx_db.conf';
define("AGIBIN_DIR", "/var/lib/asterisk/agi-bin");
include(AGIBIN_DIR."/phpagi.php");

$endpoint = 'https://youneed.it/phonenotifications/incoming_call_notification';
$configuration_path = '/etc/asterisk/nethcti_push_configuration.json';

$agi = new AGI();
$call_id = $agi->request['agi_uniqueid'];
$caller_id = $agi->request['agi_callerid'];
$caller_id_name = $agi->request['agi_calleridname'];
$ext_string = get_var($agi, 'ARG3');
$extensions = explode('-',$ext_string); // holds only main extensions

openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

syslog(LOG_INFO, "Starting script with AGI ARG3: $ext_string");

// Sometimes ARG3 is empty.
if (empty($ext_string) || empty($extensions)) {
   syslog(LOG_INFO, "ARG3 doesn't contain any ext.");
   exit(0);
}

// Check CF
foreach ($extensions as $extension) {
   $cf = $agi->database_get('CF',$extension);
   $cf = $cf['data'];
   if (!empty($cf)) {
      // check if cf is associated to a PBX user
      // if you set as cf a PBX extension, please use the main ext!
      $device_str = sprintf("%s/device", $cf);
      $device = $agi->database_get('AMPUSER',$device_str);
      $device = $device['data'];
      if (!empty($device)) {
         syslog(LOG_INFO, "Adding CF $cf to the extensions to be notified.");
         $extensions[] = $cf;
      } else {
         syslog(LOG_INFO, "Ignoring CF $cf because is not a PBX extension.");
      }
   }
}
$extensions = array_unique($extensions,SORT_REGULAR);

// Check DND
foreach ($extensions as $index => $extension) {
   $dnd = $agi->database_get('DND',$extension);
   $dnd = $dnd['data'];
   if (!empty($dnd)) {
       unset($extensions[$index]);
   }
}

if (empty($extensions)) {
   syslog(LOG_INFO, "No extensions eligible for push notifications found.");
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

// Usually "$extensions" contains a single element so, sending a HTTP request for every extension should be fine.
foreach ($extensions as $extension) {
   if (empty($extension)) {
      // for some reasons we get some empty extensions
      continue;
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