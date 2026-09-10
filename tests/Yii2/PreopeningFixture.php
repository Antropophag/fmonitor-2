<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/vendor/autoload.php';
require_once dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';
require_once dirname(__DIR__,2).'/app/AssignmentOrderOriginal/AssignmentOrderOriginalRuntime.php';

/** Private canonical fixture; SQL below is setup/audit, never a submitted command. */
final class PreopeningFixture
{
    public FMonitor2\Tests\Support\SelectedOriginalFixture $base;
    public mysqli $db;
    public string $p='jp_';
    public string $database;
    public string $artifacts;
    public string $dmlUser;
    public string $dmlPassword;
    public string $password='Correct preopening fixture 2026';
    public array $emails=[18=>'fkr.preopening@shlz.ru',73=>'engineer.preopening@shlz.ru',94=>'admin.preopening@shlz.ru',95=>'reader.preopening@shlz.ru',96=>'otiz.preopening@shlz.ru',97=>'manager.preopening@shlz.ru'];
    public ?array $server=null;
    private bool $ownsDmlUser=false;
    private string $traceNonce;

    public function __construct(public string $root)
    {
        $this->base=new FMonitor2\Tests\Support\SelectedOriginalFixture($this->p);
        try {
        $this->db=$this->base->selection->db;$this->database=$this->base->selection->schema->source->name;
        $this->artifacts=sys_get_temp_dir().'/yii-preopening-'.bin2hex(random_bytes(6));if(!mkdir($this->artifacts,0700))throw new TestFailure('SETUP_FAILURE artifacts');
        $migration=FMonitor2\InstallationProcess\CanonicalMigrationApplication::run($this->db,$this->p,FMonitor2\InstallationProcess\ProductionPilotMigrationCatalogue::migrations());
        assertSameValue(0,$migration['exitCode'],'canonical v24 preopening fixture');
        $p=$this->p;$now='2026-09-10T09:00:00+03:00';
        $this->db->query("UPDATE {$p}fm2_installation_cases SET id=6101 WHERE legacy_installation_object_id=4512");
        $this->traceNonce=bin2hex(random_bytes(16));
        $checklist='{"sections":[{"id":1,"name":"Монтаж","items":[{"id":28,"name":"Работа","weight":2}]}]}';
        $this->insert($p.'fm2_checklist_template_snapshots',['snapshot_version'=>'preopening-v1','captured_at'=>'2026-09-01 00:00:00','valid_from'=>'2026-09-01 00:00:00','validity_scope'=>'active_baseline_and_future_native_only','source_label'=>'synthetic preopening','content_sha256'=>hash('sha256',$checklist),'payload_json'=>$checklist,'created_at'=>'2026-09-01 00:00:00']);
        $this->db->query("UPDATE {$p}fm2_workforce_catalog SET reconciliation_state='delivered' WHERE installer_tab_id IN(7001,7002)");
        $this->db->query("UPDATE {$p}fm_maintable SET entrance='2',workdatestart='2026-10-01',plan_finish_date='2026-12-01',regnumber='TEST-4512' WHERE id=4512");
        foreach([[4,'access_administrator','Администратор'],[5,'user','Пользователь'],[6,'otiz_specialist','ОТиЗ'],[7,'manager','Руководитель ФКР']]as[$id,$code,$name])$this->insert($p.'fm2_pilot_roles',['role_id'=>$id,'code'=>$code,'name'=>$name,'description'=>'synthetic','status'=>1,'source_updated_at'=>$now]);
        foreach($this->emails as$id=>$email){
            if(in_array($id,[18,73],true)){$s=$this->db->prepare("UPDATE {$p}fm2_pilot_users SET email=? WHERE user_id=?");$s->execute([$email,$id]);}
            else{$this->insert($p.'fm2_pilot_users',['user_id'=>$id,'full_name'=>'Fixture '.$id,'email'=>$email,'status'=>1,'activation_state'=>'active','session_version'=>1,'source_updated_at'=>$now]);$this->insert($p.'fm2_pilot_user_roles',['user_id'=>$id,'role_id'=>$id-90,'origin'=>'fixture','assigned_at'=>$now]);}
            $this->insert($p.'fm2_pilot_auth_credentials',['user_id'=>$id,'email_normalized'=>$email,'password_hash'=>password_hash($this->password,PASSWORD_ARGON2ID),'password_set_at'=>$now,'updated_at'=>$now]);
        }
        $permissions=[1=>['objects.read','assignment_order.prepare','assignment_order.original.read','installation.open','assignment_order.composition.apply'],2=>['objects.read','assignment_order.original.read'],4=>['objects.read','access.administer'],5=>['objects.read'],6=>['objects.read','assignment_order.original.read','otiz.manage'],7=>['objects.read','assignment_order.prepare','assignment_order.composition.select','assignment_order.original.read','assignment_order.original.upload','assignment_order.original.correct','installation.open']];
        foreach($permissions as$role=>$caps)foreach($caps as$cap)$this->insert($p.'fm2_pilot_role_permissions',['role_id'=>$role,'permission'=>$cap]);
        $payload=json_encode(['schemaVersion'=>'technical-object-detail-v1','objectId'=>4512,'fields'=>['floors'=>['raw'=>'9','display'=>'9'],'weight'=>['raw'=>'630','display'=>'630']]],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        $this->insert($p.'fm2_pilot_object_details',['object_id'=>4512,'schema_version'=>'technical-object-detail-v1','content_sha256'=>hash('sha256',$payload),'payload_json'=>$payload,'captured_at'=>$now]);
        $this->dmlUser='ypj_'.bin2hex(random_bytes(5));$this->dmlPassword=bin2hex(random_bytes(20));
        $this->db->query("CREATE USER '{$this->dmlUser}'@'%' IDENTIFIED BY '{$this->dmlPassword}'");
        $this->ownsDmlUser=true;
        $this->db->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$this->database}`.* TO '{$this->dmlUser}'@'%'");
        file_put_contents($this->base->control.'/runtime-password',$this->dmlPassword);chmod($this->base->control.'/runtime-password',0600);
        foreach(['yii-sessions','yii-runtime','yii-runtime/logs']as$path)if(!is_dir($this->base->control.'/'.$path))mkdir($this->base->control.'/'.$path,0700);
        } catch(Throwable $error) {$this->close();throw $error;}
    }

    public function insert(string $table,array $row): void
    {
        $sql='INSERT INTO `'.$table.'`(`'.implode('`,`',array_keys($row)).'`) VALUES('.implode(',',array_fill(0,count($row),'?')).')';
        $this->db->prepare($sql)->execute(array_values($row));
    }

    public function environment(): array
    {
        return [
            'FMONITOR_DB_HOST'=>getenv('FMONITOR_TEST_DB_HOST')?:'127.0.0.1','FMONITOR_DB_PORT'=>getenv('FMONITOR_TEST_DB_PORT')?:'23306',
            'FMONITOR_DB_NAME'=>$this->database,'FMONITOR_DB_USER'=>$this->dmlUser,'FMONITOR_DB_PASSWORD'=>$this->dmlPassword,
            'FMONITOR_PROCESS_TABLE_PREFIX'=>$this->p,'FMONITOR_LEGACY_TABLE_PREFIX'=>$this->p,'FMONITOR_FRESH_ORDER_FLOW'=>'1',
            'FMONITOR_ARTIFACT_STORAGE_ROOT'=>$this->base->privateRoot,'FMONITOR_ORIGINAL_SAFE_LOG_FILE'=>$this->base->safeLog,
            'FMONITOR_ORIGINAL_DB_PASSWORD_FILE'=>$this->base->control.'/runtime-password',
            'FMONITOR_YII_SESSION_PATH'=>$this->base->control.'/yii-sessions','FMONITOR_YII_RUNTIME_PATH'=>$this->base->control.'/yii-runtime',
            'FMONITOR_YII_SESSION_COOKIE'=>'fm2yii_preopening','FMONITOR_YII_COOKIE_VALIDATION_KEY'=>str_repeat('preopening-key-',4),
            'FMONITOR_YII_IDENTITY_KEY'=>str_repeat('preopening-identity-',3),'FMONITOR_TRUSTED_REQUEST_SCHEME'=>'http',
            'FMONITOR_SHLZ_CSS_PATH'=>dirname($this->root).'/shlz-ui/packages/styles/dist/shlz.css',
        ];
    }

    public function start(array $overrides=[]): void
    {
        $socket=stream_socket_server('tcp://127.0.0.1:0',$error,$message);if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE listener');
        $address=stream_socket_get_name($socket,false);$port=(int)substr($address,strrpos($address,':')+1);fclose($socket);
        $env=getenv();foreach(array_keys($env)as$key)if(str_starts_with($key,'FMONITOR_'))unset($env[$key]);
        $env=array_replace($env,$this->environment(),$overrides,['FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port]);
        $router=$this->artifacts.'/router.php';$trace=$this->artifacts.'/includes.jsonl';
        file_put_contents($router,'<?php register_shutdown_function(static function(){file_put_contents('.var_export($trace,true).',json_encode(["nonce"=>'.var_export($this->traceNonce,true).',"method"=>$_SERVER["REQUEST_METHOD"],"uri"=>$_SERVER["REQUEST_URI"],"files"=>get_included_files()])."\n",FILE_APPEND|LOCK_EX);}); require '.var_export($this->root.'/public/yii.php',true).';');
        $process=proc_open([PHP_BINARY,'-d','display_errors=0','-d','post_max_size=24M','-S','127.0.0.1:'.$port,$router],[0=>['file','/dev/null','r'],1=>['file',$this->artifacts.'/server.log','a'],2=>['file',$this->artifacts.'/server.log','a']],$pipes,$this->root,$env);
        if(!is_resource($process))throw new TestFailure('SETUP_FAILURE server');$this->server=['process'=>$process,'port'=>$port];
        $deadline=microtime(true)+5;do{$s=@fsockopen('127.0.0.1',$port,$e,$m,.1);if(is_resource($s)){fclose($s);return;}usleep(20000);}while(microtime(true)<$deadline);
        throw new TestFailure('SETUP_FAILURE server readiness');
    }

    public function request(string $method,string $path,array $fields,array &$cookies,array $headers=[],?string $raw=null): array
    {
        $body=$raw??($fields===[]?'':http_build_query($fields));$lines=['Connection: close'];
        if($cookies!==[])$lines[]='Cookie: '.implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($cookies),$cookies));
        if($raw===null&&$body!=='')$lines[]='Content-Type: application/x-www-form-urlencoded';
        $lines=array_merge($lines,$headers);$context=stream_context_create(['http'=>['method'=>$method,'header'=>implode("\r\n",$lines),'content'=>$body,'ignore_errors'=>true,'follow_location'=>0,'timeout'=>20]]);
        $bytes=file_get_contents('http://127.0.0.1:'.$this->server['port'].$path,false,$context);$response=$http_response_header??[];$out=[];
        foreach(array_slice($response,1)as$line){$at=strpos($line,':');if($at!==false)$out[strtolower(substr($line,0,$at))][]=trim(substr($line,$at+1));}
        foreach($out['set-cookie']??[]as$cookie)if(preg_match('/^([^=;]+)=([^;]*)/',$cookie,$m))$cookies[$m[1]]=$m[2];
        preg_match('#^HTTP/\S+ (\d+)#',$response[0]??'',$m);return['status'=>(int)($m[1]??0),'headers'=>$out,'body'=>(string)$bytes];
    }

    public function wire(string $method,string $path,array $headers,string $body,array $cookies): array
    {
        $socket=fsockopen('127.0.0.1',$this->server['port'],$errno,$error,3);
        if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE raw socket');
        stream_set_timeout($socket,10);
        $lines=[$method.' '.$path.' HTTP/1.1','Host: 127.0.0.1:'.$this->server['port'],'Connection: close'];
        if($cookies!==[])$lines[]='Cookie: '.implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($cookies),$cookies));
        $request=implode("\r\n",array_merge($lines,$headers))."\r\n\r\n".$body;
        try {
            $sent=0;while($sent<strlen($request)){$n=fwrite($socket,substr($request,$sent));if(!is_int($n)||$n<1)throw new TestFailure('SETUP_FAILURE raw send');$sent+=$n;}
            stream_socket_shutdown($socket,STREAM_SHUT_WR);$reply=stream_get_contents($socket);$timedOut=stream_get_meta_data($socket)['timed_out'];
        }finally{fclose($socket);}
        preg_match('#^HTTP/\S+ (\d+)#',(string)$reply,$m);return ['status'=>(int)($m[1]??0),'reply'=>(string)$reply,'timedOut'=>$timedOut];
    }

    public function csrf(string $html): string
    {
        assertSameValue(1,preg_match('/name="_csrf" value="([^"]+)"/',$html,$m),'native CSRF field');return html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
    }
    public function login(array &$cookies,int $actor=18): array
    {
        $form=$this->request('GET','/pilot/login',[],$cookies);assertSameValue(200,$form['status'],'native login form');
        return $this->request('POST','/pilot/login',['_csrf'=>$this->csrf($form['body']),'email'=>$this->emails[$actor],'password'=>$this->password],$cookies);
    }
    public function nativeOriginal(string $date='2026-09-01'): FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalResult
    {
        return $this->base->app()->submitAssignmentOrderOriginal(new FMonitor2\AssignmentOrderOriginal\SubmitAssignmentOrderOriginalCommand('22222222-2222-4222-8222-000000000001',FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMode::INITIAL,6101,81,18,$date,true,null,null,null,null,new FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalUpload(new FMonitor2\AssignmentOrderOriginal\AssignmentOrderOriginalMemoryStream(FMonitor2\Tests\Support\SelectedOriginalFixture::pdf()),'signed.pdf','application/pdf')));
    }
    public function token(array &$cookies): string
    {
        $page=$this->request('GET','/pilot/objects',[],$cookies);assertSameValue(200,$page['status'],'existing queue CSRF source');return $this->csrf($page['body']);
    }
    public function form(string $path,array $fields,array &$cookies): array
    {
        $ids=$fields['installerTabIds']??null;unset($fields['installerTabIds']);$body=http_build_query($fields);
        if(is_array($ids))foreach($ids as$id)$body.='&installerTabIds%5B%5D='.rawurlencode((string)$id);
        return $this->request('POST',$path,[],$cookies,['Content-Type: application/x-www-form-urlencoded'],$body);
    }
    public function selection(array &$cookies,string $request='11111111-1111-4111-8111-000000000001',array $ids=[7001],string $mode='new_order',int $revision=0): array
    {
        return $this->form('/pilot/objects/4512/assignment-order/selection',['_csrf'=>$this->token($cookies),'requestId'=>$request,'mode'=>$mode,'expectedSelectionRevision'=>(string)$revision,'controlEngineerUserId'=>'73','controlEngineerConfirmed'=>'yes','installerTabIds'=>$ids],$cookies);
    }
    public function metadata(array &$cookies,string $request='22222222-2222-4222-8222-000000000001'): array
    {
        return ['csrfToken'=>$this->token($cookies),'requestId'=>$request,'mode'=>'initial','documentDate'=>'2026-09-01','compositionConfirmed'=>true,'rootOriginalId'=>null,'targetRevisionId'=>null,'expectedCurrentRevisionId'=>null,'correctionReason'=>null,'originalFilename'=>'signed.pdf'];
    }
    public function upload(array &$cookies,array $metadata,?string $bytes=null,int $order=81,int $object=4512,array $headers=[]): array
    {
        $bytes??=FMonitor2\Tests\Support\SelectedOriginalFixture::pdf();$json=json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_LINE_TERMINATORS|JSON_THROW_ON_ERROR);
        return $this->request('POST',"/pilot/objects/$object/assignment-orders/$order/originals",[],$cookies,array_merge(['Content-Type: application/pdf','X-CSRF-Token: '.$metadata['csrfToken'],'X-FMonitor-Original: '.base64_encode($json)],$headers),$bytes);
    }
    public function rows(string $suffix): array
    {
        return $this->db->query('SELECT * FROM `'.$this->p.$suffix.'` ORDER BY 1')->fetch_all(MYSQLI_ASSOC);
    }
    public function facts(): array
    {
        $out=[];foreach($this->db->query('SHOW TABLES')->fetch_all(MYSQLI_NUM)as[$table]){
            if(str_ends_with($table,'fm2_pilot_auth_attempts'))continue;
            $rows=$this->db->query("SELECT * FROM `$table`")->fetch_all(MYSQLI_ASSOC);usort($rows,static fn($a,$b)=>strcmp(json_encode($a),json_encode($b)));
            $ddl=$this->db->query("SHOW CREATE TABLE `$table`")->fetch_row()[1];$out[$table]=[preg_replace('/ AUTO_INCREMENT=\d+/','',$ddl),$rows];
        }ksort($out);return$out;
    }
    public function noLegacy(): void
    {
        $lines=file($this->artifacts.'/includes.jsonl',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);assertSameValue(true,is_array($lines)&&$lines!==[],'observed real requests');
        foreach($lines as$line){$record=json_decode($line,true,flags:JSON_THROW_ON_ERROR);assertSameValue($this->traceNonce,$record['nonce'],'request trace belongs to fixture');foreach($record['files']as$path)assertSameValue(false,str_contains($path,'/rapid-pilot/')||str_contains($path,'/app/PilotHttp/'),'every Yii request loads no old layer: '.$record['method'].' '.$record['uri']);}
    }
    public function close(): void
    {
        if($this->server!==null){proc_terminate($this->server['process']);proc_close($this->server['process']);$this->server=null;}
        try {if($this->ownsDmlUser){$this->db->query("DROP USER '{$this->dmlUser}'@'%'");$this->ownsDmlUser=false;}}finally{$this->base->close();}
    }
}
