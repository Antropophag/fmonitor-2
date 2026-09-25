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
    $f=new PreopeningFixture($root);
    $f->insert($f->p.'fm2_pilot_completion_facts',[
        'installation_case_id'=>6101,'fact_type'=>'pto_act','fact_date'=>'2026-09-01','details'=>'',
        'recorded_at'=>'2026-09-10 09:00:00','recorded_by_user_id'=>18,
    ]);
    $clone=static function(PreopeningFixture $f,string $table,array $replace,string $where):void{
        $result=$f->db->query('SHOW COLUMNS FROM `'.$table.'`');$columns=[];while($row=$result->fetch_assoc())$columns[]=$row['Field'];
        $select=[];foreach($columns as$column)$select[]=array_key_exists($column,$replace)?($replace[$column]===null?'NULL':("'".$f->db->real_escape_string((string)$replace[$column])."'")):('`'.$column.'`');
        $f->db->query('INSERT INTO `'.$table.'` (`'.implode('`,`',$columns).'`) SELECT '.implode(',',$select).' FROM `'.$table.'` WHERE '.$where);
    };
    for($i=1;$i<=55;$i++){
        $object=5000+$i;$case=7000+$i;
        $clone($f,$f->p.'fm_maintable',['id'=>$object,'regnumber'=>'PAGE-'.str_pad((string)$i,3,'0',STR_PAD_LEFT),'ordadr_address'=>'Тестовая улица '.$i,'entrance'=>(string)$i],'id=4512');
        $clone($f,$f->p.'fm2_installation_cases',['id'=>$case,'legacy_installation_object_id'=>$object],'id=6101');
        $f->insert($f->p.'fm2_pilot_completion_facts',['installation_case_id'=>$case,'fact_type'=>'pto_act','fact_date'=>'2026-08-'.str_pad((string)(($i%28)+1),2,'0',STR_PAD_LEFT),'details'=>'','recorded_at'=>'2026-09-10 09:00:00','recorded_by_user_id'=>18]);
    }
    $clone($f,$f->p.'fm_maintable',['id'=>6001,'regnumber'=>'BAD-DECL','ordadr_address'=>'Повреждённая история','entrance'=>'1'],'id=4512');
    $clone($f,$f->p.'fm2_installation_cases',['id'=>8001,'legacy_installation_object_id'=>6001],'id=6101');
    $f->insert($f->p.'fm2_pilot_completion_facts',['installation_case_id'=>8001,'fact_type'=>'declaration','fact_date'=>'2026-09-01','details'=>'DECL-WITHOUT-PTO','recorded_at'=>'2026-09-10 09:00:00','recorded_by_user_id'=>18]);
    $clone($f,$f->p.'fm_maintable',['id'=>6002,'regnumber'=>'UNOPENED-NO-FACTS','ordadr_address'=>'Ещё не открыт','entrance'=>'2'],'id=4512');
    $clone($f,$f->p.'fm2_installation_cases',['id'=>8002,'legacy_installation_object_id'=>6002,'opened_at'=>null,'actual_start_date'=>null],'id=6101');
    $f->start();
    $guest=[];
    assertSameValue(303,$f->request('GET','/pilot/completion-register',[],$guest)['status'],'guest redirected');
    $cookies=[];assertSameValue(303,$f->login($cookies,95)['status'],'reader login');
    $page=$f->request('GET','/pilot/completion-register?mode=pto_without_declaration&q=TEST&page=1',[],$cookies);
    assertSameValue(200,$page['status'],'register route');
    foreach(['ПТО и декларации','ПТО внесён, декларация не внесена','/pilot/objects/4512#completion']as$text)assertSameValue(true,str_contains($page['body'],$text),'register content '.$text);
    $all=$f->request('GET','/pilot/completion-register?mode=all&page=1',[],$cookies);
    assertSameValue(true,str_contains($all['body'],'57 объектов'),'global total exceeds page');
    assertSameValue(50,substr_count($all['body'],'data-object-id='),'LIMIT applied after global count');
    assertSameValue(true,str_contains($all['body'],'page=2'),'second page URL retained');
    $second=$f->request('GET','/pilot/completion-register?mode=all&page=2',[],$cookies);
    assertSameValue(7,substr_count($second['body'],'data-object-id='),'second page remainder');
    assertSameValue(false,str_contains($all['body'],'UNOPENED-NO-FACTS')||str_contains($second['body'],'UNOPENED-NO-FACTS'),'unopened case without facts excluded');
    $inconsistent=$f->request('GET','/pilot/completion-register?mode=without_pto&q=BAD-DECL&page=1',[],$cookies);
    assertSameValue(true,str_contains($inconsistent['body'],'Ошибка сведений: декларация зарегистрирована без ПТО'),'invalid fact combination is explicit');
    $empty=$f->request('GET','/pilot/completion-register?mode=all&q=NO-SUCH-VALUE&page=1',[],$cookies);
    assertSameValue(true,str_contains($empty['body'],'Это не означает отсутствие физического документа'),'honest empty state');
    $period=$f->request('GET','/pilot/completion-register?mode=all&date=pto&from=2026-09-01&to=2026-09-01&page=1',[],$cookies);
    assertSameValue(1,substr_count($period['body'],'data-object-id='),'PTO period uses document date');
    $f->insert($f->p.'fm2_pilot_completion_facts',['installation_case_id'=>6101,'fact_type'=>'declaration','fact_date'=>'2026-09-04','details'=>'DECL-ORIGINAL','recorded_at'=>'2026-09-10 10:00:00','recorded_by_user_id'=>97]);
    $declarationId=(int)$f->db->insert_id;
    $f->insert($f->p.'fm2_pilot_completion_fact_corrections',['root_fact_id'=>$declarationId,'version_no'=>1,'previous_correction_id'=>null,'previous_version_no'=>null,'fact_date'=>'2026-09-03','details'=>'DECL-CURRENT','reason'=>'Уточнение','recorded_at'=>'2026-09-10 11:00:00','recorded_by_user_id'=>18]);
    $firstCorrection=(int)$f->db->insert_id;
    $f->insert($f->p.'fm2_pilot_completion_fact_corrections',['root_fact_id'=>$declarationId,'version_no'=>2,'previous_correction_id'=>$firstCorrection,'previous_version_no'=>1,'fact_date'=>'2026-09-02','details'=>null,'reason'=>'Исправлена только дата','recorded_at'=>'2026-09-10 12:00:00','recorded_by_user_id'=>18]);
    $complete=$f->request('GET','/pilot/completion-register?mode=complete&q=DECL-CURRENT&date=declaration&from=2026-09-02&to=2026-09-02&page=1',[],$cookies);
    foreach(['DECL-CURRENT','02.09.2026','Документарные сведения внесены']as$text)assertSameValue(true,str_contains($complete['body'],$text),'effective correction '.$text);
    assertSameValue(false,str_contains($complete['body'],'DECL-ORIGINAL'),'search/render use current inherited details');
    $queueAfter=$f->request('GET','/pilot/completion-register?mode=pto_without_declaration&q=TEST&page=1',[],$cookies);
    assertSameValue(false,str_contains($queueAfter['body'],'/pilot/objects/4512#completion'),'new declaration leaves initial queue');
    $before=$f->facts();$head=$f->request('HEAD','/pilot/completion-register?mode=all&page=1',[],$cookies);
    assertSameValue([200,''],[$head['status'],$head['body']],'HEAD route');
    assertSameValue($before,$f->facts(),'GET/HEAD are read only');
    $f->db->query("DELETE FROM {$f->p}fm2_pilot_role_permissions WHERE role_id=5 AND permission='objects.read'");
    assertSameValue(403,$f->request('GET','/pilot/completion-register',[],$cookies)['status'],'objects.read required');
    $f->db->query("INSERT INTO {$f->p}fm2_pilot_role_permissions(role_id,permission)VALUES(5,'objects.read')");
    $f->db->query("DROP TABLE {$f->p}fm2_pilot_completion_fact_corrections");
    assertSameValue(503,$f->request('GET','/pilot/completion-register',[],$cookies)['status'],'source schema failure is unavailable, not empty');
    $f->noLegacy();
    echo "PASS: YII2-COMPLETION-DOCUMENT-REGISTER-001\n";
}finally{if($f instanceof PreopeningFixture)$f->close();}
