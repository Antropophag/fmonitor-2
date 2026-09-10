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
final class UserAccessController extends PilotController
{
 public $layout=false;
 public function behaviors():array{return['verbs'=>['class'=>VerbFilter::class,'actions'=>['invite'=>['POST'],'reissue'=>['POST'],'role'=>['POST'],'status'=>['POST'],'activate'=>['GET','POST']]],'access'=>['class'=>AccessControl::class,'except'=>['activate'],'rules'=>[['allow'=>true,'roles'=>['access.administer']]],'denyCallback'=>function():void{if(Yii::$app->user->isGuest){Yii::$app->user->setReturnUrl(Yii::$app->request->url);
Yii::$app->response->statusCode=303;
Yii::$app->response->headers->set('Location','/pilot/login');
}else throw new ForbiddenHttpException();
}]];
}
 public function actionIndex():string{try{$d=$this->owner()->directory((int)Yii::$app->user->id);
}catch(DomainException){throw new ForbiddenHttpException();
}catch(\Throwable$e){throw new \RuntimeException('User directory unavailable.',0,$e);
}return$this->render('@app/app/YiiRuntime/Views/users',['identity'=>Yii::$app->user->identity,'directory'=>$d,'notice'=>Yii::$app->session->getFlash('notice'),'error'=>Yii::$app->session->getFlash('error'),'invitation'=>Yii::$app->session->getFlash('invitation')]);
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
