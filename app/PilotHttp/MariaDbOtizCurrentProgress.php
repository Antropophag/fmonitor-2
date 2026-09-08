<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;

require_once \dirname(__DIR__).'/InspectionEvidence/MariaDbChecklistProgress.php';

final readonly class MariaDbOtizCurrentProgress
{
    public function __construct(private \mysqli $db,private string $prefix)
    { if(\preg_match('/^[A-Za-z0-9_]*$/D',$prefix)!==1)throw new \InvalidArgumentException(); }

    /** @param list<int> $objectIds @return array<int,array{progressBp:int,factDate:?string}> */
    public function read(array $objectIds):array
    {
        $objectIds=\array_values(\array_unique(\array_filter(\array_map('intval',$objectIds),static fn(int$id):bool=>$id>0)));if($objectIds===[])return[];$ids=\implode(',',$objectIds);
        $cases=$this->db->query("SELECT id,legacy_installation_object_id FROM `{$this->prefix}fm2_installation_cases` WHERE legacy_installation_object_id IN({$ids})")->fetch_all(MYSQLI_ASSOC);if($cases===[])return[];
        $caseIds=\array_map('intval',\array_column($cases,'id'));$caseList=\implode(',',$caseIds);$progress=(new \FMonitor2\InspectionEvidence\MariaDbChecklistProgress($this->db,$this->prefix))->forCases($caseIds);
        $facts=[];foreach($this->db->query("SELECT installation_case_id,MAX(fact_type='pto_act') has_pto,MAX(fact_type='declaration') has_declaration,MAX(recorded_at) last_fact_at FROM `{$this->prefix}fm2_pilot_completion_facts` WHERE installation_case_id IN({$caseList}) GROUP BY installation_case_id")->fetch_all(MYSQLI_ASSOC)as$row)$facts[(int)$row['installation_case_id']]=$row;
        $activity=[];foreach($this->db->query("SELECT installation_case_id,MAX(server_received_at) last_activity_at FROM `{$this->prefix}fm2_checklist_operations` WHERE installation_case_id IN({$caseList}) GROUP BY installation_case_id")->fetch_all(MYSQLI_ASSOC)as$row)$activity[(int)$row['installation_case_id']]=$row['last_activity_at'];
        $out=[];foreach($cases as$row){$case=(int)$row['id'];$fact=$facts[$case]??[];$complete=(int)($fact['has_pto']??0)===1&&(int)($fact['has_declaration']??0)===1;$at=\max((string)($activity[$case]??''),(string)($fact['last_fact_at']??''));$out[(int)$row['legacy_installation_object_id']]=['progressBp'=>$complete?10000:\min(85,(int)($progress[$case]??0))*100,'factDate'=>$at===''?null:\substr($at,0,10)];}return$out;
    }
}
