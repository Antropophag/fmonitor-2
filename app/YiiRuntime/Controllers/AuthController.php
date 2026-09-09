<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Response;

final class AuthController extends PilotController
{
    public $layout=false;
    public function behaviors():array{return['verbs'=>['class'=>VerbFilter::class,'actions'=>['logout'=>['POST']]]];}
    public function actionLogin():string|Response
    {
        Yii::$app->session->open();
        if(!Yii::$app->user->isGuest)return$this->localRedirect('/pilot/objects');
        $request=Yii::$app->request;$email=$this->normalize((string)$request->post('email',''));$password=$request->post('password');$stage='email';$error='';$name='';
        if($request->isPost&&$this->validEmail($email)){
            try{$store=$this->store();if($store->rateLimited($email)){$store->fail($email);$error=$this->neutral();}else{$found=$store->findForLogin($email);if($found===null){$store->fail($email);$error=$this->neutral();}else{$stage='password';$name=$found['identity']->displayName;if(is_string($password)){if(strlen($password)>200||$password===''||!password_verify($password,$found['passwordHash'])){$store->fail($email);$error=$this->neutral();}else{$store->succeed($email);Yii::$app->user->login($found['identity'],0);$return=Yii::$app->user->getReturnUrl('/pilot/objects');Yii::$app->user->setReturnUrl('/pilot/objects');return$this->localRedirect($this->safeReturn((string)$return));}}}}}catch(\Throwable$e){throw new \RuntimeException('Authentication unavailable.',0,$e);}
        }elseif($request->isPost)$error='Введите рабочий email в домене @shlz.ru.';
        return $this->render('@app/app/YiiRuntime/Views/login',['stage'=>$stage,'email'=>$email,'error'=>$error,'name'=>$name]);
    }
    public function actionLogout():Response{Yii::$app->user->logout();return$this->localRedirect('/pilot/login');}
    private function store():MariaDbYiiLocalIdentityStore{return Yii::$app->localIdentity;}
    private function normalize(string$email):string{return mb_strtolower(trim($email));}
    private function validEmail(string$email):bool{return filter_var($email,FILTER_VALIDATE_EMAIL)!==false&&preg_match('/^[^@]+@shlz\.ru$/Di',$email)===1;}
    private function safeReturn(string$url):string{return preg_match('#^/pilot/(?!assets(?:/|$)|login(?:/|$)|logout(?:/|$))[A-Za-z0-9/_?&=.%~-]*$#D',$url)===1?$url:'/pilot/objects';}
    private function neutral():string{return'Не удалось войти. Проверьте email и данные доступа.';}
    private function localRedirect(string$url):Response{$response=Yii::$app->response;$response->statusCode=303;$response->headers->set('Location',$url);return$response;}
}
