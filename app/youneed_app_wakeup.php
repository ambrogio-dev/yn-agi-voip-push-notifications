#!/usr/bin/env php
<?php

// requires chmod 755

include_once '/etc/freepbx_db.conf';
define("AGIBIN_DIR", "/var/lib/asterisk/agi-bin");
include(AGIBIN_DIR."/phpagi.php");

$endpoint = 'https://beta.youneed.it/phonenotifications/incoming_call_notification';
$configuration_path = '/etc/asterisk/nethcti_push_configuration.json';

$agi = new AGI();
$call_id = $agi->request['agi_uniqueid'];
$caller_id = $agi->request['agi_callerid'];
$caller_id_name = $agi->request['agi_calleridname'];
$ext_string = get_var($agi, 'ARG3');
$extensions = explode('-',$ext_string); # holds only main extensions

openlog("Ambrogio", LOG_PID | LOG_PERROR, LOG_LOCAL0);

// Check CF
foreach ($extensions as $extension) {
   $cf = $agi->database_get('CF',$extension);
   $cf = $cf['data'];
   if (!empty($cf)) {
       $extensions[] = $cf;
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

// Usually "$extensions" contains a single element, so sending a HTTP request for every extension should be fine.
foreach ($extensions as $extension) {
   $callee = $extension . "@" . $public_hostname;
   syslog(LOG_INFO, "Sending VoIP notification - from $caller_id (name: $caller_id_name) to $callee.");

   $params = array('calleeAor' => $callee, 'callerAor' => $caller_id);
   $url = $endpoint . '?' . http_build_query($params);
   syslog(LOG_DEBUG, "url: $url");

   $ch = curl_init();
   curl_setopt($ch, CURLOPT_URL, $url);
   curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
   curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));
   curl_setopt($ch, CURLOPT_USERPWD, $server_user . ':' . $server_secret);
   $response = curl_exec($ch);
   $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   curl_close($ch);

   if ($httpCode != 200) {
      syslog(LOG_ERR, "Error while sending VoUP notification to $callee, server answered $httpCode");
   } else {
      syslog(LOG_INFO, "VoIP notification sent to $callee.");
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
