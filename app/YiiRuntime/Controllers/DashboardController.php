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
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD']]],
        ];
    }

    public function actionIndex(): string
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        $actorId = (int) Yii::$app->user->id;
        if (!Yii::$app->canonicalAccess->checkAccess($actorId, 'objects.read')) {
            throw new ForbiddenHttpException();
        }
        $cutoff = $this->now()->format('Y-m-d');
        try {
            $result = InstallationProcessFactory::dashboard(
                Yii::$app->db,
                (string) (getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: ''),
                (string) (getenv('FMONITOR_LEGACY_TABLE_PREFIX') ?: ''),
            )->read($actorId, $cutoff);
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
