<?php

declare(strict_types=1);

require_once __DIR__ . '/NativePremiumNorms.php';

/** Read model for the native operational calculation boundary. It never reads legacy
 * active/historical migration payloads and never invents a missing operand. */
final class NativeOperationalPremiumInputs
{
    public function __construct(private mysqli $db, private string $prefix, private ?string $legacyPrefix=null) {$this->legacyPrefix ??= $prefix;}

    public function forDate(string $reportDate): array
    {
        $p=$this->prefix;$l=$this->legacyPrefix;
        $sql="SELECT c.id case_id,c.legacy_installation_object_id object_id,c.actual_start_date,l.regnumber,l.ordadr_address address,
                     o.id order_id,o.order_date,o.planned_finish_date_snapshot deadline_date,o.pto_act_date_snapshot completion_date
              FROM `{$p}fm2_installation_cases` c
              JOIN `{$p}fm2_migration_classification_provenance` mp ON mp.output_kind='operational_case' AND mp.output_id=c.id AND mp.legacy_object_id=c.legacy_installation_object_id AND mp.category='native_candidate'
              JOIN `{$l}fm_maintable` l ON l.id=c.legacy_installation_object_id
              JOIN `{$p}fm2_assignment_orders` o ON o.installation_case_id=c.id AND o.status='registered'
              LEFT JOIN `{$p}fm2_assignment_orders` newer ON newer.installation_case_id=o.installation_case_id AND newer.status='registered' AND newer.version_no>o.version_no
              WHERE c.process_state='working' AND c.actual_start_date<=? AND newer.id IS NULL ORDER BY c.id";
        $s=$this->db->prepare($sql);$s->bind_param('s',$reportDate);$s->execute();$out=[];
        foreach($s->get_result()->fetch_all(MYSQLI_ASSOC) as $row){
            $case=(int)$row['case_id'];$object=(int)$row['object_id'];$issues=[];
            [$progress,$progressSource,$templateIssues]=$this->progress($case,$reportDate);$issues=array_merge($issues,$templateIssues);
            $finance=$this->financeFromObjectCard($object,$issues);
            $crew=$this->crew($case,$reportDate,$issues,$progressSource['contributions']??[]);
            $deadline=(string)($row['deadline_date']??'');if(!$this->date($deadline))$issues[]=['code'=>'DEADLINE_EVIDENCE_ABSENT','message'=>'В зарегистрированном распоряжении нет подтверждённого срока окончания.','owner'=>'ФКР'];
            if($progressSource===null)$issues[]=['code'=>'PROGRESS_EVIDENCE_ABSENT','message'=>'На отчётную дату нет доказуемой версии чек-листа и server-side отметок.','owner'=>'Стройконтроль'];
            $orderSource=$this->source('Зарегистрированное распоряжение','fm2_assignment_orders/'.(int)$row['order_id'],$row);
            $fact=static fn(mixed $v,string $d,array $src):array=>['value'=>$v,'effectiveDate'=>$d,'source'=>$src];
            $operands=null;
            if($issues===[]){$financeSource=['label'=>(string)$finance['source_label'],'locator'=>(string)$finance['source_locator'],'contentSha256'=>(string)$finance['source_sha256']];$completion=$this->date((string)$row['completion_date'])?(string)$row['completion_date']:null;$operands=[
                'reportDate'=>$fact($reportDate,$reportDate,$this->source('Выбранная отчётная дата','otiz/report-date/'.$reportDate,['reportDate'=>$reportDate])),
                'premiumCents'=>$fact((int)$finance['premium_cents'],(string)$finance['effective_date'],$financeSource),
                'shaftBp'=>$fact((int)$finance['shaft_bp'],(string)$finance['effective_date'],$financeSource),
                'progressBp'=>$fact($progress,$reportDate,$progressSource),
                'deadlineDate'=>$fact($deadline,(string)$row['order_date'],$orderSource),
                'completionDate'=>$fact($completion,$reportDate,$orderSource),
            ];}
            $out[]=['id'=>$object,'caseId'=>$case,'reg'=>(string)$row['regnumber'],'address'=>(string)$row['address'],'progress'=>$progress,'deadline'=>$deadline,'pto'=>$row['completion_date'],'premium'=>(int)($finance['premium_cents']??0),'shaft'=>(int)($finance['shaft_bp']??0),'team'=>$crew,'issues'=>$issues,'operands'=>$operands];
        }return$out;
    }

