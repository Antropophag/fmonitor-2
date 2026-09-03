<?php declare(strict_types=1);
namespace FMonitor2\PilotHttp;
require_once __DIR__.'/PilotHttp.php';
final class PilotHttpEntrypoint extends PilotHttpGateway
{
    public function __construct(PilotHttpRequestFactory$requests,PilotHttpCoordinator$application,PilotHttpDependencies$dependencies,CorrelationIdSource$correlationIds,UnexpectedFailureReporter$failures,private readonly ?\Closure$localAuth=null){parent::__construct($requests,$application,$dependencies,$correlationIds,$failures);}
    public function handle(array$server):PilotHttpResponse{if($this->localAuth!==null)try{($this->localAuth)($server);}catch(\Throwable){$body="Service unavailable.\n";$headers=['Content-Type'=>'text/plain; charset=UTF-8','Content-Length'=>(string)\strlen($body),'Retry-After'=>'60','X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Content-Security-Policy'=>PilotRouteCsp::BASE,'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store'];return new PilotHttpResponse(503,$headers,($server['REQUEST_METHOD']??'GET')==='HEAD'?'':$body);}return parent::handle($server);}
}
