<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

// SHLZ-OPERATIONAL-UI-001: real object-card browser seam.
$root=dirname(__DIR__,2);
$commands=[
    [PHP_BINARY,'tests/Yii2/yii2_preopening_browser_001_test.php'],
];
$expected=[
    'tests/Yii2/yii2_preopening_browser_001_test.php'=>'INTENDED_RED object card UI:',
];
$failures=[];$unexpected=[];
foreach($commands as $command){
    $pipes=[];
    $process=proc_open($command,[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,getenv());
    if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: browser contract process');
    $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);
    fclose($pipes[1]);fclose($pipes[2]);$exit=proc_close($process);
    if($exit!==0){$output=$stdout.$stderr;$key=$command[1];$entry=['command'=>implode(' ',$command),'exit'=>$exit,'output'=>$output];if(str_contains($output,$expected[$key]))$failures[]=$entry;else $unexpected[]=$entry;}
}
if($unexpected!==[]){$text="REGRESSION_FAILURE unexpected operational browser failure\n";foreach($unexpected as$failure)$text.=$failure['command'].' exit='.$failure['exit']."\n".$failure['output']."\n";throw new TestFailure($text);}
if($failures!==[]){
    $text="INTENDED_RED SHLZ operational browser contract\n";
    foreach($failures as $failure)$text.=$failure['command'].' exit='.$failure['exit']."\n".$failure['output']."\n";
    throw new TestFailure($text);
}
echo "PASS: SHLZ-OPERATIONAL-UI-001 real object-card browser seam\n";
