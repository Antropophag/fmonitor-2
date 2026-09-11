<?php
declare(strict_types=1);
require_once __DIR__.'/PreopeningFixture.php';

/** YII2-INSTALLER-DIRECTORY-001: private canonical DB, independent accepted example. */
final class InstallerDirectoryFixture
{
    public PreopeningFixture $http;
    public array $cookies=[];
    public function __construct(string $root)
    {
        $this->http=new PreopeningFixture($root);$h=$this->http;$p=$h->p;
        $h->insert($p.'fm2_pilot_role_permissions',['role_id'=>1,'permission'=>'installers.read']);
        $h->insert($p.'fm2_pilot_role_permissions',['role_id'=>5,'permission'=>'installers.read']);
        $h->db->query("DELETE FROM {$p}fm2_order_installers");$h->db->query("DELETE FROM {$p}fm2_assignment_orders");$h->db->query("DELETE FROM {$p}fm2_workforce_catalog");
        for($i=1;$i<=125;$i++)$h->insert($p.'fm2_workforce_catalog',['installer_tab_id'=>$i,'fio'=>'Монтажник '.str_pad((string)$i,3,'0',STR_PAD_LEFT),'position'=>$i===125?'<script>Монтажник</script>':'Монтажник','employment_status'=>$i%3===0?'dismissed':'employed','employed_from'=>'2020-01-01','workforce_source'=>'one_c_zup_via_bitrix','workforce_source_updated_at'=>'2026-09-10T07:40:00+03:00','reconciliation_state'=>'delivered']);
        $order=function(int$object,int$tab,string$status='registered',int$version=1,string$action='assign',string$from='2020-01-01',?string$to='2099-12-31')use($h,$p):int{
            $h->insert($p.'fm2_installation_cases',['legacy_installation_object_id'=>$object,'process_state'=>'working','actual_start_date'=>'2026-09-01','opened_at'=>'2026-09-01T09:00:00+03:00','opened_by_user_id'=>18,'created_at'=>'2026-09-01T09:00:00+03:00','updated_at'=>'2026-09-01T09:00:00+03:00','lock_version'=>1]);$case=(int)$h->db->insert_id;
            $h->insert($p.'fm_maintable',['id'=>$object,'regnumber'=>'DIR-'.$object,'ordadr_address'=>'Адрес '.$object]);
            $insert=function(int$v,string$s,int$t,string$a,string$f,?string$until)use($h,$p,$case,$object):int{$h->insert($p.'fm2_assignment_orders',['installation_case_id'=>$case,'version_no'=>$v,'kind'=>'initial','status'=>$s,'order_date'=>'2026-09-01','registration_number'=>$s==='registered'?'ORDER-'.$object.'-'.$v:null,'registered_at'=>$s==='registered'?'2026-09-01T10:00:00+03:00':null,'registration_actor_type'=>$s==='registered'?'user':null,'registration_actor_id'=>$s==='registered'?'18':null,'registration_source'=>$s==='registered'?'fixture':null,'control_engineer_user_id'=>73,'control_engineer_fio_snapshot'=>'Инженер','control_engineer_position_snapshot'=>'Инженер строительного контроля','organization_form'=>'brigade','object_address_snapshot'=>'Адрес '.$object,'entrance_snapshot'=>'1','object_registration_number_snapshot'=>'DIR-'.$object,'planned_start_date_snapshot'=>'2026-09-01','planned_finish_date_snapshot'=>'2026-12-01','prepared_at'=>'2026-09-01T09:00:00+03:00','prepared_by_user_id'=>18]);$id=(int)$h->db->insert_id;$h->insert($p.'fm2_order_installers',['assignment_order_id'=>$id,'installer_tab_id'=>$t,'fio_snapshot'=>'Монтажник '.str_pad((string)$t,3,'0',STR_PAD_LEFT),'position_snapshot'=>'Монтажник','employment_status_snapshot'=>'employed','employed_from_snapshot'=>'2020-01-01','workforce_source_snapshot'=>'fixture','workforce_source_updated_at_snapshot'=>'2026-09-01T06:00:00Z','valid_from'=>$f,'valid_to'=>$until,'change_action'=>$a]);return$id;};
            if($version>1)$insert(1,'registered',$tab,'assign','2020-01-01','2099-12-31');$insert($version,$status,$tab,$action,$from,$to);return$case;
        };
        for($i=1;$i<=5;$i++){
            $order(5000+$i,$i);
        }
        $order(5010,1);$order(5011,10,'prepared',2);$order(5012,11,'registered',1,'release');$order(5013,12,'registered',1,'assign','2099-01-01',null);$order(5014,13,'registered',1,'assign','2020-01-01','2020-12-31');
    }
    public function open():void{$this->start();assertSameValue(303,$this->http->login($this->cookies)['status'],'directory login');}
    public function get(string $query=''):array{return $this->http->request('GET','/pilot/installers'.$query,[],$this->cookies);}
    public function restart(array $overrides=[]):void{if($this->http->server!==null){proc_terminate($this->http->server['process']);proc_close($this->http->server['process']);$this->http->server=null;}$this->start($overrides);}
    public function routeNoLegacy():void
    {
        $found=false;$boundedGetFound=false;foreach(file($this->http->artifacts.'/directory-trace.jsonl',FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)?:[]as$line){$r=json_decode($line,true,flags:JSON_THROW_ON_ERROR);if(!str_starts_with((string)$r['uri'],'/pilot/installers'))continue;$found=true;if($r['uri']==='/pilot/installers'&&$r['method']==='GET'&&$r['status']===200){$boundedGetFound=true;assertSameValue(true,$r['queries']>=1&&$r['queries']<=4,'real HTTP bounded queries '.$r['queries']);}foreach($r['files']as$path)assertSameValue(false,str_contains($path,'/rapid-pilot/')||str_contains($path,'/app/PilotHttp/'),'directory route runtime closure');}assertSameValue(true,$found,'directory route traced');assertSameValue(true,$boundedGetFound,'successful unfiltered GET query budget observed');
    }
    private function start(array $overrides=[]):void
    {
        $socket=stream_socket_server('tcp://127.0.0.1:0',$error,$message);if(!is_resource($socket))throw new TestFailure('SETUP_FAILURE listener');$address=stream_socket_get_name($socket,false);$port=(int)substr($address,strrpos($address,':')+1);fclose($socket);
        $env=getenv();foreach(array_keys($env)as$key)if(str_starts_with($key,'FMONITOR_'))unset($env[$key]);$env=array_replace($env,$this->http->environment(),$overrides,['FMONITOR_TRUSTED_REQUEST_HOST'=>'127.0.0.1:'.$port]);$router=$this->http->artifacts.'/directory-router.php';$trace=$this->http->artifacts.'/directory-trace.jsonl';$root=$this->http->root;
        $code='<?php require '.var_export($root.'/vendor/autoload.php',true).';require '.var_export($root.'/vendor/yiisoft/yii2/Yii.php',true).';class DirectoryCountingCommand extends yii\\db\\Command{public static int $queries=0;protected function queryInternal($method,$fetchMode=null){$sql=$this->getRawSql();if(str_contains($sql,"fm2_workforce_catalog")||str_contains($sql,"fm2_order_installers"))self::$queries++;return parent::queryInternal($method,$fetchMode);}} register_shutdown_function(static function(){file_put_contents('.var_export($trace,true).',json_encode(["method"=>$_SERVER["REQUEST_METHOD"],"uri"=>$_SERVER["REQUEST_URI"],"status"=>http_response_code(),"queries"=>DirectoryCountingCommand::$queries,"files"=>get_included_files()])."\\n",FILE_APPEND|LOCK_EX);});$config=require '.var_export($root.'/config/yii/web.php',true).';$factory=$config["components"]["db"];$config["components"]["db"]=static function()use($factory){$db=$factory();$db->commandClass=DirectoryCountingCommand::class;return $db;};(new yii\\web\\Application($config))->run();';file_put_contents($router,$code);
        $process=proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,$router],[0=>['file','/dev/null','r'],1=>['file',$this->http->artifacts.'/server.log','a'],2=>['file',$this->http->artifacts.'/server.log','a']],$pipes,$root,$env);if(!is_resource($process))throw new TestFailure('SETUP_FAILURE server');$this->http->server=['process'=>$process,'port'=>$port];$deadline=microtime(true)+5;do{$s=@fsockopen('127.0.0.1',$port,$e,$m,.1);if(is_resource($s)){fclose($s);return;}usleep(20000);}while(microtime(true)<$deadline);throw new TestFailure('SETUP_FAILURE server readiness');
    }
    public function close():void{$this->http->close();}
}
