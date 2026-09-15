<?php
declare(strict_types=1);

[$script,$configPath,$resultPath,$barrierPath,$commandPath,$bytesPath]=array_pad($argv,6,'');
try{
    $config=json_decode((string)file_get_contents($configPath),true,flags:JSON_THROW_ON_ERROR);
    $operation=json_decode((string)file_get_contents($commandPath),true,flags:JSON_THROW_ON_ERROR);
    $body=$bytesPath===''?json_encode($operation,JSON_THROW_ON_ERROR):file_get_contents($bytesPath);if(!is_string($body))throw new RuntimeException('request body');
    $deadline=microtime(true)+10;while(!is_file($barrierPath)&&microtime(true)<$deadline)usleep(1000);if(!is_file($barrierPath))throw new RuntimeException('barrier timeout');
    $headers=['Connection: close','Cookie: '.$config['cookie'],'X-FM2-CSRF: '.$config['csrf'],'Origin: http://127.0.0.1:'.$config['port'],'Sec-Fetch-Site: same-origin'];
    if($bytesPath==='')$headers[]='Content-Type: application/json; charset=UTF-8';else{$headers[]='Content-Type: '.$operation['mime'];$headers[]='X-FM2-Operation: '.base64_encode(json_encode($operation,JSON_THROW_ON_ERROR));}
    $context=stream_context_create(['http'=>['method'=>'POST','header'=>implode("\r\n",$headers),'content'=>$body,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>20]]);
    $responseBody=file_get_contents('http://127.0.0.1:'.$config['port'].'/pilot/objects/4512/checklist/'.($bytesPath===''?'operations':'photos'),false,$context);$response=$http_response_header??[];preg_match('#^HTTP/\S+ (\d+)#',$response[0]??'',$match);
    $decoded=json_decode((string)$responseBody,true,flags:JSON_THROW_ON_ERROR);
    file_put_contents($resultPath,json_encode(['ok'=>true,'status'=>(int)($match[1]??0),'body'=>['status'=>$decoded['status']??null,'revision'=>$decoded['revision']??null]],JSON_THROW_ON_ERROR));
}catch(Throwable $error){file_put_contents($resultPath,json_encode(['ok'=>false,'error'=>get_class($error).': '.$error->getMessage()],JSON_THROW_ON_ERROR));exit(1);}
