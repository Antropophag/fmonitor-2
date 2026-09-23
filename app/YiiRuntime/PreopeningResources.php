<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime;

use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

require_once dirname(__DIR__).'/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

final class PreopeningResources
{
    public readonly \mysqli $db;
    public readonly string $prefix;

    public function __construct(private readonly ?\yii\db\Connection $yiiDb = null)
    {
        $this->prefix=(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:'');
        if((string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:'')!==$this->prefix)throw new \RuntimeException();
        $this->db=$this->connect();
    }
    public function connect():\mysqli
    {
        $port=filter_var(getenv('FMONITOR_DB_PORT'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>65535]]);
        $host=(string)getenv('FMONITOR_DB_HOST');$name=(string)getenv('FMONITOR_DB_NAME');
        if($port===false||$host===''||$name==='')throw new \RuntimeException();
        $db=new \mysqli($host,(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),$name,$port);
        if(!$db->set_charset('utf8mb4')){$db->close();throw new \RuntimeException();}return$db;
    }
    public function portal():C\AssignmentOrderSelectionPortalQuery{return C\ProductionAssignmentOrderSelectionPortalFactory::create($this->db,$this->prefix);}
    public function currentInstallerAssignments(array $tabIds):array
    {
        $tabIds=array_values(array_unique(array_filter(array_map('intval',$tabIds),static fn(int$id):bool=>$id>0)));if($tabIds===[])return[];
        $ids=implode(',',$tabIds);$p=$this->prefix;$completion="NOT (EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` pto WHERE pto.installation_case_id=c.id AND pto.fact_type='pto_act') AND EXISTS(SELECT 1 FROM `{$p}fm2_pilot_completion_facts` declaration WHERE declaration.installation_case_id=c.id AND declaration.fact_type='declaration'))";$day=$this->db->real_escape_string((new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'));
        $legacy=$this->db->query("SELECT oi.installer_tab_id,c.legacy_installation_object_id object_id,CONCAT(o.registration_number,' · ',l.regnumber) registration_number,l.ordadr_address address FROM `{$p}fm2_order_installers` oi JOIN `{$p}fm2_assignment_orders` o ON o.id=oi.assignment_order_id JOIN `{$p}fm2_installation_cases` c ON c.id=o.installation_case_id JOIN `{$p}fm_maintable` l ON l.id=c.legacy_installation_object_id WHERE oi.installer_tab_id IN ({$ids}) AND o.status='registered' AND o.version_no=(SELECT MAX(x.version_no) FROM `{$p}fm2_assignment_orders` x WHERE x.installation_case_id=o.installation_case_id) AND oi.change_action<>'release' AND oi.valid_from<='{$day}' AND (oi.valid_to IS NULL OR oi.valid_to>='{$day}') AND NOT EXISTS(SELECT 1 FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=o.installation_case_id) AND {$completion} ORDER BY oi.installer_tab_id,c.legacy_installation_object_id")->fetch_all(MYSQLI_ASSOC);
        $native=$this->db->query("SELECT a.selected_snapshot_json,c.legacy_installation_object_id object_id,l.regnumber registration_number,l.ordadr_address address FROM `{$p}fm2_assignment_order_applications` a JOIN `{$p}fm2_installation_cases` c ON c.id=a.installation_case_id JOIN `{$p}fm_maintable` l ON l.id=c.legacy_installation_object_id WHERE a.application_sequence=(SELECT MAX(x.application_sequence) FROM `{$p}fm2_assignment_order_applications` x WHERE x.installation_case_id=a.installation_case_id) AND {$completion} ORDER BY c.legacy_installation_object_id")->fetch_all(MYSQLI_ASSOC);
        $out=[];$add=static function(array&$target,int$tabId,array$row):void{$objectId=(int)$row['object_id'];if($tabId<1||$objectId<1||trim((string)$row['registration_number'])===''||trim((string)$row['address'])==='')throw new \RuntimeException();$target[$tabId][$objectId]=['objectId'=>$objectId,'registrationNumber'=>(string)$row['registration_number'],'address'=>(string)$row['address']];};foreach($legacy as$row)$add($out,(int)$row['installer_tab_id'],$row);
        $wanted=array_fill_keys($tabIds,true);foreach($native as$row){$snapshot=json_decode((string)$row['selected_snapshot_json'],true,512,JSON_THROW_ON_ERROR);foreach($snapshot['selectedInstallers']??[]as$installer){$tabId=(int)($installer['tabId']??0);if(isset($wanted[$tabId]))$add($out,$tabId,$row);}}foreach($out as&$rows){ksort($rows,SORT_NUMERIC);$rows=array_values($rows);}unset($rows);return$out;
    }
    public function assignmentReader():I\MariaDbControlEngineerAssignmentReader{return new I\MariaDbControlEngineerAssignmentReader($this->db,$this->prefix);}
    public function assignEngineer(I\ControlEngineerAssignmentCommand $command):array{return I\ProductionControlEngineerAssignmentFactory::create($this->db,$this->prefix)->assign($command);}
    public function editObjectDetails(I\ObjectDetailsEditCommand$command):array{return I\ProductionObjectDetailsEditFactory::create($this->db,$this->prefix,static fn():string=>(new \DateTimeImmutable('now',new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.u\Z'))->edit($command);}
    public function selectAssignmentOrderComposition(C\SelectAssignmentOrderCompositionCommand $command): array
    {
        $application = C\ProductionAssignmentOrderCompositionFactory::create($this->db, $this->connect(...), $this->prefix);
        return (new C\SelectionResultSerializer())->serialize($application->selectAssignmentOrderComposition($command));
    }
    public function template(int$case,int$order,int$actor):array{return C\ProductionAssignmentOrderTemplateFactory::create($this->db,$this->prefix)->generateAssignmentOrderTemplate($case,$order,$actor);}
    public function submission():O\AssignmentOrderOriginalSubmissionQuery{return O\ProductionAssignmentOrderOriginalSubmissionFactory::create($this->db,$this->prefix);}
    public function originalAccess():O\AssignmentOrderOriginalAccessQuery
    {
        if ($this->yiiDb === null) throw new \RuntimeException('Yii database connection required for original admission.');
        return new O\MariaDbAssignmentOrderOriginalAccessQuery($this->db,$this->yiiDb,$this->prefix,$this->submission());
    }
    public function history():O\AssignmentOrderOriginalHistoryReader{return O\AssignmentOrderOriginalHistoryReaderFactory::create($this->db,(string)getenv('FMONITOR_ARTIFACT_STORAGE_ROOT'),$this->prefix);}
    public function original():O\AssignmentOrderOriginalApplication
    {
        $config=new O\AssignmentOrderOriginalFreshReaderConfig((string)getenv('FMONITOR_DB_HOST'),(int)getenv('FMONITOR_DB_PORT'),(string)getenv('FMONITOR_DB_NAME'),(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_ORIGINAL_DB_PASSWORD_FILE'),$this->prefix);
        $fresh=new O\AssignmentOrderOriginalMariaDbFreshTerminalReaderFactory($config);$probe=$fresh->open();
        if($probe->status!==O\AssignmentOrderOriginalFreshReaderOpenStatus::OPENED||$probe->reader===null||$probe->reader->close()!==O\AssignmentOrderOriginalFreshReaderCloseStatus::CLOSED)throw new \RuntimeException();
        return O\ProductionAssignmentOrderOriginalFactory::createForSelections($this->db,new O\AssignmentOrderOriginalProductionConfig((string)getenv('FMONITOR_ARTIFACT_STORAGE_ROOT'),$this->prefix,(string)getenv('FMONITOR_ORIGINAL_SAFE_LOG_FILE')),$fresh);
    }
    public function close():void{$this->db->close();}
}
