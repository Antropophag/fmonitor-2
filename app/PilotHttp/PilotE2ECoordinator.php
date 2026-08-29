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
        private readonly ProductionChecklistRenderer $checklists,
    ) { parent::__construct($identity,new ProductionPilotShellRenderer(),$dependencies); }

    public function handle(PilotHttpRequest $r):PilotHttpResponse
    {
        if(!$this->dependencies->pilotUiConfigured()||!$this->dependencies->prepareCommandConfigured()||!$this->dependencies->e2eConfigured())return $this->reads->handle($r);
        if($r->path==='/pilot/users')return $this->redirect('/pilot/admin/users');
        if(preg_match('#^/pilot/objects/([1-9][0-9]*)/checklist(?:/operations|/photos)?$#D',$r->path,$checklistMatch)===1&&self::positive($checklistMatch[1]))return $this->checklist($r,(int)$checklistMatch[1]);
        if(in_array($r->path,['/pilot/admin/users','/pilot/admin/roles'],true)||preg_match('#^/pilot/admin/users/([1-9][0-9]*)/roles/([1-9][0-9]*)$#D',$r->path,$userRoleMatch)===1)return $this->users($r,$userRoleMatch??[]);
        if($r->path==='/pilot/installers'){
            if(!\in_array($r->method,['GET','HEAD'],true))return $this->response(405,"Method not allowed.\n",['Allow'=>'GET, HEAD'],$r->method);
            try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
            try{$this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();$user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);$html=(new ProductionInstallerDirectoryRenderer())->render($user,$this->dependencies->installerDirectory()->read($this->dependencies->processDate()));return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
        }
        $route=$this->route($r->path);$cardId=null;if(\preg_match('#^/pilot/objects/([1-9][0-9]*)$#D',$r->path,$m)===1&&self::positive($m[1]))$cardId=(int)$m[1];
        if($route===null&&$cardId===null&&$r->path!=='/pilot/objects')return $this->reads->handle($r);
        if($route===null){if(!\in_array($r->method,['GET','HEAD'],true))return $this->response(405,"Method not allowed.\n",['Allow'=>'GET, HEAD'],$r->method);try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}try{$this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();$user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);return $cardId===null?$this->queue($r,$user):$this->card($r,$cardId,$user);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}}
        $allow=$route['kind']==='prepare'?'GET, HEAD, POST':($route['kind']==='artifact'?'GET, HEAD':'POST');
        if(!\in_array($r->method,\explode(', ',$allow),true))return $this->response(405,"Method not allowed.\n",['Allow'=>$allow],$r->method);
        try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
        try{
            $this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();
            $user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);
            $cap=match($route['kind']){'prepare','artifact','engineer'=>'assignment_order.prepare','registration'=>'assignment_order.confirm_registration','open'=>'installation.open'};
            if(!$this->dependencies->hasCapability($user->id,$cap))return $this->response(403,"Access denied.\n",[],$r->method);
            if($route['kind']==='artifact')return $this->artifact($r,$route,$user);
            if($r->method==='POST'){try{return $this->command($r,$route,$user);}catch(\LogicException $e){return $this->commandLogicException($r,$route,$user,$e);}}
            return $this->preparePage($r,$route['id'],$user);
        }catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
        catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
    }

    private function checklist(PilotHttpRequest $r,int $objectId):PilotHttpResponse
    {
        $isPage=str_ends_with($r->path,'/checklist');$isPhoto=str_ends_with($r->path,'/photos');$allow=$isPage?'GET, HEAD':'POST';if(!in_array($r->method,explode(', ',$allow),true))return $this->response(405,"Method not allowed.\n",['Allow'=>$allow],$r->method);
        try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
        try{
            $this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();$user=$this->dependencies->users()->resolveActiveUser($principal);if($user===null)return $this->response(403,"Access denied.\n",[],$r->method);$card=$this->dependencies->objectCards()->read($objectId);if($card===null)return $this->response(404,"Not found.\n",[],$r->method);
            [$db,$prefix,,$root,$now]=$this->dependencies->commandResources();$sync=new ChecklistSync($db,$prefix,$root,$now);$sync->ensureSchema();$roleAccess=$this->dependencies->canEditChecklist($user->id);$allowed=(bool)$card['opened']&&($roleAccess||(int)($card['controlEngineer']['userId']??0)===$user->id);
            if($isPage){[$session,$headers]=$this->session($r,$user,true);$csrf=$this->token($session,$user,$objectId);return $this->response(200,$this->checklists->render($user,$card,$roleAccess,$sync->projection($objectId),$csrf),['Content-Type'=>'text/html; charset=UTF-8']+$headers,$r->method);}
            if(!$allowed)return $this->json(403,['status'=>'rejected','message'=>'Недостаточно прав для изменения чек-листа.']);
            $declaredLength=$r->server['CONTENT_LENGTH']??null;if(!is_string($declaredLength)||!ctype_digit($declaredLength)||(int)$declaredLength>($isPhoto?5*1024*1024:32768))return $this->json(413,['status'=>'rejected','message'=>'Размер операции превышает допустимый.']);$r=new PilotHttpRequest($r->method,$r->path,$r->host,$r->serverIdentity,$r->server,(string)file_get_contents('php://input'));if((int)$declaredLength!==strlen($r->body))return $this->json(400,['status'=>'rejected','message'=>'Некорректный размер операции.']);[$session]=$this->session($r,$user,false);$csrf=(string)($r->server['HTTP_X_FM2_CSRF']??'');if($session===null||!$this->validChecklistRequest($r,$session,$user)||!$this->validToken($session,$csrf,$user,$objectId))return $this->json(403,['status'=>'rejected','message'=>'Сеанс проверки истёк. Обновите страницу.']);
            if($isPhoto){$metadata=$r->server['HTTP_X_FM2_OPERATION']??'';if(!is_string($metadata)||strlen($metadata)>4096)return $this->json(400,['status'=>'rejected','message'=>'Некорректные данные фотографии.']);$operation=json_decode(base64_decode($metadata,true)?:'',true,16,JSON_THROW_ON_ERROR);if(!is_array($operation))return $this->json(400,['status'=>'rejected','message'=>'Некорректные данные фотографии.']);$result=$sync->accept($objectId,$user,$operation,$r->body);}
            else{if(!preg_match('#^application/json(?:;\s*charset=UTF-8)?$#iD',(string)($r->server['CONTENT_TYPE']??''))||strlen($r->body)>32768)return $this->json(400,['status'=>'rejected','message'=>'Некорректный формат операции.']);$operation=json_decode($r->body,true,16,JSON_THROW_ON_ERROR);if(!is_array($operation))return $this->json(400,['status'=>'rejected','message'=>'Некорректный формат операции.']);$result=$sync->accept($objectId,$user,$operation);}
            $result['projection']=$sync->projection($objectId);return $this->json(in_array($result['status'],['accepted','duplicate'],true)?200:($result['status']==='conflict'?409:422),$result);
        }catch(\JsonException){return $this->json(400,['status'=>'rejected','message'=>'Некорректные данные операции.']);}catch(CssAssetUnavailable|PilotHttpInfrastructureUnavailable){return $this->json(503,['status'=>'retryable','message'=>'Сервис временно недоступен.']);}catch(\Throwable){return $this->json(503,['status'=>'retryable','message'=>'Сервис временно недоступен.']);}
    }

    private function users(PilotHttpRequest $r,array $route):PilotHttpResponse
    {
        $isCommand=$route!==[];$allow=$isCommand?'POST':'GET, HEAD';if(!in_array($r->method,explode(', ',$allow),true))return $this->response(405,"Method not allowed.\n",['Allow'=>$allow],$r->method);
        try{$principal=$this->identity->resolve($r->serverIdentity);}catch(InvalidServerIdentity){return $this->response(401,"Authentication required.\n",[],$r->method);}
        try{$this->dependencies->css()->readBytes();$this->dependencies->pilotCss()->readBytes();$actor=$this->dependencies->users()->resolveActiveUser($principal);if($actor===null||!$this->dependencies->hasCapability($actor->id,'assignment_order.prepare'))return $this->response(403,"Access denied.\n",[],$r->method);[$db,$prefix,,,$now]=$this->dependencies->commandResources();$directory=new MariaDbPilotUserDirectory($db,$prefix);
            if(!$isCommand){$data=$directory->read();if($r->path==='/pilot/admin/roles'){$html=(new ProductionUserDirectoryRenderer())->renderRoles($actor,$data);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8'],$r->method);}[$session,$headers]=$this->session($r,$actor,true);$tokens=[];foreach($data['users']as$user)$tokens[$user['id']]=$this->token($session,$actor,$user['id']);$html=(new ProductionUserDirectoryRenderer())->renderUsers($actor,$data,$tokens);return $this->response(200,$html,['Content-Type'=>'text/html; charset=UTF-8']+$headers,$r->method);}
            $r=new PilotHttpRequest($r->method,$r->path,$r->host,$r->serverIdentity,$r->server,(string)file_get_contents('php://input'));$userId=(int)$route[1];$roleId=(int)$route[2];[$session]=$this->session($r,$actor,false);if($session===null||!$this->validRequest($r,$session,$actor))return $this->response(403,"Invalid request.\n");try{$fields=$this->body($r,['csrfToken','action']);}catch(InvalidCsrfRequest){return $this->response(403,"Invalid request.\n");}if($fields===null||!$this->consume($session,$fields['csrfToken'][0]??'',$actor,$userId))return $this->response(403,"Invalid request.\n");$action=$fields['action'][0]??'';if(!$directory->changeRole($userId,$roleId,$action,$actor->id,$now))return $this->response(400,"Bad request.\n");return $this->redirect('/pilot/admin/users');
        }catch(PilotHttpInfrastructureUnavailable|CssAssetUnavailable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}catch(\Throwable){return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60'],$r->method);}
    }

    public function card(PilotHttpRequest $r,int $id,HttpUser $user):PilotHttpResponse
    {
        $card=$this->dependencies->objectCards()->read($id);if($card===null)return $this->response(404,"Not found.\n",[],$r->method);
        $session=null;$headers=[];$capPrepare=$this->dependencies->hasCapability($user->id,'assignment_order.prepare');$capRegister=$this->dependencies->hasCapability($user->id,'assignment_order.confirm_registration');$capOpen=$this->dependencies->hasCapability($user->id,'installation.open');
        [$existingSession]=$this->session($r,$user,false);$flash=$existingSession===null?null:$this->pullFlash($existingSession,$r->path);if(($flash['suppressOpen']??false)===true)$capOpen=false;
        $card['engineers']=$capPrepare?$this->engineers():[];$card['canAssignEngineer']=$capPrepare;
        $needsCommand=$capPrepare||($card['order']!==null&&$card['order']['status']==='prepared'&&$capRegister)||($card['order']!==null&&$card['order']['status']==='registered'&&!$card['opened']&&$capOpen);
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
        $allowed=match($route['kind']){'prepare'=>['csrfToken','processRevision','installerTabIds[]'],'engineer'=>['csrfToken','processRevision','controlEngineerUserId'],'registration'=>['csrfToken','processRevision','assignmentOrderVersion','registrationNumber'],'open'=>['csrfToken','processRevision','assignmentOrderVersion','actualStartDate']};
        try{$fields=$this->body($r,$allowed);}catch(InvalidCsrfRequest){return $this->response(403,"Invalid request.\n");}if($fields===null)return $this->response(400,"Bad request.\n");
        if(!$this->consume($session,$fields['csrfToken'][0]??'', $user,$route['id']))return $this->response(403,"Invalid request.\n");
        $card=$this->dependencies->objectCards()->read($route['id']);if($card===null)return $this->response(404,"Not found.\n");
        if(($fields['processRevision'][0]??'')!==$this->revision($session,$route['id']))return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Данные объекта монтажа изменились. Проверьте актуальное состояние и повторите действие.');
        [$connection,$processPrefix,$legacyPrefix,$root,$now]=$this->dependencies->commandResources();$clock=new class($now) implements Clock{public function __construct(private string $value){}public function now():string{return $this->value;}};$config=new ProductionInstallationProcessConfig($processPrefix,$legacyPrefix,$root);$process=ProductionInstallationProcessFactory::create($connection,$config,$clock);
        if($route['kind']==='engineer')return $this->assignEngineer($session,$route['id'],$fields,$user,$connection,$processPrefix,$now);
        if($route['kind']==='prepare'){
            $path=$r->path;$error=null;$installers=$fields['installerTabIds[]']??[];$eligible=$this->dependencies->prepareForms()->read($route['id'],$this->dependencies->processDate());$installerIds=\array_map(static fn(array $x):string=>(string)$x['tabId'],$eligible['installers']);$engineer=$eligible['engineer']??null;
            if($installers===[]||\count($installers)!==\count(\array_unique($installers))||\count(\array_filter($installers,[self::class,'positive']))!==\count($installers))$error=['installers','Выберите хотя бы одного монтажника.'];
            elseif(!\is_array($engineer)||!self::positive((string)($engineer['userId']??'')))$error=['engineer','Сначала назначьте инженера строительного контроля в карточке объекта.'];
            if($error===null)foreach($installers as$value)if(!\in_array($value,$installerIds,true)){$error=['installers','Состав монтажников изменился. Проверьте доступных сотрудников.'];break;}
            if($error!==null)return $this->flashRedirect($session,$path,$error[1],$error[0],false,['installers'=>\array_values(\array_intersect($installers,$installerIds??[]))]);
            $session['pendingSelection']=['installers'=>\array_values(\array_intersect($installers,$installerIds))];$_SESSION=$session;$result=$process->prepareAssignmentOrder($route['id'],\array_map('intval',$installers),(int)$engineer['userId'],$user->id);if(($result['accepted']??false)===true){if(($result['status']??null)!=='prepared'||!self::positive((string)($result['assignmentOrderVersion']??'')))return $this->response(503,"Service unavailable.\n",['Retry-After'=>'60']);return $this->flashRedirect($session,'/pilot/objects/'.$route['id'],'Распоряжение подготовлено.');}return $this->violation($session,$route,$result,$path);
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
        foreach([['prepare','#^/pilot/objects/([1-9][0-9]*)/assignment-order/prepare$#D'],['engineer','#^/pilot/objects/([1-9][0-9]*)/control-engineer$#D'],['artifact','#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/artifacts/(order|appendix)$#D'],['registration','#^/pilot/objects/([1-9][0-9]*)/assignment-orders/([1-9][0-9]*)/registration$#D'],['open','#^/pilot/objects/([1-9][0-9]*)/open$#D']]as[$kind,$pattern])if(\preg_match($pattern,$path,$m)===1){foreach(\array_slice($m,1)as$x)if(\ctype_digit($x)&&!self::positive($x))return null;return ['kind'=>$kind,'id'=>(int)$m[1]]+(\in_array($kind,['artifact','registration'],true)?['version'=>(int)$m[2]]:[])+($kind==='artifact'?['type'=>$m[3]]:[]);}return null;
    }
    private function engineers():array
    {
        [$db,$prefix,$legacy]=$this->dependencies->commandResources();$pilotUsers=$prefix.'fm2_pilot_users';$table=$db->prepare('SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');$table->bind_param('s',$pilotUsers);$table->execute();if($table->get_result()->fetch_assoc()!==null){$sql="SELECT u.user_id,u.full_name FROM `{$pilotUsers}` u JOIN `{$prefix}fm2_pilot_user_roles` ur ON ur.user_id=u.user_id JOIN `{$prefix}fm2_pilot_roles` r ON r.role_id=ur.role_id WHERE u.status=1 AND r.status=1 AND r.name='Строительный контроль' ORDER BY u.full_name,u.user_id LIMIT 101";$rows=$db->query($sql)->fetch_all(MYSQLI_ASSOC);$mapped=\array_map(static fn(array $x):array=>['userId'=>(int)$x['user_id'],'fullName'=>(string)$x['full_name'],'position'=>'Строительный контроль'],$rows);}else{$rows=$db->query("SELECT u.id,u.name FROM `{$legacy}users` u JOIN `{$legacy}users_roles` r ON r.id=u.role_id WHERE u.status=1 AND r.status=1 AND r.name='Строительный контроль' ORDER BY u.name,u.id LIMIT 101")->fetch_all(MYSQLI_ASSOC);$mapped=\array_map(static fn(array $x):array=>['userId'=>(int)$x['id'],'fullName'=>(string)$x['name'],'position'=>'Инженер строительного контроля'],$rows);}if(\count($mapped)>100)throw new PilotHttpInfrastructureUnavailable();return $mapped;
    }
    private function assignEngineer(array &$session,int $objectId,array $fields,HttpUser $actor,\mysqli $db,string $prefix,string $now):PilotHttpResponse
    {
        $values=$fields['controlEngineerUserId']??[];if(\count($values)!==1||!self::positive($values[0]))return $this->flashRedirect($session,"/pilot/objects/{$objectId}",'Выберите инженера строительного контроля.','controlEngineerUserId');$selected=null;foreach($this->engineers()as$engineer)if($engineer['userId']===(int)$values[0]){$selected=$engineer;break;}if($selected===null)return $this->flashRedirect($session,"/pilot/objects/{$objectId}",'Выбранный инженер больше недоступен. Выберите другого.','controlEngineerUserId');
        $db->begin_transaction();try{$case=$db->prepare("SELECT id,lock_version FROM `{$prefix}fm2_installation_cases` WHERE legacy_installation_object_id=? LIMIT 2 FOR UPDATE");$case->bind_param('i',$objectId);$case->execute();$rows=$case->get_result()->fetch_all(MYSQLI_ASSOC);if(\count($rows)!==1)throw new PilotHttpInfrastructureUnavailable();$card=$this->dependencies->objectCards()->read($objectId);$previous=$card['controlEngineer']??null;if(($previous['userId']??null)===$selected['userId']){$db->commit();return $this->flashRedirect($session,"/pilot/objects/{$objectId}",'Инженер строительного контроля уже назначен.');}$payload=\json_encode(['previousEngineer'=>$previous,'engineer'=>$selected],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$event=$db->prepare("INSERT INTO `{$prefix}fm2_process_events`(installation_case_id,event_type,occurred_at,actor_user_id,payload_json) VALUES(?,'control_engineer_changed',?,?,?)");$caseId=(int)$rows[0]['id'];$actorId=$actor->id;$event->bind_param('isis',$caseId,$now,$actorId,$payload);$event->execute();$update=$db->prepare("UPDATE `{$prefix}fm2_installation_cases` SET updated_at=?,lock_version=lock_version+1 WHERE id=? AND lock_version=?");$version=(int)$rows[0]['lock_version'];$update->bind_param('sii',$now,$caseId,$version);$update->execute();if($update->affected_rows!==1)throw new PilotHttpInfrastructureUnavailable();$db->commit();return $this->flashRedirect($session,"/pilot/objects/{$objectId}",'Инженер строительного контроля назначен.');}catch(\Throwable $error){$db->rollback();throw $error;}
    }
    private function session(PilotHttpRequest $r,HttpUser $user,bool $create):array
    {
        $headers=[];if(\session_status()!==PHP_SESSION_ACTIVE){\session_name('fm2pilot');\ini_set('session.use_cookies','0');$incoming=null;if(\preg_match('/(?:^|;\s*)fm2pilot=([A-Za-z0-9,-]{16,128})/',(string)($r->server['HTTP_COOKIE']??''),$m)===1)$incoming=$m[1];if($incoming===null&&!$create)return [null,[]];if($incoming!==null)\session_id($incoming);if(!@\session_start())throw new PilotHttpInfrastructureUnavailable();if($incoming===null)$headers=['Set-Cookie'=>'fm2pilot='.\session_id().(self::trustedDemo($r)?'':'; Secure').'; HttpOnly; SameSite=Strict; Path=/pilot'];}
        if(isset($_SESSION['actor'])&&$_SESSION['actor']!==$user->id){\session_regenerate_id(true);$_SESSION=[];}
        if(!isset($_SESSION['actor'])){if(!$create)return [null,[]];$_SESSION=['actor'=>$user->id,'secret'=>\random_bytes(32),'tokens'=>[],'flash'=>[]];}
        return [&$_SESSION,$headers];
    }
    private function token(array &$s,HttpUser $u,int $id):string{$t=\bin2hex(\random_bytes(16));$s['tokens'][$t]=['actor'=>$u->id,'id'=>$id,'at'=>\time()];$_SESSION=$s;return $t;}
    private function validToken(array $s,string $token,HttpUser $u,int $id):bool{$x=$s['tokens'][$token]??null;return \is_array($x)&&($x['actor']??null)===$u->id&&($x['id']??null)===$id&&\time()-(int)($x['at']??0)<=1800;}
    private function consume(array &$s,string $token,HttpUser $u,int $id):bool{$x=$s['tokens'][$token]??null;unset($s['tokens'][$token]);$_SESSION=$s;return \is_array($x)&&$x['actor']===$u->id&&$x['id']===$id&&\time()-$x['at']<=1800;}
    private function revision(array $s,int $id):string{[$db,$prefix]=$this->dependencies->commandResources();$q=$db->prepare("SELECT lock_version FROM `{$prefix}fm2_installation_cases` WHERE legacy_installation_object_id=? LIMIT 2");$q->bind_param('i',$id);$q->execute();$rows=$q->get_result()->fetch_all(MYSQLI_ASSOC);if(\count($rows)!==1)throw new PilotHttpInfrastructureUnavailable();return \hash_hmac('sha256',$id.':'.$rows[0]['lock_version'],$s['secret']);}
    private function validRequest(PilotHttpRequest $r,array $s,HttpUser $u):bool
    {
        $origin=$r->server['HTTP_ORIGIN']??null;$fetch=$r->server['HTTP_SEC_FETCH_SITE']??null;
        $trustedDemo=self::trustedDemo($r);$expectedOrigin=($trustedDemo?'http://':'https://').$r->host;
        $originAllowed=$origin===null||$origin===$expectedOrigin||($trustedDemo&&$origin==='null');
        return $s['actor']===$u->id&&$originAllowed&&($fetch===null||$fetch==='same-origin');
    }
    private function validChecklistRequest(PilotHttpRequest $r,array $s,HttpUser $u):bool
    {
        $expected=(self::trustedDemo($r)?'http://':'https://').$r->host;
        return ($s['actor']??null)===$u->id&&($r->server['HTTP_ORIGIN']??null)===$expected&&($r->server['HTTP_SEC_FETCH_SITE']??null)==='same-origin';
    }
    private static function trustedDemo(PilotHttpRequest $r):bool
    {
        $nonce=$r->server['FMONITOR_DEMO_LOOPBACK_NONCE']??null;$trustedHost=$r->server['FMONITOR_DEMO_TRUSTED_REQUEST_HOST']??null;
        return PHP_SAPI==='cli-server'&&\is_string($nonce)&&\preg_match('/^[0-9a-f]{32}$/D',$nonce)===1
            &&\is_string($trustedHost)&&\preg_match('/^127\.0\.0\.1:([1-9][0-9]{3,4})$/D',$trustedHost,$parts)===1
            &&(int)$parts[1]>=1024&&(int)$parts[1]<=65535&&$r->host===$trustedHost;
    }
    private function body(PilotHttpRequest $r,array $allowed):?array{$type=(string)($r->server['CONTENT_TYPE']??'');$length=$r->server['CONTENT_LENGTH']??null;if(!\preg_match('#^application/x-www-form-urlencoded(?:;\s*charset=UTF-8)?$#iD',$type)||!\is_string($length)||!\ctype_digit($length)||(int)$length>16384||(int)$length!==\strlen($r->body))return null;$out=[];$nextInstaller=0;foreach(\explode('&',$r->body)as$part){if($part==='')continue;$pair=\explode('=',$part,2);if(\preg_match('/%(?![0-9A-Fa-f]{2})/',($pair[0]??'').($pair[1]??''))===1)return null;$key=\rawurldecode(\str_replace('+',' ',$pair[0]));$value=\rawurldecode(\str_replace('+',' ',$pair[1]??''));if(\preg_match('/^installerTabIds\[([0-9]+)\]$/D',$key,$m)===1){if((int)$m[1]!==$nextInstaller++||$nextInstaller>500)return null;$key='installerTabIds[]';}if(!\in_array($key,$allowed,true)||!\mb_check_encoding($key,'UTF-8')||!\mb_check_encoding($value,'UTF-8'))return null;$out[$key][]=$value;if($key==='installerTabIds[]'&&\count($out[$key])>500)return null;}if(!isset($out['csrfToken'])||\count($out['csrfToken'])!==1)throw new InvalidCsrfRequest();foreach($out as$key=>$values)if($key!=='installerTabIds[]'&&\count($values)!==1)return null;return $out;}
    private function flashRedirect(array &$s,string $path,string $message,?string $field=null,bool $suppressOpen=false,array $selected=[]):PilotHttpResponse{if($selected===[]&&isset($s['pendingSelection']))$selected=$s['pendingSelection'];unset($s['pendingSelection']);$s['flash'][$path]=['message'=>$message,'field'=>$field,'suppressOpen'=>$suppressOpen,'selected'=>$selected];$_SESSION=$s;return $this->redirect($path);}
    private function pullFlash(array &$s,string $path):?array{$f=$s['flash'][$path]??null;unset($s['flash'][$path]);$_SESSION=$s;return $f;}
    private function redirect(string $path):PilotHttpResponse{return $this->response(303,'',['Location'=>$path]);}
    private function actorName(int $id):string{[$db,, $prefix]=$this->dependencies->commandResources();$s=$db->prepare("SELECT name FROM `{$prefix}users` WHERE id=? LIMIT 2");$s->bind_param('i',$id);$s->execute();$rows=$s->get_result()->fetch_all(MYSQLI_ASSOC);return \count($rows)===1?(string)$rows[0]['name']:(string)$id;}
    public static function positive(string $v):bool{return \preg_match('/^[1-9][0-9]*$/D',$v)===1&&\strlen($v)<=19&&(\strlen($v)<19||\strcmp($v,'9223372036854775807')<=0);}
    private static function date(string $v):bool{return \preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D',$v,$m)===1&&$m[1]!=='0000'&&\checkdate((int)$m[2],(int)$m[3],(int)$m[1]);}
    private function response(int $status,string $body,array $extra=[],string $method='GET'):PilotHttpResponse{$checklist=($extra['Content-Type']??'')==='text/html; charset=UTF-8'&&\str_contains($body,'data-checklist');$csp=$checklist?"default-src 'none'; style-src 'self'; script-src 'self'; worker-src 'self'; connect-src 'self'; img-src 'self' blob:; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'":"default-src 'none'; style-src 'self'; script-src 'self'; img-src 'self'; font-src 'self'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'";$headers=$extra+['Content-Type'=>'text/plain; charset=UTF-8','X-Content-Type-Options'=>'nosniff','Referrer-Policy'=>'no-referrer','X-Frame-Options'=>'DENY','Content-Security-Policy'=>$csp,'Permissions-Policy'=>'camera=(), microphone=(), geolocation=()','Cross-Origin-Opener-Policy'=>'same-origin','Cache-Control'=>'no-store'];$headers['Content-Length']=(string)\strlen($body);return new PilotHttpResponse($status,$headers,$method==='HEAD'?'':$body);}
    private function json(int $status,array $payload):PilotHttpResponse{return $this->response($status,\json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),['Content-Type'=>'application/json; charset=UTF-8']);}
}
