<?php

declare(strict_types=1);

final class RapidPilotShell
{
    public static function decorate(string $html, string $csrf, bool $calendarActive, bool $otizAllowed, bool $otizActive): string
    {
        if ($otizAllowed) $html = RapidPilotOtiz::decorateNavigation($html, $otizActive);
        $html = RapidPilotCalendar::decorateNavigation($html, $calendarActive);
        $html = self::distillNavigation($html);
        $html = str_replace(
            ['<span class="fm2-logo-name">FMonitor</span>', 'aria-label="FMonitor — объекты монтажа"', ' · FMonitor</title>'],
            ['<span class="fm2-logo-name">FMonitor 2.0</span>', 'aria-label="FMonitor 2.0 — объекты монтажа"', ' · FMonitor 2.0</title>'],
            $html
        );

        $token = htmlspecialchars($csrf, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logoutIcon = '<svg class="fm2-user-logout-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="/pilot/assets/shlz-icons.svg#shlz-icon-logout"/></svg>';
        $logout = '<form method="post" action="/pilot/logout" class="fm2-logout-form"><input type="hidden" name="csrfToken" value="' . $token . '"><button class="fm2-logout" type="submit">' . $logoutIcon . '<span class="fm2-logout-text">Выйти</span></button></form>';

        $preloader = '<div class="fm2-preloader" data-fm2-preloader role="status" aria-live="polite" aria-label="FMonitor загружается"><div class="fm2-preloader__lockup"><svg class="fm2-preloader__mark" viewBox="0 0 76 76" aria-hidden="true"><rect width="76" height="76" rx="16" fill="#fff"/><rect x="18" y="15" width="8" height="46" rx="2" fill="#253d98"/><rect x="50" y="15" width="8" height="46" rx="2" fill="#253d98"/><rect class="fm2-preloader__lift" x="30" y="28" width="16" height="20" rx="2" fill="#6f8cff"/></svg><p class="fm2-preloader__name">FMonitor <span>2.0</span></p><div class="fm2-preloader__track" aria-hidden="true"></div><p class="fm2-preloader__status">Готовим рабочее пространство</p></div></div>';

        $html = str_replace('<!-- fm2-user-actions -->', $logout, $html);
        $html = str_replace('<link rel="stylesheet" href="/pilot/assets/shlz.css">', '<script src="/pilot/assets/preloader.js"></script><link rel="stylesheet" href="/pilot/assets/shlz.css">', $html);

        return str_replace('<body class="shlz-scope">', '<body class="shlz-scope">' . $preloader, $html);
    }

    private static function distillNavigation(string $html): string
    {
        $html = preg_replace(
            '#<span class="fm2-nav-item fm2-nav-item--muted" aria-disabled="true"><svg.*?</svg><span class="fm2-nav-text">.*?</span></span>#s',
            '',
            $html
        ) ?? $html;
        $html = preg_replace(
            '#(<a class="fm2-nav-item" href="/pilot/construction-control"[^>]*>.*?</a>)(<a class="fm2-nav-item" href="/pilot/objects"[^>]*>.*?</a>)#s',
            '$2$1',
            $html,
            1
        ) ?? $html;
        return preg_replace(
            '#<span class="fm2-nav-group">([^<]+)</span>(?=<span class="fm2-nav-group">|</nav>)#',
            '',
            $html
        ) ?? $html;
    }
}
