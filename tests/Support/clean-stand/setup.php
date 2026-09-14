<?php
declare(strict_types=1);
function fail(string $reason): never { echo json_encode(['ok'=>false,'reason'=>$reason]),"\n"; exit(64); }
function context(): array {
    foreach(['FMONITOR_ACCEPTANCE_CONTEXT_FILE','FMONITOR_ACCEPTANCE_CONTEXT_DIGEST','FMONITOR_ACCEPTANCE_OPERATION_ID','FMONITOR_ACCEPTANCE_TARGET_DIGEST'] as $key) if(!is_string(getenv($key))||getenv($key)==='') fail('ACCEPTANCE_CONTEXT_REQUIRED');
    $path=(string)getenv('FMONITOR_ACCEPTANCE_CONTEXT_FILE'); if(!is_file($path)||is_link($path)) fail('ACCEPTANCE_CONTEXT_INVALID');
    $raw=file_get_contents($path); if(!is_string($raw)||!hash_equals((string)getenv('FMONITOR_ACCEPTANCE_CONTEXT_DIGEST'),hash('sha256',$raw))) fail('ACCEPTANCE_CONTEXT_INVALID');
    try{$value=json_decode($raw,true,512,JSON_THROW_ON_ERROR);}catch(Throwable){fail('ACCEPTANCE_CONTEXT_INVALID');}
    if(($value['operationId']??null)!==getenv('FMONITOR_ACCEPTANCE_OPERATION_ID')||($value['targetDigest']??null)!==getenv('FMONITOR_ACCEPTANCE_TARGET_DIGEST')) fail('ACCEPTANCE_CONTEXT_INVALID'); return $value;
}
$context=context();
// The exact setup statements are supplied as canonical, digest-bound SQL in the
// private context. This acceptance-only adapter cannot be reached by production.
$sql=$context['setupSql']??null; if(!is_array($sql)||$sql===[]) fail('ACCEPTANCE_SETUP_INVALID');
$passwordFile=getenv('FMONITOR_DB_PASSWORD_FILE'); if(!is_string($passwordFile)||!is_file($passwordFile)||is_link($passwordFile)||!in_array(fileperms($passwordFile)&0777,[0400,0600],true)) fail('ACCEPTANCE_CREDENTIAL_INVALID');
mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
try{$db=new mysqli((string)getenv('FMONITOR_DB_HOST'),(string)getenv('FMONITOR_DB_USER'),trim((string)file_get_contents($passwordFile)),(string)getenv('FMONITOR_DB_NAME'),(int)getenv('FMONITOR_DB_PORT'));$db->set_charset('utf8mb4');foreach($sql as $statement){if(!is_string($statement)||$statement==='')fail('ACCEPTANCE_SETUP_INVALID');$db->query($statement);}echo json_encode(['ok'=>true,'operationId'=>$context['operationId']]),"\n";}catch(Throwable){fail('ACCEPTANCE_SETUP_FAILED');}
