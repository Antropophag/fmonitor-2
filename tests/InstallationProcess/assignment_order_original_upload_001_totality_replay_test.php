<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizer;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalAuthorizationStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalByteStream;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalDependencies;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalReason;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamRead;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalStreamReadStatus;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalVerificationFactory;
use FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand;
use FMonitor2\Tests\Support\InMemoryAssignmentOrderOriginalInitialEnvironment;

require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalApplication.php';require dirname(__DIR__).'/Support/InMemoryAssignmentOrderOriginalInitialEnvironment.php';
$pdf=(string)base64_decode('JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCA3MiA3Ml0gPj4KZW5kb2JqCnhyZWYKMCA0CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAwOSAwMDAwMCBuIAowMDAwMDAwMDU4IDAwMDAwIG4gCjAwMDAwMDAxMTUgMDAwMDAgbiAKdHJhaWxlcgo8PCAvU2l6ZSA0IC9Sb290IDEgMCBSID4+CnN0YXJ0eHJlZgoxODQKJSVFT0YK',true);
$stream=static function(int&$reads)use($pdf):AssignmentOrderOriginalByteStream{return new class($pdf,$reads)implements AssignmentOrderOriginalByteStream{private int$o=0;public function __construct(private string$b,private int&$r){}public function read(int$m):AssignmentOrderOriginalStreamRead{$this->r++;if($this->o===strlen($this->b))return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::EOF,'');$x=substr($this->b,$this->o,$m);$this->o+=strlen($x);return new AssignmentOrderOriginalStreamRead(AssignmentOrderOriginalStreamReadStatus::BYTES,$x);}public function close():void{}};};
$command=static fn(string$id,AssignmentOrderOriginalMode$mode,AssignmentOrderOriginalByteStream$s,string$date='2026-09-01',?string$root=null,?string$target=null,?string$expected=null,?string$reason=null)=>new SubmitAssignmentOrderOriginalCommand($id,$mode,4512,81,18,$date,true,$root,$target,$expected,$reason,new AssignmentOrderOriginalUpload($s,'signed.pdf','application/pdf'));
$rejected=static fn($r)=>[$r->status(),$r->reasonCode(),$r->retryable(),$r->rootOriginalId(),$r->currentRevisionId()];

foreach([
    ['NOT-A-UUID',AssignmentOrderOriginalMode::INITIAL,'2026-09-01',null,null,null,null],
    ['00000000-0000-4000-8000-000000000201',AssignmentOrderOriginalMode::INITIAL,'2026-02-30',null,null,null,null],
    ['00000000-0000-4000-8000-000000000202',AssignmentOrderOriginalMode::INITIAL,'2026-09-01','unexpected-root',null,null,null],
    ['00000000-0000-4000-8000-000000000203',AssignmentOrderOriginalMode::CORRECTION,'2026-09-01',null,null,null,'reason'],
    ['00000000-0000-4000-8000-000000000204',AssignmentOrderOriginalMode::CORRECTION,'2026-09-01','root','revision','revision','   '],
]as[$id,$mode,$date,$root,$target,$expected,$reason]){$env=new InMemoryAssignmentOrderOriginalInitialEnvironment();$reads=0;$result=AssignmentOrderOriginalVerificationFactory::create($env->dependencies())->submitAssignmentOrderOriginal($command($id,$mode,$stream($reads),$date,$root,$target,$expected,$reason));assertSameValue([AssignmentOrderOriginalStatus::REJECTED,AssignmentOrderOriginalReason::INVALID_COMMAND,false,null,null],$rejected($result),'Malformed shape/date/lineage/reason is typed INVALID_COMMAND.');assertSameValue([0,0,0],[$reads,$env->requestLookupCalls,count($env->storageEvents)],'Malformed command fails before authorization-adjacent repository and stream/storage work.');}

$env=new InMemoryAssignmentOrderOriginalInitialEnvironment();$reads=0;$initial=AssignmentOrderOriginalVerificationFactory::create($env->dependencies())->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000205',AssignmentOrderOriginalMode::INITIAL,$stream($reads)));$env->allowedCapability='assignment_order.original.correct';$reads=0;$fresh=AssignmentOrderOriginalVerificationFactory::create($env->dependencies());$noChange=$fresh->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000206',AssignmentOrderOriginalMode::CORRECTION,$stream($reads),'2026-09-01',$initial->rootOriginalId(),$initial->currentRevisionId(),$initial->currentRevisionId(),'new reason'));assertSameValue([AssignmentOrderOriginalStatus::REJECTED,AssignmentOrderOriginalReason::NO_CHANGES],[$noChange->status(),$noChange->reasonCode()],'NO_CHANGES derives from committed lineage after fresh application construction.');

$race=new InMemoryAssignmentOrderOriginalInitialEnvironment();$app=AssignmentOrderOriginalVerificationFactory::create($race->dependencies());$reads=0;$one=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000207',AssignmentOrderOriginalMode::INITIAL,$stream($reads)));$race->allowedCapability='assignment_order.original.correct';$reads=0;$two=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000208',AssignmentOrderOriginalMode::CORRECTION,$stream($reads),'2026-09-02',$one->rootOriginalId(),$one->currentRevisionId(),$one->currentRevisionId(),'date'));$reads=0;$replay=$app->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000209',AssignmentOrderOriginalMode::CORRECTION,$stream($reads),'2026-09-02',$one->rootOriginalId(),$one->currentRevisionId(),$one->currentRevisionId(),'another reason'));assertSameValue([AssignmentOrderOriginalStatus::REPLAYED,$two->currentRevisionId()],[$replay->status(),$replay->currentRevisionId()],'Accepted fingerprint replay wins before stale/current checks after leaf moves.');

$throwing=new InMemoryAssignmentOrderOriginalInitialEnvironment();$base=$throwing->dependencies();$authorizer=new class implements AssignmentOrderOriginalAuthorizer{public function authorize(int$a,string$c):AssignmentOrderOriginalAuthorizationStatus{throw new RuntimeException('private port detail');}};$deps=new AssignmentOrderOriginalDependencies($authorizer,$base->compositions,$base->clock,$base->ids,$base->inspector,$base->storage,$base->repository,$base->lifecycle,$base->storageObserver,$base->faults,$base->safeLog,$base->delivery);$reads=0;$thrownResult=AssignmentOrderOriginalVerificationFactory::create($deps)->submitAssignmentOrderOriginal($command('00000000-0000-4000-8000-000000000210',AssignmentOrderOriginalMode::INITIAL,$stream($reads)));assertSameValue([AssignmentOrderOriginalStatus::FAILED,AssignmentOrderOriginalReason::PERSISTENCE_FAILURE,true],[$thrownResult->status(),$thrownResult->reasonCode(),$thrownResult->retryable()],'Port Throwable maps to typed safe persistence failure.');assertSameValue(0,$reads,'Authorization port failure does not read stream.');
fwrite(STDOUT,"ASSIGNMENT_ORDER_ORIGINAL_UPLOAD_TOTALITY_REPLAY_OK\n");
