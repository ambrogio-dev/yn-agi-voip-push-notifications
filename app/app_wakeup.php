#!/usr/bin/env php
<?php

include_once '/etc/freepbx_db.conf';
define("AGIBIN_DIR", "/var/lib/asterisk/agi-bin");
include(AGIBIN_DIR."/phpagi.php");

$agi = new AGI();
$call_id = $agi->request['agi_uniqueid'];
$caller_id = $agi->request['agi_callerid'];
$caller_id_name = $agi->request['agi_calleridname'];
$ext_string = get_var($agi, 'ARG3');
$extensions = explode('-',$ext_string); # why there are more than 1?

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

// TEST: Get all extensions from mainextensions
$devices = array();
foreach ($extensions as $extension) {
   $device_str = sprintf("%s/device", $extension);
   $device = $agi->database_get('AMPUSER',$device_str);
   $device = $device['data'];
   $devices = array_merge($devices,explode('&',$device));
}
$devices = array_unique($devices,SORT_REGULAR);

foreach ($devices as $device) {
   syslog(LOG_ERR, "device: $device");
}

try {
   $serverCredentials = json_decode(file_get_contents('/etc/asterisk/nethcti_push_configuration.json'),TRUE);
   if (is_null($serverCredentials)) {
      syslog(LOG_ERR, "Error reading public hostname");
      Throw new Exception('Error reading public hostname');
   }
} catch (Exception $e) {
   $agi->verbose($e->getMessage());
   exit(1);
}

$public_hostname = $serverCredentials['Host'];

foreach ($extensions as $extension) {
   syslog(LOG_ERR, "extension: $extension");
}
syslog(LOG_ERR, "callerid: $caller_id");
syslog(LOG_ERR, "callerid name: $caller_id_name");
syslog(LOG_ERR, "hostname: $public_hostname");

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

# 126	Command invoked cannot execute: Permission problem or command is not an executable
# 127	Command not found	illegal_command: Possible problem with $PATH or a typo

# sudo -u root php -r 'exec("/sbin/e-smith/config getprop nethvoice PublicHost", $out, $result); var_dump($out);'