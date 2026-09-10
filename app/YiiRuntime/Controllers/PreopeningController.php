<?php
declare(strict_types=1);
namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\IdentityAccess\MariaDbYiiLocalIdentityStore;
use Yii;
use yii\filters\AccessControl;
use yii\web\Response;

abstract class PreopeningController extends PilotController
{
    public $layout = false;

    public function behaviors(): array
    {
        return ['access' => [
            'class' => AccessControl::class,
            'rules' => [['allow' => true, 'roles' => ['@']]],
            'denyCallback' => function (): void {
                Yii::$app->user->setReturnUrl(Yii::$app->request->url);
                Yii::$app->response->statusCode = 303;
                Yii::$app->response->headers->set('Location', '/pilot/login');
            },
        ]];
    }

    public function beforeAction($action): bool
    {
        foreach (['id', 'orderId'] as $key) {
            $value = Yii::$app->requestedParams[$key] ?? null;
            if ($value !== null && $this->canonicalId($value) === null) throw new \yii\web\NotFoundHttpException();
        }
        return parent::beforeAction($action);
    }

    protected function actor(): int { return (int) Yii::$app->user->id; }
    protected function cap(string $capability): bool { return Yii::$app->canonicalAccess->checkAccess($this->actor(), $capability); }
    protected function processCap(string $capability): bool { return $this->cap($capability) && $this->hasProcessRole(); }

    protected function roleCodes(): array
    {
        $store = Yii::$app->localIdentity;
        if (!$store instanceof MariaDbYiiLocalIdentityStore) throw new \RuntimeException('Identity store unavailable.');
        return $store->activeRoleCodes($this->actor());
    }

    protected function hasProcessRole(): bool
    {
        return array_intersect($this->roleCodes(), ['fkr_operator', 'manager']) !== [];
    }

    protected function canonicalId(string|int $value): ?int
    {
        $text = (string) $value;
        if (!preg_match('/^[1-9][0-9]*$/D', $text)) return null;
        $id = filter_var($text, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    protected function status(int $status, bool $retry = false): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->statusCode = $status;
        if ($retry) $response->headers->set('Retry-After', '60');
        $response->content = match ($status) {
            400 => "Bad request.\n", 403 => "Access denied.\n", 404 => "Not found.\n",
            410 => "Gone.\n", 413 => "Request too large.\n",
            422 => "Действие отклонено. Проверьте актуальные данные и повторите.\n",
            default => "Service unavailable.\n",
        };
        return $response;
    }

    protected function methodNotAllowed(string $allow): Response
    {
        $response = $this->status(405);
        $response->headers->set('Allow', $allow);
        return $response;
    }

    protected function redirect303(string $url): Response
    {
        $response = Yii::$app->response;
        $response->statusCode = 303;
        $response->headers->set('Location', $url);
        return $response;
    }

    protected function json(int $status, array $data, bool $retry = false): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->statusCode = $status;
        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        if ($retry) $response->headers->set('Retry-After', '60');
        $response->content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        return $response;
    }

    protected function domain(array $result): Response
    {
        $reason = $result['reasonCode'] ?? '';
        $status = match (true) {
            ($result['status'] ?? '') === 'failed' => 503,
            $reason === 'SERVICE_UNAVAILABLE' => 503,
            ($result['status'] ?? '') === 'conflict' => 409,
            $reason === 'authorization_denied' || $reason === 'ACCESS_DENIED' => 403,
            in_array($reason, ['object_not_found', 'order_not_found', 'NOT_FOUND'], true) => 404,
            default => 422,
        };
        return $this->status($status, $status === 503);
    }

    protected function pageError(int $status, string $message, string $backUrl, string $backLabel = 'Вернуться'): string|Response
    {
        $response = Yii::$app->response;
        $response->statusCode = $status;
        if ($status === 503) $response->headers->set('Retry-After', '60');
        return $this->render('@app/app/YiiRuntime/Views/preopening-error', [
            'identity' => Yii::$app->user->identity,
            'title' => $status === 503 ? 'Сервис временно недоступен' : 'Действие не выполнено',
            'message' => $message, 'backUrl' => $backUrl, 'backLabel' => $backLabel,
            'retryable' => $status === 503,
        ]);
    }
}
