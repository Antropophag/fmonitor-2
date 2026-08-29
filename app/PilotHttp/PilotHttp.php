<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

final readonly class PilotHttpRequest { public function __construct(public string $method,public string $path,public string $host,public mixed $serverIdentity,public array $server=[],public string $body=''){} }
final readonly class PilotHttpResponse { public function __construct(public int $status,public array $headers,public string $body){} }
final readonly class HttpUser { public function __construct(public int $id,public string $displayName,public string $email){} }
final class InvalidServerIdentity extends \RuntimeException {}
final class CssAssetUnavailable extends \RuntimeException {}
final class CssDescriptorCloseFailed extends \RuntimeException { public function __construct(){parent::__construct('CSS descriptor close failed.',0,null);} }
final class PilotHttpInfrastructureUnavailable extends \RuntimeException {}
final class InvalidHttpRequest extends \RuntimeException {}

interface TrustedServerIdentity { public function resolve(mixed $serverIdentity):string; }
interface HttpUserDirectory { public function resolveActiveUser(string $principal):?HttpUser; }
interface PilotShellRenderer { public function render(HttpUser $user):string; }
interface CompatibilityPilotShellRenderer { public function renderCompatibility(HttpUser $user):string; }
interface ObjectCardRenderer { public function render(HttpUser $user,array $card):string; }
interface CompatibilityObjectCardRenderer { public function renderCompatibility(HttpUser $user,array $card):string; }
interface ObjectCardReader { public function read(int $installationObjectId):array|null; }
interface ObjectListRenderer { public function render(HttpUser $user,array $objects):string; }
interface CompatibilityObjectListRenderer { public function renderCompatibility(HttpUser $user,array $objects):string; }
interface ObjectListReader { public function read():array; }
interface PrepareFormRenderer { public function render(HttpUser $user,array $form):string; }
interface CompatibilityPrepareFormRenderer { public function renderCompatibility(HttpUser $user,array $form):string; }
interface PrepareFormReader { public function read(int $installationObjectId,string $processDate):array|null; }
interface CssAsset { public function readBytes():string; public function close():void; }
interface CssDescriptor { public function readBytes():string; public function close():void; }
interface CssDescriptorOpener { public function open(string $absolutePath):CssDescriptor; }
interface PhpStreamCloser { public function close(mixed $phpStream):void; }
interface PhpStreamClosePrimitive { public function close(mixed $phpStream):bool; }
interface EnvironmentSource { public function read(string $name):string|false; }
interface PilotHttpDependencies { public function css():CssAsset; public function users():HttpUserDirectory; public function close():void; }
interface ObjectCardReaderProvider { public function objectCards():ObjectCardReader; }
interface ObjectListReaderProvider { public function objectList():ObjectListReader; }
interface PrepareFormReaderProvider { public function prepareForms():PrepareFormReader; public function hasCapability(int $userId,string $capability):bool; public function processDate():string; }
interface PilotUiConfiguration { public function pilotUiConfigured():bool; public function prepareCommandConfigured():bool; public function pilotCss():CssAsset; }
interface UnexpectedFailureReporter { public function report(string $category,string $correlationId):void; }
interface CorrelationIdSource { public function nextId():string; }

final class RemoteUserIdentity implements TrustedServerIdentity
{
    public function resolve(mixed $value):string
    {
        if(!\is_string($value)||\strlen($value)<3||\strlen($value)>254||\preg_match("/^[A-Za-z0-9.!#$%&'*+\\/=?^_`{|}~-]+@[A-Za-z0-9.-]+$/D",$value)!==1)throw new InvalidServerIdentity();
        return $value;
    }
}

abstract class NamedCssAsset implements CssAsset
{
    private ?CssDescriptor $descriptor=null;private ?string $bytes=null;private bool $closeAttempted=false;
    protected function __construct(private readonly string $path,private readonly CssDescriptorOpener $descriptors,private readonly string $basename){}
    public function readBytes():string
    {
        if($this->bytes!==null)return $this->bytes;
        if($this->path===''||$this->path[0]!=='/'||\basename($this->path)!==$this->basename)throw new CssAssetUnavailable();
        $this->descriptor=$this->descriptors->open($this->path);return $this->bytes=$this->descriptor->readBytes();
    }
    public function close():void
    {
        if($this->closeAttempted)return;$this->closeAttempted=true;$descriptor=$this->descriptor;
        try{if($descriptor!==null)$descriptor->close();}finally{$this->descriptor=null;$this->bytes=null;}
    }
}

final class ShlzCssAsset extends NamedCssAsset
{
    public function __construct(string $path,CssDescriptorOpener $descriptors){parent::__construct($path,$descriptors,'shlz.css');}
}

final class PilotCssAsset extends NamedCssAsset
{
    public function __construct(string $path,CssDescriptorOpener $descriptors){parent::__construct($path,$descriptors,'pilot.css');}
}

final class PhpCssDescriptor implements CssDescriptor
{
    private mixed $resource;private bool $closeAttempted=false;
    public function __construct(mixed $resource,private readonly PhpStreamCloser $closer){if(!\is_resource($resource))throw new CssAssetUnavailable();$this->resource=$resource;}
    public function readBytes():string{$before=@\fstat($this->resource);if(!\is_array($before)||($before['mode']&0170000)!==0100000)throw new CssAssetUnavailable();$size=(int)$before['size'];$bytes='';while(\strlen($bytes)<$size){$chunk=@\fread($this->resource,$size-\strlen($bytes));if(!\is_string($chunk)||$chunk==='')throw new CssAssetUnavailable();$bytes.=$chunk;}$after=@\fstat($this->resource);if(!\is_array($after)||$before['dev']!==$after['dev']||$before['ino']!==$after['ino']||(int)$after['size']!==$size||\strlen($bytes)!==$size)throw new CssAssetUnavailable();return $bytes;}
    public function close():void{if($this->closeAttempted)return;$this->closeAttempted=true;$resource=$this->resource;$this->resource=null;$this->closer->close($resource);}
}

