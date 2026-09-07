<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class ExecutionHttpCoordinator extends PilotHttpCoordinator
{
    public function __construct(private readonly PilotHttpCoordinator $next,private readonly EnvironmentSource $environment) {}
    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        if($this->environment->read('FMONITOR_FRESH_ORDER_FLOW')==='1') {
            if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/originals/history$#D',$r->path,$m))return (new OriginalHistoryHttpHandler($this->environment))->handle($r,(int)$m[1],(int)$m[2]);
            if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/originals/([A-Za-z0-9][A-Za-z0-9._:-]{0,79})/download$#D',$r->path,$m))return (new OriginalHistoryHttpHandler($this->environment))->handle($r,(int)$m[1],(int)$m[2],$m[3]);
            if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/execution$#D',$r->path,$m))return (new ExecutionHttpHandler($this->environment))->handle($r,(int)$m[1]);
            if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/open$#D',$r->path,$m))return FreshOrderHttpHandler::response($r,303,'',['Location'=>'/pilot/objects/'.$m[1].'/execution']);
        }
        return $this->next->handle($r);
    }
}
