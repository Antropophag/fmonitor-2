<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\InstallationProcess\MariaDbYiiCompletionRegister;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

final class CompletionRegisterController extends PilotController
{
    public $layout=false;

    public function behaviors():array
    {
        return[
            'verbs'=>['class'=>VerbFilter::class,'actions'=>['index'=>['GET', 'HEAD']]],
            'access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['@']]],'denyCallback'=>function():void{Yii::$app->user->setReturnUrl(Yii::$app->request->url);Yii::$app->response->statusCode=303;Yii::$app->response->headers->set('Location','/pilot/login');}],
        ];
    }

    public function actionIndex():string|Response
    {
        if(!Yii::$app->canonicalAccess->checkAccess((int)Yii::$app->user->id,'objects.read'))throw new ForbiddenHttpException();
        try{$model=(new MariaDbYiiCompletionRegister(Yii::$app->db,(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:''),(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:'')))->read($this->filters());
            return$this->render('@app/app/YiiRuntime/Views/completion-register',$model+['identity'=>Yii::$app->user->identity]);
        }catch(\OutOfRangeException){throw new BadRequestHttpException();}
        catch(\Throwable){$response=Yii::$app->response;$response->statusCode=503;$response->format=Response::FORMAT_RAW;$response->headers->set('Retry-After','60');$response->content="Service unavailable.\n";return$response;}
    }

    private function filters():array
    {
        $values=['mode'=>'pto_without_declaration','q'=>'','date'=>'','from'=>'','to'=>'','sort'=>'','page'=>'1'];$seen=[];$raw=(string)($_SERVER['QUERY_STRING']??'');
        foreach($raw===''?[]:explode('&',$raw)as$part){if($part===''||!str_contains($part,'=')||preg_match('/%(?![0-9A-Fa-f]{2})/',$part)===1)throw new BadRequestHttpException();[$key,$value]=explode('=',$part,2);$key=rawurldecode($key);if(!array_key_exists($key,$values)||isset($seen[$key])||str_contains($key,'['))throw new BadRequestHttpException();$seen[$key]=true;$values[$key]=rawurldecode(str_replace('+',' ',$value));}
        if(preg_match('/^[1-9][0-9]*$/D',$values['page'])!==1||strlen($values['page'])>18)throw new BadRequestHttpException();$values['q']=trim($values['q']);$values['page']=(int)$values['page'];return$values;
    }
}
