<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplicationReferenceFactory;
use FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalApplicationReferenceStatus;

/** Read-only use of the same application, original and workforce rules as opening. */
final readonly class MariaDbCurrentOpeningEligibility
{
    public function __construct(private \mysqli $db, private string $prefix) {}

    public function reasons(int $objectId, string $asOfDate): array
    {
        $reader = AssignmentOrderApplicationReaderFactory::create($this->db, $this->prefix);
        $current = $reader->readCurrent($objectId);
        if ($current->status !== 'found' || $current->value === null) return ['Нет применимого состава распоряжения'];
        $application = $current->value['application'];
        $references=AssignmentOrderOriginalApplicationReferenceFactory::create($this->db,$this->prefix);
        $reference=$references->readCurrent($objectId,$application['orderId']);
        if($reference->status!==AssignmentOrderOriginalApplicationReferenceStatus::FOUND
            ||$reference->reference===null)return['Нет подписанного оригинала распоряжения'];
        if($reference->reference->metadata()['revisionId']!==$application['originalRevisionId']){
            return['Оригинал требует повторного применения'];
        }
        $case = $this->caseRow($objectId);
        if ($case === null) return ['Недостаточно данных для оценки'];
        if ($case['actual_start_date'] !== null) return [];
        if ($this->hasCompletionEvidence((int)$case['id'], $objectId)) return ['Есть основание завершения или акт ПТО'];
        return $this->workforceReasons($application, $asOfDate);
    }

    private function caseRow(int $objectId): ?array
    {
        $statement=$this->db->prepare("SELECT id,actual_start_date FROM `{$this->prefix}fm2_installation_cases` WHERE legacy_installation_object_id=?");
        $statement->bind_param('i',$objectId);$statement->execute();$rows=$statement->get_result()->fetch_all(MYSQLI_ASSOC);
        return count($rows)===1?$rows[0]:null;
    }

    private function hasCompletionEvidence(int $caseId,int $objectId): bool
    {
        $sql="SELECT 1 FROM `{$this->prefix}fm2_pilot_completion_facts` "
            .'WHERE installation_case_id=? LIMIT 1';
        $statement=$this->db->prepare($sql);
        $statement->bind_param('i',$caseId);
        $statement->execute();
        if($statement->get_result()->fetch_row()!==null)return true;
        $statement=$this->db->prepare(
            "SELECT workdatefinish,ptoactdate FROM `{$this->prefix}fm_maintable` WHERE id=?",
        );
        $statement->bind_param('i',$objectId);
        $statement->execute();
        $row=$statement->get_result()->fetch_assoc();
        return $row!==null&&($this->datePresent($row['workdatefinish'])||$this->datePresent($row['ptoactdate']));
    }

    private function workforceReasons(array $application,string $asOfDate): array
    {
        $this->db->begin_transaction();
        try{
            $status=AssignmentOrderCurrentEligibility::confirm(
                $this->db,$this->prefix,$application['installerTabIds'],
                $application['engineerUserId'],$application['documentDate'],
            );
            $this->db->rollback();
        }catch(\Throwable){
            try{$this->db->rollback();}catch(\Throwable){}
            return['Недостаточно данных для оценки'];
        }
        return match($status){
            'eligible'=>[],
            'installer_not_employed'=>['Монтажник не подтверждён как трудоустроенный'],
            'control_engineer_required','control_engineer_not_eligible'=>[
                'Не подтверждён инженер строительного контроля',
            ],
            default=>['Недостаточно данных для оценки'],
        };
    }
    private function datePresent(mixed$value):bool{return is_string($value)&&$value!==''&&!str_starts_with($value,'0000-00-00');}
}
