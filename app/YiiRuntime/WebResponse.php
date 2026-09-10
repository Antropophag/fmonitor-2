<?php
declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use Yii;
use yii\base\Event;
use yii\web\Response;

/** HTTP policy applied through the framework response lifecycle. */
final class WebResponse
{
    public static function closeAndSecure(Event $event): void
    {
        self::closeSession($event);
        self::secure($event);
    }

    public static function closeSession(Event $event): void
    {
        if (!Yii::$app->has('session', true) || !Yii::$app->session->isActive) return;
        Yii::$app->session->close();
    }

    public static function secure(Event $event): void
    {
        /** @var Response $response */
        $response = $event->sender;
        header_remove('X-Powered-By');
        if (!$response->headers->has('Cache-Control')) $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Frame-Options', 'DENY');
        $checklist=preg_match('#^pilot/(?:objects|construction-control/objects)/[1-9][0-9]*/checklist$#D',Yii::$app->request->pathInfo)===1;
        $response->headers->set('Content-Security-Policy', $checklist?"default-src 'none'; script-src 'self'; worker-src 'self'; connect-src 'self'; style-src 'self'; img-src 'self' blob:; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'":"default-src 'none'; script-src 'self'; connect-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
    }

    public static function head(Event $event): void
    {
        if (!Yii::$app->request->getIsHead()) return;
        /** @var Response $response */
        $response = $event->sender;
        $response->headers->set('Content-Length', (string) strlen($response->content ?? ''));
        $response->content = '';
        $response->stream = null;
    }
}
