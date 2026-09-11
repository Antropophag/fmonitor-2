<?php
declare(strict_types=1);
require dirname(__DIR__) . '/bootstrap.php';
/** YII2-CANONICAL-MIGRATIONS-001 A5: alias and Yii route have identical durable results. */
$root=dirname(__DIR__,2);$token=bin2hex(random_bytes(5));$names=['alias'=>'t_ycm_alias_'.$token,'yii'=>'t_ycm_direct_'.$token];$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);$user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';$admin=new mysqli($host,$user,$password,'',$port);$admin->set_charset('utf8mb4');
function ycmAliasRun(array$command,array$environment,string$root):array{$pipes=[];$process=proc_open([PHP_BINARY,...$command],[0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE process');$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return[proc_close($process),$out,$err];}
function ycmAliasState(mysqli$db):array{$out=[];foreach($db->query('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() ORDER BY BINARY TABLE_NAME')->fetch_all(MYSQLI_ASSOC)as$row){$table=$row['TABLE_NAME'];$quoted='`'.str_replace('`','``',$table).'`';$out[$table]=['create'=>$db->query("SHOW CREATE TABLE {$quoted}")->fetch_assoc()['Create Table'],'rows'=>$db->query("SELECT * FROM {$quoted} ORDER BY 1")->fetch_all(MYSQLI_ASSOC)];}return$out;}
try {
    foreach($names as$name)$admin->query("CREATE DATABASE `{$name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $commands=['alias'=>[$root.'/bin/fmonitor2-migrate.php'],'yii'=>[$root.'/bin/yii','schema-migrate/run','--interactive=0']];$results=[];$states=[];
    foreach($names as$kind=>$name){$env=['PATH'=>(string)getenv('PATH'),'FMONITOR_DB_HOST'=>$host,'FMONITOR_DB_PORT'=>(string)$port,'FMONITOR_DB_NAME'=>$name,'FMONITOR_DB_USER'=>$user,'FMONITOR_DB_PASSWORD'=>$password,'FMONITOR_PROCESS_TABLE_PREFIX'=>'equiv_'];$results[$kind]=ycmAliasRun($commands[$kind],$env,$root);$db=new mysqli($host,$user,$password,$name,$port);$db->set_charset('utf8mb4');$states[$kind]=ycmAliasState($db);$db->close();}
    assertSameValue($results['alias'],$results['yii'],'INTENDED_RED: legacy alias and direct Yii route have identical fresh output and exit.');assertSameValue($states['alias'],$states['yii'],'Alias and Yii route produce exact equal schema, ledger and rows.');
    $invalid=['PATH'=>(string)getenv('PATH'),'FMONITOR_DB_PORT'=>'23306','FMONITOR_DB_NAME'=>'private_name','FMONITOR_DB_USER'=>'private_user','FMONITOR_DB_PASSWORD'=>'PRIVATE_ALIAS_PASSWORD','FMONITOR_PROCESS_TABLE_PREFIX'=>'private_'];
    assertSameValue(ycmAliasRun($commands['alias'],$invalid,$root),ycmAliasRun($commands['yii'],$invalid,$root),'Alias and Yii route have identical configuration-failure output and exit.');
    $unavailable=$invalid;$unavailable['FMONITOR_DB_HOST']='127.0.0.1';$unavailable['FMONITOR_DB_PORT']='1';
    assertSameValue(ycmAliasRun($commands['alias'],$unavailable,$root),ycmAliasRun($commands['yii'],$unavailable,$root),'Alias and Yii route have identical database-failure output and exit.');
    foreach(['PRIVATE_ALIAS_PASSWORD','private_name','private_user','private_']as$secret)assertSameValue(false,str_contains(implode('',ycmAliasRun($commands['yii'],$unavailable,$root)),$secret),'Failure equivalence remains redacted.');
    echo"PASS: YII2-CANONICAL-MIGRATIONS-001 alias equivalence\n";
} finally { foreach($names as$name)try{$admin->query("DROP DATABASE IF EXISTS `{$name}`");}catch(Throwable){}$admin->close(); }
