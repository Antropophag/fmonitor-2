<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once __DIR__.'/MariaDbInstallationCaseIdResolver.php';require_once __DIR__.'/MariaDbChecklistProjectionStateReader.php';
foreach(['InspectionEvidenceClock','InspectionRecording','InspectionEvidenceView','InspectionEvidenceApplication','CompleteInspectionItem','ItemCompletionResult','InstallerEvidence','ItemCompletionEvidence','InspectionItemCommandPolicy','ItemCompletionEvidenceCodec','InspectionEvidence','MariaDbInspectionAuthorization','MariaDbInspectionTransaction','MariaDbInspectionCaseDirectory','MariaDbInspectionTemplateDirectory','MariaDbInspectionEvidenceWriter','MariaDbInspectionEvidenceReader','MariaDbChecklistMutationFacts','MariaDbInspectionEvidenceEnvironment','ProductionInspectionEvidenceConfig','ProductionInspectionEvidenceFactory']as$file)require_once \dirname(__DIR__).'/InspectionEvidence/'.$file.'.php';
foreach(['AssignmentOrderApplicationReadResult','AssignmentOrderApplicationReader','AssignmentOrderApplicationPayload','MariaDbAssignmentOrderApplicationSql','MariaDbAssignmentOrderApplicationReader','AssignmentOrderApplicationReaderFactory']as$file)require_once \dirname(__DIR__).'/AssignmentOrderComposition/'.$file.'.php';

use FMonitor2\InspectionEvidence\{CompleteInspectionItem,InspectionEvidenceClock,InspectionRecording,MariaDbChecklistMutationFacts,ProductionInspectionEvidenceConfig,ProductionInspectionEvidenceFactory};use FMonitor2\InstallationProcess\{InspectionEvidenceSchemaMigration,InspectionPhotoContentIndexSchemaMigration};
final class ChecklistSync
{
    private const SECTION_ITEMS=[1=>[28,29,30,31,32,33,34,35,36],2=>[37,38,39,40,41],3=>[1,2,3,4,5,6],4=>[7,8,9,10],5=>[11,12,13,14,15],6=>[16,17,18,19,20,21],7=>[22,23,24,25,26,27],8=>[42]];
    private ?InspectionRecording $inspectionRecording;private readonly \Closure $installationCaseIdResolver;private readonly ProductionInspectionEvidenceConfig $inspectionConfig;private readonly MariaDbChecklistMutationFacts $mutationFacts;private ?array $appliedComposition=null;
    public function __construct(
        private readonly \mysqli $db,
        private readonly string $prefix,
        private readonly string $storageRoot,
        private readonly string $now,
        ?InspectionRecording $inspectionRecording = null,
        ?callable $installationCaseIdResolver = null,
    ) {
        $this->inspectionConfig = new ProductionInspectionEvidenceConfig($prefix);
        $this->mutationFacts = new MariaDbChecklistMutationFacts($db,$prefix);
        $this->inspectionRecording = $inspectionRecording;
        $this->installationCaseIdResolver = $installationCaseIdResolver === null
            ? $this->resolveInstallationCaseId(...)
            : \Closure::fromCallable($installationCaseIdResolver);
    }
    public function ensureSchema():void
    {
        if (!InspectionPhotoContentIndexSchemaMigration::isCompleteCompatible($this->db, $this->prefix) && !InspectionEvidenceSchemaMigration::isCompleteCompatible($this->db, $this->prefix)) {
            throw new PilotHttpInfrastructureUnavailable('INSPECTION_EVIDENCE_SCHEMA_REQUIRED');
        }
    }
    public function projection(int $objectId):array
    {
        return $this->shared()->projection($objectId);
    }
    public function accept(int $objectId,HttpUser $actor,array $operation,?string $bytes=null):array
    {
        if((string)($operation['type']??'')==='item_completed'){
            if($this->inspectionRecording===null)$this->loadAppliedComposition($objectId);
            return $this->completeItem($objectId,$actor,$operation);
        }
        try{return $this->shared()->accept($objectId,$actor->id,$operation,$bytes);}
        catch(\FMonitor2\InspectionEvidence\ChecklistInfrastructureUnavailable $failure){throw new PilotHttpInfrastructureUnavailable('CHECKLIST_INFRASTRUCTURE_UNAVAILABLE',0,$failure);}
    }
    private function shared():\FMonitor2\InspectionEvidence\MariaDbYiiChecklist
    {
        return new \FMonitor2\InspectionEvidence\MariaDbYiiChecklist(
            $this->db,$this->prefix,$this->prefix,$this->storageRoot,$this->now,
        );
    }
    private function completeItem(int $objectId,HttpUser $actor,array $operation):array
    {
        try{$caseId=($this->installationCaseIdResolver)($objectId);}catch(PilotHttpInfrastructureUnavailable $e){throw $e;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('INSTALLATION_CASE_RESOLUTION_FAILED',0,$e);}
        if($caseId===null)return ['status'=>'rejected','revision'=>0];
        $result=$this->inspectionRecording()->completeItem(new CompleteInspectionItem(
            $actor->id,
            $caseId,
            (string)($operation['clientOperationId']??''),
            (string)($operation['deviceInstallationId']??''),
            (string)($operation['deviceTime']??''),
            (int)($operation['baseRevision']??-1),
            (int)($operation['sectionId']??0),
            (int)($operation['itemId']??0),
            \array_map('intval',(array)($operation['installerTabIds']??[])),
        ));
        if($result->status==='INSPECTION_SCHEMA_UNAVAILABLE')throw new PilotHttpInfrastructureUnavailable('INSPECTION_EVIDENCE_SCHEMA_REQUIRED');
        $status=match($result->status){
            'ACCEPTED'=>'accepted',
            'DUPLICATE'=>'duplicate',
            'STALE_REVISION','OPERATION_PAYLOAD_CONFLICT'=>'conflict',
            default=>'rejected',
        };
        return ['status'=>$status,'revision'=>$result->revision];
    }

