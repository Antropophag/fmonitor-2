<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final class FreshOrderHttpCoordinator extends PilotHttpCoordinator
{
    public function __construct(private readonly PilotHttpCoordinator $next,private readonly EnvironmentSource $environment) {}
    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        $original=\preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/originals(/submit)?$#D',$r->path,$originalIds)===1;
        if($original){
            if($this->environment->read('FMONITOR_FRESH_ORDER_FLOW')!=='1')return OriginalUploadHttpHandler::error($r,404,'NOT_FOUND');
            $object=FreshOrderFormInput::positive($originalIds[1]);$order=FreshOrderFormInput::positive($originalIds[2]);
            if($object===null||$order===null)return OriginalUploadHttpHandler::error($r,400,'INVALID_REQUEST');
            return (new OriginalUploadHttpHandler($this->environment))->handle($r,$object,$order,isset($originalIds[3]));
        }
        if($r->path==='/pilot/assets/original-upload.js'){
            if(!\in_array($r->method,['GET','HEAD'],true))return OriginalUploadHttpHandler::error($r,405,'METHOD_NOT_ALLOWED',['Allow'=>'GET, HEAD']);
            $bytes=\file_get_contents(__DIR__.'/original-upload.js');
            return $bytes===false?OriginalUploadHttpHandler::error($r,503,'SERVICE_UNAVAILABLE'):FreshOrderHttpHandler::response($r,200,$bytes,['Content-Type'=>'text/javascript; charset=UTF-8']);
        }
        $new=\preg_match('#^/pilot/objects/([1-9][0-9]*)/(?:assignment-order/selection|assignment-orders/([1-9][0-9]*)/template)$#D',$r->path,$m)===1;
        if($this->environment->read('FMONITOR_FRESH_ORDER_FLOW')!=='1')return $new?FreshOrderHttpHandler::response($r,404,"Not found.\n",['Content-Type'=>'text/plain; charset=UTF-8']):$this->next->handle($r);
        $handler=new FreshOrderHttpHandler($this->environment);
        if($new){$object=FreshOrderFormInput::positive($m[1]);$order=isset($m[2])?FreshOrderFormInput::positive($m[2]):null;
            if($object===null||(isset($m[2])&&$order===null))return FreshOrderHttpHandler::response($r,400,"Bad request.\n",['Content-Type'=>'text/plain; charset=UTF-8']);
            return $handler->handle($r,$object,$order);
        }
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D',$r->path,$legacy)===1){
            $id=FreshOrderFormInput::positive($legacy[1]);if($id===null)return FreshOrderHttpHandler::response($r,400,'Bad request.');
            if(\in_array($r->method,['GET','HEAD'],true))return FreshOrderHttpHandler::response($r,303,'',['Location'=>FreshOrderSelectionView::path($id)]);
            if($r->method==='POST')return $handler->error($r,410,'fresh_route_unavailable',$id);
        }
        if(\preg_match('#^/pilot/objects/([1-9][0-9]*)/(?:assignment-orders/[1-9][0-9]*/(?:registration|artifacts/(?:order|appendix|signed_original))|control-engineer|open)$#D',$r->path,$legacy)===1){
            $always=\str_contains($r->path,'/assignment-orders/');
            if($always||$r->method==='POST')return $handler->error($r,410,'fresh_route_unavailable',(int)$legacy[1]);
        }
        return $this->next->handle($r);
    }
}
