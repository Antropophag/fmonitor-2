<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require __DIR__.'/PreopeningFixture.php';

$root=dirname(__DIR__,2);
$required=[
    'app/InstallationProcess/MariaDbYiiCompletionRegister.php',
    'app/YiiRuntime/Controllers/CompletionRegisterController.php',
    'app/YiiRuntime/Views/completion-register.php',
];
foreach($required as$file)assertSameValue(true,is_file($root.'/'.$file),'INTENDED_RED missing '.$file);
$controller=(string)file_get_contents($root.'/'.$required[1]);
$view=(string)file_get_contents($root.'/'.$required[2]);
$reader=(string)file_get_contents($root.'/'.$required[0]);
assertSameValue(true,str_contains($controller,"'GET', 'HEAD'")||str_contains($controller,"'GET','HEAD'"),'GET/HEAD only');
assertSameValue(true,str_contains($controller,"objects.read"),'objects.read admission');
assertSameValue(true,str_contains($view,'#completion'),'existing completion workflow link');
foreach(['ПТО и декларации','Не внесён в FMonitor','Не внесена в FMonitor']as$text)assertSameValue(true,str_contains($view,$text),'exact UI copy '.$text);
foreach(['LIMIT','COUNT','version_no','previous_correction_id','previous_version_no']as$text)assertSameValue(true,str_contains($reader,$text),'bounded/history SQL '.$text);
assertSameValue(false,str_contains($view,'просроч'),'no invented overdue claim');
assertSameValue(false,str_contains($view,'штраф'),'no invented fine claim');

$f=null;
try{
    $f=new PreopeningFixture($root);$f->start();
    $guest=[];
    assertSameValue(303,$f->request('GET','/pilot/completion-register',[],$guest)['status'],'guest redirected');
    $cookies=[];assertSameValue(303,$f->login($cookies,95)['status'],'reader login');$before=$f->facts();
    $page=$f->request('GET','/pilot/completion-register?mode=pto_without_declaration&q=TEST&page=1',[],$cookies);
    assertSameValue(200,$page['status'],'register route');
    foreach(['ПТО и декларации','ПТО внесён, декларация не внесена','/pilot/objects/4512#completion']as$text)assertSameValue(true,str_contains($page['body'],$text),'register content '.$text);
    $head=$f->request('HEAD','/pilot/completion-register?mode=all&page=1',[],$cookies);
    assertSameValue([200,''],[$head['status'],$head['body']],'HEAD route');
    assertSameValue($before,$f->facts(),'GET/HEAD are read only');
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='objects.read'");
    assertSameValue(403,$f->request('GET','/pilot/completion-register',[],$cookies)['status'],'objects.read required');
    $f->noLegacy();
    echo "PASS: YII2-COMPLETION-DOCUMENT-REGISTER-001\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}
