<?php declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use FMonitor2\Otiz\OtizSettlementV2Admission;use Yii;use yii\filters\{AccessControl,VerbFilter};use yii\web\{ForbiddenHttpException,Response};
final class OtizAdmissionController extends PilotController
{
 public $layout=false;public function behaviors():array{return['verbs'=>['class'=>VerbFilter::class,'actions'=>['record'=>['POST']]],'access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['composition_mismatch.produce']]],'denyCallback'=>static function():void{throw new ForbiddenHttpException();}]];}
 public function actionRecord():Response{$post=Yii::$app->request->post();foreach(['operationId','incidentId','objectId','sourceRevision','decision','acceptedOriginalId','applicationId','effectiveAttributionRevision']as$key)if(trim((string)($post[$key]??''))==='')throw new \yii\web\UnprocessableEntityHttpException('ADMISSION_EVIDENCE_REQUIRED');$result=(new OtizSettlementV2Admission(Yii::$app->db,$this->prefix()))->record((int)Yii::$app->user->id,(string)$post['operationId'],(string)$post['incidentId'],(int)$post['objectId'],(string)$post['sourceRevision'],(string)$post['decision'],(string)($post['reasonCode']??''),['acceptedOriginalId'=>(string)$post['acceptedOriginalId'],'applicationId'=>(string)$post['applicationId'],'effectiveAttributionRevision'=>(string)$post['effectiveAttributionRevision']],(new \DateTimeImmutable('now',new \DateTimeZone('Europe/Moscow')))->format(DATE_ATOM));$r=Yii::$app->response;$r->format=Response::FORMAT_JSON;$r->data=$result;return$r;}
 private function prefix():string{return(string)getenv('FMONITOR_PROCESS_TABLE_PREFIX');}
}
