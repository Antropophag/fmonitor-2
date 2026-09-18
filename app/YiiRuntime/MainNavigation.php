<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use yii\helpers\Html;

final class MainNavigation
{
    public static function render(object $identity, string $currentSection): string
    {
        $actorId = (int) $identity->id;
        $access = \Yii::$app->canonicalAccess;
        $feedbackUrl = '/pilot/feedback?from=' . rawurlencode('/' . ltrim(\Yii::$app->request->pathInfo, '/'));

        $html = '<nav class="fm2-primary-nav" aria-label="Основная навигация">';
        $html .= '<span class="fm2-nav-group">Монтаж</span>';
        foreach ([
            ['objects.read', 'objects', '/pilot/objects', 'Объекты монтажа', 'M4 20V8l8-5 8 5v12H4Zm5 0v-6h6v6'],
            ['installers.read', 'installers', '/pilot/installers', 'Монтажники', 'M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm8 0a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3 20c.2-4 1.8-6 5-6s4.8 2 5 6H3Zm10 0c-.1-2.4-.8-4.2-2-5.2.9-.5 2-.8 3-.8 3.2 0 4.8 2 5 6h-6Z'],
            ['construction_control.read', 'construction-control', '/pilot/construction-control', 'Стройконтроль', 'M4 19h16M6 16V8h12v8M9 8V5h6v3'],
            ['otiz.manage', 'otiz', '/pilot/otiz', 'ОТиЗ', 'M5 4h14v16H5V4Zm3 4h8M8 12h3m2 0h3M8 16h3m2 0h3'],
        ] as $link) {
            if ($access->checkAccess($actorId, $link[0])) {
                $html .= self::link($link[1], $link[2], $link[3], $link[4], $currentSection);
            }
        }

        if ($access->checkAccess($actorId, 'access.administer')) {
            $html .= '<span class="fm2-nav-group">Администрирование</span>';
            $html .= self::link('admin-users', '/pilot/admin/users', 'Пользователи', 'M10.6 2h2.8l.6 2.5 2 .8 2.2-1.3 2 2-1.3 2.2.8 2 2.3.6v2.8l-2.3.6-.8 2 1.3 2.2-2 2-2.2-1.3-2 .8-.6 2.5h-2.8l-.6-2.5-2-.8-2.2 1.3-2-2 1.3-2.2-.8-2-2.3-.6v-2.8l2.3-.6.8-2L3.8 6l2-2L8 5.3l2-.8.6-2.5Z', $currentSection);
            $html .= self::link('admin-roles', '/pilot/admin/roles', 'Роли', 'M6 3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm12 0a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7ZM2 19.5c.3-4.2 1.7-6.5 4-6.5s3.7 2.3 4 6.5H2Zm12 0c.3-4.2 1.7-6.5 4-6.5s3.7 2.3 4 6.5h-8Z', $currentSection);
        }

        $html .= self::link('feedback', $feedbackUrl, 'Обратная связь', 'M4 5h16v12H8l-4 3V5Zm4 4h8M8 13h5', $currentSection, true);

        return $html . '</nav>';
    }

    private static function link(string $section, string $href, string $label, string $icon, string $currentSection, bool $outline = false): string
    {
        $current = $section === $currentSection ? ' aria-current="page"' : '';
        $stroke = $outline ? ' fill="none" stroke="currentColor" stroke-width="1.7"' : '';
        return '<a class="fm2-nav-item" href="' . Html::encode($href) . '"' . $current . ' aria-label="' . Html::encode($label) . '">'
            . '<svg class="fm2-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="' . Html::encode($icon) . '"' . $stroke . '/></svg>'
            . '<span class="fm2-nav-text">' . Html::encode($label) . '</span></a>';
    }
}
