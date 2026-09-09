<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/autoload.php';
use FMonitor2\IdentityAccess\MariaDbInitialOwnerProvisioning;

$exit=0;$db=null;
try{
    if($argc!==3||$argv[1]!=='--email'||!is_string($argv[2]))throw new InvalidArgumentException();
    $required=static function(string$name):string{$value=getenv($name);if(!is_string($value)||$value==='')throw new InvalidArgumentException();return$value;};
    $host=$required('FMONITOR_DB_HOST');$port=$required('FMONITOR_DB_PORT');$name=$required('FMONITOR_DB_NAME');$user=$required('FMONITOR_DB_USER');$dbPassword=$required('FMONITOR_DB_PASSWORD');$prefix=$required('FMONITOR_PROCESS_TABLE_PREFIX');$ownerPassword=$required('FMONITOR_BOOTSTRAP_SUPERADMIN_PASSWORD');
    if(preg_match('/^[1-9][0-9]{0,4}$/D',$port)!==1||(int)$port>65535||preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix)!==1)throw new InvalidArgumentException();
    mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=mysqli_init();$db->options(MYSQLI_OPT_CONNECT_TIMEOUT,3);@$db->real_connect($host,$user,$dbPassword,$name,(int)$port);$db->set_charset('utf8mb4');
    $result=MariaDbInitialOwnerProvisioning::provision($db,$prefix,$argv[2],$ownerPassword);
    if($result->status==='identity_not_empty'){$exit=65;$out=['ok'=>false,'reason'=>'IDENTITY_NOT_EMPTY'];}else$out=['ok'=>true,'status'=>$result->status];
}catch(InvalidArgumentException){$exit=64;$out=['ok'=>false,'reason'=>'CONFIGURATION_INVALID'];}
catch(Throwable$error){$reason=$error->getMessage();[$exit,$public]=match($reason){'SCHEMA_NOT_READY'=>[70,'SCHEMA_NOT_READY'],'PROVISIONING_BUSY'=>[75,'PROVISIONING_BUSY'],default=>[70,'PROVISIONING_UNAVAILABLE']};$out=['ok'=>false,'reason'=>$public];}
finally{if($db instanceof mysqli)try{$db->close();}catch(Throwable){}}
echo json_encode($out,JSON_THROW_ON_ERROR),"\n";exit($exit);
