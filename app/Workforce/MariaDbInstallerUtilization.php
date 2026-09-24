<?php

declare(strict_types=1);

namespace FMonitor2\Workforce;

use FMonitor2\InstallationProcess\YiiObjectCardApplicationIntegrity;
use FMonitor2\InstallationProcess\MariaDbEffectiveObjectDetails;
use FMonitor2\InstallationProcess\MariaDbYiiObjectQueue;
use yii\db\Connection;
use yii\db\Expression;
use yii\db\Query;

final class MariaDbInstallerUtilization
{
    private const PAGE_SIZE = 50;

    public function __construct(private readonly Connection $db, private readonly string $processPrefix, private readonly string $legacyPrefix)
    {
        foreach ([$processPrefix, $legacyPrefix] as $prefix) {
            if (strlen($prefix) > 32 || preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) throw new \RuntimeException('Utilization configuration unavailable.');
        }
    }

    public function directory(array $filters, int $actorId=0):array
    {
        [$ids,$total,$summary]=$this->directoryPage($filters,$actorId);$pages=max(1,(int)ceil($total/self::PAGE_SIZE));if($filters['page']>$pages)throw new \OutOfRangeException('Directory page unavailable.');$projection=$this->projection($actorId,$ids);$byId=[];foreach($projection['directory']as$row)$byId[$row['installer_tab_id']]=$row;$rows=[];foreach($ids as$id)if(isset($byId[$id]))$rows[]=$byId[$id];
        return['summary'=>$summary,'rows'=>$rows,'filters'=>$filters+['total'=>$total,'pages'=>$pages]];
    }

    public function card(int $tabId, int $historyPage, int $actorId=0): array
    {
        $projection = $this->projection($actorId);
        $installer = null;
        foreach ($projection['installers'] as $candidate) if ($candidate['installer_tab_id'] === $tabId) $installer = $candidate;
        if ($installer === null) throw new \DomainException('Installer not found.');
        $installer['assignments']=$installer['currentWorks'];
        [$periods,$periodTotal]=$this->historyPage($tabId,$historyPage,$actorId);
        $pages = max(1, (int) ceil($periodTotal / self::PAGE_SIZE));
        if ($historyPage > $pages) throw new \OutOfRangeException('History page unavailable.');
        return $projection + ['installer'=>$installer,'periods'=>$periods,'history'=>['page'=>$historyPage,'pages'=>$pages,'total'=>$periodTotal]];
    }

    public function compact(array $tabIds,int $actorId=0): array
    {
        $wanted=array_fill_keys(array_map('intval',$tabIds),true);$result=[];
        foreach($this->projection($actorId)['installers'] as$row)if(isset($wanted[$row['installer_tab_id']]))$result[$row['installer_tab_id']]=['currentWorkCount'=>$row['currentWorkCount'],'upcomingAssignments'=>array_map(static fn(array$a):array=>['objectId'=>$a['object_id'],'registrationNumber'=>$a['registration_number'],'address'=>$a['address'],'plannedStartDate'=>$a['planned_start']],$row['upcomingAssignments'])];
        return$result;
    }

