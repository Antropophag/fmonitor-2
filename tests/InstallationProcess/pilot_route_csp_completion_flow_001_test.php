<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// Specification: PILOT-ROUTE-CSP-001, A10 and A12.4.
// Public browser seam: final checklist HTML plus its external same-origin asset.
$root=dirname(__DIR__,2);
$completion=(string)file_get_contents($root.'/rapid-pilot/CompletionFlow.php');
$assetPath=$root.'/app/PilotHttp/checklist.js';$asset=(string)file_get_contents($assetPath);
assertSameValue(false,$completion===''||$asset==='','fixture sources are readable');

$failures=[];$inlineBlocks=preg_match_all('#<script(?:\s[^>]*)?>(.*?)</script>#si',$completion,$matches);
if($inlineBlocks!==0)$failures[]="A10 final checklist inline script blocks: expected 0, actual $inlineBlocks";
if(preg_match('/\son[a-z]+\s*=/i',$completion)===1)$failures[]='A10 CompletionFlow contains inline event attribute';
$browser=$root.'/tests/InstallationProcess/support/pilot_route_csp_completion_browser.js';
$command=['node',$browser,$assetPath];$pipes=[];$process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);if(!is_resource($process))throw new TestFailure('setup: start completion browser verifier');$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);if($exit!==0)$failures[]="A10 executable external 85/100 cap behavior failed (exit $exit): ".trim($stderr);elseif($stdout!=="completion external cap behavior: PASS\n")$failures[]='A10 executable cap output changed';
if($failures!==[])throw new TestFailure("PILOT-ROUTE-CSP-001 CompletionFlow intended RED:\n- ".implode("\n- ",$failures));

echo "pilot_route_csp_completion_flow_001_test: PASS\n";
