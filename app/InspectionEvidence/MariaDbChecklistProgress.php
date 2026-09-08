<?php
declare(strict_types=1);
namespace FMonitor2\InspectionEvidence;

final class MariaDbChecklistProgress
{
 private const WEIGHTS=[28=>2,29=>2,30=>2,31=>2,32=>1,33=>1,34=>2,35=>1,36=>2,37=>3,38=>3,39=>3,40=>3,41=>2,1=>2,2=>2,3=>1,4=>2,5=>1,6=>1,7=>2,8=>5,9=>1,10=>1,11=>3,12=>2,13=>2,14=>1,15=>2,16=>4,17=>4,18=>3,19=>3,20=>3,21=>2,22=>3,23=>2,24=>1,25=>1,26=>1,27=>1];
 public function __construct(private readonly \mysqli$db,private readonly string$p){if(preg_match('/^[A-Za-z0-9_]*$/D',$p)!==1)throw new \InvalidArgumentException();}
 public function forCase(int$caseId):int{return$this->forCases([$caseId])[$caseId]??0;}
 /** @param list<int> $caseIds @return array<int,int> */
 public function forCases(array$caseIds):array
 {
  $caseIds=array_values(array_unique(array_filter(array_map('intval',$caseIds),fn($id)=>$id>0)));if($caseIds===[])return[];$cases=implode(',',$caseIds);$items=implode(',',array_keys(self::WEIGHTS));$rows=$this->db->query("SELECT installation_case_id,item_id,operation_type FROM `{$this->p}fm2_checklist_operations` WHERE installation_case_id IN($cases) AND item_id IN($items) AND operation_type IN('item_completed','completion_retracted') ORDER BY installation_case_id,item_id,accepted_revision,id")->fetch_all(MYSQLI_ASSOC);$latest=[];foreach($rows as$row)$latest[(int)$row['installation_case_id']][(int)$row['item_id']]=$row['operation_type'];$out=array_fill_keys($caseIds,0);foreach($latest as$case=>$states)foreach($states as$item=>$state)if($state==='item_completed')$out[$case]+=self::WEIGHTS[$item];foreach($out as&$value)$value=min(85,$value);return$out;
 }
}
