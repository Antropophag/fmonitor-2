<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use Yii;use yii\filters\AccessControl;use yii\web\ForbiddenHttpException;
final class RolesController extends PilotController
{
 public $layout=false;
 public function behaviors():array{return['access'=>['class'=>AccessControl::class,'only'=>['index'],'rules'=>[['allow'=>true,'actions'=>['index'],'roles'=>['access.administer']]],'denyCallback'=>function():void{if(Yii::$app->user->isGuest){Yii::$app->user->setReturnUrl(Yii::$app->request->url);Yii::$app->response->statusCode=303;Yii::$app->response->headers->set('Location','/pilot/login');}else throw new ForbiddenHttpException();}]];}
 public function actionIndex():string{try{$directory=Yii::$app->localIdentity->roles();}catch(\Throwable$e){throw new \RuntimeException('Role directory unavailable.',0,$e);}return$this->render('@app/app/YiiRuntime/Views/roles',['identity'=>Yii::$app->user->identity,'directory'=>$directory]);}
}