    private function progress(int $case,string $reportDate):array
    {
        $p=$this->prefix;$s=$this->db->prepare("SELECT o.item_id,o.client_operation_id,o.device_time,o.server_received_at,o.template_snapshot_id,o.template_content_sha256,t.payload_json FROM `{$p}fm2_checklist_operations` o JOIN `{$p}fm2_checklist_template_snapshots` t ON t.id=o.template_snapshot_id AND t.content_sha256=o.template_content_sha256 WHERE o.installation_case_id=? AND o.operation_type='item_completed' AND LEFT(o.device_time,10)<=? AND LEFT(o.server_received_at,10)<=? ORDER BY o.id");$s->bind_param('iss',$case,$reportDate,$reportDate);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);if($rows===[])return[0,null,[]];$items=[];$definitions=[];$hashes=[];foreach($rows as$r){$payload=json_decode((string)$r['payload_json'],true);foreach($payload['definitions']??[]as$d)$definitions[(int)$d['id']]=(int)$d['share'];$items[(int)$r['item_id']]=$r;$hashes[(string)$r['template_content_sha256']]=true;}if(count($hashes)!==1)return[0,null,[['code'=>'DEFINITION_VERSION_UNPROVEN','message'=>'Отметки используют несовместимые версии чек-листа.','owner'=>'Администратор']]];$unknown=array_diff(array_keys($items),array_keys($definitions));if($unknown!==[])return[0,null,[['code'=>'CHECKLIST_ITEM_UNPROVEN','message'=>'Отмеченный пункт отсутствует в неизменяемой версии чек-листа.','owner'=>'Администратор']]];$bp=0;$contributions=[];foreach($items as$id=>$event){$share=$definitions[$id]*100;$bp+=$share;$a=$this->db->prepare("SELECT installer_tab_id FROM `{$p}fm2_checklist_operation_installers` WHERE client_operation_id=? ORDER BY installer_tab_id");$a->bind_param('s',$event['client_operation_id']);$a->execute();$tabs=array_column($a->get_result()->fetch_all(MYSQLI_ASSOC),'installer_tab_id');if($tabs===[])return[0,null,[['code'=>'CHECKLIST_ATTRIBUTION_ABSENT','message'=>'Выполненный пункт не имеет server-side attribution монтажника.','owner'=>'Стройконтроль']]];$each=intdiv($share,count($tabs));foreach($tabs as$tab)$contributions[(string)$tab]=($contributions[(string)$tab]??0)+$each;}$canonical=['caseId'=>$case,'events'=>array_values(array_map(fn($r)=>$r['client_operation_id'],$items)),'templateHash'=>array_key_first($hashes)];$source=$this->source('Append-only история чек-листа','fm2_checklist_operations/case/'.$case,$canonical);$source['contributions']=$contributions;return[min(10000,$bp),$source,[]];
    }

    private function crew(int $case,string $date,array &$issues,array $contributions):array
    {
        $p=$this->prefix;$sql="SELECT oi.installer_tab_id,oi.fio_snapshot,oi.position_snapshot FROM `{$p}fm2_assignment_orders` o JOIN `{$p}fm2_order_installers` oi ON oi.assignment_order_id=o.id WHERE o.installation_case_id=? AND o.status='registered' AND NOT EXISTS(SELECT 1 FROM `{$p}fm2_assignment_orders` n WHERE n.installation_case_id=o.installation_case_id AND n.status='registered' AND n.version_no>o.version_no) ORDER BY oi.installer_tab_id";$s=$this->db->prepare($sql);$s->bind_param('i',$case);$s->execute();$crew=[];foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$r){$tab=(string)$r['installer_tab_id'];$contribution=(int)($contributions[$tab]??0);if($contribution===0){$issues[]=['code'=>'INSTALLER_ATTRIBUTION_ABSENT','message'=>'У монтажника '.$r['fio_snapshot'].' нет подтверждённого вклада checklist на дату.','owner'=>'Стройконтроль'];continue;}$crew[]=['tab'=>$tab,'name'=>$r['fio_snapshot'],'position'=>$r['position_snapshot'],'contribution'=>$contribution,'weight'=>$contribution,'basis'=>'Фактический вклад checklist × базовый управленческий коэффициент 1,00'];}if($crew===[])$issues[]=['code'=>'CREW_EVIDENCE_ABSENT','message'=>'Нет состава с подтверждённым вкладом на отчётную дату.','owner'=>'Стройконтроль'];return$crew;
    }

    private function financeFromObjectCard(int $object,array &$issues):?array
    {
        $p=$this->prefix;$s=$this->db->prepare("SELECT content_sha256,payload_json,captured_at FROM `{$p}fm2_pilot_object_details` WHERE object_id=? LIMIT 1");$s->bind_param('i',$object);$s->execute();$row=$s->get_result()->fetch_assoc();
        if(!is_array($row)){$issues[]=['code'=>'OBJECT_CARD_EVIDENCE_ABSENT','message'=>'Нет неизменяемого снимка характеристик карточки объекта.','owner'=>'Администратор'];return null;}
        try{$payload=json_decode((string)$row['payload_json'],true,flags:JSON_THROW_ON_ERROR);}catch(JsonException){$payload=null;}
        if(!is_array($payload)||!preg_match('/^[a-f0-9]{64}$/D',(string)$row['content_sha256'])){$issues[]=['code'=>'OBJECT_CARD_PROVENANCE_INVALID','message'=>'Снимок характеристик карточки объекта повреждён.','owner'=>'Администратор'];return null;}
        $fields=$payload['fields']??[];$floors=filter_var($fields['floors']['raw']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$capacity=filter_var($fields['weight']['raw']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);$material=trim((string)($fields['pitmaterial']['display']??''));$typeText=mb_strtolower((string)($fields['lift_type']['display']??''),'UTF-8');$type=str_contains($typeText,'груз')?'cargo':(str_contains($typeText,'пассаж')?'passenger':null);
        $norms=new NativePremiumNorms();$premium=$floors===false||$capacity===false?null:$norms->premiumCents($type,(int)$floors,(int)$capacity);$shaft=$norms->shaftBasisPoints($material);
        if($premium===null){$issues[]=['code'=>'PREMIUM_NORM_UNRESOLVED','message'=>'По этажности и грузоподъёмности карточки не найдена плановая премия приложения 4.','owner'=>'ФКР'];}
        if($shaft===null){$issues[]=['code'=>'SHAFT_COEFFICIENT_UNRESOLVED','message'=>'По материалу шахты карточки не найден коэффициент приложения 4.','owner'=>'ФКР'];}
        if($premium===null||$shaft===null)return null;
        return['premium_cents'=>$premium,'shaft_bp'=>$shaft,'effective_date'=>substr((string)$row['captured_at'],0,10),'source_label'=>'Характеристики карточки объекта + приложение 4 к приказу №178','source_locator'=>'fm2_pilot_object_details/'.$object,'source_sha256'=>(string)$row['content_sha256']];
    }
    private function source(string$label,string$locator,array$data):array{$json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);return compact('label','locator')+['contentSha256'=>hash('sha256',$json)];}
    private function date(string$v):bool{$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d!==false&&$d->format('Y-m-d')===$v;}
}
