<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use yii\web\Controller;
use yii\web\Response;

final class SiteController extends Controller
{
    public function actionIndex(): Response
    {
        $response = $this->response;
        $response->statusCode = 302;
        $response->headers->set('Location', '/pilot/objects');
        return $response;
    }
}
