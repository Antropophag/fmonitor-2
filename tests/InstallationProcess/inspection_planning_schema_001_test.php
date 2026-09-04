<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

// INSPECTION-PLANNING-SCHEMA-001 sections 2-6, Gate 2 task 2.1.
// Public seams: InspectionPlanningSchemaMigration::apply() and
// bin/fmonitor2-migrate.php. Expectations below are test-owned literals.

function ipsQ(string $v): string { return '`'.str_replace('`','``',$v).'`'; }
function ipsDb(string $name): mysqli {
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
    $db=new mysqli(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root',getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',$name,(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306));
    $db->set_charset('utf8mb4'); return $db;
}
/** @return array<string,mixed> */
function ipsApply(mysqli $db,string $prefix):array {
    $class='FMonitor2\\InstallationProcess\\InspectionPlanningSchemaMigration';
    if(!class_exists($class))throw new TestFailure('RED: approved public InspectionPlanningSchemaMigration v9 seam is missing.');
    return $class::apply($db,$prefix);
}
/** @return array{exitCode:int,stdout:string,stderr:string} */
function ipsRunner(string $name,string $prefix,bool $unreachable=false):array {
    $env=['FMONITOR_DB_HOST'=>$unreachable?'127.0.0.1':(getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1'),'FMONITOR_DB_PORT'=>$unreachable?'1':(getenv('FMONITOR_TEST_DB_PORT')?:'23306'),'FMONITOR_DB_NAME'=>$unreachable?'not_accessed':$name,'FMONITOR_DB_USER'=>$unreachable?'not_accessed':(getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root'),'FMONITOR_DB_PASSWORD'=>$unreachable?'secret':(getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local'),'FMONITOR_PROCESS_TABLE_PREFIX'=>$prefix];
    $cmd=array_merge(['env'],array_map(fn($k,$v)=>"$k=$v",array_keys($env),$env),[PHP_BINARY,dirname(__DIR__,2).'/bin/fmonitor2-migrate.php']);
    $p=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2)); if(!is_resource($p))throw new TestFailure('SETUP_FAILURE: runner start');fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return['exitCode'=>proc_close($p),'stdout'=>$out,'stderr'=>$err];
}
function ipsCreate(mysqli $db,string $p,string $member):void {
    $tail=' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    if($member==='schedules')$db->query('CREATE TABLE '.ipsQ($p.'fm2_pilot_inspection_schedules').'(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,installation_case_id BIGINT UNSIGNED NOT NULL,legacy_object_id BIGINT UNSIGNED NOT NULL,control_engineer_user_id BIGINT UNSIGNED NOT NULL,inspection_date DATE NOT NULL,scheduled_by_user_id BIGINT UNSIGNED NOT NULL,scheduled_at VARCHAR(40) NOT NULL,PRIMARY KEY(id),UNIQUE KEY unique_planned_inspection(installation_case_id,control_engineer_user_id,inspection_date),KEY calendar_date(inspection_date,id),KEY engineer_day(control_engineer_user_id,inspection_date,id))'.$tail);
    else $db->query('CREATE TABLE '.ipsQ($p.'fm2_pilot_inspection_schedule_events').'(id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,schedule_id BIGINT UNSIGNED NOT NULL,installation_case_id BIGINT UNSIGNED NOT NULL,event_type VARCHAR(80) NOT NULL,payload_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,actor_user_id BIGINT UNSIGNED NOT NULL,occurred_at VARCHAR(40) NOT NULL,PRIMARY KEY(id),KEY schedule_id(schedule_id,id),KEY installation_case_id(installation_case_id,id),CHECK(json_valid(payload_json)))'.$tail);
}
/** @return array<string,mixed> */
function ipsState(mysqli$db,string$p):array{$out=[];foreach(['fm2_pilot_inspection_schedules','fm2_pilot_inspection_schedule_events']as$b){$n=$p.$b;$e=$db->real_escape_string($n);$t=$db->query("SELECT ENGINE,TABLE_COLLATION,AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$e'")->fetch_assoc();if(!$t){$out[$n]=null;continue;}$out[$n]=['table'=>$t,'columns'=>$db->query("SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,IS_GENERATED,GENERATION_EXPRESSION,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$e' ORDER BY ORDINAL_POSITION")->fetch_all(MYSQLI_ASSOC),'indexes'=>$db->query("SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME,SUB_PART,COLLATION,INDEX_TYPE,IGNORED FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$e' ORDER BY BINARY INDEX_NAME,SEQ_IN_INDEX")->fetch_all(MYSQLI_ASSOC),'checks'=>$db->query("SELECT tc.CONSTRAINT_NAME,cc.CHECK_CLAUSE FROM information_schema.TABLE_CONSTRAINTS tc JOIN information_schema.CHECK_CONSTRAINTS cc ON cc.CONSTRAINT_SCHEMA=tc.CONSTRAINT_SCHEMA AND cc.TABLE_NAME=tc.TABLE_NAME AND cc.CONSTRAINT_NAME=tc.CONSTRAINT_NAME WHERE tc.CONSTRAINT_SCHEMA=DATABASE() AND tc.TABLE_NAME='$e' AND tc.CONSTRAINT_TYPE='CHECK' ORDER BY BINARY tc.CONSTRAINT_NAME")->fetch_all(MYSQLI_ASSOC),'rows'=>$db->query('SELECT * FROM '.ipsQ($n).' ORDER BY 1')->fetch_all(MYSQLI_ASSOC)];}return$out;}
/** Remove only balanced parentheses enclosing the complete expression. */
function ipsNormalizeCheck(string $clause):string {
    $value=strtolower(str_replace([" ","\n","\r","\t","\f","\v",'`'],'',$clause));
    while(strlen($value)>=2&&$value[0]==='('&&$value[strlen($value)-1]===')'){
        $depth=0;$encloses=true;
        for($i=0,$length=strlen($value);$i<$length;$i++){
            if($value[$i]==='(')$depth++;elseif($value[$i]===')')$depth--;
            if($depth===0&&$i<$length-1){$encloses=false;break;}
            if($depth<0){$encloses=false;break;}
        }
        if(!$encloses||$depth!==0)break;$value=substr($value,1,-1);
    }
    return$value;
}
/** @param list<array<string,mixed>> $rows @return list<string> */
function ipsColumnManifest(array$rows):array{return array_map(static fn(array$c):string=>implode('|',[
    $c['COLUMN_NAME'],$c['COLUMN_TYPE'],$c['IS_NULLABLE'],$c['COLUMN_DEFAULT']===null?'NULL':(string)$c['COLUMN_DEFAULT'],$c['EXTRA'],$c['IS_GENERATED'],$c['GENERATION_EXPRESSION']===null?'NULL':(string)$c['GENERATION_EXPRESSION'],$c['CHARACTER_SET_NAME']??'NULL',$c['COLLATION_NAME']??'NULL'
]),$rows);}
/** @param list<array<string,mixed>> $rows @return list<string> */
function ipsIndexManifest(array$rows):array{return array_map(static fn(array$i):string=>implode('|',[
    $i['INDEX_NAME'],$i['NON_UNIQUE'],$i['SEQ_IN_INDEX'],$i['COLUMN_NAME'],$i['SUB_PART']===null?'NULL':(string)$i['SUB_PART'],$i['COLLATION'],$i['INDEX_TYPE'],$i['IGNORED']
]),$rows);}
function ipsFinal(mysqli$db,string$p,string$collation='utf8mb4_unicode_ci'):void{
    $s=ipsState($db,$p);
    $expected=[
      'fm2_pilot_inspection_schedules'=>[
       'columns'=>[
        'id|bigint(20) unsigned|NO|NULL|auto_increment|NEVER|NULL|NULL|NULL',
        'installation_case_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        'legacy_object_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        'control_engineer_user_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        'inspection_date|date|NO|NULL||NEVER|NULL|NULL|NULL',
        'scheduled_by_user_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        "scheduled_at|varchar(40)|NO|NULL||NEVER|NULL|utf8mb4|$collation",
       ],
       'indexes'=>[
        'PRIMARY|0|1|id|NULL|A|BTREE|NO',
        'calendar_date|1|1|inspection_date|NULL|A|BTREE|NO','calendar_date|1|2|id|NULL|A|BTREE|NO',
        'engineer_day|1|1|control_engineer_user_id|NULL|A|BTREE|NO','engineer_day|1|2|inspection_date|NULL|A|BTREE|NO','engineer_day|1|3|id|NULL|A|BTREE|NO',
        'unique_planned_inspection|0|1|installation_case_id|NULL|A|BTREE|NO','unique_planned_inspection|0|2|control_engineer_user_id|NULL|A|BTREE|NO','unique_planned_inspection|0|3|inspection_date|NULL|A|BTREE|NO',
       ],
      ],
      'fm2_pilot_inspection_schedule_events'=>[
       'columns'=>[
        'id|bigint(20) unsigned|NO|NULL|auto_increment|NEVER|NULL|NULL|NULL',
        'schedule_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        'installation_case_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        "event_type|varchar(80)|NO|NULL||NEVER|NULL|utf8mb4|$collation",
        'payload_json|longtext|NO|NULL||NEVER|NULL|utf8mb4|utf8mb4_bin',
        'actor_user_id|bigint(20) unsigned|NO|NULL||NEVER|NULL|NULL|NULL',
        "occurred_at|varchar(40)|NO|NULL||NEVER|NULL|utf8mb4|$collation",
       ],
       'indexes'=>[
        'PRIMARY|0|1|id|NULL|A|BTREE|NO',
        'installation_case_id|1|1|installation_case_id|NULL|A|BTREE|NO','installation_case_id|1|2|id|NULL|A|BTREE|NO',
        'schedule_id|1|1|schedule_id|NULL|A|BTREE|NO','schedule_id|1|2|id|NULL|A|BTREE|NO',
       ],
      ],
    ];
    foreach($expected as$base=>$manifest){$name=$p.$base;$actual=$s[$name];assertSameValue('InnoDB',$actual['table']['ENGINE'],"$name engine");assertSameValue($collation,$actual['table']['TABLE_COLLATION'],"$name exact database-default collation");assertSameValue($manifest['columns'],ipsColumnManifest($actual['columns']),"$name exact ordered columns/default/generated/charset/collation");assertSameValue($manifest['indexes'],ipsIndexManifest($actual['indexes']),"$name exact ordered index manifest");}
    assertSameValue([],$s[$p.'fm2_pilot_inspection_schedules']['checks'],'schedules has no CHECK');
    $escaped=$db->real_escape_string($p.'fm2_pilot_inspection_schedules');$foreign=$db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$escaped' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetch_all(MYSQLI_ASSOC);assertSameValue([],$foreign,'schedules has no FK');
    $escaped=$db->real_escape_string($p.'fm2_pilot_inspection_schedule_events');$foreign=$db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$escaped' AND CONSTRAINT_TYPE='FOREIGN KEY'")->fetch_all(MYSQLI_ASSOC);assertSameValue([],$foreign,'events has no FK');
    $c=$s[$p.'fm2_pilot_inspection_schedule_events']['checks'];assertSameValue(1,count($c),'events has exactly one JSON CHECK');assertSameValue('json_valid(payload_json)',ipsNormalizeCheck((string)$c[0]['CHECK_CLAUSE']),'normative JSON CHECK preserves function parentheses');
}

