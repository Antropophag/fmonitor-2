<?php

declare(strict_types=1);

// Isolated HTTP adapter for INSTALLATION-COMPLETION-SCHEMA-001 Gate 2. It
// invokes the real authenticated public consumers without replacing their DB
// or response behavior.
require dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__,2).'/rapid-pilot/ObjectDetails.php';
require_once dirname(__DIR__,2).'/rapid-pilot/Otiz.php';
require_once dirname(__DIR__,2).'/rapid-pilot/Calendar.php';
require_once dirname(__DIR__,2).'/rapid-pilot/Shell.php';
require_once dirname(__DIR__,2).'/rapid-pilot/ObjectQueue.php';
require_once dirname(__DIR__,2).'/rapid-pilot/CompletionFlow.php';
require_once dirname(__DIR__,2).'/rapid-pilot/InspectionSchedule.php';

$_SERVER['FMONITOR_AUTH_USER_ID']='901';
$_SERVER['FMONITOR_AUTH_CSRF']='completion-schema-csrf';
$_SERVER['REMOTE_USER']='completion-schema@example.invalid';
$path=(string)parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
if(RapidPilotObjectQueue::matches($path))RapidPilotObjectQueue::handle();
if(RapidPilotCompletionFlow::matches($path))RapidPilotCompletionFlow::handle($path);
if(preg_match('#^/pilot/objects/[1-9][0-9]*(?:/checklist)?$#D',$path)===1){
    $entrypoint=require dirname(__DIR__,2).'/app/PilotHttp/production-entrypoint.php';
    $response=$entrypoint->handle($_SERVER);$body=$response->body;
    if($response->status===200&&preg_match('#^/pilot/objects/([1-9][0-9]*)(/checklist)?$#D',$path,$m)===1){$body=isset($m[2])&&$m[2]!==''?RapidPilotCompletionFlow::enhanceChecklist($body,(int)$m[1]):RapidPilotCompletionFlow::enhanceCard($body,(int)$m[1]);}
    http_response_code($response->status);foreach($response->headers as$n=>$v)header($n.': '.$v);echo$body;exit;
}
http_response_code(404);echo"Not found\n";
