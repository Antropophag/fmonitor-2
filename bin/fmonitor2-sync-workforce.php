<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/autoload.php';
use FMonitor2\Workforce\{BitrixWorkforceDeliveryConfig,BitrixWorkforceDeliveryFactory};
use FMonitor2\InstallationProcess\MariaDbWorkforceSynchronization;

// Operator-invoked native sync. Credentials are read from a private token file, never printed.
$db=null;
try {
    $required=static function(string $name):string{$v=getenv($name);if(!is_string($v)||$v==='')throw new RuntimeException('CONFIGURATION_INVALID');return $v;};
    $id=filter_var($required('FMONITOR_BITRIX_WEBHOOK_USER_ID'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    $departments=json_decode($required('FMONITOR_BITRIX_DEPARTMENT_IDS_JSON'),true,8,JSON_THROW_ON_ERROR);
    if($id===false||!is_array($departments))throw new RuntimeException('CONFIGURATION_INVALID');
    $config=new BitrixWorkforceDeliveryConfig($required('FMONITOR_BITRIX_ORIGIN'),$id,$required('FMONITOR_BITRIX_TOKEN_FILE'),$departments,
        caFile:getenv('FMONITOR_BITRIX_CA_FILE')?:null);
    $client=BitrixWorkforceDeliveryFactory::create($config);
    $prefix=getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(!is_string($prefix)||!preg_match('/^[A-Za-z0-9_]{0,25}$/D',$prefix))throw new RuntimeException('CONFIGURATION_INVALID');
    $port=filter_var($required('FMONITOR_DB_PORT'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
    if($port===false)throw new RuntimeException('CONFIGURATION_INVALID');
    $db=new mysqli($required('FMONITOR_DB_HOST'),$required('FMONITOR_DB_USER'),$required('FMONITOR_DB_PASSWORD'),$required('FMONITOR_DB_NAME'),$port);
    $db->set_charset('utf8mb4');
    $bytes=random_bytes(16);$bytes[6]=chr((ord($bytes[6])&15)|64);$bytes[8]=chr((ord($bytes[8])&63)|128);$hex=bin2hex($bytes);
    $run=substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20);
    $result=(new MariaDbWorkforceSynchronization($db,$prefix))->run($client,$run);
    echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),"\n";
    $exit=$result['status']==='completed'?0:1;
}catch(Throwable){echo "{\"status\":\"failed\",\"reason\":\"SYNC_UNAVAILABLE\"}\n";$exit=1;}
finally{if($db instanceof mysqli)try{$db->close();}catch(Throwable){}}
exit($exit);