final class PhpCssDescriptorOpener implements CssDescriptorOpener
{
    public function __construct(private readonly PhpStreamCloser $closer){}
    public function open(string $path):CssDescriptor{$before=@\lstat($path);if(!self::same($before,$before)||\is_link($path)||!\is_readable($path))throw new CssAssetUnavailable();$resource=@\fopen($path,'rb');if($resource===false)throw new CssAssetUnavailable();try{$opened=@\fstat($resource);$after=@\lstat($path);if(!self::same($before,$opened)||!self::same($before,$after)||\is_link($path))throw new CssAssetUnavailable();return new PhpCssDescriptor($resource,$this->closer);}catch(\Throwable $e){try{$this->closer->close($resource);}catch(\Throwable){}throw $e;}}
    private static function same(array|false $a,array|false $b):bool{return \is_array($a)&&\is_array($b)&&($a['mode']&0170000)===0100000&&($b['mode']&0170000)===0100000&&$a['dev']===$b['dev']&&$a['ino']===$b['ino'];}
}

final class NativePhpFclosePrimitive implements PhpStreamClosePrimitive
{
    public function close(mixed $phpStream):bool{return \fclose($phpStream);}
}

final class NativePhpStreamCloser implements PhpStreamCloser
{
    public function __construct(private readonly PhpStreamClosePrimitive $primitive){}
    public function close(mixed $phpStream):void
    {
        $warning=false;\set_error_handler(static function()use(&$warning):bool{$warning=true;return true;});
        try{try{$result=$this->primitive->close($phpStream);}catch(\Throwable){throw new CssDescriptorCloseFailed();}if($warning||$result!==true)throw new CssDescriptorCloseFailed();}finally{\restore_error_handler();}
    }
}

final class PrepareFormUnavailable extends \RuntimeException {}

