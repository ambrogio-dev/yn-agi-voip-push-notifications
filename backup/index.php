<?php

# Extension format sample:
# sip:xxx@xx.xx.xx.xx;fs-conn-id=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=tls:host.publicname.com#012
# Endpoint example:
# https://beta.youneed.it/mobile/api/v1/phone/incoming_call_notification?calleeAor=45@214-neth-01.youneed.tech&callerAor=56

// syslog(LOG_ERR, "----------");
// $request_method = $_SERVER["REQUEST_METHOD"];
// $query_string = $_SERVER['QUERY_STRING'];
// syslog(LOG_ERR, "Request method: $request_method");
// syslog(LOG_ERR, "Query string: $query_string");

syslog(LOG_ERR, "----------");
foreach($_REQUEST as $key => $value) 
{
   syslog(LOG_ERR, "$key: $value");
}
syslog(LOG_ERR, "----------");


#$extension = 'sip:abc@xx.xx.xx.xx;ip:fs-conn-id=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=tls:host.publicname.com#012';
$extension = $_REQUEST['to'];
$callid = $_REQUEST['callid'];
$from = $_REQUEST['from'];
$caller = $_REQUEST['caller'];
$calleeAor = substr($extension, 5, strpos($extension, ';')-5); // calleeAor

$debug = false;

if (isset($_REQUEST['loglevel']) && ($_REQUEST['loglevel'] == "debug") ) {
  $debug = true;
}

#TODO: use the SIP proxy loglevel argument to print in sys log with:
# if ($debug) {
#
# }

syslog(LOG_INFO, "extension: $extension");
syslog(LOG_INFO, "from: $from");
syslog(LOG_INFO, "caller: $caller");
syslog(LOG_INFO, "callee: $calleeAor");

$endpoint = 'https://beta.youneed.it/mobile/api/v1/phone/incoming_call_notification';
$params = array('calleeAor' => $calleeAor, 'callerAor' => 'TODO');
$url = $endpoint . '?' . http_build_query($params);

syslog(LOG_DEBUG, $url);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);

// CURLOPT_VERBOSE: TRUE to output verbose information. Writes output to STDERR, 
// or the file specified using CURLOPT_STDERR.
#curl_setopt($ch, CURLOPT_VERBOSE, true); // enable cURL

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

syslog(LOG_DEBUG, "HTTP code: $httpCode");

if ($httpdCode >= 400) {
  syslog(LOG_WARNING, "push-notification-script: request failed.");
  http_response_code(500);
  exit(1);
} else {
  syslog(LOG_INFO, "push-notification-script: request sent.");
  http_response_code(200);
  exit(0);
}