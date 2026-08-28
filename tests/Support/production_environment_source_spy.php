<?php
declare(strict_types=1);
namespace { require dirname(__DIR__).'/bootstrap.php'; }
namespace FMonitor2\PilotHttp\Gate2EnvironmentSpy {
use FMonitor2\PilotHttp\EnvironmentSource;
final class Source implements EnvironmentSource { public array $reads=[];public function __construct(private array $values){}public function read(string $name):string|false{$this->reads[]=$name;return $this->values[$name]??false;}}
}
namespace {
use FMonitor2\PilotHttp\Gate2EnvironmentSpy\Source;
use FMonitor2\PilotHttp\ProductionPilotHttpEntrypointFactory;
$values=json_decode((string)getenv('FMONITOR_TEST_ENV_VALUES'),true,512,JSON_THROW_ON_ERROR);
$server=static fn(string $uri,mixed $identity=null):array=>['REQUEST_METHOD'=>'GET','REQUEST_URI'=>$uri,'FMONITOR_TRUSTED_REQUEST_HOST'=>'environment-spy.example','REMOTE_USER'=>$identity];
$run=static function(array $request,bool $handle=true)use($values):array{$source=new Source($values);$entrypoint=ProductionPilotHttpEntrypointFactory::create($source);$before=$source->reads;$status=$handle?$entrypoint->handle($request)->status:null;return ['before'=>$before,'after'=>$source->reads,'status'=>$status];};
$aliases=['FMONITOR_SHLZ_CSS_PATH'=>'SHLZ_CSS_PATH','FMONITOR_DB_HOST'=>'DB_HOST','FMONITOR_DB_PORT'=>'MYSQL_PORT','FMONITOR_DB_NAME'=>'MYSQL_DATABASE','FMONITOR_DB_USER'=>'MYSQL_USER','FMONITOR_DB_PASSWORD'=>'FMONITOR_DB_PASS','FMONITOR_LEGACY_TABLE_PREFIX'=>'LEGACY_TABLE_PREFIX'];$aliasMatrix=[];foreach($aliases as $canonical=>$alias){$candidate=$values;unset($candidate[$canonical]);$candidate[$alias]=$values[$canonical];$source=new Source($candidate);$entrypoint=ProductionPilotHttpEntrypointFactory::create($source);$route=$canonical==='FMONITOR_SHLZ_CSS_PATH'?'/pilot/assets/shlz.css':'/pilot/';$status=$entrypoint->handle($server($route,$route==='/pilot/'?'sidorov@shlz.ru':null))->status;$aliasMatrix[$canonical]=['alias'=>$alias,'reads'=>$source->reads,'status'=>$status];}
echo json_encode(['unknown'=>$run($server('/pilot/unknown')),'asset'=>$run($server('/pilot/assets/shlz.css')),'missingIdentity'=>$run($server('/pilot/',null)),'shell'=>$run($server('/pilot/','sidorov@shlz.ru')),'aliasMatrix'=>$aliasMatrix],JSON_UNESCAPED_SLASHES),"\n";
}
