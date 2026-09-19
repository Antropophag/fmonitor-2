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
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\ServiceUnavailableHttpException;

final class CalendarController extends PilotController
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

    public function actionIndex(string $slash = ''): string
    {
        Yii::$app->response->headers->set('Cache-Control', 'no-store');
        $actorId = (int) Yii::$app->user->id;
        if (!Yii::$app->canonicalAccess->checkAccess($actorId, 'objects.read')) {
            throw new ForbiddenHttpException();
        }

        $query = Yii::$app->request->queryParams;
        unset($query['slash']);
        if (array_diff(array_keys($query), ['date']) !== []) {
            throw new BadRequestHttpException('Некорректные параметры календаря.');
        }
        $today = $this->now()->setTime(0, 0);
        $first = $today->modify('-30 days');
        $last = $today->modify('+6 months');
        $selected = $today;
        if (array_key_exists('date', $query)) {
            if (!is_string($query['date'])) {
                throw new BadRequestHttpException('Укажите одну дату в формате ГГГГ-ММ-ДД.');
            }
            $selected = DateTimeImmutable::createFromFormat('!Y-m-d', $query['date'], new DateTimeZone(self::ZONE));
            if ($selected === false || $selected->format('Y-m-d') !== $query['date'] || $selected < $first || $selected > $last) {
                throw new BadRequestHttpException('Выбранный день не входит в доступный период календаря.');
            }
        }

        try {
            $events = InstallationProcessFactory::calendar(
                Yii::$app->db,
                $this->prefix(),
                $this->legacyPrefix(),
            )->readCalendar($first->format('Y-m-d'), $last->format('Y-m-d'));
        } catch (Throwable $error) {
            Yii::error('calendar_read_failed ' . $error::class, __METHOD__);
            throw new ServiceUnavailableHttpException('Календарь временно недоступен. Обновите страницу или вернитесь к объектам монтажа.');
        }

        return $this->render('@app/app/YiiRuntime/Views/calendar', [
            'identity' => Yii::$app->user->identity,
            'today' => $today,
            'first' => $first,
            'last' => $last,
            'selected' => $selected,
            'events' => $events,
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

    private function prefix(): string
    {
        $prefix = (string) (getenv('FMONITOR_PROCESS_TABLE_PREFIX') ?: '');
        if (preg_match('/^[A-Za-z0-9_]+$/D', $prefix) !== 1) throw new \RuntimeException('Invalid table prefix.');
        return $prefix;
    }

    private function legacyPrefix(): string
    {
        $prefix = (string) (getenv('FMONITOR_LEGACY_TABLE_PREFIX') ?: '');
        if (preg_match('/^[A-Za-z0-9_]*$/D', $prefix) !== 1) throw new \RuntimeException('Invalid legacy table prefix.');
        return $prefix;
    }
}
