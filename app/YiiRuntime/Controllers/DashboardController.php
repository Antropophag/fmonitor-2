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
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD'],'observation'=>['GET','HEAD']]],
        ];
    }

    public function actionIndex(): string
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        $actorId = (int) Yii::$app->user->id;
        if (!Yii::$app->canonicalAccess->checkAccess($actorId, 'objects.read')) {
            throw new ForbiddenHttpException();
        }
        if(!Yii::$app->canonicalAccess->checkAccess($actorId,'installers.read'))throw new ForbiddenHttpException();
        $this->assertFullObjectScope($actorId);
        $cutoff = $this->now()->format('Y-m-d');
        try {
            $result = InstallationProcessFactory::dashboard(
                Yii::$app->db,
                (string) (getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: ''),
                (string) (getenv('FMONITOR_LEGACY_TABLE_PREFIX') ?: ''),
            )->read($actorId, $cutoff);$observations=$this->observations();$result['installerUtilization']=$observations->currentSummary($actorId);$result['installerUtilizationHistory']=$observations->history();
        } catch (\DomainException) {
            throw new ForbiddenHttpException();
        } catch (Throwable $error) {
            Yii::error('dashboard_read_failed ' . $error::class, __METHOD__);
            Yii::$app->response->statusCode = 503;
            return $this->render('@app/app/YiiRuntime/Views/dashboard', [
                'identity' => Yii::$app->user->identity,
                'unavailable' => true,
            ]);
        }
        return $this->render('@app/app/YiiRuntime/Views/dashboard', $result + [
            'identity' => Yii::$app->user->identity,
            'unavailable' => false,
        ]);
    }

    public function actionObservation(string$date,string$series):string
    {
        $actor=(int)Yii::$app->user->id;if(!Yii::$app->canonicalAccess->checkAccess($actor,'objects.read')||!Yii::$app->canonicalAccess->checkAccess($actor,'installers.read'))throw new ForbiddenHttpException();$this->assertFullObjectScope($actor);
        try{$detail=$this->observations()->detail($date,$series);}catch(\OutOfBoundsException|\InvalidArgumentException){throw new NotFoundHttpException();}catch(Throwable){Yii::$app->response->statusCode=503;Yii::$app->response->headers->set('Retry-After','60');return'';}
        return$this->render('@app/app/YiiRuntime/Views/installer-utilization-observation',$detail+['identity'=>Yii::$app->user->identity]);
    }

    private function observations():MariaDbInstallerUtilizationObservations
    {
        mysqli_report(MYSQLI_REPORT_ERROR|MYSQLI_REPORT_STRICT);$db=new \mysqli(getenv('FMONITOR_DB_HOST')?:'127.0.0.1',getenv('FMONITOR_DB_USER')?:'',getenv('FMONITOR_DB_PASSWORD')?:'',getenv('FMONITOR_DB_NAME')?:'',(int)(getenv('FMONITOR_DB_PORT')?:3306));$db->set_charset('utf8mb4');$p=(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:'');return new MariaDbInstallerUtilizationObservations($db,$p,new MariaDbInstallerUtilization(Yii::$app->db,$p,(string)(getenv('FMONITOR_LEGACY_TABLE_PREFIX')?:'')));
    }
    private function assertFullObjectScope(int$actor):void{$p=(string)(getenv('FMONITOR_PROCESS_TABLE_PREFIX')?:'');$restricted=(int)Yii::$app->db->createCommand("SELECT COUNT(*) FROM `{$p}fm2_pilot_user_roles` ur JOIN `{$p}fm2_pilot_roles` r ON r.role_id=ur.role_id AND r.status=1 WHERE ur.user_id=:actor AND BINARY r.code='construction_control_engineer'",[':actor'=>$actor])->queryScalar();if($restricted>0)throw new ForbiddenHttpException();}

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
