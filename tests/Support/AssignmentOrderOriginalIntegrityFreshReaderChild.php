<?php

declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';
use FMonitor2\AssignmentOrderOriginal as O;

$reader=null;$exitCode=0;
try{
    if(!class_exists(O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory::class)){
        fwrite(STDERR,"INTENDED_RED: fresh factory declaration absent\n");exit(42);
    }
    if($argc!==3)throw new RuntimeException('child protocol');
    $raw=file_get_contents($argv[1]);if(!is_string($raw)||strlen($raw)>65536)throw new RuntimeException('child config');
    $data=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
    $keys=['databaseHost','databasePort','databaseName','databaseUser','databasePasswordFile','tablePrefix'];
    if(array_keys($data)!==$keys)throw new RuntimeException('child config keys');
    $factory=new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory(new O\AssignmentOrderOriginalFreshReaderConfig(...$data));
    $opened=$factory->open();
    if($opened->status!==O\AssignmentOrderOriginalFreshReaderOpenStatus::OPENED){fwrite(STDOUT,"{\"opened\":false,\"status\":\"unavailable\",\"close\":null}\n");exit(0);}
    $reader=$opened->reader;$lookup=$reader->findTerminalRequest($argv[2]);$status=$lookup->status()->value;$value=$lookup->result();
    $result=$value===null?null:['status'=>$value->status()->value,'requestId'=>$value->requestId(),'revisionId'=>$value->currentRevisionId(),'revisionNumber'=>$value->revisionNumber()];
    $closing=$reader;$reader=null;$closed=$closing->close()->value;
    fwrite(STDOUT,json_encode(['opened'=>true,'status'=>$status,'close'=>$closed,'result'=>$result],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n");
}catch(Throwable){fwrite(STDERR,"INTEGRITY_FRESH_CHILD_FAILED\n");$exitCode=70;}
finally{if($reader!==null)try{$reader->close();}catch(Throwable){}}
exit($exitCode);