final class MariaDbPrepareFormReader implements PrepareFormReader
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function read(int $id,string $processDate):array|null
    {
        try{
            if(self::date($processDate)!==$processDate)throw new PilotHttpInfrastructureUnavailable();$cases=$this->many('SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id FROM fm2_installation_cases WHERE legacy_installation_object_id=? LIMIT 2',$id);$legacy=$this->many("SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,workdatefinish,ptoactdate,responsstroicontrol FROM `{$this->prefix}fm_maintable` WHERE id=? LIMIT 2",$id);
            if($cases===[]&&$legacy===[])return null;if(\count($cases)!==1||\count($legacy)!==1)throw new PilotHttpInfrastructureUnavailable();$case=$cases[0];$l=$legacy[0];if((string)$case['process_state']!=='needs_assignment_order')throw new PrepareFormUnavailable();
            $address=\trim((string)$l['ordadr_address']);$entrance=\trim((string)$l['entrance']);$registration=\trim((string)$l['regnumber']);$start=self::datePrefix($l['workdatestart']);$finish=self::datePrefix($l['workdateendadjusted'])??self::datePrefix($l['plan_finish_date']);$completion=self::optionalLegacyDate($l['workdatefinish']);$pto=self::optionalLegacyDate($l['ptoactdate']);if(self::positiveId($case['id'])===null||self::positiveId($case['legacy_installation_object_id'])!==$id||self::positiveId($l['id'])!==$id||$address===''||$entrance===''||$registration===''||$start===null||$finish===null||$case['actual_start_date']!==null||$case['opened_at']!==null||$case['opened_by_user_id']!==null||$completion!==null||$pto!==null)throw new PilotHttpInfrastructureUnavailable();if($this->many('SELECT id FROM fm2_assignment_orders WHERE installation_case_id=? LIMIT 1',(int)$case['id'])!==[])throw new PilotHttpInfrastructureUnavailable();
            $historyColumns=$this->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fm2_workforce_catalog' AND COLUMN_NAME IN ('reconciliation_state','last_successful_sync_run_id','last_successful_sync_at')")->fetch_all(MYSQLI_ASSOC);if(\count($historyColumns)!==0&&\count($historyColumns)!==3)throw new PilotHttpInfrastructureUnavailable();$history=\count($historyColumns)===3;$runId=null;$syncAt=null;if($history){$metadata=$this->query('SELECT singleton_id,last_successful_run_id,last_successful_at FROM fm2_workforce_sync_metadata LIMIT 2')->fetch_all(MYSQLI_ASSOC);if(\count($metadata)!==1||self::positiveId($metadata[0]['singleton_id'])!==1||!\is_string($metadata[0]['last_successful_run_id'])||$metadata[0]['last_successful_run_id']===''||!self::rfc3339($metadata[0]['last_successful_at']))throw new PilotHttpInfrastructureUnavailable();$runId=$metadata[0]['last_successful_run_id'];$syncAt=$metadata[0]['last_successful_at'];$s=$this->connection->prepare($this->prefixed('SELECT run_id,status,completed_at FROM fm2_workforce_sync_runs WHERE run_id=? LIMIT 2'));$s->bind_param('s',$runId);$s->execute();$runs=$s->get_result()->fetch_all(MYSQLI_ASSOC);if(\count($runs)!==1||$runs[0]['status']!=='completed'||!self::rfc3339($runs[0]['completed_at'])||$runs[0]['completed_at']!==$syncAt)throw new PilotHttpInfrastructureUnavailable();}
            $fields='installer_tab_id,fio,position,employment_status,employed_from,employed_to,workforce_source,workforce_source_updated_at'.($history?',reconciliation_state,last_successful_sync_run_id,last_successful_sync_at':'');$workforce=$this->query("SELECT {$fields} FROM fm2_workforce_catalog LIMIT 502")->fetch_all(MYSQLI_ASSOC);$busy=[];$busyColumns=$this->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='fm2_order_installers' AND COLUMN_NAME='valid_to'")->fetch_all(MYSQLI_ASSOC);$busyRows=$busyColumns===[]?[]:$this->query("SELECT installer_tab_id,MAX(valid_to) AS busy_until FROM fm2_order_installers WHERE valid_to IS NOT NULL GROUP BY installer_tab_id LIMIT 502")->fetch_all(MYSQLI_ASSOC);foreach($busyRows as $busyRow){$busyTab=self::positiveId($busyRow["installer_tab_id"]);$busyUntil=self::date($busyRow["busy_until"]);if($busyTab===null||$busyUntil===null||isset($busy[$busyTab]))throw new PilotHttpInfrastructureUnavailable();$busy[$busyTab]=$busyUntil;}$installers=[];$seen=[];foreach($workforce as $x){$tab=self::positiveId($x['installer_tab_id']);$fio=\trim((string)$x['fio']);$position=\trim((string)$x['position']);$source=\trim((string)$x['workforce_source']);$from=self::date($x['employed_from']);$to=$x['employed_to']===null?null:self::date($x['employed_to']);if($tab===null||isset($seen[$tab])||$fio===''||$position===''||$source===''||$from===null||($x['employed_to']!==null&&$to===null)||!self::rfc3339($x['workforce_source_updated_at']))throw new PilotHttpInfrastructureUnavailable();$seen[$tab]=true;$reconciliation='delivered';if($history){$provenance=[$x['reconciliation_state'],$x['last_successful_sync_run_id'],$x['last_successful_sync_at']];$legacy=\count(\array_filter($provenance,static fn(mixed $v):bool=>$v!==null))===0;if(!$legacy&&!\in_array($x['reconciliation_state'],['delivered','missing_from_delivery'],true))throw new PilotHttpInfrastructureUnavailable();if(!$legacy&&(!self::rfc3339($x['last_successful_sync_at'])||$x['last_successful_sync_run_id']!==$runId||$x['last_successful_sync_at']!==$syncAt))throw new PilotHttpInfrastructureUnavailable();$reconciliation=$legacy?'delivered':$x['reconciliation_state'];}if($reconciliation==='delivered'&&$x['employment_status']==='employed'&&$from<=$processDate&&($to===null||$to>=$finish))$installers[]=['tabId'=>$tab,'fio'=>$fio,'position'=>$position,'source'=>$source,'updatedAt'=>(string)$x['workforce_source_updated_at'],'busyUntil'=>$busy[$tab]??null];}if(\count($installers)>500)throw new PilotHttpInfrastructureUnavailable();\usort($installers,static fn(array $a,array $b):int=>[$a['fio'],$a['tabId']]<=>[$b['fio'],$b['tabId']]);
            $rows=$this->query("SELECT u.id,u.name,c.position_snapshot FROM fm2_process_user_capabilities c JOIN `{$this->prefix}users` u ON u.id=c.user_id JOIN `{$this->prefix}users_roles` r ON r.id=u.role_id WHERE c.capability='construction_control_engineer' AND u.status=1 AND r.status=1 LIMIT 102")->fetch_all(MYSQLI_ASSOC);if(\count($rows)>100)throw new PilotHttpInfrastructureUnavailable();$engineers=[];$seen=[];$prefill=self::positiveId($l['responsstroicontrol']);foreach($rows as $x){$uid=self::positiveId($x['id']);$fio=\trim((string)$x['name']);$position=\trim((string)$x['position_snapshot']);if($uid===null||isset($seen[$uid])||$fio===''||$position==='')throw new PilotHttpInfrastructureUnavailable();$seen[$uid]=true;$engineers[]=['userId'=>$uid,'fio'=>$fio,'position'=>$position,'prefilled'=>$uid===$prefill];}\usort($engineers,static fn(array $a,array $b):int=>[$a['fio'],$a['userId']]<=>[$b['fio'],$b['userId']]);
            return ['id'=>$id,'registrationNumber'=>$registration,'address'=>$address,'entrance'=>$entrance,'plannedStartDate'=>$start,'plannedFinishDate'=>$finish,'installers'=>$installers,'engineers'=>$engineers];
        }catch(PrepareFormUnavailable|PilotHttpInfrastructureUnavailable $e){throw $e;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function prefixed(string $sql):string{return \str_replace('fm2_',$this->processPrefix.'fm2_',$sql);}
    private function query(string $sql):\mysqli_result|bool{return $this->connection->query($this->prefixed($sql));}
    private function many(string $sql,int $id):array{$s=$this->connection->prepare($this->prefixed($sql));$s->bind_param('i',$id);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);}
    private static function positiveId(mixed $v):?int{if(!\is_int($v)&&(!\is_string($v)||\preg_match('/^[1-9][0-9]*$/D',$v)!==1))return null;$n=\filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $n===false?null:$n;}
    private static function date(mixed $v):?string{if(!\is_string($v)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $v;}
    private static function optionalLegacyDate(mixed $v):?string{if($v===null)return null;$v=\trim((string)$v);if($v===''||\preg_match('/^0+$/D',$v)===1||\str_starts_with($v,'0000-00-00'))return null;$date=self::datePrefix($v);if($date===null)throw new PilotHttpInfrastructureUnavailable();return $date;}
    private static function datePrefix(mixed $v):?string{if(!\is_string($v)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$v,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $m[1].'-'.$m[2].'-'.$m[3];}
    private static function rfc3339(mixed $v):bool{if(!\is_string($v)||\preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D',$v,$m)!==1)return false;$c=$m[1].($m[2]==='Z'?'+00:00':$m[2]);$d=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$c);$er=\DateTimeImmutable::getLastErrors();return $d!==false&&($er===false||($er['warning_count']===0&&$er['error_count']===0))&&$d->format('Y-m-d\TH:i:sP')===$c;}
}

final class MariaDbObjectCardReader implements ObjectCardReader
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function read(int $id):array|null
    {
        try{
            $case=$this->one('SELECT id,legacy_installation_object_id,process_state,actual_start_date,opened_at,opened_by_user_id FROM fm2_installation_cases WHERE legacy_installation_object_id=? LIMIT 2',$id);
            $table=$this->prefix.'fm_maintable';$column=$this->connection->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME='ptoactdate' LIMIT 1");$column->bind_param('s',$table);$column->execute();$ptoSelect=$column->get_result()->fetch_assoc()!==null?'ptoactdate':'NULL AS ptoactdate';$legacy=$this->one("SELECT id,ordadr_address,entrance,regnumber,workdatestart,workdateendadjusted,plan_finish_date,{$ptoSelect} FROM `{$table}` WHERE id=? LIMIT 2",$id);
            if($case===null||$legacy===null)return null;
            $card=$this->legacyCard($legacy,$id);if($card===null)return null;
            $caseId=(int)$case['id'];
            $orders=$this->many('SELECT id,installation_case_id,version_no,status,order_date,registration_number,control_engineer_user_id,control_engineer_fio_snapshot,control_engineer_position_snapshot,organization_form,object_address_snapshot,entrance_snapshot,object_registration_number_snapshot,planned_start_date_snapshot,planned_finish_date_snapshot FROM fm2_assignment_orders WHERE installation_case_id=? ORDER BY version_no DESC,id DESC',$caseId);
            $order=null;
            $highestVersion=null;
            if($orders!==[]){
                $highestVersion=self::positiveId($orders[0]['version_no']);
                if($highestVersion===null)throw new PilotHttpInfrastructureUnavailable();
                $highest=\array_values(\array_filter($orders,static fn(array $candidate):bool=>(string)$candidate['version_no']===(string)$highestVersion));
                $active=\array_values(\array_filter($orders,static fn(array $candidate):bool=>\in_array($candidate['status'],['prepared','registered'],true)));
                if(\count($highest)!==1||\count($active)!==1||!\in_array($highest[0]['status'],['prepared','registered'],true))throw new PilotHttpInfrastructureUnavailable();
                $order=$highest[0];
            }
            $opened=$case['actual_start_date']!==null||$case['opened_at']!==null||$case['opened_by_user_id']!==null;
            $allOpening=$case['actual_start_date']!==null&&$case['opened_at']!==null&&$case['opened_by_user_id']!==null;
            if($opened!==$allOpening)throw new PilotHttpInfrastructureUnavailable();
            if($allOpening&&(self::date($case['actual_start_date'])!==(string)$case['actual_start_date']||!self::rfc3339($case['opened_at'])||self::positiveId($case['opened_by_user_id'])===null))throw new PilotHttpInfrastructureUnavailable();
            $state=(string)$case['process_state'];
            $status=match($state){
                'needs_assignment_order'=>(!$opened&&$order===null)?'Требуется распоряжение':throw new PilotHttpInfrastructureUnavailable(),
                'assignment_order_prepared'=>(!$opened&&$order!==null&&$order['status']==='prepared')?'Распоряжение подготовлено':((!$opened&&$order!==null&&$order['status']==='registered')?'Готов к открытию':throw new PilotHttpInfrastructureUnavailable()),
                'working'=>( $allOpening&&$order!==null&&$order['status']==='registered')?'В работе':throw new PilotHttpInfrastructureUnavailable(),
                'needs_assignment_change'=>($allOpening&&$order!==null&&$order['status']==='registered')?'Требуется изменение':throw new PilotHttpInfrastructureUnavailable(),
                default=>throw new PilotHttpInfrastructureUnavailable(),
            };
            $renderedOrder=null;
            if($order!==null){
                foreach(['object_address_snapshot'=>'address','entrance_snapshot'=>'entrance','object_registration_number_snapshot'=>'registrationNumber','planned_start_date_snapshot'=>'plannedStartDate','planned_finish_date_snapshot'=>'plannedFinishDate'] as $snapshot=>$live)if((string)$order[$snapshot]!==$card[$live])throw new PilotHttpInfrastructureUnavailable();
                $engineerId=self::positiveId($order['control_engineer_user_id']);
                $registrationNumber=$order['registration_number'];
                $registrationNumberValid=$order['status']==='prepared'
                    ?$registrationNumber===null
                    :\is_string($registrationNumber)&&$registrationNumber!==''&&\trim($registrationNumber)===$registrationNumber;
                if(!\in_array($order['organization_form'],['individual','brigade'],true)||self::date($order['order_date'])!==(string)$order['order_date']||!$registrationNumberValid||$engineerId===null||\trim((string)$order['control_engineer_fio_snapshot'])===''||\trim((string)$order['control_engineer_position_snapshot'])==='')throw new PilotHttpInfrastructureUnavailable();
                $installers=$this->many('SELECT assignment_order_id,installer_tab_id,fio_snapshot,position_snapshot,employment_status_snapshot FROM fm2_order_installers WHERE assignment_order_id=? ORDER BY installer_tab_id ASC',(int)$order['id']);
                if($installers===[])throw new PilotHttpInfrastructureUnavailable();
                $renderedInstallers=[];
                foreach($installers as $installer){$tabId=self::positiveId($installer['installer_tab_id']);if($tabId===null||\trim((string)$installer['fio_snapshot'])===''||\trim((string)$installer['position_snapshot'])===''||!\in_array($installer['employment_status_snapshot'],['employed','dismissed'],true))throw new PilotHttpInfrastructureUnavailable();$renderedInstallers[]=['tabId'=>$tabId,'fullName'=>(string)$installer['fio_snapshot'],'position'=>(string)$installer['position_snapshot'],'status'=>(string)$installer['employment_status_snapshot']];}
                $renderedOrder=['version'=>$highestVersion,'status'=>$order['status'],'orderDate'=>$order['order_date'],'registrationNumber'=>$registrationNumber,'organizationType'=>$order['organization_form'],'engineer'=>['userId'=>$engineerId,'fullName'=>(string)$order['control_engineer_fio_snapshot'],'position'=>(string)$order['control_engineer_position_snapshot']],'installers'=>$renderedInstallers];
            }
            $events=$this->many('SELECT id,installation_case_id,event_type,occurred_at,actor_user_id FROM fm2_process_events WHERE installation_case_id=? ORDER BY id DESC LIMIT 3',$caseId);
            $renderedEvents=[];
            foreach($events as $event){$actorId=self::positiveId($event['actor_user_id']);if($actorId===null||\trim((string)$event['event_type'])===''||!self::rfc3339($event['occurred_at']))throw new PilotHttpInfrastructureUnavailable();$renderedEvents[]=['type'=>(string)$event['event_type'],'occurredAt'=>(string)$event['occurred_at'],'actorId'=>$actorId];}
            $pto=self::optionalLegacyDate($legacy['ptoactdate']);return $card+['status'=>$status,'order'=>$renderedOrder,'opened'=>$allOpening,'actualStartDate'=>$case['actual_start_date'],'openedAt'=>$case['opened_at'],'openedByUserId'=>$allOpening?self::positiveId($case['opened_by_user_id']):null,'events'=>$renderedEvents,'hasPtoAct'=>$pto!==null];
        }catch(PilotHttpInfrastructureUnavailable $e){throw $e;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function one(string $sql,int $id):array|null{$rows=$this->many($sql,$id);if(\count($rows)>1)throw new PilotHttpInfrastructureUnavailable();return $rows[0]??null;}
    private function many(string $sql,int $id):array{$s=$this->connection->prepare(\str_replace('fm2_',$this->processPrefix.'fm2_',$sql));$s->bind_param('i',$id);$s->execute();return $s->get_result()->fetch_all(MYSQLI_ASSOC);}
    private function legacyCard(array $r,int $id):array|null
    {
        $address=\trim((string)$r['ordadr_address']);$entrance=\trim((string)$r['entrance']);$registration=\trim((string)$r['regnumber']);
        $start=self::date($r['workdatestart']);$finish=self::date($r['workdateendadjusted'])??self::date($r['plan_finish_date']);
        if((int)$r['id']!==$id||$address===''||$entrance===''||$registration===''||$start===null||$finish===null)return null;
        return ['id'=>$id,'address'=>$address,'entrance'=>$entrance,'registrationNumber'=>$registration,'plannedStartDate'=>$start,'plannedFinishDate'=>$finish];
    }
    private static function date(mixed $value):?string{if(!\is_string($value)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$value,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $m[1].'-'.$m[2].'-'.$m[3];}
    private static function optionalLegacyDate(mixed $value):?string{if($value===null)return null;$value=\trim((string)$value);if($value===''||\preg_match('/^0+$/D',$value)===1||\str_starts_with($value,'0000-00-00'))return null;$date=self::date($value);if($date===null)throw new PilotHttpInfrastructureUnavailable();return $date;}
    private static function positiveId(mixed $value):?int{if(!\is_int($value)&&(!\is_string($value)||\preg_match('/^[1-9][0-9]*$/D',$value)!==1))return null;$id=\filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id;}
    private static function rfc3339(mixed $value):bool
    {
        if(!\is_string($value)||\preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2})(?:\.\d+)?(Z|[+-]\d{2}:\d{2})$/D',$value,$parts)!==1)return false;
        $canonical=$parts[1].($parts[2]==='Z'?'+00:00':$parts[2]);
        $parsed=\DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:sP',$canonical);
        $errors=\DateTimeImmutable::getLastErrors();
        return $parsed!==false&&($errors===false||($errors['warning_count']===0&&$errors['error_count']===0))&&$parsed->format('Y-m-d\TH:i:sP')===$canonical;
    }
}

final class MariaDbObjectListReader implements ObjectListReader
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix,private readonly string $processPrefix=''){}
    public function read():array
    {
        try{$rows=$this->connection->query("SELECT c.id AS case_id,c.legacy_installation_object_id,l.id AS legacy_id,l.ordadr_address,l.entrance,l.regnumber,l.workdatestart,l.workdateendadjusted,l.plan_finish_date FROM `{$this->processPrefix}fm2_installation_cases` c LEFT JOIN `{$this->prefix}fm_maintable` l ON l.id=c.legacy_installation_object_id LIMIT 501")->fetch_all(MYSQLI_ASSOC);}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
        if(\count($rows)>500)throw new PilotHttpInfrastructureUnavailable();
        $objects=[];$caseIds=[];$objectIds=[];
        foreach($rows as $row){$caseId=self::positiveId($row['case_id']);$id=self::positiveId($row['legacy_installation_object_id']);$legacyId=self::positiveId($row['legacy_id']);$address=\trim((string)$row['ordadr_address']);$entrance=\trim((string)$row['entrance']);$registration=\trim((string)$row['regnumber']);$start=self::date($row['workdatestart']);$finish=self::date($row['workdateendadjusted'])??self::date($row['plan_finish_date']);if($caseId===null||$id===null||$legacyId!==$id||isset($caseIds[$caseId])||isset($objectIds[$id])||$address===''||$entrance===''||$registration===''||$start===null||$finish===null)throw new PilotHttpInfrastructureUnavailable();$caseIds[$caseId]=true;$objectIds[$id]=true;$objects[]=['id'=>$id,'registrationNumber'=>$registration,'address'=>$address,'entrance'=>$entrance,'plannedStartDate'=>$start,'plannedFinishDate'=>$finish];}
        \usort($objects,static fn(array $a,array $b):int=>[$a['plannedStartDate'],$a['id']]<=>[$b['plannedStartDate'],$b['id']]);return $objects;
    }
    private static function date(mixed $value):?string{if(!\is_string($value)||\preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$value,$m)!==1||!\checkdate((int)$m[2],(int)$m[3],(int)$m[1])||$m[1]==='0000')return null;return $m[1].'-'.$m[2].'-'.$m[3];}
    private static function positiveId(mixed $value):?int{if(!\is_int($value)&&(!\is_string($value)||\preg_match('/^[1-9][0-9]*$/D',$value)!==1))return null;$id=\filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);return $id===false?null:$id;}
}

class PilotHttpCoordinator
{
    public function __construct(private TrustedServerIdentity $identity,private PilotShellRenderer $shell,private PilotHttpDependencies $dependencies,private ?ObjectCardRenderer $cards=null,private ?ObjectCardReaderProvider $cardReaders=null,private ?ObjectListRenderer $lists=null,private ?ObjectListReaderProvider $listReaders=null,private ?PrepareFormRenderer $prepareForms=null,private ?PrepareFormReaderProvider $prepareReaders=null){}
    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        $cardId=self::cardId($r->path);$prepareId=self::prepareId($r->path);
        if(!\in_array($r->path,['/pilot','/pilot/','/pilot/assets/shlz.css','/pilot/assets/pilot.css','/pilot/objects'],true)&&$cardId===null&&$prepareId===null)return $this->response(404,"Not found.\n");
        if(!\in_array($r->method,['GET','HEAD'],true))return $this->response(405,"Method not allowed.\n",['Allow'=>'GET, HEAD'],$r->method);
        if($r->path==='/pilot')return $this->response(308,'',['Location'=>'/pilot/'],$r->method);
        if($r->path==='/pilot/assets/shlz.css'){try{$body=$this->dependencies->css()->readBytes();return $this->response(200,$body,['Content-Type'=>'text/css; charset=UTF-8'],$r->method);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        if($r->path==='/pilot/assets/pilot.css'){$assetConfigured=$this->dependencies instanceof PilotUiConfiguration&&$this->dependencies->pilotUiConfigured();if(!$assetConfigured)return $this->response(404,"Not found.\n",[],$r->method);try{$body=$this->dependencies->pilotCss()->readBytes();return $this->response(200,$body,['Content-Type'=>'text/css; charset=UTF-8'],$r->method);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
        $configured=$this->dependencies instanceof PilotUiConfiguration&&$this->dependencies->pilotUiConfigured();
        try{$this->dependencies->css()->readBytes();if($configured)$this->dependencies->pilotCss()->readBytes();$user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);if($prepareId!==null){if($this->prepareReaders===null||$this->prepareForms===null)throw new PilotHttpInfrastructureUnavailable();if(!$this->prepareReaders->hasCapability($user->id,'assignment_order.prepare'))return $this->response(403,"Access denied.\n",[],$r->method);$form=$this->prepareReaders->prepareForms()->read($prepareId,$this->prepareReaders->processDate());if($form===null)return $this->response(404,"Not found.\n",[],$r->method);$html=!$configured&&$this->prepareForms instanceof CompatibilityPrepareFormRenderer?$this->prepareForms->renderCompatibility($user,$form):$this->prepareForms->render($user,$form);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}if($r->path==='/pilot/objects'){if($this->listReaders===null||$this->lists===null)throw new PilotHttpInfrastructureUnavailable();$objects=$this->listReaders->objectList()->read();$html=!$configured&&$this->lists instanceof CompatibilityObjectListRenderer?$this->lists->renderCompatibility($user,$objects):$this->lists->render($user,$objects);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}if($cardId!==null){if($this->cardReaders===null||$this->cards===null)throw new PilotHttpInfrastructureUnavailable();$card=$this->cardReaders->objectCards()->read($cardId);if($card===null)return $this->response(404,"Not found.\n",[],$r->method);$prepareConfigured=!($this->dependencies instanceof PilotUiConfiguration)||$this->dependencies->prepareCommandConfigured();$compatibility=$this->dependencies instanceof PilotUiConfiguration&&!$configured&&!$prepareConfigured;$hasPrepareCapability=false;if(!$compatibility){if($this->prepareReaders===null)throw new PilotHttpInfrastructureUnavailable();$hasPrepareCapability=$this->prepareReaders->hasCapability($user->id,'assignment_order.prepare');}$card['canPrepare']=$prepareConfigured&&$hasPrepareCapability;$html=!$configured&&$this->cards instanceof CompatibilityObjectCardRenderer?$this->cards->renderCompatibility($user,$card):$this->cards->render($user,$card);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}$html=!$configured&&$this->shell instanceof CompatibilityPilotShellRenderer?$this->shell->renderCompatibility($user):$this->shell->render($user);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}catch(PrepareFormUnavailable){return $this->response(409,"Формирование распоряжения недоступно для текущего состояния объекта монтажа.\n",[],$r->method);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
    }
    private static function cardId(string $path):?int
    {
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)$#D',$path,$m)!==1)return null;
        $digits=$m[1];if(\strlen($digits)>19||(\strlen($digits)===19&&\strcmp($digits,'9223372036854775807')>0))return null;
        return (int)$digits;
    }
    private static function prepareId(string $path):?int
    {
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D',$path,$m)!==1)return null;$digits=$m[1];if(\strlen($digits)>19||(\strlen($digits)===19&&\strcmp($digits,'9223372036854775807')>0))return null;return (int)$digits;
    }
    private function response(int $status,string $getBody,array $headers=[],string $method='GET'):PilotHttpResponse
    {
        $headers += [
            'Content-Type'=>'text/plain; charset=UTF-8',
            'X-Content-Type-Options'=>'nosniff',
            'Referrer-Policy'=>'no-referrer',
            'X-Frame-Options'=>'DENY',
            'Content-Security-Policy'=>"default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",
            'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()',
            'Cross-Origin-Opener-Policy'=>'same-origin',
            'Cache-Control'=>'no-store',
        ];
        $headers['Content-Length']=(string)\strlen($getBody);
        return new PilotHttpResponse($status,$headers,$method==='HEAD'?'':$getBody);
    }
}

final class PilotHttpRequestFactory
{
    public function fromServer(array $server):PilotHttpRequest
    {
        $host=PHP_SAPI==='cli-server'?($server['HTTP_HOST']??null):($server['FMONITOR_TRUSTED_REQUEST_HOST']??null);if(!self::validHost($host))throw new InvalidHttpRequest();
        $method=\strtoupper((string)($server['REQUEST_METHOD']??''));$uri=(string)($server['REQUEST_URI']??'');$raw=\explode('?',$uri,2)[0];$invalid=\preg_match('/%(?![0-9A-Fa-f]{2})/',$raw)===1||\preg_match('/%(?:2f|5c)/i',$raw)===1;$path=\rawurldecode($raw);$invalid=$invalid||\str_contains($path,"\0")||\str_contains($path,'\\')||\str_contains($path,'//')||(\str_starts_with($path,'/pilot/objects/')&&$raw!==$path);foreach(\explode('/',$path) as $s)if($s==='.'||$s==='..')$invalid=true;if($invalid){if(PHP_SAPI!=='cli-server')throw new InvalidHttpRequest();$path="\0invalid-path";}
        return new PilotHttpRequest($method,$path,$host,$server['REMOTE_USER']??null,$server,'');
    }

    private static function validHost(mixed $host):bool
    {
        if (!\is_string($host) || \strlen($host) < 1 || \strlen($host) > 253
            || \preg_match('/[\x00-\x20\x7f,\/\\\\@#?%]/', $host) === 1) return false;
        if (\str_starts_with($host, '[')) {
            if (\preg_match('/^\[([^\]]+)\](?::([0-9]+))?$/D', $host, $match) !== 1
                || \str_contains($match[1], '%') || @\inet_pton($match[1]) === false
                || \strlen((string)@\inet_pton($match[1])) !== 16) return false;
            return self::validPort($match[2] ?? null);
        }
        if (\substr_count($host, ':') > 1) return false;
        $parts=\explode(':',$host,2);$name=$parts[0];if(!self::validPort($parts[1]??null))return false;
        if(\preg_match('/^[0-9.]+$/D',$name)===1){$octets=\explode('.',$name);if(\count($octets)!==4)return false;foreach($octets as $octet)if(\preg_match('/^(?:0|[1-9][0-9]{0,2})$/D',$octet)!==1||(int)$octet>255)return false;return true;}
        foreach(\explode('.',$name) as $label)if(\preg_match('/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,61}[A-Za-z0-9])?$/D',$label)!==1)return false;
        return true;
    }

    private static function validPort(?string $port):bool{return $port===null||(\preg_match('/^[1-9][0-9]*$/D',$port)===1&&(int)$port<=65535);}
}

final class ProcessEnvironmentSource implements EnvironmentSource
{
    public function read(string $name):string|false{return \getenv($name);}
}

final class ProductionPilotHttpDependencies implements PilotHttpDependencies,ObjectCardReaderProvider,ObjectListReaderProvider,PrepareFormReaderProvider,PilotUiConfiguration
{
    private ?CssAsset $cssAsset=null;
    private ?CssAsset $pilotCssAsset=null;
    private ?HttpUserDirectory $userDirectory=null;
    private ?ObjectCardReader $objectCardReader=null;
    private ?ObjectListReader $objectListReader=null;
    private ?PrepareFormReader $prepareFormReader=null;
    private ?\mysqli $connection=null;
    private ?string $legacyTablePrefix=null;
    private string $processTablePrefix='';
    public function __construct(private readonly EnvironmentSource $environment,private readonly CssDescriptorOpener $cssDescriptors){}
    public function css():CssAsset
    {
        if($this->cssAsset!==null)return $this->cssAsset;
        $path=$this->environment->read('FMONITOR_SHLZ_CSS_PATH');
        if(!\is_string($path))throw new CssAssetUnavailable();
        return $this->cssAsset=new ShlzCssAsset($path,$this->cssDescriptors);
    }
    public function pilotCss():CssAsset
    {
        if($this->pilotCssAsset!==null)return $this->pilotCssAsset;
        $configured=$this->environment->read('FMONITOR_PILOT_CSS_PATH');if(!\is_string($configured))throw new CssAssetUnavailable();
        return $this->pilotCssAsset=new PilotCssAsset($configured,$this->cssDescriptors);
    }
    public function users():HttpUserDirectory
    {
        if($this->userDirectory!==null)return $this->userDirectory;
        $names=['FMONITOR_DB_HOST','FMONITOR_DB_PORT','FMONITOR_DB_NAME','FMONITOR_DB_USER','FMONITOR_DB_PASSWORD','FMONITOR_LEGACY_TABLE_PREFIX'];$v=[];
        foreach($names as $name)$v[$name]=$this->environment->read($name);
        if(!\is_string($v['FMONITOR_DB_HOST'])||!\is_string($v['FMONITOR_DB_PORT'])||!\is_string($v['FMONITOR_DB_NAME'])||!\is_string($v['FMONITOR_DB_USER'])||!\is_string($v['FMONITOR_DB_PASSWORD'])||!\is_string($v['FMONITOR_LEGACY_TABLE_PREFIX']))throw new PilotHttpInfrastructureUnavailable();
        $port=\preg_match('/^[1-9][0-9]*$/D',$v['FMONITOR_DB_PORT'])===1?(int)$v['FMONITOR_DB_PORT']:0;
        if($port<1||$port>65535||$v['FMONITOR_DB_HOST']===''||$v['FMONITOR_DB_NAME']===''||$v['FMONITOR_DB_USER']===''||\strlen($v['FMONITOR_LEGACY_TABLE_PREFIX'])>32||\preg_match('/^[A-Za-z0-9_]*$/D',$v['FMONITOR_LEGACY_TABLE_PREFIX'])!==1)throw new PilotHttpInfrastructureUnavailable();
        $this->legacyTablePrefix=$v['FMONITOR_LEGACY_TABLE_PREFIX'];
        try{$this->connection=@new \mysqli($v['FMONITOR_DB_HOST'],$v['FMONITOR_DB_USER'],$v['FMONITOR_DB_PASSWORD'],$v['FMONITOR_DB_NAME'],$port);if(!$this->connection->set_charset('utf8mb4'))throw new \RuntimeException();return $this->userDirectory=new MariaDbHttpUserDirectory($this->connection,$this->legacyTablePrefix);}catch(\Throwable $e){if($this->connection instanceof \mysqli)try{$this->connection->close();}catch(\Throwable){}$this->connection=null;$this->legacyTablePrefix=null;throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    public function objectCards():ObjectCardReader
    {
        if($this->objectCardReader!==null)return $this->objectCardReader;
        $this->users();
        if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();
        return $this->objectCardReader=new MariaDbObjectCardReader($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());
    }
    public function objectList():ObjectListReader
    {
        if($this->objectListReader!==null)return $this->objectListReader;$this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();return $this->objectListReader=new MariaDbObjectListReader($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());
    }
    public function prepareForms():PrepareFormReader
    {
        if($this->prepareFormReader!==null)return $this->prepareFormReader;$this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();return $this->prepareFormReader=new MariaDbPrepareFormReader($this->connection,$this->legacyTablePrefix,$this->resolveProcessPrefix());
    }
    public function hasCapability(int $userId,string $capability):bool
    {
        $this->users();$this->resolveProcessPrefix();try{$s=$this->connection->prepare("SELECT user_id FROM `{$this->processTablePrefix}fm2_process_user_capabilities` WHERE user_id=? AND capability=? LIMIT 2");$s->bind_param('is',$userId,$capability);$s->execute();return \count($s->get_result()->fetch_all(MYSQLI_ASSOC))===1;}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}
    }
    private function resolveProcessPrefix():string{$configured=$this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX');$this->processTablePrefix=\is_string($configured)?$configured:'';if(\strlen($this->processTablePrefix)>32||\preg_match('/^[A-Za-z0-9_]*$/D',$this->processTablePrefix)!==1)throw new PilotHttpInfrastructureUnavailable();return $this->processTablePrefix;}
    public function processDate():string{$v=$this->environment->read('FMONITOR_NOW');try{return new \DateTimeImmutable(\is_string($v)?$v:'now')->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d');}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}}
    public function commandResources():array
    {
        $this->users();if(!$this->connection instanceof \mysqli||$this->legacyTablePrefix===null)throw new PilotHttpInfrastructureUnavailable();
        $processPrefix=$this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX');$root=$this->environment->read('FMONITOR_ARTIFACT_STORAGE_ROOT');$now=$this->environment->read('FMONITOR_NOW');
        if(!\is_string($processPrefix)||!\is_string($root)||!\is_string($now))throw new PilotHttpInfrastructureUnavailable();
        return [$this->connection,$processPrefix,$this->legacyTablePrefix,$root,$now];
    }
    public function e2eConfigured():bool{return \is_string($this->environment->read('FMONITOR_ARTIFACT_STORAGE_ROOT'))&&\is_string($this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX'))&&\is_string($this->environment->read('FMONITOR_NOW'));}
    public function pilotUiConfigured():bool{return $this->environment instanceof ProcessEnvironmentSource&&\is_string($this->environment->read('FMONITOR_PILOT_CSS_PATH'));}
    public function prepareCommandConfigured():bool{if(!$this->environment instanceof ProcessEnvironmentSource)return false;try{$this->users();$prefix=$this->environment->read('FMONITOR_PROCESS_TABLE_PREFIX');$table=(\is_string($prefix)?$prefix:'').'fm2_process_user_capabilities';$q=$this->connection->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$q->bind_param('s',$table);$q->execute();return $q->get_result()->fetch_assoc()!==null;}catch(\Throwable){return false;}}
    public function close():void
    {
        $css=$this->cssAsset;$pilotCss=$this->pilotCssAsset;$this->cssAsset=null;$this->pilotCssAsset=null;$connection=$this->connection;$this->connection=null;$this->legacyTablePrefix=null;$this->userDirectory=null;$this->objectCardReader=null;$this->objectListReader=null;$this->prepareFormReader=null;$first=null;
        try{if($css!==null)$css->close();}catch(\Throwable $e){$first=$e;}
        try{if($pilotCss!==null)$pilotCss->close();}catch(\Throwable $e){$first??=$e;}
        try{if($connection instanceof \mysqli&&$connection->close()!==true)throw new \RuntimeException();}catch(\Throwable $e){$first??=$e;}
        if($first!==null)throw $first;
    }
}

final class MariaDbHttpUserDirectory implements HttpUserDirectory
{
    public function __construct(private readonly \mysqli $connection,private readonly string $prefix){}
    public function resolveActiveUser(string $principal):?HttpUser
    {
        try{$s=$this->connection->prepare("SELECT u.id,u.name,u.email FROM `{$this->prefix}users` u JOIN `{$this->prefix}users_roles` r ON r.id=u.role_id WHERE BINARY u.email=BINARY ? AND u.status=1 AND r.status=1 LIMIT 2");$s->bind_param('s',$principal);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);}catch(\Throwable $e){throw new PilotHttpInfrastructureUnavailable('',0,$e);}if(\count($rows)!==1)return null;$row=$rows[0];$id=\filter_var($row['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if($id===false||\trim((string)$row['name'])===''||$row['email']!==$principal)return null;return new HttpUser($id,(string)$row['name'],(string)$row['email']);
    }
}

final class ErrorLogUnexpectedFailureReporter implements UnexpectedFailureReporter
{
    public function report(string $category,string $correlationId):void{\error_log($category.' '.$correlationId);}
}

final class RandomCorrelationIdSource implements CorrelationIdSource
{
    public function nextId():string{return \bin2hex(\random_bytes(16));}
}

class PilotHttpGateway
{
    public function __construct(private readonly PilotHttpRequestFactory $requests,private readonly PilotHttpCoordinator $application,private readonly PilotHttpDependencies $dependencies,private readonly CorrelationIdSource $correlationIds,private readonly UnexpectedFailureReporter $failures){}
    public function handle(array $server):PilotHttpResponse
    {
        $correlationId='correlation-unavailable';$reported=false;$correlationValid=false;
        try{$candidate=$this->correlationIds->nextId();if(\preg_match('/^[A-Za-z0-9._-]{1,128}$/D',$candidate)!==1)throw new \RuntimeException();$correlationId=$candidate;$correlationValid=true;}
        catch(\Throwable){$response=self::failure(503,"Service unavailable.\n",['Retry-After'=>'60']);$reported=true;$this->guardedReport('pilot_http_correlation_failure',$correlationId);}
        if($correlationValid){try{$response=$this->application->handle($this->requests->fromServer($server));}catch(InvalidHttpRequest){$response=self::failure(400,"Bad request.\n");}catch(\Throwable){$response=self::failure(503,"Service unavailable.\n",['Retry-After'=>'60']);$reported=true;$this->guardedReport('pilot_http_unexpected_failure',$correlationId);}}
        try{$this->dependencies->close();}catch(\Throwable){$response=self::failure(503,"Service unavailable.\n",['Retry-After'=>'60']);if(!$reported)$this->guardedReport('pilot_http_unexpected_failure',$correlationId);}
        return $response;
    }
    private function guardedReport(string $category,string $correlationId):void{try{$this->failures->report($category,$correlationId);}catch(\Throwable){}}
    private static function failure(int $status,string $body,array $extra=[]):PilotHttpResponse
    {
        $headers=['Content-Type'=>'text/plain; charset=UTF-8','X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Content-Security-Policy'=>"default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store']+$extra;$headers['Content-Length']=(string)\strlen($body);return new PilotHttpResponse($status,$headers,$body);
    }
}

final class ProductionPilotHttpGatewayFactory
{
    public static function create(EnvironmentSource $environment):PilotHttpGateway
    {
        $closer=new NativePhpStreamCloser(new NativePhpFclosePrimitive());
        $dependencies=new ProductionPilotHttpDependencies($environment,new PhpCssDescriptorOpener($closer));
        $application=new PilotHttpCoordinator(new RemoteUserIdentity(),new ProductionPilotShellRenderer(),$dependencies,new ProductionObjectCardRenderer(),$dependencies,new ProductionObjectListRenderer(),$dependencies,new ProductionPrepareFormRenderer(),$dependencies);
        return new PilotHttpGateway(new PilotHttpRequestFactory(),$application,$dependencies,new RandomCorrelationIdSource(),new ErrorLogUnexpectedFailureReporter());
    }
}

require_once __DIR__.'/PilotView.php';
require_once __DIR__.'/PilotShellView.php';
require_once __DIR__.'/ObjectListView.php';
require_once __DIR__.'/ObjectCardView.php';
require_once __DIR__.'/PrepareFormView.php';