function ipsAssertRunnerSuccess(string$name,array$versions,string$label):void{$run=ipsRunner($name,'');assertSameValue(0,$run['exitCode'],"$label runner exit");assertSameValue('',$run['stderr'],"$label runner stderr");assertSameValue(['ok'=>true,'schemaVersion'=>12,'appliedVersions'=>$versions],json_decode(trim($run['stdout']),true,512,JSON_THROW_ON_ERROR),"$label exact runner result");}
function ipsAssertEmptyFamily(mysqli$db,string$p,string$label):void{$state=ipsState($db,$p);assertSameValue([],$state[$p.'fm2_pilot_inspection_schedules']['rows'],"$label schedules has no seed/backfill rows");assertSameValue([],$state[$p.'fm2_pilot_inspection_schedule_events']['rows'],"$label events has no seed/backfill rows");}

$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$pass=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';$name='t_ips_'.bin2hex(random_bytes(4));
try{$admin=new mysqli($host,$user,$pass,'',$port);$admin->query('CREATE DATABASE '.ipsQ($name).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');$db=ipsDb($name);
    assertSameValue([36,28,29],[strlen('fm2_pilot_inspection_schedule_events'),64-strlen('fm2_pilot_inspection_schedule_events'),65-strlen('fm2_pilot_inspection_schedule_events')],'family-local identifier inventory is evidence only');
    // Clean, repeat and both populated partial states are exercised only through
    // the approved deployment seam, never merely through the migration class.
    $clean='t_ips_clean_'.bin2hex(random_bytes(4));$admin->query('CREATE DATABASE '.ipsQ($clean).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');ipsAssertRunnerSuccess($clean,[1,2,3,4,5,6,7,8,9,10,11,12],'clean v1-v12');$cleanDb=ipsDb($clean);ipsFinal($cleanDb,'');ipsAssertEmptyFamily($cleanDb,'','clean public runner before sentinel inserts');$cleanDb->query("INSERT INTO fm2_pilot_inspection_schedules VALUES(2,7,8,9,'2026-09-09',10,'2026-09-01T10:00:00+03:00')");$cleanDb->query("INSERT INTO fm2_pilot_inspection_schedule_events VALUES(2,2,7,'inspection_scheduled','{\"тест\":\"байты\"}',10,'2026-09-01T10:00:00+03:00')");$cleanDb->query('ALTER TABLE fm2_pilot_inspection_schedules AUTO_INCREMENT=3');$cleanDb->query('ALTER TABLE fm2_pilot_inspection_schedule_events AUTO_INCREMENT=3');$before=ipsState($cleanDb,'');ipsAssertRunnerSuccess($clean,[],'populated repeat');assertSameValue($before,ipsState($cleanDb,''),'public runner repeat preserves exact schema, Unicode JSON bytes, rows and allocators');$cleanDb->close();$admin->query('DROP DATABASE '.ipsQ($clean));
    foreach([['schedules','events'],['events','schedules']]as[$present,$missing]){$partial='t_ips_'.$present.'_'.bin2hex(random_bytes(4));$admin->query('CREATE DATABASE '.ipsQ($partial).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');$partialDb=ipsDb($partial);ipsCreate($partialDb,'',$present);$presentTable=$present==='schedules'?'fm2_pilot_inspection_schedules':'fm2_pilot_inspection_schedule_events';$missingTable=$missing==='schedules'?'fm2_pilot_inspection_schedules':'fm2_pilot_inspection_schedule_events';if($present==='schedules'){$partialDb->query("INSERT INTO fm2_pilot_inspection_schedules VALUES(11,71,81,91,'2026-09-09',101,'2026-09-01T10:00:00+03:00')");$partialDb->query('ALTER TABLE fm2_pilot_inspection_schedules AUTO_INCREMENT=77');}else{$partialDb->query("INSERT INTO fm2_pilot_inspection_schedule_events VALUES(12,11,71,'inspection_scheduled','{\"частичный\":\"Юникод\"}',101,'2026-09-01T10:00:00+03:00')");$partialDb->query('ALTER TABLE fm2_pilot_inspection_schedule_events AUTO_INCREMENT=88');}$before=ipsState($partialDb,'');ipsAssertRunnerSuccess($partial,[1,2,3,4,5,6,7,8,9,10,11,12],"populated $present-only partial");$after=ipsState($partialDb,'');assertSameValue($before[$presentTable],$after[$presentTable],"$present partial rows/Unicode bytes/allocator preserved");assertSameValue([],$after[$missingTable]['rows'],"$missing sibling created empty");ipsFinal($partialDb,'');$partialDb->close();$admin->query('DROP DATABASE '.ipsQ($partial));}
    $directClean='direct_clean_';assertSameValue(['applied'=>true,'schemaVersion'=>9,'tablesCreated'=>[$directClean.'fm2_pilot_inspection_schedule_events',$directClean.'fm2_pilot_inspection_schedules']],ipsApply($db,$directClean),'direct clean exact result has only binary-ascending created family');ipsFinal($db,$directClean);ipsAssertEmptyFamily($db,$directClean,'direct clean before any sentinel inserts');
    foreach([['direct_schedules_','schedules','events'],['direct_events_','events','schedules']]as[$p,$present,$missing]){ipsCreate($db,$p,$present);$presentTable=$p.($present==='schedules'?'fm2_pilot_inspection_schedules':'fm2_pilot_inspection_schedule_events');$missingTable=$p.($missing==='schedules'?'fm2_pilot_inspection_schedules':'fm2_pilot_inspection_schedule_events');if($present==='schedules'){$db->query('INSERT INTO '.ipsQ($presentTable)." VALUES(21,171,181,191,'2026-09-09',201,'2026-09-01T10:00:00+03:00')");$db->query('ALTER TABLE '.ipsQ($presentTable).' AUTO_INCREMENT=177');}else{$db->query('INSERT INTO '.ipsQ($presentTable)." VALUES(22,21,171,'inspection_scheduled','{\"прямой\":\"Юникод\"}',201,'2026-09-01T10:00:00+03:00')");$db->query('ALTER TABLE '.ipsQ($presentTable).' AUTO_INCREMENT=188');}$before=ipsState($db,$p);assertSameValue(['applied'=>true,'schemaVersion'=>9,'tablesCreated'=>[$missingTable]],ipsApply($db,$p),"direct populated $present-only partial exact result has only missing member");$after=ipsState($db,$p);assertSameValue($before[$presentTable],$after[$presentTable],"direct populated $present-only partial preserves present rows/bytes/allocator");assertSameValue([],$after[$missingTable]['rows'],"direct populated $present-only partial creates missing member empty");ipsFinal($db,$p);}
    $mutations=[
      'column'=>'ALTER TABLE %s MODIFY scheduled_at VARCHAR(39) NOT NULL',
      'default'=>"ALTER TABLE %s MODIFY scheduled_at VARCHAR(40) NOT NULL DEFAULT 'runtime'",
      'generated'=>'ALTER TABLE %s DROP scheduled_at, ADD scheduled_at VARCHAR(40) AS (CAST(id AS CHAR)) VIRTUAL',
      'column_collation'=>'ALTER TABLE %s MODIFY scheduled_at VARCHAR(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL',
      'index'=>'ALTER TABLE %s ADD KEY unexpected(legacy_object_id)',
      'unique'=>'ALTER TABLE %s ADD UNIQUE KEY unexpected_unique(legacy_object_id)',
      'direction'=>'ALTER TABLE %s DROP KEY calendar_date, ADD KEY calendar_date(inspection_date DESC,id)',
      'subpart'=>'ALTER TABLE %s ADD KEY unexpected_subpart(scheduled_at(4))',
      'type'=>'ALTER TABLE %s ADD FULLTEXT KEY unexpected_fulltext(scheduled_at)',
      'visibility'=>'ALTER TABLE %s ALTER INDEX calendar_date IGNORED',
      'foreign'=>'ALTER TABLE %s ADD CONSTRAINT unexpected_fk FOREIGN KEY(installation_case_id) REFERENCES %s(id)',
      'collation'=>'ALTER TABLE %s DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
    ];foreach($mutations as$k=>$sql){$p='x'.$k.'_';ipsCreate($db,$p,'schedules');$n=$p.'fm2_pilot_inspection_schedules';$db->query($k==='foreign'?sprintf($sql,ipsQ($n),ipsQ($n)):sprintf($sql,ipsQ($n)));$before=ipsState($db,$p);assertSameValue(['applied'=>false,'schemaVersion'=>9,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$n]],ipsApply($db,$p),"$k conflict");$runnerConflict=ipsRunner($name,$p);assertSameValue([2,"{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":9}\n",''],[$runnerConflict['exitCode'],$runnerConflict['stdout'],$runnerConflict['stderr']],"$k public runner conflict");assertSameValue($before,ipsState($db,$p),"$k conflict zero planning-family mutation");}
    foreach(['absent'=>'ALTER TABLE %s DROP CONSTRAINT %s','extra'=>'ALTER TABLE %s ADD CHECK(payload_json IS NOT NULL)','changed'=>'ALTER TABLE %s DROP CONSTRAINT %s, ADD CHECK(json_valid(payload_json) AND length(payload_json)>0)','duplicate'=>'ALTER TABLE %s ADD CHECK(json_valid(payload_json))']as$kind=>$sql){$p='j'.$kind.'_';ipsCreate($db,$p,'events');$n=$p.'fm2_pilot_inspection_schedule_events';$check=$db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='$n' AND CONSTRAINT_TYPE='CHECK'")->fetch_assoc()['CONSTRAINT_NAME'];if(str_contains($sql,'%s, ADD'))$db->query(sprintf($sql,ipsQ($n),ipsQ($check)));elseif(substr_count($sql,'%s')===2)$db->query(sprintf($sql,ipsQ($n),ipsQ($check)));else$db->query(sprintf($sql,ipsQ($n)));$before=ipsState($db,$p);assertSameValue(['applied'=>false,'schemaVersion'=>9,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>[$n]],ipsApply($db,$p),"JSON CHECK $kind");assertSameValue($before,ipsState($db,$p),"JSON CHECK $kind zero mutation");}
    ipsCreate($db,'multi_','schedules');ipsCreate($db,'multi_','events');$db->query('ALTER TABLE multi_fm2_pilot_inspection_schedules ADD z_conflict INT');$db->query('ALTER TABLE multi_fm2_pilot_inspection_schedule_events ADD a_conflict INT');$multiBefore=ipsState($db,'multi_');assertSameValue(['applied'=>false,'schemaVersion'=>9,'reason'=>'SCHEMA_MIGRATION_CONFLICT','conflictingTables'=>['multi_fm2_pilot_inspection_schedule_events','multi_fm2_pilot_inspection_schedules']],ipsApply($db,'multi_'),'all conflicts binary ascending');assertSameValue($multiBefore,ipsState($db,'multi_'),'hostile multi-conflict zero mutation');
    ipsCreate($db,'one_','schedules');ipsCreate($db,'one_','events');$db->query("INSERT INTO one_fm2_pilot_inspection_schedules VALUES(3,1,2,3,'2026-09-09',4,'kept')");$db->query("INSERT INTO one_fm2_pilot_inspection_schedule_events VALUES(4,3,1,'kept','{\"configured\":true}',4,'kept')");ipsCreate($db,'two_','schedules');ipsCreate($db,'two_','events');$db->query('ALTER TABLE two_fm2_pilot_inspection_schedules ADD decoy INT');$db->query("INSERT INTO two_fm2_pilot_inspection_schedules(id,installation_case_id,legacy_object_id,control_engineer_user_id,inspection_date,scheduled_by_user_id,scheduled_at,decoy) VALUES(5,1,2,3,'2026-09-09',4,'decoy',9)");ipsCreate($db,'','schedules');ipsCreate($db,'','events');$db->query('ALTER TABLE fm2_pilot_inspection_schedule_events ADD unprefixed_drift INT');$db->query("INSERT INTO fm2_pilot_inspection_schedule_events(id,schedule_id,installation_case_id,event_type,payload_json,actor_user_id,occurred_at,unprefixed_drift) VALUES(6,5,1,'decoy','{\"не\":\"трогать\"}',4,'decoy',9)");$configured=ipsState($db,'one_');$decoy=ipsState($db,'two_');$unprefixed=ipsState($db,'');assertSameValue(['applied'=>false,'schemaVersion'=>9,'tablesCreated'=>[]],ipsApply($db,'one_'),'configured family ignores populated decoys');assertSameValue($configured,ipsState($db,'one_'),'configured populated family preserved');assertSameValue($decoy,ipsState($db,'two_'),'other-prefix populated decoy byte-equivalent');assertSameValue($unprefixed,ipsState($db,''),'unprefixed populated decoy byte-equivalent');
    assertSameValue(true,ipsApply($db,str_repeat('p',25))['applied'],'25-byte prefix accepted');$db->close();foreach([str_repeat('p',26),'bad-prefix','префикс']as$bad)assertSameValue(['exitCode'=>64,'stdout'=>"{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n",'stderr'=>''],ipsRunner($name,$bad,true),'invalid/26/non-ASCII prefix rejected before DB access without disclosure');
    $runName='t_ips_runner_'.bin2hex(random_bytes(4));$admin->query('CREATE DATABASE '.ipsQ($runName).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');$run=ipsRunner($runName,'r_');assertSameValue(0,$run['exitCode'],'runner clean exit');assertSameValue(['ok'=>true,'schemaVersion'=>12,'appliedVersions'=>[1,2,3,4,5,6,7,8,9,10,11,12]],json_decode(trim($run['stdout']),true,512,JSON_THROW_ON_ERROR),'runner exact v12 output');$runDb=ipsDb($runName);$runDb->query('ALTER TABLE r_fm2_pilot_inspection_schedules ADD conflict INT');$conflictRun=ipsRunner($runName,'r_');assertSameValue(2,$conflictRun['exitCode'],'runner stops on v9 conflict');assertSameValue("{\"ok\":false,\"reason\":\"SCHEMA_MIGRATION_CONFLICT\",\"schemaVersion\":9}\n",$conflictRun['stdout'],'runner exact safe conflict output');assertSameValue('',$conflictRun['stderr'],'runner conflict stderr');$runDb->close();$admin->query('DROP DATABASE '.ipsQ($runName));
    $uca='t_ips_uca_'.bin2hex(random_bytes(4));$latin='t_ips_latin_'.bin2hex(random_bytes(4));$admin->query('CREATE DATABASE '.ipsQ($uca).' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci');$admin->query('CREATE DATABASE '.ipsQ($latin).' DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci');try{$ucaDb=ipsDb($uca);assertSameValue(true,ipsApply($ucaDb,'u_')['applied'],'validated MariaDB UCA alias applies');ipsFinal($ucaDb,'u_','utf8mb4_uca1400_ai_ci');$ucaDb->close();$latinDb=ipsDb($latin);$before=ipsState($latinDb,'bad_');try{ipsApply($latinDb,'bad_');throw new TestFailure('non-utf8mb4 database default must fail setup preflight');}catch(FMonitor2\InstallationProcess\DatabaseUnavailable){}assertSameValue($before,ipsState($latinDb,'bad_'),'invalid database default causes zero target mutation');$latinDb->close();}finally{$admin->query('DROP DATABASE IF EXISTS '.ipsQ($uca));$admin->query('DROP DATABASE IF EXISTS '.ipsQ($latin));}
    echo "PASS: INSPECTION-PLANNING-SCHEMA-001 migration matrix\n";
}catch(mysqli_sql_exception$e){throw new TestFailure('SETUP_FAILURE: MariaDB fixture: '.$e->getMessage(),0,$e);}finally{if(isset($admin))$admin->query('DROP DATABASE IF EXISTS '.ipsQ($name));}
