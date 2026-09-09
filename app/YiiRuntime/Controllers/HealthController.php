<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use FMonitor2\Runtime\RuntimeConfiguration;
use FMonitor2\Runtime\RuntimeReadiness;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

final class HealthController extends Controller
{
    public function behaviors(): array
    {
        return ['verbs' => ['class' => VerbFilter::class, 'actions' => [
            'live' => ['GET', 'HEAD'], 'ready' => ['GET', 'HEAD'],
        ]]];
    }

    public function actionLive(): Response
    {
        return $this->asJson(['ok' => true]);
    }

    public function actionReady(): Response
    {
        try {
            RuntimeReadiness::assertReady(RuntimeConfiguration::fromEnvironment(getenv()));
            return $this->asJson(['ok' => true]);
        } catch (\Throwable) {
            $response = $this->asJson(['ok' => false, 'reason' => 'SERVICE_UNAVAILABLE']);
            $response->statusCode = 503;
            return $response;
        }
    }
}
