<?php
declare(strict_types=1);
require_once __DIR__.'/UserAccessFixture.php';

/** Canonical private DB setup and independent observation; never implements scheduling. */
final class ObjectQueueFixture
{
    public UserAccessFixture $http;
    public mysqli $db;
    public string $p;
    public function __construct(public string $root)
    {
        $this->http=new UserAccessFixture($root);$this->db=$this->http->db;$this->p=$this->http->p;
        $this->db->query("CREATE TABLE fm_maintable(id BIGINT UNSIGNED PRIMARY KEY,ordadr_address VARCHAR(500),entrance VARCHAR(80),regnumber VARCHAR(120),workdatestart VARCHAR(40),workdateendadjusted VARCHAR(40),plan_finish_date VARCHAR(40)) ENGINE=InnoDB");
        foreach(['objects.read','inspection.schedule'] as $permission)$this->insert($this->p.'fm2_pilot_role_permissions',['role_id'=>9201,'permission'=>$permission]);
        $this->object(451201,6101,'working');$this->order(6111,6101,1,7299);$this->order(6112,6101,2,7301);
    }
    public function insert(string $table,array $row):void
    {
        $names=implode(',',array_map(static fn($v)=>'`'.$v.'`',array_keys($row)));
        $s=$this->db->prepare('INSERT INTO `'.$table.'`('.$names.') VALUES('.implode(',',array_fill(0,count($row),'?')).')');
        $values=array_values($row);$s->bind_param(str_repeat('s',count($row)),...$values);$s->execute();
    }
    public function object(int $object,int $case,string $state='needs_assignment_order',?string $start='2026-09-12',string $address='Синтетический адрес'):void
    {
        $this->insert('fm_maintable',['id'=>$object,'ordadr_address'=>$address,'entrance'=>'1','regnumber'=>'REG-'.$object,'workdatestart'=>$start,'plan_finish_date'=>'2026-12-01']);
        $opened=in_array($state,['working','needs_assignment_change'],true);
        $this->insert($this->p.'fm2_installation_cases',['id'=>$case,'legacy_installation_object_id'=>$object,'process_state'=>$state,'actual_start_date'=>$opened?'2026-09-01':null,'opened_at'=>$opened?'2026-09-01T09:00:00+03:00':null,'opened_by_user_id'=>$opened?9101:null,'created_at'=>'2026-09-01T09:00:00+03:00','updated_at'=>'2026-09-01T09:00:00+03:00','lock_version'=>0]);
        $payload=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>$object,'fields'=>[]],JSON_THROW_ON_ERROR);
        $this->insert($this->p.'fm2_pilot_object_details',['object_id'=>$object,'schema_version'=>'technical-object-detail-v1','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'captured_at'=>'2026-09-01T09:00:00Z']);
    }
    public function order(int $id,int $case,int $version,int $engineer,string $status='registered'):void
    {
        $this->insert($this->p.'fm2_assignment_orders',['id'=>$id,'installation_case_id'=>$case,'version_no'=>$version,'kind'=>'initial','status'=>$status,'order_date'=>'2026-09-01','control_engineer_user_id'=>$engineer,'control_engineer_fio_snapshot'=>'Инженер '.$engineer,'control_engineer_position_snapshot'=>'Инженер','organization_form'=>'individual','object_address_snapshot'=>'Синтетический адрес','entrance_snapshot'=>'1','object_registration_number_snapshot'=>'REG','planned_start_date_snapshot'=>'2026-09-01','planned_finish_date_snapshot'=>'2026-12-01','prepared_at'=>'2026-09-01T09:00:00+03:00','prepared_by_user_id'=>9101]);
    }
    public function completion(int $case,string $type):void
    {
        $this->insert($this->p.'fm2_pilot_completion_facts',['installation_case_id'=>$case,'fact_type'=>$type,'fact_date'=>'2026-09-10','details'=>$type==='declaration'?'Декларация теста':'','recorded_at'=>'2026-09-10T09:30:00+03:00','recorded_by_user_id'=>9101]);
    }
    public function checklist(int $case,int $item,int $revision,string $type='item_completed'):void
    {
        $this->insert($this->p.'fm2_checklist_operations',['installation_case_id'=>$case,'client_operation_id'=>sprintf('12345678-1234-4234-8234-%012d',$case*1000+$revision),'device_installation_id'=>'12345678-1234-4234-8234-000000000000','operation_type'=>$type,'section_id'=>1,'item_id'=>$item,'actor_user_id'=>9101,'device_time'=>'2026-09-10T09:30:00+03:00','server_received_at'=>'2026-09-10T09:30:00+03:00','base_revision'=>$revision-1,'accepted_revision'=>$revision,'payload_json'=>'{}']);
    }
    public function connection():yii\db\Connection
    {
        $e=$this->http->environment();return new yii\db\Connection(['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'],'username'=>$e['FMONITOR_DB_USER'],'password'=>$e['FMONITOR_DB_PASSWORD'],'charset'=>'utf8mb4']);
    }
    public function planning():object
    {
        assertSameValue(true,class_exists(FMonitor2\InstallationProcess\YiiInspectionPlanning::class),'INTENDED_RED YII2-OBJECT-QUEUE-001 planning owner absent');
        return FMonitor2\YiiRuntime\InstallationProcessFactory::planning($this->connection(),$this->p,static fn()=>new DateTimeImmutable('2026-09-10T09:30:00+03:00'));
    }
    public function queue():object
    {
        assertSameValue(true,class_exists(FMonitor2\InstallationProcess\YiiObjectQueue::class),'INTENDED_RED YII2-OBJECT-QUEUE-001 queue owner absent');
        return FMonitor2\YiiRuntime\InstallationProcessFactory::queue($this->connection(),$this->p,'');
    }
    public function rows(string $suffix):array{return $this->db->query('SELECT * FROM `'.$this->p.$suffix.'` ORDER BY 1')->fetch_all(MYSQLI_ASSOC);}
    public function facts():array
    {
        $out=[];$tables=$this->db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM);
        foreach($tables as[$table]){
            if(str_ends_with($table,'fm2_pilot_auth_attempts'))continue;
            $rows=$this->db->query('SELECT * FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC);
            usort($rows,static fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));
            $ddl=$this->db->query('SHOW CREATE TABLE `'.$table.'`')->fetch_row()[1];
            $out[$table]=[preg_replace('/ AUTO_INCREMENT=\d+/','',$ddl),$rows];
        }ksort($out);return$out;
    }
    public function noLegacy():void
    {
        $paths=json_decode((string)file_get_contents($this->http->artifacts.'/includes.json'),true,flags:JSON_THROW_ON_ERROR);
        foreach($paths as $path)assertSameValue(false,str_contains($path,'/rapid-pilot/')||str_contains($path,'/app/PilotHttp/'),'migrated request loads no legacy HTTP/domain adapter');
    }
    public function close():void{$this->http->close();}
}
