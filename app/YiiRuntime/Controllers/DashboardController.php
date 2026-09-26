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
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
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
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD'],'observation'=>['GET','HEAD'],'forecast'=>['GET','HEAD']]],
        ];
    }

    public function actionIndex(): string
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        $actorId = (int) Yii::$app->user->id;
        $cutoff = $this->now()->format('Y-m-d');
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
        try{$result['installerForecast']=$this->utilization()->forecast($cutoff,0);$result['installerForecastUnavailable']=false;}catch(Throwable$error){Yii::warning('installer_forecast_unavailable '.$error::class,__METHOD__);$result['installerForecast']=null;$result['installerForecastUnavailable']=true;}
        return $this->render('@app/app/YiiRuntime/Views/dashboard', $result + [
            'identity' => Yii::$app->user->identity,
            'unavailable' => false,
        ]);
    }

    public function actionForecast(string$weekStart,string$bucket):string
    {
        $date=\DateTimeImmutable::createFromFormat('!Y-m-d',$weekStart,new \DateTimeZone(self::ZONE));if($date===false||$date->format('Y-m-d')!==$weekStart||$date->format('N')!=='1')throw new NotFoundHttpException();
        try{$detail=$this->utilization()->forecastDetail($weekStart,$bucket,0);}catch(\OutOfBoundsException|\InvalidArgumentException){throw new NotFoundHttpException();}catch(Throwable){Yii::$app->response->statusCode=503;return'';}
        return$this->render('@app/app/YiiRuntime/Views/installer-utilization-forecast',$detail+['identity'=>Yii::$app->user->identity]);
    }

    private function utilization():MariaDbInstallerUtilization{return new MariaDbInstallerUtilization(Yii::$app->db,(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:''),(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:''));}

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
}
