<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\Workforce\MariaDbInstallerUtilization;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class InstallerUtilizationController extends PilotController
{
    public $layout = false;

    public function behaviors(): array
    {
        return [
            'verbs'=>['class'=>VerbFilter::class,'actions'=>['index'=>['GET','HEAD'],'view'=>['GET','HEAD']]],
            'access'=>['class'=>AccessControl::class,'rules'=>[['allow'=>true,'roles'=>['@']]],'denyCallback'=>function(): void {Yii::$app->user->setReturnUrl(Yii::$app->request->url);Yii::$app->response->statusCode=303;Yii::$app->response->headers->set('Location','/pilot/login');}],
        ];
    }

    public function actionIndex(): string|Response
    {
        if ((string)($_SERVER['QUERY_STRING']??'')!=='') throw new BadRequestHttpException();
        return $this->redirect('/pilot/dashboard#installer-utilization',303);
    }

    public function actionView(string $tabId): string|Response
    {
        if (preg_match('/^[1-9][0-9]*$/D',$tabId)!==1 || strlen($tabId)>18) throw new NotFoundHttpException();
        $query=$this->query(['historyPage'=>'1','return'=>'']);$page=$this->positive($query['historyPage']);$returnUrl=$this->returnUrl($query['return']);
        try {$actor=(int)Yii::$app->user->id;return $this->respond(static fn(MariaDbInstallerUtilization $read): array => $read->card((int)$tabId,$page,$actor)+['returnUrl'=>$returnUrl], '@app/app/YiiRuntime/Views/installer-card');}
        catch (\DomainException) {throw new NotFoundHttpException();}
    }

    private function respond(callable $load, string $view): string|Response
    {
        if (!Yii::$app->canonicalAccess->checkAccess((int)Yii::$app->user->id,'installers.read')) throw new ForbiddenHttpException();
        try {
            $read=new MariaDbInstallerUtilization(Yii::$app->db,(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:''),(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:''));
            $model=$load($read);return $this->stableCsrf($this->render($view,$model+['identity'=>Yii::$app->user->identity]));
        } catch (\OutOfRangeException) {throw new BadRequestHttpException();}
        catch (\DomainException $e) {throw $e;}
        catch (\Throwable) {$response=Yii::$app->response;$response->statusCode=503;$response->format=Response::FORMAT_RAW;$response->headers->set('Retry-After','60');$response->content="Service unavailable.\n";return $response;}
    }

    private function query(array $defaults,array $choices=[]): array
    {
        $raw=(string)($_SERVER['QUERY_STRING']??'');$seen=[];$values=$defaults;
        if($raw!=='')foreach(explode('&',$raw)as$part){if($part===''||!str_contains($part,'=')||preg_match('/%(?![0-9A-Fa-f]{2})/',$part)===1)throw new BadRequestHttpException();[$key,$value]=explode('=',$part,2);$key=rawurldecode($key);if(!array_key_exists($key,$defaults)||isset($seen[$key])||str_contains($key,'['))throw new BadRequestHttpException();$seen[$key]=true;$values[$key]=rawurldecode(str_replace('+',' ',$value));}
        foreach($choices as$key=>$allowed)if(!in_array($values[$key],$allowed,true))throw new BadRequestHttpException();
        return $values;
    }
    private function positive(string $value): int {if(preg_match('/^[1-9][0-9]*$/D',$value)!==1||strlen($value)>18)throw new BadRequestHttpException();return(int)$value;}
    private function returnUrl(string$value):string
    {
        if($value==='')return'/pilot/installers';if(strlen($value)>600)throw new BadRequestHttpException();$allowed=['q'=>'','current'=>'','upcoming'=>'','page'=>'1'];$seen=[];
        foreach(explode('&',$value)as$part){if($part===''||!str_contains($part,'=')||preg_match('/%(?![0-9A-Fa-f]{2})/',$part)===1)throw new BadRequestHttpException();[$key,$item]=explode('=',$part,2);$key=rawurldecode($key);if(!array_key_exists($key,$allowed)||isset($seen[$key])||str_contains($key,'['))throw new BadRequestHttpException();$seen[$key]=true;$allowed[$key]=rawurldecode(str_replace('+',' ',$item));}
        if(mb_strlen($allowed['q'])>120||!in_array($allowed['current'],['','present','absent'],true)||!in_array($allowed['upcoming'],['','present','absent'],true))throw new BadRequestHttpException();$this->positive($allowed['page']);$kept=array_intersect_key($allowed,$seen);$parts=[];foreach($kept as$key=>$item)$parts[]=$key.'='.$this->queryValue($item);return'/pilot/installers'.($parts===[]?'':'?'.implode('&',$parts));
    }
    private function queryValue(string$value):string{return preg_replace_callback('/[^\pL\pN_.~-]/u',static fn(array$match):string=>rawurlencode($match[0]),$value)??throw new BadRequestHttpException();}
    private function stableCsrf(string$html):string
    {
        $masked=Yii::$app->request->csrfToken;if(!is_string($masked)||$masked==='')return$html;$raw=Yii::$app->security->unmaskToken($masked);if($raw==='')return$html;$mask=substr(hash('sha256','installer-utilization-card-v1'.$raw,true),0,strlen($raw));$stable=rtrim(strtr(base64_encode($mask.($mask^$raw)),'+/','-_'),'=');return str_replace('value="'.htmlspecialchars($masked,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'"','value="'.$stable.'"',$html);
    }
}
