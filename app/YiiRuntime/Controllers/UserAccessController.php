<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use DomainException;
use FMonitor2\IdentityAccess\YiiUserAccess;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use FMonitor2\IdentityAccess\LegacyIdentityLinkCommand;
use FMonitor2\IdentityAccess\ProductionLegacyIdentityLinkFactory;
final class UserAccessController extends PilotController
{
 public $layout=false;
 public function beforeAction($action):bool{if($action->id==='legacy-link'&&Yii::$app->request->isPost){$this->enableCsrfValidation=false;if(max((int)($_SERVER['CONTENT_LENGTH']??0),strlen(Yii::$app->request->rawBody))>8192){Yii::$app->response->statusCode=413;return false;}if(!str_starts_with((string)Yii::$app->request->contentType,'application/x-www-form-urlencoded')){Yii::$app->response->statusCode=415;return false;}}return parent::beforeAction($action);}
 public function behaviors():array{return['verbs'=>['class'=>VerbFilter::class,'actions'=>['invite'=>['POST'],'reissue'=>['POST'],'role'=>['POST'],'status'=>['POST'],'legacy-link'=>['POST'],'activate'=>['GET','POST']]],'access'=>['class'=>AccessControl::class,'except'=>['activate'],'rules'=>[['allow'=>true,'roles'=>['access.administer']]],'denyCallback'=>function():void{if(Yii::$app->user->isGuest){Yii::$app->user->setReturnUrl(Yii::$app->request->url);
Yii::$app->response->statusCode=303;
Yii::$app->response->headers->set('Location','/pilot/login');
}else throw new ForbiddenHttpException();
}]];
}
 public function actionIndex():string{try{$actor=(int)Yii::$app->user->id;$d=$this->owner()->directory($actor);$db=$this->legacyDb();$process=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');$legacy=(string)getenv('FMONITOR_LEGACY_TABLE_PREFIX');if($legacy==='')$legacy=$process;try{$links=ProductionLegacyIdentityLinkFactory::create($db,$process,$legacy)->directory($actor);}finally{$db->close();}
}catch(DomainException){throw new ForbiddenHttpException();
}catch(\Throwable$e){throw new \RuntimeException('User directory unavailable.',0,$e);
}return$this->render('@app/app/YiiRuntime/Views/users',['identity'=>Yii::$app->user->identity,'directory'=>$d,'legacyLinks'=>$links,'notice'=>Yii::$app->session->getFlash('notice'),'error'=>Yii::$app->session->getFlash('error'),'invitation'=>Yii::$app->session->getFlash('invitation')]);
}
 public function actionLegacyLink(int$id):Response{
  if(strlen(http_build_query(Yii::$app->request->post()))>8192){Yii::$app->response->statusCode=413;return Yii::$app->response;}if(!Yii::$app->request->validateCsrfToken())throw new BadRequestHttpException();
  if((int)($_SERVER['CONTENT_LENGTH']??0)>8192){Yii::$app->response->statusCode=413;return Yii::$app->response;}if(!str_starts_with((string)Yii::$app->request->contentType,'application/x-www-form-urlencoded')){Yii::$app->response->statusCode=415;return Yii::$app->response;}
  $request=$this->scalar('requestId');$legacy=$this->scalar('legacyUserId');$reason=Yii::$app->request->post('correctionReason');if(!is_string($reason)&&$reason!==null)throw new BadRequestHttpException();
  try{$command=new LegacyIdentityLinkCommand($request,$id,filter_var($legacy,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])?:0,(int)Yii::$app->user->id,$reason);}catch(\InvalidArgumentException){if(preg_match('/^[0-9a-f-]{36}$/D',$request)!==1)throw new BadRequestHttpException();Yii::$app->response->statusCode=422;return Yii::$app->response;}
  $db=$this->legacyDb();$process=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');$legacy=(string)getenv('FMONITOR_LEGACY_TABLE_PREFIX');if($legacy==='')$legacy=$process;try{$r=ProductionLegacyIdentityLinkFactory::create($db,$process,$legacy)->link($command);}finally{$db->close();}
  if($r['status']==='failed'){Yii::$app->response->statusCode=503;return Yii::$app->response;}if($r['status']==='conflict'){Yii::$app->response->statusCode=409;return Yii::$app->response;}if($r['status']==='rejected'){Yii::$app->response->statusCode=$r['reasonCode']==='authorization_denied'?403:422;return Yii::$app->response;}return$this->redirectUsers(true);
 }
 public function actionInvite():Response{$email=$this->scalar('email');
$name=$this->scalar('fullName');
try{$r=$this->owner()->invite((int)Yii::$app->user->id,$email,$name);
}catch(\Throwable$e){throw new \RuntimeException('Invitation unavailable.',0,$e);
}if($r['status']==='access_denied')throw new ForbiddenHttpException();
if($r['status']==='issued'){$this->success('Пользователь приглашён.',$r);
}else Yii::$app->session->setFlash('error','Проверьте рабочий email, имя и отсутствие дубликата.');
return$this->redirectUsers(true);
}
 public function actionReissue(int$id):Response{try{$r=$this->owner()->reissue((int)Yii::$app->user->id,$id);
}catch(\Throwable$e){throw new \RuntimeException('Invitation unavailable.',0,$e);
}if($r['status']==='access_denied')throw new ForbiddenHttpException();
if($r['status']==='issued')$this->success('Новая ссылка приглашения создана.',$r);
else Yii::$app->session->setFlash('error','Новую ссылку можно выдать только приглашённому пользователю.');
return$this->redirectUsers(true);
}
 public function actionRole(int$id,?int$roleId=null):Response{$action=$this->scalar('action');
if($roleId===null){$raw=$this->scalar('roleId');
if(filter_var($raw,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]])===false)throw new BadRequestHttpException();
$roleId=(int)$raw;
}try{$r=$this->owner()->changeRole((int)Yii::$app->user->id,$id,$roleId,$action);
}catch(\Throwable$e){throw new \RuntimeException('Role change unavailable.',0,$e);
}if($r['status']==='access_denied')throw new ForbiddenHttpException();
if($r['status']==='invalid')throw new BadRequestHttpException();
if($r['status']==='changed')Yii::$app->session->setFlash('notice','Роль изменена.');
return$this->redirectUsers($r['status']==='changed');
}
 public function actionStatus(int$id):Response{$action=$this->scalar('action');
try{$r=$this->owner()->changeStatus((int)Yii::$app->user->id,$id,$action);
}catch(\Throwable$e){throw new \RuntimeException('Status change unavailable.',0,$e);
}if($r['status']==='access_denied')throw new ForbiddenHttpException();
if($r['status']!=='changed')throw new BadRequestHttpException();
Yii::$app->session->setFlash('notice',$action==='block'?'Доступ заблокирован.':'Доступ восстановлен.');
return$this->redirectUsers(true);
}
 public function actionActivate():string
 { $request=Yii::$app->request;
$token=$request->isPost?$this->scalar('token'):$request->get('token','');
if(!is_string($token))throw new BadRequestHttpException();
$password=$request->isPost?$this->scalar('password'):'';
$confirmation=$request->isPost?$this->scalar('passwordConfirmation'):'';
$entry=null;
$done=false;
$error='';
try{$entry=$this->owner()->invitation($token);
if($request->isPost){$r=$this->owner()->activate($token,$password,$confirmation);
if($r['status']==='activated')$done=true;
elseif($r['status']==='invalid_password'){$error=(string)$r['reason'];
$entry=$this->owner()->invitation($token);
}else{$entry=null;
$error='Ссылка активации недействительна или истекла.';
}}elseif($entry===null)$error='Ссылка активации недействительна или истекла.';
}catch(\Throwable$e){throw new \RuntimeException('Activation unavailable.',0,$e);
}return$this->render('@app/app/YiiRuntime/Views/activate',['token'=>$token,'entry'=>$entry,'done'=>$done,'error'=>$error]);
 }
 private function owner():YiiUserAccess{return Yii::$app->userAccess;
}
 private function legacyDb():\mysqli{$db=new \mysqli((string)getenv('FMONITOR_DB_HOST'),(string)getenv('FMONITOR_DB_USER'),(string)getenv('FMONITOR_DB_PASSWORD'),(string)getenv('FMONITOR_DB_NAME'),(int)getenv('FMONITOR_DB_PORT'));$db->set_charset('utf8mb4');return$db;}
 private function scalar(string$name):string{$v=Yii::$app->request->post($name,'');
if(!is_string($v))throw new BadRequestHttpException();
return$v;
}
 private function success(string$message,array$r):void{Yii::$app->session->setFlash('notice',$message);
Yii::$app->session->setFlash('invitation','/pilot/activate?token='.$r['token']);
}
 private function redirectUsers(bool$rotate):Response{if($rotate)Yii::$app->request->getCsrfToken(true);
$response=Yii::$app->response;
$response->statusCode=303;
$response->headers->set('Location','/pilot/admin/users');
return$response;
}
}
