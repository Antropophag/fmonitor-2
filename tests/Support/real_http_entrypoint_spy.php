<?php
declare(strict_types=1);
namespace { require dirname(__DIR__).'/bootstrap.php'; }
namespace FMonitor2\PilotHttp\Gate2Spy {
use FMonitor2\PilotHttp\{CorrelationIdSource,CssAsset,CssAssetUnavailable,HttpUser,HttpUserDirectory,InvalidServerIdentity,PilotHttpDependencies,PilotShellRenderer,TrustedServerIdentity,UnexpectedFailureReporter};
final class Trace { public static array $calls=[];public static array $reports=[]; }
final class Identity implements TrustedServerIdentity { public function __construct(private string $mode='ok'){}public function resolve(mixed $value):string{Trace::$calls[]='identity';if($this->mode==='invalid')throw new InvalidServerIdentity();if($this->mode==='throw')throw new \RuntimeException('IDENTITY-SECRET');return 'spy@example.test';}}
final class Users implements HttpUserDirectory { public function resolveActiveUser(string $principal):?HttpUser{Trace::$calls[]='directory';return new HttpUser(18,'Spy User',$principal);}}
final class Shell implements PilotShellRenderer { public function render(HttpUser $user):string{Trace::$calls[]='renderer';return '<spy-shell>safe</spy-shell>';}}
final class Css implements CssAsset { private bool $closed=false;public function __construct(private string $mode='ok'){}public function readBytes():string{Trace::$calls[]='css.read';if($this->mode==='expected')throw new CssAssetUnavailable();if($this->mode==='throw')throw new \RuntimeException('CSS-SECRET');return "SPY-CSS\n";}public function close():void{if($this->closed)return;$this->closed=true;Trace::$calls[]='css.close';}}
final class Dependencies implements PilotHttpDependencies { public int $closeCount=0;private ?Css $cssAsset=null;public function __construct(private string $cssMode='ok',private bool $closeThrows=false){}public function css():CssAsset{Trace::$calls[]='dependencies.css';return $this->cssAsset??=new Css($this->cssMode);}public function users():HttpUserDirectory{Trace::$calls[]='dependencies.users';return new Users();}public function close():void{++$this->closeCount;Trace::$calls[]='close';$css=$this->cssAsset;$this->cssAsset=null;if($css!==null)$css->close();if($this->closeThrows)throw new \RuntimeException('CLOSE-SECRET');}}
final class Correlations implements CorrelationIdSource { public function __construct(private string $mode='valid'){}public function nextId():string{Trace::$calls[]='correlation';if($this->mode==='throw')throw new \RuntimeException('ENTROPY-SECRET');return match($this->mode){'empty'=>'','long'=>str_repeat('x',129),'invalid'=>'bad id!','valid'=>'corr-valid',default=>$this->mode};}}
final class Reporter implements UnexpectedFailureReporter { public function __construct(private bool $throws=false){}public function report(string $category,string $correlationId):void{Trace::$calls[]='report';Trace::$reports[]=[$category,$correlationId];if($this->throws)throw new \RuntimeException('REPORTER-SECRET');}}
}
namespace {
use FMonitor2\PilotHttp\{PilotHttpApplication,PilotHttpEntrypoint,PilotHttpRequestFactory};
use FMonitor2\PilotHttp\Gate2Spy\{Correlations,Css,Dependencies,Identity,Reporter,Shell,Trace};
$run=static function(array $server,string $identityMode='ok',string $cssMode='ok',bool $closeThrows=false,string $correlationMode='valid',bool $reporterThrows=false):array{Trace::$calls=[];Trace::$reports=[];$dependencies=new Dependencies($cssMode,$closeThrows);$reporter=new Reporter($reporterThrows);$application=new PilotHttpApplication(new Identity($identityMode),new Shell(),$dependencies);$entrypoint=new PilotHttpEntrypoint(new PilotHttpRequestFactory(),$application,$dependencies,new Correlations($correlationMode),$reporter);$response=$entrypoint->handle($server);return ['status'=>$response->status,'headers'=>$response->headers,'body'=>$response->body,'calls'=>Trace::$calls,'reports'=>Trace::$reports,'closeCount'=>$dependencies->closeCount];};
$server=static fn(string $method,string $uri,mixed $identity='spy@example.test',string $host='composition.example'):array=>['REQUEST_METHOD'=>$method,'REQUEST_URI'=>$uri,'FMONITOR_TRUSTED_REQUEST_HOST'=>$host,'REMOTE_USER'=>$identity];
echo json_encode([
'hostBeforeUri'=>$run($server('POST','/pilot/%ZZ',null,'bad host')),
'malformedUri'=>$run($server('GET','/pilot/%ZZ')),
'unknown'=>$run($server('GET','/pilot/unknown')),
'method'=>$run($server('OPTIONS','/pilot')),
'redirect'=>$run($server('GET','/pilot')),
'asset'=>$run($server('GET','/pilot/assets/shlz.css',null)),
'missingIdentity'=>$run($server('GET','/pilot/',null),'invalid'),
'shell'=>$run($server('GET','/pilot/')),
'expectedCssFailure'=>$run($server('GET','/pilot/assets/shlz.css',null),'ok','expected'),
'unexpected'=>$run($server('GET','/pilot/'),'throw'),
'closeFailure'=>$run($server('GET','/pilot/unknown'),'ok','ok',true),
'closeReporterThrow'=>$run($server('GET','/pilot/unknown'),'ok','ok',true,'valid',true),
'correlationThrow'=>$run($server('GET','/pilot/'),'ok','ok',false,'throw'),
'correlationEmpty'=>$run($server('GET','/pilot/'),'ok','ok',false,'empty'),
'correlationLong'=>$run($server('GET','/pilot/'),'ok','ok',false,'long'),
'correlationInvalid'=>$run($server('GET','/pilot/'),'ok','ok',false,'invalid'),
'correlationReporterThrow'=>$run($server('GET','/pilot/'),'ok','ok',false,'throw',true),
'unexpectedReporterThrow'=>$run($server('GET','/pilot/'),'throw','ok',false,'valid',true),
'cssCloseIdempotency'=>(static function():array{Trace::$calls=[];$css=new Css();$css->close();$css->close();return Trace::$calls;})(),
],JSON_UNESCAPED_SLASHES),"\n";
}
