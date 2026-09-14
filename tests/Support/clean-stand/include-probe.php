<?php
declare(strict_types=1);
function acceptanceProbeFail(string $reason): never { echo json_encode(['ok'=>false,'reason'=>$reason]),"\n"; exit(64); }
$path=getenv('FMONITOR_ACCEPTANCE_CONTEXT_FILE');
if(!is_string($path)||$path===''||!is_file($path)||is_link($path)) acceptanceProbeFail('ACCEPTANCE_CONTEXT_REQUIRED');
$raw=file_get_contents($path); try{$context=json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable){acceptanceProbeFail('ACCEPTANCE_CONTEXT_INVALID');}
$operation=getenv('FMONITOR_ACCEPTANCE_OPERATION_ID');if($operation===false)$operation=$context['operationId']??null;$target=getenv('FMONITOR_ACCEPTANCE_TARGET_DIGEST');if($target===false)$target=$context['targetDigest']??null;$digest=getenv('FMONITOR_ACCEPTANCE_CONTEXT_DIGEST');
if(($context['operationId']??null)!==$operation||($context['targetDigest']??null)!==$target||(is_string($digest)&&$digest!==''&&!hash_equals($digest,hash('sha256',(string)$raw)))) acceptanceProbeFail('ACCEPTANCE_CONTEXT_INVALID');
register_shutdown_function(static function()use($context):void{$root=getenv('FMONITOR_ACCEPTANCE_TRACE_ROOT');if(!is_string($root)||$root==='')return;if(!is_dir($root)&&!mkdir($root,0700,true)&&!is_dir($root))return;$record=['operationId'=>$context['operationId'],'targetDigest'=>$context['targetDigest'],'files'=>get_included_files()];$file=$root.'/'.getmypid().'-'.bin2hex(random_bytes(6)).'.json';file_put_contents($file,json_encode($record,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);});
