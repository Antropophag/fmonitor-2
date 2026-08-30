<?php

declare(strict_types=1);

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
            $finance=$this->latest("{$p}fm2_operational_premium_facts",$case,$reportDate,'effective_date');
            if(!$finance){$issues[]=['code'=>'PREMIUM_EVIDENCE_ABSENT','message'=>'Нет подтверждённой плановой премии и коэффициента шахты для native-объекта.','owner'=>'ОТиЗ'];}
            elseif(!$this->storedSourceValid($finance)){$issues[]=['code'=>'FINANCIAL_PROVENANCE_INVALID','message'=>'Provenance финансового факта повреждён или неполон.','owner'=>'Администратор'];}
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
        $p=$this->prefix;$sql="SELECT oi.installer_tab_id,oi.fio_snapshot,oi.position_snapshot,k.id ktu_id,k.ktu_bp,k.effective_date,k.source_label,k.source_locator,k.source_sha256 FROM `{$p}fm2_assignment_orders` o JOIN `{$p}fm2_order_installers` oi ON oi.assignment_order_id=o.id LEFT JOIN `{$p}fm2_operational_ktu_facts` k ON k.installation_case_id=o.installation_case_id AND k.installer_tab_id=oi.installer_tab_id AND k.effective_date<=? WHERE o.installation_case_id=? AND o.status='registered' AND NOT EXISTS(SELECT 1 FROM `{$p}fm2_assignment_orders` n WHERE n.installation_case_id=o.installation_case_id AND n.status='registered' AND n.version_no>o.version_no) ORDER BY oi.installer_tab_id,k.effective_date DESC,k.id DESC";$s=$this->db->prepare($sql);$s->bind_param('si',$date,$case);$s->execute();$crew=[];$seen=[];foreach($s->get_result()->fetch_all(MYSQLI_ASSOC)as$r){$tab=(string)$r['installer_tab_id'];if(isset($seen[$tab]))continue;$seen[$tab]=true;if($r['ktu_id']===null){$issues[]=['code'=>'KTU_EVIDENCE_ABSENT','message'=>'Нет подтверждённого КТУ монтажника '.$r['fio_snapshot'].'.','owner'=>'ОТиЗ'];continue;}if(!$this->storedSourceValid($r)){$issues[]=['code'=>'KTU_PROVENANCE_INVALID','message'=>'Provenance КТУ монтажника '.$r['fio_snapshot'].' повреждён.','owner'=>'Администратор'];continue;}$contribution=(int)($contributions[$tab]??0);if($contribution===0){$issues[]=['code'=>'INSTALLER_ATTRIBUTION_ABSENT','message'=>'У монтажника '.$r['fio_snapshot'].' нет подтверждённого вклада checklist на дату.','owner'=>'Стройконтроль'];continue;}$crew[]=['tab'=>$tab,'name'=>$r['fio_snapshot'],'position'=>$r['position_snapshot'],'contribution'=>$contribution,'weight'=>intdiv($contribution*(int)$r['ktu_bp'],10000),'basis'=>'Checklist attribution + подтверждённый КТУ #'.(int)$r['ktu_id']];}if($crew===[])$issues[]=['code'=>'CREW_EVIDENCE_ABSENT','message'=>'Нет состава с подтверждённым КТУ на отчётную дату.','owner'=>'ОТиЗ'];return$crew;
    }
    private function latest(string$table,int$case,string$date,string$dateColumn):?array{$s=$this->db->prepare("SELECT * FROM `{$table}` WHERE installation_case_id=? AND `{$dateColumn}`<=? ORDER BY `{$dateColumn}` DESC,id DESC LIMIT 1");$s->bind_param('is',$case,$date);$s->execute();return$s->get_result()->fetch_assoc()?:null;}
    private function source(string$label,string$locator,array$data):array{$json=json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);return compact('label','locator')+['contentSha256'=>hash('sha256',$json)];}
    private function storedSourceValid(array$row):bool{return trim((string)($row['source_label']??''))!==''&&trim((string)($row['source_locator']??''))!==''&&preg_match('/^[a-f0-9]{64}$/D',(string)($row['source_sha256']??''))===1;}
    private function date(string$v):bool{$d=DateTimeImmutable::createFromFormat('!Y-m-d',$v);return$d!==false&&$d->format('Y-m-d')===$v;}
}