    private function inspectionRecording():InspectionRecording
    {
        if($this->inspectionRecording!==null)return $this->inspectionRecording;
        $clock=new readonly class($this->now) implements InspectionEvidenceClock{
            public function __construct(private string$now){}
            public function now():\DateTimeImmutable{return new \DateTimeImmutable($this->now);}
        };
        return $this->inspectionRecording=ProductionInspectionEvidenceFactory::create(
            $this->db,
            $this->inspectionConfig,
            $clock,
            $this->appliedComposition,
        );
    }

    private function resolveInstallationCaseId(int $objectId):?int
    {
        $resolver=new MariaDbInstallationCaseIdResolver(
            $this->db,
            $this->inspectionConfig,
        );
        return $resolver($objectId);
    }

    private function loadAppliedComposition(int $objectId):void{try{$result=\FMonitor2\AssignmentOrderComposition\AssignmentOrderApplicationReaderFactory::create($this->db,$this->prefix)->readCurrent($objectId);}catch(\Throwable $e){try{$this->db->thread_id;}catch(\Throwable){return;}if($this->inspectionRecording!==null||!$this->applicationStorageExists())return;throw new PilotHttpInfrastructureUnavailable('ASSIGNMENT_ORDER_APPLICATION_READ_UNAVAILABLE',0,$e);}if($result->status==='found'){$this->appliedComposition=$result->value;return;}if($result->status==='unavailable'){if(!$this->applicationStorageExists())return;throw new PilotHttpInfrastructureUnavailable('ASSIGNMENT_ORDER_APPLICATION_READ_UNAVAILABLE');}$this->appliedComposition=null;}
    private function applicationStorageExists():bool{return$this->mutationFacts->applicationStorageExists();}
}
