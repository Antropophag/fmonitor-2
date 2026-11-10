<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';require __DIR__.'/InstallerDirectoryFixture.php';
$f=new InstallerDirectoryFixture(dirname(__DIR__,2));$db=null;
try{
 $h=$f->http;$p=$h->p;
 for($i=126;$i<=1320;$i++)$h->insert($p.'fm2_workforce_catalog',['installer_tab_id'=>$i,'fio'=>'Тестовый свободный '.str_pad((string)$i,4,'0',STR_PAD_LEFT),'position'=>'Монтажник','employment_status'=>'employed','employed_from'=>'2020-01-01','workforce_source'=>'one_c_zup_via_bitrix','workforce_source_updated_at'=>'2026-09-10T07:40:00+03:00','reconciliation_state'=>'delivered']);
 $count=(int)$h->db->query("SELECT COUNT(*) FROM {$p}fm2_installation_cases")->fetch_column();
 for($i=$count;$i<309;$i++){$id=80000+$i;$h->insert($p.'fm2_installation_cases',['legacy_installation_object_id'=>$id,'process_state'=>'needs_assignment_order','created_at'=>'2026-09-01T09:00:00+03:00','updated_at'=>'2026-09-01T09:00:00+03:00','lock_version'=>0]);$h->insert($p.'fm_maintable',['id'=>$id,'regnumber'=>'PERF-'.$id,'ordadr_address'=>'Синтетический объект '.$i]);}
 $env=$h->environment();$db=new yii\db\Connection(['dsn'=>'mysql:host='.$env['FMONITOR_DB_HOST'].';port='.$env['FMONITOR_DB_PORT'].';dbname='.$h->database,'username'=>$env['FMONITOR_DB_USER'],'password'=>$env['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);$db->open();$read=new FMonitor2\Workforce\MariaDbInstallerUtilization($db,$p,$p);
 $work=static function()use($db):int{$n=0;foreach($db->createCommand("SHOW SESSION STATUS WHERE Variable_name IN('Handler_read_key','Handler_read_next','Handler_read_rnd_next')")->queryAll()as$r)$n+=(int)$r['Value'];return$n;};
 $filters=['q'=>'','tab'=>'','status'=>'','availability'=>'','current'=>'','upcoming'=>'','page'=>1];$before=$h->facts();$start=microtime(true);$workBefore=$work();$v=$read->directory($filters);$reads=$work()-$workBefore;$elapsed=microtime(true)-$start;echo json_encode(['handlerReads'=>$reads,'seconds'=>$elapsed])."\n";
 assertSameValue([1320,1279,41,5],[$v['summary']['total'],$v['summary']['working'],$v['summary']['dismissed'],$v['summary']['assigned']],'unchanged global counts');assertSameValue([1320,27,50],[$v['filters']['total'],$v['filters']['pages'],count($v['rows'])],'server pagination');assertSameValue(range(1,50),array_column($v['rows'],'installer_tab_id'),'stable first page');
 assertSameValue(true,$reads<100000,'INTENDED_RED directory work must not multiply all workers by all cases');
 foreach([['availability'=>'assigned'],['current'=>'present']]as$filter){$r=$read->directory(array_replace($filters,$filter));assertSameValue([1,2,3,4,5],array_column($r['rows'],'installer_tab_id'),'active filters same members');assertSameValue($v['summary'],$r['summary'],'summary remains global');}
 $r=$read->directory(array_replace($filters,['status'=>'dismissed','availability'=>'assigned']));assertSameValue([3],array_column($r['rows'],'installer_tab_id'),'combined filter');$r=$read->directory(array_replace($filters,['upcoming'=>'present']));assertSameValue([],$r['rows'],'empty upcoming list');$r=$read->directory(array_replace($filters,['q'=>'Монтажник 003']));assertSameValue([3],array_column($r['rows'],'installer_tab_id'),'literal search');$r=$read->directory(array_replace($filters,['page'=>2]));assertSameValue(range(51,100),array_column($r['rows'],'installer_tab_id'),'stable second page');
 assertSameValue($before,$h->facts(),'read-only performance correction preserves all facts/schema');
 echo "PASS: bounded directory database work, filters, summary and pagination\n";
}finally{if($db!==null)$db->close();$f->close();}
