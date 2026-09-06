<?php
declare(strict_types=1);
// PILOT-HEALTHCHECK-SESSION-001: real LocalAuth/native session storage, no database.
$modeFile=getenv('HEALTH_FIXTURE_MODE');
$mode=is_file($modeFile)?trim((string)file_get_contents($modeFile)):'';
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if($mode==='slow'){sleep(4);http_response_code(503);exit;}
if($mode==='non200'){http_response_code(204);exit;}
if($mode==='external'){header('Location: '.getenv('HEALTH_FIXTURE_TRAP').'/out',true,302);exit;}
if($mode===$path){http_response_code(503);echo 'fixture unavailable';exit;}
if(in_array($mode,['chain3','chain4'],true)&&((int)($_GET['hop']??0))<(int)substr($mode,-1)){header('Location: /pilot/login?hop='.((int)($_GET['hop']??0)+1),true,302);exit;}
if($mode==='loop'){header('Location: /pilot/objects',true,302);exit;}
require dirname(__DIR__,2).'/rapid-pilot/LocalAuth.php';
(new RapidPilotLocalAuth())->handle($path);
http_response_code(500);echo 'unexpected authenticated request';
