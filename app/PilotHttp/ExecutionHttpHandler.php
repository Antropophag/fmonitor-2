<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderComposition as C;
use FMonitor2\AssignmentOrderOriginal as O;
final readonly class ExecutionHttpHandler
{
    public function __construct(private EnvironmentSource $environment) {}
    public function handle(PilotHttpRequest $r,int $object):PilotHttpResponse
    {
        if(!\in_array($r->method,['GET','HEAD','POST'],true))return FreshOrderHttpHandler::response($r,405,'',['Allow'=>'GET, HEAD, POST']);
        $actor=FreshOrderFormInput::positive($r->server['FMONITOR_AUTH_USER_ID']??null);
        if($actor===null)return FreshOrderHttpHandler::response($r,303,'',['Location'=>'/pilot/login']);
        $resources=null;
        try {
            $resources=new FreshOrderHttpResources($this->environment);
            if($r->method!=='POST'&&$resources->query->authorizeActor($actor)['status']!=='allowed')return FreshOrderHttpHandler::response($r,403,'Доступ запрещён.');
            $csrf=$r->server['FMONITOR_AUTH_CSRF']??'';
            if(!\is_string($csrf)||\strlen($csrf)!==64)throw new \RuntimeException();
            if($r->method==='POST')return $this->submit($r,$resources,$actor,$object,$csrf);
            $model=$resources->query->readSelectionPortal($object,$actor);
            if($model['status']!=='found')return FreshOrderHttpHandler::response($r,404,'Объект не найден.');
            $read=C\AssignmentOrderApplicationReaderFactory::create($resources->db,$resources->prefix)->readCurrent($object);
            if($read->status==='unavailable')throw new \RuntimeException();
            $applied=$read->value;$original=null;
            if($model['latest']!==null){
                $ref=O\AssignmentOrderOriginalApplicationReferenceFactory::create($resources->db,$resources->prefix)->readCurrent($object,$model['latest']['orderId']);
                if($ref->status===O\AssignmentOrderOriginalApplicationReferenceStatus::UNAVAILABLE)throw new \RuntimeException();
                $original=$ref->reference?->metadata();
            }
            $case=(new MariaDbExecutionPageQuery($resources->db,$resources->prefix))->read($object);
            return FreshOrderHttpHandler::response($r,200,ExecutionView::render($resources->user($actor),$model,$applied,$original,$case,$csrf));
        } catch(\Throwable) {return FreshOrderHttpHandler::response($r,503,'Данные временно недоступны. Повторите позже.',['Retry-After'=>'30']);}
        finally{$resources?->close();}
    }
    private function submit(PilotHttpRequest $r,FreshOrderHttpResources $x,int $actor,int $object,string $csrf):PilotHttpResponse
    {
        $body=$r->body!==''?$r->body:\file_get_contents('php://input',false,null,0,8193);
        if(!\is_string($body)||\strlen($body)>8192)return FreshOrderHttpHandler::response($r,400,'Некорректная форма.');
        $fields=[];
        foreach(\explode('&',$body) as $pair){$parts=\explode('=',$pair,2);$key=\urldecode($parts[0]);
            if(!\in_array($key,['csrfToken','action','requestId','orderId','revisionId','sequence','applicationId','actualStartDate'],true)||isset($fields[$key]))return FreshOrderHttpHandler::response($r,400,'Некорректная форма.');
            $fields[$key]=\urldecode($parts[1]??'');}
        if(!\hash_equals($csrf,$fields['csrfToken']??''))return FreshOrderHttpHandler::response($r,403,'Срок действия формы истёк. Откройте страницу заново.');
        $action=$fields['action']??'';
        if($action==='open_confirmed'){
            $user=$x->user($actor);$permissions=AccessPolicy::forUser($x->db,$x->prefix,$user->id);if(!AccessPolicy::grants($permissions,'installation.open'))return FreshOrderHttpHandler::response($r,403,'Доступ запрещён.');
        }elseif($x->query->authorizeActor($actor)['status']!=='allowed')return FreshOrderHttpHandler::response($r,403,'Доступ запрещён.');
        if($action==='apply'){
            $order=FreshOrderFormInput::positive($fields['orderId']??null);$sequence=$fields['sequence']??'';
            if($order===null||!\preg_match('/^(0|[1-9][0-9]{0,9})$/D',$sequence))return FreshOrderHttpHandler::response($r,400,'Некорректная форма.');
            $command=new C\ApplyAssignmentOrderOriginalCommand($fields['requestId']??'',$object,$order,$fields['revisionId']??'',(int)$sequence,$actor);
            $result=C\ProductionAssignmentOrderApplicationFactory::create($x->db,$x->prefix)->applyAssignmentOrderOriginal($command);
            $success=\in_array($result->status,['applied','replayed'],true);$reason=$result->reason;
        }elseif($action==='open'){
            $id=FreshOrderFormInput::positive($fields['applicationId']??null);if($id===null)return FreshOrderHttpHandler::response($r,400,'Некорректная форма.');
            $result=C\ProductionOriginalOpeningFactory::create($x->db,$x->prefix)->openInstallation($object,$fields['actualStartDate']??'',$id,$actor);
            $success=$result['accepted'];$reason=$result['reasonCode']??null;
        }elseif($action==='open_confirmed'){
            $order=FreshOrderFormInput::positive($fields['orderId']??null);$sequence=$fields['sequence']??'';$request=$fields['requestId']??'';$revision=$fields['revisionId']??'';
            if($order===null||!\preg_match('/^(0|[1-9][0-9]{0,9})$/D',$sequence)||!\preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',$request)||!\preg_match('/^[A-Za-z0-9][A-Za-z0-9._:-]{0,79}$/D',$revision))return FreshOrderHttpHandler::response($r,400,'Некорректная форма.');
            $command=new C\OpenConfirmedOriginalCommand($request,$object,$order,$revision,(int)$sequence,$fields['actualStartDate']??'',$actor);
            $result=C\ProductionConfirmedOriginalOpeningFactory::create($x->db,$x->prefix)->openConfirmedOriginal($command);$success=$result['accepted'];$reason=$result['reasonCode']??null;
        }else return FreshOrderHttpHandler::response($r,400,'Неизвестное действие.');
        if($success)return FreshOrderHttpHandler::response($r,303,'',['Location'=>'/pilot/objects/'.$object.($action==='open_confirmed'?'':'/execution')]);
        return FreshOrderHttpHandler::response($r,422,ExecutionView::error($object,(string)$reason));
    }
}
