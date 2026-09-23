<?php
declare(strict_types=1);
namespace FMonitor2\AssignmentOrderComposition;

final readonly class MariaDbCurrentInstallerAssignmentsQuery implements CurrentInstallerAssignmentsQuery
{
    public function __construct(private \mysqli $db, private string $prefix = '') {}

    public function find(array $installerTabIds): array
    {
        $tabIds=array_values(array_unique(array_filter(array_map('intval',$installerTabIds),static fn(int$id):bool=>$id>0)));
        if($tabIds===[])return[];

        $ids=implode(',',$tabIds);$p=$this->prefix;
        $completion="NOT (EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` pto WHERE pto.installation_case_id=c.id AND pto.fact_type='pto_act') AND EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` declaration WHERE declaration.installation_case_id=c.id AND declaration.fact_type='declaration'))";
        $day=$this->db->real_escape_string((new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'));
        $legacy=$this->rows("SELECT oi.installer_tab_id,c.legacy_installation_object_id object_id,CONCAT(o.registration_number,' · ',l.regnumber) registration_number,l.ordadr_address address FROM `{$p}fm2_order_installers` oi JOIN `{$p}fm2_assignment_orders` o ON o.id=oi.assignment_order_id JOIN `{$p}fm2_installation_cases` c ON c.id=o.installation_case_id JOIN `{$p}fm_maintable` l ON l.id=c.legacy_installation_object_id WHERE oi.installer_tab_id IN ({$ids}) AND o.status='registered' AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x WHERE x.installation_case_id=o.installation_case_id) AND oi.change_action<>'release' AND oi.valid_from<='{$day}' AND (oi.valid_to IS NULL OR oi.valid_to>='{$day}') AND NOT EXISTS(SELECT 1 FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=o.installation_case_id) AND {$completion} ORDER BY oi.installer_tab_id,c.legacy_installation_object_id");

        $wantedSql=implode(' UNION ALL ',array_map(static fn(int$id):string=>"SELECT {$id} tab_id",$tabIds));
        $native=$this->rows("SELECT wanted.tab_id,a.selected_snapshot_json,c.legacy_installation_object_id object_id,l.regnumber registration_number,l.ordadr_address address FROM ({$wantedSql}) wanted JOIN `{$p}fm2_assignment_order_applications` a ON JSON_CONTAINS(a.selected_snapshot_json,CONCAT('{\"tabId\":',wanted.tab_id,'}'),'$.selectedInstallers')=1 JOIN `{$p}fm2_installation_cases` c ON c.id=a.installation_case_id JOIN `{$p}fm_maintable` l ON l.id=c.legacy_installation_object_id WHERE a.application_sequence=(SELECT MAX(x.application_sequence) FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=a.installation_case_id) AND {$completion} ORDER BY wanted.tab_id,c.legacy_installation_object_id");

        $out=[];$add=static function(array&$target,int$tabId,array$row):void{$objectId=(int)$row['object_id'];if($tabId<1||$objectId<1||trim((string)$row['registration_number'])===''||trim((string)$row['address'])==='')throw new \RuntimeException();$target[$tabId][$objectId]=['objectId'=>$objectId,'registrationNumber'=>(string)$row['registration_number'],'address'=>(string)$row['address']];};
        foreach($legacy as$row)$add($out,(int)$row['installer_tab_id'],$row);
        $wanted=array_fill_keys($tabIds,true);
        foreach($native as$row){$snapshot=json_decode((string)$row['selected_snapshot_json'],true,512,JSON_THROW_ON_ERROR);if(!is_array($snapshot)||!isset($snapshot['selectedInstallers'])||!is_array($snapshot['selectedInstallers']))throw new \RuntimeException();$tabId=(int)$row['tab_id'];$selected=false;foreach($snapshot['selectedInstallers']as$installer){if(!is_array($installer)||!isset($installer['tabId'])||filter_var($installer['tabId'],FILTER_VALIDATE_INT)===false)throw new \RuntimeException();if((int)$installer['tabId']===$tabId)$selected=true;}if(!$selected||!isset($wanted[$tabId]))throw new \RuntimeException();$add($out,$tabId,$row);}
        foreach($out as&$rows){ksort($rows,SORT_NUMERIC);$rows=array_values($rows);}unset($rows);return$out;
    }

    private function rows(string $sql): array
    {
        $result=$this->db->query($sql);if(!$result instanceof \mysqli_result)throw new \RuntimeException();return$result->fetch_all(MYSQLI_ASSOC);
    }
}
