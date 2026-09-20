<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use FMonitor2\Runtime\SafeRuntimeFailure;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\web\Response;

/** Do not serialize native exception messages, configuration or request secrets. */
final class SafeErrorHandler extends ErrorHandler
{
    private ?string $errorId = null;

    public function logException($exception): void
    {
        if ($this->status($exception) >= 500) $this->errorId = SafeRuntimeFailure::report($exception, 'yii_error_handler');
    }

    protected function renderException($exception): void
    {
        $response = Yii::$app->response;
        $status = $this->status($exception);
        if ($status >= 500) {
            $response->headers->removeAll();
            $response->cookies->removeAll();
            header_remove('Location');
            header_remove('Set-Cookie');
            $this->errorId ??= SafeRuntimeFailure::report($exception, 'yii_error_handler');
            $response->headers->set('X-FMonitor-Error-ID', $this->errorId);
        }
        $response->isSent = false;
        $response->stream = null;
        $response->format = Response::FORMAT_JSON;
        $response->statusCode = $status;
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
