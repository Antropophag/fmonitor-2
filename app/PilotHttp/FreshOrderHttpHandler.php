<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
final readonly class FreshOrderHttpHandler
{
    public function __construct(private EnvironmentSource $environment) {}
    public function handle(PilotHttpRequest $r,int $object,?int $order):PilotHttpResponse
    {
        $allow=$order===null?'GET, HEAD, POST':'POST';
        if(!in_array($r->method,explode(', ',$allow),true))return self::response($r,405,'Method not allowed.', ['Allow'=>$allow,'Content-Type'=>'text/plain; charset=UTF-8']);
        $actor=FreshOrderFormInput::positive($r->server['FMONITOR_AUTH_USER_ID']??null);
        if($actor===null)return self::response($r,303,'',['Location'=>'/pilot/login']);
        $resources=null;
        try {
            $resources=new FreshOrderHttpResources($this->environment);$a=$resources->query->authorizeActor($actor);
            if($a['status']!=='allowed')return $this->error($r,$a['status']==='failed'?503:403,$a['reasonCode'],$object);
            $csrf=$r->server['FMONITOR_AUTH_CSRF']??null;if(!is_string($csrf)||preg_match('/^[0-9a-f]{64}$/D',$csrf)!==1)throw new \RuntimeException();
            if($r->method!=='POST'){
                $model=$resources->query->readSelectionPortal($object,$actor);
                if($model['status']!=='found')return $this->domainError($r,$model,$object);
                return self::response($r,200,FreshOrderSelectionView::render($resources->user($actor),$model,$csrf));
            }
            $decoded=FreshOrderFormInput::parse($r,$order!==null);
            if(isset($decoded['error']))return $this->error($r,$decoded['error'],$decoded['reason'],$object);
            $f=$decoded['fields'];if(!isset($f['csrfToken'])||!hash_equals($csrf,$f['csrfToken']))return $this->error($r,403,'csrf_invalid',$object);
            if($order!==null){
                $model=$resources->query->readSelectionPortal($object,$actor);if($model['status']!=='found')return $this->domainError($r,$model,$object);
                $result=$resources->template($model['caseId'],$order,$actor);
                if($result['status']!=='generated')return $this->domainError($r,$result,$object);
                return self::response($r,200,$result['bytes'],['Content-Type'=>'application/pdf',
                    'Content-Disposition'=>'attachment; filename="assignment-order.pdf"; filename*=UTF-8\'\''.rawurlencode($result['filename'])]);
            }
            $command=FreshOrderFormInput::command($f,$object,$actor);
            if(isset($command['error']))return $this->error($r,$command['error'],$command['reason'],$object);
            $result=$resources->saveComposition($command['command']);
            if(in_array($result['status'],['selected','replayed'],true))return self::response($r,303,'',['Location'=>FreshOrderSelectionView::path($object)]);
            return $this->domainError($r,$result,$object,($result['retryable']??false)?$f:null);
        }catch(\Throwable){return $this->error($r,503,'dependency_unavailable',$object);}
        finally{if($resources!==null)$resources->close();}
    }
    private function domainError(PilotHttpRequest $r,array $result,int $object,?array $retry=null):PilotHttpResponse
    {
        $reason=$result['reasonCode'];$status=match(true){$result['status']==='failed'=>503,$result['status']==='conflict'=>409,
            $reason==='authorization_denied'=>403,in_array($reason,['object_not_found','order_not_found'],true)=>404,$reason==='invalid_command'=>400,default=>422};
        return self::response($r,$status,FreshOrderSelectionView::error($reason,$object,$retry),$status===503?['Retry-After'=>'60']:[]);
    }
    public function error(PilotHttpRequest $r,int $status,string $reason,int $object):PilotHttpResponse
    {
        if($r->method==='POST'&&preg_match('/^[a-z_]+$/D',$reason)===1)@file_put_contents('php://stderr',"FMONITOR_ORDER_HTTP_REJECT status=$status reason=$reason\n");
        return self::response($r,$status,FreshOrderSelectionView::error($reason,$object),$status===503?['Retry-After'=>'60']:[]);
    }
    public static function response(PilotHttpRequest $r,int $status,string $body,array $extra=[]):PilotHttpResponse
    {
        $type=$extra['Content-Type']??'text/html; charset=UTF-8';
        $headers=$extra+['Content-Type'=>$type,'Content-Length'=>(string)strlen($body),'Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff',
            'Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Content-Security-Policy'=>PilotRouteCsp::forResponse($r->method,$r->path,$status,$type,$body),
            'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin'];
        return new PilotHttpResponse($status,$headers,$r->method==='HEAD'?'':$body);
    }
}
