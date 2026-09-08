<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalIntegrityClock implements O\AssignmentOrderOriginalClock
{
    public int $calls=0;
    public function __construct(private string|\Throwable $value,private OriginalIntegrityTrace $trace){}
    public function nowUtc():string{++$this->calls;$this->trace->add('clock');if($this->value instanceof \Throwable)throw $this->value;return $this->value;}
}
final class OriginalIntegrityStream implements O\AssignmentOrderOriginalByteStream
{
    public int $readCalls=0;public int $closeCalls=0;private bool $read=false;
    public function __construct(private string $bytes,private OriginalIntegrityTrace $trace,private bool $closeThrows=false){}
    public function read(int $maximumBytes):O\AssignmentOrderOriginalStreamRead
    {++$this->readCalls;$this->trace->add('stream.read');if($this->read)return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::EOF,'');$this->read=true;return new O\AssignmentOrderOriginalStreamRead(O\AssignmentOrderOriginalStreamReadStatus::BYTES,$this->bytes);}
    public function close():void{++$this->closeCalls;$this->trace->add('stream.close');if($this->closeThrows)throw new \RuntimeException('close unread stream');}
}
final readonly class OriginalIntegrityContent implements O\AssignmentOrderOriginalPrivateContent
{
    public function __construct(private string $sha,private int $size){}
    public function opaqueIdentity():string{return 'private-content-0001';}
    public function sha256():string{return $this->sha;}
    public function byteSize():int{return $this->size;}
}
final readonly class OriginalIntegrityOutcome implements O\AssignmentOrderOriginalStorageOutcome
{
    public function __construct(private OriginalIntegrityLease $value){}
    public function status():O\AssignmentOrderOriginalStorageStatus{return O\AssignmentOrderOriginalStorageStatus::OK;}
    public function lease():?O\AssignmentOrderOriginalPrivateContentLease{return $this->value;}
}
final class OriginalIntegrityLease implements O\AssignmentOrderOriginalPrivateContentLease
{
    public int $releaseCalls=0;
    public function __construct(private string $sha,private int $size,private OriginalIntegrityTrace $trace){}
    public function status():O\AssignmentOrderOriginalStorageStatus{return O\AssignmentOrderOriginalStorageStatus::OK;}
    public function content():?O\AssignmentOrderOriginalPrivateContent{return new OriginalIntegrityContent($this->sha,$this->size);}
    public function release():O\AssignmentOrderOriginalStorageStatus{++$this->releaseCalls;$this->trace->add('lease.release');return O\AssignmentOrderOriginalStorageStatus::OK;}
}
final class OriginalIntegrityStage implements O\AssignmentOrderOriginalPrivateStage
{
    private string $bytes='';public int $finalizeCalls=0;public int $abortCalls=0;public int $closeCalls=0;public ?OriginalIntegrityLease $lease=null;
    public function __construct(private OriginalIntegrityTrace $trace){}
    public function write(string $chunk):O\AssignmentOrderOriginalStorageStatus{$this->trace->add('stage.write');$this->bytes.=$chunk;return O\AssignmentOrderOriginalStorageStatus::OK;}
    public function completedBytesForInspection():string{return $this->bytes;}
    public function finalize(string $sha256,int $byteSize):O\AssignmentOrderOriginalStorageOutcome{++$this->finalizeCalls;$this->trace->add('stage.finalize');$this->lease=new OriginalIntegrityLease($sha256,$byteSize,$this->trace);return new OriginalIntegrityOutcome($this->lease);}
    public function abort():O\AssignmentOrderOriginalStorageStatus{++$this->abortCalls;$this->trace->add('stage.abort');return O\AssignmentOrderOriginalStorageStatus::OK;}
    public function close():void{++$this->closeCalls;$this->trace->add('stage.close');}
}
final class OriginalIntegrityStorage implements O\AssignmentOrderOriginalPrivateStorage
{
    public int $beginCalls=0;public ?OriginalIntegrityStage $stage=null;
    public function __construct(private OriginalIntegrityTrace $trace){}
    public function beginStage():O\AssignmentOrderOriginalPrivateStage{++$this->beginCalls;$this->trace->add('stage.begin');return $this->stage=new OriginalIntegrityStage($this->trace);}
    public function listOrphans(string $cutoffUtc,int $limit,?string $cursor):O\AssignmentOrderOriginalOrphanPage{throw new \LogicException('unexpected maintenance');}
    public function acquireDigestLock(string $opaqueIdentity):O\AssignmentOrderOriginalDigestLock{throw new \LogicException('unexpected maintenance');}
    public function deleteLocked(O\AssignmentOrderOriginalDigestLock $lock):O\AssignmentOrderOriginalStorageStatus{throw new \LogicException('unexpected maintenance');}
    public function inventoryCanonicalJson():string{return '{"stages":[],"finalized":[]}';}
}
final class OriginalIntegrityObservers implements O\AssignmentOrderOriginalLifecycleObserver,O\AssignmentOrderOriginalStorageObserver,O\AssignmentOrderOriginalFaultInjector,O\AssignmentOrderOriginalSafeLogObserver,O\AssignmentOrderOriginalResultDeliveryObserver
{
    public array $lifecycle=[];public array $storage=[];public array $logs=[];public int $deliveryCalls=0;
    public function __construct(private OriginalIntegrityTrace $trace,private array $options){}
    public function observe(O\AssignmentOrderOriginalLifecycleEvent|O\AssignmentOrderOriginalStorageEvent $event,?string $opaqueIdentity=null):void
    {if($event instanceof O\AssignmentOrderOriginalLifecycleEvent)$this->lifecycle[]=$event;else $this->storage[]=[$event,$opaqueIdentity];}
    public function before(O\AssignmentOrderOriginalFaultPoint $point):void{}
    public function record(string $event,array $safeFields):void{$this->trace->add('log:'.($safeFields['phase']??''));$this->logs[]=[$event,$safeFields];if($this->options['logThrows']??false)throw new \RuntimeException('synthetic log unavailable');}
    public function afterCommitBeforeReturn(O\AssignmentOrderOriginalResult $result):void{++$this->deliveryCalls;$this->trace->add('delivery');if($this->options['deliveryThrows']??false)throw new \RuntimeException('synthetic response loss');}
}
final class OriginalIntegrityIds implements O\AssignmentOrderOriginalIdSource
{
    public int $rootCalls=0;public int $revisionCalls=0;
    public function __construct(private bool $correction){}
    public function nextRootId():O\AssignmentOrderOriginalIdResult{if(++$this->rootCalls!==1)throw new \RuntimeException('unexpected second root allocation');return new O\AssignmentOrderOriginalIdResult(O\AssignmentOrderOriginalIdStatus::GENERATED,'original-0001');}
    public function nextRevisionId():O\AssignmentOrderOriginalIdResult{if(++$this->revisionCalls!==1)throw new \RuntimeException('unexpected second revision allocation');return new O\AssignmentOrderOriginalIdResult(O\AssignmentOrderOriginalIdStatus::GENERATED,$this->correction?'revision-0002':'revision-0001');}
}
final class OriginalIntegrityInspector implements O\AssignmentOrderOriginalPdfInspector
{
    public int $calls=0;
    public function algorithmId():string{return 'fmonitor-passive-pdf-v1';}
    public function inspect(string $completedBytes):O\AssignmentOrderOriginalPdfInspection{++$this->calls;return O\AssignmentOrderOriginalPdfInspection::passive();}
}
