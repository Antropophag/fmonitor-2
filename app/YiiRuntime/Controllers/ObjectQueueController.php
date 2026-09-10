<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\YiiRuntime\InstallationProcessFactory;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;
use yii\web\UnprocessableEntityHttpException;

final class ObjectQueueController extends PilotController
{
    public $layout = false;

    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => [
                'index' => ['GET', 'HEAD'],
                'schedule' => ['POST'],
            ]],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
                'denyCallback' => function (): void {
                    Yii::$app->user->setReturnUrl(Yii::$app->request->url);
                    Yii::$app->response->statusCode = 303;
                    Yii::$app->response->headers->set('Location', '/pilot/login');
                },
            ],
        ];
    }

    public function actionIndex(): string
    {
        $request = Yii::$app->request;
        $query = $request->get('q', '');
        $status = $request->get('status', '');
        $page = $request->get('page', '1');
        if (!is_string($query) || !is_string($status) || !is_string($page)
            || filter_var($page, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new \RuntimeException('Queue unavailable.');
        }
        try {
            $result = $this->queue()->read((int) Yii::$app->user->id, $query, $status, (int) $page);
        } catch (\DomainException) {
            throw new ForbiddenHttpException();
        }
        $candidate = $request->get('inspectionScheduled', '');
        $notice = is_string($candidate) && self::validDate($candidate) ? $candidate : '';
        return $this->render('@app/app/YiiRuntime/Views/objects', $result + [
            'identity' => Yii::$app->user->identity,
            'canSchedule' => Yii::$app->canonicalAccess->checkAccess(
                (int) Yii::$app->user->id,
                'inspection.schedule',
            ),
            'notice' => $notice,
            'today' => (new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'),
        ]);
    }

    public function actionSchedule(int $id): Response
    {
        $date = Yii::$app->request->post('inspectionDate', '');
        if (!is_string($date)) {
            throw new BadRequestHttpException();
        }
        $result = $this->planning()->scheduleInspection((int) Yii::$app->user->id, $id, $date);
        if ($result['status'] === 'access_denied') {
            throw new ForbiddenHttpException();
        }
        if ($result['status'] === 'invalid_date') {
            throw new UnprocessableEntityHttpException();
        }
        if ($result['status'] === 'ineligible') {
            $response = Yii::$app->response;
            $response->statusCode = 409;
            $response->content = "Объект недоступен для планирования.\n";
            return $response;
        }
        $response = Yii::$app->response;
        $response->statusCode = 303;
        $response->headers->set('Location', '/pilot/objects?inspectionScheduled=' . rawurlencode($date));
        return $response;
    }

    private function queue(): object
    {
        return InstallationProcessFactory::queue(Yii::$app->db, $this->prefix(), $this->legacyPrefix());
    }

    private function planning(): object
    {
        return InstallationProcessFactory::planning(Yii::$app->db, $this->prefix());
    }

    private function prefix(): string
    {
        return (string) (getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: '');
    }

    private function legacyPrefix(): string
    {
        return (string) (getenv('FMONITOR_LEGACY_TABLE_PREFIX') ?: '');
    }

    private static function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('Europe/Moscow'));
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
