<?php
declare(strict_types=1);

$target=getenv('FMONITOR_TEST_CSS_SWAP_PATH');$ready=getenv('FMONITOR_TEST_CSS_SWAP_READY');
if(!is_string($target)||$target===''||!is_string($ready)||$ready==='')exit(2);
$nonTarget=dirname($target).'/non-target.css';$missing=dirname($target).'/missing.css';
$results=[];clearstatcache(true,$nonTarget);$results['nonTargetStat']=lstat($nonTarget)!==false;$results['nonTargetReady']=file_exists($ready);
clearstatcache(true,$missing);$results['missingStat']=@lstat($missing)!==false;$results['missingReady']=file_exists($ready);
clearstatcache(true,$target);$results['targetStat']=lstat($target)!==false;$results['targetReady']=file_exists($ready);
echo json_encode($results,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
