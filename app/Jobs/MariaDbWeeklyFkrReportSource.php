<?php
declare(strict_types=1);
namespace FMonitor2\Jobs;

use FMonitor2\InspectionEvidence\ProductionChecklistProgressFactory;

/** Global read-only report projection using canonical checklist progress weights. */
final readonly class MariaDbWeeklyFkrReportSource implements WeeklyFkrReportSource
{
    public function __construct(private \mysqli $db, private string $prefix, private string $legacyPrefix) {}

    public function allObjectsAsOf(string $start, string $end, string $generatedAt): array
    {
        $rows=$this->objects();
        if($rows===[])return[];
        $caseIds=array_map('intval',array_column($rows,'case_id'));
        $progress=ProductionChecklistProgressFactory::create($this->db,$this->prefix);
        $startUtc=$this->moscowEndOfDay((new \DateTimeImmutable($start,new \DateTimeZone('Europe/Moscow')))->modify('-1 day'));
        $endUtc=$this->moscowEndOfDay(new \DateTimeImmutable($end,new \DateTimeZone('Europe/Moscow')));
        $generatedUtc=(new \DateTimeImmutable($generatedAt))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');
        $workStart=$progress->forCasesAsOf($caseIds,$startUtc);$workEnd=$progress->forCasesAsOf($caseIds,$endUtc);$workCurrent=$progress->forCasesAsOf($caseIds,$generatedUtc);
        $documentsStart=$this->documentsAsOf($caseIds,$startUtc);
        $documentsEnd=$this->documentsAsOf($caseIds,$endUtc);
        $documentsCurrent=$this->documentsAsOf($caseIds,$generatedUtc);
        return array_map(
            fn(array $row): array => $this->project(
                $row,$workStart,$workEnd,$workCurrent,
                $documentsStart,$documentsEnd,$documentsCurrent,
            ),
            $rows,
        );
    }

    private function objects(): array
    {
        $p=$this->prefix;$l=$this->legacyPrefix;
        $sql="SELECT c.id case_id,c.legacy_installation_object_id id,c.actual_start_date,"
            ."l.regnumber,l.ordadr_address,l.workdatestart,l.workdateendadjusted,l.plan_finish_date "
            ."FROM `{$p}fm2_installation_cases` c "
            ."JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id "
            ."ORDER BY c.legacy_installation_object_id";
        return $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    private function documentsAsOf(array $caseIds,string $asOfUtc): array
    {
        $result=array_fill_keys($caseIds,0);if($caseIds===[])return$result;$ids=implode(',',$caseIds);
        $sql="SELECT installation_case_id,fact_type "
            ."FROM `{$this->prefix}fm2_pilot_completion_facts` "
            ."WHERE installation_case_id IN({$ids}) AND recorded_at<=?";
        $statement=$this->db->prepare($sql);
        $statement->bind_param('s',$asOfUtc);
        $statement->execute();$types=[];
        foreach($statement->get_result()->fetch_all(MYSQLI_ASSOC)as$row)$types[(int)$row['installation_case_id']][$row['fact_type']]=true;
        foreach($types as$case=>$facts)$result[$case]=(isset($facts['pto_act'])?7:0)+(isset($facts['declaration'])?8:0);
        return$result;
    }

    private function project(array$row,array$workStart,array$workEnd,array$workCurrent,array$documentsStart,array$documentsEnd,array$documentsCurrent):array
    {
        $case=(int)$row['case_id'];$work=$workCurrent[$case]??null;$documents=$documentsCurrent[$case]??null;
        return [
            'id'=>(string)$row['id'],
            'registrationNumber'=>(string)$row['regnumber'],
            'address'=>(string)$row['ordadr_address'],
            'openingPlanDate'=>$this->date($row['workdatestart']),
            'openingCompleted'=>$row['actual_start_date']!==null,
            'openingStatus'=>$row['actual_start_date']!==null?'Открыт':'Не открыт',
            'openingReasons'=>[],
            'closingPlanDate'=>$this->date($row['workdateendadjusted'])??$this->date($row['plan_finish_date']),
            'closingCompleted'=>$work===85&&$documents===15,
            'work'=>$work,
            'documents'=>$documents,
            'progressStart'=>isset($workStart[$case],$documentsStart[$case])
                ? ['work'=>$workStart[$case],'documents'=>$documentsStart[$case]] : null,
            'progressEnd'=>isset($workEnd[$case],$documentsEnd[$case])
                ? ['work'=>$workEnd[$case],'documents'=>$documentsEnd[$case]] : null,
            'violations'=>[],
        ];
    }
    private function moscowEndOfDay(\DateTimeImmutable$date):string{return$date->setTime(23,59,59,999999)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z');}
    private function date(mixed$value):?string{return is_string($value)&&preg_match('/^\d{4}-\d{2}-\d{2}/',$value)===1?substr($value,0,10):null;}
}
