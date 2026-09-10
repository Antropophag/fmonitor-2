<?php
declare(strict_types=1);
require_once __DIR__.'/Yii2AuthFixture.php';
require_once dirname(__DIR__,2).'/vendor/autoload.php';
require_once dirname(__DIR__,2).'/vendor/yiisoft/yii2/Yii.php';

/** Isolated fixture; writes below are setup/fault controls, never the tested operation. */
final class UserAccessFixture
{
    public FMonitor2\Tests\Yii2\Yii2AuthFixture $auth;
    public mysqli $db;
    public string $p;
    public string $artifacts;
    public string $dmlUser;
    public string $dmlPassword;
    public ?array $server=null;
    public function __construct(public string $root)
    {
        $this->auth=new FMonitor2\Tests\Yii2\Yii2AuthFixture($root);
        $this->db=$this->auth->db;$this->p=$this->auth->prefix;
        $this->artifacts=sys_get_temp_dir().'/yii-user-access-'.bin2hex(random_bytes(6));mkdir($this->artifacts,0700);
        $p=$this->p;
        foreach([[9210,'user','Пользователь',1],[9211,'superadministrator','Суперадминистратор',1],[9212,'control_engineer','Инженер',1],[9213,'archived','Архивная роль',0]]as[$id,$code,$name,$status])$this->db->query("INSERT INTO {$p}fm2_pilot_roles(role_id,code,name,description,status,source_updated_at) VALUES($id,'$code','$name','fixture',$status,'2026-09-10T03:00:00+03:00')");
        $this->db->query("INSERT INTO {$p}fm2_pilot_role_permissions(role_id,permission) VALUES(9211,'access.administer'),(9211,'access.superadminister'),(9212,'checklist.edit'),(9213,'otiz.manage')");
        foreach([[9401,'ordinary.person@shlz.ru'],[9402,'super.person@shlz.ru'],[9403,'other.person@shlz.ru']]as[$id,$email]){
            $s=$this->db->prepare("INSERT INTO {$p}fm2_pilot_users(user_id,full_name,email,phone,status,activation_state,session_version,source_updated_at) VALUES(?, ?,?,'',1,'active',1,'2026-09-10T03:00:00+03:00')");$name='Fixture '.$id;$s->bind_param('iss',$id,$name,$email);$s->execute();
            $hash=$this->auth->passwordHash;$s=$this->db->prepare("INSERT INTO {$p}fm2_pilot_auth_credentials(user_id,email_normalized,password_hash,password_set_at,updated_at) VALUES(?,?,?,'2026-09-10','2026-09-10')");$s->bind_param('iss',$id,$email,$hash);$s->execute();
            $role=$id===9402?9211:9210;$this->db->query("INSERT INTO {$p}fm2_pilot_user_roles(user_id,role_id,origin,assigned_at,assigned_by_user_id) VALUES($id,$role,'fixture','2026-09-10',9101)");
        }
        $this->dmlUser='yua_'.bin2hex(random_bytes(5));$this->dmlPassword=bin2hex(random_bytes(20));
        $this->db->query("CREATE USER '{$this->dmlUser}'@'%' IDENTIFIED BY '{$this->dmlPassword}'");
        $this->db->query("GRANT SELECT,INSERT,UPDATE,DELETE ON `{$this->auth->database}`.* TO '{$this->dmlUser}'@'%'");
    }
    public function environment():array{return array_replace($this->auth->environment(),['FMONITOR_DB_USER'=>$this->dmlUser,'FMONITOR_DB_PASSWORD'=>$this->dmlPassword]);}
    public function owner():object
    {
        assertSameValue(true,class_exists(FMonitor2\IdentityAccess\YiiUserAccess::class),'INTENDED_RED YII2-USER-ACCESS-001 application owner absent');
        $e=$this->environment();$db=new yii\db\Connection(['dsn'=>'mysql:host='.$e['FMONITOR_DB_HOST'].';port='.$e['FMONITOR_DB_PORT'].';dbname='.$e['FMONITOR_DB_NAME'],'username'=>$this->dmlUser,'password'=>$this->dmlPassword,'charset'=>'utf8mb4']);
        return new FMonitor2\IdentityAccess\YiiUserAccess(['db'=>$db,'tablePrefix'=>$this->p]);
    }
    public function rows(string $name):array{return $this->db->query("SELECT * FROM {$this->p}{$name} ORDER BY 1,2")->fetch_all(MYSQLI_ASSOC);}
    public function facts():array{$result=[];foreach(['users','auth_credentials','roles','user_roles','role_permissions','invitations','user_role_events','user_status_events']as$n)$result[$n]=$this->rows('fm2_pilot_'.$n);return$result;}
    public function user(int $id):array{$s=$this->db->prepare("SELECT * FROM {$this->p}fm2_pilot_users WHERE user_id=?");$s->bind_param('i',$id);$s->execute();return$s->get_result()->fetch_assoc()??[];}
    public function start():void
    {
        $listener=stream_socket_server('tcp://127.0.0.1:0',$error,$message);if(!is_resource($listener))throw new TestFailure('SETUP_FAILURE: listener');$address=(string)stream_socket_get_name($listener,false);$port=(int)substr($address,strrpos($address,':')+1);fclose($listener);
        $env=getenv();foreach(array_keys($env)as$key)if(str_starts_with((string)$key,'FMONITOR_'))unset($env[$key]);$env=array_replace($env,$this->environment(),['FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port]);
        $trace=$this->artifacts.'/includes.json';$entry=$this->root.'/public/yii.php';$router=$this->artifacts.'/router.php';
        file_put_contents($router,'<?php register_shutdown_function(static function(){file_put_contents('.var_export($trace,true).',json_encode(get_included_files()));}); require '.var_export($entry,true).';');
        $process=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,$router],[0=>['file','/dev/null','r'],1=>['file',$this->artifacts.'/server.log','a'],2=>['file',$this->artifacts.'/server.log','a']],$pipes,$this->root,$env);
        if(!is_resource($process))throw new TestFailure('SETUP_FAILURE: server');$this->server=['process'=>$process,'port'=>$port];
        $deadline=microtime(true)+5;do{$s=@fsockopen('127.0.0.1',$port,$code,$message,.1);if(is_resource($s)){fclose($s);return;}usleep(20000);}while(microtime(true)<$deadline);throw new TestFailure('SETUP_FAILURE: server startup');
    }
    public function request(string $method,string $path,array $fields,array &$cookies,array $extra=[]):array
    {
        $body=$fields===[]?'':http_build_query($fields);$h=['Connection: close'];if($cookies!==[])$h[]='Cookie: '.implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($cookies),$cookies));if($body!=='')$h[]='Content-Type: application/x-www-form-urlencoded';$h=array_merge($h,$extra);
        $ctx=stream_context_create(['http'=>['ignore_errors'=>true,'follow_location'=>0,'timeout'=>8,'method'=>$method,'header'=>implode("\r\n",$h),'content'=>$body]]);
        $bytes=file_get_contents('http://127.0.0.1:'.$this->server['port'].$path,false,$ctx);$raw=$http_response_header??[];$headers=[];
        foreach(array_slice($raw,1)as$l){$at=strpos($l,':');if($at!==false)$headers[strtolower(substr($l,0,$at))][]=trim(substr($l,$at+1));}
        foreach($headers['set-cookie']??[]as$c)if(preg_match('/^([^=;]+)=([^;]*)/',$c,$m)){$cookies[$m[1]]=$m[2];}
        preg_match('#^HTTP/\S+ (\d+)#',$raw[0]??'',$m);return['status'=>(int)($m[1]??0),'headers'=>$headers,'body'=>(string)$bytes];
    }
    public function csrf(string $html):string{if(!preg_match('/name="_csrf" value="([^"]+)"/',$html,$m))throw new TestFailure('rendered form has no Yii CSRF');return html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5,'UTF-8');}
    public function login(array &$cookies,?string $email=null,?string $password=null):array{$form=$this->request('GET','/pilot/login',[],$cookies);assertSameValue(200,$form['status'],'login form');return$this->request('POST','/pilot/login',['_csrf'=>$this->csrf($form['body']),'email'=>$email??$this->auth->email,'password'=>$password??$this->auth->password],$cookies);}
    public function page(array &$cookies):array{return$this->request('GET','/pilot/admin/users',[],$cookies);}
    public function post(string $path,array $fields,array &$cookies):array{$form=$this->page($cookies);assertSameValue(200,$form['status'],'working admin return page');return$this->request('POST',$path,['_csrf'=>$this->csrf($form['body'])]+$fields,$cookies);}
    public function noLegacy():void{$paths=json_decode((string)file_get_contents($this->artifacts.'/includes.json'),true,flags:JSON_THROW_ON_ERROR);foreach($paths as$p){assertSameValue(false,str_contains($p,'/rapid-pilot/'),'Yii route has no rapid runtime load');assertSameValue(false,preg_match('#/app/PilotHttp/(?:PilotUser|PilotSession|MariaDbPilotUserDirectory|MariaDbLocalAuthRepository|MariaDbUserStatusApplication)#',$p)===1,'no old HTTP/auth/application adapter on migrated request');}}
    public function close():void{if($this->server!==null){proc_terminate($this->server['process']);proc_close($this->server['process']);$this->server=null;}$this->db->query("DROP USER IF EXISTS '{$this->dmlUser}'@'%'");$this->auth->close();}
}
