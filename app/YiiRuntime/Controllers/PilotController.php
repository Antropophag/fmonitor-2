<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
abstract class PilotController extends Controller
{
    public function beforeAction($action):bool
    {
        $trusted=getenv('FMONITOR_TRUSTED_REQUEST_HOST');$received=$_SERVER['HTTP_HOST']??null;
        if(!is_string($trusted)||$trusted===''||!is_string($received)||!hash_equals($trusted,$received))throw new BadRequestHttpException();
        return parent::beforeAction($action);
    }
}
