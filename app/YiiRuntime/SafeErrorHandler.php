<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use Yii;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\web\Response;

/** Do not serialize native exception messages, configuration or request secrets. */
final class SafeErrorHandler extends ErrorHandler
{
    public function logException($exception): void
    {
        Yii::error(['event' => 'http_failure', 'status' => $this->status($exception)], 'fmonitor.http');
    }

    protected function renderException($exception): void
    {
        $response = Yii::$app->response;
        $response->isSent = false;
        $response->stream = null;
        $response->format = Response::FORMAT_JSON;
        $response->statusCode = $this->status($exception);
        $response->data = ['ok' => false, 'reason' => match ($response->statusCode) {
            404 => 'NOT_FOUND', 405 => 'METHOD_NOT_ALLOWED',
            400 => 'BAD_REQUEST', 403 => 'ACCESS_DENIED',
            default => 'SERVICE_UNAVAILABLE',
        }];
        $response->send();
    }

    private function status(\Throwable $exception): int
    {
        return $exception instanceof HttpException && $exception->statusCode < 500
            ? $exception->statusCode : 503;
    }
}
