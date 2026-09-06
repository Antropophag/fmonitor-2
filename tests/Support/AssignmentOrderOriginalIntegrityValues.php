<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

/** Foreign passive values deliberately permit semantically impossible, PHP-type-correct tuples. */
final class OriginalIntegrityResult implements O\AssignmentOrderOriginalResult
{
    public array $gets=[];
    public function __construct(public array $values=[],private ?string $throws=null)
    {
        $this->values += ['status'=>O\AssignmentOrderOriginalStatus::ACCEPTED,'reason'=>null,'retryable'=>false,
            'request'=>'00000000-0000-4000-8000-000000000001','root'=>'original-0001','revision'=>'revision-0001',
            'number'=>1,'date'=>'2026-09-01','sha'=>'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',
            'size'=>327,'at'=>'2026-09-02T09:15:30Z'];
    }
    private function get(string $key):mixed
    { $this->gets[$key]=($this->gets[$key]??0)+1;if($key===$this->throws)throw new \RuntimeException('synthetic result getter');return $this->values[$key]; }
    public function status():O\AssignmentOrderOriginalStatus{return $this->get('status');}
    public function reasonCode():?O\AssignmentOrderOriginalReason{return $this->get('reason');}
    public function retryable():bool{return $this->get('retryable');}
    public function requestId():string{return $this->get('request');}
    public function rootOriginalId():?string{return $this->get('root');}
    public function currentRevisionId():?string{return $this->get('revision');}
    public function revisionNumber():?int{return $this->get('number');}
    public function documentDate():?string{return $this->get('date');}
    public function sha256():?string{return $this->get('sha');}
    public function byteSize():?int{return $this->get('size');}
    public function uploadedAt():?string{return $this->get('at');}
    public static function rejected(O\AssignmentOrderOriginalReason $reason=O\AssignmentOrderOriginalReason::INVALID_PDF):self
    {return new self(['status'=>O\AssignmentOrderOriginalStatus::REJECTED,'reason'=>$reason,'root'=>null,'revision'=>null,'number'=>null,'date'=>null,'sha'=>null,'size'=>null,'at'=>null]);}
}
final class OriginalIntegrityLookup implements O\AssignmentOrderOriginalResultLookup
{
    public int $statusCalls=0;public int $resultCalls=0;
    public function __construct(private O\AssignmentOrderOriginalLookupStatus $state,private ?O\AssignmentOrderOriginalResult $value=null,private ?string $throws=null){}
    public function status():O\AssignmentOrderOriginalLookupStatus{++$this->statusCalls;if($this->throws==='status')throw new \RuntimeException('lookup status');return $this->state;}
    public function result():?O\AssignmentOrderOriginalResult{++$this->resultCalls;if($this->throws==='result')throw new \RuntimeException('lookup result');return $this->value;}
}

// This test-only marker does not define/alias any production class. Missing public API
// is separately an intended RED; old production can still receive a foreign base value.
if(interface_exists(O\AssignmentOrderOriginalCompleteLineageLookup::class)){
    interface OriginalIntegrityCompleteMarker extends O\AssignmentOrderOriginalCompleteLineageLookup {}
}else{
    interface OriginalIntegrityCompleteMarker extends O\AssignmentOrderOriginalLineageLookup,O\AssignmentOrderOriginalCurrentEvidenceLookup {}
}
final class OriginalIntegrityLineage implements OriginalIntegrityCompleteMarker
{
    public array $gets=[];public array $membershipCalls=[];
    public function __construct(public array $values=[],private ?string $throws=null)
    {
        $this->values += ['status'=>O\AssignmentOrderOriginalLookupStatus::FOUND,'root'=>'original-0001','revision'=>'revision-0001','number'=>1,
            'identity'=>'composition-81-v1','hash'=>'388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5',
            'date'=>'2026-09-01','sha'=>'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784',
            'case'=>4512,'order'=>81,'ids'=>['revision-0001'],'contains'=>null];
    }
    private function get(string $key):mixed
    {$this->gets[$key]=($this->gets[$key]??0)+1;if($key===$this->throws)throw new \RuntimeException('lineage getter');return $this->values[$key];}
    public function status():O\AssignmentOrderOriginalLookupStatus{return $this->get('status');}
    public function rootOriginalId():?string{return $this->get('root');}
    public function currentRevisionId():?string{return $this->get('revision');}
    public function currentRevisionNumber():?int{return $this->get('number');}
    public function compositionIdentity():?string{return $this->get('identity');}
    public function compositionSha256():?string{return $this->get('hash');}
    public function currentDocumentDate():?string{return $this->get('date');}
    public function currentPdfSha256():?string{return $this->get('sha');}
    public function installationCaseId():?int{return $this->get('case');}
    public function assignmentOrderId():?int{return $this->get('order');}
    public function revisionIds():array{return $this->get('ids');}
    public function containsRevision(string $revisionId):bool
    {$this->membershipCalls[]=$revisionId;if($this->throws==='contains')throw new \RuntimeException('membership getter');return $this->values['contains']??in_array($revisionId,$this->values['ids'],true);}
    public static function absent(O\AssignmentOrderOriginalLookupStatus $status=O\AssignmentOrderOriginalLookupStatus::NOT_FOUND):self
    {return new self(['status'=>$status,'root'=>null,'revision'=>null,'number'=>null,'identity'=>null,'hash'=>null,'date'=>null,'sha'=>null,'case'=>null,'order'=>null,'ids'=>[],'contains'=>false]);}
}
final class OriginalIntegrityIncompleteLineage implements O\AssignmentOrderOriginalLineageLookup
{
    public function status():O\AssignmentOrderOriginalLookupStatus{return O\AssignmentOrderOriginalLookupStatus::FOUND;}
    public function rootOriginalId():?string{return 'original-0001';}
    public function currentRevisionId():?string{return 'revision-0001';}
    public function currentRevisionNumber():?int{return 1;}
    public function compositionIdentity():?string{return 'composition-81-v1';}
    public function compositionSha256():?string{return '388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5';}
    public function containsRevision(string $revisionId):bool{return $revisionId==='revision-0001';}
}
