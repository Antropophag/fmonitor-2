<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use DomainException;use FMonitor2\Otiz\MariaDbNativePremiumInputs;use FMonitor2\Otiz\MariaDbSnapshotStore;use FMonitor2\Otiz\SnapshotPublication;use Yii;use yii\filters\AccessControl;use yii\filters\VerbFilter;use yii\web\ForbiddenHttpException;use yii\web\NotFoundHttpException;use yii\web\Response;
final class OtizPublicationController extends PilotController
{
 public function behaviors():array{return['verbs'=>['class'=>VerbFilter::class,'actions'=>['calculate'=>['POST'],'accept'=>['POST']]],'access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['otiz.manage']]],'denyCallback'=>function():void{if(Yii::$app->user->isGuest){Yii::$app->user->setReturnUrl(Yii::$app->request->url);Yii::$app->response->statusCode=303;Yii::$app->response->headers->set('Location','/pilot/login');return;}throw new ForbiddenHttpException();}]];}
 public function actionCalculate():Response
 {
  $date=(string)Yii::$app->request->post('reportDate','');$parsed=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);if(!$parsed||$parsed->format('Y-m-d')!==$date)return$this->go('/pilot/otiz/payments?error=date');
  try{$id=$this->owner()->buildAndPublish($this->actor(),$date,(string)Yii::$app->request->post('operationId',''));return$this->go('/pilot/otiz/snapshots/'.$id.'?created=1');}
  catch(DomainException$e){if($e->getMessage()==='FORBIDDEN')throw new ForbiddenHttpException();if(in_array($e->getMessage(),['OPERATION_CONFLICT','INVALID_OPERATION_ID','INVALID_DATE'],true)){Yii::$app->response->statusCode=409;return Yii::$app->response;}throw$e;}
 }
 public function actionAccept(int$id):Response
 {
  try{$this->owner()->accept($this->actor(),$id);return$this->go('/pilot/otiz/snapshots/'.$id.'?accepted=1');}
  catch(DomainException$e){if($e->getMessage()==='NOT_FOUND')throw new NotFoundHttpException();if($e->getMessage()==='FORBIDDEN')throw new ForbiddenHttpException();$code=match($e->getMessage()){'IMMUTABLE'=>'immutable','BLOCKERS'=>'blockers','SNAPSHOT_INCOMPLETE'=>'incomplete',default=>throw$e};return$this->go('/pilot/otiz/snapshots/'.$id.'?error='.$code);}
 }
 private function owner():SnapshotPublication{$db=$this->db();$p=$this->prefix();$legacy=(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:$p);if(preg_match('/^[A-Za-z0-9_]+$/D',$legacy)!==1)throw new \RuntimeException();$inputs=new MariaDbNativePremiumInputs($db,$p,$legacy);return new SnapshotPublication(new MariaDbSnapshotStore($db,$p),$inputs->forDate(...),$this->now(...));}
 private function db():\mysqli{mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new \mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:3306));$db->set_charset('utf8mb4');return$db;}
 private function actor():int{return(int)Yii::$app->user->id;}private function prefix():string{$p=(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');if(preg_match('/^[A-Za-z0-9_]{1,25}$/D',$p)!==1)throw new \RuntimeException();return$p;}private function now():string{return(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format('Y-m-d\TH:i:sP');}private function go(string$p):Response{$r=Yii::$app->response;$r->statusCode=303;$r->headers->set('Location',$p);return$r;}
}
