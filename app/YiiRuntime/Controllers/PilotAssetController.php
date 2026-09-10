<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime\Controllers;

use Yii;
use yii\web\NotFoundHttpException;
use yii\web\Response;

final class PilotAssetController extends PilotController
{
    public function actionFile(string$path): Response
    {
        $root = dirname(__DIR__) . '/Assets';
        $map = [];
        foreach (['navigation.js','preloader.js','users.js','object-queue.js','inspection-schedule.js'] as $file) {
            $map[$file] = [$root . '/' . $file,'text/javascript; charset=UTF-8',3600];
        }foreach (['shlz.css','pilot.css'] as $file) {
            $map[$file] = [$root . '/' . $file,'text/css; charset=UTF-8',3600];
        }$map['favicon.svg'] = [$root . '/favicon.svg','image/svg+xml; charset=UTF-8',31536000];
        if (preg_match('/^fonts\/(golos-text-(?:cyrillic|latin)-(?:400|500|600)-normal\.woff2)$/D', $path, $m) === 1) {
            $entry = [$root . '/fonts/' . $m[1],'font/woff2',31536000];
        } else {
            $entry = $map[$path] ?? null;
        }if (!is_array($entry) || !is_file($entry[0]) || is_link($entry[0])) {
            throw new NotFoundHttpException();
        }$bytes = file_get_contents($entry[0]);
        if (!is_string($bytes)) {
            throw new \RuntimeException();
        }$r = Yii::$app->response;
        $r->format = Response::FORMAT_RAW;
        $r->headers->set('Content-Type', $entry[1]);
        $r->headers->set('Cache-Control', 'public, max-age=' . $entry[2] . ($entry[2] === 31536000 ? ', immutable' : ''));
        $r->headers->set('X-Content-Type-Options', 'nosniff');
        $r->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $r->content = $bytes;
        return$r;
    }
}
