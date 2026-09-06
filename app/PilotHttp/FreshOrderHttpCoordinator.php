<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class FreshOrderHttpCoordinator extends PilotHttpCoordinator
{
    public function __construct(private readonly PilotHttpCoordinator $next,private readonly EnvironmentSource $environment) {}
    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        $new=preg_match('#^/pilot/objects/([1-9][0-9]*)/(?:assignment-order/selection|assignment-orders/([1-9][0-9]*)/template)$#D',$r->path,$m)===1;
        if($this->environment->read('FMONITOR_FRESH_ORDER_FLOW')!=='1')return $new?FreshOrderHttpHandler::response($r,404,"Not found.\n",['Content-Type'=>'text/plain; charset=UTF-8']):$this->next->handle($r);
        $handler=new FreshOrderHttpHandler($this->environment);
        if($new){$object=FreshOrderFormInput::positive($m[1]);$order=isset($m[2])?FreshOrderFormInput::positive($m[2]):null;
            if($object===null||(isset($m[2])&&$order===null))return FreshOrderHttpHandler::response($r,400,"Bad request.\n",['Content-Type'=>'text/plain; charset=UTF-8']);
            return $handler->handle($r,$object,$order);
        }
        if(preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D',$r->path,$legacy)===1){
            $id=FreshOrderFormInput::positive($legacy[1]);if($id===null)return FreshOrderHttpHandler::response($r,400,'Bad request.');
            if(in_array($r->method,['GET','HEAD'],true))return FreshOrderHttpHandler::response($r,303,'',['Location'=>FreshOrderSelectionView::path($id)]);
            if($r->method==='POST')return $handler->error($r,410,'fresh_route_unavailable',$id);
        }
        if(preg_match('#^/pilot/objects/([1-9][0-9]*)/(?:assignment-orders/[1-9][0-9]*/(?:registration|artifacts/(?:order|appendix|signed_original))|control-engineer|open)$#D',$r->path,$legacy)===1){
            $always=str_contains($r->path,'/assignment-orders/');
            if($always||$r->method==='POST')return $handler->error($r,410,'fresh_route_unavailable',(int)$legacy[1]);
        }
        return $this->next->handle($r);
    }
}
