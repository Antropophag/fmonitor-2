<?php

declare(strict_types=1);
require __DIR__.'/AssignmentOrderOriginalIntegrityTestBootstrap.php';
require __DIR__.'/AssignmentOrderOriginalIntegrityCommits.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/MariaDbRuntimeRepository.php';
use FMonitor2\AssignmentOrderOriginal as O;
$db=null;
try {
 $c=json_decode(file_get_contents($argv[1]),true,32,JSON_THROW_ON_ERROR);
 if(preg_match('/^t_aoou_integrity_[0-9a-f]{12}$/D',$c['database'])!==1||!in_array($argv[2],['denied','accepted'],true)||!in_array($argv[3],['hold','signal'],true))throw new RuntimeException();
 $db=new mysqli($c['host'],$c['user'],rtrim(file_get_contents($c['passwordFile']),"\n"),$c['database'],$c['port']);$db->set_charset('utf8mb4');$db->query('SET SESSION innodb_lock_wait_timeout=4');
 $observer=new class($argv[3]) implements O\AssignmentOrderOriginalPersistenceObserver {
  public function __construct(private string $barrier){}
  public function observe(O\AssignmentOrderOriginalPersistenceEvent $event):void {
   if(($this->barrier==='hold'&&$event===O\AssignmentOrderOriginalPersistenceEvent::BEFORE_NATIVE_COMMIT)||($this->barrier==='signal'&&$event===O\AssignmentOrderOriginalPersistenceEvent::BEFORE_WRITE_BEGIN)){
    fwrite(STDOUT,"READY\n");fflush(STDOUT);
    if($this->barrier==='hold'){$read=[STDIN];$write=$except=[];if(stream_select($read,$write,$except,5)!==1||fgets(STDIN)!=="RELEASE\n")throw new RuntimeException('Owned barrier unavailable');}
   }
  }
 };
 $id='00000000-0000-4000-8000-000000000701';
 if($argv[2]==='accepted'){$r=(new O\AssignmentOrderOriginalMariaDbRepository($db,$c['prefix'],null,$observer))->commitAccepted(FMonitor2\Tests\Support\OriginalIntegrityCommits::accepted(['requestId'=>$id,'uploadedAt'=>'2026-09-06T09:00:00Z']));}
 else {$writer=new O\AssignmentOrderOriginalMariaDbAttemptAuditWriter($db,$c['prefix'],$observer);$r=$writer->recordDenied(new O\AssignmentOrderOriginalSafeAttemptAudit($id,$argv[3]==='hold'?18:19,O\AssignmentOrderOriginalMode::INITIAL,4512,81,O\AssignmentOrderOriginalStatus::REJECTED,O\AssignmentOrderOriginalReason::AUTHORIZATION_DENIED,$argv[3]==='hold'?'2026-09-06T09:00:00Z':'2026-09-06T09:01:00Z'));}
 fwrite(STDOUT,'RESULT '.$r->value."\n");$db->close();$db=null;
} catch(Throwable) {if($db instanceof mysqli)try{$db->close();}catch(Throwable){}fwrite(STDERR,"AUDIT_RACE_FAILED\n");exit(70);}
