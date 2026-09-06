<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;

final class OriginalIntegrityNoSql extends \mysqli
{
    public array $calls=[];
    public function __construct(){}
    private function deny(string $name):never{$this->calls[]=$name;throw new \RuntimeException('synthetic SQL call sentinel');}
    public function query(string $query,int $result_mode=MYSQLI_STORE_RESULT):\mysqli_result|bool{$this->deny('query');}
    public function real_escape_string(string $string):string{$this->deny('escape');}
    public function begin_transaction(int $flags=0,?string $name=null):bool{$this->deny('begin');}
    public function commit(int $flags=0,?string $name=null):bool{$this->deny('commit');}
    public function rollback(int $flags=0,?string $name=null):bool{$this->deny('rollback');}
}
final class OriginalIntegrityCommits
{
    public static function acceptedValues(array $changes=[],bool $recomputeFingerprint=true):array
    {
        $v=$changes+['requestId'=>'00000000-0000-4000-8000-000000000001','fingerprint'=>'', 'mode'=>O\AssignmentOrderOriginalMode::INITIAL,
            'installationCaseId'=>4512,'assignmentOrderId'=>81,'actorUserId'=>18,'rootOriginalId'=>'original-0001','newRevisionId'=>'revision-0001','newRevisionNumber'=>1,
            'previousRevisionId'=>null,'expectedCurrentRevisionId'=>null,'compositionIdentity'=>'composition-81-v1',
            'compositionSha256'=>'388c7d94b3cf91235dabddf26398ac05f754d3d12a0b41a7a91ac3d5370faba5','documentDate'=>'2026-09-01','uploadedAt'=>'2026-09-02T09:15:30Z',
            'pdfSha256'=>'4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784','byteSize'=>327,
            'privateContentIdentity'=>'content-sha256-4028af3714fa07d2f20e758649532faef11b4818c99a2b8dc0c88170a0dc8784','correctionReason'=>null,'domainEventType'=>'assignment_order_original_accepted'];
        if($recomputeFingerprint&&!array_key_exists('fingerprint',$changes))$v['fingerprint']=self::fingerprint($v);
        return $v;
    }
    public static function fingerprint(array $v):string
    {
        $correction=$v['mode']===O\AssignmentOrderOriginalMode::CORRECTION;$bytes='';
        foreach([$v['mode']->value,(string)$v['installationCaseId'],(string)$v['assignmentOrderId'],$correction?$v['rootOriginalId']:'',
            $correction?($v['previousRevisionId']??''):'',$correction?($v['expectedCurrentRevisionId']??''):'',$v['documentDate'],$v['compositionIdentity'],$v['compositionSha256'],$v['pdfSha256']] as $part)$bytes.=pack('N',strlen($part)).$part;
        return hash('sha256',$bytes);
    }
    public static function accepted(array $changes=[]):O\AssignmentOrderOriginalAcceptedCommit{return new O\AssignmentOrderOriginalAcceptedCommit(...self::acceptedValues($changes));}
    public static function correctionValues():array{return ['requestId'=>'00000000-0000-4000-8000-000000000101','mode'=>O\AssignmentOrderOriginalMode::CORRECTION,
        'newRevisionId'=>'revision-0002','newRevisionNumber'=>2,'previousRevisionId'=>'revision-0001','expectedCurrentRevisionId'=>'revision-0001',
        'documentDate'=>'2026-09-02','uploadedAt'=>'2026-09-02T09:16:00Z','correctionReason'=>'Исправлена дата','domainEventType'=>'assignment_order_original_corrected'];}
    public static function attempt(array $changes=[]):O\AssignmentOrderOriginalAttemptCommit
    {return new O\AssignmentOrderOriginalAttemptCommit(...($changes+['requestId'=>'00000000-0000-4000-8000-000000000301','actorUserId'=>18,'mode'=>O\AssignmentOrderOriginalMode::INITIAL,'installationCaseId'=>4512,'assignmentOrderId'=>81,'status'=>O\AssignmentOrderOriginalStatus::REJECTED,'reason'=>O\AssignmentOrderOriginalReason::INVALID_PDF,'retryable'=>false,'attemptedAt'=>'2026-09-02T09:15:30Z']));}
}
