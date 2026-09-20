<?php

declare(strict_types=1);

namespace FMonitor2\YiiRuntime;

use RuntimeException;
use yii\helpers\Html;

final class MainNavigation
{
    public static function render(object $identity, ?string $currentSection = null): string
    {
        $actorId = (int) $identity->id;
        $access = \Yii::$app->canonicalAccess;
        $groups = [
            'Монтаж' => [
                ['objects.read', 'dashboard', '/pilot/dashboard', 'Дашборд', 'pie-chart'],
                ['objects.read', 'objects', '/pilot/objects', 'Объекты монтажа', 'circle-grid-interface-sidebar'],
                ['construction_control.read', 'construction-control', '/pilot/construction-control', 'Стройконтроль', 'setting-tool-circle'],
                ['objects.read', 'calendar', '/pilot/calendar', 'Календарь', 'circle-grid-interface-sidebar'],
                ['otiz.manage', 'otiz', '/pilot/otiz', 'ОТиЗ', 'pie-chart'],
                ['installers.read', 'installers', '/pilot/installers', 'Монтажники', 'user-sidebar'],
            ],
            'Администрирование' => [
                ['access.administer', 'admin-users', '/pilot/admin/users', 'Пользователи', 'user-1'],
                ['access.administer', 'admin-roles', '/pilot/admin/roles', 'Роли', 'book'],
            ],
        ];

        $html = '<nav class="fm2-primary-nav" aria-label="Основная навигация">';
        foreach ($groups as $label => $links) {
            $permitted = array_values(array_filter($links, static fn(array $link): bool => $access->checkAccess($actorId, $link[0])));
            if ($permitted === []) {
                continue;
            }
            $html .= '<span class="fm2-nav-group">' . Html::encode($label) . '</span>';
            foreach ($permitted as $link) {
                $html .= self::link($link[1], $link[2], $link[3], $link[4], $currentSection);
            }
        }

        $html .= '</nav>';
        if (\Yii::$app->request->pathInfo !== 'pilot/feedback') {
            $from = '/' . ltrim(\Yii::$app->request->pathInfo, '/');
            $html .= '<a class="fm2-feedback-fab" href="/pilot/feedback?from=' . rawurlencode($from) . '" aria-label="Обратная связь" data-shlz-icon="chat">'
                . self::icon('chat', 'fm2-feedback-fab-icon') . '</a>';
        }
        return $html;
    }

    public static function icon(string $name, string $class = 'fm2-nav-icon fm2-nav-icon--shlz'): string
    {
        if (!preg_match('/^[a-z0-9-]+$/', $name)) {
            throw new RuntimeException('Invalid shlz-ui icon name');
        }
        $path = __DIR__ . '/Assets/shlz-icons/' . $name . '.svg';
        $svg = file_get_contents($path);
        if (!is_string($svg)) {
            throw new RuntimeException('Missing pinned shlz-ui icon: ' . $name);
        }
        $svg = preg_replace('/<svg\b/', '<svg class="' . Html::encode($class) . '" aria-hidden="true" data-shlz-icon="' . Html::encode($name) . '"', $svg, 1);
        return is_string($svg) ? trim($svg) : '';
    }

    public static function collapseControl(): string
    {
        return '<details class="fm2-nav-state" open><summary class="fm2-nav-trigger" aria-label="Свернуть меню" data-shlz-icon="chevron-left-duo">'
            . self::icon('chevron-left-duo', 'fm2-nav-trigger-icon fm2-nav-trigger-icon--collapse')
            . self::icon('chevron-right-duo', 'fm2-nav-trigger-icon fm2-nav-trigger-icon--expand')
            . '<span class="fm2-nav-trigger-text">Свернуть меню</span></summary></details>';
    }

    private static function link(string $section, string $href, string $label, string $icon, ?string $currentSection): string
    {
        $current = $section === $currentSection ? ' aria-current="page"' : '';
        return '<a class="fm2-nav-item" href="' . Html::encode($href) . '"' . $current . ' aria-label="' . Html::encode($label) . '">'
            . self::icon($icon) . '<span class="fm2-nav-text">' . Html::encode($label) . '</span></a>';
    }
}
