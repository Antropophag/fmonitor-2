<?php

declare(strict_types=1);

require_once __DIR__.'/legacy-migration/LegacyChecklistTemplateSnapshot.php';
function templateEnv(string$name):string{$value=getenv($name);if(!is_string($value)||$value==='')throw new RuntimeException("Missing {$name}");return$value;}
$options=getopt('',['captured-at:','apply']);$capturedAt=(string)($options['captured-at']??'');if($capturedAt==='')throw new InvalidArgumentException('Usage: --captured-at="Y-m-d H:i:s" [--apply]');
$source=new mysqli(getenv('FMONITOR_SOURCE_HOST')?:'127.0.0.1',templateEnv('FMONITOR_SOURCE_USER'),templateEnv('FMONITOR_SOURCE_PASSWORD'),getenv('FMONITOR_SOURCE_NAME')?:'c1_fmonitor',(int)(getenv('FMONITOR_SOURCE_PORT')?:13306));$source->set_charset('utf8mb4');
$snapshot=(new LegacyChecklistTemplateMySqlSource($source))->extract($capturedAt);$result=['mode'=>isset($options['apply'])?'apply':'dry-run','capturedAt'=>$capturedAt,'contentSha256'=>$snapshot['contentSha256'],'counts'=>$snapshot['counts'],'validity'=>'active_baseline_and_future_native_only'];
if(isset($options['apply'])){$manifest=json_decode((string)file_get_contents(templateEnv('FMONITOR_PILOT_ACTIVE_MANIFEST')),true,flags:JSON_THROW_ON_ERROR);$target=new mysqli(templateEnv('FMONITOR_DB_HOST'),templateEnv('FMONITOR_DB_USER'),templateEnv('FMONITOR_DB_PASSWORD'),templateEnv('FMONITOR_DB_NAME'),(int)templateEnv('FMONITOR_DB_PORT'));$target->set_charset('utf8mb4');$result+=(new LegacyChecklistTemplateMySqlTarget($target,(string)($manifest['processPrefix']??'')))->apply($snapshot,$capturedAt,gmdate('Y-m-d H:i:s'));}
echo json_encode($result,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),"\n";
