<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/HistoricalPremiumOperandProfiler.php';
function operandEnv(string$name):string{$value=getenv($name);if(!is_string($value)||$value==='')throw new InvalidArgumentException('CONFIGURATION_INVALID');return$value;}
try{$port=filter_var(getenv('FMONITOR_SOURCE_PORT')?:'13306',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);$page=filter_var(getenv('LEGACY_PROFILE_PAGE_SIZE')?:'500',FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>5000]]);if($port===false||$page===false)throw new InvalidArgumentException('CONFIGURATION_INVALID');
    $db=new mysqli(getenv('FMONITOR_SOURCE_HOST')?:'127.0.0.1',operandEnv('FMONITOR_SOURCE_USER'),operandEnv('FMONITOR_SOURCE_PASSWORD'),getenv('FMONITOR_SOURCE_NAME')?:'c1_fmonitor',$port);$db->set_charset('utf8mb4');$result=(new HistoricalPremiumOperandProfiler($db,$page))->profile();echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
}catch(InvalidArgumentException){echo "{\"ok\":false,\"reason\":\"CONFIGURATION_INVALID\"}\n";exit(64);}catch(Throwable){echo "{\"ok\":false,\"reason\":\"LEGACY_PROFILE_UNAVAILABLE\"}\n";exit(69);}
