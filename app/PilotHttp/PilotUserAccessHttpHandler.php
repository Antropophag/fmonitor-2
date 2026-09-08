<?php
declare(strict_types=1);
namespace FMonitor2\PilotHttp;
use FMonitor\IdentityAccess\PilotSessionOperationStatus;
use FMonitor\IdentityAccess\PilotSessionStorage;
final class PilotUserAccessHttpHandler
{
    private readonly PilotUserAdminHttpHandler $admin;
    public function __construct(private readonly PilotHttpCoordinator $reads,TrustedServerIdentity $identity,ProductionPilotHttpDependencies $dependencies,private readonly ?PilotSessionStorage $sessionStorage,private readonly ?EnvironmentSource $environment)
    { $this->admin=new PilotUserAdminHttpHandler($identity,$dependencies); }
    public function matchesOwnerSession(string $path):bool{return$this->sessionStorage!==null&&($path==='/pilot/login'||$this->ownerPath($path));}
    public function matchesAdmin(string $path):bool{return$path==='/pilot/admin/roles'||$this->ownerPath($path);}
    public function handle(PilotHttpRequest $request):PilotHttpResponse
    {
        if($this->matchesOwnerSession($request->path))return$this->sessionResponse($request);$route=[];\preg_match('#^/pilot/admin/users/([1-9][0-9]*)/roles(?:/([1-9][0-9]*))?$#D',$request->path,$route);
        return$this->admin->handle($request,$route,new PilotUserAdminSession($this->sessionStorage,$this->environment));
    }
    private function sessionResponse(PilotHttpRequest $request):PilotHttpResponse
    {
        $scheme=$this->environment?->read('FMONITOR_TRUSTED_REQUEST_SCHEME');if(!\in_array($scheme,['http','https'],true))return PilotUserAccessResponse::unavailable($request);
        $cookieName=\preg_match('/:(\d{1,5})$/D',$request->host,$match)===1?'fm2auth_'.$match[1]:'fm2auth';$incoming=null;if(\preg_match('/(?:^|;\s*)'.\preg_quote($cookieName,'/').'=([A-Za-z0-9,-]{16,128})(?:;|$)/',(string)($request->server['HTTP_COOKIE']??''),$cookie)===1)$incoming=$cookie[1];
        $started=$this->sessionStorage->start($incoming);if($started->status()===PilotSessionOperationStatus::UNAVAILABLE)return PilotUserAccessResponse::unavailable($request);$payload=$started->sessionPayload();$state=$payload===null?[]:(new PilotSessionPayloadCodec())->decode($payload);
        if($state===null){@\file_put_contents('php://stderr','PILOT_SESSION_UNAVAILABLE category=payload_invalid correlation_id='.\bin2hex(\random_bytes(6))."\n");return PilotUserAccessResponse::unavailable($request);}
        $id=$started->currentSessionId();if($id===null){$id=$this->sessionStorage->start(null)->currentSessionId();if($id===null)return PilotUserAccessResponse::unavailable($request);}
        $csrf=$state['auth_csrf']??null;$reused=\is_string($csrf)&&\preg_match('/^[0-9a-f]{64}$/D',$csrf)===1;
        if($this->ownerPath($request->path)&&$reused&&\is_int($state['auth_user_id']??null)&&\is_string($state['auth_email']??null))return$this->ownerUserAccess($request,$id,$state);
        if(!$reused){$csrf=\bin2hex(\random_bytes(32));$committed=$this->sessionStorage->writeCommit($id,$this->sessionBytes(['auth_csrf'=>$csrf]));if($committed->status()!==PilotSessionOperationStatus::OK)return PilotUserAccessResponse::unavailable($request);}
        if($request->path!=='/pilot/login')return$this->reads->handle($request);$headers=['Content-Type'=>'text/html; charset=UTF-8'];if(!$reused)$headers['Set-Cookie']=$cookieName.'='.$id.'; Max-Age=604800; Path=/pilot; HttpOnly; SameSite=Strict'.($scheme==='https'?'; Secure':'');
        return PilotUserAccessResponse::create($request,200,PilotSessionView::login($csrf),$headers);
    }
    private function ownerUserAccess(PilotHttpRequest $request,string $id,array $initialState):PilotHttpResponse
    {
        $session=new PilotUserAdminSession($this->sessionStorage,$this->environment,$initialState);$server=$request->server+['FMONITOR_AUTH_USER_ID'=>(string)$initialState['auth_user_id'],'FMONITOR_AUTH_CSRF'=>(string)$initialState['auth_csrf']];$trusted=new PilotHttpRequest($request->method,$request->path,$request->host,(string)$initialState['auth_email'],$server,$request->body);$route=[];\preg_match('#^/pilot/admin/users/([1-9][0-9]*)/roles(?:/([1-9][0-9]*))?$#D',$request->path,$route);$response=$this->admin->handle($trusted,$route,$session);$state=$session->ownerState()??$initialState;
        if(($request->path==='/pilot/admin/users/invite'||\preg_match('#^/pilot/admin/users/[1-9][0-9]*/invitation$#D',$request->path)===1)&&$request->method==='POST'&&\in_array($response->status,[201,400],true)){$state['fm2_invitation_flash']=$response->status===201&&\preg_match('#^Invitation: (/pilot/activate\?token=[A-Za-z0-9_-]{43})\n$#D',$response->body,$match)===1?['kind'=>'success','url'=>(($this->environment?->read('FMONITOR_TRUSTED_REQUEST_SCHEME')==='https'?'https':'http').'://'.$request->host.$match[1])]:['kind'=>'error'];$response=PilotUserAccessResponse::create($request,303,'',['Location'=>'/pilot/admin/users']);}
        if($response->status===200){$flash=$state['fm2_invitation_flash']??null;$body=$response->body;if(\is_array($flash)&&($flash['kind']??null)==='success'&&\is_string($flash['url']??null)){$body=PilotSessionView::withInvitationFeedback($body,$flash['url']);unset($state['fm2_invitation_flash']);}elseif(\is_array($flash)&&($flash['kind']??null)==='error'){$body=PilotSessionView::withInvitationError($body);unset($state['fm2_invitation_flash']);}$response=PilotUserAccessResponse::create($request,200,$body,['Content-Type'=>'text/html; charset=UTF-8']);}
        if($state!==$initialState){$commit=$this->sessionStorage->writeCommit($id,$this->sessionBytes($state));if($commit->status()!==PilotSessionOperationStatus::OK)return PilotUserAccessResponse::unavailable($request);}return$response;
    }
    private function ownerPath(string $path):bool{return \in_array($path,['/pilot/admin/users','/pilot/admin/users/invite'],true)||\preg_match('#^/pilot/admin/users/[1-9][0-9]*/(?:invitation|roles(?:/[1-9][0-9]*)?)$#D',$path)===1;}
    private function sessionBytes(array $state):string{$bytes=(new PilotSessionPayloadCodec())->encode($state);if($bytes===null)throw new PilotHttpInfrastructureUnavailable();return$bytes;}
}
