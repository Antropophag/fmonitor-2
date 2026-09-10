<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\Workforce\MariaDbYiiInstallerDirectory;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

final class InstallerDirectoryController extends PilotController
{
    public $layout = false;

    public function behaviors(): array
    {
        return [
            'verbs' => ['class' => VerbFilter::class, 'actions' => ['index' => ['GET', 'HEAD']]],
            'access' => ['class' => AccessControl::class, 'rules' => [['allow' => true, 'roles' => ['@']]], 'denyCallback' => function (): void {
                Yii::$app->user->setReturnUrl(Yii::$app->request->url);
                Yii::$app->response->statusCode = 303;
                Yii::$app->response->headers->set('Location', '/pilot/login');
            }],
        ];
    }

    public function actionIndex(): string|Response
    {
        $filters = $this->filters();
        if (!Yii::$app->canonicalAccess->checkAccess((int) Yii::$app->user->id, 'installers.read')) {
            throw new ForbiddenHttpException();
        }
        try {
            $model = (new MariaDbYiiInstallerDirectory(
                Yii::$app->db,
                (string) (getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: ''),
                (string) (getenv('FMONITOR_LEGACY_TABLE_PREFIX') ?: ''),
            ))->read((new \DateTimeImmutable('now', new \DateTimeZone('Europe/Moscow')))->format('Y-m-d'), $filters);
            return $this->render('@app/app/YiiRuntime/Views/installers', $model + [
                'identity' => Yii::$app->user->identity,
                'canAdmin' => Yii::$app->canonicalAccess->checkAccess((int) Yii::$app->user->id, 'access.administer'),
                'canControl' => Yii::$app->canonicalAccess->checkAccess((int) Yii::$app->user->id, 'construction_control.read'),
            ]);
        } catch (\OutOfRangeException) {
            throw new BadRequestHttpException();
        } catch (\Throwable) {
            $response = Yii::$app->response;
            $response->statusCode = 503;
            $response->format = Response::FORMAT_RAW;
            $response->headers->set('Retry-After', '60');
            $response->content = "Service unavailable.\n";
            return $response;
        }
    }

    private function filters(): array
    {
        $raw = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $values = ['q' => '', 'status' => '', 'availability' => '', 'page' => '1'];
        $seen = [];
        if ($raw !== '') {
            foreach (explode('&', $raw) as $part) {
                if ($part === '' || !str_contains($part, '=')) throw new BadRequestHttpException();
                [$encodedKey, $encodedValue] = explode('=', $part, 2);
                if (preg_match('/%(?![0-9A-Fa-f]{2})/', $part) === 1) throw new BadRequestHttpException();
                $key = rawurldecode($encodedKey);
                if (!array_key_exists($key, $values) || isset($seen[$key]) || str_contains($key, '[')) throw new BadRequestHttpException();
                $seen[$key] = true;
                $values[$key] = rawurldecode(str_replace('+', ' ', $encodedValue));
            }
        }
        $values['q'] = trim($values['q']);
        if (mb_strlen($values['q']) > 120
            || !in_array($values['status'], ['', 'employed', 'dismissed'], true)
            || !in_array($values['availability'], ['', 'assigned', 'free'], true)
            || preg_match('/^[1-9][0-9]*$/D', $values['page']) !== 1 || strlen($values['page']) > 18
        ) throw new BadRequestHttpException();
        $digits = preg_replace('/[^0-9]+/', '', $values['q']) ?? '';
        $digits = ltrim($digits, '0');
        return ['q' => $values['q'], 'tab' => $digits, 'status' => $values['status'], 'availability' => $values['availability'], 'page' => (int) $values['page']];
    }
}
