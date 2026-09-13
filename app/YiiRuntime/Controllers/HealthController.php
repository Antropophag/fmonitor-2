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
        return $this->json("{\"ok\":true}\n");
    }

    public function actionReady(): Response
    {
        try {
            RuntimeReadiness::assertReady(RuntimeConfiguration::fromEnvironment(getenv()));
            return $this->json("{\"ok\":true}\n");
        } catch (\Throwable) {
            $response = $this->json("{\"ok\":false,\"reason\":\"SERVICE_UNAVAILABLE\"}\n");
            $response->statusCode = 503;
            return $response;
        }
    }

    private function json(string $content): Response
    {
        $response = $this->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->content = $content;
        return $response;
    }
}
