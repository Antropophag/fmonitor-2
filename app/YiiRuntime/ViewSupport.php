<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use FMonitor2\YiiRuntime\Assets\PreopeningAssetBundle;
use yii\helpers\Html;
use yii\web\View;

final class ViewSupport
{
    public static function begin(View $view, string $title, object $identity): void
    {
        PreopeningAssetBundle::register($view);
        $csrf = Html::hiddenInput(\Yii::$app->request->csrfParam, \Yii::$app->request->csrfToken);
        $parts = preg_split('/\s+/u', trim((string) $identity->displayName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        $canAdmin = \Yii::$app->canonicalAccess->checkAccess((int) $identity->id, 'access.administer');
        $canControl = \Yii::$app->canonicalAccess->checkAccess((int) $identity->id, 'construction_control.read');
        $view->beginPage();
        ?><!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= Html::encode($title) ?> · FMonitor 2.0</title>
    <link rel="icon" href="/pilot/assets/favicon.svg">
    <?php $view->head() ?>
</head>
<body class="shlz-scope">
<?php $view->beginBody() ?>
<a class="fm2-skip shlz-link" href="#main-content">Перейти к содержанию</a>
<div class="fm2-shell">
    <aside class="fm2-sidebar">
        <div class="fm2-nav-body">
            <a class="fm2-logo" href="/pilot/objects" aria-label="FMonitor 2.0">
                <svg class="fm2-logo-mark" viewBox="0 0 32 32" aria-hidden="true"><rect class="fm2-logo-rail" x="5" y="4" width="4" height="24"/><rect class="fm2-logo-rail" x="23" y="4" width="4" height="24"/><rect class="fm2-logo-progress" x="11" y="10" width="10" height="12"/></svg>
                <span class="fm2-logo-name">FMonitor 2.0</span>
            </a>
            <nav class="fm2-primary-nav" aria-label="Основная навигация">
                <span class="fm2-nav-group">Монтаж</span>
                <a class="fm2-nav-item" href="/pilot/objects" aria-current="page" aria-label="Объекты монтажа">
                    <svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V8l8-5 8 5v12H4Zm5 0v-6h6v6"/></svg>
                    <span class="fm2-nav-text">Объекты монтажа</span>
                </a>
                <?php if ($canControl): ?><a class="fm2-nav-item" href="/pilot/construction-control" aria-label="Стройконтроль"><svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16M6 16V8h12v8M9 8V5h6v3"/></svg><span class="fm2-nav-text">Стройконтроль</span></a><?php endif ?>
                <?php if ($canAdmin): ?>
                    <span class="fm2-nav-group">Администрирование</span>
                    <a class="fm2-nav-item" href="/pilot/admin/users" aria-label="Пользователи"><svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10.6 2h2.8l.6 2.5 2 .8 2.2-1.3 2 2-1.3 2.2.8 2 2.3.6v2.8l-2.3.6-.8 2 1.3 2.2-2 2-2.2-1.3-2 .8-.6 2.5h-2.8l-.6-2.5-2-.8-2.2 1.3-2-2 1.3-2.2-.8-2-2.3-.6v-2.8l2.3-.6.8-2L3.8 6l2-2L8 5.3l2-.8.6-2.5Z"/></svg><span class="fm2-nav-text">Пользователи</span></a>
                    <a class="fm2-nav-item" href="/pilot/admin/roles" aria-label="Роли"><svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm12 0a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM2 19.5c.3-4.2 1.7-6.5 4-6.5s3.7 2.3 4 6.5H2Zm12 0c.3-4.2 1.7-6.5 4-6.5s3.7 2.3 4 6.5h-8Z"/></svg><span class="fm2-nav-text">Роли</span></a>
                <?php endif ?>
            </nav>
        </div>
        <div class="fm2-sidebar-user">
            <span class="shlz-avatar shlz-avatar--32 fm2-sidebar-avatar"><?= Html::encode($initials) ?></span>
            <span class="fm2-sidebar-user-copy"><strong><?= Html::encode($identity->displayName) ?></strong><small><?= Html::encode($identity->email) ?></small></span>
            <form method="post" action="/pilot/logout" class="fm2-logout-form"><?= $csrf ?><button class="fm2-logout" type="submit" aria-label="Выйти"><svg class="fm2-user-logout-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4H5v16h5M14 8l4 4-4 4m4-4H9" fill="none" stroke="currentColor" stroke-width="1.7"/></svg><span class="fm2-logout-text">Выйти</span></button></form>
        </div>
        <details class="fm2-nav-state" open><summary class="fm2-nav-trigger"><span class="fm2-nav-trigger-text">Свернуть меню</span></summary></details>
    </aside>
    <div class="fm2-workspace"><div class="fm2-main" id="main-content" tabindex="-1" role="main">
<?php
    }

    public static function end(View $view): void
    {
        ?></div></div></div><?php
        $view->endBody();
        ?></body></html><?php
        $view->endPage();
    }

    public static function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
        $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
