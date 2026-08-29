<?php
declare(strict_types=1);

namespace FMonitor2\PilotHttp;

use FMonitor2\InstallationProcess\ArtifactUnavailableException;
use FMonitor2\InstallationProcess\ArtifactIntegrityException;
use FMonitor2\InstallationProcess\ArtifactNotFoundException;
use FMonitor2\InstallationProcess\Clock;
use FMonitor2\InstallationProcess\ProductionInstallationProcessConfig;
use FMonitor2\InstallationProcess\ProductionInstallationProcessFactory;
final class InvalidCsrfRequest extends \RuntimeException {}

/** Configured production HTTP command adapter for PILOT-E2E-FLOW-001. */
final class PilotE2ECoordinator extends PilotHttpCoordinator
{
    public function __construct(
        private readonly PilotHttpCoordinator $reads,
        private readonly TrustedServerIdentity $identity,
        private readonly ProductionPilotHttpDependencies $dependencies,
        private readonly ObjectCardRenderer $cards,
        private readonly ObjectListRenderer $lists,
        private readonly PrepareFormRenderer $forms,
    ) { parent::__construct($identity,new ProductionPilotShellRenderer(),$dependencies); }

    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        if(!$this->dependencies->pilotUiConfigured()||!$this->dependencies->prepareCommandConfigured()||!$this->dependencies->e2eConfigured())return $this->reads->handle($r);
        $route=$this->route($r->path);$cardId=null;if(\preg_match('#^/pilot/objects/([1-9][0-9]*)$#D',$r->path,$m)===1&&self::positive($m[1]))$cardId=(int)$m[1];
        if($route===null&&$cardId===null&&$r->path!=='/pilot/objects')return $this->reads->handle($r);
        if($route===null){if(!\in_array($r->method,['GET','HEAD'],true))return $this->response(405,"Method not allowed.\n",['Allow'=>'GET, HEAD'],$r->method);try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}try{$this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();$user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);return $cardId===null?$this->queue($r,$user):$this->card($r,$cardId,$user);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        $allow=$route['kind']==='prepare'?'GET, HEAD, POST':($route['kind']==='artifact'?'GET, HEAD':'POST');
        if(!\in_array($r->method,\explode(', ',$allow),true))return $this->response(405,"Method not allowed.\n",['Allow'=>$allow],$r->method);
        try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
        try{
            $this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();
            $user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);
            $cap=match($route['kind']){'prepare','artifact'=>'assignment_order.prepare','registration'=>'assignment_order.confirm_registration','open'=>'installation.open'};
            if(!$this->dependencies->hasCapability($user->id,$cap))return $this->response(403,"Access denied.\n",[],$r->method);
            if($route['kind']==='artifact')return $this->artifact($r,$route,$user);
            if($r->method==='POST'){try{return $this->command($r,$route,$user);}catch(\LogicException $e){return $this->commandLogicException($r,$route,$user,$e);}}
            return $this->preparePage($r,$route['id'],$user);
        }catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
        catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
    }

    public function card(PilotHttpRequest $r,int $id,HttpUser $user):PilotHttpResponse
    {
        $card=$this->dependencies->objectCards()->read($id);if($card===null)return $this->response(404,"Not found.\n",[],$r->method);
        $session=null;$headers=[];$capPrepare=$this->dependencies->hasCapability($user->id,'assignment_order.prepare');$capRegister=$this->dependencies->hasCapability($user->id,'assignment_order.confirm_registration');$capOpen=$this->dependencies->hasCapability($user->id,'installation.open');
        [$existingSession]=$this->session($r,$user,false);$flash=$existingSession===null?null:$this->pullFlash($existingSession,$r->path);if(($flash['suppressOpen']??false)===true)$capOpen=false;
        $needsCommand=($card['order']===null&&$capPrepare)||($card['order']!==null&&$card['order']['status']==='prepared'&&$capRegister)||($card['order']!==null&&$card['order']['status']==='registered'&&!$card['opened']&&$capOpen);
        if($needsCommand){[$session,$headers]=$this->session($r,$user,true);$card['csrfToken']=$this->token($session,$user,$id);$card['processRevision']=$this->revision($session,$id);}
        else{[$session]=$this->session($r,$user,false);}
        $card['canPrepare']=$capPrepare;$card['canRegister']=$capRegister;$card['canOpen']=$capOpen;$card['flash']=$flash;$card['openedByName']=$card['opened']?$this->actorName((int)$card['openedByUserId']):null;
        return $this->response(200,$this->cards->render($user,$card),['Content-Type'=>'text/html; charset=UTF-8']+$headers,$r->method);
    }

    public function queue(PilotHttpRequest $r,HttpUser $user):PilotHttpResponse
    {
        $caps=['Требуется распоряжение'=>$this->dependencies->hasCapability($user->id,'assignment_order.prepare'),'Распоряжение подготовлено'=>$this->dependencies->hasCapability($user->id,'assignment_order.confirm_registration'),'Готов к открытию'=>$this->dependencies->hasCapability($user->id,'installation.open')];$objects=$this->dependencies->objectList()->read();foreach($objects as &$object){$card=$this->dependencies->objectCards()->read($object['id']);$object['status']=$card['status'];$object['nextStep']=($caps[$card['status']]??($card['status']==='В работе'))?match($card['status']){'Требуется распоряжение'=>'Сформировать распоряжение','Распоряжение подготовлено'=>'Внести номер 1С ДО','Готов к открытию'=>'Открыть работы','В работе'=>'Инженеру: провести первую инспекцию',default=>'Откройте карточку объекта монтажа'}:'Откройте карточку объекта монтажа';}unset($object);
        return $this->response(200,$this->lists->render($user,$objects),['Content-Type'=>'text/html; charset=UTF-8'],$r->method);
    }

    private function preparePage(PilotHttpRequest $r,int $id,HttpUser $user):PilotHttpResponse
    {
        try{$form=$this->dependencies->prepareForms()->read($id,$this->dependencies->processDate());}catch(PrepareFormUnavailable){return $this->redirect('/pilot/objects/'.$id);}
        if($form===null)return $this->response(404,"Not found.\n",[],$r->method);[$session,$headers]=$this->session($r,$user,true);$form['csrfToken']=$this->token($session,$user,$id);$form['processRevision']=$this->revision($session,$id);$form['flash']=$this->pullFlash($session,$r->path);$form['selected']=$form['flash']['selected']??[];return $this->response(200,$this->forms->render($user,$form),['Content-Type'=>'text/html; charset=UTF-8']+$headers,$r->method);
    }

    private function command(PilotHttpRequest $r,array $route,HttpUser $user):PilotHttpResponse
    {
        $r=new PilotHttpRequest($r->method,$r->path,$r->host,$r->serverIdentity,$r->server,(string)\file_get_contents('php://input'));
        [$session]=$this->session($r,$user,false);if($session===null||!$this->validRequest($r,$session,$user))return $this->response(403,"Invalid request.\n");
        $allowed=match($route['kind']){'prepare'=>['csrfToken','processRevision','installerTabIds[]','controlEngineerUserId','controlEngineerConfirmed'],'registration'=>['csrfToken','processRevision','assignmentOrderVersion','registrationNumber'],'open'=>['csrfToken','processRevision','assignmentOrderVersion','actualStartDate']};
        try{$fields=$this->body($r,$allowed);}catch(InvalidCsrfRequest){return $this->response(403,"Invalid request.\n");}if($fields===null)return $this->response(400,"Bad request.\n");
        if(!$this->consume($session,$fields['csrfToken'][0]??'', $user,$route['id']))return $this->response(403,"Invalid request.\n");
        $card=$this->dependencies->objectCards()->read($route['id']);if($card===null)return $this->response(404,"Not found.\n");
        if(($fields['processRevision'][0]??'')!==$this->revision($session,$route['id']))return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Данные объекта монтажа изменились. Проверьте актуальное состояние и повторите действие.');
        [$connection,$processPrefix,$legacyPrefix,$root,$now]=$this->dependencies->commandResources();$clock=new class($now) implements Clock{public function __construct(private string $value){}public function now():string{return $this->value;}};$config=new ProductionInstallationProcessConfig($processPrefix,$legacyPrefix,$root);$process=ProductionInstallationProcessFactory::create($connection,$config,$clock);
        if($route['kind']==='prepare'){
            $path=$r->path;$error=null;$installers=$fields['installerTabIds[]']??[];$engineers=$fields['controlEngineerUserId']??[];$confirm=$fields['controlEngineerConfirmed']??[];$eligible=$this->dependencies->prepareForms()->read($route['id'],$this->dependencies->processDate());$installerIds=\array_map(static fn(array $x):string=>(string)$x['tabId'],$eligible['installers']);$engineerIds=\array_map(static fn(array $x):string=>(string)$x['userId'],$eligible['engineers']);
            if($installers===[]||\count($installers)!==\count(\array_unique($installers))||\count(\array_filter($installers,[self::class,'positive']))!==\count($installers))$error=['installers','Выберите хотя бы одного монтажника.'];
            elseif(\count($engineers)!==1||!self::positive($engineers[0]))$error=['engineer','Выберите одного инженера строительного контроля.'];elseif($confirm!==['yes'])$error=['confirmation','Подтвердите выбор инженера строительного контроля.'];
            if($error===null){foreach($installers as$value)if(!\in_array($value,$installerIds,true)){$error=['installers','Состав монтажников изменился. Проверьте доступных сотрудников.'];break;}if($error===null&&!\in_array($engineers[0],$engineerIds,true))$error=['engineer','Выбранный инженер больше недоступен. Выберите другого.'];}
            if($error!==null)return $this->flashRedirect($session,$path,$error[1],$error[0],false,['installers'=>\array_values(\array_intersect($installers,$installerIds??[])),'engineer'=>(isset($engineers[0])&&\in_array($engineers[0],$engineerIds??[],true))?$engineers[0]:null,'confirmed'=>$confirm===['yes']]);
            $session['pendingSelection']=['installers'=>\array_values(\array_intersect($installers,$installerIds)),'engineer'=>\in_array($engineers[0],$engineerIds,true)?$engineers[0]:null,'confirmed'=>$confirm===['yes']];$_SESSION=$session;$result=$process->prepareAssignmentOrder($route['id'],\array_map('intval',$installers),(int)$engineers[0],$user->id);if(($result['accepted']??false)===true){if(($result['status']??null)!=='prepared'||!self::positive((string)($result['assignmentOrderVersion']??'')))return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60']);return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Распоряжение подготовлено.');}return $this->violation($session,$route,$result,$path);
        }
        $version=$fields['assignmentOrderVersion']??[];if(\count($version)!==1||!self::positive($version[0])||$card['order']===null||(int)$version[0]!==$card['order']['version']||($route['kind']==='registration'&&(int)$version[0]!==$route['version'])||($route['kind']==='open'&&$card['order']['status']!=='registered'))return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Данные объекта монтажа изменились. Проверьте актуальное состояние и повторите действие.');
        if($route['kind']==='registration'){$number=$fields['registrationNumber'][0]??'';$number=\trim($number);if($number===''||\mb_strlen($number)>120||\preg_match('/[\x00-\x1F\x7F]/u',$number)===1)return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Введите номер распоряжения в 1С ДО.','registrationNumber');$result=$process->confirmOrderRegistration($route['id'],(int)$version[0],$number,'manual',$user->id);if(($result['accepted']??false)===true){if(($result['status']??null)!=='registered'||($result['assignmentOrderVersion']??null)!==(int)$version[0])return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60']);return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Номер 1С ДО сохранён. Распоряжение зарегистрировано.');}return $this->violation($session,$route,$result,'/pilot/objects/'.$route['id']);}
        $date=$fields['actualStartDate'][0]??'';$today=(new \DateTimeImmutable($now))->setTimezone(new \DateTimeZone('Europe/Moscow'))->format('Y-m-d');if(!self::date($date)||$date>$today||$date<$card['order']['orderDate'])return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Укажите фактическую дату от даты распоряжения до сегодняшнего дня.','actualStartDate');$result=$process->openInstallation($route['id'],$date,$user->id);if(($result['accepted']??false)===true){if(($result['processState']??null)!=='working'||($result['actualStartDate']??null)!==$date)return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60']);return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Работы открыты.');}return $this->violation($session,$route,$result,'/pilot/objects/'.$route['id']);
    }

    private function artifact(PilotHttpRequest $r,array $route,HttpUser $user):PilotHttpResponse
    {
        [$connection,$processPrefix,$legacyPrefix,$root]=$this->dependencies->commandResources();try{$a=ProductionInstallationProcessFactory::createArtifactService($connection,new ProductionInstallationProcessConfig($processPrefix,$legacyPrefix,$root))->download($route['id'],$route['version'],$route['type'],$user->id);}catch(ArtifactNotFoundException|ArtifactIntegrityException){return $this->response(404,"Not found.\n",[],$r->method);}catch(ArtifactUnavailableException){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}if(!($a['accepted']??false))return $this->response(403,"Access denied.\n",[],$r->method);$filename=$a['filename']??'';$bytes=$a['bytes']??'';if(!\is_string($filename)||\preg_match('/^[\x20-\x21\x23-\x2E\x30-\x5B\x5D-\x7E]+$/D',$filename)!==1||!\is_string($bytes))return $this->response(404,"Not found.\n",[],$r->method);return $this->response(200,$bytes,['Content-Type'=>$a['mediaType'],'Content-Disposition'=>'attachment; filename="'.$filename.'"'],$r->method);
    }

    private function commandLogicException(PilotHttpRequest $r,array $route,HttpUser $user,\LogicException $error):PilotHttpResponse
    {
        $message=$error->getMessage();$authorization=['registration'=>'Registration confirmation is not authorized.','open'=>'Installation opening is not authorized.'];if(($authorization[$route['kind']]??null)===$message)return $this->response(403,"Access denied.\n",[],$r->method);
        $concurrency=['registration'=>['The current prepared assignment order was not found.','The requested assignment order is not the current prepared version.'],'open'=>['Installation is already open.','A current registered assignment order is required.','The current assignment order is not registered.']];$employment=$route['kind']==='open'&&$message==='A current installer is not employed on the actual start date.';if(!$employment&&!\in_array($message,$concurrency[$route['kind']]??[],true))return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);
        [$session]=$this->session($r,$user,false);if($session===null)return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);return $employment?$this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Состав монтажников изменился. Открытие работ недоступно.',null,true):$this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Данные объекта монтажа изменились. Проверьте актуальное состояние и повторите действие.');
    }

    private function violation(array &$session,array $route,array $result,string $path):PilotHttpResponse
    {
        $code=$result['violations'][0]['code']??'';if($route['kind']==='open'&&$code==='REGISTERED_ORDER_COMPOSITION_INVALID')return $this->flashRedirect($session,$path,'Состав зарегистрированного распоряжения повреждён. Открытие работ недоступно.',null,true);if($route['kind']==='open'&&$code==='INSTALLER_NOT_EMPLOYED')return $this->flashRedirect($session,$path,'Состав монтажников изменился. Открытие работ недоступно.',null,true);$map=['INSTALLER_NOT_IN_CATALOG'=>['Состав монтажников изменился. Проверьте доступных сотрудников.','installers'],'INSTALLER_NOT_EMPLOYED'=>['Состав монтажников изменился. Проверьте доступных сотрудников.','installers'],'CONTROL_ENGINEER_NOT_ELIGIBLE'=>['Выбранный инженер больше недоступен. Выберите другого.','engineer'],'CONTROL_ENGINEER_REQUIRED'=>['Выберите одного инженера строительного контроля.','engineer'],'INSTALLER_REQUIRED'=>['Выберите хотя бы одного монтажника.','installers'],'INSTALLATION_OBJECT_REQUIRED_DATA_MISSING'=>['В карточке объекта монтажа не хватает обязательных данных.',null],'REGISTRATION_NUMBER_REQUIRED'=>['Введите номер распоряжения в 1С ДО.','registrationNumber'],'ACTUAL_START_BEFORE_ORDER_DATE'=>['Укажите фактическую дату от даты распоряжения до сегодняшнего дня.','actualStartDate']];if(isset($map[$code]))return $this->flashRedirect($session,$path,$map[$code][0],$map[$code][1]);if($code==='FORBIDDEN')return $this->response(403,"Access denied.\n");if(\in_array($code,['ORDER_HAS_PTO_ACT','ASSIGNMENT_ORDER_ALREADY_PREPARED','CONCURRENT_MODIFICATION'],true))return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],$code==='CONCURRENT_MODIFICATION'?'Данные объекта монтажа изменились. Проверьте актуальное состояние и повторите действие.':'Формирование распоряжения недоступно для текущего состояния объекта монтажа.');return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60']);
    }

    private function route(string $path):?array
    {
        foreach([['prepare','#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D'],['artifact','#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/artifacts/(order|appendix)$#D'],['registration','#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/registration$#D'],['open','#^/pilot/objects/([1-9][0-9]*)/open$#D']]as[$kind,$pattern])if(\preg_match($pattern,$path,$m)===1){foreach(\array_slice($m,1)as$x)if(\ctype_digit($x)&&!self::positive($x))return null;return ['kind'=>$kind,'id'=>(int)$m[1]]+(\in_array($kind,['artifact','registration'],true)?['version'=>(int)$m[2]]:[])+($kind==='artifact'?['type'=>$m[3]]:[]);}return null;
    }
    private function session(PilotHttpRequest $r,HttpUser $user,bool $create):array
    {
        $headers=[];if(\session_status()!==PHP_SESSION_ACTIVE){\session_name('fm2pilot');\ini_set('session.use_cookies','0');$incoming=null;if(\preg_match('/(?:^|;\s*)fm2pilot=([A-Za-z0-9,-]{16,128})/',(string)($r->server['HTTP_COOKIE']??''),$m)===1)$incoming=$m[1];if($incoming===null&&!$create)return [null,[]];if($incoming!==null)\session_id($incoming);if(!@\session_start())throw new PilotHttpInfrastructureUnavailable();if($incoming===null){$demoNonce=$r->server['FMONITOR_DEMO_LOOPBACK_NONCE']??null;$trustedDemo=PHP_SAPI==='cli-server'&&\is_string($demoNonce)&&\preg_match('/^[0-9a-f]{32}$/D',$demoNonce)===1&&\preg_match('/^127\.0\.0\.1:[1-9][0-9]*$/D',$r->host)===1;$headers=['Set-Cookie'=>'fm2pilot='.\session_id().($trustedDemo?'':'; Secure').'; HttpOnly; SameSite=Strict; Path=/pilot'];}}
        if(isset($_SESSION['actor'])&&$_SESSION['actor']!==$user->id){\session_regenerate_id(true);$_SESSION=[];}
        if(!isset($_SESSION['actor'])){if(!$create)return [null,[]];$_SESSION=['actor'=>$user->id,'secret'=>\random_bytes(32),'tokens'=>[],'flash'=>[]];}
        return [&$_SESSION,$headers];
    }
    private function token(array &$s,HttpUser $u,int $id):string{$t=\bin2hex(\random_bytes(16));$s['tokens'][$t]=['actor'=>$u->id,'id'=>$id,'at'=>\time()];$_SESSION=$s;return $t;}
    private function consume(array &$s,string $token,HttpUser $u,int $id):bool{$x=$s['tokens'][$token]??null;unset($s['tokens'][$token]);$_SESSION=$s;return \is_array($x)&&$x['actor']===$u->id&&$x['id']===$id&&\time()-$x['at']<=1800;}
    private function revision(array $s,int $id):string{[$db,$prefix]=$this->dependencies->commandResources();$q=$db->prepare("SELECT lock_version FROM `{$prefix}fm2_installation_cases` WHERE legacy_installation_object_id=? LIMIT 2");$q->bind_param('i',$id);$q->execute();$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);if(\count($rows)!==1)throw new PilotHttpInfrastructureUnavailable();return \hash_hmac('sha256',$id.':'.$rows[0]['lock_version'],$s['secret']);}
    private function validRequest(PilotHttpRequest $r,array $s,HttpUser $u):bool
    {
        $origin=$r->server['HTTP_ORIGIN']??null;$fetch=$r->server['HTTP_SEC_FETCH_SITE']??null;
        $expectedOrigin='https://'.$r->host;
        $demoNonce=$r->server['FMONITOR_DEMO_LOOPBACK_NONCE']??null;
        if(PHP_SAPI==='cli-server'&&\is_string($demoNonce)&&\preg_match('/^[0-9a-f]{32}$/D',$demoNonce)===1
            &&\preg_match('/^127\.0\.0\.1:[1-9][0-9]*$/D',$r->host)===1)$expectedOrigin='http://'.$r->host;
        return $s['actor']===$u->id&&($origin===null||$origin===$expectedOrigin)&&($fetch===null||$fetch==='same-origin');
    }
    private function body(PilotHttpRequest $r,array $allowed):?array{$type=(string)($r->server['CONTENT_TYPE']??'');$length=$r->server['CONTENT_LENGTH']??null;if(!\preg_match('#^application/x-www-form-urlencoded(?:;\s*charset=UTF-8)?$#iD',$type)||!\is_string($length)||!\ctype_digit($length)||(int)$length>16384||(int)$length!==\strlen($r->body))return null;$out=[];$nextInstaller=0;foreach(\explode('&',$r->body)as$part){if($part==='')continue;$pair=\explode('=',$part,2);if(\preg_match('/%(?![0-9A-Fa-f]{2})/',($pair[0]??'').($pair[1]??''))===1)return null;$key=\rawurldecode(\str_replace('+',' ',$pair[0]));$value=\rawurldecode(\str_replace('+',' ',$pair[1]??''));if(\preg_match('/^installerTabIds\[([0-9]+)\]$/D',$key,$m)===1){if((int)$m[1]!==$nextInstaller++||$nextInstaller>500)return null;$key='installerTabIds[]';}if(!\in_array($key,$allowed,true)||!\mb_check_encoding($key,'UTF-8')||!\mb_check_encoding($value,'UTF-8'))return null;$out[$key][]=$value;if($key==='installerTabIds[]'&&\count($out[$key])>500)return null;}if(!isset($out['csrfToken'])||\count($out['csrfToken'])!==1)throw new InvalidCsrfRequest();foreach($out as$key=>$values)if($key!=='installerTabIds[]'&&\count($values)!==1)return null;return $out;}
    private function flashRedirect(array &$s,string $path,string $message,?string $field=null,bool $suppressOpen=false,array $selected=[]):PilotHttpResponse{if($selected===[]&&isset($s['pendingSelection']))$selected=$s['pendingSelection'];unset($s['pendingSelection']);$s['flash'][$path]=['message'=>$message,'field'=>$field,'suppressOpen'=>$suppressOpen,'selected'=>$selected];$_SESSION=$s;return $this->redirect($path);}
    private function pullFlash(array &$s,string $path):?array{$f=$s['flash'][$path]??null;unset($s['flash'][$path]);$_SESSION=$s;return $f;}
    private function redirect(string $path):PilotHttpResponse{return $this->response(303,'',['Location'=>$path]);}
    private function actorName(int $id):string{[$db,, $prefix]=$this->dependencies->commandResources();$s=$db->prepare("SELECT name FROM `{$prefix}users` WHERE id=? LIMIT 2");$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);return \count($rows)===1?(string)$rows[0]['name']:(string)$id;}
    public static function positive(string $v):bool{return \preg_match('/^[1-9][0-9]*$/D',$v)===1&&\strlen($v)<=19&&(\strlen($v)<19||\strcmp($v,'9223372036854775807')<=0);}
    private static function date(string $v):bool{return \preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m)===1&&$m[1]!=='0000'&&\checkdate((int)$m[2],(int)$m[3],(int)$m[1]);}
    private function response(int $status,string $body,array $extra=[],string $method='GET'):PilotHttpResponse{$headers=$extra+['Content-Type'=>'text/plain; charset=UTF-8','X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Content-Security-Policy'=>"default-src 'none'; style-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'",'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store'];$headers['Content-Length']=(string)\strlen($body);return new PilotHttpResponse($status,$headers,$method==='HEAD'?'':$body);}
}
