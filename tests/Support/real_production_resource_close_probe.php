<?php
declare(strict_types=1);
namespace { require dirname(__DIR__).'/bootstrap.php'; }
namespace FMonitor2\PilotHttp\Gate2ResourceProbe {
use FMonitor2\PilotHttp\{CorrelationIdSource,EnvironmentSource,HttpUser,PilotShellRenderer,TrustedServerIdentity};
final class Source implements EnvironmentSource { public function __construct(private array $values){}public function read(string $name):string|false{return $this->values[$name]??false;}}
final class Identity implements TrustedServerIdentity { public function resolve(mixed $value):string{return 'unused@example.test';}}
final class Shell implements PilotShellRenderer { public function render(HttpUser $user):string{return 'unused';}}
final class Correlation implements CorrelationIdSource { public function nextId():string{return 'resource-close-probe';}}
}
namespace {
use FMonitor2\PilotHttp\{NativePhpFclosePrimitive,NativePhpStreamCloser,PhpCssDescriptorOpener,PilotHttpApplication,PilotHttpEntrypoint,PilotHttpRequestFactory,ProductionPilotHttpDependencies,ProductionPilotHttpEntrypointFactory};
use FMonitor2\PilotHttp\Gate2ResourceProbe\{Correlation,Identity,Shell,Source};
$config=json_decode((string)getenv('FMONITOR_TEST_RESOURCE_CONFIG'),true,512,JSON_THROW_ON_ERROR);$ready=(string)getenv('FMONITOR_TEST_RESOURCE_READY');$release=(string)getenv('FMONITOR_TEST_RESOURCE_RELEASE');$after=(string)getenv('FMONITOR_TEST_RESOURCE_AFTER');$finish=(string)getenv('FMONITOR_TEST_RESOURCE_FINISH');
$source=new Source($config);$factoryEntrypoint=ProductionPilotHttpEntrypointFactory::create($source);if(!$factoryEntrypoint instanceof PilotHttpEntrypoint)exit(71);$dependencies=new ProductionPilotHttpDependencies($source,new PhpCssDescriptorOpener(new NativePhpStreamCloser(new NativePhpFclosePrimitive())));$dependencies->css()->readBytes();$dependencies->users();file_put_contents($ready,"ready\n",LOCK_EX);$deadline=microtime(true)+10;while(!file_exists($release)&&microtime(true)<$deadline)usleep(1000);if(!file_exists($release))exit(72);$application=new PilotHttpApplication(new Identity(),new Shell(),$dependencies);
$reporter=new class implements \FMonitor2\PilotHttp\UnexpectedFailureReporter{public array $reports=[];public function report(string $category,string $correlationId):void{$this->reports[]=[$category,$correlationId];}};
$entrypoint=new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new Correlation(),$reporter);
$response=$entrypoint->handle(['REQUEST_METHOD'=>'GET','REQUEST_URI'=>'/pilot/unknown','FMONITOR_TRUSTED_REQUEST_HOST'=>'resource-probe.example','REMOTE_USER'=>null]);
$repeatNoOp=true;try{$dependencies->close();}catch(\Throwable){$repeatNoOp=false;}
file_put_contents($after,json_encode(['response'=>['status'=>$response->status,'headers'=>$response->headers,'body'=>$response->body],'reports'=>$reporter->reports,'repeatNoOp'=>$repeatNoOp],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES)."\n",LOCK_EX);
$deadline=microtime(true)+10;while(!file_exists($finish)&&microtime(true)<$deadline)usleep(1000);if(!file_exists($finish))exit(73);
}
