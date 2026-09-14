<?php
declare(strict_types=1);
// YII2-LOCAL-QUICKSTART-001: real MariaDB create/exact replay/mismatch seam.
require dirname(__DIR__).'/bootstrap.php';

$root=dirname(__DIR__,2);
$host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';
$port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
$adminPassword=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
$suffix=bin2hex(random_bytes(5));$database='fm2_quick_'.$suffix;$runtimeUser='fm2_quick_'.$suffix;$runtimePassword='Runtime-'.$suffix.'!';
$admin=new mysqli($host,'root',$adminPassword,'',$port);$admin->set_charset('utf8mb4');
$run=static function()use($root,$host,$port,$database,$runtimeUser,$runtimePassword):array{
    $env=array_replace(getenv(),[
        'FMONITOR_DB_HOST'=>$host,'FMONITOR_DB_PORT'=>(string)$port,'FMONITOR_DB_NAME'=>$database,
        'FMONITOR_DB_USER'=>$runtimeUser,'FMONITOR_DB_PASSWORD'=>$runtimePassword,
        'FMONITOR_MIGRATION_DB_USER'=>'root','FMONITOR_MIGRATION_DB_PASSWORD'=>getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local',
    ]);
    $pipes=[];$process=proc_open([PHP_BINARY,$root.'/bin/yii','local-runtime/provision-database','--interactive=0'],[1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$env);
    $stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    return [proc_close($process),$stdout,$stderr];
};
try{
    $admin->query("CREATE DATABASE `{$database}` DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    [$code,$out,$err]=$run();assertSameValue(0,$code,'LOCAL_RUNTIME_COMMAND_ABSENT');assertTrueValue(str_contains($out,'RUNTIME_DB_ACCOUNT_READY'),'safe create result');assertSameValue('',$err,'create stderr');
    assertTrueValue(!str_contains($out.$err,$runtimePassword),'create output is secret-free');
    $grants=[];$result=$admin->query("SHOW GRANTS FOR `{$runtimeUser}`@'%'");while($row=$result->fetch_row())$grants[]=$row[0];sort($grants,SORT_STRING);
    $escapedUser=$admin->real_escape_string($runtimeUser);$grantee="'{$escapedUser}'@'%'";
    $probe="SELECT (SELECT COUNT(*)<>1 OR COALESCE(SUM(PRIVILEGE_TYPE='USAGE'),0)<>1 FROM information_schema.USER_PRIVILEGES WHERE GRANTEE=\"{$grantee}\")+(SELECT COUNT(*)<>4 OR COALESCE(SUM(TABLE_SCHEMA='{$database}' AND PRIVILEGE_TYPE IN ('SELECT','INSERT','UPDATE','DELETE')),0)<>4 OR COUNT(DISTINCT PRIVILEGE_TYPE)<>4 FROM information_schema.SCHEMA_PRIVILEGES WHERE GRANTEE=\"{$grantee}\")+(SELECT COUNT(*)<>0 FROM information_schema.TABLE_PRIVILEGES WHERE GRANTEE=\"{$grantee}\")+(SELECT COUNT(*)<>0 FROM information_schema.COLUMN_PRIVILEGES WHERE GRANTEE=\"{$grantee}\")";
    assertSameValue(0,(int)$admin->query($probe)->fetch_column(),'exact DML-only account');
    $before=$grants;[$code,$out,$err]=$run();assertSameValue(0,$code,'exact replay');assertTrueValue(str_contains($out,'RUNTIME_DB_ACCOUNT_READY'),'safe replay result');assertTrueValue(!str_contains($out.$err,$runtimePassword),'replay output is secret-free');
    $replayed=[];$result=$admin->query("SHOW GRANTS FOR `{$runtimeUser}`@'%'");while($row=$result->fetch_row())$replayed[]=$row[0];sort($replayed,SORT_STRING);assertSameValue($before,$replayed,'replay preserves exact account grants');
    $admin->query("GRANT CREATE ON `{$database}`.* TO `{$runtimeUser}`@'%'");$mismatchBefore=[];$result=$admin->query("SHOW GRANTS FOR `{$runtimeUser}`@'%'");while($row=$result->fetch_row())$mismatchBefore[]=$row[0];sort($mismatchBefore,SORT_STRING);
    [$code,$out,$err]=$run();assertSameValue(65,$code,'mismatch rejected');assertTrueValue(str_contains($out.$err,'LOCAL_DB_ACCOUNT_MISMATCH'),'stable mismatch');
    assertTrueValue(!str_contains($out.$err,$runtimePassword),'mismatch output is secret-free');$after=[];$result=$admin->query("SHOW GRANTS FOR `{$runtimeUser}`@'%'");while($row=$result->fetch_row())$after[]=$row[0];sort($after,SORT_STRING);
    assertSameValue($mismatchBefore,$after,'mismatch leaves full account snapshot unchanged');
}finally{
    try{$admin->query("DROP USER IF EXISTS `{$runtimeUser}`@'%'");}catch(Throwable){}
    try{$admin->query("DROP DATABASE IF EXISTS `{$database}`");}catch(Throwable){}
    $admin->close();
}
echo "PASS: YII2-LOCAL-QUICKSTART-001 database provisioning\n";
