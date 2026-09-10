<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/DocumentaryFixture.php';
// YII2-DOCUMENTARY-CLOSURE-001 A5: separate servers/sessions, both DB requests wait on a held case lock.
$f=null;$second=null;$child=null;
try {
    $f=new DocumentaryFixture(dirname(__DIR__,2));$f->open();$f->progress();$h=$f->http;$first=$h->server;
    $h->server=null;$h->start();$second=$h->server;$other=[];$h->login($other,97);$token=$h->token($other);
    assertSameValue(false,$first['port']===$second['port'],'distinct servers');assertSameValue(false,$f->cookies===$other,'distinct real sessions');
    $race=function(array $a,array $b)use(&$f,&$h,&$first,&$second,&$other,&$token,&$child):array{
        $requests=[];foreach([[$first,$f->cookies,$f->csrf,$a],[$second,$other,$token,$b]] as [$server,$jar,$csrf,$fields])$requests[]=['port'=>$server['port'],'path'=>'/pilot/objects/4512/completion','cookies'=>$jar,'headers'=>['Content-Type: application/x-www-form-urlencoded'],'body'=>http_build_query(['_csrf'=>$csrf]+$fields)];
        $config=$h->artifacts.'/documentary-race.json';$result=$h->artifacts.'/documentary-race-result.json';$script=$h->artifacts.'/documentary-race.php';$log=$h->artifacts.'/documentary-race.log';
        file_put_contents($config,json_encode($requests,JSON_THROW_ON_ERROR));chmod($config,0600);
        file_put_contents($script,'<?php require '.var_export(dirname(__DIR__).'/bootstrap.php',true).';require '.var_export(__DIR__.'/PreopeningConcurrentRequests.php',true).';file_put_contents($argv[2],json_encode(preopeningConcurrent(json_decode(file_get_contents($argv[1]),true,flags:JSON_THROW_ON_ERROR)),JSON_THROW_ON_ERROR));');
        $h->db->begin_transaction();$h->db->query("SELECT id FROM {$h->p}fm2_installation_cases WHERE id=6101 FOR UPDATE");
        try {
            $child=proc_open([PHP_BINARY,$script,$config,$result],[0=>['file','/dev/null','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$h->root);
            if(!is_resource($child))throw new TestFailure('SETUP_FAILURE concurrent child');
            $deadline=microtime(true)+8;$waiting=0;
            do{$query=$h->db->prepare("SELECT COUNT(*) n FROM information_schema.INNODB_TRX t JOIN information_schema.PROCESSLIST p ON p.ID=t.trx_mysql_thread_id WHERE p.DB=? AND t.trx_state='LOCK WAIT'");$query->execute([$h->database]);$waiting=(int)$query->get_result()->fetch_assoc()['n'];$state=proc_get_status($child);if($waiting>=2||!$state['running'])break;usleep(100000);}while(microtime(true)<$deadline);
        }finally{$h->db->rollback();}
        $deadline=microtime(true)+35;while($state['running']&&microtime(true)<$deadline){usleep(20000);$state=proc_get_status($child);}if($state['running'])throw new TestFailure('concurrency timeout');$exit=$state['exitcode'];proc_close($child);$child=null;unlink($config);
        assertSameValue(0,$exit,'concurrency worker '.file_get_contents($log));$responses=json_decode(file_get_contents($result),true,flags:JSON_THROW_ON_ERROR);
        if(array_column($responses,'status')===[404,404])echo "INTENDED_RED concurrent completion routes absent\n";
        assertSameValue(true,$waiting>=2,'INTENDED_RED both completion commands reached held public case lock; responses='.json_encode($responses));return $responses;
    };
    $before=$h->facts();$r=$race(['action'=>'record_pto','ptoActDate'=>'2026-09-05'],['action'=>'record_pto','ptoActDate'=>'2026-09-04']);$statuses=array_column($r,'status');sort($statuses);assertSameValue([303,409],$statuses,'one root winner');$roots=$h->rows('fm2_pilot_completion_facts');assertSameValue(1,count($roots),'one root');$winner=$r[0]['status']===303?['18','2026-09-05']:['97','2026-09-04'];assertSameValue($winner,[$roots[0]['recorded_by_user_id'],$roots[0]['fact_date']],'matched winner payload');$f->unchangedExcept($before,['fm2_pilot_completion_facts']);
    $root=$roots[0];$before=$h->facts();$r=$race(['action'=>'correct_pto','factId'=>$root['id'],'ptoActDate'=>'2026-09-03','reason'=>'Поправка А'],['action'=>'correct_pto','factId'=>$root['id'],'ptoActDate'=>'2026-09-02','reason'=>'Поправка Б']);assertSameValue([303,303],array_column($r,'status'),'both corrections accepted');$corr=$h->rows('fm2_pilot_completion_fact_corrections');assertSameValue(2,count($corr),'both corrections retained');assertSameValue(['1','2'],array_column($corr,'version_no'),'serial versions');assertSameValue([$corr[0]['id'],'1'],[$corr[1]['previous_correction_id'],$corr[1]['previous_version_no']],'linear lineage');$reasons=array_column($corr,'reason');sort($reasons);assertSameValue(['Поправка А','Поправка Б'],$reasons,'both reasons retained');assertSameValue($roots,$h->rows('fm2_pilot_completion_facts'),'root immutable');$f->unchangedExcept($before,['fm2_pilot_completion_fact_corrections']);
    // Declaration roots have the same one-winner contract.
    $r=$race(['action'=>'record_declaration','declarationDate'=>'2026-09-06','declarationDetails'=>'Д-А'],['action'=>'record_declaration','declarationDate'=>'2026-09-06','declarationDetails'=>'Д-Б']);$statuses=array_column($r,'status');sort($statuses);assertSameValue([303,409],$statuses,'one declaration winner');assertSameValue(2,count($h->rows('fm2_pilot_completion_facts')),'two documentary roots total');$h->noLegacy();
    // Fresh canonical case: cross-type competitors must both acquire the same case lock.
    proc_terminate($second['process']);proc_close($second['process']);$second=null;$h->server=$first;$f->close();$f=null;
    $f=new DocumentaryFixture(dirname(__DIR__,2));$f->open();$f->progress();$h=$f->http;$first=$h->server;$h->server=null;$h->start();$second=$h->server;$other=[];$h->login($other,97);$token=$h->token($other);
    $before=$h->facts();$cross=$race(['action'=>'record_pto','ptoActDate'=>'2026-09-05'],['action'=>'record_declaration','declarationDate'=>'2026-09-06','declarationDetails'=>str_repeat('Ж',500)]);assertSameValue(303,$cross[0]['status'],'cross PTO accepted');assertSameValue(true,in_array($cross[1]['status'],[303,409],true),'declaration actual serialization outcome');
    $rows=$h->rows('fm2_pilot_completion_facts');assertSameValue($cross[1]['status']===303?2:1,count($rows),'exact cross-race facts');$byType=array_column($rows,null,'fact_type');assertSameValue(['18','2026-09-05'],[$byType['pto_act']['recorded_by_user_id'],$byType['pto_act']['fact_date']],'PTO winner data');$f->unchangedExcept($before,['fm2_pilot_completion_facts']);
    if($cross[1]['status']===409){assertSameValue("Сначала зафиксируйте дату акта ПТО.\n",$cross[1]['body'],'declaration before PTO rejected');DocumentaryFixture::accepted($h->form('/pilot/objects/4512/completion',['_csrf'=>$token,'action'=>'record_declaration','declarationDate'=>'2026-09-06','declarationDetails'=>str_repeat('Ж',500)],$other));}
    $byType=array_column($h->rows('fm2_pilot_completion_facts'),null,'fact_type');assertSameValue(['97',str_repeat('Ж',500)],[$byType['declaration']['recorded_by_user_id'],$byType['declaration']['details']],'declaration only after accepted PTO');$h->noLegacy();echo "PASS: YII2-DOCUMENTARY-CLOSURE-001 held-lock HTTP concurrency\n";
}finally{if(is_resource($child)){proc_terminate($child,9);proc_close($child);}if($second!==null){proc_terminate($second['process']);proc_close($second['process']);if($f!==null)$f->http->server=$first;}if($f instanceof DocumentaryFixture)$f->close();}
