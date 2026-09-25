<?php

declare(strict_types=1);

namespace FMonitor2\InstallationProcess;

use yii\db\Connection;

final readonly class MariaDbYiiObjectQueue
{
    private const CALENDAR_ROW_LIMIT = 5000;

    public function __construct(private Connection$db, private string$prefix, private string$legacyPrefix, private MariaDbYiiObjectQueueProjection $projection)
    {
        foreach ([$prefix,$legacyPrefix] as $p) {
            if (strlen($p) > 28 || preg_match('/^[A-Za-z0-9_]*$/D', $p) !== 1) {
                throw new \InvalidArgumentException();
            }
        }
    }
    public function authorized(int$actor): bool
    {
        $p = $this->prefix;
        $sql = "SELECT 1 FROM `{$p}fm2_pilot_users` u JOIN `{$p}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id JOIN `{$p}fm2_pilot_role_permissions` rp ON rp.role_id=r.role_id WHERE u.user_id=:id AND u.status=1 AND u.activation_state='active' AND r.status=1 AND BINARY rp.permission='objects.read' LIMIT 1";
        return(bool)$this->db->createCommand($sql, [':id' => $actor])->queryScalar();
    }
    /** @return list<array{scheduleId:?int,objectId:int,date:string,type:string,registration:string,address:string,entrance:string}> */
    public function readCalendar(int|string $actor,string $today,string $first='', string $last=''): array
    {
        if(is_string($actor)){$last=$today;$first=$actor;$today=$first;$actor=0;}
        $table = $this->prefix . InspectionPlanningDefinitionSchemaMigration::SCHEDULES;
        $columns = $this->db->createCommand('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table ORDER BY ORDINAL_POSITION', [':table'=>$table])->queryColumn();
        if ($columns !== ['id','installation_case_id','legacy_object_id','control_engineer_user_id','inspection_date','scheduled_by_user_id','scheduled_at']) {
            throw new \RuntimeException('Inspection planning schema is not ready.');
        }
        $p=$this->prefix;$l=$this->legacyPrefix;
        if((int)$this->db->createCommand("SELECT COUNT(*) FROM `{$p}fm2_pilot_inspection_schedules`")->queryScalar()>self::CALENDAR_ROW_LIMIT)throw new \RuntimeException('Calendar source projection overflow.');
        $effective=static fn(string$field,string$column):string=>MariaDbEffectiveObjectDetails::sqlValue($field,'m.'.$column);
        $current=MariaDbYiiInspectionPlanning::currentProjectionSql($p,':today');$scope=$actor===0?'1=1':MariaDbYiiInspectionPlanning::actorScopeSql($p,':actor','current.installation_case_id');
        $scopeParams=[':today'=>$today]+($actor===0?[]:[':actor'=>$actor]);if((int)$this->db->createCommand("SELECT MAX(inspection_count) FROM ($current) current WHERE $scope",$scopeParams)->queryScalar()>1)throw new \RuntimeException('Multiple current inspection plans.');
        $rows=$this->db->createCommand("SELECT current.schedule_id,current.object_id,current.inspection_date event_date,'inspection' event_type,current.inspection_count,".$effective('regnumber','regnumber')." regnumber,".$effective('address','ordadr_address')." ordadr_address,".$effective('entrance','entrance')." entrance FROM ($current) current LEFT JOIN `{$l}fm_maintable` m ON m.id=current.object_id LEFT JOIN `{$p}fm2_object_detail_edits` e ON e.object_id=current.object_id WHERE $scope AND current.inspection_date BETWEEN :first AND :last ORDER BY current.inspection_date,current.object_id,current.schedule_id LIMIT ".(self::CALENDAR_ROW_LIMIT+1),$scopeParams+[':first'=>$first,':last'=>$last])->queryAll();
        foreach($rows as$row)if((int)$row['inspection_count']!==1)throw new \RuntimeException('Multiple current inspection plans.');
        if(count($rows)>self::CALENDAR_ROW_LIMIT)throw new \RuntimeException('Calendar source projection overflow.');
        $legacyTable=$l.'fm_maintable';
        $legacyColumns=$this->db->createCommand('SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table',[':table'=>$legacyTable])->queryColumn();
        $finishColumn=in_array('workdatefinish',$legacyColumns,true)?'m.workdatefinish':'NULL';
        $finishExpression="COALESCE(NULLIF({$finishColumn},''),m.plan_finish_date)";
        $plannedScope=$actor===0?'1=1':'('.MariaDbYiiInspectionPlanning::actorGlobalScopeSql($p,':actor')." OR EXISTS(SELECT 1 FROM `{$p}fm2_installation_cases` scope_case WHERE scope_case.legacy_installation_object_id=m.id AND ".MariaDbYiiInspectionPlanning::actorAssignedScopeSql($p,':actor','scope_case.id').'))';$plannedParams=($actor===0?[]:[':actor'=>$actor])+[':first'=>$first,':last'=>$last];$planned=$this->db->createCommand("SELECT m.id object_id,m.workdatestart,{$finishColumn} workdatefinish,m.plan_finish_date,".$effective('regnumber','regnumber')." regnumber,".$effective('address','ordadr_address')." ordadr_address,".$effective('entrance','entrance')." entrance FROM `{$legacyTable}` m LEFT JOIN `{$p}fm2_object_detail_edits` e ON e.object_id=m.id WHERE $plannedScope AND ((LEFT(m.workdatestart,10) BETWEEN :first AND :last) OR (LEFT({$finishExpression},10) BETWEEN :first AND :last)) LIMIT ".(self::CALENDAR_ROW_LIMIT+1),$plannedParams)->queryAll();
        if(count($planned)>self::CALENDAR_ROW_LIMIT)throw new \RuntimeException('Calendar source projection overflow.');
        foreach($planned as$row){$start=self::date($row['workdatestart']);$finish=self::date($row['workdatefinish'])??self::date($row['plan_finish_date']);foreach([['planned_start',$start],['planned_end',$finish]]as[$type,$date])if($date!==null&&$date>=$first&&$date<=$last)$rows[]=['schedule_id'=>null,'object_id'=>$row['object_id'],'event_date'=>$date,'event_type'=>$type,'regnumber'=>$row['regnumber'],'ordadr_address'=>$row['ordadr_address'],'entrance'=>$row['entrance']];}
        if(count($rows)>self::CALENDAR_ROW_LIMIT)throw new \RuntimeException('Calendar source projection overflow.');
        usort($rows,static fn(array$a,array$b):int=>[$a['event_date'],(int)$a['object_id'],(int)($a['schedule_id']??0)]<=>[$b['event_date'],(int)$b['object_id'],(int)($b['schedule_id']??0)]);
        return array_map(static fn(array$row):array=>['scheduleId'=>$row['schedule_id']===null?null:(int)$row['schedule_id'],'objectId'=>(int)$row['object_id'],'date'=>(string)$row['event_date'],'type'=>(string)$row['event_type'],'registration'=>trim((string)$row['regnumber']),'address'=>trim((string)$row['ordadr_address']),'entrance'=>trim((string)$row['entrance'])],$rows);
    }
    public function read(int$actor, string$q, string$status, int$page, int$size = 50, string $chart = '', string $bucket = '', ?string $cutoff = null): array
    {
        $allowed = ['','needs_assignment_order','ready_to_open','installation','document_closeout','completed','needs_assignment_change'];
        if (mb_strlen($q) > 120 || $page < 1 || !in_array($status, $allowed, true)) {
            throw new \RuntimeException('Invalid queue filter.');
        }
        if (!MariaDbYiiSchemaFingerprint::queueFamiliesReady($this->db, $this->prefix)) {
            throw new \RuntimeException('Queue schema unavailable.');
        }
        [$from,$params] = $this->query($q, $status, $chart, $bucket, $cutoff);
        $total = (int)$this->db->createCommand('SELECT COUNT(*)' . $from, $params)->queryScalar();
        $pages = max(1, (int)ceil($total / $size));
        if ($page > $pages) {
            throw new \RuntimeException('Page unavailable.');
        }
        $offset = ($page - 1) * $size;
        $effective=static fn(string$field,string$column):string=>MariaDbEffectiveObjectDetails::sqlValue($field,'l.'.$column);$columns = "c.id case_id,c.legacy_installation_object_id,c.process_state,c.actual_start_date,c.opened_at,c.opened_by_user_id,".$effective('address','ordadr_address')." ordadr_address,".$effective('entrance','entrance')." entrance,".$effective('regnumber','regnumber')." regnumber,".$effective('zavnumber','zavnumber')." zavnumber,l.workdatestart,l.workdateendadjusted,l.plan_finish_date,o.status order_status,a.application_id,s.assignment_order_id selection_order_id,v.revision_id original_revision_id";
        $rows = $this->db->createCommand("SELECT {$columns}{$from} ORDER BY l.workdatestart IS NULL,LEFT(l.workdatestart,10),c.legacy_installation_object_id LIMIT {$size} OFFSET {$offset}", $params)->queryAll();
        $objects = [];
        foreach ($rows as $row) {
            $objects[] = $this->map($row, $page, $pages, $total);
        }
        $objects = $this->projection->decorate($objects, $actor);
        $objects = $this->engineers($objects);
        $filters=['q'=>$q,'status'=>$status,'page'=>$page,'pages'=>$pages,'total'=>$total];
        if($chart!=='')$filters+=['chart'=>$chart,'bucket'=>$bucket,'cutoff'=>$cutoff];
        return['objects'=>$objects,'filters'=>$filters];
    }
    private function query(string$q, string$status, string $chart, string $bucket, ?string $cutoff): array
    {
        $p = $this->prefix;
        $l = $this->legacyPrefix;
        $count = self::currentChecklistCompletionCount($p);
        $pto = "EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='pto_act')";
        $dec = "EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='declaration')";
        $active = "c.process_state IN('working','completed','needs_assignment_change') AND (a.application_id IS NOT NULL OR o.status IN('prepared','registered'))";
        $ready = "(v.revision_id IS NOT NULL OR (s.assignment_order_id IS NULL AND (a.application_id IS NOT NULL OR COALESCE(o.status='registered',0))))";
        $filter = match ($status) {
            'needs_assignment_order' => "c.process_state IN('needs_assignment_order','assignment_order_prepared')"
                . " AND NOT {$ready} AND (s.assignment_order_id IS NOT NULL"
                . " OR (c.process_state='needs_assignment_order' AND o.id IS NULL AND a.application_id IS NULL)"
                . " OR (c.process_state='assignment_order_prepared' AND o.status='prepared' AND a.application_id IS NULL))",
            'ready_to_open' => "c.process_state IN('needs_assignment_order','assignment_order_prepared') AND {$ready}",
            'installation' => "{$active} AND {$count}<41 AND NOT({$pto} AND {$dec})",
            'document_closeout' => "{$active} AND {$count}=41 AND NOT({$pto} AND {$dec})",
            'completed' => "{$active} AND {$pto} AND {$dec}",
            'needs_assignment_change' => "c.process_state='needs_assignment_change'"
                . " AND (a.application_id IS NOT NULL OR o.status='registered')",
            default => '1=1',
        };
        $params = [];
        if ($chart !== '') {
            $stages = self::stagePredicates($p);
            if ($status !== '' || $cutoff === null) throw new \RuntimeException('Invalid chart filter.');
            if ($chart === 'stage' && isset($stages[$bucket])) $filter = $stages[$bucket];
            elseif (in_array($chart, ['planned-start','planned-finish'], true) && preg_match('/^[0-5]$/D',$bucket)===1) {
                $monday=(new \DateTimeImmutable($cutoff,new \DateTimeZone('Europe/Moscow')))->modify('monday this week')->modify('+'.(int)$bucket.' weeks');
                $params[':chartFrom']=$monday->format('Y-m-d');$params[':chartTo']=$monday->modify('+6 days')->format('Y-m-d');
                $expr=$chart==='planned-start'?self::plannedStartDateExpression('l.workdatestart'):"COALESCE(dc.new_deadline,NULLIF(LEFT(l.workdateendadjusted,10),''),LEFT(l.plan_finish_date,10))";
                $filter="{$expr} BETWEEN :chartFrom AND :chartTo";
            } elseif ($chart === 'start-risk') {
                $start = self::plannedStartDateExpression('l.workdatestart');
                $risks = self::startRiskPredicates($stages, $start);
                if (!isset($risks[$bucket])) throw new \RuntimeException('Invalid chart filter.');
                $filter = $risks[$bucket];
                $params = array_filter(
                    self::startRiskParams($cutoff),
                    static fn(string $value, string $name): bool => str_contains($filter, $name),
                    ARRAY_FILTER_USE_BOTH,
                );
            } else throw new \RuntimeException('Invalid chart filter.');
        }
        $source = "(m.category='native_candidate' OR (m.output_id IS NULL AND d.object_id=c.legacy_installation_object_id AND d.content_sha256=SHA2(d.payload_json,256)))";
        $where = "{$source} AND ({$filter})";
        if ($q !== '') {
            $value=static fn(string$field,string$column):string=>MariaDbEffectiveObjectDetails::sqlValue($field,'l.'.$column);$where .= " AND (CAST(c.legacy_installation_object_id AS CHAR) LIKE :q ESCAPE '\\\\' OR ".$value('regnumber','regnumber')." LIKE :q ESCAPE '\\\\' OR ".$value('zavnumber','zavnumber')." LIKE :q ESCAPE '\\\\' OR ".$value('address','ordadr_address')." LIKE :q ESCAPE '\\\\' OR ".$value('entrance','entrance')." LIKE :q ESCAPE '\\\\')";
            $params[':q'] = '%' . str_replace(['\\','%','_'], ['\\\\','\\%','\\_'], $q) . '%';
        }
        $from = " FROM `{$p}fm2_installation_cases` c"
            . " JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id"
            . " LEFT JOIN `{$p}fm2_migration_classification_provenance` m"
            . " ON m.output_kind='operational_case' AND m.output_id=c.id"
            . ' AND m.legacy_object_id=c.legacy_installation_object_id'
            . " LEFT JOIN `{$p}fm2_pilot_object_details` d ON d.object_id=c.legacy_installation_object_id"
            . " LEFT JOIN `{$p}fm2_object_detail_edits` e ON e.object_id=c.legacy_installation_object_id"
            . " LEFT JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id"
            . " AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x"
            . ' WHERE x.installation_case_id=c.id)'
            . " LEFT JOIN `{$p}fm2_assignment_order_applications` a ON a.application_id="
            . "(SELECT x.application_id FROM `{$p}fm2_assignment_order_applications` x"
            . ' WHERE x.installation_case_id=c.id ORDER BY x.application_sequence DESC LIMIT 1)'
            . " LEFT JOIN `{$p}fm2_assignment_order_selections` s ON s.installation_case_id=c.id"
            . " AND s.selection_revision=(SELECT MAX(x.selection_revision) FROM `{$p}fm2_assignment_order_selections` x"
            . ' WHERE x.installation_case_id=c.id)'
            . " LEFT JOIN `{$p}fm2_assignment_order_original_roots` r ON r.installation_case_id=c.id"
            . ' AND r.assignment_order_id=s.assignment_order_id AND r.composition_identity=s.composition_identity'
            . ' AND r.composition_sha256=s.composition_sha256'
            . " LEFT JOIN `{$p}fm2_assignment_order_original_revisions` v ON v.root_original_id=r.root_original_id"
            . " AND v.revision_id=r.current_revision_id LEFT JOIN `{$p}fm2_deadline_certificate_roots` dr ON dr.installation_case_id=c.id LEFT JOIN `{$p}fm2_deadline_certificate_revisions` dc ON dc.id=dr.current_revision_id WHERE {$where}";
        return[$from,$params];
    }
    public static function stagePredicates(string $p): array
    {
        $count=self::currentChecklistCompletionCount($p);$pto="EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='pto_act')";$dec="EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` cf WHERE cf.installation_case_id=c.id AND cf.fact_type='declaration')";$applied="(a.application_id IS NOT NULL OR o.status IN('prepared','registered'))";$ready="(v.revision_id IS NOT NULL OR (s.assignment_order_id IS NULL AND (a.application_id IS NOT NULL OR COALESCE(o.status='registered',0))))";
        return ['needs_assignment_order'=>"c.process_state IN('needs_assignment_order','assignment_order_prepared') AND NOT {$ready} AND (s.assignment_order_id IS NOT NULL OR (c.process_state='needs_assignment_order' AND o.id IS NULL AND a.application_id IS NULL) OR (c.process_state='assignment_order_prepared' AND o.status='prepared' AND a.application_id IS NULL))",'ready_to_open'=>"c.process_state IN('needs_assignment_order','assignment_order_prepared') AND {$ready}",'installation'=>"c.process_state='working' AND {$applied} AND {$count}<41 AND NOT({$pto} AND {$dec})",'document_closeout'=>"c.process_state='working' AND {$applied} AND {$count}=41 AND NOT({$pto} AND {$dec})",'completed'=>"c.process_state IN('working','completed') AND {$applied} AND {$pto} AND {$dec}",'needs_assignment_change'=>"c.process_state='needs_assignment_change' AND (a.application_id IS NOT NULL OR o.status='registered')"];
    }
    public static function currentChecklistCompletionCount(string $p): string
    {
        return "(SELECT COUNT(*) FROM `{$p}fm2_checklist_operations` co"
            . " WHERE co.installation_case_id=c.id AND co.operation_type='item_completed' AND co.item_id<>42"
            . " AND NOT EXISTS(SELECT 1 FROM `{$p}fm2_checklist_operations` later"
            . " WHERE later.installation_case_id=co.installation_case_id AND later.item_id=co.item_id"
            . " AND later.operation_type IN('item_completed','completion_retracted')"
            . " AND (COALESCE(later.accepted_revision,-1)>COALESCE(co.accepted_revision,-1)"
            . " OR (COALESCE(later.accepted_revision,-1)=COALESCE(co.accepted_revision,-1) AND later.id>co.id))))";
    }
    /** @param array<string,string> $stages @return array<string,string> */
    public static function startRiskPredicates(array $stages, string $start): array
    {
        $needsOrder = $stages['needs_assignment_order'];
        $ready = $stages['ready_to_open'];
        $opened = '(' . $stages['installation'] . ' OR ' . $stages['document_closeout'] . ')';
        return [
            'overdue_start' => "({$needsOrder} OR {$ready}) AND {$start}<:riskCutoff",
            'order_0_7' => "{$needsOrder} AND {$start} BETWEEN :riskCutoff AND :riskDay6",
            'order_8_14' => "{$needsOrder} AND {$start} BETWEEN :riskDay7 AND :riskDay13",
            'ready_0_14' => "{$ready} AND {$start} BETWEEN :riskCutoff AND :riskDay13",
            'opened_0_14' => "{$opened} AND {$start} BETWEEN :riskCutoff AND :riskDay13",
        ];
    }
    public static function plannedStartDateExpression(string $column): string
    {
        $date = "LEFT({$column},10)";
        $month = "SUBSTRING({$date},6,2)";
        $day = "CAST(SUBSTRING({$date},9,2) AS UNSIGNED)";
        $lastDay = "DAY(LAST_DAY(CONCAT(LEFT({$date},7),'-01')))";
        return "CASE WHEN {$date} REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'"
            . " AND LEFT({$date},4)<>'0000'"
            . " AND {$month} BETWEEN '01' AND '12'"
            . " AND {$day} BETWEEN 1 AND {$lastDay}"
            . " THEN {$date} ELSE NULL END";
    }
    /** @return array<string,string> */
    public static function startRiskParams(string $cutoff): array
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $cutoff, new \DateTimeZone('Europe/Moscow'));
        if ($date === false || $date->format('Y-m-d') !== $cutoff) throw new \RuntimeException('Invalid chart cutoff.');
        return [':riskCutoff'=>$cutoff, ':riskDay6'=>$date->modify('+6 days')->format('Y-m-d'), ':riskDay7'=>$date->modify('+7 days')->format('Y-m-d'), ':riskDay13'=>$date->modify('+13 days')->format('Y-m-d')];
    }
    private function map(array$r, int$page, int$pages, int$total): array
    {
        $status=InstallationCaseCurrentStatus::project($r);$opened=$status['opened'];$label=$status['label'];
        $id = (int)$r['legacy_installation_object_id'];
        if ($id < 1 || trim((string)$r['ordadr_address']) === ''
            || trim((string)$r['entrance']) === '') {
            throw new \RuntimeException('Malformed queue row.');
        }
        $start = self::date($r['workdatestart']);
        $finish = self::date($r['workdateendadjusted']) ?? self::date($r['plan_finish_date']);
        return [
            'id'=>$id, 'caseId'=>(int)$r['case_id'], 'registrationNumber'=>trim((string)($r['regnumber']??'')),
            'factoryNumber'=>trim((string)($r['zavnumber']??'')),
            'address'=>$r['ordadr_address'], 'entrance'=>$r['entrance'],
            'plannedStartDate'=>$start ?? 'Не указано', 'plannedFinishDate'=>$finish ?? 'Не указано',
            'planningDatesUnknownAtCutover'=>$start === null || $finish === null,
            'dataOrigin'=>'migration_native', 'status'=>$label,
            'nextStep'=>'Откройте карточку объекта монтажа',
            'controlEngineer'=>null,
            '_pagination'=>compact('page', 'pages', 'total'),
        ];
    }
    private function engineers(array $objects):array
    {
        if($objects===[])return$objects;
        $cases=array_values(array_unique(array_map(static fn(array$o):int=>(int)$o['caseId'],$objects)));$params=[];$marks=[];foreach($cases as$index=>$case){$name=':case'.$index;$marks[]=$name;$params[$name]=$case;}$in=implode(',',$marks);
        try{$rows=$this->db->createCommand("SELECT a.installation_case_id,a.engineer_user_id,u.full_name,u.activation_state FROM `{$this->prefix}fm2_control_engineer_assignments` a JOIN `{$this->prefix}fm2_pilot_users` u ON u.user_id=a.engineer_user_id WHERE a.installation_case_id IN ({$in}) AND a.assignment_sequence=(SELECT MAX(x.assignment_sequence) FROM `{$this->prefix}fm2_control_engineer_assignments` x WHERE x.installation_case_id=a.installation_case_id)",$params)->queryAll();}
        catch(\Throwable){return$objects;}
        $byCase=[];foreach($rows as$row){$case=(int)$row['installation_case_id'];if(isset($byCase[$case])||(int)$row['engineer_user_id']<1||trim((string)$row['full_name'])==='')return$objects;$byCase[$case]=['userId'=>(int)$row['engineer_user_id'],'fullName'=>(string)$row['full_name'],'status'=>self::engineerStatus((string)$row['activation_state'])];}
        foreach($objects as&$object)$object['controlEngineer']=$byCase[(int)$object['caseId']]??null;unset($object);return$objects;
    }
    private static function engineerStatus(string $state):string{return match($state){'pending_invitation'=>'Ожидает приглашения','invited'=>'Приглашён','active'=>'Активен',default=>'Недоступен'};}
    private static function date(mixed$v): ?string
    {
        $v = trim((string)$v);
        return preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:\D|$)/D', $v, $m) === 1 && $m[1] !== '0000' && checkdate((int)$m[2], (int)$m[3], (int)$m[1]) ? $m[1] . '-' . $m[2] . '-' . $m[3] : null;
    }
}