    private function directoryPage(array$filters,int$actorId):array
    {
        $p=$this->processPrefix;$catalog="`{$p}fm2_workforce_catalog`";$cases="`{$p}fm2_installation_cases`";$apps="`{$p}fm2_assignment_order_applications`";$orders="`{$p}fm2_assignment_orders`";$members="`{$p}fm2_order_installers`";$selections="`{$p}fm2_assignment_order_selections`";$selectionMembers="`{$p}fm2_assignment_order_selection_members`";$roots="`{$p}fm2_assignment_order_original_roots`";$pto="`{$p}fm2_pilot_completion_facts`";$today=$this->db->quoteValue((new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'));$actor=(int)$actorId;
        $scope="(NOT EXISTS(SELECT 1 FROM `{$p}fm2_pilot_user_roles` dur JOIN `{$p}fm2_pilot_roles` dr ON dr.role_id=dur.role_id AND dr.status=1 WHERE dur.user_id=$actor AND BINARY dr.code='construction_control_engineer') OR EXISTS(SELECT 1 FROM `{$p}fm2_control_engineer_assignments` dce WHERE dce.installation_case_id=c.id AND dce.assignment_sequence=(SELECT MAX(dce2.assignment_sequence) FROM `{$p}fm2_control_engineer_assignments` dce2 WHERE dce2.installation_case_id=c.id) AND dce.engineer_user_id=$actor))";
        $latestApp="app.application_sequence=(SELECT MAX(app2.application_sequence) FROM $apps app2 WHERE app2.installation_case_id=c.id)";$native="EXISTS(SELECT 1 FROM $apps app WHERE app.installation_case_id=c.id AND $latestApp AND JSON_CONTAINS(app.selected_snapshot_json,JSON_OBJECT('tabId',w.installer_tab_id),'$.selectedInstallers')=1)";$legacy="(NOT EXISTS(SELECT 1 FROM $apps any_app WHERE any_app.installation_case_id=c.id) AND EXISTS(SELECT 1 FROM $orders ao JOIN $members oi ON oi.assignment_order_id=ao.id WHERE ao.installation_case_id=c.id AND ao.version_no=(SELECT MAX(ao2.version_no) FROM $orders ao2 WHERE ao2.installation_case_id=c.id) AND ao.status='registered' AND oi.change_action<>'release' AND oi.installer_tab_id=w.installer_tab_id AND oi.valid_from<=$today AND (oi.valid_to IS NULL OR oi.valid_to>=$today)))";$member="($native OR $legacy)";$notPto="NOT EXISTS(SELECT 1 FROM $pto pf WHERE pf.installation_case_id=c.id AND pf.fact_type='pto_act')";
        $current="SELECT COUNT(*) FROM $cases c WHERE c.actual_start_date IS NOT NULL AND $notPto AND $scope AND $member";$assigned="SELECT COUNT(*) FROM $cases c WHERE $notPto AND $scope AND $member";
        $upcoming="SELECT COUNT(*) FROM $cases c JOIN $selections s ON s.installation_case_id=c.id AND s.selection_revision=(SELECT MAX(s2.selection_revision) FROM $selections s2 WHERE s2.installation_case_id=c.id) JOIN $roots r ON r.installation_case_id=c.id AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity AND r.composition_sha256=s.composition_sha256 WHERE c.actual_start_date IS NULL AND $scope AND (($native) OR (NOT EXISTS(SELECT 1 FROM $apps ua WHERE ua.installation_case_id=c.id) AND EXISTS(SELECT 1 FROM $selectionMembers sm WHERE sm.assignment_order_id=s.assignment_order_id AND sm.installer_tab_id=w.installer_tab_id)))";
        $base="SELECT w.installer_tab_id,w.fio,w.employment_status,($current) current_count,($upcoming) upcoming_count,($assigned) assigned_count,w.workforce_source_updated_at,w.reconciliation_state FROM $catalog w";$where=['1=1'];$params=[];if($filters['status']!==''){$where[]='x.employment_status=:status';$params[':status']=$filters['status'];}if($filters['availability']==='assigned')$where[]='x.assigned_count>0';elseif($filters['availability']==='free')$where[]='x.assigned_count=0';if($filters['current']==='present')$where[]='x.current_count>0';elseif($filters['current']==='absent')$where[]='x.current_count=0';if($filters['upcoming']==='present')$where[]='x.upcoming_count>0';elseif($filters['upcoming']==='absent')$where[]='x.upcoming_count=0';if($filters['q']!==''){$search='LOCATE(LOWER(:q),LOWER(x.fio))>0';$params[':q']=$filters['q'];if($filters['tab']!==''){$search.=' OR LOCATE(:tab,CAST(x.installer_tab_id AS CHAR))>0';$params[':tab']=$filters['tab'];}$where[]="($search)";}$predicate=implode(' AND ',$where);
        $summaryRow=$this->db->createCommand("SELECT SUM(CASE WHEN $predicate THEN 1 ELSE 0 END) filtered_total,SUM(x.reconciliation_state='delivered') total,SUM(x.reconciliation_state='delivered' AND x.employment_status='employed') working,SUM(x.reconciliation_state='delivered' AND x.assigned_count>0) assigned,MAX(CASE WHEN x.reconciliation_state='delivered' THEN x.workforce_source_updated_at END) updated_at FROM ($base) x",$params)->queryOne();if(!is_array($summaryRow))throw new \RuntimeException('Directory summary unavailable.');$total=(int)$summaryRow['filtered_total'];$offset=($filters['page']-1)*self::PAGE_SIZE;$ids=array_map('intval',$this->db->createCommand("SELECT x.installer_tab_id FROM ($base) x WHERE $predicate ORDER BY x.fio,x.installer_tab_id LIMIT ".self::PAGE_SIZE." OFFSET ".$offset,$params)->queryColumn());$summaryTotal=(int)$summaryRow['total'];return[$ids,$total,['total'=>$summaryTotal,'working'=>(int)$summaryRow['working'],'dismissed'=>$summaryTotal-(int)$summaryRow['working'],'assigned'=>(int)$summaryRow['assigned'],'updatedAt'=>$summaryRow['updated_at']]];
    }

    private function projection(int $actorId=0,?array$onlyTabs=null): array
    {
        $catalog = $this->processPrefix . 'fm2_workforce_catalog';
        $applications = $this->processPrefix . 'fm2_assignment_order_applications';
        $orders = $this->processPrefix . 'fm2_assignment_orders';
        $members = $this->processPrefix . 'fm2_order_installers';
        $cases = $this->processPrefix . 'fm2_installation_cases';
        $objects = $this->legacyPrefix . 'fm_maintable';
        $edits=$this->processPrefix.'fm2_object_detail_edits';$effectiveReg=MariaDbEffectiveObjectDetails::sqlValue('regnumber','m.regnumber','e');$effectiveAddress=MariaDbEffectiveObjectDetails::sqlValue('address','m.ordadr_address','e');$plannedStart=MariaDbYiiObjectQueue::plannedStartDateExpression('m.workdatestart');
        $actor=(int)$actorId;$scope="(NOT EXISTS(SELECT 1 FROM `{$this->processPrefix}fm2_pilot_user_roles` sur JOIN `{$this->processPrefix}fm2_pilot_roles` sr ON sr.role_id=sur.role_id AND sr.status=1 WHERE sur.user_id=$actor AND BINARY sr.code='construction_control_engineer') OR EXISTS(SELECT 1 FROM `{$this->processPrefix}fm2_control_engineer_assignments` sce WHERE sce.installation_case_id=c.id AND sce.assignment_sequence=(SELECT MAX(sce2.assignment_sequence) FROM `{$this->processPrefix}fm2_control_engineer_assignments` sce2 WHERE sce2.installation_case_id=c.id) AND sce.engineer_user_id=$actor))";

        $workforceQuery=(new Query())->select(['installer_tab_id','fio','position','employment_status','dismissal_effective_at','workforce_source','workforce_source_updated_at','reconciliation_state'])->from($catalog)->orderBy(['fio'=>SORT_ASC,'installer_tab_id'=>SORT_ASC]);if($onlyTabs!==null)$workforceQuery->andWhere(['installer_tab_id'=>array_map('intval',$onlyTabs)]);$workforce=$workforceQuery->all($this->db);
        $latestApplications=(new Query())->select(['installation_case_id','application_sequence'=>new Expression('MAX([[application_sequence]])')])->from($applications)->groupBy('installation_case_id');
        $released="EXISTS(SELECT 1 FROM `{$this->processPrefix}fm2_pilot_completion_facts` release_fact WHERE release_fact.installation_case_id=c.id AND release_fact.fact_type='pto_act')";
        $nativeBase=(new Query())->select(['a.application_id','a.installation_case_id','a.application_sequence','a.object_id','a.assignment_order_id','a.order_version','a.composition_identity','a.composition_sha256','a.control_engineer_user_id','a.selected_snapshot_json','a.document_date','planned_finish'=>new Expression('NULL'),'registration_number'=>new Expression($effectiveReg),'address'=>new Expression($effectiveAddress),'is_active'=>new Expression("NOT($released)"),'is_current'=>new Expression("c.actual_start_date IS NOT NULL AND NOT($released)")])->from(['a'=>$applications])->innerJoin(['la'=>$latestApplications],'la.installation_case_id=a.installation_case_id AND la.application_sequence=a.application_sequence')->innerJoin(['c'=>$cases],'c.id=a.installation_case_id')->innerJoin(['m'=>$objects],'m.id=a.object_id')->leftJoin(['e'=>$edits],'e.object_id=a.object_id')->andWhere(new Expression($scope));$nativeRows=$nativeBase->all($this->db);$nativeApplied=array_values(array_filter($nativeRows,static fn(array$row):bool=>(bool)$row['is_active']));$native=array_values(array_filter($nativeRows,static fn(array$row):bool=>(bool)$row['is_current']));
        $latestOrders = (new Query())->select(['installation_case_id','version_no'=>new Expression('MAX([[version_no]])')])->from($orders)->groupBy('installation_case_id');
        $today=(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d');
        $legacyBase=(new Query())->select(['oi.installer_tab_id','object_id'=>'c.legacy_installation_object_id','registration_number'=>new Expression("CONCAT([[ao.registration_number]], ' · ', $effectiveReg)"),'address'=>new Expression($effectiveAddress),'start'=>'oi.valid_from','end'=>'oi.valid_to','planned_finish'=>'ao.planned_finish_date_snapshot','is_active'=>new Expression("NOT($released)"),'is_current'=>new Expression("c.actual_start_date IS NOT NULL AND NOT($released)")])->from(['oi'=>$members])->innerJoin(['ao'=>$orders],'ao.id=oi.assignment_order_id')->innerJoin(['lo'=>$latestOrders],'lo.installation_case_id=ao.installation_case_id AND lo.version_no=ao.version_no')->innerJoin(['c'=>$cases],'c.id=ao.installation_case_id')->innerJoin(['m'=>$objects],'m.id=c.legacy_installation_object_id')->leftJoin(['e'=>$edits],'e.object_id=c.legacy_installation_object_id')->where(['ao.status'=>'registered'])->andWhere(new Expression($scope))->andWhere(['<>','oi.change_action','release'])->andWhere(['<=','oi.valid_from',$today])->andWhere(['or',['oi.valid_to'=>null],['>=','oi.valid_to',$today]])->andWhere(['not exists',(new Query())->from(['x'=>$applications])->where(new Expression('[[x.installation_case_id]]=[[ao.installation_case_id]]'))]);$legacyRows=$legacyBase->all($this->db);$legacyApplied=array_values(array_filter($legacyRows,static fn(array$row):bool=>(bool)$row['is_active']));$legacy=array_values(array_filter($legacyRows,static fn(array$row):bool=>(bool)$row['is_current']));

        $current = [];
        $selections=$this->processPrefix.'fm2_assignment_order_selections';$selectionMembers=$this->processPrefix.'fm2_assignment_order_selection_members';$roots=$this->processPrefix.'fm2_assignment_order_original_roots';
        $latestSelections=(new Query())->select(['installation_case_id','selection_revision'=>new Expression('MAX([[selection_revision]])')])->from($selections)->groupBy('installation_case_id');
        $upcomingRows=$this->db->createCommand("SELECT sm.installer_tab_id,a.selected_snapshot_json,c.legacy_installation_object_id object_id,CASE WHEN JSON_CONTAINS_PATH(e.values_json,'one','$.regnumber') THEN NULLIF(JSON_UNQUOTE(JSON_EXTRACT(e.values_json,'$.regnumber')),'null') ELSE m.regnumber END registration_number,CASE WHEN JSON_CONTAINS_PATH(e.values_json,'one','$.address') THEN NULLIF(JSON_UNQUOTE(JSON_EXTRACT(e.values_json,'$.address')),'null') ELSE m.ordadr_address END address,($plannedStart) planned_start,(a.application_id IS NOT NULL) is_applied FROM `$selections` s JOIN (SELECT installation_case_id,MAX(selection_revision) selection_revision FROM `$selections` GROUP BY installation_case_id) ls ON ls.installation_case_id=s.installation_case_id AND ls.selection_revision=s.selection_revision JOIN `$selectionMembers` sm ON sm.assignment_order_id=s.assignment_order_id JOIN `$roots` r ON r.installation_case_id=s.installation_case_id AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity AND r.composition_sha256=s.composition_sha256 JOIN `$cases` c ON c.id=s.installation_case_id LEFT JOIN `$applications` a ON a.installation_case_id=c.id AND a.application_sequence=(SELECT MAX(ax.application_sequence) FROM `$applications` ax WHERE ax.installation_case_id=c.id) JOIN `$objects` m ON m.id=c.legacy_installation_object_id LEFT JOIN `$edits` e ON e.object_id=c.legacy_installation_object_id WHERE c.actual_start_date IS NULL AND $scope")->queryAll();
        foreach ($native as $row) {
            $snapshot = $this->nativeSnapshot($row);
            $ids = array_map(static fn(array $installer): int => (int) $installer['tabId'], $snapshot['selectedInstallers']);
            foreach($ids as$tabId)$current[$tabId][]=$this->assignment($row)+['source'=>'native','history_start'=>(string)$row['document_date']];
        }
        foreach ($legacy as $row) {
            $this->validateAssignment($row);
            $tabId = (int) $row['installer_tab_id'];
            $assignment = ['object_id'=>(int)$row['object_id'],'registration_number'=>(string)$row['registration_number'],'address'=>(string)$row['address'],'planned_finish'=>$this->dateOrNull($row['planned_finish'] ?? null),'source'=>'legacy','history_start'=>(string)$row['start']];
            $current[$tabId][] = $assignment;
        }
        $applied=[];foreach($nativeApplied as$row){$snapshot=$this->nativeSnapshot($row);foreach($snapshot['selectedInstallers']as$installer)$applied[(int)$installer['tabId']][]=$this->assignment($row)+['source'=>'native','history_start'=>(string)$row['document_date']];}
        foreach($legacyApplied as$row){$this->validateAssignment($row);$applied[(int)$row['installer_tab_id']][]=['object_id'=>(int)$row['object_id'],'registration_number'=>(string)$row['registration_number'],'address'=>(string)$row['address'],'planned_finish'=>$this->dateOrNull($row['planned_finish']??null),'source'=>'legacy','history_start'=>(string)$row['start']];}
        foreach($applied as&$items)usort($items,static fn(array$a,array$b):int=>[$a['object_id'],$a['history_start']]<=>[$b['object_id'],$b['history_start']]);unset($items);
        $upcoming=[];$appliedCases=[];$addUpcoming=function(int$tabId,array$row)use(&$upcoming):void{$upcoming[$tabId][]=['object_id'=>(int)$row['object_id'],'registration_number'=>(string)$row['registration_number'],'registrationNumber'=>(string)$row['registration_number'],'address'=>(string)$row['address'],'planned_start'=>$this->dateOrNull($row['planned_start']??null),'plannedStartDate'=>$this->dateOrNull($row['planned_start']??null),'is_applied'=>(bool)$row['is_applied']];};
        foreach($upcomingRows as$row){if($row['selected_snapshot_json']!==null){$object=(int)$row['object_id'];if(isset($appliedCases[$object]))continue;$appliedCases[$object]=true;$snapshot=json_decode((string)$row['selected_snapshot_json'],true,512,JSON_THROW_ON_ERROR);if(!is_array($snapshot)||array_keys($snapshot)!==['selectedInstallers','selectedEngineer']||!is_array($snapshot['selectedInstallers']))throw new \RuntimeException('Utilization projection unavailable.');foreach($snapshot['selectedInstallers']as$installer)$addUpcoming((int)($installer['tabId']??0),$row);}else $addUpcoming((int)$row['installer_tab_id'],$row);}

        $summary = ['total'=>0,'free'=>0,'assigned'=>0,'overloaded'=>0,'unknown'=>0];
        $installers = [];$directory=[];$directorySummary=['total'=>0,'working'=>0,'dismissed'=>0,'assigned'=>0,'updatedAt'=>null];
        $latestUpdate = null;$sources=[];
        foreach ($workforce as $row) {
            $this->validateWorkforce($row);
            $tabId = (int) $row['installer_tab_id'];
            $assignments = $current[$tabId] ?? [];
            $load = $row['reconciliation_state'] !== 'delivered' ? 'unknown' : (count($assignments) === 0 ? 'free' : (count($assignments) === 1 ? 'assigned' : 'overloaded'));
            $release = 'now';
            if ($assignments !== []) {
                $finishes = array_column($assignments, 'planned_finish');
                $release = in_array(null, $finishes, true) ? null : max($finishes);
            }
            $enriched=$row+['installer_tab_id'=>$tabId,'load'=>$load,'assignments'=>$applied[$tabId]??[],'currentWorks'=>$assignments,'currentWorkCount'=>count($assignments),'upcomingAssignments'=>$upcoming[$tabId]??[],'planned_release'=>$release];$directory[]=$enriched;
            if($row['reconciliation_state']==='delivered'){$directorySummary['total']++;$directorySummary[$row['employment_status']==='employed'?'working':'dismissed']++;if(($applied[$tabId]??[])!==[])$directorySummary['assigned']++;$directorySummary['updatedAt']=$directorySummary['updatedAt']===null||$row['workforce_source_updated_at']>$directorySummary['updatedAt']?$row['workforce_source_updated_at']:$directorySummary['updatedAt'];}
            if($row['employment_status']!=='employed')continue;
            $summary['total']++; $summary[$load]++;
            $sources[(string)$row['workforce_source']]=true;
            $latestUpdate = $latestUpdate === null || $row['workforce_source_updated_at'] > $latestUpdate ? $row['workforce_source_updated_at'] : $latestUpdate;
            $installers[] = $enriched;
        }
        return ['summary'=>$summary,'installers'=>$installers,'directory'=>$directory,'directorySummary'=>$directorySummary,'sources'=>array_keys($sources),'updatedAt'=>$latestUpdate];
    }

    private function historyPage(int $tabId,int $page,int$actorId=0):array
    {
        $p=$this->processPrefix;$l=$this->legacyPrefix;$applications=$p.'fm2_assignment_order_applications';$orders=$p.'fm2_assignment_orders';$members=$p.'fm2_order_installers';$cases=$p.'fm2_installation_cases';$objects=$l.'fm_maintable';$edits=$p.'fm2_object_detail_edits';
        $historyReg="CASE WHEN JSON_CONTAINS_PATH(e.values_json,'one','$.regnumber') THEN NULLIF(JSON_UNQUOTE(JSON_EXTRACT(e.values_json,'$.regnumber')),'null') ELSE m.regnumber END";$historyAddress="CASE WHEN JSON_CONTAINS_PATH(e.values_json,'one','$.address') THEN NULLIF(JSON_UNQUOTE(JSON_EXTRACT(e.values_json,'$.address')),'null') ELSE m.ordadr_address END";
        $candidate=$this->db->quoteValue(json_encode(['tabId'=>$tabId],JSON_THROW_ON_ERROR));$today=$this->db->quoteValue((new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'));
        $contains="JSON_CONTAINS(a.selected_snapshot_json,$candidate,'$.selectedInstallers')=1";
        $actor=(int)$actorId;$role="NOT EXISTS(SELECT 1 FROM `{$p}fm2_pilot_user_roles` hur JOIN `{$p}fm2_pilot_roles` hr ON hr.role_id=hur.role_id AND hr.status=1 WHERE hur.user_id=$actor AND BINARY hr.code='construction_control_engineer')";$owned=static fn(string$alias):string=>"EXISTS(SELECT 1 FROM `{$p}fm2_control_engineer_assignments` hce WHERE hce.installation_case_id=$alias.id AND hce.assignment_sequence=(SELECT MAX(hce2.assignment_sequence) FROM `{$p}fm2_control_engineer_assignments` hce2 WHERE hce2.installation_case_id=$alias.id) AND hce.engineer_user_id=$actor)";$ownedNative=$owned('hc');$ownedLegacy=$owned('c');
        $previous="SELECT MAX(p.application_sequence) FROM `$applications` p WHERE p.installation_case_id=a.installation_case_id AND p.application_sequence<a.application_sequence";
        $replacementEnd="(SELECT n.document_date FROM `$applications` n WHERE n.installation_case_id=a.installation_case_id AND n.application_sequence>a.application_sequence AND JSON_CONTAINS(n.selected_snapshot_json,$candidate,'$.selectedInstallers')=0 ORDER BY n.application_sequence ASC LIMIT 1)";$ptoEnd="(SELECT MIN(pf.fact_date) FROM `{$p}fm2_pilot_completion_facts` pf WHERE pf.installation_case_id=a.installation_case_id AND pf.fact_type='pto_act' AND pf.fact_date>=a.document_date)";$end="COALESCE(LEAST($replacementEnd,$ptoEnd),$replacementEnd,$ptoEnd)";
        $legacyPtoEnd="(SELECT MIN(lpf.fact_date) FROM `{$p}fm2_pilot_completion_facts` lpf WHERE lpf.installation_case_id=c.id AND lpf.fact_type='pto_act' AND lpf.fact_date>=oi.valid_from)";$legacyEnd="COALESCE(LEAST(oi.valid_to,$legacyPtoEnd),oi.valid_to,$legacyPtoEnd)";
        $native="SELECT a.application_sequence,a.installation_case_id,a.object_id,a.document_date start,$end end,CONVERT(($historyReg) USING utf8mb4) COLLATE utf8mb4_unicode_ci registration_number,CONVERT(($historyAddress) USING utf8mb4) COLLATE utf8mb4_unicode_ci address,o.planned_finish_date_snapshot planned_finish FROM `$applications` a JOIN `$orders` o ON o.id=a.assignment_order_id JOIN `$cases` hc ON hc.id=a.installation_case_id JOIN `$objects` m ON m.id=a.object_id LEFT JOIN `$edits` e ON e.object_id=a.object_id WHERE ($role OR $ownedNative) AND $contains AND NOT EXISTS(SELECT 1 FROM `$applications` pp WHERE pp.installation_case_id=a.installation_case_id AND pp.application_sequence=($previous) AND JSON_CONTAINS(pp.selected_snapshot_json,$candidate,'$.selectedInstallers')=1)";
        $legacy="SELECT 0 application_sequence,c.id installation_case_id,c.legacy_installation_object_id object_id,oi.valid_from start,$legacyEnd end,CONVERT(CONCAT(ao.registration_number,' · ',($historyReg)) USING utf8mb4) COLLATE utf8mb4_unicode_ci registration_number,CONVERT(($historyAddress) USING utf8mb4) COLLATE utf8mb4_unicode_ci address,ao.planned_finish_date_snapshot planned_finish FROM `$members` oi JOIN `$orders` ao ON ao.id=oi.assignment_order_id JOIN `$cases` c ON c.id=ao.installation_case_id JOIN `$objects` m ON m.id=c.legacy_installation_object_id LEFT JOIN `$edits` e ON e.object_id=c.legacy_installation_object_id WHERE ($role OR $ownedLegacy) AND oi.installer_tab_id=".(int)$tabId." AND ao.status='registered' AND oi.change_action<>'release' AND oi.valid_from<=$today AND ao.version_no=(SELECT MAX(lo.version_no) FROM `$orders` lo WHERE lo.installation_case_id=ao.installation_case_id) AND NOT EXISTS(SELECT 1 FROM `$applications` x WHERE x.installation_case_id=ao.installation_case_id)";
        $combined="$native UNION ALL $legacy";$total=(int)$this->db->createCommand("SELECT COUNT(*) FROM ($combined) combined_history")->queryScalar();if($page>max(1,(int)ceil($total/self::PAGE_SIZE)))throw new \OutOfRangeException('History page unavailable.');$offset=($page-1)*self::PAGE_SIZE;
        $rows=$this->db->createCommand("SELECT * FROM ($combined) combined_history ORDER BY start DESC,object_id DESC,application_sequence DESC LIMIT ".self::PAGE_SIZE." OFFSET $offset")->queryAll();
        $periods=[];foreach($rows as$row){$this->validateAssignment($row);$periods[]=$this->assignment($row)+['start'=>(string)$row['start'],'end'=>$this->dateOrNull($row['end']??null),'sequence'=>(int)$row['application_sequence'],'case_id'=>(int)$row['installation_case_id']];}return[$periods,$total];
    }

    private function nativeSnapshot(array $row): array
    {
        try {$snapshot = json_decode((string) ($row['selected_snapshot_json'] ?? ''), true, 512, JSON_THROW_ON_ERROR);}
        catch (\Throwable) {throw new \RuntimeException('Utilization projection unavailable.');}
        if ((int)($row['application_sequence']??0)<1||!is_array($snapshot)||array_keys($snapshot)!==['selectedInstallers','selectedEngineer']) throw new \RuntimeException('Utilization projection unavailable.');
        try {YiiObjectCardApplicationIntegrity::validateComposition($row, $snapshot);} catch (\Throwable) {throw new \RuntimeException('Utilization projection unavailable.');}
        $this->validateAssignment($row);
        return $snapshot;
    }

    private function assignment(array $row): array {return ['object_id'=>(int)$row['object_id'],'registration_number'=>(string)$row['registration_number'],'address'=>(string)$row['address'],'planned_finish'=>$this->dateOrNull($row['planned_finish']??null)];}
    private function validateWorkforce(array $row): void {if ((int)($row['installer_tab_id']??0)<1 || trim((string)($row['fio']??''))==='' || trim((string)($row['position']??''))==='' || trim((string)($row['workforce_source']??''))==='' || !in_array($row['employment_status']??null,['employed','dismissed'],true) || (($row['dismissal_effective_at']??null)!==null&&!$this->date((string)$row['dismissal_effective_at'])) || !in_array($row['reconciliation_state']??null,['delivered','missing_from_delivery'],true) || !$this->timestamp((string)($row['workforce_source_updated_at']??''))) throw new \RuntimeException('Utilization workforce unavailable.');}
    private function validateAssignment(array $row): void {if ((int)($row['object_id']??0)<1 || trim((string)($row['registration_number']??''))==='' || trim((string)($row['address']??''))==='' || !$this->date((string)($row['document_date']??$row['start']??''))) throw new \RuntimeException('Utilization assignment unavailable.');}
    private function dateOrNull(mixed $value): ?string {if ($value===null || $value==='') return null; if (!$this->date((string)$value)) throw new \RuntimeException('Utilization date unavailable.'); return (string)$value;}
    private function date(string $value): bool {$date=\DateTimeImmutable::createFromFormat('!Y-m-d',$value,new \DateTimeZone('Europe/Moscow'));return $date!==false&&$date->format('Y-m-d')===$value;}
    private function timestamp(string $value): bool {try {new \DateTimeImmutable($value); return $value!=='';} catch (\Throwable) {return false;}}
}
