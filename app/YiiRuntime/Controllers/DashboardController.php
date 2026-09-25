<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use DateTimeImmutable;
use DateTimeZone;
use FMonitor2\YiiRuntime\InstallationProcessFactory;
use Throwable;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use FMonitor2\Workforce\{MariaDbInstallerUtilization,MariaDbInstallerUtilizationObservations};

final class DashboardController extends PilotController
{
    private const ZONE = 'Europe/Moscow';
    public $layout = false;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
                'denyCallback' => function (): void {
                    Yii::$app->user->setReturnUrl(Yii::$app->request->url);
                    Yii::$app->response->statusCode = 303;
                    Yii::$app->response->headers->set('Location', '/pilot/login');
                },
            ],
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD'],'observation'=>['GET','HEAD']]],
        ];
    }

    public function actionIndex(): string
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        $actorId = (int) Yii::$app->user->id;
        $cutoff = $this->now()->format('Y-m-d');
        $requestedTo=Yii::$app->request->get('utilizationTo');
        if($requestedTo!==null&&(!is_string($requestedTo)||!$this->validDate($requestedTo)||$requestedTo>$cutoff))throw new BadRequestHttpException('Invalid utilization period.');
        $utilizationTo=$requestedTo??$cutoff;
        $utilizationFrom=(new DateTimeImmutable($utilizationTo,new DateTimeZone(self::ZONE)))->modify('-41 days')->format('Y-m-d');
        try {
            $result = InstallationProcessFactory::dashboard(
                Yii::$app->db,
                (string) (getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: ''),
                (string) (getenv('FMONITOR_LEGACY_TABLE_PREFIX') ?: ''),
            )->read($actorId, $cutoff);
        } catch (\DomainException) {
            Yii::$app->response->statusCode = 403;
            return '';
        } catch (Throwable $error) {
            Yii::error('dashboard_read_failed ' . $error::class, __METHOD__);
            Yii::$app->response->statusCode = 503;
            return $this->render('@app/app/YiiRuntime/Views/dashboard', [
                'identity' => Yii::$app->user->identity,
                'unavailable' => true,
            ]);
        }
        try{$observations=$this->observations();$result['installerUtilization']=$observations->currentSummary(0);$result['installerUtilizationHistory']=$observations->historyBetween($utilizationFrom,$utilizationTo);$result['installerUtilizationUnavailable']=false;}catch(Throwable$error){Yii::warning('installer_utilization_read_unavailable '.$error::class,__METHOD__);$result['installerUtilization']=null;$result['installerUtilizationHistory']=[];$result['installerUtilizationUnavailable']=true;}
        return $this->render('@app/app/YiiRuntime/Views/dashboard', $result + [
            'identity' => Yii::$app->user->identity,
            'unavailable' => false,
            'utilizationFrom'=>$utilizationFrom,
            'utilizationTo'=>$utilizationTo,
            'utilizationCutoff'=>$cutoff,
        ]);
    }

    public function actionObservation(string$date,string$series):string
    {
        try{$detail=$this->observations()->detail($date,$series);}catch(\OutOfBoundsException|\InvalidArgumentException){throw new NotFoundHttpException();}catch(Throwable){Yii::$app->response->statusCode=503;Yii::$app->response->headers->set('Retry-After','60');return'';}
        return$this->render('@app/app/YiiRuntime/Views/installer-utilization-observation',$detail+['identity'=>Yii::$app->user->identity]);
    }

    private function observations():MariaDbInstallerUtilizationObservations
    {
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new \mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:3306));$db->set_charset('utf8mb4');$p=(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:'');return new MariaDbInstallerUtilizationObservations($db,$p,new MariaDbInstallerUtilization(Yii::$app->db,$p,(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:'')));
    }
    private function now(): DateTimeImmutable
    {
        $fixed = getenv('FMONITOR_NOW');
        try {
            return (new DateTimeImmutable(is_string($fixed) && $fixed !== '' ? $fixed : 'now'))
                ->setTimezone(new DateTimeZone(self::ZONE));
        } catch (Throwable) {
            return new DateTimeImmutable('now', new DateTimeZone(self::ZONE));
        }
    }
    private function validDate(string$value):bool
    {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$value,new DateTimeZone(self::ZONE));
        return $date!==false&&$date->format('Y-m-d')===$value;
    }
}
