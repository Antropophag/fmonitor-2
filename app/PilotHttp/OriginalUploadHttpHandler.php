<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor2\AssignmentOrderOriginal as O;
final readonly class OriginalUploadHttpHandler
{
    public function __construct(private EnvironmentSource $environment) {}
    public function handle(PilotHttpRequest $r,int $object,int $order,bool $form):PilotHttpResponse
    {
        $allow=$form?'GET, HEAD':'POST';
        if(!\in_array($r->method,\explode(', ',$allow),true))return self::error($r,405,'METHOD_NOT_ALLOWED',['Allow'=>$allow]);
        $actor=FreshOrderFormInput::positive($r->server['FMONITOR_AUTH_USER_ID']??null);
        if($actor===null)return FreshOrderHttpHandler::response($r,303,'',['Location'=>'/pilot/login']);
        $resources=null;$stream=null;
        try{
            $csrf=$r->server['FMONITOR_AUTH_CSRF']??null;
            if(!$form){
                $input=OriginalUploadInput::metadata($r);if(isset($input['error']))return self::error($r,$input['status'],$input['error']);
                $f=$input['fields'];if(!\is_string($csrf)||$csrf===''||$f['csrfToken']===''||!\hash_equals($csrf,$f['csrfToken']))return self::error($r,403,'CSRF_INVALID');
            }elseif(!\is_string($csrf)||\preg_match('/^[0-9a-f]{64}$/D',$csrf)!==1)throw new \RuntimeException();
            $resources=new OriginalUploadResources($this->environment);
            $context=$form?$resources->query->readSubmissionForm($actor,$object,$order):$resources->query->resolveSubmissionContext($actor,$object,$order,$f['mode']);
            if($context['status']!=='found'){
                $code=$context['reasonCode'];return self::error($r,match($code){'ACCESS_DENIED'=>403,'NOT_FOUND'=>404,default=>503},$code);
            }
            if($form)return FreshOrderHttpHandler::response($r,200,OriginalUploadView::render($resources->native->user($actor),$context,$csrf));
            $application=$resources->application();$stream=OriginalUploadBody::acquire($input['length']);
            if($stream===null)return self::error($r,400,'INVALID_REQUEST');
            $command=new O\SubmitAssignmentOrderOriginalCommand($f['requestId'],O\AssignmentOrderOriginalMode::from($f['mode']),$context['caseId'],$context['orderId'],$actor,
                $f['documentDate'],$f['compositionConfirmed'],$f['rootOriginalId'],$f['targetRevisionId'],$f['expectedCurrentRevisionId'],$f['correctionReason'],new O\AssignmentOrderOriginalUpload($stream,$f['originalFilename'],'application/pdf'));
            $result=$application->submitAssignmentOrderOriginal($command);
            $status=match($result->status()){
                O\AssignmentOrderOriginalStatus::ACCEPTED=>201,O\AssignmentOrderOriginalStatus::REPLAYED=>200,O\AssignmentOrderOriginalStatus::CONFLICT=>409,O\AssignmentOrderOriginalStatus::FAILED=>503,
                O\AssignmentOrderOriginalStatus::REJECTED=>match($result->reasonCode()){
                    O\AssignmentOrderOriginalReason::AUTHORIZATION_DENIED=>403,O\AssignmentOrderOriginalReason::ORDER_NOT_FOUND=>404,O\AssignmentOrderOriginalReason::FILE_TOO_LARGE=>413,default=>422}};
            return FreshOrderHttpHandler::response($r,$status,O\AssignmentOrderOriginalWorkerResultEncoder::encode($result),['Content-Type'=>'application/json; charset=UTF-8']+($status===503?['Retry-After'=>'60']:[]));
        }catch(\Throwable){return self::error($r,503,'SERVICE_UNAVAILABLE');}
        finally{$stream?->close();$resources?->close();}
    }
    public static function error(PilotHttpRequest $r,int $status,string $code,array $extra=[]):PilotHttpResponse
    {
        $allowed=['METHOD_NOT_ALLOWED','INVALID_REQUEST','UNSUPPORTED_MEDIA_TYPE','LENGTH_REQUIRED','REQUEST_TOO_LARGE','CSRF_INVALID','ACCESS_DENIED','NOT_FOUND','SERVICE_UNAVAILABLE'];
        if(!\in_array($code,$allowed,true)){$code='SERVICE_UNAVAILABLE';$status=503;}
        if($r->method==='POST')@\file_put_contents('php://stderr',"FMONITOR_ORIGINAL_HTTP_REJECT status=$status reason=$code\n");
        return FreshOrderHttpHandler::response($r,$status,\json_encode(['error'=>$code],JSON_THROW_ON_ERROR)."\n",$extra+['Content-Type'=>'application/json; charset=UTF-8']+($status===503?['Retry-After'=>'60']:[]));
    }
}
