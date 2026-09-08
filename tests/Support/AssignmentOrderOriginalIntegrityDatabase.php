<?php

declare(strict_types=1);
namespace FMonitor2\Tests\Support;
use FMonitor2\AssignmentOrderOriginal as O;
use FMonitor2\InstallationProcess as I;

/** Synthetic task-owned database only. Expected facts are seeded without calling the repository under test. */
final class OriginalIntegrityDatabase
{
    public string $database;public string $prefix='data_';public ?\mysqli $db=null;
    public string $control='';public string $privateRoot='';public string $passwordFile='';public string $safeLog='';
    private ?\mysqli $admin=null;private bool $created=false;private int $report;private ?array $controlIdentity=null;
    public string $host;public int $port;public string $user;private string $password;
    public const ORIGINAL_TABLES=['fm2_assignment_order_original_roots','fm2_assignment_order_original_revisions','fm2_assignment_order_original_requests','fm2_assignment_order_original_events','fm2_assignment_order_original_audits','fm2_assignment_order_original_maintenance_requests','fm2_assignment_order_original_maintenance_audits'];
    public function __construct()
    {
        $this->report=(new \mysqli_driver())->report_mode;\mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);
        $this->host=getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1';$this->port=(int)(getenv('FMONITOR_TEST_DB_PORT')?:23306);
        $this->user=getenv('FMONITOR_TEST_DB_ADMIN_USER')?:'root';$this->password=getenv('FMONITOR_TEST_DB_ADMIN_PASSWORD')?:'fmonitor2_test_root_local';
        $token=bin2hex(random_bytes(6));$this->database='t_aoou_integrity_'.$token;
        try{
            $this->admin=new \mysqli($this->host,$this->user,$this->password,'',$this->port);$this->admin->query('SET SESSION lock_wait_timeout=3');
            $this->admin->query('CREATE DATABASE `'.$this->database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');$this->created=true;
            $this->db=$this->connect();
            $path=sys_get_temp_dir().'/aoou-integrity-'.$token;if(!mkdir($path,0700))throw new \RuntimeException('fixture directory');
            $this->control=(string)realpath($path);$stat=lstat($this->control);$this->controlIdentity=[$stat['dev'],$stat['ino']];$this->privateRoot=$this->control.'/private';mkdir($this->privateRoot,0700);
            $this->passwordFile=$this->control.'/password';$this->safeLog=$this->control.'/safe.log';
            file_put_contents($this->passwordFile,$this->password."\n");chmod($this->passwordFile,0600);file_put_contents($this->safeLog,'');chmod($this->safeLog,0600);
            I\ProductionProcessSchemaMigration::apply($this->db,$this->prefix);I\IdentityAccessSchemaMigration::apply($this->db,$this->prefix);
            I\ProcessUserCapabilitiesSchemaMigration::apply($this->db,$this->prefix);I\ProcessCommandCapabilitiesSchemaMigration::apply($this->db,$this->prefix);
            O\AssignmentOrderOriginalSchemaMigration::apply($this->db,$this->prefix);O\AssignmentOrderOriginalVerificationDatabaseFixture::seedExampleA($this->db,$this->prefix);
        }catch(\Throwable $e){$this->close();throw $e;}
    }
    public function connect(string $mysqliClass=\mysqli::class):\mysqli{if(!is_a($mysqliClass,\mysqli::class,true))throw new \LogicException('non-mysqli fixture connection');$db=new $mysqliClass($this->host,$this->user,$this->password,$this->database,$this->port);$db->set_charset('utf8mb4');$db->query('SET SESSION innodb_lock_wait_timeout=3');$db->query('SET SESSION lock_wait_timeout=3');return $db;}
    public function assertSelectedDatabase():void
    {if(!$this->created||$this->db===null||($this->db->query('SELECT DATABASE() selected_database')->fetch_assoc()['selected_database']??null)!==$this->database)throw new \RuntimeException('refuse non-fixture database');}
    public function q(mixed $value):string{return $value===null?'NULL':"'".$this->db->real_escape_string((string)$value)."'";}
    public function insert(string $table,array $row):void
    {
        $this->assertSelectedDatabase();if(!in_array($table,self::ORIGINAL_TABLES,true))throw new \LogicException('non-fixture table');
        $columns=implode(',',array_map(static fn($key)=>'`'.$key.'`',array_keys($row)));$values=implode(',',array_map($this->q(...),array_values($row)));
        $this->db->query('INSERT INTO `'.$this->prefix.$table.'`('.$columns.') VALUES('.$values.')');
    }
    public static function sqlTime(string $at):string{return str_replace(['T','Z'],[' ','.000000'],$at);}
    public function seedAccepted(array $changes=[]):array
    {
$this->assertSelectedDatabase();$v=OriginalIntegrityCommits::acceptedValues($changes);$at=self::sqlTime($v['uploadedAt']);$initial=$v['mode']===O\AssignmentOrderOriginalMode::INITIAL;
        if($initial)$this->insert(self::ORIGINAL_TABLES[0],['root_original_id'=>$v['rootOriginalId'],'installation_case_id'=>$v['installationCaseId'],'assignment_order_id'=>$v['assignmentOrderId'],'current_revision_id'=>$v['newRevisionId'],'composition_identity'=>$v['compositionIdentity'],'composition_sha256'=>$v['compositionSha256'],'created_at_utc'=>$at]);
        else $this->db->query('UPDATE `'.$this->prefix.self::ORIGINAL_TABLES[0].'` SET current_revision_id='.$this->q($v['newRevisionId']).' WHERE root_original_id='.$this->q($v['rootOriginalId']));
        $this->insert(self::ORIGINAL_TABLES[1],['revision_id'=>$v['newRevisionId'],'root_original_id'=>$v['rootOriginalId'],'revision_number'=>$v['newRevisionNumber'],'previous_revision_id'=>$v['previousRevisionId'],'document_date'=>$v['documentDate'],'uploaded_at_utc'=>$at,'actor_user_id'=>$v['actorUserId'],'pdf_sha256'=>$v['pdfSha256'],'byte_size'=>$v['byteSize'],'private_content_identity'=>$v['privateContentIdentity'],'correction_reason'=>$v['correctionReason'],'request_id'=>$v['requestId'],'operation_fingerprint'=>$v['fingerprint'],'event_type'=>$v['domainEventType']]);
        $this->insert(self::ORIGINAL_TABLES[2],['request_id'=>$v['requestId'],'mode'=>$v['mode']->value,'installation_case_id'=>$v['installationCaseId'],'assignment_order_id'=>$v['assignmentOrderId'],'actor_identity'=>(string)$v['actorUserId'],'status'=>'accepted','reason_code'=>null,'retryable'=>0,'root_original_id'=>$v['rootOriginalId'],'current_revision_id'=>$v['newRevisionId'],'revision_number'=>$v['newRevisionNumber'],'document_date'=>$v['documentDate'],'sha256'=>$v['pdfSha256'],'byte_size'=>$v['byteSize'],'uploaded_at_utc'=>$at,'attempted_at_utc'=>$at]);
        $this->insert(self::ORIGINAL_TABLES[3],['event_type'=>$v['domainEventType'],'installation_case_id'=>$v['installationCaseId'],'assignment_order_id'=>$v['assignmentOrderId'],'root_original_id'=>$v['rootOriginalId'],'revision_id'=>$v['newRevisionId'],'occurred_at_utc'=>$at,'actor_user_id'=>$v['actorUserId']]);
        $this->insert(self::ORIGINAL_TABLES[4],['request_id'=>$v['requestId'],'actor_identity'=>(string)$v['actorUserId'],'mode'=>$v['mode']->value,'installation_case_id'=>$v['installationCaseId'],'assignment_order_id'=>$v['assignmentOrderId'],'status'=>'accepted','reason_code'=>null,'attempted_at_utc'=>$at]);
        return $v;
    }
    public function seedRejected(string $reason='invalid_pdf'):void
    {
        $base=['request_id'=>'00000000-0000-4000-8000-000000000301','actor_identity'=>'18','mode'=>'initial','installation_case_id'=>4512,'assignment_order_id'=>81,'status'=>'rejected','reason_code'=>$reason,'attempted_at_utc'=>'2026-09-02 09:15:30.000000'];
        $this->insert(self::ORIGINAL_TABLES[2],$base+['retryable'=>0]);$this->insert(self::ORIGINAL_TABLES[4],$base);
    }
    public function rows(string $table):array
    {$rows=$this->db->query('SELECT * FROM `'.$this->prefix.$table.'`')->fetch_all(MYSQLI_ASSOC);usort($rows,static fn($a,$b)=>strcmp(json_encode($a,JSON_THROW_ON_ERROR),json_encode($b,JSON_THROW_ON_ERROR)));return $rows;}
    public function facts():string
    {$all=[];foreach(array_merge(self::ORIGINAL_TABLES,['fm2_assignment_orders','fm2_order_installers','fm2_installation_cases','fm2_process_tasks']) as $table)$all[$table]=$this->rows($table);return json_encode($all,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
    public function catalog():string
    {
        $out=[];foreach(self::ORIGINAL_TABLES as $name){$table=$this->prefix.$name;$out[$name]=['columns'=>$this->db->query('SHOW FULL COLUMNS FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC),'indexes'=>$this->db->query('SHOW INDEX FROM `'.$table.'`')->fetch_all(MYSQLI_ASSOC)];foreach($out[$name]['indexes'] as &$index)unset($index['Cardinality']);unset($index);}
        return json_encode($out,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }
    public function corrupt(callable $change):void
    {
        $this->assertSelectedDatabase();$prior=$this->db->query('SELECT @@SESSION.foreign_key_checks f,@@SESSION.check_constraint_checks c,@@SESSION.sql_mode m')->fetch_assoc();
        $this->db->query('SET SESSION foreign_key_checks=0');$this->db->query('SET SESSION check_constraint_checks=0');$this->db->query("SET SESSION sql_mode=''");
        try{$change($this->db);}finally{$this->db->query('SET SESSION foreign_key_checks='.(int)$prior['f']);$this->db->query('SET SESSION check_constraint_checks='.(int)$prior['c']);$this->db->query('SET SESSION sql_mode='.$this->q($prior['m']));}
    }
    public function resetOriginals():void
    {$this->corrupt(function(){foreach(array_reverse(self::ORIGINAL_TABLES) as $table)$this->db->query('DELETE FROM `'.$this->prefix.$table.'`');});}
    private function removeTree(string $directory):void
    {
        if(is_link($directory)||!str_starts_with($directory.'/', $this->control.'/'))throw new \RuntimeException('refuse unowned directory');
        foreach(scandir($directory) as $name){if($name==='.'||$name==='..')continue;$path=$directory.'/'.$name;if(is_link($path))throw new \RuntimeException('refuse fixture symlink');if(is_dir($path))$this->removeTree($path);elseif(is_file($path))unlink($path);else throw new \RuntimeException('refuse special fixture file');}
        rmdir($directory);
    }
    public function close():void
    {
        if($this->db!==null){try{$this->db->close();}catch(\Throwable){}$this->db=null;}
        if($this->admin!==null){if($this->created){if(preg_match('/^t_aoou_integrity_[0-9a-f]{12}$/D',$this->database)!==1)throw new \RuntimeException('refuse unowned cleanup');$this->admin->query('DROP DATABASE `'.$this->database.'`');$this->created=false;}$this->admin->close();$this->admin=null;}
        if($this->control!==''&&preg_match('~/aoou-integrity-[0-9a-f]{12}$~D',$this->control)===1&&is_dir($this->control)){
            $stat=lstat($this->control);if(is_link($this->control)||[$stat['dev'],$stat['ino']]!==$this->controlIdentity)throw new \RuntimeException('refuse replaced fixture directory');
            $this->removeTree($this->control);
        }
        \mysqli_report($this->report);
    }
}
